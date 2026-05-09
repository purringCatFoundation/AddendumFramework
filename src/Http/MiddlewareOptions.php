<?php
declare(strict_types=1);

namespace PCF\Addendum\Http;

use Ds\Map;

class MiddlewareOptions
{
    /** @var Map<string, mixed> */
    public readonly Map $additionalData;

    public function __construct(
        iterable $additionalData = []
    ) {
        $this->additionalData = $additionalData instanceof Map
            ? $additionalData->copy()
            : new Map($additionalData);
    }

    public static function fromArray(array $options): self
    {
        return new self(array_diff_key($options, ['actionClass' => true]));
    }

    public function toArray(): array
    {
        return $this->additionalData->toArray();
    }

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
