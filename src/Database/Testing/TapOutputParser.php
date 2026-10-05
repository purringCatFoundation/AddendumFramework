<?php

declare(strict_types=1);

namespace PCF\Addendum\Database\Testing;

use PCF\Addendum\Command\DatabaseTestResult;

final class TapOutputParser
{
    public function parse(string $output, bool $processSuccess): DatabaseTestResult
    {
        $testCount = 0;
        $hasFailure = false;
        foreach (explode("\n", $output) as $line) {
            if (preg_match('/^\s*(?:\|\s*)?not ok \d+\b/', $line)) {
                ++$testCount;
                $hasFailure = true;
            } elseif (preg_match('/^\s*(?:\|\s*)?ok \d+\b/', $line)) {
                ++$testCount;
            } elseif (str_contains($line, 'ERROR:') || str_contains($line, 'FATAL:')) {
                $hasFailure = true;
            }
        }

        return new DatabaseTestResult(!$hasFailure && $processSuccess, $testCount, $output);
    }
}
