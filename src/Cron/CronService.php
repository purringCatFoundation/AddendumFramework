<?php
declare(strict_types=1);

namespace PCF\Addendum\Cron;

use PCF\Addendum\Attribute\Cron as CronAttribute;
use PCF\Addendum\Util\FinderFactory;
use DateTimeImmutable;
use Cron\CronExpression;
use ReflectionClass;
use RuntimeException;
use Throwable;

class CronService
{
    public function __construct(private ScheduleResource $resource, private FinderFactory $finderFactory)
    {
    }

    /**
     * Run due cron jobs.
     */
    public function runScheduled(?string $code = null): void
    {
        $crons = $this->getCronsToSchedule();
        foreach ($this->resource->getScheduled($code) as $item) {
            $cron = $crons->get($item->code);
            if ($cron === null) {
                $this->resource->markFailed($item->id);
                continue;
            }

            $this->resource->markStarted($item->id);
            $class = $cron->className;
            try {
                $factoryClass = $class . 'Factory';
                if (!class_exists($factoryClass) || !method_exists($factoryClass, 'create')) {
                    throw new RuntimeException('Cron factory not found');
                }
                /** @var CronInterface $instance */
                $instance = new $factoryClass()->create();
                $instance->run();
                $this->resource->markCompleted($item->id);
            } catch (Throwable $e) {
                $this->resource->markFailed($item->id);
            }
        }
    }

    /**
     * Schedule upcoming cron jobs.
     */
    public function scheduleJobs(?string $code = null): void
    {
        $toSchedule = $this->getCronsToSchedule();
        $now = new DateTimeImmutable('now');
        foreach ($toSchedule as $cron) {
            if ($code !== null && $cron->code !== $code) {
                continue;
            }
            if (!$cron->enabled) {
                continue;
            }
            $expression = new CronExpression($cron->expression);
            $last = $this->resource->lastScheduledAt($cron->code) ?? $now;
            $next = $last;
            for ($i = 0; $i < 5; $i++) {
                $next = $expression->getNextRunDate($next);
                if (!$this->resource->isScheduled($cron->code, $next)) {
                    $this->resource->createSchedule($cron->code, $next);
                }
            }
        }
    }

    public function listCrons(): CronDefinitionCollection
    {
        $this->getCronsToSchedule();
        return $this->resource->getCrons();
    }

    public function enable(string $code): void
    {
        $this->resource->enable($code);
    }

    public function disable(string $code): void
    {
        $this->resource->disable($code);
    }

    public function updateExpression(string $code, string $expression): void
    {
        $this->resource->setExpression($code, $expression);
    }

    private function getCronsToSchedule(): SchedulableCronCollection
    {
        $finder = $this->finderFactory->create();
        $path = dirname(__DIR__) . '/Cron';
        if (!is_dir($path)) {
            return new SchedulableCronCollection();
        }
        $finder->files()->in($path)->name('*.php');
        $classes = new SchedulableCronCollection();
        foreach ($finder as $file) {
            $class = 'CitiesRpg\\ApiBackend\\Cron\\' . $file->getBasename('.php');
            if (!class_exists($class)) {
                continue;
            }
            $refl = new ReflectionClass($class);
            if (!$refl->implementsInterface(CronInterface::class)) {
                continue;
            }
            $attrs = $refl->getAttributes(CronAttribute::class);
            if (empty($attrs)) {
                continue;
            }
            /** @var CronAttribute $attr */
            $attr = $attrs[0]->newInstance();
            $classes->add(new SchedulableCron(
                code: $attr->code,
                className: $class,
                expression: $attr->expression,
                enabled: true,
            ));
            if (!$this->resource->cronExists($attr->code)) {
                $this->resource->registerCron($attr->code, $attr->expression);
            }
        }

        $configured = new SchedulableCronCollection();
        foreach ($this->resource->getCrons() as $row) {
            $class = $classes->get($row->code);
            if ($class === null) {
                continue;
            }
            $configured->add(new SchedulableCron(
                code: $row->code,
                className: $class->className,
                expression: $row->expression,
                enabled: $row->enabled,
            ));
        }
        return $configured;
    }
}
