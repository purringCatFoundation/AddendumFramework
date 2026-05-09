<?php
declare(strict_types=1);

namespace PCF\Addendum\Application\Cache;

enum ApplicationCacheMode: string
{
    case OFF = 'off';
    case AUTO = 'auto';
    case REQUIRED = 'required';
}
