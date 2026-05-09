<?php
declare(strict_types=1);

namespace PCF\Addendum\Cron;

use PCF\Addendum\Cron\ScheduleResource;
use PCF\Addendum\Database\DbConnectionFactory;

class ScheduleResourceFactory
{
    public function __construct(private DbConnectionFactory $dbConnectionFactory)
    {
    }

    public function create(): ScheduleResource
    {
        $pdo = $this->dbConnectionFactory->create();
        return new ScheduleResource($pdo);
    }
}
