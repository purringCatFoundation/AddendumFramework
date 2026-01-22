<?php
declare(strict_types=1);

namespace PCF\Addendum\Auth;

use PCF\Addendum\Action\FactoryInterface;
use PCF\Addendum\Config\JwtConfigFactory;
use PCF\Addendum\Repository\User\AdminRepositoryFactory;
use PCF\Addendum\Repository\User\AuthRepositoryFactory;

class AuthServiceFactory implements FactoryInterface
{
    public function __construct(
        private ?AuthRepositoryFactory $authRepositoryFactory = null,
        private ?TokenValidationRepositoryFactory $tokenValidationRepositoryFactory = null,
        private ?AdminRepositoryFactory $adminRepositoryFactory = null,
        private ?JwtConfigFactory $jwtConfigFactory = null,
        private ?JtiGeneratorFactory $jtiGeneratorFactory = null
    ) {
        $this->authRepositoryFactory ??= new AuthRepositoryFactory();
        $this->tokenValidationRepositoryFactory ??= new TokenValidationRepositoryFactory();
        $this->adminRepositoryFactory ??= new AdminRepositoryFactory();
        $this->jwtConfigFactory ??= new JwtConfigFactory();
        $this->jtiGeneratorFactory ??= new JtiGeneratorFactory();
    }

    public function create(): AuthService
    {
        $repository = $this->authRepositoryFactory->create();
        $tokenValidationRepository = $this->tokenValidationRepositoryFactory->create();
        $adminRepository = $this->adminRepositoryFactory->create();
        $jwtConfig = $this->jwtConfigFactory->create();
        $jtiGenerator = $this->jtiGeneratorFactory->create();

        return new AuthService(
            $repository,
            $tokenValidationRepository,
            $adminRepository,
            $jwtConfig,
            $jtiGenerator
        );
    }
}