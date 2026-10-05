<?php

declare(strict_types=1);

namespace PCF\Addendum\Command;

use Ds\Map;
use Ds\Vector;
use PCF\Addendum\Database\Testing\DatabaseTestRunner;
use PCF\Addendum\Database\Testing\DatabaseTestSuite;
use PDO;
use PDOException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;

#[AsCommand(
    name: 'db:test',
    description: 'Run pgTAP database tests'
)]
class DatabaseTestCommand extends Command
{
    private ?PDO $pdo = null;
    private ?PDO $testPdo = null;
    private SymfonyStyle $io;

    private string $host;
    private int $port;
    private string $mainDb;
    private string $testDb;
    private string $user;
    private string $password;

    protected function configure(): void
    {
        $this
            ->addArgument(
                'pattern',
                InputArgument::OPTIONAL,
                'Test file pattern (e.g., "001*.sql")',
                '*.sql'
            )
            ->addOption(
                'cleanup',
                'c',
                InputOption::VALUE_NONE,
                'Clean up test database after tests'
            )
            ->addOption(
                'setup-only',
                null,
                InputOption::VALUE_NONE,
                'Only setup the test database, don\'t run tests'
            )
            ->addOption(
                'drop',
                null,
                InputOption::VALUE_NONE,
                'Drop and recreate test database before running tests'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        $this->loadConfiguration();

        $pattern = $input->getArgument('pattern');
        $cleanup = $input->getOption('cleanup');
        $setupOnly = $input->getOption('setup-only');
        $drop = $input->getOption('drop');

        $this->io->title('Database Test Runner (pgTAP)');
        $this->io->text([
            "Host: {$this->host}:{$this->port}",
            "Test Database: {$this->testDb}",
        ]);

        // Check PostgreSQL connection
        if (!$this->checkConnection()) {
            return Command::FAILURE;
        }

        // Drop database if requested
        if ($drop) {
            $this->dropTestDatabase();
        }

        // Setup test database
        if (!$this->setupTestDatabase()) {
            return Command::FAILURE;
        }

        // Run migrations
        if (!$this->runMigrations()) {
            return Command::FAILURE;
        }

        // Setup pgTAP extension
        if (!$this->setupPgTap()) {
            return Command::FAILURE;
        }

        if ($setupOnly) {
            $this->io->success("Test database '{$this->testDb}' is ready.");
            return Command::SUCCESS;
        }

        // Run tests
        $result = $this->runTests($pattern);

        // Cleanup if requested
        if ($cleanup) {
            $this->dropTestDatabase();
        }

        return $result;
    }

    private function loadConfiguration(): void
    {
        $this->host = $_ENV['POSTGRES_HOST'] ?? getenv('POSTGRES_HOST') ?: 'localhost';
        $this->port = (int) ($_ENV['POSTGRES_PORT'] ?? getenv('POSTGRES_PORT') ?: 5432);
        $this->mainDb = $_ENV['POSTGRES_DB'] ?? getenv('POSTGRES_DB') ?: 'app';
        $this->testDb = $_ENV['TEST_POSTGRES_DB'] ?? getenv('TEST_POSTGRES_DB') ?: $this->mainDb . '_test';
        $this->user = $_ENV['POSTGRES_USER'] ?? getenv('POSTGRES_USER') ?: 'app';
        $this->password = $_ENV['POSTGRES_PASSWORD'] ?? getenv('POSTGRES_PASSWORD') ?: '';

        $this->validateDatabaseName($this->mainDb);
        $this->validateDatabaseName($this->testDb);
    }

    private function validateDatabaseName(string $databaseName): void
    {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $databaseName)) {
            throw new \InvalidArgumentException(
                'PostgreSQL database names may contain only letters, digits and underscores'
            );
        }
    }

    private function checkConnection(): bool
    {
        $this->io->section('Checking PostgreSQL connection');

        try {
            $this->getMainPdo();
            $this->io->success('PostgreSQL connection successful');
            return true;
        } catch (PDOException $e) {
            $this->io->error('PostgreSQL connection failed: ' . $e->getMessage());
            return false;
        }
    }

    private function getMainPdo(): PDO
    {
        if ($this->pdo === null) {
            $dsn = "pgsql:host={$this->host};port={$this->port};dbname={$this->mainDb}";
            $this->pdo = new PDO($dsn, $this->user, $this->password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
        }
        return $this->pdo;
    }

    private function getTestPdo(): PDO
    {
        if ($this->testPdo === null) {
            $dsn = "pgsql:host={$this->host};port={$this->port};dbname={$this->testDb}";
            $this->testPdo = new PDO($dsn, $this->user, $this->password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
        }
        return $this->testPdo;
    }

    private function setupTestDatabase(): bool
    {
        $this->io->section('Setting up test database');

        try {
            $pdo = $this->getMainPdo();

            // Check if database exists
            $stmt = $pdo->prepare("SELECT 1 FROM pg_database WHERE datname = :dbname");
            $stmt->execute(['dbname' => $this->testDb]);

            if (!$stmt->fetch()) {
                $this->io->text("Creating database '{$this->testDb}'...");
                $pdo->exec("CREATE DATABASE \"{$this->testDb}\"");
                $this->io->text('Database created');
            } else {
                $this->io->text('Database already exists');
            }

            $this->io->success('Test database ready');
            return true;
        } catch (PDOException $e) {
            $this->io->error('Failed to setup test database: ' . $e->getMessage());
            return false;
        }
    }

    private function dropTestDatabase(): void
    {
        $this->io->section('Dropping test database');

        try {
            // Close test connection first
            $this->testPdo = null;

            $pdo = $this->getMainPdo();

            // Terminate active connections
            $pdo->exec("
                SELECT pg_terminate_backend(pid)
                FROM pg_stat_activity
                WHERE datname = '{$this->testDb}' AND pid <> pg_backend_pid()
            ");

            $pdo->exec("DROP DATABASE IF EXISTS \"{$this->testDb}\"");
            $this->io->success('Test database dropped');
        } catch (PDOException $e) {
            $this->io->warning('Failed to drop test database: ' . $e->getMessage());
        }
    }

    private function runMigrations(): bool
    {
        $this->io->section('Running migrations');

        $migrationPaths = $this->getMigrationPaths();
        $appliedCount = 0;

        if ($migrationPaths->isEmpty()) {
            $this->io->warning('No migration directories found');
            return true;
        }

        try {
            $pdo = $this->getTestPdo();

            // Create migrations table
            $pdo->exec('CREATE TABLE IF NOT EXISTS migrations (name TEXT PRIMARY KEY)');

            // Get applied migrations
            $stmt = $pdo->query('SELECT name FROM migrations');
            $applied = new Map();

            foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) ?: [] as $name) {
                $applied->put($name, true);
            }

            // Find and apply migrations
            $finder = new Finder();
            $finder->files()->name('*.sql')->in($migrationPaths->toArray())->sortByName();

            foreach ($finder as $file) {
                $name = $file->getFilename();

                if ($applied->hasKey($name)) {
                    continue;
                }

                $this->io->text("Applying: {$name}");
                $pdo->exec($file->getContents());

                $stmt = $pdo->prepare('INSERT INTO migrations (name) VALUES (:name)');
                $stmt->execute(['name' => $name]);
                $appliedCount++;
            }

            if ($appliedCount > 0) {
                $this->io->success("Applied {$appliedCount} migration(s)");
            } else {
                $this->io->text('No new migrations to apply');
            }

            return true;
        } catch (PDOException $e) {
            $this->io->error('Migration failed: ' . $e->getMessage());
            return false;
        }
    }

    private function setupPgTap(): bool
    {
        $this->io->section('Setting up pgTAP');

        try {
            $pdo = $this->getTestPdo();
            $pdo->exec('CREATE EXTENSION IF NOT EXISTS pgtap');
            $this->io->success('pgTAP extension ready');
            return true;
        } catch (PDOException $e) {
            $this->io->error('Failed to setup pgTAP: ' . $e->getMessage());
            $this->io->warning('Make sure pgTAP is installed on your PostgreSQL server');
            return false;
        }
    }

    private function runTests(string $pattern): int
    {
        $runner = new DatabaseTestRunner(
            $this->getTestPdo(...),
            $this->host,
            $this->port,
            $this->testDb,
            $this->user,
            $this->password
        );

        return new DatabaseTestSuite($runner, $this->io)->run($pattern, $this->getTestPaths());
    }

    /**
     * Get paths to migration directories
     *
     * @return Vector<string>
     */
    private function getMigrationPaths(): Vector
    {
        // Framework migrations (framework/src/Command -> framework/migrations)
        $frameworkMigrations = dirname(__DIR__, 2) . '/migrations';
        // Project migrations (framework/src/Command -> project root/migrations)
        $projectMigrations = dirname(__DIR__, 3) . '/migrations';

        $paths = new Vector();

        foreach ([$frameworkMigrations, $projectMigrations] as $path) {
            if (is_dir($path)) {
                $paths->push($path);
            }
        }

        return $paths;
    }

    /**
     * Get paths to test directories
     *
     * @return Vector<string>
     */
    private function getTestPaths(): Vector
    {
        // Framework tests (framework/src/Command -> framework/dev/tests/database)
        $frameworkTests = dirname(__DIR__, 2) . '/dev/tests/database';
        // Project tests (framework/src/Command -> project root/tests/database)
        $projectTests = dirname(__DIR__, 3) . '/tests/database';

        return new Vector([$frameworkTests, $projectTests]);
    }
}
