<?php

declare(strict_types=1);

namespace Deinte\BladeValidator\GrumPHP;

use GrumPHP\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * GrumPHP extension loader for Blade validation.
 */
class ExtensionLoader implements ExtensionInterface
{
    public function load(ContainerBuilder $container): void
    {
        $container->register('task.blade_validation', BladeValidationTask::class)
            ->addArgument(new Reference('process_builder'))
            ->addArgument(new Reference('formatter.raw_process'))
            ->addTag('grumphp.task', ['task' => 'blade_validation']);
    }

    public function imports(): iterable
    {
        return [];
    }
}
