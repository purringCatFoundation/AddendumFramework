<?php

declare(strict_types=1);

namespace PCF\Addendum\Util;

use PhpToken;

final class ClassNameReader
{
    public static function fromSource(string $source): ?string
    {
        $tokens = PhpToken::tokenize($source);
        $namespace = '';

        foreach ($tokens as $index => $token) {
            if ($token->id === T_NAMESPACE) {
                $namespace = self::namespaceAfter($tokens, $index);
            }

            if ($token->id === T_CLASS) {
                $name = self::classAfter($tokens, $index);
                if ($name !== null) {
                    return $namespace === '' ? $name : $namespace . '\\' . $name;
                }
            }
        }

        return null;
    }

    /** @param list<PhpToken> $tokens */
    private static function namespaceAfter(array $tokens, int $index): string
    {
        $namespace = '';

        for (++$index; isset($tokens[$index]); ++$index) {
            $token = $tokens[$index];
            if ($token->is([';', '{'])) {
                break;
            }
            if ($token->is([T_NAME_QUALIFIED, T_STRING, T_NS_SEPARATOR])) {
                $namespace .= $token->text;
            }
        }

        return $namespace;
    }

    /** @param list<PhpToken> $tokens */
    private static function classAfter(array $tokens, int $index): ?string
    {
        for (++$index; isset($tokens[$index]); ++$index) {
            $token = $tokens[$index];
            if ($token->isIgnorable()) {
                continue;
            }

            return $token->id === T_STRING ? $token->text : null;
        }

        return null;
    }
}
