<?php
declare(strict_types=1);

namespace PCF\Addendum\Exception;

use RuntimeException;

class HttpException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly int $statusCode,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public static function badRequest(string $message = 'Bad Request'): self
    {
        return new self($message, 400);
    }

    public static function unauthorized(string $message = 'Unauthorized'): self
    {
        return new self($message, 401);
    }

    public static function forbidden(string $message = 'Forbidden'): self
    {
        return new self($message, 403);
    }

    public static function notFound(string $message = 'Not Found'): self
    {
        return new self($message, 404);
    }

    public static function conflict(string $message = 'Conflict'): self
    {
        return new self($message, 409);
    }

    public static function unprocessableEntity(string $message = 'Unprocessable Entity'): self
    {
        return new self($message, 422);
    }

    public static function internalServerError(string $message = 'Internal Server Error'): self
    {
        return new self($message, 500);
    }
}