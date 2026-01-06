<?php

declare(strict_types=1);

namespace Deinte\BladeValidator\Rules;

use Deinte\BladeValidator\Data\BladeValidationError;

/**
 * Detects Blade directives used inside component tag attributes.
 *
 * Invalid: <x-button @if($active) color="primary" @endif>
 * Valid: <x-button @class(['active' => $isActive])>
 */
class DirectiveInComponentAttribute implements BladeRuleInterface
{
    /**
     * Directives that are NOT allowed inside component attributes.
     *
     * @var array<string>
     */
    private array $prohibitedDirectives = [
        '@if',
        '@unless',
        '@isset',
        '@empty',
        '@foreach',
        '@forelse',
        '@for',
        '@while',
        '@switch',
        '@php',
        '@auth',
        '@guest',
        '@can',
        '@cannot',
        '@canany',
        '@env',
        '@production',
    ];

    public function getName(): string
    {
        return 'directive-in-component-attribute';
    }

    public function getDescription(): string
    {
        return 'Detects Blade directives used inside component tag attributes, which is invalid syntax.';
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

        // Pattern to find component tags: <x-name or <x:name with attributes
        // This captures the tag name and all content until the closing > or />
        $pattern = '/<\s*(x[-:][\w\-:.]+)([^>]*?)(?:\/)?>/s';

        if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[2] as $index => $attributeMatch) {
                $attributes = $attributeMatch[0];
                $offset = $attributeMatch[1];

                // Calculate line number from offset
                $lineNumber = substr_count(substr($content, 0, $offset), "\n") + 1;

                foreach ($this->prohibitedDirectives as $directive) {
                    if ($this->containsProhibitedDirective($attributes, $directive)) {
                        $componentName = $matches[1][$index][0];
                        $errors[] = new BladeValidationError(
                            file: $filePath,
                            line: $lineNumber,
                            rule: $this->getName(),
                            message: "Blade directive '{$directive}' found inside <{$componentName}> attributes. Move the directive outside the component tag or use conditional attributes.",
                            severity: $this->getDefaultSeverity(),
                        );
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * Check if attributes contain a prohibited directive.
     * Allows @class() and @style() which are valid in attributes.
     */
    private function containsProhibitedDirective(string $attributes, string $directive): bool
    {
        // Remove allowed patterns: @class(...) and @style(...)
        // These are valid Blade attribute directives
        $cleaned = preg_replace('/@(class|style)\s*\([^)]*\)/s', '', $attributes);

        if ($cleaned === null) {
            $cleaned = $attributes;
        }

        return str_contains($cleaned, $directive);
    }
}
