<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Cache;

use GuzzleHttp\Psr7\Response as PsrResponse;
use GuzzleHttp\Psr7\Utils;
use JsonException;
use Psr\Http\Message\ResponseInterface;

final readonly class HttpCachedResponse
{
    /**
     * @param array<string, list<string>> $headers
     */
    public function __construct(
        public int $statusCode,
        public array $headers,
        public string $body
    ) {
    }

    public static function fromResponse(ResponseInterface $response): self
    {
        return new self(
            statusCode: $response->getStatusCode(),
            headers: array_diff_key($response->getHeaders(), [
                'X-Redis-Cache' => true,
                'X-Http-Cache' => true,
                'X-Http-Cache-Provider' => true,
            ]),
            body: self::bodyContents($response)
        );
    }

    public static function fromJson(string $payload): ?self
    {
        try {
            $data = json_decode($payload, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        if (!is_array($data) || !isset($data['statusCode'], $data['headers'], $data['body'])) {
            return null;
        }

        if (!is_int($data['statusCode']) || !is_array($data['headers']) || !is_string($data['body'])) {
            return null;
        }

        return new self($data['statusCode'], $data['headers'], $data['body']);
    }

    public function toResponse(): ResponseInterface
    {
        return new PsrResponse(
            status: $this->statusCode,
            headers: $this->headers,
            body: Utils::streamFor($this->body)
        );
    }

    public function toJson(): string
    {
        return json_encode([
            'statusCode' => $this->statusCode,
            'headers' => $this->headers,
            'body' => $this->body,
        ], JSON_THROW_ON_ERROR);
    }

    private static function bodyContents(ResponseInterface $response): string
    {
        $body = $response->getBody();
        $position = $body->isSeekable() ? $body->tell() : null;

        if ($body->isSeekable()) {
            $body->rewind();
        }

        $contents = $body->getContents();

        if ($position !== null) {
            $body->seek($position);
        }

        return $contents;
    }
}
