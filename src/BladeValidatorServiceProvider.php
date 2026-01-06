<?php

declare(strict_types=1);

namespace Deinte\BladeValidator;

use Deinte\BladeValidator\Commands\ValidateBladeCommand;
use Illuminate\Support\ServiceProvider;

class BladeValidatorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/blade-validator.php',
            'blade-validator'
        );

        $this->app->singleton(BladeValidator::class, function ($app) {
            $validator = new BladeValidator;

            // Register rules based on configuration
            $rules = config('blade-validator.rules', []);

            foreach ($rules as $ruleName => $enabled) {
                if (! $enabled) {
                    $validator->removeRule($ruleName);
                }
            }

            return $validator;
        });

        $this->app->alias(BladeValidator::class, 'blade-validator');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/blade-validator.php' => config_path('blade-validator.php'),
            ], 'blade-validator-config');

            $this->commands([
                ValidateBladeCommand::class,
            ]);
        }
    }
}
