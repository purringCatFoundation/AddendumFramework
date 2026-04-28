#!/usr/bin/env php
<?php
declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use PCF\Addendum\Command\DatabaseTestCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;

$application = new Application('Addendum DB Tests');
$application->addCommand(new DatabaseTestCommand());

$input = new ArrayInput([
    'command' => 'db:test',
    '--drop' => true,
]);

$output = new ConsoleOutput();

exit($application->run($input, $output));
