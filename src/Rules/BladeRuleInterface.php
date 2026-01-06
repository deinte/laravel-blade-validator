<?php

declare(strict_types=1);

namespace Deinte\BladeValidator\Rules;

use Deinte\BladeValidator\Data\BladeValidationError;

/**
 * Interface for Blade validation rules.
 *
 * Implement this interface to create custom validation rules that can be
 * registered with the BladeValidator.
 */
interface BladeRuleInterface
{
    /**
     * Get the unique identifier for this rule.
     *
     * This identifier is used to reference the rule in configuration
     * and error reporting.
     */
    public function getName(): string;

    /**
     * Get a human-readable description of what this rule checks.
     */
    public function getDescription(): string;

    /**
     * Get the default severity for violations of this rule.
     *
     * @return string 'error', 'warning', or 'info'
     */
    public function getDefaultSeverity(): string;

    /**
     * Validate the given Blade content.
     *
     * @param  string  $content  The raw content of the Blade file
     * @param  string  $filePath  The path to the file being validated
     * @return array<BladeValidationError>
     */
    public function validate(string $content, string $filePath): array;
}
