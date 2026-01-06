# Changelog

All notable changes to `laravel-blade-validator` will be documented in this file.

## 1.0.0 - 2024-XX-XX

- Initial release
- Blade syntax validation using Laravel's BladeCompiler
- Auto-fix support with `--fix` and `--dry-run` options
- 8 built-in validation rules:
  - `directive-in-component-attribute`: Detects directives in component tags
  - `use-statement-in-php-block`: Detects use statements in @php blocks
  - `raw-output-usage`: Security check for raw output {!! !!}
  - `unclosed-directive`: Detects unclosed block directives
  - `inline-javascript`: Security check for inline JS with Blade
  - `sensitive-data-exposure`: Security check for sensitive data output
  - `deprecated-syntax`: Detects deprecated Blade patterns
  - `legacy-php-tags`: Detects legacy PHP tags (<?= and <?php) that should use Blade
- Artisan command `blade:validate` with multiple output formats
- GrumPHP integration for pre-commit validation
- Configurable rules and severity levels
- Extensible with custom rules
