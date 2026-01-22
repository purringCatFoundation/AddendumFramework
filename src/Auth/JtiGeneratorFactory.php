<?php
declare(strict_types=1);

namespace PCF\Addendum\Auth;

class JtiGeneratorFactory
{
    public function create(): JtiGenerator
    {
        return new JtiGenerator();
    }
}
