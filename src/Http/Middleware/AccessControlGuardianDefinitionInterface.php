<?php
declare(strict_types=1);

namespace PCF\Addendum\Http\Middleware;

use PCF\Addendum\Auth\Session;
use Psr\Http\Message\ServerRequestInterface;

interface AccessControlGuardianDefinitionInterface
{
    public function authorize(ServerRequestInterface $request, Session $session): void;
}
