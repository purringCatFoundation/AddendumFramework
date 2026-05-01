<?php
declare(strict_types=1);

namespace PCF\Addendum\Application;

use GuzzleHttp\Psr7\ServerRequest;
use PCF\Addendum\Attribute\AttributeReader;
use PCF\Addendum\Attribute\AttributeReaderFactory;
use PCF\Addendum\Attribute\Actions;
use PCF\Addendum\Attribute\Commands;
use PCF\Addendum\Attribute\Name;
use PCF\Addendum\Attribute\Version;
use PCF\Addendum\Command\CommandScanner;
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
    private ?array $actionPaths = null;
    private ?array $commandPaths = null;
    private ?string $frameworkDir = null;
    private AttributeReader $attributeReader;

    public function __construct(?AttributeReaderFactory $attributeReaderFactory = null)
    {
        $this->attributeReader = ($attributeReaderFactory ?? new AttributeReaderFactory())->create($this);
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
    public function getActionPaths(): array
    {
        if ($this->actionPaths === null) {
            $paths = [];

            // Framework built-in actions
            $frameworkDir = $this->getFrameworkDir();
            $frameworkUserActions = $frameworkDir . '/Action/User';
            $frameworkAdminActions = $frameworkDir . '/Action/Admin';

            if (is_dir($frameworkUserActions)) {
                $paths[] = $frameworkUserActions;
            }
            if (is_dir($frameworkAdminActions)) {
                $paths[] = $frameworkAdminActions;
            }

            // Application actions from attributes
            $paths = array_merge($paths, $this->attributeReader->getAttributeValues(Actions::class, 'path')->getValues());

            $this->actionPaths = $paths;
        }

        return $this->actionPaths;
    }

    /**
     * Get all command paths from #[Commands] attributes + framework built-in
     */
    public function getCommandPaths(): array
    {
        if ($this->commandPaths === null) {
            $paths = [];

            // Framework built-in commands
            $frameworkDir = $this->getFrameworkDir();
            $frameworkCommands = $frameworkDir . '/Command';

            if (is_dir($frameworkCommands)) {
                $paths[] = $frameworkCommands;
            }

            // Application commands from attributes
            $paths = array_merge($paths, $this->attributeReader->getAttributeValues(Commands::class, 'path')->getValues());

            $this->commandPaths = $paths;
        }

        return $this->commandPaths;
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
        $scanners = array_map(
            fn(string $path) => new ActionScanner($path),
            $this->getActionPaths()
        );
        $httpCacheRuntimeFactory = new HttpCacheRuntimeFactory()->create();

        return new AppFactory($scanners, $httpCacheRuntimeFactory)->create();
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

        $commands = [];

        foreach ($this->getCommandPaths() as $path) {
            if (!is_dir($path)) {
                continue;
            }

            $scanner = new CommandScanner($path);
            $commands = array_merge($commands, $scanner->scanCommands());
        }

        foreach ($commands as $definition) {
            $factoryClass = $definition['factory'] ?? null;
            $commandClass = $definition['class'];

            $consoleApp->add(new LazyCommand(
                name: $definition['name'],
                aliases: [],
                description: $definition['description'],
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
