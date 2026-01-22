<?php
declare(strict_types=1);

namespace PCF\Addendum\Cron;

interface CronInterface
{
    public function run(): void;
}
