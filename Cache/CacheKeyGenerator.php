<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Cache;

use Psr\Http\Message\ServerRequestInterface;

class CacheKeyGenerator
{
    /**
     * Generate cache key based on path, selected params and optionally session token.
     *
     * @param ServerRequestInterface $request
     * @param bool $useSession When true, token_jti attribute will be included.
     * @param string[] $params List of query or attribute params to include.
     */
    public function generate(ServerRequestInterface $request, bool $useSession, array $params): string
    {
        $parts = [$request->getUri()->getPath()];
        foreach ($params as $param) {
            $value = $request->getAttribute($param);
            if ($value === null) {
                $query = $request->getQueryParams();
                $value = $query[$param] ?? '';
            }
            $parts[] = $param . '=' . $value;
        }
        if ($useSession) {
            $sessionId = (string) $request->getAttribute('token_jti', '');
            $parts[] = 'session=' . $sessionId;
        }
        return md5(implode('|', $parts));
    }
}
