<?php

declare(strict_types=1);

namespace Deinte\BladeValidator\Fixers;

/**
 * Fixes legacy PHP tags to Blade syntax.
 */
final class LegacyPhpTagsFixer implements BladeFixerInterface
{
    public function fix(string $content, string $filePath): BladeFixResult
    {
        $changes = [];
        $original = $content;

        // Fix short echo tags
        $content = preg_replace_callback(
            '/<\?=\s*(.*?)\s*\?>/s',
            function ($matches) use (&$changes) {
                $expression = trim($matches[1]);
                $changes[] = 'Converted short tag to {{ '.$expression.' }}';

                return '{{ '.$expression.' }}';
            },
            $content
        );

        // Fix php echo statements
        $content = preg_replace_callback(
            '/<\?php\s+echo\s+(.+?);\s*\?>/s',
            function ($matches) use (&$changes) {
                $expression = trim($matches[1]);
                $changes[] = 'Converted php echo to {{ '.$expression.' }}';

                return '{{ '.$expression.' }}';
            },
            $content
        );

        // Fix remaining php blocks to @php @endphp
        $content = preg_replace_callback(
            '/(?<!^)<\?php\s*(.*?)\s*\?>/s',
            function ($matches) use (&$changes) {
                $code = trim($matches[1]);
                if (empty($code)) {
                    return '';
                }
                $changes[] = 'Converted php block to @php @endphp';

                return "@php\n    ".$code."\n@endphp";
            },
            $content
        );

        if ($content === $original) {
            return BladeFixResult::unchanged($original);
        }

        return BladeFixResult::fixed($content, $changes);
    }
}
