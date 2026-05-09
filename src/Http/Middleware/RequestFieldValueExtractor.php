<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Middleware;

use Ds\Map;
use JsonException;
use PCF\Addendum\Exception\HttpException;
use PCF\Addendum\Validation\RequestFieldSource;
use Psr\Http\Message\ServerRequestInterface;

final readonly class RequestFieldValueExtractor
{
    public function extract(ServerRequestInterface $request, string $fieldName, RequestFieldSource $source): mixed
    {
        if ($source === RequestFieldSource::Header) {
            return $this->headerValue($request, $fieldName);
        }

        $data = $source === RequestFieldSource::Query
            ? $this->queryData($request)
            : $this->requestData($request);

        return $data->hasKey($fieldName) ? $data->get($fieldName) : null;
    }

    private function headerValue(ServerRequestInterface $request, string $fieldName): mixed
    {
        if ($fieldName === 'jwt_token') {
            $authHeader = $request->getHeaderLine('Authorization');
            if (preg_match('/Bearer\s+(.+)$/i', $authHeader, $matches)) {
                return trim($matches[1]);
            }

            return null;
        }

        $headerValue = $request->getHeaderLine($fieldName);

        return $headerValue !== '' ? $headerValue : null;
    }

    /** @return Map<string, mixed> */
    private function queryData(ServerRequestInterface $request): Map
    {
        return new Map($request->getQueryParams());
    }

    /** @return Map<string, mixed> */
    private function requestData(ServerRequestInterface $request): Map
    {
        $contentType = $request->getHeaderLine('Content-Type');

        if (str_contains($contentType, 'application/json')) {
            $body = (string) $request->getBody();

            if (trim($body) === '') {
                return new Map();
            }

            try {
                $data = json_decode($body, true, flags: JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                throw HttpException::badRequest('Malformed JSON request body');
            }

            return is_array($data) ? new Map($data) : new Map();
        }

        $parsedBody = $request->getParsedBody();

        return is_array($parsedBody) ? new Map($parsedBody) : new Map();
    }
}
