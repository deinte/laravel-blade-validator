<?php

declare(strict_types=1);

namespace Deinte\BladeValidator\Commands;

use Deinte\BladeValidator\BladeValidator;
use Deinte\BladeValidator\Data\BladeValidationResult;
use Deinte\BladeValidator\Fixers\BladeFixer;
use Generator;
use Illuminate\Console\Command;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class ValidateBladeCommand extends Command
{
    protected $signature = 'blade:validate
                            {path?* : Specific files or directories to validate}
                            {--fix : Automatically fix safe issues}
                            {--dry-run : Preview fixes without applying them}
                            {--ignore=* : Patterns to ignore (fnmatch format)}
                            {--format=text : Output format (text, json, github)}
                            {--no-fail : Exit with 0 even if errors found}
                            {--rules= : Comma-separated rules to run}
                            {--exclude-rules= : Comma-separated rules to skip}
                            {--severity=error : Minimum severity (error, warning, info)}';

    protected $description = 'Validate Blade templates for syntax errors, security issues, and best practices';

    public function handle(BladeValidator $validator): int
    {
        $paths = $this->argument('path');
        $format = $this->option('format');
        $shouldFix = $this->option('fix') || $this->option('dry-run');
        $dryRun = (bool) $this->option('dry-run');

        if (empty($paths)) {
            $paths = config('blade-validator.paths', [resource_path('views')]);
        }

        $ignorePatterns = $this->getIgnorePatterns();
        $files = iterator_to_array($this->collectFiles($paths, $ignorePatterns));

        // Fix mode
        if ($shouldFix) {
            return $this->handleFix($files, $dryRun);
        }

        // Validate mode
        return $this->handleValidate($validator, $files, $format);
    }

    private function handleFix(array $files, bool $dryRun): int
    {
        $fixer = new BladeFixer;
        $action = $dryRun ? 'Preview' : 'Fixing';

        $this->components->info("{$action} Blade templates...");
        $this->newLine();

        $totalModified = 0;
        $totalChanges = 0;

        foreach ($files as $filePath) {
            $result = $fixer->fixFile($filePath, $dryRun);

            if (! $result['modified']) {
                continue;
            }

            $totalModified++;
            $totalChanges += count($result['changes']);

            $relativePath = str_replace(base_path().'/', '', $filePath);
            $status = $dryRun ? 'would fix' : 'fixed';

            $this->components->twoColumnDetail(
                "<fg=green>{$relativePath}</>",
                count($result['changes'])." {$status}"
            );

            foreach ($result['changes'] as $change) {
                $this->line("  <fg=gray>-</> {$change}");
            }

            $this->newLine();
        }

        if ($totalModified === 0) {
            $this->components->info('Nothing to fix.');

            return self::SUCCESS;
        }

        $verb = $dryRun ? 'would be fixed' : 'fixed';
        $this->components->info("{$totalChanges} issue(s) {$verb} in {$totalModified} file(s).");

        return self::SUCCESS;
    }

    private function handleValidate(BladeValidator $validator, array $files, string $format): int
    {
        $noFail = (bool) $this->option('no-fail');
        $minSeverity = $this->option('severity');

        $this->configureRules($validator);

        if ($format !== 'json' && $format !== 'github') {
            $this->components->info('Validating Blade templates...');
        }

        $result = $validator->validateFiles($files);
        $filteredErrors = $result->filterBySeverity($minSeverity);
        $hasErrors = ! empty($filteredErrors);

        $this->outputResult($result, $format, $minSeverity);

        if (! $hasErrors) {
            if ($format !== 'json' && $format !== 'github') {
                $this->components->info("All {$result->filesChecked} Blade file(s) passed validation.");
            }

            return self::SUCCESS;
        }

        if ($format !== 'json' && $format !== 'github') {
            $errorCount = count($filteredErrors);
            $fileCount = count($result->errorsByFile());
            $this->components->error("Found {$errorCount} issue(s) in {$fileCount} file(s).");

            // Hint about fixable issues
            $fixableCount = $this->countFixableErrors($filteredErrors);
            if ($fixableCount > 0) {
                $this->newLine();
                $this->line("<fg=gray>  {$fixableCount} issue(s) can be auto-fixed. Run with --fix to apply.</>");
            }
        }

        return $noFail ? self::SUCCESS : self::FAILURE;
    }

    private function countFixableErrors(array $errors): int
    {
        $fixableRules = ['legacy-php-tags', 'deprecated-syntax', 'use-statement-in-php-block'];

        return count(array_filter(
            $errors,
            fn ($error) => in_array($error->rule, $fixableRules, true)
        ));
    }

    private function configureRules(BladeValidator $validator): void
    {
        $rulesOption = $this->option('rules');
        $excludeOption = $this->option('exclude-rules');

        if ($rulesOption !== null && $rulesOption !== '') {
            $enabledRules = array_map('trim', explode(',', $rulesOption));
            foreach (array_keys($validator->getRules()) as $rule) {
                if (! in_array($rule, $enabledRules, true)) {
                    $validator->removeRule($rule);
                }
            }
        }

        if ($excludeOption !== null && $excludeOption !== '') {
            foreach (array_map('trim', explode(',', $excludeOption)) as $rule) {
                $validator->removeRule($rule);
            }
        }
    }

    private function getIgnorePatterns(): array
    {
        return array_merge(
            config('blade-validator.ignore', []),
            $this->option('ignore')
        );
    }

    private function collectFiles(array $paths, array $ignorePatterns): Generator
    {
        foreach ($paths as $path) {
            $fullPath = $this->resolvePath($path);

            if ($fullPath === null) {
                continue;
            }

            if (is_dir($fullPath)) {
                yield from $this->getBladeFilesFromDirectory($fullPath, $ignorePatterns);
            } elseif (is_file($fullPath) && str_ends_with($fullPath, '.blade.php')) {
                if (! $this->shouldIgnore($fullPath, $ignorePatterns)) {
                    yield $fullPath;
                }
            }
        }
    }

    private function resolvePath(string $path): ?string
    {
        $basePath = base_path($path);
        if (file_exists($basePath)) {
            return $basePath;
        }

        if (file_exists($path)) {
            return $path;
        }

        return null;
    }

    private function getBladeFilesFromDirectory(string $directory, array $ignorePatterns): Generator
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $filePath = $file->getPathname();

            if (! $this->shouldIgnore($filePath, $ignorePatterns)) {
                yield $filePath;
            }
        }
    }

    private function shouldIgnore(string $filePath, array $ignorePatterns): bool
    {
        foreach ($ignorePatterns as $pattern) {
            $pattern = trim($pattern);
            if (fnmatch($pattern, $filePath) || fnmatch($pattern, basename($filePath))) {
                return true;
            }
        }

        return false;
    }

    private function outputResult(BladeValidationResult $result, string $format, string $minSeverity): void
    {
        match ($format) {
            'json' => $this->outputJson($result),
            'github' => $this->outputGitHub($result, $minSeverity),
            default => $this->outputText($result, $minSeverity),
        };
    }

    private function outputText(BladeValidationResult $result, string $minSeverity): void
    {
        $errors = $result->filterBySeverity($minSeverity);

        if (empty($errors)) {
            return;
        }

        $this->newLine();

        $byFile = [];
        foreach ($errors as $error) {
            $byFile[$error->file][] = $error;
        }

        foreach ($byFile as $file => $fileErrors) {
            $relativePath = str_replace(base_path().'/', '', $file);
            $this->components->twoColumnDetail(
                "<fg=red;options=bold>{$relativePath}</>",
                count($fileErrors).' issue(s)'
            );

            foreach ($fileErrors as $error) {
                $color = match ($error->severity) {
                    'error' => 'red',
                    'warning' => 'yellow',
                    default => 'gray',
                };

                $this->line(sprintf(
                    '  <fg=gray>Line %d:</> <fg=%s>[%s]</> [%s] %s',
                    $error->line,
                    $color,
                    strtoupper($error->severity),
                    $error->rule,
                    $error->message
                ));
            }

            $this->newLine();
        }
    }

    private function outputJson(BladeValidationResult $result): void
    {
        $this->line(json_encode($result->toArray(), JSON_PRETTY_PRINT));
    }

    private function outputGitHub(BladeValidationResult $result, string $minSeverity): void
    {
        foreach ($result->filterBySeverity($minSeverity) as $error) {
            $relativePath = str_replace(base_path().'/', '', $error->file);
            $level = match ($error->severity) {
                'error' => 'error',
                'warning' => 'warning',
                default => 'notice',
            };

            $this->line("::{$level} file={$relativePath},line={$error->line},title={$error->rule}::{$error->message}");
        }
    }
}
