<?php

declare(strict_types=1);

namespace PCF\Addendum\Database\Testing;

use Ds\Vector;
use PCF\Addendum\Command\DatabaseTestFailure;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;

final readonly class DatabaseTestSuite
{
    public function __construct(private DatabaseTestRunner $runner, private SymfonyStyle $io)
    {
    }

    /** @param iterable<string> $paths */
    public function run(string $pattern, iterable $paths): int
    {
        $this->io->section('Running tests');
        $validPaths = [];
        foreach ($paths as $path) {
            if (is_dir($path)) {
                $validPaths[] = $path;
            }
        }
        if ($validPaths === []) {
            $this->io->warning('No test directories found');
            return Command::SUCCESS;
        }
        $finder = new Finder();
        $finder->files()->name($pattern)->in($validPaths)->sortByName();
        $total = 0;
        $passed = 0;
        $failures = new Vector();
        foreach ($finder as $file) {
            ++$total;
            $name = $file->getFilename();
            $this->io->text("Running: {$name}");
            $result = $this->runner->run($file->getRealPath());
            if ($result->success) {
                ++$passed;
                $this->io->text("  <fg=green>✓ PASSED</> ({$result->tests} tests)");
            } else {
                $failures->push(new DatabaseTestFailure($name, $result->output));
                $this->io->text('  <fg=red>✗ FAILED</>');
            }
        }

        return $this->printSummary($total, $passed, $failures);
    }

    /** @param Vector<DatabaseTestFailure> $failures */
    private function printSummary(int $total, int $passed, Vector $failures): int
    {
        $failed = $failures->count();
        $this->io->newLine();
        $this->io->section('Test Summary');
        $this->io->definitionList(
            ['Total tests' => (string) $total],
            ['Passed' => "<fg=green>{$passed}</>"],
            ['Failed' => $failed > 0 ? "<fg=red>{$failed}</>" : '0'],
        );
        if (!$failures->isEmpty()) {
            $this->io->section('Failed Tests');
            foreach ($failures as $failure) {
                $this->io->error($failure->name);
                $this->io->text($failure->output);
            }
            $this->io->error("{$failed} test(s) failed!");
            return Command::FAILURE;
        }
        $this->io->success('All tests passed!');

        return Command::SUCCESS;
    }
}
