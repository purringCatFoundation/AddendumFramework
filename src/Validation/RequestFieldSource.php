<?php
declare(strict_types=1);

namespace PCF\Addendum\Validation;

use InvalidArgumentException;

enum RequestFieldSource: string
{
    case Body = 'body';
    case Query = 'query';
    case Header = 'header';

    public static function fromString(string $source): self
    {
        return self::tryFrom($source)
            ?? throw new InvalidArgumentException(sprintf('Unsupported request field source "%s"', $source));
    }
}
