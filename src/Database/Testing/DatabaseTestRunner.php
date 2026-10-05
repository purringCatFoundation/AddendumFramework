<?php

declare(strict_types=1);

namespace PCF\Addendum\Database\Testing;

use Closure;
use PDO;
use PDOException;
use PCF\Addendum\Command\DatabaseTestResult;
use Symfony\Component\Process\Process;

final readonly class DatabaseTestRunner
{
    /** @var Closure(): PDO */
    private Closure $pdoProvider;

    /** @param callable(): PDO $pdoProvider */
    public function __construct(
        callable $pdoProvider,
        private string $host,
        private int $port,
        private string $database,
        private string $user,
        private string $password
    ) {
        $this->pdoProvider = Closure::fromCallable($pdoProvider);
    }

    public function run(string $filePath): DatabaseTestResult
    {
        $psqlPath = $this->findPsql();
        if ($psqlPath !== null) {
            return $this->runWithPsql($psqlPath, $filePath);
        }

        return $this->runWithPdo((string) file_get_contents($filePath));
    }

    private function findPsql(): ?string
    {
        foreach (['/usr/bin/psql', '/usr/local/bin/psql', 'psql'] as $path) {
            $process = new Process(['which', $path]);
            $process->run();
            if ($process->isSuccessful()) {
                return trim($process->getOutput());
            }
            if (file_exists($path) && is_executable($path)) {
                return $path;
            }
        }

        return null;
    }

    private function runWithPsql(string $psqlPath, string $filePath): DatabaseTestResult
    {
        $process = new Process([
            $psqlPath,
            '-h', $this->host,
            '-p', (string) $this->port,
            '-U', $this->user,
            '-d', $this->database,
            '-f', $filePath,
            '-v', 'ON_ERROR_STOP=1',
        ]);
        $process->setEnv(['PGPASSWORD' => $this->password]);
        $process->setTimeout(60);
        $process->run();

        return new TapOutputParser()->parse(
            $process->getOutput() . $process->getErrorOutput(),
            $process->isSuccessful()
        );
    }

    private function runWithPdo(string $sql): DatabaseTestResult
    {
        $pdo = ($this->pdoProvider)();
        $output = '';
        $hasFailure = false;
        $pdo->beginTransaction();

        try {
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
            $pdo->exec($sql);
            foreach (new SqlStatementSplitter()->split($sql) as $statement) {
                $statement = trim($statement);
                if (empty($statement)) {
                    continue;
                }
                try {
                    $output .= $this->executeStatement($pdo, $statement);
                } catch (PDOException $exception) {
                    $output .= 'ERROR: ' . $exception->getMessage() . "\n";
                    $hasFailure = true;
                }
            }
        } finally {
            $pdo->rollBack();
        }

        return new TapOutputParser()->parse($output, !$hasFailure);
    }

    private function executeStatement(PDO $pdo, string $statement): string
    {
        if (stripos($statement, 'SELECT') !== 0) {
            $pdo->exec($statement);
            return '';
        }
        $query = $pdo->query($statement);
        if ($query === false) {
            return '';
        }
        $output = '';
        foreach ($query->fetchAll(PDO::FETCH_NUM) as $row) {
            $output .= implode(' ', array_map('strval', $row)) . "\n";
        }

        return $output;
    }
}
