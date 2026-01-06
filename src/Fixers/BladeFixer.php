<?php

declare(strict_types=1);

namespace Deinte\BladeValidator\Fixers;

use Illuminate\Filesystem\Filesystem;

/**
 * Orchestrates all Blade fixers.
 */
final class BladeFixer
{
    /** @var array<BladeFixerInterface> */
    private array $fixers;

    private Filesystem $files;

    public function __construct()
    {
        $this->files = new Filesystem;
        $this->fixers = [
            new LegacyPhpTagsFixer,
            new DeprecatedSyntaxFixer,
            new UseStatementFixer,
        ];
    }

    /**
     * Fix a single file.
     *
     * @return array{modified: bool, changes: array<string>}
     */
    public function fixFile(string $filePath, bool $dryRun = false): array
    {
        if (! $this->files->exists($filePath)) {
            return ['modified' => false, 'changes' => []];
        }

        $content = $this->files->get($filePath);
        $allChanges = [];

        foreach ($this->fixers as $fixer) {
            $result = $fixer->fix($content, $filePath);
            if ($result->modified) {
                $content = $result->content;
                $allChanges = array_merge($allChanges, $result->changes);
            }
        }

        if (empty($allChanges)) {
            return ['modified' => false, 'changes' => []];
        }

        if (! $dryRun) {
            $this->files->put($filePath, $content);
        }

        return ['modified' => true, 'changes' => $allChanges];
    }

    /**
     * Fix multiple files.
     *
     * @param  array<string>  $filePaths
     * @return array{filesModified: int, totalChanges: int, results: array<string, array{modified: bool, changes: array<string>}>}
     */
    public function fixFiles(array $filePaths, bool $dryRun = false): array
    {
        $results = [];
        $filesModified = 0;
        $totalChanges = 0;

        foreach ($filePaths as $filePath) {
            $result = $this->fixFile($filePath, $dryRun);
            $results[$filePath] = $result;

            if ($result['modified']) {
                $filesModified++;
                $totalChanges += count($result['changes']);
            }
        }

        return [
            'filesModified' => $filesModified,
            'totalChanges' => $totalChanges,
            'results' => $results,
        ];
    }

    /**
     * Preview fixes without applying them.
     */
    public function preview(string $filePath): array
    {
        return $this->fixFile($filePath, dryRun: true);
    }
}
