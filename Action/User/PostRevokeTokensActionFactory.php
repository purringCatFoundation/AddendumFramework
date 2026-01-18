<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Action\User;

use Pradzikowski\Framework\Action\ActionFactoryInterface;
use Pradzikowski\Framework\Auth\TokenValidationRepositoryFactory;
use Pradzikowski\Framework\Database\DbConnectionFactory;

class PostRevokeTokensActionFactory implements ActionFactoryInterface
{
    public function create(): PostRevokeTokensAction
    {
        return new PostRevokeTokensAction(
            new TokenValidationRepositoryFactory(
                new DbConnectionFactory()
            )->create()
        );
    }
}
