<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Auth;

use Pradzikowski\Framework\Action\FactoryInterface;
use Pradzikowski\Framework\Config\JwtConfigFactory;
use Pradzikowski\Framework\Repository\User\AdminRepositoryFactory;

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