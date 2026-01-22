<?php
declare(strict_types=1);

namespace PCF\Addendum\Command;

use PCF\Addendum\Command\FactoryInterface;
use PCF\Addendum\Cron\CronServiceFactory;

class CronCommandFactory implements FactoryInterface
{
    public function create(): CronCommand
    {
        $service = new CronServiceFactory()->create();
        return new CronCommand($service);
    }
}
