<?php
declare(strict_types=1);

namespace PCF\Addendum\Command;

use PCF\Addendum\Command\FactoryInterface;
use PCF\Addendum\Application\Cache\ApplicationCacheConfiguration;
use PCF\Addendum\Application\Cache\ApplicationCacheMode;
use PCF\Addendum\Http\Routing\ActionScanner;
use PCF\Addendum\Http\RouterFactory;

class ListRoutesCommandFactory implements FactoryInterface
{
    public function create(): ListRoutesCommand
    {
        // Configure action scanners for framework and application
        $scanners = [
            // Framework actions
            new ActionScanner(
                actionNamespace: 'Pradzikowski\\Framework\\Action',
                actionDirectory: __DIR__ . '/../../framework/Action'
            ),
            // Game application actions
            new ActionScanner(
                actionNamespace: 'Pradzikowski\\Game\\Action',
                actionDirectory: __DIR__ . '/../Action'
            ),
        ];

        $routerFactory = new RouterFactory(
            $scanners,
            new ApplicationCacheConfiguration(ApplicationCacheMode::OFF, 'dev', sys_get_temp_dir() . '/addendum-no-cache')
        );
        $router = $routerFactory->create();

        return new ListRoutesCommand($router);
    }
}
