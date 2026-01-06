<?php

declare(strict_types=1);

namespace Deinte\BladeValidator\Rules;

use Deinte\BladeValidator\Data\BladeValidationError;

/**
 * Detects PHP use statements inside @php blocks.
 *
 * Invalid:
 * @php
 *     use App\Models\User;
 * @endphp
 *
 * Valid alternatives:
 * - Use fully qualified class names: \App\Models\User::find(1)
 * - Use @use directive at file top: @use('App\Models\User')
 */
class UseStatementInPhpBlock implements BladeRuleInterface
{
    public function getName(): string
    {
        return 'use-statement-in-php-block';
    }

    public function getDescription(): string
    {
        return 'Detects PHP use statements inside @php blocks, which do not work as expected in Blade.';
    }

    public function getDefaultSeverity(): string
    {
        return 'error';
    }

    /**
     * @return array<BladeValidationError>
     */
    public function validate(string $content, string $filePath): array
    {
        $errors = [];

        // Pattern to find @php ... @endphp blocks
        $pattern = '/@php\s*(.*?)@endphp/s';

        if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[1] as $match) {
                $phpContent = $match[0];
                $offset = $match[1];

                // Calculate line number
                $lineNumber = substr_count(substr($content, 0, $offset), "\n") + 1;

                // Check for use statements
                // Pattern: use SomeNamespace\SomeClass; (at start or after whitespace/semicolon)
                if (preg_match('/(?:^|\s|;)\s*use\s+[A-Z][\w\\\\]+(\s*,\s*[A-Z][\w\\\\]+)*\s*;/m', $phpContent)) {
                    $errors[] = new BladeValidationError(
                        file: $filePath,
                        line: $lineNumber,
                        rule: $this->getName(),
                        message: 'Use statement found inside @php block. Use the @use directive at the top of the file or use fully qualified class names instead.',
                        severity: $this->getDefaultSeverity(),
                    );
                }
            }
        }

        return $errors;
    }
}
