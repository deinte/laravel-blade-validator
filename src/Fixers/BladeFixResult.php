<?php

declare(strict_types=1);

namespace Deinte\BladeValidator\Fixers;

/**
 * Result of applying fixes to a Blade file.
 */
final class BladeFixResult
{
    /**
     * @param  array<string>  $changes  Human-readable descriptions of changes made
     */
    public function __construct(
        public readonly string $content,
        public readonly bool $modified,
        public readonly array $changes = [],
    ) {}

    public static function unchanged(string $content): self
    {
        return new self($content, false, []);
    }

    public static function fixed(string $content, array $changes): self
    {
        return new self($content, true, $changes);
    }
}
