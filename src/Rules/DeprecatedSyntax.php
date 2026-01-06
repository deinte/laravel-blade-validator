<?php

declare(strict_types=1);

namespace Deinte\BladeValidator\Rules;

use Deinte\BladeValidator\Data\BladeValidationError;

/**
 * Detects deprecated Blade syntax and patterns.
 *
 * Flagged patterns:
 * - Old-style section usage
 * - Deprecated helper functions
 * - Legacy component syntax
 * - Outdated Blade features
 */
class DeprecatedSyntax implements BladeRuleInterface
{
    public function getName(): string
    {
        return 'deprecated-syntax';
    }

    public function getDescription(): string
    {
        return 'Detects deprecated Blade syntax and patterns that should be updated to modern equivalents.';
    }

    public function getDefaultSeverity(): string
    {
        return 'warning';
    }

    /**
     * @return array<BladeValidationError>
     */
    public function validate(string $content, string $filePath): array
    {
        $errors = [];

        $deprecatedPatterns = [
            // Old echo syntax {{{ }}} (Laravel 4.x)
            [
                'pattern' => '/\{\{\{\s*.*?\s*\}\}\}/s',
                'message' => 'Triple-brace syntax {{{ }}} is deprecated. Use {{ }} for escaped output.',
                'severity' => 'warning',
            ],

            // @inject without proper use case
            [
                'pattern' => '/@inject\s*\(\s*[\'"][^\'"]+[\'"]\s*,\s*[\'"][^\'"]+[\'"]\s*\)/',
                'message' => '@inject is generally discouraged in Blade templates. Consider passing dependencies through the controller or using view composers.',
                'severity' => 'info',
            ],

            // Old @section with @parent (check for common issues)
            [
                'pattern' => '/@section\s*\([^)]+\)\s*\n\s*@parent\s*\n\s*@endsection/s',
                'message' => '@section with only @parent is redundant. Consider removing the section or adding actual content.',
                'severity' => 'info',
            ],

            // string helper (deprecated in Laravel 6+)
            [
                'pattern' => '/\{\{[^}]*str_(?:limit|random|slug|plural|singular|title|snake|camel|studly|kebab)[^}]*\}\}/i',
                'message' => 'str_* helper functions are deprecated. Use Str::method() instead.',
                'severity' => 'warning',
            ],

            // array helper (deprecated in Laravel 6+)
            [
                'pattern' => '/\{\{[^}]*array_(?:add|collapse|divide|dot|except|first|flatten|forget|get|has|last|only|pluck|prepend|pull|random|set|sort|sortRecursive|where|wrap)[^}]*\}\}/i',
                'message' => 'array_* helper functions are deprecated. Use Arr::method() instead.',
                'severity' => 'warning',
            ],

            // Old collection methods
            [
                'pattern' => '/\{\{[^}]*->lists\s*\([^}]*\}\}/i',
                'message' => 'The lists() method is deprecated. Use pluck() instead.',
                'severity' => 'warning',
            ],

            // e() with double escaping
            [
                'pattern' => '/\{\{\s*e\s*\([^)]+\)\s*\}\}/',
                'message' => 'Using e() inside {{ }} causes double escaping. {{ }} already escapes output.',
                'severity' => 'warning',
            ],

            // htmlspecialchars/htmlentities in output (already escaped)
            [
                'pattern' => '/\{\{\s*(?:htmlspecialchars|htmlentities)\s*\([^)]+\)\s*\}\}/',
                'message' => 'Manual HTML escaping inside {{ }} is redundant. {{ }} already escapes output.',
                'severity' => 'warning',
            ],

            // @elseif with spacing issues
            [
                'pattern' => '/@else\s+if\b/',
                'message' => 'Use @elseif (one word) instead of @else if (two words).',
                'severity' => 'error',
            ],

            // Deprecated asset helpers
            [
                'pattern' => '/\{\{[^}]*elixir\s*\([^}]*\}\}/i',
                'message' => 'elixir() is deprecated. Use mix() or Vite instead.',
                'severity' => 'warning',
            ],

            // Old @stack vs @push usage
            [
                'pattern' => '/@yield\s*\(\s*[\'"](?:scripts|styles)[\'"]\s*\)/',
                'message' => 'Consider using @stack instead of @yield for scripts and styles.',
                'severity' => 'info',
            ],

            // Inline styles that should use classes
            [
                'pattern' => '/style\s*=\s*["\'][^"\']{100,}["\']/',
                'message' => 'Long inline styles detected. Consider using CSS classes instead.',
                'severity' => 'info',
            ],
        ];

        // Add patterns from config
        $configPatterns = config('blade-validator.deprecated_patterns', []);
        $deprecatedPatterns = array_merge($deprecatedPatterns, $configPatterns);

        foreach ($deprecatedPatterns as $check) {
            if (! isset($check['pattern']) || ! isset($check['message'])) {
                continue;
            }

            if (preg_match_all($check['pattern'], $content, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $offset = $match[1];
                    $lineNumber = substr_count(substr($content, 0, $offset), "\n") + 1;

                    $errors[] = new BladeValidationError(
                        file: $filePath,
                        line: $lineNumber,
                        rule: $this->getName(),
                        message: $check['message'],
                        severity: $check['severity'] ?? $this->getDefaultSeverity(),
                    );
                }
            }
        }

        return $errors;
    }
}
