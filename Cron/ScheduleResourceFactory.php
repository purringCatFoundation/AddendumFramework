<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Cron;

use Pradzikowski\Framework\Cron\ScheduleResource;
use Pradzikowski\Framework\Database\DbConnectionFactory;

class ScheduleResourceFactory
{
    public function __construct(private ?DbConnectionFactory $dbConnectionFactory = null)
    {
        $this->dbConnectionFactory ??= new DbConnectionFactory();
    }

    public function create(): ScheduleResource
    {
        $pdo = $this->dbConnectionFactory->create();
        return new ScheduleResource($pdo);
    }
}
