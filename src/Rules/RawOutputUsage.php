<?php

declare(strict_types=1);

namespace Deinte\BladeValidator\Rules;

use Deinte\BladeValidator\Data\BladeValidationError;

/**
 * Detects potentially unsafe raw output usage {!! !!}.
 *
 * Raw output bypasses Blade's automatic escaping and can lead to XSS vulnerabilities
 * if user input is displayed without proper sanitization.
 *
 * Flagged:
 * - {!! $userInput !!}
 * - {!! request()->input() !!}
 *
 * Can be suppressed with: {{-- @security-ignore raw-output --}}
 */
class RawOutputUsage implements BladeRuleInterface
{
    public function getName(): string
    {
        return 'raw-output-usage';
    }

    public function getDescription(): string
    {
        return 'Detects raw output {!! !!} usage that bypasses escaping and may cause XSS vulnerabilities.';
    }

    public function getDefaultSeverity(): string
    {
        $severity = config('blade-validator.raw_output.severity');

        return is_string($severity) ? $severity : 'warning';
    }

    /**
     * @return array<BladeValidationError>
     */
    public function validate(string $content, string $filePath): array
    {
        $errors = [];

        // Get allowed patterns from config
        $allowedPatterns = config('blade-validator.raw_output.allowed_patterns', []);

        // Pattern to find {!! ... !!}
        $pattern = '/\{!!\s*(.*?)\s*!!\}/s';

        if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $index => $match) {
                $fullMatch = $match[0];
                $offset = $match[1];
                $expression = $matches[1][$index][0];

                // Calculate line number
                $lineNumber = substr_count(substr($content, 0, $offset), "\n") + 1;

                // Check if this line has a security-ignore comment
                if ($this->hasIgnoreComment($content, $offset)) {
                    continue;
                }

                // Check if expression matches any allowed patterns
                if ($this->matchesAllowedPattern($expression, $allowedPatterns)) {
                    continue;
                }

                // Determine risk level based on expression content
                $riskLevel = $this->assessRisk($expression);

                $errors[] = new BladeValidationError(
                    file: $filePath,
                    line: $lineNumber,
                    rule: $this->getName(),
                    message: "Raw output '{$fullMatch}' bypasses escaping. {$riskLevel} Ensure the content is properly sanitized or use {{ }} for automatic escaping.",
                    severity: $this->getDefaultSeverity(),
                );
            }
        }

        return $errors;
    }

    /**
     * Check if there's a security-ignore comment for this line.
     */
    private function hasIgnoreComment(string $content, int $offset): bool
    {
        // Get the line containing this offset
        $lineStart = strrpos(substr($content, 0, $offset), "\n");
        $lineStart = $lineStart === false ? 0 : $lineStart;
        $lineEnd = strpos($content, "\n", $offset);
        $lineEnd = $lineEnd === false ? strlen($content) : $lineEnd;

        $line = substr($content, $lineStart, $lineEnd - $lineStart);

        // Check for ignore comment on same line
        if (preg_match('/\{\{--.*@security-ignore.*raw-output.*--\}\}/', $line)) {
            return true;
        }

        // Check previous line for ignore comment
        if ($lineStart > 0) {
            $prevLineStart = strrpos(substr($content, 0, $lineStart - 1), "\n");
            $prevLineStart = $prevLineStart === false ? 0 : $prevLineStart;
            $prevLine = substr($content, $prevLineStart, $lineStart - $prevLineStart);

            if (preg_match('/\{\{--.*@security-ignore.*raw-output.*--\}\}/', $prevLine)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the expression matches an allowed pattern.
     *
     * @param  array<string>  $allowedPatterns
     */
    private function matchesAllowedPattern(string $expression, array $allowedPatterns): bool
    {
        foreach ($allowedPatterns as $pattern) {
            if (@preg_match($pattern, $expression)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Assess the risk level of the raw output expression.
     */
    private function assessRisk(string $expression): string
    {
        // High risk patterns - user input
        $highRiskPatterns = [
            '/request\s*\(/',
            '/\$_(?:GET|POST|REQUEST|COOKIE)/',
            '/->input\s*\(/',
            '/->get\s*\(/',
            '/\$request\b/',
        ];

        foreach ($highRiskPatterns as $pattern) {
            if (preg_match($pattern, $expression)) {
                return 'HIGH RISK: Expression appears to contain user input.';
            }
        }

        // Medium risk - database content
        $mediumRiskPatterns = [
            '/->content\b/',
            '/->body\b/',
            '/->html\b/',
            '/->description\b/',
        ];

        foreach ($mediumRiskPatterns as $pattern) {
            if (preg_match($pattern, $expression)) {
                return 'MEDIUM RISK: Expression may contain user-generated content from database.';
            }
        }

        return 'Review carefully to ensure content is safe.';
    }
}
