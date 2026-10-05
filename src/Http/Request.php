<?php

declare(strict_types=1);

namespace PCF\Addendum\Http;

use JsonException;
use PCF\Addendum\Exception\HttpException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

class Request implements RequestInterface
{
    public function __construct(private ServerRequestInterface $serverRequest)
    {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $attribute = $this->serverRequest->getAttribute($key);
        if ($attribute !== null) {
            return $attribute;
        }
        $query = $this->serverRequest->getQueryParams();
        return $query[$key] ?? $default;
    }

    /**
     * Return the JSON-decoded request body.
     *
     * @return array<string, mixed>
     */
    public function json(): array
    {
        $body = (string) $this->serverRequest->getBody();

        if (trim($body) === '') {
            return [];
        }

        try {
            $data = json_decode($body, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw HttpException::badRequest('Malformed JSON request body');
        }

        return is_array($data) ? $data : [];
    }

    public function getRequestTarget(): string
    {
        return $this->serverRequest->getRequestTarget();
    }

    public function withRequestTarget($requestTarget): static
    {
        return $this->withServerRequest($this->serverRequest->withRequestTarget($requestTarget));
    }

    public function getMethod(): string
    {
        return $this->serverRequest->getMethod();
    }

    public function withMethod($method): static
    {
        return $this->withServerRequest($this->serverRequest->withMethod($method));
    }

    public function getUri(): UriInterface
    {
        return $this->serverRequest->getUri();
    }

    public function withUri(UriInterface $uri, $preserveHost = false): static
    {
        return $this->withServerRequest($this->serverRequest->withUri($uri, $preserveHost));
    }

    public function getProtocolVersion(): string
    {
        return $this->serverRequest->getProtocolVersion();
    }

    public function withProtocolVersion($version): static
    {
        return $this->withServerRequest($this->serverRequest->withProtocolVersion($version));
    }

    public function getHeaders(): array
    {
        return $this->serverRequest->getHeaders();
    }

    public function hasHeader($name): bool
    {
        return $this->serverRequest->hasHeader($name);
    }

    public function getHeader($name): array
    {
        return $this->serverRequest->getHeader($name);
    }

    public function getHeaderLine($name): string
    {
        return $this->serverRequest->getHeaderLine($name);
    }

    public function withHeader($name, $value): static
    {
        return $this->withServerRequest($this->serverRequest->withHeader($name, $value));
    }

    public function withAddedHeader($name, $value): static
    {
        return $this->withServerRequest($this->serverRequest->withAddedHeader($name, $value));
    }

    public function withoutHeader($name): static
    {
        return $this->withServerRequest($this->serverRequest->withoutHeader($name));
    }

    public function getBody(): StreamInterface
    {
        return $this->serverRequest->getBody();
    }

    public function withBody(StreamInterface $body): static
    {
        return $this->withServerRequest($this->serverRequest->withBody($body));
    }

    private function withServerRequest(ServerRequestInterface $serverRequest): static
    {
        $request = clone $this;
        $request->serverRequest = $serverRequest;

        return $request;
    }
}
