<?php
declare(strict_types=1);

namespace PCF\Addendum\Database;

use Ds\Map;
use Ds\Vector;
use PCF\Addendum\Util\FinderFactory;
use PDO;
use Symfony\Component\Finder\Finder;

class MigrationRunner
{
    private ?PDO $pdo = null;

    /** @var Vector<string> */
    private Vector $paths;

    /**
     * @param DbConnectionFactory $dbConnectionFactory
     * @param string|iterable<string> $paths Single path or paths to migration directories
     * @param FinderFactory $finderFactory
     */
    public function __construct(
        private DbConnectionFactory $dbConnectionFactory,
        string|iterable $paths,
        private FinderFactory $finderFactory
    ) {
        $this->paths = is_string($paths) ? new Vector([$paths]) : new Vector($paths);
    }

    private function getPdo(): PDO
    {
        if ($this->pdo === null) {
            $this->pdo = $this->dbConnectionFactory->create();
        }
        return $this->pdo;
    }

    /** @return Map<string, string> [migration name => SQL] */
    public function pending(): Map
    {
        $pdo = $this->getPdo();
        $pdo->exec('CREATE TABLE IF NOT EXISTS migrations (name TEXT PRIMARY KEY)');
        $stmt = $pdo->query('SELECT name FROM migrations');
        $applied = new Map();

        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) ?: [] as $name) {
            $applied->put($name, true);
        }

        $validPaths = new Vector();
        foreach ($this->paths as $path) {
            if (is_dir($path)) {
                $validPaths->push($path);
            }
        }

        if ($validPaths->isEmpty()) {
            return new Map();
        }

        $finder = $this->finderFactory->create();
        $finder->files()->name('*.sql')->in($validPaths->toArray())->sortByName();

        $pending = new Map();
        foreach ($finder as $file) {
            $name = $file->getFilename();
            if ($applied->hasKey($name)) {
                continue;
            }
            $pending->put($name, $file->getContents());
        }

        return $pending;
    }

    /**
     * @param callable|null $onBeforeMigration Callback called before each migration with migration name
     * @return Vector<string> executed migration names
     */
    public function run(?callable $onBeforeMigration = null): Vector
    {
        $executed = new Vector();
        $pdo = $this->getPdo();
        foreach ($this->pending() as $name => $sql) {
            if ($onBeforeMigration !== null) {
                $onBeforeMigration($name);
            }
            $pdo->exec($sql);
            $ins = $pdo->prepare('INSERT INTO migrations (name) VALUES (:name)');
            $ins->execute(['name' => $name]);
            $executed->push($name);
        }
        return $executed;
    }
}
