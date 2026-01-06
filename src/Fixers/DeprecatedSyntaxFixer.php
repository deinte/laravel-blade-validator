<?php

declare(strict_types=1);

namespace Deinte\BladeValidator\Fixers;

/**
 * Fixes deprecated Blade syntax.
 *
 * {{{ $var }}}     → {{ $var }}
 * {{ e($var) }}    → {{ $var }}
 * @else if         → @elseif
 */
final class DeprecatedSyntaxFixer implements BladeFixerInterface
{
    public function fix(string $content, string $filePath): BladeFixResult
    {
        $changes = [];
        $original = $content;

        // Fix triple braces {{{ }}} to double braces {{ }}
        $content = preg_replace_callback(
            '/\{\{\{\s*(.*?)\s*\}\}\}/s',
            function ($matches) use (&$changes) {
                $changes[] = 'Converted {{{ }}} to {{ }}';

                return '{{ '.trim($matches[1]).' }}';
            },
            $content
        );

        // Fix double escaping {{ e($var) }} to {{ $var }}
        $content = preg_replace_callback(
            '/\{\{\s*e\s*\(\s*(.+?)\s*\)\s*\}\}/',
            function ($matches) use (&$changes) {
                $changes[] = 'Removed redundant e() wrapper';

                return '{{ '.trim($matches[1]).' }}';
            },
            $content
        );

        // Fix @else if to @elseif
        $content = preg_replace_callback(
            '/@else\s+if\b/',
            function () use (&$changes) {
                $changes[] = 'Converted @else if to @elseif';

                return '@elseif';
            },
            $content
        );

        if ($content === $original) {
            return BladeFixResult::unchanged($original);
        }

        return BladeFixResult::fixed($content, $changes);
    }
}
