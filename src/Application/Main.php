<?php
declare(strict_types=1);

namespace PCF\Addendum\Application;

use GuzzleHttp\Psr7\ServerRequest;
use PCF\Addendum\Application\Cache\ApplicationCacheConfigurationFactory;
use PCF\Addendum\Application\Cache\CompiledHttpApplicationGenerator;
use PCF\Addendum\Application\Cache\CompiledHttpApplicationCache;
use PCF\Addendum\Application\Cache\PhpFileWriter;
use PCF\Addendum\Config\SystemEnvironmentProvider;
use Psr\Http\Message\ResponseInterface;

class Main
{
    /**
     * Execute the main application request handling
     *
     * @return void
     */
    public function execute(): void
    {
        error_reporting(E_ALL & ~E_DEPRECATED);
        ini_set('display_errors', '0');
        $app = $this->createApp();
        $request = ServerRequest::fromGlobals();
        $response = $app->handle($request);
        $this->emit($response);
    }

    private function createApp(): App
    {
        return new CompiledHttpApplicationCache(
            new ApplicationCacheConfigurationFactory(new SystemEnvironmentProvider())->create(),
            new CompiledHttpApplicationGenerator(),
            new PhpFileWriter()
        )->load();
    }

    /**
     * Emit HTTP response to the client
     *
     * @param ResponseInterface $response PSR-7 response to send
     * @return void
     */
    private function emit(ResponseInterface $response): void
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
