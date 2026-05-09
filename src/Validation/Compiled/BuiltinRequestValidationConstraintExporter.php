<?php
declare(strict_types=1);

namespace PCF\Addendum\Validation\Compiled;

use Ds\Vector;
use Nette\PhpGenerator\Dumper;
use PCF\Addendum\Validation\PasswordStrength;
use PCF\Addendum\Validation\RequestValidationConstraintInterface;
use PCF\Addendum\Validation\Rules\Email;
use PCF\Addendum\Validation\Rules\In;
use PCF\Addendum\Validation\Rules\JwtToken;
use PCF\Addendum\Validation\Rules\MaxLength;
use PCF\Addendum\Validation\Rules\MinLength;
use PCF\Addendum\Validation\Rules\Numeric;
use PCF\Addendum\Validation\Rules\Pattern;
use PCF\Addendum\Validation\Rules\Required;
use PCF\Addendum\Validation\Rules\Uuid;
use PCF\Addendum\Validation\SafeString;
use RuntimeException;
use UnitEnum;

final readonly class BuiltinRequestValidationConstraintExporter implements RequestValidationConstraintExporterInterface
{
    private const array NO_ARGUMENT_CONSTRAINTS = [
        Required::class,
        Email::class,
        Numeric::class,
        Uuid::class,
        PasswordStrength::class,
    ];

    public function supports(RequestValidationConstraintInterface $constraint): bool
    {
        return in_array($constraint::class, self::NO_ARGUMENT_CONSTRAINTS, true)
            || $constraint instanceof MinLength
            || $constraint instanceof MaxLength
            || $constraint instanceof Pattern
            || $constraint instanceof In
            || $constraint instanceof JwtToken
            || $constraint instanceof SafeString;
    }

    public function export(RequestValidationConstraintInterface $constraint): string
    {
        foreach (self::NO_ARGUMENT_CONSTRAINTS as $constraintClass) {
            if ($constraint::class === $constraintClass) {
                return sprintf('new \\%s()', $constraintClass);
            }
        }

        return match (true) {
            $constraint instanceof MinLength => sprintf(
                'new \\%s(minLength: %d)',
                MinLength::class,
                $constraint->minLength()
            ),
            $constraint instanceof MaxLength => sprintf(
                'new \\%s(maxLength: %d)',
                MaxLength::class,
                $constraint->maxLength()
            ),
            $constraint instanceof Pattern => sprintf(
                'new \\%s(pattern: %s, errorMessage: %s)',
                Pattern::class,
                $this->valueCode($constraint->pattern()),
                $this->valueCode($constraint->errorMessage())
            ),
            $constraint instanceof In => sprintf(
                'new \\%s(allowedValues: %s)',
                In::class,
                $this->valueCode($constraint->allowedValues())
            ),
            $constraint instanceof JwtToken => sprintf(
                'new \\%s(requiredTokenType: %s)',
                JwtToken::class,
                $this->valueCode($constraint->requiredTokenType())
            ),
            $constraint instanceof SafeString => sprintf(
                'new \\%s(allowBasicHtml: %s)',
                SafeString::class,
                $constraint->allowBasicHtml() ? 'true' : 'false'
            ),
            default => throw new RuntimeException(sprintf('Cannot export unsupported validation constraint %s', $constraint::class)),
        };
    }

    private function valueCode(mixed $value): string
    {
        if ($value instanceof UnitEnum) {
            return sprintf('\\%s::%s', ltrim($value::class, '\\'), $value->name);
        }

        if (is_array($value)) {
            return $this->arrayCode($value);
        }

        if ($value instanceof Vector) {
            return $this->arrayCode($value->toArray());
        }

        if (is_object($value) || is_resource($value)) {
            throw new RuntimeException(sprintf('Cannot export validation value of type %s', get_debug_type($value)));
        }

        return new Dumper()->dump($value);
    }

    private function arrayCode(array $values): string
    {
        if ($values === []) {
            return '[]';
        }

        $items = [];
        $isList = array_is_list($values);

        foreach ($values as $key => $value) {
            $valueCode = $this->valueCode($value);
            $items[] = $isList
                ? $valueCode
                : new Dumper()->dump($key) . ' => ' . $valueCode;
        }

        return '[' . implode(', ', $items) . ']';
    }
}
