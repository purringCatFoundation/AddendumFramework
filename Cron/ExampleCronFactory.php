<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Cron;

use Pradzikowski\Framework\Cron\ExampleCron;

class ExampleCronFactory
{
    public function create(): ExampleCron
    {
        return new ExampleCron();
    }
}

