<?php

declare(strict_types=1);

namespace PCF\Addendum\Auth;

use PCF\Addendum\Cache\RedisCacheFactory;
use PCF\Addendum\Repository\User\ApplicationTokenRepositoryFactory;

class ApplicationTokenValidatorFactory
{
    public function create(): ApplicationTokenValidator
    {
        return new ApplicationTokenValidator(
            new ApplicationTokenRepositoryFactory()->create(),
            new ApplicationTokenHashCache(new RedisCacheFactory()->create())
        );
    }
}
