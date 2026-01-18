<?php
declare(strict_types=1);

namespace Pradzikowski\Framework\Auth;

class JtiGeneratorFactory
{
    public function create(): JtiGenerator
    {
        return new JtiGenerator();
    }
}
