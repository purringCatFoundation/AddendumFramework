<?php
declare(strict_types=1);

namespace PCF\Addendum\Application;

use GuzzleHttp\Psr7\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class Main
{
    /**
     * @param AppFactory|null $appFactory
     */
    public function __construct(
        private readonly ?AppFactory $appFactory = null
    ) {
    }

    /**
     * Execute the main application request handling
     *
     * @return void
     */
    public function execute(): void
    {
        error_reporting(E_ALL & ~E_DEPRECATED);
        ini_set('display_errors', '0');
        $app = $this->appFactory->create();
        $request = ServerRequest::fromGlobals();
        $response = $app->handle($request);
        $this->emit($response);
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
