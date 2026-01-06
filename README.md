# Laravel Blade Validator

Validate Blade templates for syntax errors, security issues, and best practices.

## Installation

You can install the package via composer:

```bash
composer require --dev deinte/laravel-blade-validator
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="blade-validator-config"
```

## Usage

### Basic Usage

Validate all Blade templates in `resources/views`:

```bash
php artisan blade:validate
```

Validate specific paths:

```bash
php artisan blade:validate resources/views/components resources/views/layouts
```

### Command Options

```bash
php artisan blade:validate [options] [path...]

Options:
  --fix                Automatically fix safe issues
  --dry-run            Preview fixes without applying them
  --ignore=*           Patterns to ignore (fnmatch format)
  --format=text        Output format: text, json, github
  --no-fail            Exit with 0 even if errors found
  --rules=             Comma-separated rules to run
  --exclude-rules=     Comma-separated rules to skip
  --severity=error     Minimum severity: error, warning, info
```

### Auto-Fix

The validator can automatically fix certain safe issues:

```bash
# Preview what would be fixed
php artisan blade:validate --dry-run

# Apply fixes
php artisan blade:validate --fix
```

**Fixable rules:**
- `legacy-php-tags`: Converts `<?= ?>` to `{{ }}`
- `deprecated-syntax`: Fixes `{{{ }}}`, `{{ e() }}`, `@else if`
- `use-statement-in-php-block`: Moves `use` to `@use()` directive

### Examples

```bash
# JSON output for CI/CD
php artisan blade:validate --format=json

# GitHub Actions annotations
php artisan blade:validate --format=github

# Ignore patterns
php artisan blade:validate --ignore="**/cache/**" --ignore="**/vendor/**"

# Run only specific rules
php artisan blade:validate --rules=raw-output-usage,sensitive-data-exposure

# Exclude specific rules
php artisan blade:validate --exclude-rules=deprecated-syntax

# Show warnings and errors
php artisan blade:validate --severity=warning
```

## Validation Rules

### directive-in-component-attribute (error)

Detects Blade directives used inside component tag attributes.

```blade
{{-- Invalid --}}
<x-button @if($active) color="primary" @endif />

{{-- Valid --}}
<x-button @class(['primary' => $active]) />
```

### use-statement-in-php-block (error)

Detects PHP `use` statements inside `@php` blocks.

```blade
{{-- Invalid --}}
@php
    use App\Models\User;
    $users = User::all();
@endphp

{{-- Valid --}}
@use('App\Models\User')

@php
    $users = User::all();
@endphp

{{-- Or use fully qualified class names --}}
@php
    $users = \App\Models\User::all();
@endphp
```

### raw-output-usage (warning)

Detects potentially unsafe raw output `{!! !!}` that bypasses escaping.

```blade
{{-- Flagged --}}
{!! $userContent !!}
{!! request()->input('html') !!}

{{-- Suppress with comment --}}
{{-- @security-ignore raw-output --}}
{!! $trustedHtml !!}
```

### unclosed-directive (error)

Detects unclosed block directives.

```blade
{{-- Invalid --}}
@if($show)
    <div>Content</div>
{{-- Missing @endif --}}

{{-- Valid --}}
@if($show)
    <div>Content</div>
@endif
```

### inline-javascript (warning)

Detects inline JavaScript patterns with Blade expressions.

```blade
{{-- Flagged --}}
<button onclick="{{ $action }}">Click</button>
<a href="javascript:{{ $code }}">Link</a>

{{-- Valid alternatives --}}
<button x-on:click="action">Click</button>
<button data-action="{{ $action }}">Click</button>
```

### sensitive-data-exposure (error)

Detects potential exposure of sensitive data.

```blade
{{-- Flagged --}}
{{ $user->password }}
{{ config('app.key') }}
{{ env('DB_PASSWORD') }}
{{ $request->bearerToken() }}

{{-- Safe --}}
{{ $user->name }}
{{ $user->email }}
```

### deprecated-syntax (warning)

Detects deprecated Blade syntax.

```blade
{{-- Flagged --}}
{{{ $variable }}}           {{-- Use {{ }} instead --}}
{{ e($variable) }}          {{-- Double escaping --}}
@else if($condition)        {{-- Use @elseif --}}
{{ str_limit($text, 100) }} {{-- Use Str::limit() --}}

{{-- Valid --}}
{{ $variable }}
@elseif($condition)
{{ Str::limit($text, 100) }}
```

### legacy-php-tags (error)

Detects legacy PHP tags (`<?=` and `<?php`) that should be converted to Blade syntax.

