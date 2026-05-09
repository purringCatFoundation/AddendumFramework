<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Middleware;

use PCF\Addendum\Exception\HttpException;
use GuzzleHttp\Psr7\Response as PsrResponse;
use GuzzleHttp\Psr7\Utils;
use Ds\Map;
use PCF\Addendum\Validation\RequestAttributeProviderValidatorInterface;
use PCF\Addendum\Validation\RequestValidationErrorBag;
use PCF\Addendum\Validation\RequestValidationPlan;
use PCF\Addendum\Validation\RequestValidationResult;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ValidateRequestAttribute implements MiddlewareInterface
{
    private const int JSON_FLAGS = JSON_THROW_ON_ERROR
        | JSON_HEX_TAG
        | JSON_HEX_AMP
        | JSON_HEX_APOS
        | JSON_HEX_QUOT;

    public function __construct(
        private readonly RequestValidationPlan $plan = new RequestValidationPlan(),
        private readonly RequestFieldValueExtractor $extractor = new RequestFieldValueExtractor()
    ) {
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
        try {
            $result = $this->validateRequest($request);
        } catch (HttpException $exception) {
            $body = Utils::streamFor(json_encode(['error' => $exception->getMessage()], self::JSON_FLAGS));

            return new PsrResponse(
                $exception->getStatusCode(),
                ['Content-Type' => 'application/json'],
                $body
            );
        }
        
        if (!$result->errors->isEmpty()) {
            $body = Utils::streamFor(json_encode(['errors' => $result->errors->toArray()], self::JSON_FLAGS));
            return new PsrResponse(
                400,
                ['Content-Type' => 'application/json'],
                $body
            );
        }

        return $handler->handle($result->request);
    }

    private function validateRequest(ServerRequestInterface $request): RequestValidationResult
    {
        $errors = new RequestValidationErrorBag();

        if ($this->plan->isEmpty()) {
            return new RequestValidationResult($errors, $request);
        }

        $requestAttributes = new Map();

        foreach ($this->plan as $rule) {
            $value = $this->extractor->extract($request, $rule->fieldName, $rule->source);

            // Validate with all validators for this field
            foreach ($rule->validators() as $validator) {
                $error = $validator->validate($value);
                if ($error !== null) {
                    $errors->add($rule->fieldName, $error);

                    continue;
                }

                if ($validator instanceof RequestAttributeProviderValidatorInterface) {
                    foreach ($validator->requestAttributes($value) as $name => $attributeValue) {
                        $requestAttributes->put($name, $attributeValue);
                    }
                }
            }
        }

        if ($errors->isEmpty()) {
            foreach ($requestAttributes as $name => $value) {
                $request = $request->withAttribute($name, $value);
            }
        }

        return new RequestValidationResult($errors, $request);
    }
}
