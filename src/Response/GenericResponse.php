<?php
declare(strict_types=1);

namespace PCF\Addendum\Response;

use JsonSerializable;

class GenericResponse implements JsonSerializable, HttpStatusAware
{
    public function __construct(
        private readonly bool    $success,
        private readonly string  $message,
        private readonly ?array  $data = null,
        private readonly int     $statusCode = 200
    ) {
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function jsonSerialize(): array
    {
        $response = [
            'success' => $this->success,
            'message' => $this->message
        ];

        if ($this->data !== null) {
            $response['data'] = $this->data;
        }

        return $response;
    }
}