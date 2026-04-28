<?php
declare(strict_types=1);

namespace PCF\Addendum\Response;

interface HttpHeadersAware
{
    /**
     * @return array<string, string>
     */
    public function getHeaders(): array;
}
