<?php
declare(strict_types=1);

namespace PCF\Addendum\Cron;

use PCF\Addendum\Cron\CronInterface;
use PCF\Addendum\Attribute\Cron;

#[Cron('example')]
class ExampleCron implements CronInterface
{
    /**
     * Execute the scheduled cron job
     *
     * @return void
     */
    public function run(): void
    {

    }
}
