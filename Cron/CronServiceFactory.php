<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Cron;

use Pradzikowski\Framework\Util\FinderFactory;

class CronServiceFactory
{
    public function create(): CronService
    {
        $resource = new ScheduleResourceFactory()->create();
        $finderFactory = new FinderFactory();
        return new CronService($resource, $finderFactory);
    }
}
