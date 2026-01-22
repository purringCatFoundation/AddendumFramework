<?php
declare(strict_types=1);

namespace PCF\Addendum\Http;

class MiddlewareOptions
{
    public function __construct(
        public readonly string $actionClass = '',
        public readonly array $additionalData = []
    ) {
    }

    public static function fromArray(array $options): self
    {
        return new self(
            $options['actionClass'] ?? '',
            array_diff_key($options, ['actionClass' => true])
        );
    }

    public function toArray(): array
    {
        return array_merge(
            ['actionClass' => $this->actionClass],
            $this->additionalData
        );
    }

    public function withActionClass(string $actionClass): self
    {
        return new self($actionClass, $this->additionalData);
    }

    public function withAdditionalData(array $data): self
    {
        return new self($this->actionClass, array_merge($this->additionalData, $data));
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->additionalData[$key] ?? $default;
    }
}