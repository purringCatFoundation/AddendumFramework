<?php
declare(strict_types=1);

namespace PCF\Addendum\Tests\Auth;

use PCF\Addendum\Auth\JtiGenerator;
use PHPUnit\Framework\TestCase;

class JtiGeneratorTest extends TestCase
{
    private JtiGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new JtiGenerator();
    }

    public function testGenerateReturnsValidUuidFormat(): void
    {
        $jti = $this->generator->generate();

        // UUID v4 format: xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx
        $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';

        $this->assertMatchesRegularExpression($pattern, $jti);
    }

    public function testGenerateReturnsUniqueValues(): void
    {
        $jti1 = $this->generator->generate();
        $jti2 = $this->generator->generate();

        $this->assertNotEquals($jti1, $jti2);
    }

    public function testGenerateReturnsUuidVersion4(): void
    {
        $jti = $this->generator->generate();
        $parts = explode('-', $jti);

        // Version 4 UUID has '4' as first character of third group
        $this->assertStringStartsWith('4', $parts[2]);
    }

    public function testGenerateReturnsUuidWithCorrectVariant(): void
    {
        $jti = $this->generator->generate();
        $parts = explode('-', $jti);

        // Variant bits (first char of fourth group) should be 8, 9, a, or b
        $firstChar = strtolower($parts[3][0]);
        $this->assertContains($firstChar, ['8', '9', 'a', 'b']);
    }

    public function testGenerateReturnsStringLength36(): void
    {
        $jti = $this->generator->generate();

        // UUID format is always 36 characters (32 hex + 4 hyphens)
        $this->assertSame(36, strlen($jti));
    }

    public function testGenerateMultipleTimesProducesUniqueValues(): void
    {
        $jtis = [];

        for ($i = 0; $i < 1000; $i++) {
            $jtis[] = $this->generator->generate();
        }

        // All generated JTIs should be unique
        $uniqueJtis = array_unique($jtis);
        $this->assertCount(1000, $uniqueJtis);
    }
}
