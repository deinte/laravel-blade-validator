<?php

declare(strict_types=1);

namespace Deinte\BladeValidator\Rules;

use Deinte\BladeValidator\Data\BladeValidationError;

/**
 * Detects legacy PHP tags in Blade templates.
 *
 * Blade templates should use Blade syntax instead of raw PHP tags:
 * - <?= $var ? > should be {{ $var }}
 * - <?php ... ? > should be @php ... @endphp
 *
 * This is especially common in legacy codebases being migrated to Blade.
 */
class LegacyPhpTags implements BladeRuleInterface
{
    public function getName(): string
    {
        return 'legacy-php-tags';
    }

    public function getDescription(): string
    {
        return 'Detects legacy PHP tags (<?php, <?=) that should be converted to Blade syntax.';
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

        // Check for short echo tags: <?= ... ? >
        $errors = array_merge($errors, $this->checkShortEchoTags($content, $filePath));

        // Check for full PHP tags: <?php ... ? >
        $errors = array_merge($errors, $this->checkPhpTags($content, $filePath));

        return $errors;
    }

    /**
     * Check for short echo tags <?= ... ? >
     *
     * @return array<BladeValidationError>
     */
    private function checkShortEchoTags(string $content, string $filePath): array
    {
        $errors = [];

        // Pattern: <?= ... ? >
        $pattern = '/<\?=\s*(.*?)\s*\?>/s';

        if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $index => $match) {
                $fullMatch = $match[0];
                $offset = $match[1];
                $expression = trim($matches[1][$index][0]);

                $lineNumber = substr_count(substr($content, 0, $offset), "\n") + 1;

                // Suggest the Blade equivalent
                $suggestion = $this->suggestBladeEquivalent($expression);

                $errors[] = new BladeValidationError(
                    file: $filePath,
                    line: $lineNumber,
                    rule: $this->getName(),
                    message: "Legacy PHP short echo tag '<?= ?>' found. Convert to Blade syntax: {$suggestion}",
                    severity: $this->getDefaultSeverity(),
                );
            }
        }

        return $errors;
    }

    /**
     * Check for full PHP tags <?php ... ? >
     *
     * @return array<BladeValidationError>
     */
    private function checkPhpTags(string $content, string $filePath): array
    {
        $errors = [];

        // Pattern: <?php ... ? > (not at the very start of file which might be a config file)
        // We look for <?php that's not at position 0
        $pattern = '/<\?php\s*(.*?)\s*\?>/s';

        if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $index => $match) {
                $offset = $match[1];
                $phpContent = trim($matches[1][$index][0]);

                // Skip if this is at the very beginning (might be intentional)
                if ($offset === 0) {
                    continue;
                }

                $lineNumber = substr_count(substr($content, 0, $offset), "\n") + 1;

                // Determine if it's a simple echo or more complex code
                $isSimpleEcho = $this->isSimpleEcho($phpContent);

                if ($isSimpleEcho) {
                    $expression = $this->extractEchoExpression($phpContent);
                    $suggestion = $this->suggestBladeEquivalent($expression);
                    $message = "Legacy PHP tag with echo found. Convert to Blade syntax: {$suggestion}";
                } else {
                    $message = "Legacy PHP tag '<?php ?>' found. Convert to @php ... @endphp or refactor to use Blade directives.";
                }

                $errors[] = new BladeValidationError(
                    file: $filePath,
                    line: $lineNumber,
                    rule: $this->getName(),
                    message: $message,
                    severity: $this->getDefaultSeverity(),
                );
            }
        }

        return $errors;
    }

    /**
     * Check if PHP content is a simple echo statement.
     */
    private function isSimpleEcho(string $phpContent): bool
    {
        // Check for: echo $var; or echo "string"; or echo $obj->method();
        return (bool) preg_match('/^\s*echo\s+.+;\s*$/s', $phpContent);
    }

    /**
     * Extract the expression from an echo statement.
     */
    private function extractEchoExpression(string $phpContent): string
    {
        if (preg_match('/^\s*echo\s+(.+?);\s*$/s', $phpContent, $matches)) {
            return trim($matches[1]);
        }

        return $phpContent;
    }

    /**
     * Suggest the Blade equivalent for an expression.
     */
    private function suggestBladeEquivalent(string $expression): string
    {
        // Check if expression contains HTML or might need raw output
        $needsRaw = $this->mightNeedRawOutput($expression);

        if ($needsRaw) {
            return "{!! {$expression} !!} (if HTML is intentional) or {{ {$expression} }} (for escaped output)";
        }

        return "{{ {$expression} }}";
    }

    /**
     * Check if expression might need raw output.
     */
    private function mightNeedRawOutput(string $expression): bool
    {
        $htmlPatterns = [
            '/->render\s*\(/',
            '/->toHtml\s*\(/',
            '/nl2br\s*\(/',
            '/htmlentities\s*\(/',
            '/htmlspecialchars\s*\(/',
            '/<[a-z]/i',
        ];

        foreach ($htmlPatterns as $pattern) {
            if (preg_match($pattern, $expression)) {
                return true;
            }
        }

        return false;
    }
}
