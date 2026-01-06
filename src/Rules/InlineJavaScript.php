<?php

declare(strict_types=1);

namespace Deinte\BladeValidator\Rules;

use Deinte\BladeValidator\Data\BladeValidationError;

/**
 * Detects inline JavaScript with Blade expressions that may be XSS vectors.
 *
 * Flagged patterns:
 * - onclick="{{ $var }}" - Event handlers with Blade variables
 * - javascript:{{ $link }} - JavaScript protocol with Blade
 * - <script>{{ $var }}</script> - Script tags with Blade
 * - onload="{{ $code }}" - Event handlers with dynamic code
 */
class InlineJavaScript implements BladeRuleInterface
{
    /**
     * Event handlers that should not contain Blade expressions.
     *
     * @var array<string>
     */
    private array $eventHandlers = [
        'onclick',
        'ondblclick',
        'onchange',
        'onsubmit',
        'onload',
        'onerror',
        'onmouseover',
        'onmouseout',
        'onmousedown',
        'onmouseup',
        'onmousemove',
        'onfocus',
        'onblur',
        'onkeydown',
        'onkeyup',
        'onkeypress',
        'oninput',
        'onscroll',
        'onresize',
        'oncontextmenu',
        'ondrag',
        'ondrop',
        'oncopy',
        'onpaste',
        'oncut',
    ];

    public function getName(): string
    {
        return 'inline-javascript';
    }

    public function getDescription(): string
    {
        return 'Detects inline JavaScript patterns with Blade expressions that may lead to XSS vulnerabilities.';
    }

    public function getDefaultSeverity(): string
    {
        $severity = config('blade-validator.inline_javascript.severity');

        return is_string($severity) ? $severity : 'warning';
    }

    /**
     * @return array<BladeValidationError>
     */
    public function validate(string $content, string $filePath): array
    {
        $errors = [];

        // Check for event handlers with Blade expressions
        $errors = array_merge($errors, $this->checkEventHandlers($content, $filePath));

        // Check for javascript: protocol with Blade
        $errors = array_merge($errors, $this->checkJavaScriptProtocol($content, $filePath));

        // Check for script tags with Blade expressions
        $errors = array_merge($errors, $this->checkScriptTags($content, $filePath));

        // Check for eval-like patterns
        $errors = array_merge($errors, $this->checkEvalPatterns($content, $filePath));

        return $errors;
    }

