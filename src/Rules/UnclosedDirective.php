<?php

declare(strict_types=1);

namespace Deinte\BladeValidator\Rules;

use Deinte\BladeValidator\Data\BladeValidationError;

/**
 * Detects unclosed Blade directives.
 *
 * Checks for matching pairs of:
 * - @if/@elseif/@else/@endif
 * - @foreach/@endforeach
 * - @forelse/@empty/@endforelse
 * - @for/@endfor
 * - @while/@endwhile
 * - @switch/@case/@default/@endswitch
 * - @php/@endphp
 * - @section/@endsection (or @show/@stop)
 * - @push/@endpush
 * - @prepend/@endprepend
 * - @once/@endonce
 * - @verbatim/@endverbatim
 * - @auth/@endauth
 * - @guest/@endguest
 * - @can/@endcan
 * - @cannot/@endcannot
 * - @canany/@endcanany
 */
class UnclosedDirective implements BladeRuleInterface
{
    /**
     * Directive pairs to check.
     * Key is the opening directive, value is array of valid closing directives.
     *
     * @var array<string, array<string>>
     */
    private array $directivePairs = [
        '@if' => ['@endif'],
        '@unless' => ['@endunless'],
        '@isset' => ['@endisset'],
        '@empty' => ['@endempty'],
        '@foreach' => ['@endforeach'],
        '@forelse' => ['@endforelse'],
        '@for' => ['@endfor'],
        '@while' => ['@endwhile'],
        '@switch' => ['@endswitch'],
        '@php' => ['@endphp'],
        '@section' => ['@endsection', '@show', '@stop'],
        '@push' => ['@endpush'],
        '@pushOnce' => ['@endPushOnce'],
        '@prepend' => ['@endprepend'],
        '@prependOnce' => ['@endPrependOnce'],
        '@once' => ['@endonce'],
        '@verbatim' => ['@endverbatim'],
        '@auth' => ['@endauth'],
        '@guest' => ['@endguest'],
        '@can' => ['@endcan'],
        '@cannot' => ['@endcannot'],
        '@canany' => ['@endcanany'],
        '@env' => ['@endenv'],
        '@production' => ['@endproduction'],
        '@component' => ['@endcomponent'],
        '@slot' => ['@endslot'],
        '@error' => ['@enderror'],
        '@fragment' => ['@endfragment'],
    ];

    public function getName(): string
    {
        return 'unclosed-directive';
    }

    public function getDescription(): string
    {
        return 'Detects unclosed Blade directives that are missing their closing counterpart.';
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

        // Remove Blade comments to avoid false positives
        $contentWithoutComments = preg_replace('/\{\{--.*?--\}\}/s', '', $content) ?? $content;

        // Remove content inside @verbatim blocks
        $contentWithoutVerbatim = preg_replace('/@verbatim.*?@endverbatim/s', '', $contentWithoutComments) ?? $contentWithoutComments;

        // Track directive occurrences with their positions
        $directiveStack = [];

        // Find all directives with their positions
        $pattern = '/@(\w+)(?:\s*\([^)]*\))?/';

        if (preg_match_all($pattern, $contentWithoutVerbatim, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $index => $match) {
                $fullDirective = $match[0];
                $offset = $match[1];
                $directiveName = '@'.$matches[1][$index][0];
                $lineNumber = substr_count(substr($content, 0, $offset), "\n") + 1;

                // Check if this is an opening directive
                if (isset($this->directivePairs[$directiveName])) {
                    $directiveStack[] = [
                        'directive' => $directiveName,
                        'line' => $lineNumber,
                        'closers' => $this->directivePairs[$directiveName],
                    ];
                }

                // Check if this is a closing directive
                foreach ($directiveStack as $key => $stackItem) {
                    if (in_array($directiveName, $stackItem['closers'], true)) {
                        // Found a matching closer, remove from stack (LIFO for proper nesting)
                        // Find the last matching opener
                        for ($i = count($directiveStack) - 1; $i >= 0; $i--) {
                            if (in_array($directiveName, $directiveStack[$i]['closers'], true)) {
                                unset($directiveStack[$i]);
                                $directiveStack = array_values($directiveStack);
                                break;
                            }
                        }
                        break;
                    }
                }
            }
        }

        // Any remaining items in the stack are unclosed
        foreach ($directiveStack as $unclosed) {
            $errors[] = new BladeValidationError(
                file: $filePath,
                line: $unclosed['line'],
                rule: $this->getName(),
                message: "Unclosed directive '{$unclosed['directive']}' - expected ".implode(' or ', $unclosed['closers']).'.',
                severity: $this->getDefaultSeverity(),
            );
        }

        return $errors;
    }
}