```blade
{{-- Flagged --}}
<td><?= $payment->name ?></td>
<td><?= $payment->iban ?></td>
<td><?= "€" . number_format($amount, 2) ?></td>
<?php echo $variable; ?>

{{-- Valid Blade equivalents --}}
<td>{{ $payment->name }}</td>
<td>{{ $payment->iban }}</td>
<td>€{{ number_format($amount, 2) }}</td>
{{ $variable }}
```

This is especially useful when migrating legacy PHP templates to Blade.

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag="blade-validator-config"
```

### Configuration Options

```php
return [
    // Default paths to validate
    'paths' => [
        resource_path('views'),
    ],

    // Patterns to ignore
    'ignore' => [
        '**/vendor/**',
        '**/node_modules/**',
    ],

    // Enable/disable rules
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

    // Raw output settings
    'raw_output' => [
        'severity' => 'warning',
        'allowed_patterns' => [],
    ],

    // Sensitive data patterns
    'sensitive_patterns' => [
        'password', 'secret', 'token', 'key', 'credential',
        'bearerToken', 'api_key', 'apiKey', 'private',
    ],
];
```

## GrumPHP Integration

### Setup

1. Install GrumPHP:

```bash
composer require --dev phpro/grumphp
```

2. Copy the configuration file:

```bash
cp vendor/deinte/laravel-blade-validator/grumphp.yml.dist grumphp.yml
```

3. Initialize git hooks:

```bash
vendor/bin/grumphp git:init
```

### Configuration

```yaml
# grumphp.yml
grumphp:
    extensions:
        - Deinte\BladeValidator\GrumPHP\ExtensionLoader

    tasks:
        blade_validation:
            triggered_by: ['blade.php']
            severity: error
            ignore:
                - '**/cache/**'
```

Now Blade validation will run automatically on every commit!

## Programmatic Usage

### Using the Facade

```php
use Deinte\BladeValidator\Facades\BladeValidator;

// Validate a single file
$result = BladeValidator::validateFile('/path/to/template.blade.php');

// Validate multiple files
$result = BladeValidator::validateFiles([
    '/path/to/template1.blade.php',
    '/path/to/template2.blade.php',
]);

// Validate a directory
$result = BladeValidator::validateDirectory(resource_path('views'));

// Check results
if (!$result->valid) {
    foreach ($result->errors as $error) {
        echo "{$error->file}:{$error->line} - [{$error->rule}] {$error->message}\n";
    }
}
```

### Using Dependency Injection

```php
use Deinte\BladeValidator\BladeValidator;

class MyController
{
    public function validateViews(BladeValidator $validator)
    {
        $result = $validator->validateDirectory(resource_path('views'));

        return response()->json($result->toArray());
    }
}
```

## Creating Custom Rules

Implement the `BladeRuleInterface`:

```php
<?php

namespace App\BladeRules;

use Deinte\BladeValidator\Data\BladeValidationError;
use Deinte\BladeValidator\Rules\BladeRuleInterface;

class NoTodoComments implements BladeRuleInterface
{
    public function getName(): string
    {
        return 'no-todo-comments';
    }

    public function getDescription(): string
    {
        return 'Detects TODO comments in Blade templates.';
    }

    public function getDefaultSeverity(): string
    {
        return 'warning';
    }

    public function validate(string $content, string $filePath): array
    {
        $errors = [];

        if (preg_match_all('/\{\{--.*TODO.*--\}\}/i', $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $match) {
                $line = substr_count(substr($content, 0, $match[1]), "\n") + 1;

                $errors[] = new BladeValidationError(
                    file: $filePath,
                    line: $line,
                    rule: $this->getName(),
                    message: 'TODO comment found in template.',
                    severity: $this->getDefaultSeverity(),
                );
            }
        }

        return $errors;
    }
}
```

Register your rule in a service provider:

```php
use Deinte\BladeValidator\BladeValidator;
use App\BladeRules\NoTodoComments;

public function boot(BladeValidator $validator)
{
    $validator->addRule(new NoTodoComments());
}
```

## CI/CD Integration

### GitHub Actions

```yaml
name: Blade Validation

on: [push, pull_request]

jobs:
  validate:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'

      - name: Install dependencies
        run: composer install

      - name: Validate Blade templates
        run: php artisan blade:validate --format=github
```

### GitLab CI

```yaml
blade-validation:
  script:
    - composer install
    - php artisan blade:validate --format=json
  only:
    changes:
      - "resources/views/**/*.blade.php"
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Deinte](https://github.com/deinte)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
