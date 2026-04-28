<?php
declare(strict_types=1);

use PCF\Addendum\Application\AppFactory;
use PCF\Addendum\Application\Main;
use PCF\Addendum\Http\Routing\ActionScanner;

$rootDir = dirname(__DIR__, 3);

require $rootDir . '/vendor/autoload.php';

(new Main(new AppFactory([
    new ActionScanner($rootDir . '/src/Action'),
])))->execute();
