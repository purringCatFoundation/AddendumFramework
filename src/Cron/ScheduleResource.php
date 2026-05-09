<?php
declare(strict_types=1);

namespace PCF\Addendum\Cron;

use DateTimeImmutable;
use DateTimeInterface;
use PDO;
use PCF\Addendum\Cron\CronStatus;

class ScheduleResource
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * Fetch scheduled cron jobs. The underlying database function
     * handles duplicate and disabled jobs.
     */
    public function getScheduled(?string $code = null): ScheduledCronJobCollection
    {
        if ($code === null) {
            $stmt = $this->pdo->query('SELECT id, code FROM cron_get_scheduled_jobs()');
        } else {
            $stmt = $this->pdo->prepare('SELECT id, code FROM cron_get_scheduled_jobs(:code)');
            $stmt->execute(['code' => $code]);
        }

        $jobs = new ScheduledCronJobCollection();

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $jobs->add(ScheduledCronJob::fromDatabaseRow($row));
        }

        return $jobs;
    }

    public function markStarted(int $id): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE cron_schedule SET started_at = NOW(), status = :status WHERE id = :id'
        );
        $stmt->execute(['id' => $id, 'status' => CronStatus::RUNNING->value]);
    }

    public function markCompleted(int $id): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE cron_schedule SET ended_at = NOW(), status = :status WHERE id = :id'
        );
        $stmt->execute(['id' => $id, 'status' => CronStatus::SUCCESS->value]);
    }

    public function markFailed(int $id): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE cron_schedule SET ended_at = NOW(), status = :status WHERE id = :id'
        );
        $stmt->execute(['id' => $id, 'status' => CronStatus::FAILED->value]);
    }

    public function lastScheduledAt(string $code): ?DateTimeImmutable
    {
        $stmt = $this->pdo->prepare('SELECT MAX(scheduled_at) FROM cron_schedule WHERE code = :code');
        $stmt->execute(['code' => $code]);
        $time = $stmt->fetchColumn();
        return $time ? new DateTimeImmutable($time) : null;
    }

    public function isScheduled(string $code, DateTimeInterface $time): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM cron_schedule WHERE code = :code AND scheduled_at = :time'
        );
        $stmt->execute(['code' => $code, 'time' => $time->format('Y-m-d H:i:s')]);
        return (bool) $stmt->fetchColumn();
    }

    public function createSchedule(string $code, DateTimeInterface $time): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO cron_schedule(code, scheduled_at, status) VALUES(:code, :time, :status) ON CONFLICT (code, scheduled_at) DO NOTHING'
        );
        $stmt->execute([
            'code' => $code,
            'time' => $time->format('Y-m-d H:i:s'),
            'status' => CronStatus::PENDING->value,
        ]);
    }

    public function enable(string $code): void
    {
        $stmt = $this->pdo->prepare('UPDATE cron SET enabled = TRUE WHERE code = :code');
        $stmt->execute(['code' => $code]);
    }

    public function disable(string $code): void
    {
        $stmt = $this->pdo->prepare('UPDATE cron SET enabled = FALSE WHERE code = :code');
        $stmt->execute(['code' => $code]);
    }

    public function cronExists(string $code): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM cron WHERE code = :code');
        $stmt->execute(['code' => $code]);
        return (bool) $stmt->fetchColumn();
    }

    public function registerCron(string $code, string $expression): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO cron(code, expression) VALUES(:code, :expression) ON CONFLICT (code) DO NOTHING');
        $stmt->execute(['code' => $code, 'expression' => $expression]);
    }

    public function getCrons(): CronDefinitionCollection
    {
        $stmt = $this->pdo->query('SELECT code, expression, enabled FROM cron');
        $crons = new CronDefinitionCollection();

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $crons->add(CronDefinition::fromDatabaseRow($row));
        }

        return $crons;
    }

    public function setExpression(string $code, string $expression): void
    {
        $stmt = $this->pdo->prepare('UPDATE cron SET expression = :expression WHERE code = :code');
        $stmt->execute(['code' => $code, 'expression' => $expression]);
    }
}
