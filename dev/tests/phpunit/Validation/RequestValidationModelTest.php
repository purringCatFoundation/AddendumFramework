<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Validation;

use PCF\Addendum\Attribute\ValidateRequest;
use PCF\Addendum\Auth\TokenValidationRepository;
use PCF\Addendum\Auth\TokenValidationRepositoryFactory;
use PCF\Addendum\Config\JwtConfig;
use PCF\Addendum\Config\JwtConfigFactory;
use PCF\Addendum\Validation\RequestFieldSource;
use PCF\Addendum\Validation\RequestValidationConstraintInterface;
use PCF\Addendum\Validation\RequestValidationPlanFactory;
use PCF\Addendum\Validation\RequestValidationRuleCollection;
use PCF\Addendum\Validation\RequestValidatorResolver;
use PCF\Addendum\Validation\Rules\JwtToken;
use PCF\Addendum\Validation\Rules\JwtTokenValidator;
use PCF\Addendum\Validation\Rules\JwtTokenValidatorProvider;
use PCF\Addendum\Validation\Rules\Required;
use PCF\Addendum\Validation\SelfValidatingConstraintProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class RequestValidationModelTest extends TestCase
{
    public function testValidateRequestCreatesTypedRule(): void
    {
        $attribute = new ValidateRequest('X-Token', new Required(), RequestFieldSource::Header);

        $rule = $attribute->toRule();

        self::assertSame('X-Token', $rule->fieldName);
        self::assertSame(RequestFieldSource::Header, $rule->source);
        self::assertCount(1, $rule->constraints);
        self::assertInstanceOf(Required::class, $rule->constraints->all()[0]);
    }

    public function testPlanFactoryResolvesSelfValidatingConstraints(): void
    {
        $attribute = new ValidateRequest('name', new Required());
        $rules = RequestValidationRuleCollection::of(
            $attribute->toRule()
        );
        $factory = new RequestValidationPlanFactory(new RequestValidatorResolver([
            new SelfValidatingConstraintProvider(),
        ]));

        $plan = $factory->create($rules);

        self::assertCount(1, $plan);
        self::assertInstanceOf(Required::class, $plan->all()[0]->validators()[0]);
    }

    public function testResolverFailsForUnsupportedConstraint(): void
    {
        $resolver = new RequestValidatorResolver([]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No request validator provider registered');

        $resolver->resolve(new UnsupportedRequestValidationConstraint());
    }

    public function testJwtTokenProviderCreatesRuntimeValidator(): void
    {
        $configFactory = $this->createMock(JwtConfigFactory::class);
        $configFactory->expects(self::once())
            ->method('create')
            ->willReturn(new JwtConfig('0123456789abcdef0123456789abcdef', 7200, 1209600));
        $repositoryFactory = $this->createMock(TokenValidationRepositoryFactory::class);
        $repositoryFactory->expects(self::once())
            ->method('create')
            ->willReturn($this->createMock(TokenValidationRepository::class));

        $provider = new JwtTokenValidatorProvider($configFactory, $repositoryFactory);

        self::assertTrue($provider->supports(new JwtToken()));
        self::assertInstanceOf(JwtTokenValidator::class, $provider->create(new JwtToken()));
    }
}

final class UnsupportedRequestValidationConstraint implements RequestValidationConstraintInterface
{
}
