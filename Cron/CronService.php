<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Cron;

use Pradzikowski\Framework\Attribute\Cron as CronAttribute;
use Pradzikowski\Framework\Util\FinderFactory;
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
            $jobCode = $item['code'];
            if (!isset($crons[$jobCode])) {
                $this->resource->markFailed($item['id']);
                continue;
            }
            $this->resource->markStarted($item['id']);
            $class = $crons[$jobCode]['class'];
            try {
                $factoryClass = $class . 'Factory';
                if (!class_exists($factoryClass) || !method_exists($factoryClass, 'create')) {
                    throw new RuntimeException('Cron factory not found');
                }
                /** @var CronInterface $instance */
                $instance = new $factoryClass()->create();
                $instance->run();
                $this->resource->markCompleted($item['id']);
            } catch (Throwable $e) {
                $this->resource->markFailed($item['id']);
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
        foreach ($toSchedule as $cronCode => $meta) {
            if ($code !== null && $cronCode !== $code) {
                continue;
            }
            if (!$meta['enabled']) {
                continue;
            }
            $expression = new CronExpression($meta['expression']);
            $last = $this->resource->lastScheduledAt($cronCode) ?? $now;
            $next = $last;
            for ($i = 0; $i < 5; $i++) {
                $next = $expression->getNextRunDate($next);
                if (!$this->resource->isScheduled($cronCode, $next)) {
                    $this->resource->createSchedule($cronCode, $next);
                }
            }
        }
    }

    public function listCrons(): array
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

    /**
     * @return array<string,array{class:string,expression:string,enabled:bool}>
     */
    private function getCronsToSchedule(): array
    {
        $finder = $this->finderFactory->create();
        $path = dirname(__DIR__) . '/Cron';
        if (!is_dir($path)) {
            return [];
        }
        $finder->files()->in($path)->name('*.php');
        $classes = [];
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
            $classes[$attr->code] = ['class' => $class, 'expression' => $attr->expression];
            if (!$this->resource->cronExists($attr->code)) {
                $this->resource->registerCron($attr->code, $attr->expression);
            }
        }

        $configured = [];
        foreach ($this->resource->getCrons() as $row) {
            $code = $row['code'];
            if (!isset($classes[$code])) {
                continue;
            }
            $configured[$code] = [
                'class' => $classes[$code]['class'],
                'expression' => $row['expression'],
                'enabled' => (bool) $row['enabled'],
            ];
        }
        return $configured;
    }
}
