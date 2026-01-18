<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Exception;

use RuntimeException;

class InvalidCredentialsException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Invalid credentials');
    }
}

