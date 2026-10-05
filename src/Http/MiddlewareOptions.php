<?php

declare(strict_types=1);

namespace PCF\Addendum\Http;

use Ds\Map;

class MiddlewareOptions
{
    /** @var Map<string, mixed> */
    public readonly Map $additionalData;

    /** @param iterable<string, mixed> $additionalData */
    public function __construct(
        iterable $additionalData = []
    ) {
        $this->additionalData = $additionalData instanceof Map
            ? $additionalData->copy()
            : new Map($additionalData);
    }

    /** @param array<string, mixed> $options */
    public static function fromArray(array $options): self
    {
        return new self(array_diff_key($options, ['actionClass' => true]));
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->additionalData->toArray();
    }

    /** @param iterable<string, mixed> $data */
    public function withAdditionalData(iterable $data): self
    {
        $merged = $this->additionalData->copy();

        foreach ($data as $key => $value) {
            $merged->put($key, $value);
        }

        return new self($merged);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->additionalData->hasKey($key) ? $this->additionalData->get($key) : $default;
    }
}
