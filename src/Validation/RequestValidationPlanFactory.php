<?php
declare(strict_types=1);

namespace PCF\Addendum\Validation;

use PCF\Addendum\Auth\TokenValidationRepositoryFactory;
use PCF\Addendum\Config\JwtConfigFactory;
use PCF\Addendum\Config\SystemEnvironmentProvider;
use PCF\Addendum\Database\DbConnectionFactory;
use PCF\Addendum\Validation\Rules\JwtTokenValidatorProvider;

final readonly class RequestValidationPlanFactory
{
    public function __construct(
        private RequestValidatorResolver $resolver
    ) {
    }

    public static function default(): self
    {
        return new self(new RequestValidatorResolver([
            new JwtTokenValidatorProvider(
                new JwtConfigFactory(new SystemEnvironmentProvider()),
                new TokenValidationRepositoryFactory(new DbConnectionFactory())
            ),
            new SelfValidatingConstraintProvider(),
        ]));
    }

    public function create(RequestValidationRuleCollection $rules): RequestValidationPlan
    {
        $planRules = [];

        foreach ($rules as $rule) {
            $validators = [];

            foreach ($rule->constraints as $constraint) {
                $validators[] = $this->resolver->resolve($constraint);
            }

            $planRules[] = new RequestValidationPlanRule($rule->fieldName, $rule->source, $validators);
        }

        return new RequestValidationPlan($planRules);
    }
}
