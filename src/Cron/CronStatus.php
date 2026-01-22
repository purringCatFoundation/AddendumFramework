<?php
declare(strict_types=1);

namespace PCF\Addendum\Cron;

enum CronStatus: string
{
    case PENDING = 'pending';
    case RUNNING = 'running';
    case SUCCESS = 'success';
    case FAILED = 'failed';
    case OMITTED = 'omitted';
    case CANCELLED = 'cancelled';
}
