<?php
declare(strict_types=1);

use PCF\Addendum\Dev\DevApp;

$rootDir = dirname(__DIR__, 3);

require $rootDir . '/vendor/autoload.php';
require $rootDir . '/dev/frankenphp/DevApp.php';
require $rootDir . '/dev/frankenphp/bootstrap.php';

DevApp::http();
