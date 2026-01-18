<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Action;

use Pradzikowski\Framework\Http\Request;
use JsonSerializable;

interface ActionInterface
{
    public function __invoke(Request $request): JsonSerializable;
}
