<?php
declare(strict_types=1);

namespace PCF\Addendum\Database;

use PCF\Addendum\Util\FinderFactory;
use PDO;
use Symfony\Component\Finder\Finder;

class MigrationRunner
{
    private ?PDO $pdo = null;

    /**
     * @param DbConnectionFactory $dbConnectionFactory
     * @param string|array<string> $paths Single path or array of paths to migration directories
     * @param FinderFactory $finderFactory
     */
    public function __construct(
        private DbConnectionFactory $dbConnectionFactory,
        private string|array $paths,
        private FinderFactory $finderFactory
    ) {
    }

    private function getPdo(): PDO
    {
        if ($this->pdo === null) {
            $this->pdo = $this->dbConnectionFactory->create();
        }
        return $this->pdo;
    }

    /**
     * @return array<string, string> [migration name => SQL]
     */
    public function pending(): array
    {
        $pdo = $this->getPdo();
        $pdo->exec('CREATE TABLE IF NOT EXISTS migrations (name TEXT PRIMARY KEY)');
        $stmt = $pdo->query('SELECT name FROM migrations');
        $applied = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
        $applied = array_flip($applied);

        $paths = is_array($this->paths) ? $this->paths : [$this->paths];
        $validPaths = array_filter($paths, fn($p) => is_dir($p));

        if (empty($validPaths)) {
            return [];
        }

        $finder = $this->finderFactory->create();
        $finder->files()->name('*.sql')->in($validPaths)->sortByName();

        $pending = [];
        foreach ($finder as $file) {
            $name = $file->getFilename();
            if (isset($applied[$name])) {
                continue;
            }
            $pending[$name] = $file->getContents();
        }

        return $pending;
    }

    /**
     * @param callable|null $onBeforeMigration Callback called before each migration with migration name
     * @return string[] executed migration names
     */
    public function run(?callable $onBeforeMigration = null): array
    {
        $executed = [];
        $pdo = $this->getPdo();
        foreach ($this->pending() as $name => $sql) {
            if ($onBeforeMigration !== null) {
                $onBeforeMigration($name);
            }
            $pdo->exec($sql);
            $ins = $pdo->prepare('INSERT INTO migrations (name) VALUES (:name)');
            $ins->execute(['name' => $name]);
            $executed[] = $name;
        }
        return $executed;
    }
}

