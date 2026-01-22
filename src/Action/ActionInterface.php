<?php
declare(strict_types=1);

namespace PCF\Addendum\Action;

use PCF\Addendum\Http\Request;
use JsonSerializable;

interface ActionInterface
{
    public function __invoke(Request $request): JsonSerializable;
}
