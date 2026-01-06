<?php

declare(strict_types=1);

use Deinte\BladeValidator\Rules\InlineJavaScript;

beforeEach(function () {
    $this->rule = new InlineJavaScript;
});

it('has correct name', function () {
    expect($this->rule->getName())->toBe('inline-javascript');
});

it('detects onclick with blade expression', function () {
    $content = '<button onclick="{{ $action }}">Click</button>';

    $errors = $this->rule->validate($content, '/test.blade.php');

    expect($errors)->toHaveCount(1);
    expect($errors[0]->rule)->toBe('inline-javascript');
    expect($errors[0]->message)->toContain('onclick');
});

it('detects javascript protocol with blade', function () {
    $content = '<a href="javascript:{{ $code }}">Link</a>';

    $errors = $this->rule->validate($content, '/test.blade.php');

    expect($errors)->toHaveCount(1);
    expect($errors[0]->severity)->toBe('error');
});

it('detects blade in script tags', function () {
    $content = '<script>var data = {{ $data }};</script>';

    $errors = $this->rule->validate($content, '/test.blade.php');

    expect($errors)->not->toBeEmpty();
});

it('allows safe patterns in script tags', function () {
    $content = '<script>var data = @json($data);</script>';

    $errors = $this->rule->validate($content, '/test.blade.php');

    expect($errors)->toBeEmpty();
});

it('allows route and url helpers', function () {
    $content = '<script>var url = "{{ route(\'home\') }}";</script>';

    $errors = $this->rule->validate($content, '/test.blade.php');

    expect($errors)->toBeEmpty();
});

it('detects eval with blade', function () {
    $content = 'eval("{{ $code }}")';

    $errors = $this->rule->validate($content, '/test.blade.php');

    expect($errors)->not->toBeEmpty();
});
