<?php
declare(strict_types=1);

namespace PCF\Addendum\Cron;

use PCF\Addendum\Database\DbConnectionFactory;
use PCF\Addendum\Util\FinderFactory;

class CronServiceFactory
{
    public function create(): CronService
    {
        $resource = new ScheduleResourceFactory(new DbConnectionFactory())->create();
        $finderFactory = new FinderFactory();
        return new CronService($resource, $finderFactory);
    }
}
