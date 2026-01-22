<?php
declare(strict_types=1);

namespace PCF\Addendum\Http;

class RouteMiddleware
{
    public function __construct(
        private string $class,
        private MiddlewareOptions $options
    ) {
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function getOptions(): MiddlewareOptions
    {
        return $this->options;
    }

    public function withActionClass(string $actionClass): self
    {
        $this->options = $this->options->withActionClass($actionClass);
        return $this;
    }

    public function addOption(string $key, mixed $value): self
    {
        $this->options = $this->options->withAdditionalData([$key => $value]);
        return $this;
    }

    public function addOptions(array $options): self
    {
        $this->options = $this->options->withAdditionalData($options);
        return $this;
    }
}