<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Middleware;

use PCF\Addendum\Auth\Session;
use PCF\Addendum\Guardian\AccessControlGuardianInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

final readonly class ClassAccessControlGuardianDefinition implements AccessControlGuardianDefinitionInterface
{
    /**
     * @param class-string $guardianClass
     */
    public function __construct(
        public string $guardianClass
    ) {
    }

    public function authorize(ServerRequestInterface $request, Session $session): void
    {
        $instance = new $this->guardianClass();

        if (!$instance instanceof AccessControlGuardianInterface) {
            throw new RuntimeException("Guardian '{$this->guardianClass}' does not implement AccessControlGuardianInterface");
        }

        $instance->authorize($request, $session);
    }
}
