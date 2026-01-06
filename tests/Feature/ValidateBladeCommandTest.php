<?php

declare(strict_types=1);

use function Pest\Laravel\artisan;

it('can run the validation command', function () {
    artisan('blade:validate', ['path' => [$this->getFixturePath('valid-template.blade.php')]])
        ->assertSuccessful();
});

it('returns failure for invalid files', function () {
    artisan('blade:validate', ['path' => [$this->getFixturePath('invalid-directive-in-attribute.blade.php')]])
        ->assertFailed();
});

it('supports json output format', function () {
    artisan('blade:validate', [
        'path' => [$this->getFixturePath('valid-template.blade.php')],
        '--format' => 'json',
    ])->assertSuccessful();
});

it('supports no-fail option', function () {
    artisan('blade:validate', [
        'path' => [$this->getFixturePath('invalid-directive-in-attribute.blade.php')],
        '--no-fail' => true,
    ])->assertSuccessful();
});

it('supports severity filtering', function () {
    artisan('blade:validate', [
        'path' => [$this->getFixturePath('valid-template.blade.php')],
        '--severity' => 'warning',
    ])->assertSuccessful();
});

it('supports excluding rules', function () {
    artisan('blade:validate', [
        'path' => [$this->getFixturePath('invalid-directive-in-attribute.blade.php')],
        '--exclude-rules' => 'directive-in-component-attribute,unclosed-directive',
    ])->assertSuccessful();
});

it('supports running specific rules only', function () {
    artisan('blade:validate', [
        'path' => [$this->getFixturePath('valid-template.blade.php')],
        '--rules' => 'deprecated-syntax',
    ])->assertSuccessful();
});
