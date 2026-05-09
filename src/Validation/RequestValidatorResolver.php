<?php
declare(strict_types=1);

namespace PCF\Addendum\Validation;

use Ds\Vector;
use RuntimeException;

final readonly class RequestValidatorResolver
{
    /** @var Vector<RequestValidatorProviderInterface> */
    private Vector $providers;

    public function __construct(iterable $providers)
    {
        $this->providers = new Vector();

        foreach ($providers as $provider) {
            $this->providers->push($provider);
        }
    }

    public function resolve(RequestValidationConstraintInterface $constraint): RequestValidatorInterface
    {
        foreach ($this->providers as $provider) {
            if ($provider->supports($constraint)) {
                return $provider->create($constraint);
            }
        }

        throw new RuntimeException(sprintf('No request validator provider registered for %s', $constraint::class));
    }
}
