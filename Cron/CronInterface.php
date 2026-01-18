<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Cron;

interface CronInterface
{
    public function run(): void;
}
