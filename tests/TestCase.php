<?php

declare(strict_types=1);

namespace Deinte\BladeValidator\Tests;

use Deinte\BladeValidator\BladeValidatorServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app): array
    {
        return [
            BladeValidatorServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Set up any environment configuration
        $app['config']->set('blade-validator.paths', [
            $this->getFixturesPath(),
        ]);
    }

    protected function getFixturesPath(): string
    {
        return __DIR__.'/Fixtures/Blade';
    }

    protected function getFixturePath(string $filename): string
    {
        return $this->getFixturesPath().'/'.$filename;
    }
}
