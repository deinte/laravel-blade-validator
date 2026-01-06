<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default Paths
    |--------------------------------------------------------------------------
    |
    | The default paths to validate when no paths are specified in the command.
    |
    */
    'paths' => [
        resource_path('views'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Ignore Patterns
    |--------------------------------------------------------------------------
    |
    | Glob patterns for files and directories to ignore during validation.
    | These patterns are matched against both the full path and the filename.
    |
    */
    'ignore' => [
        '**/vendor/**',
        '**/node_modules/**',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rules Configuration
    |--------------------------------------------------------------------------
    |
    | Enable or disable specific validation rules. Set to false to disable
    | a rule entirely, or true to enable it with default settings.
    |
    */
    'rules' => [
        'directive-in-component-attribute' => true,
        'use-statement-in-php-block' => true,
        'raw-output-usage' => true,
        'unclosed-directive' => true,
        'inline-javascript' => true,
        'sensitive-data-exposure' => true,
        'deprecated-syntax' => true,
        'legacy-php-tags' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Raw Output Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for the raw-output-usage rule that detects {!! !!} usage.
    |
    */
    'raw_output' => [
        // Severity level: 'error' or 'warning'
        'severity' => 'warning',

        // Patterns that are allowed to use raw output (regex patterns)
        'allowed_patterns' => [
            // Example: '/\$safeHtml/',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Sensitive Data Patterns
    |--------------------------------------------------------------------------
    |
    | Patterns that indicate sensitive data that should not be output directly.
    | These are matched case-insensitively against variable names and method calls.
    |
    */
    'sensitive_patterns' => [
        'password',
        'secret',
        'token',
        'key',
        'credential',
        'bearerToken',
        'api_key',
        'apiKey',
        'private',
        'ssn',
        'credit_card',
        'creditCard',
    ],

    /*
    |--------------------------------------------------------------------------
    | Inline JavaScript Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for the inline-javascript rule.
    |
    */
    'inline_javascript' => [
        // Severity level: 'error' or 'warning'
        'severity' => 'warning',

        // Event handlers to check for Blade variables
        'event_handlers' => [
            'onclick',
            'onchange',
            'onsubmit',
            'onload',
            'onerror',
            'onmouseover',
            'onmouseout',
            'onfocus',
            'onblur',
            'onkeydown',
            'onkeyup',
            'onkeypress',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Deprecated Syntax Patterns
    |--------------------------------------------------------------------------
    |
    | Blade syntax patterns that are deprecated and should be avoided.
    |
    */
    'deprecated_patterns' => [
        // Add deprecated patterns here as they are identified
    ],
];
