<?php
declare(strict_types=1);

namespace PCF\Addendum\Command;

use PCF\Addendum\Repository\User\ApplicationTokenRepositoryFactory;

final class RevokeApplicationTokensCommandFactory
{
    public function create(): RevokeApplicationTokensCommand
    {
        $tokenRepositoryFactory = new ApplicationTokenRepositoryFactory();
        $tokenRepository = $tokenRepositoryFactory->create();

        return new RevokeApplicationTokensCommand($tokenRepository);
    }
}
