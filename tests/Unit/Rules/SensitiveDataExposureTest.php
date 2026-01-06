<?php

declare(strict_types=1);

use Deinte\BladeValidator\Rules\SensitiveDataExposure;

beforeEach(function () {
    $this->rule = new SensitiveDataExposure;
});

it('has correct name', function () {
    expect($this->rule->getName())->toBe('sensitive-data-exposure');
});

it('has correct default severity', function () {
    expect($this->rule->getDefaultSeverity())->toBe('error');
});

it('detects password property exposure', function () {
    $content = '{{ $user->password }}';

    $errors = $this->rule->validate($content, '/test.blade.php');

    expect($errors)->toHaveCount(1);
    expect($errors[0]->rule)->toBe('sensitive-data-exposure');
    expect($errors[0]->message)->toContain('password');
});

it('detects token exposure', function () {
    $content = '{{ $user->token }}';

    $errors = $this->rule->validate($content, '/test.blade.php');

    expect($errors)->toHaveCount(1);
});

it('detects env password exposure', function () {
    $content = '{{ env("DB_PASSWORD") }}';

    $errors = $this->rule->validate($content, '/test.blade.php');

    expect($errors)->toHaveCount(1);
});

it('detects bearerToken exposure', function () {
    $content = '{{ $request->bearerToken() }}';

    $errors = $this->rule->validate($content, '/test.blade.php');

    expect($errors)->not->toBeEmpty();
});

it('allows safe property access', function () {
    $content = '{{ $user->name }} {{ $user->email }}';

    $errors = $this->rule->validate($content, '/test.blade.php');

    expect($errors)->toBeEmpty();
});

it('detects sensitive data in raw output', function () {
    $content = '{!! $user->password !!}';

    $errors = $this->rule->validate($content, '/test.blade.php');

    expect($errors)->toHaveCount(1);
});

it('detects array access with sensitive key', function () {
    $content = '{{ $data["password"] }}';

    $errors = $this->rule->validate($content, '/test.blade.php');

    expect($errors)->toHaveCount(1);
});
