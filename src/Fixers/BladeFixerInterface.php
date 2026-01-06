<?php

declare(strict_types=1);

namespace Deinte\BladeValidator\Fixers;

/**
 * Interface for rules that can automatically fix issues.
 */
interface BladeFixerInterface
{
    /**
     * Fix all issues in the content that this fixer can handle.
     *
     * @return BladeFixResult
     */
    public function fix(string $content, string $filePath): BladeFixResult;
}
