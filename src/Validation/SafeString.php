<?php
declare(strict_types=1);

namespace PCF\Addendum\Validation;

use PCF\Addendum\Validation\AbstractRequestValidator;

/**
 * Safe string validation rule - XSS protection
 *
 * Detects potentially malicious content (XSS attacks) and optionally
 * sanitizes strings.
 *
 * Example usage:
 * #[ValidateRequest('name', new SafeString())]
 * #[ValidateRequest('description', new SafeString(allowBasicHtml: true))]
 */
class SafeString extends AbstractRequestValidator
{
    private const SUSPICIOUS_PATTERNS = [
        '/<script[\s\S]*?>[\s\S]*?<\/script>/i',
        '/javascript:/i',
        '/on\w+\s*=/i',
        '/<iframe/i',
        '/<embed/i',
        '/<object/i',
        '/vbscript:/i',
        '/data:text\/html/i',
        '/<link/i',
        '/<meta/i',
        '/expression\s*\(/i',
        '/@import/i',
    ];

    public function __construct(
        private readonly bool $allowBasicHtml = false
    ) {}

    public function allowBasicHtml(): bool
    {
        return $this->allowBasicHtml;
    }

    public function validate(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_string($value)) {
            return 'Value must be a string';
        }

        // Check for XSS patterns
        foreach (self::SUSPICIOUS_PATTERNS as $pattern) {
            if (preg_match($pattern, $value)) {
                return 'Input contains potentially malicious content';
            }
        }

        return null;
    }

    /**
     * Static method to sanitize string (can be used in middleware or services)
     *
     * @param string $value Value to sanitize
     * @param bool $allowBasicHtml Allow basic HTML tags
     * @return string Sanitized value
     */
    public static function sanitize(string $value, bool $allowBasicHtml = false): string
    {
        // Remove null bytes
        $value = str_replace("\0", '', $value);

        // Strip tags (allow basic HTML if requested)
        if ($allowBasicHtml) {
            $value = strip_tags($value, '<b><i><em><strong><p><br><ul><ol><li>');
        } else {
            $value = strip_tags($value);
        }

        // Remove control characters (except newlines and tabs)
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);

        // Normalize whitespace
        $value = preg_replace('/\s+/', ' ', $value);

        return trim($value);
    }
}
