<?php

declare(strict_types=1);

namespace PCF\Addendum\Http\Routing;

use Ds\Map;
use Ds\Vector;
use Nette\PhpGenerator\Dumper;
use PCF\Addendum\Attribute\RateLimit;
use PCF\Addendum\Http\Middleware\AccessControlGuardianCollection;
use PCF\Addendum\Http\Middleware\ClassAccessControlGuardianDefinition;
use PCF\Addendum\Http\MiddlewareOptions;
use PCF\Addendum\Validation\Compiled\RequestValidationRuleCodeGenerator;
use PCF\Addendum\Validation\RequestValidationRuleCollection;
use RuntimeException;
use UnitEnum;

final class RouteMiddlewareOptionsCodeGenerator
{
    public function generate(MiddlewareOptions $options): string
    {
        if ($options->additionalData->isEmpty()) {
            return 'new \\' . MiddlewareOptions::class . '()';
        }

        return sprintf(
            'new \\%s(additionalData: %s)',
            MiddlewareOptions::class,
            $this->valueCode($options->additionalData)
        );
    }

    public function valueCode(mixed $value): string
    {
        return match (true) {
            $value instanceof RequestValidationRuleCollection =>
                new RequestValidationRuleCodeGenerator()->generateCollection($value),
            $value instanceof AccessControlGuardianCollection => $this->guardiansCode($value),
            $value instanceof RateLimit => $this->rateLimitCode($value),
            $value instanceof Map => $this->arrayCode($value->toArray()),
            $value instanceof Vector => $this->arrayCode($value->toArray()),
            $value instanceof UnitEnum => sprintf('\\%s::%s', ltrim($value::class, '\\'), $value->name),
            is_array($value) => $this->arrayCode($value),
            default => $this->scalarCode($value),
        };
    }

    private function rateLimitCode(RateLimit $limit): string
    {
        return sprintf(
            'new \\%s(maxAttempts: %d, windowSeconds: %d, scope: %s, scopeKey: %s, errorMessage: %s)',
            RateLimit::class,
            $limit->maxAttempts,
            $limit->windowSeconds,
            $this->scalarCode($limit->scope),
            $this->scalarCode($limit->scopeKey),
            $this->scalarCode($limit->errorMessage)
        );
    }

    private function guardiansCode(AccessControlGuardianCollection $guardians): string
    {
        if ($guardians->isEmpty()) {
            return 'new \\' . AccessControlGuardianCollection::class . '()';
        }
        $items = [];
        foreach ($guardians as $guardian) {
            if (!$guardian instanceof ClassAccessControlGuardianDefinition) {
                throw new RuntimeException(sprintf(
                    'Cannot compile access control guardian of type %s',
                    $guardian::class
                ));
            }
            $items[] = sprintf(
                'new \\%s(guardianClass: \\%s::class)',
                ClassAccessControlGuardianDefinition::class,
                ltrim($guardian->guardianClass, '\\')
            );
        }

        return sprintf(
            "new \\%s([\n%s,\n])",
            AccessControlGuardianCollection::class,
            $this->indent(implode(",\n", $items))
        );
    }

    /** @param array<array-key, mixed> $values */
    private function arrayCode(array $values): string
    {
        if ($values === []) {
            return '[]';
        }
        $items = [];
        $isList = array_is_list($values);
        foreach ($values as $key => $value) {
            $code = $this->valueCode($value);
            $items[] = $isList ? $code : $this->scalarCode($key) . ' => ' . $code;
        }

        return "[\n" . $this->indent(implode(",\n", $items)) . ",\n]";
    }

    private function scalarCode(mixed $value): string
    {
        if (is_object($value) || is_resource($value)) {
            throw new RuntimeException(sprintf(
                'Cannot compile value of type %s into route cache',
                get_debug_type($value)
            ));
        }

        return new Dumper()->dump($value);
    }

    private function indent(string $code): string
    {
        return preg_replace('/^/m', '    ', $code) ?? $code;
    }
}
