<?php

declare(strict_types=1);

namespace Deinte\BladeValidator\Data;

/**
 * Represents a single Blade validation error.
 */
final class BladeValidationError
{
    public function __construct(
        public readonly string $file,
        public readonly int $line,
        public readonly string $rule,
        public readonly string $message,
        public readonly string $severity = 'error',
    ) {}

    public function toString(): string
    {
        return sprintf(
            '%s:%d - [%s] %s',
            $this->file,
            $this->line,
            $this->rule,
            $this->message
        );
    }

    /**
     * @return array{file: string, line: int, rule: string, message: string, severity: string}
     */
    public function toArray(): array
    {
        return [
            'file' => $this->file,
            'line' => $this->line,
            'rule' => $this->rule,
            'message' => $this->message,
            'severity' => $this->severity,
        ];
    }
}
