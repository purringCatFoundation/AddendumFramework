<?php
declare(strict_types=1);

namespace PCF\Addendum\Application;

use Ds\Map;
use Ds\Vector;
use GuzzleHttp\Psr7\ServerRequest;
use PCF\Addendum\Application\Cache\ApplicationCacheConfigurationFactory;
use PCF\Addendum\Attribute\AttributeReader;
use PCF\Addendum\Attribute\AttributeReaderFactory;
use PCF\Addendum\Attribute\Actions;
use PCF\Addendum\Attribute\Commands;
use PCF\Addendum\Attribute\Name;
use PCF\Addendum\Attribute\Version;
use PCF\Addendum\Command\CommandScanner;
use PCF\Addendum\Config\SystemEnvironmentProvider;
use PCF\Addendum\Http\Cache\HttpCacheBackendProviderFactory;
use PCF\Addendum\Http\Cache\HttpCacheConfigurationFactory;
use PCF\Addendum\Http\Cache\HttpCacheRuntimeFactory;
use PCF\Addendum\Http\Routing\ActionScanner;
use Psr\Http\Message\ResponseInterface;
use ReflectionClass;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Command\LazyCommand;
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
 */
abstract class Application
{
    private static ?self $instance = null;

    // Cached attribute values
    private ?string $name = null;
    private ?string $version = null;
    private ?Vector $actionPaths = null;
    private ?Vector $commandPaths = null;
    private ?string $frameworkDir = null;
    private AttributeReader $attributeReader;

    public function __construct()
    {
        $this->attributeReader = new AttributeReaderFactory()->create($this);
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
        $request = ServerRequest::fromGlobals();
        $response = $httpApp->handle($request);

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
        if (self::$instance === null) {
            self::$instance = new static();
        }

        return self::$instance;
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
        $environmentProvider = new SystemEnvironmentProvider();
        $cacheConfiguration = new ApplicationCacheConfigurationFactory($environmentProvider)->create();
        $scanners = new Vector();

        if (!$cacheConfiguration->isEnabled() || $cacheConfiguration->shouldRefreshOnRequest() || !is_file($cacheConfiguration->routesFile())) {
            foreach ($this->getActionPaths() as $path) {
                $scanners->push(new ActionScanner($path));
            }
        }

        $httpCacheRuntimeFactory = new HttpCacheRuntimeFactory(
            new HttpCacheConfigurationFactory($environmentProvider),
            new HttpCacheBackendProviderFactory()
        )->create();

        return new AppFactory($scanners, $httpCacheRuntimeFactory, $cacheConfiguration)->create();
    }

    /**
     * Create console application
     */
    protected function createConsoleApp(): ConsoleApplication
    {
        $consoleApp = new ConsoleApplication(
            $this->getName(),
            $this->getVersion()
        );

        $commands = new Map();

        foreach ($this->getCommandPaths() as $path) {
            if (!is_dir($path)) {
                continue;
            }

            $scanner = new CommandScanner($path);
            foreach ($scanner->scanCommands() as $name => $definition) {
                $commands->put($name, $definition);
            }
        }

        foreach ($commands as $definition) {
            $factoryClass = $definition->factory;
            $commandClass = $definition->class;

            $consoleApp->add(new LazyCommand(
                name: $definition->name,
                aliases: [],
                description: $definition->description,
                isHidden: false,
                commandFactory: $factoryClass !== null
                    ? fn() => new $factoryClass()->create()
                    : fn() => new $commandClass(),
            ));
        }

        return $consoleApp;
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
