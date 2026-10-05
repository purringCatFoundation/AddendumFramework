<?php

declare(strict_types=1);

namespace PCF\Addendum\Application;

use Ds\Vector;
use PCF\Addendum\Attribute\AttributeReader;
use PCF\Addendum\Attribute\Actions;
use PCF\Addendum\Attribute\Commands;
use PCF\Addendum\Attribute\Name;
use PCF\Addendum\Attribute\Version;
use Psr\Http\Message\ResponseInterface;
use ReflectionClass;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Dotenv\Dotenv;

/**
 * Base Application class
 *
 * Extend this class and use attributes to configure your application:
 *
 * ```php
 * #[Name('MyApp')]
 * #[Version('1.0.0')]
 * #[Actions(__DIR__ . '/Action')]
 * #[Commands(__DIR__ . '/Command')]
 * final class App extends Application {}
 * ```
 *
 * @phpstan-consistent-constructor
 */
abstract class Application
{
    /** @var array<class-string<self>, self> */
    private static array $instances = [];

    // Cached attribute values
    private ?string $name = null;
    private ?string $version = null;
    /** @var Vector<string>|null */
    private ?Vector $actionPaths = null;
    /** @var Vector<string>|null */
    private ?Vector $commandPaths = null;
    private ?string $frameworkDir = null;
    private AttributeReader $attributeReader;

    public function __construct()
    {
        $this->attributeReader = new AttributeReader($this);
    }

    /**
     * Run HTTP application
     */
    public static function http(): void
    {
        $app = static::getInstance();
        $app->loadEnvironment();
        $app->configureErrorHandling();

        $httpApp = $app->createHttpApp();
        $response = new HttpApplicationFactory()->handleGlobals($httpApp);

        $app->emit($response);
    }

    /**
     * Run console application
     */
    public static function console(): never
    {
        $app = static::getInstance();
        $app->loadEnvironment();

        $consoleApp = $app->createConsoleApp();
        $exitCode = $consoleApp->run();

        exit($exitCode);
    }

    /**
     * Get singleton instance
     */
    protected static function getInstance(): static
    {
        $instance = self::$instances[static::class] ?? null;

        if (!$instance instanceof static) {
            $instance = new static();
            self::$instances[static::class] = $instance;
        }

        return $instance;
    }

    /**
     * Get application name from #[Name] attribute
     */
    public function getName(): string
    {
        if ($this->name === null) {
            $this->name = $this->attributeReader->getAttributeValues(Name::class, 'value')->getFirst('Application');
        }

        return $this->name;
    }

    /**
     * Get application version from #[Version] attribute
     */
    public function getVersion(): string
    {
        if ($this->version === null) {
            $this->version = $this->attributeReader->getAttributeValues(Version::class, 'value')->getFirst('1.0.0');
        }

        return $this->version;
    }

    /**
     * Get all action paths from #[Actions] attributes + framework built-in
     */
    /** @return Vector<string> */
    public function getActionPaths(): Vector
    {
        if ($this->actionPaths === null) {
            $paths = new Vector();

            // Framework built-in actions
            $frameworkDir = $this->getFrameworkDir();
            $frameworkUserActions = $frameworkDir . '/Action/User';
            $frameworkAdminActions = $frameworkDir . '/Action/Admin';

            if (is_dir($frameworkUserActions)) {
                $paths->push($frameworkUserActions);
            }
            if (is_dir($frameworkAdminActions)) {
                $paths->push($frameworkAdminActions);
            }

            // Application actions from attributes
            $paths->push(...$this->attributeReader->getAttributeValues(Actions::class, 'path')->getValues());

            $this->actionPaths = $paths;
        }

        return $this->actionPaths->copy();
    }

    /**
     * Get all command paths from #[Commands] attributes + framework built-in
     */
    /** @return Vector<string> */
    public function getCommandPaths(): Vector
    {
        if ($this->commandPaths === null) {
            $paths = new Vector();

            // Framework built-in commands
            $frameworkDir = $this->getFrameworkDir();
            $frameworkCommands = $frameworkDir . '/Command';

            if (is_dir($frameworkCommands)) {
                $paths->push($frameworkCommands);
            }

            // Application commands from attributes
            $paths->push(...$this->attributeReader->getAttributeValues(Commands::class, 'path')->getValues());

            $this->commandPaths = $paths;
        }

        return $this->commandPaths->copy();
    }

    /**
     * Get framework directory path
     */
    protected function getFrameworkDir(): string
    {
        if ($this->frameworkDir === null) {
            $this->frameworkDir = dirname(__DIR__);
        }

        return $this->frameworkDir;
    }

    /**
     * Get project root directory (where App class is defined)
     */
    protected function getProjectDir(): string
    {
        $reflection = new ReflectionClass(static::class);
        $appFile = $reflection->getFileName();

        // Go up from src/App.php to project root
        return dirname($appFile, 2);
    }

    /**
     * Load environment variables
     */
    protected function loadEnvironment(): void
    {
        $projectDir = $this->getProjectDir();
        $envFile = $projectDir . '/.env';

        if (file_exists($envFile)) {
            new Dotenv()->loadEnv($envFile);
        }
    }

    /**
     * Configure PHP error handling
     */
    protected function configureErrorHandling(): void
    {
        error_reporting(E_ALL & ~E_DEPRECATED);
        ini_set('display_errors', '0');
    }

    /**
     * Create HTTP application
     */
    protected function createHttpApp(): App
    {
        return new HttpApplicationFactory()->create($this->getActionPaths(...));
    }

    /**
     * Create console application
     */
    protected function createConsoleApp(): ConsoleApplication
    {
        return new ConsoleApplicationFactory()->create(
            $this->getName(),
            $this->getVersion(),
            $this->getCommandPaths()
        );
    }

    /**
     * Emit HTTP response
     */
    protected function emit(ResponseInterface $response): void
    {
        http_response_code($response->getStatusCode());

        foreach ($response->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                header($name . ': ' . $value, false);
            }
        }

        $output = fopen('php://output', 'wb');
        $body = $response->getBody();
        $body->rewind();

        while (!$body->eof()) {
            fwrite($output, $body->read(8192));
        }

        fclose($output);
    }
}
