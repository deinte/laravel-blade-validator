<?php

declare(strict_types=1);

namespace Deinte\BladeValidator\Data;

/**
 * Represents the result of validating one or more Blade files.
 */
final class BladeValidationResult
{
    /**
     * @param  array<BladeValidationError>  $errors
     */
    public function __construct(
        public readonly bool $valid,
        public readonly array $errors = [],
        public readonly int $filesChecked = 0,
    ) {}

    /**
     * Merge another result into this one.
     */
    public function merge(BladeValidationResult $other): self
    {
        return new self(
            valid: $this->valid && $other->valid,
            errors: array_merge($this->errors, $other->errors),
            filesChecked: $this->filesChecked + $other->filesChecked,
        );
    }

    /**
     * Get the total error count.
     */
    public function errorCount(): int
    {
        return count($this->errors);
    }

    /**
     * Get the count of errors by severity.
     */
    public function errorCountBySeverity(string $severity): int
    {
        return count(array_filter(
            $this->errors,
            fn (BladeValidationError $error) => $error->severity === $severity
        ));
    }

    /**
     * Group errors by file path.
     *
     * @return array<string, array<BladeValidationError>>
     */
    public function errorsByFile(): array
    {
        $grouped = [];

        foreach ($this->errors as $error) {
            $grouped[$error->file][] = $error;
        }

        return $grouped;
    }

    /**
     * Filter errors by minimum severity.
     *
     * @return array<BladeValidationError>
     */
    public function filterBySeverity(string $minSeverity): array
    {
        $severityOrder = ['info' => 0, 'warning' => 1, 'error' => 2];
        $minLevel = $severityOrder[$minSeverity] ?? 0;

        return array_filter(
            $this->errors,
            fn (BladeValidationError $error) => ($severityOrder[$error->severity] ?? 0) >= $minLevel
        );
    }

    /**
     * @return array{valid: bool, filesChecked: int, errorCount: int, errors: array<array{file: string, line: int, rule: string, message: string, severity: string}>}
     */
    public function toArray(): array
    {
        return [
            'valid' => $this->valid,
            'filesChecked' => $this->filesChecked,
            'errorCount' => $this->errorCount(),
            'errors' => array_map(fn (BladeValidationError $e) => $e->toArray(), $this->errors),
        ];
    }
}
