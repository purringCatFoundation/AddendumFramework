<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Middleware;

use PCF\Addendum\Attribute\ValidateRequest;
use PCF\Addendum\Validation\RequestValidatorInterface;
use GuzzleHttp\Psr7\Response as PsrResponse;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionClass;

class ValidateRequestAttribute implements MiddlewareInterface
{
    public function __construct(private readonly string $actionClass)
    {
    }

    /**
     * Validate request using ValidateRequest attributes on action class
     *
     * @param ServerRequestInterface $request HTTP request to validate
     * @param RequestHandlerInterface $handler Next request handler
     * @return ResponseInterface HTTP 400 with errors or continue to handler
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $result = $this->validateRequest($request);
        
        if (!empty($result['errors'])) {
            $body = Utils::streamFor(json_encode(['errors' => $result['errors']]));
            return new PsrResponse(
                400,
                ['Content-Type' => 'application/json'],
                $body
            );
        }

        return $handler->handle($result['request']);
    }

    private function validateRequest(ServerRequestInterface $request): array
    {
        $reflection = new ReflectionClass($this->actionClass);
        $validateRequestAttributes = $reflection->getAttributes(ValidateRequest::class);

        if (empty($validateRequestAttributes)) {
            return ['errors' => [], 'request' => $request];
        }

        $allErrors = [];

        foreach ($validateRequestAttributes as $attributeReflection) {
            $validateRequestAttribute = $attributeReflection->newInstance();

            $fieldName = $validateRequestAttribute->fieldName;
            $source = $validateRequestAttribute->getSource();
            $validators = $validateRequestAttribute->validators;

            $value = $this->getFieldValue($request, $fieldName, $source);

            // Validate with all validators for this field
            foreach ($validators as $validator) {
                $error = $validator->validate($value);
                if ($error !== null) {
                    if (!isset($allErrors[$fieldName])) {
                        $allErrors[$fieldName] = [];
                    }
                    $allErrors[$fieldName][] = $error;
                } else {
                    if ($validator instanceof \Pradzikowski\Game\Validation\JwtToken) {
                        $request = $request->withAttribute('jwt_token', $value);
                    }
                }
            }
        }

        return ['errors' => $allErrors, 'request' => $request];
    }

    private function getFieldValue(ServerRequestInterface $request, string $fieldName, string $source): mixed
    {
        return match ($source) {
            ValidateRequest::SOURCE_HEADER => $this->getHeaderValue($request, $fieldName),
            ValidateRequest::SOURCE_QUERY => $request->getQueryParams()[$fieldName] ?? null,
            ValidateRequest::SOURCE_BODY => $this->getRequestData($request)[$fieldName] ?? null,
            default => null
        };
    }

    private function getHeaderValue(ServerRequestInterface $request, string $fieldName): mixed
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

    private function getRequestData(ServerRequestInterface $request): array
    {
        $contentType = $request->getHeaderLine('Content-Type');
        
        if (str_contains($contentType, 'application/json')) {
            $body = (string) $request->getBody();
            return json_decode($body, true) ?? [];
        }
        
        return $request->getParsedBody() ?? [];
    }
}