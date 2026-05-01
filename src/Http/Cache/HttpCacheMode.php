<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Cache;

enum HttpCacheMode: string
{
    case PUBLIC = 'public';
    case GUEST_AWARE = 'guestAware';
    case USER_AWARE = 'userAware';
    case PRIVATE = 'private';
}
