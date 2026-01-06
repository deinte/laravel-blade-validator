<?php

declare(strict_types=1);

namespace Deinte\BladeValidator\Fixers;

/**
 * Fixes use statements in @php blocks.
 *
 * Moves use statements to @use() directives at the top of the file.
 *
 * Before:
 * @php
 *     use App\Models\User;
 *     $users = User::all();
 * @endphp
 *
 * After:
 * @use('App\Models\User')
 *
 * @php
 *     $users = User::all();
 * @endphp
 */
final class UseStatementFixer implements BladeFixerInterface
{
    public function fix(string $content, string $filePath): BladeFixResult
    {
        $changes = [];
        $useDirectives = [];
        $original = $content;

        // Find and extract use statements from @php blocks
        $content = preg_replace_callback(
            '/@php\s*(.*?)@endphp/s',
            function ($matches) use (&$changes, &$useDirectives) {
                $phpContent = $matches[1];

                // Extract use statements
                $phpContent = preg_replace_callback(
                    '/(?:^|\s|;)\s*use\s+([A-Z][\w\\\\]+(?:\s*,\s*[A-Z][\w\\\\]+)*)\s*;/m',
                    function ($useMatches) use (&$changes, &$useDirectives) {
                        // Handle multiple use statements (use A, B, C;)
                        $classes = array_map('trim', explode(',', $useMatches[1]));
                        foreach ($classes as $class) {
                            $useDirectives[] = $class;
                            $changes[] = "Moved use {$class} to @use directive";
                        }

                        return '';
                    },
                    $phpContent
                );

                // Clean up the remaining content
                $phpContent = trim($phpContent);

                // If nothing left in the block, remove it entirely
                if (empty($phpContent)) {
                    return '';
                }

                return "@php\n    ".$phpContent."\n@endphp";
            },
            $content
        );

        // Add @use directives at the top of the file
        if (! empty($useDirectives)) {
            $useStatements = array_map(
                fn ($class) => "@use('{$class}')",
                array_unique($useDirectives)
            );

            // Find the best insertion point (after any existing @use or at the start)
            if (preg_match('/^(\s*(?:@use\([^)]+\)\s*)+)/m', $content, $matches)) {
                // Add after existing @use statements
                $existingUse = $matches[1];
                $newUse = implode("\n", $useStatements)."\n";
                $content = str_replace($existingUse, $existingUse.$newUse, $content);
            } else {
                // Add at the beginning, preserving any comments
                $useBlock = implode("\n", $useStatements)."\n\n";
                if (preg_match('/^(\s*\{\{--.*?--\}\}\s*)/s', $content, $matches)) {
                    // After opening comment
                    $content = $matches[1].$useBlock.substr($content, strlen($matches[1]));
                } else {
                    $content = $useBlock.$content;
                }
            }
        }

        // Clean up multiple blank lines
        $content = preg_replace('/\n{3,}/', "\n\n", $content);

        if ($content === $original) {
            return BladeFixResult::unchanged($original);
        }

        return BladeFixResult::fixed($content, $changes);
    }
}
