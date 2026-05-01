<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Cache;

final class HttpCacheHeader
{
    public static function cacheControl(HttpCachePolicy $policy): string
    {
        if ($policy->mode === HttpCacheMode::PRIVATE) {
            return 'private, no-store';
        }

        $directives = ['public'];

        if ($policy->maxAge !== null) {
            $directives[] = 'max-age=' . $policy->maxAge;
        }

        if ($policy->sharedMaxAge !== null) {
            $directives[] = 's-maxage=' . $policy->sharedMaxAge;
        }

        if ($policy->staleWhileRevalidate !== null) {
            $directives[] = 'stale-while-revalidate=' . $policy->staleWhileRevalidate;
        }

        if ($policy->staleIfError !== null) {
            $directives[] = 'stale-if-error=' . $policy->staleIfError;
        }

        return implode(', ', $directives);
    }

    /**
     * @param list<string> $values
     */
    public static function headerList(array $values, string $separator = ', '): string
    {
        $clean = array_values(array_unique(array_filter(
            array_map(static fn(string $value): string => trim($value), $values),
            static fn(string $value): bool => $value !== ''
        )));

        return implode($separator, $clean);
    }

    /**
     * @param list<string> $tags
     */
    public static function cacheTags(array $tags, string $separator = ','): string
    {
        $clean = array_map(
            static fn(string $tag): string => preg_replace('/[^A-Za-z0-9_:\-.]/', '-', trim($tag)) ?? '',
            $tags
        );

        return self::headerList($clean, $separator);
    }
}