    /**
     * Check for event handlers containing Blade expressions.
     *
     * @return array<BladeValidationError>
     */
    private function checkEventHandlers(string $content, string $filePath): array
    {
        $errors = [];

        // Use configured event handlers or defaults
        $handlers = config('blade-validator.inline_javascript.event_handlers', $this->eventHandlers);
        $handlersPattern = implode('|', $handlers);

        // Pattern: on*="...{{ ... }}..." or on*='...{{ ... }}...'
        $pattern = '/('.$handlersPattern.')\s*=\s*["\'][^"\']*\{\{[^}]+\}\}[^"\']*["\']/i';

        if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $index => $match) {
                $offset = $match[1];
                $lineNumber = substr_count(substr($content, 0, $offset), "\n") + 1;
                $handler = $matches[1][$index][0];

                $errors[] = new BladeValidationError(
                    file: $filePath,
                    line: $lineNumber,
                    rule: $this->getName(),
                    message: "Blade expression found in '{$handler}' event handler. This may lead to XSS vulnerabilities. Consider using Alpine.js, Livewire, or data attributes instead.",
                    severity: $this->getDefaultSeverity(),
                );
            }
        }

        return $errors;
    }

    /**
     * Check for javascript: protocol with Blade expressions.
     *
     * @return array<BladeValidationError>
     */
    private function checkJavaScriptProtocol(string $content, string $filePath): array
    {
        $errors = [];

        // Pattern: href="javascript:..." with Blade
        $pattern = '/href\s*=\s*["\']javascript:[^"\']*\{\{[^}]+\}\}[^"\']*["\']/i';

        if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $match) {
                $offset = $match[1];
                $lineNumber = substr_count(substr($content, 0, $offset), "\n") + 1;

                $errors[] = new BladeValidationError(
                    file: $filePath,
                    line: $lineNumber,
                    rule: $this->getName(),
                    message: "Blade expression found in 'javascript:' URL. This is a security risk. Use proper event handling instead.",
                    severity: 'error',
                );
            }
        }

        return $errors;
    }

    /**
     * Check for script tags containing Blade expressions.
     *
     * @return array<BladeValidationError>
     */
    private function checkScriptTags(string $content, string $filePath): array
    {
        $errors = [];

        // Pattern: <script>...{{ ... }}...</script>
        // Be careful not to flag @vite or asset() calls which are legitimate
        $pattern = '/<script[^>]*>(.*?)<\/script>/si';

        if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[1] as $match) {
                $scriptContent = $match[0];
                $offset = $match[1];
                $lineNumber = substr_count(substr($content, 0, $offset), "\n") + 1;

                // Check for Blade expressions in script content
                if (preg_match('/\{\{(?!\s*--)[^}]+\}\}/', $scriptContent) || preg_match('/\{!![^}]+!!\}/', $scriptContent)) {
                    // Check if it's a safe pattern (like JSON encoding or config)
                    if ($this->isUnsafeScriptContent($scriptContent)) {
                        $errors[] = new BladeValidationError(
                            file: $filePath,
                            line: $lineNumber,
                            rule: $this->getName(),
                            message: 'Blade expression found inside <script> tag. Use @json() directive or ensure proper escaping to prevent XSS.',
                            severity: $this->getDefaultSeverity(),
                        );
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * Check for eval-like patterns.
     *
     * @return array<BladeValidationError>
     */
    private function checkEvalPatterns(string $content, string $filePath): array
    {
        $errors = [];

        // Pattern: eval({{ ... }}) or new Function({{ ... }})
        $patterns = [
            '/eval\s*\(\s*["\']?\{\{[^}]+\}\}["\']?\s*\)/i' => 'eval()',
            '/new\s+Function\s*\([^)]*\{\{[^}]+\}\}[^)]*\)/i' => 'new Function()',
            '/setTimeout\s*\(\s*["\']?\{\{[^}]+\}\}["\']?\s*,/i' => 'setTimeout() with string',
            '/setInterval\s*\(\s*["\']?\{\{[^}]+\}\}["\']?\s*,/i' => 'setInterval() with string',
        ];

        foreach ($patterns as $pattern => $name) {
            if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $offset = $match[1];
                    $lineNumber = substr_count(substr($content, 0, $offset), "\n") + 1;

                    $errors[] = new BladeValidationError(
                        file: $filePath,
                        line: $lineNumber,
                        rule: $this->getName(),
                        message: "Blade expression used in {$name}. This is a critical security vulnerability.",
                        severity: 'error',
                    );
                }
            }
        }

        return $errors;
    }

    /**
     * Determine if script content is potentially unsafe.
     */
    private function isUnsafeScriptContent(string $scriptContent): bool
    {
        // Safe patterns: JSON encoding, Vite, config values
        $safePatterns = [
            '/@json\s*\(/',           // @json directive
            '/JSON\.parse\s*\(\s*\'/',  // JSON.parse with escaped content
            '/\{\{\s*Vite::/',        // Vite helper
            '/\{\{\s*asset\s*\(/',    // Asset helper
            '/\{\{\s*config\s*\(/',   // Config values (usually safe)
            '/\{\{\s*route\s*\(/',    // Route helper
            '/\{\{\s*url\s*\(/',      // URL helper
            '/\{\{\s*csrf_token\s*\(/',  // CSRF token
            '/data-[a-z-]+\s*=/',     // Data attributes approach
        ];

        foreach ($safePatterns as $pattern) {
            if (preg_match($pattern, $scriptContent)) {
                return false;
            }
        }

        return true;
    }
}
