<?php

declare(strict_types=1);

namespace Deinte\BladeValidator;

use Deinte\BladeValidator\Data\BladeValidationError;
use Deinte\BladeValidator\Data\BladeValidationResult;
use Deinte\BladeValidator\Rules\BladeRuleInterface;
use Deinte\BladeValidator\Rules\DeprecatedSyntax;
use Deinte\BladeValidator\Rules\DirectiveInComponentAttribute;
use Deinte\BladeValidator\Rules\InlineJavaScript;
use Deinte\BladeValidator\Rules\LegacyPhpTags;
use Deinte\BladeValidator\Rules\RawOutputUsage;
use Deinte\BladeValidator\Rules\SensitiveDataExposure;
use Deinte\BladeValidator\Rules\UnclosedDirective;
use Deinte\BladeValidator\Rules\UseStatementInPhpBlock;
use Generator;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Compilers\BladeCompiler;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;

/**
 * Core Blade template validator.
 *
 * Validates Blade templates using Laravel's BladeCompiler for syntax errors
 * and custom rules for pattern validation and security checks.
 */
class BladeValidator
{
    /**
     * @var array<string, BladeRuleInterface>
     */
    private array $rules = [];

    private BladeCompiler $compiler;

    private Filesystem $files;

    public function __construct(?BladeCompiler $compiler = null)
    {
        $this->files = new Filesystem;
        $this->compiler = $compiler ?? $this->resolveCompiler();

        $this->registerDefaultRules();
    }

    /**
     * Resolve the Blade compiler from the application.
     */
    private function resolveCompiler(): BladeCompiler
    {
        // Try to get from facade - Blade facade resolves to BladeCompiler directly
        try {
            $blade = Blade::getFacadeRoot();
            if ($blade instanceof BladeCompiler) {
                return $blade;
            }
        } catch (Throwable) {
            // Fall through to creating a new instance
        }

        // Fall back to creating a new instance
        return new BladeCompiler(
            $this->files,
            sys_get_temp_dir()
        );
    }

    /**
     * Register the default validation rules.
     */
    private function registerDefaultRules(): void
    {
        $this->addRule(new DirectiveInComponentAttribute);
        $this->addRule(new UseStatementInPhpBlock);
        $this->addRule(new RawOutputUsage);
        $this->addRule(new UnclosedDirective);
        $this->addRule(new InlineJavaScript);
        $this->addRule(new SensitiveDataExposure);
        $this->addRule(new DeprecatedSyntax);
        $this->addRule(new LegacyPhpTags);
    }

    /**
     * Add a custom validation rule.
     */
    public function addRule(BladeRuleInterface $rule): self
    {
        $this->rules[$rule->getName()] = $rule;

        return $this;
    }

    /**
     * Remove a validation rule by name.
     */
    public function removeRule(string $ruleName): self
    {
        unset($this->rules[$ruleName]);

        return $this;
    }

    /**
     * Get all registered rules.
     *
     * @return array<string, BladeRuleInterface>
     */
    public function getRules(): array
    {
        return $this->rules;
    }

    /**
     * Validate a single Blade file.
     */
    public function validateFile(string $filePath): BladeValidationResult
    {
        if (! $this->files->exists($filePath)) {
            return new BladeValidationResult(
                valid: false,
                errors: [
                    new BladeValidationError(
                        file: $filePath,
                        line: 0,
                        rule: 'file-exists',
                        message: 'File does not exist',
                    ),
                ],
                filesChecked: 1,
            );
        }

        $content = $this->files->get($filePath);
        $errors = [];

        // 1. Check for Blade compile errors using Laravel's compiler
        $compileErrors = $this->checkCompileErrors($content, $filePath);
        $errors = array_merge($errors, $compileErrors);

        // 2. Run custom rules
        foreach ($this->rules as $rule) {
            $ruleErrors = $rule->validate($content, $filePath);
            $errors = array_merge($errors, $ruleErrors);
        }

        return new BladeValidationResult(
            valid: empty($errors),
            errors: $errors,
            filesChecked: 1,
        );
    }

    /**
     * Check for compile errors using Laravel's BladeCompiler.
     *
     * @return array<BladeValidationError>
     */
    private function checkCompileErrors(string $content, string $filePath): array
    {
        try {
            // Attempt to compile the Blade template
            $this->compiler->compileString($content);

            return [];
        } catch (Throwable $e) {
            $line = 1;

            // Try to extract line number from error message
            if (preg_match('/line\s+(\d+)/i', $e->getMessage(), $matches)) {
                $line = (int) $matches[1];
            }

            return [
                new BladeValidationError(
                    file: $filePath,
                    line: $line,
                    rule: 'blade-compile',
                    message: 'Blade compile error: '.$e->getMessage(),
                ),
            ];
        }
    }

    /**
     * Validate multiple files.
     *
     * @param  array<string>  $filePaths
     */
    public function validateFiles(array $filePaths): BladeValidationResult
    {
        $result = new BladeValidationResult(valid: true, errors: [], filesChecked: 0);

        foreach ($filePaths as $filePath) {
            $fileResult = $this->validateFile($filePath);
            $result = $result->merge($fileResult);
        }

        return $result;
    }

    /**
     * Validate files from a generator (memory efficient for large file sets).
     *
     * @param  Generator<string>  $files
     */
    public function validateFilesFromGenerator(Generator $files): BladeValidationResult
    {
        $result = new BladeValidationResult(valid: true, errors: [], filesChecked: 0);

        foreach ($files as $filePath) {
            $fileResult = $this->validateFile($filePath);
            $result = $result->merge($fileResult);
        }

        return $result;
    }

    /**
     * Validate all Blade files in a directory.
     */
    public function validateDirectory(string $directory): BladeValidationResult
    {
        if (! is_dir($directory)) {
            return new BladeValidationResult(
                valid: false,
                errors: [
                    new BladeValidationError(
                        file: $directory,
                        line: 0,
                        rule: 'directory-exists',
                        message: 'Directory does not exist',
                    ),
                ],
                filesChecked: 0,
            );
        }

        return $this->validateFilesFromGenerator($this->findBladeFiles($directory));
    }

    /**
     * Find all Blade files in a directory recursively.
     *
     * Uses a generator for memory efficiency with large directories.
     *
     * @return Generator<string>
     */
    public function findBladeFiles(string $directory): Generator
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
                yield $file->getPathname();
            }
        }
    }
}
