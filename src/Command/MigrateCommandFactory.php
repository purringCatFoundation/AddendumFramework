<?php
declare(strict_types=1);

namespace PCF\Addendum\Command;

use PCF\Addendum\Command\FactoryInterface;
use PCF\Addendum\Database\DbConnectionFactory;
use PCF\Addendum\Database\MigrationRunner;
use PCF\Addendum\Util\FinderFactory;

class MigrateCommandFactory implements FactoryInterface
{
    public function create(): MigrateCommand
    {
        // Framework migrations (framework/src/Command -> framework/migrations)
        $frameworkMigrations = dirname(__DIR__, 2) . '/migrations';
        // Project migrations (framework/src/Command -> project root/migrations)
        $projectMigrations = dirname(__DIR__, 3) . '/migrations';

        $paths = [$frameworkMigrations, $projectMigrations];

        $finderFactory = new FinderFactory();
        $runner = new MigrationRunner(new DbConnectionFactory(), $paths, $finderFactory);
        return new MigrateCommand($runner);
    }
}
