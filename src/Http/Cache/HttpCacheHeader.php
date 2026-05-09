<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Cache;

use Ds\Vector;

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

    /** @param iterable<string> $values */
    public static function headerList(iterable $values, string $separator = ', '): string
    {
        $clean = new Vector();

        foreach ($values as $value) {
            $value = trim($value);

            if ($value !== '' && !$clean->contains($value)) {
                $clean->push($value);
            }
        }

        return implode($separator, $clean->toArray());
    }

    /** @param iterable<string> $tags */
    public static function cacheTags(iterable $tags, string $separator = ','): string
    {
        $clean = new Vector();

        foreach ($tags as $tag) {
            $clean->push(preg_replace('/[^A-Za-z0-9_:\-.]/', '-', trim($tag)) ?? '');
        }

        return self::headerList($clean, $separator);
    }
}
