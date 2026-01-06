<?php

declare(strict_types=1);

namespace Deinte\BladeValidator\GrumPHP;

use GrumPHP\Runner\TaskResult;
use GrumPHP\Runner\TaskResultInterface;
use GrumPHP\Task\AbstractExternalTask;
use GrumPHP\Task\Context\ContextInterface;
use GrumPHP\Task\Context\GitPreCommitContext;
use GrumPHP\Task\Context\RunContext;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Process\Process;

/**
 * GrumPHP task for validating Blade templates.
 *
 * Configuration in grumphp.yml:
 *
 * grumphp:
 *   tasks:
 *     blade_validation:
 *       triggered_by: ['blade.php']
 *       severity: error
 *       paths: []
 *       ignore: []
 *       rules: []
 *       exclude_rules: []
 */
class BladeValidationTask extends AbstractExternalTask
{
    public static function getConfigurableOptions(): OptionsResolver
    {
        $resolver = new OptionsResolver;

        $resolver->setDefaults([
            'triggered_by' => ['blade.php'],
            'severity' => 'error',
            'paths' => [],
            'ignore' => [],
            'rules' => [],
            'exclude_rules' => [],
            'no_fail' => false,
        ]);

        $resolver->addAllowedTypes('triggered_by', ['array']);
        $resolver->addAllowedTypes('severity', ['string']);
        $resolver->addAllowedTypes('paths', ['array']);
        $resolver->addAllowedTypes('ignore', ['array']);
        $resolver->addAllowedTypes('rules', ['array']);
        $resolver->addAllowedTypes('exclude_rules', ['array']);
        $resolver->addAllowedTypes('no_fail', ['bool']);

        $resolver->setAllowedValues('severity', ['error', 'warning', 'info']);

        return $resolver;
    }

    public function canRunInContext(ContextInterface $context): bool
    {
        return $context instanceof GitPreCommitContext || $context instanceof RunContext;
    }

    public function run(ContextInterface $context): TaskResultInterface
    {
        $config = $this->getConfig()->getOptions();

        // Get files to validate
        $files = $context->getFiles()->extensions($config['triggered_by']);

        // If no Blade files changed, skip
        if ($files->isEmpty()) {
            return TaskResult::createSkipped($this, $context);
        }

        // Build the artisan command
        $arguments = $this->buildArguments($config, $files);

        // Run the command
        $process = new Process($arguments);
        $process->setWorkingDirectory($this->getWorkingDirectory());
        $process->run();

        if (! $process->isSuccessful()) {
            return TaskResult::createFailed(
                $this,
                $context,
                $this->formatter->format($process)
            );
        }

        return TaskResult::createPassed($this, $context);
    }

    /**
     * Build command arguments.
     *
     * @param  array<string, mixed>  $config
     * @param  \GrumPHP\Collection\FilesCollection  $files
     * @return array<string>
     */
    private function buildArguments(array $config, $files): array
    {
        $arguments = [
            'php',
            'artisan',
            'blade:validate',
        ];

        // Add file paths
        foreach ($files as $file) {
            $arguments[] = $file->getPathname();
        }

        // Add severity option
        if (! empty($config['severity'])) {
            $arguments[] = '--severity='.$config['severity'];
        }

        // Add ignore patterns
        foreach ($config['ignore'] as $pattern) {
            $arguments[] = '--ignore='.$pattern;
        }

        // Add rules
        if (! empty($config['rules'])) {
            $arguments[] = '--rules='.implode(',', $config['rules']);
        }

        // Add exclude rules
        if (! empty($config['exclude_rules'])) {
            $arguments[] = '--exclude-rules='.implode(',', $config['exclude_rules']);
        }

        // Add no-fail if configured
        if ($config['no_fail']) {
            $arguments[] = '--no-fail';
        }

        return $arguments;
    }

    /**
     * Get the working directory.
     */
    private function getWorkingDirectory(): string
    {
        return getcwd() ?: '.';
    }
}
