<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Cron;

use Pradzikowski\Framework\Cron\CronInterface;
use Pradzikowski\Framework\Attribute\Cron;

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
