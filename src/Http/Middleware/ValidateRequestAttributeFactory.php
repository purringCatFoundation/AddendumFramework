<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Middleware;

use InvalidArgumentException;
use PCF\Addendum\Http\Middleware\MiddlewareFactoryInterface;
use PCF\Addendum\Http\MiddlewareOptions;
use PCF\Addendum\Validation\RequestValidationPlanFactory;
use PCF\Addendum\Validation\RequestValidationRuleCollection;

class ValidateRequestAttributeFactory implements MiddlewareFactoryInterface
{
    public function create(MiddlewareOptions $options): ValidateRequestAttribute
    {
        $rules = $options->get('validationRules', RequestValidationRuleCollection::empty());

        if (empty($rules)) {
            $rules = RequestValidationRuleCollection::empty();
        }

        if (!$rules instanceof RequestValidationRuleCollection) {
            throw new InvalidArgumentException('Validation rules must be a RequestValidationRuleCollection');
        }

        return new ValidateRequestAttribute(RequestValidationPlanFactory::default()->create($rules));
    }
}
