<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Cache;

use Psr\Http\Message\ServerRequestInterface;

final class HttpCacheKeyGenerator
{
    public function generate(
        string $keyPrefix,
        HttpCachePolicy $policy,
        ServerRequestInterface $request,
        HttpCacheRequestContext $context
    ): string {
        $uri = $request->getUri();
        $parts = [
            strtoupper($request->getMethod()),
            $uri->getPath(),
            $uri->getQuery(),
            $policy->mode->value,
        ];

        foreach ($policy->vary as $header) {
            $parts[] = strtolower($header) . '=' . $request->getHeaderLine($header);
        }

        if ($policy->mode === HttpCacheMode::GUEST_AWARE) {
            $parts[] = 'authState=' . $context->authState;
        }

        if ($policy->mode === HttpCacheMode::USER_AWARE) {
            $parts[] = 'userContext=' . ($context->userContextHash ?? '');
        }

        return $keyPrefix . 'response:' . hash('sha256', implode("\n", $parts));
    }
}
