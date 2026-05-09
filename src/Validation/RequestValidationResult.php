<?php
declare(strict_types=1);

namespace PCF\Addendum\Validation;

use Psr\Http\Message\ServerRequestInterface;

final readonly class RequestValidationResult
{
    public function __construct(
        public RequestValidationErrorBag $errors,
        public ServerRequestInterface $request
    ) {
    }
}
