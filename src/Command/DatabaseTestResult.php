<?php
declare(strict_types=1);

namespace PCF\Addendum\Command;

final readonly class DatabaseTestResult
{
    public function __construct(
        public bool $success,
        public int $tests,
        public string $output
    ) {
    }

    public function withOutput(string $output): self
    {
        return new self($this->success, $this->tests, $output);
    }
}
