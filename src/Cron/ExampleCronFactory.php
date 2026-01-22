<?php
declare(strict_types=1);

namespace PCF\Addendum\Cron;

use PCF\Addendum\Cron\ExampleCron;

class ExampleCronFactory
{
    public function create(): ExampleCron
    {
        return new ExampleCron();
    }
}

