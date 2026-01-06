<?php

declare(strict_types=1);

use Deinte\BladeValidator\Rules\DeprecatedSyntax;

beforeEach(function () {
    $this->rule = new DeprecatedSyntax;
});

it('has correct name', function () {
    expect($this->rule->getName())->toBe('deprecated-syntax');
});

it('has correct default severity', function () {
    expect($this->rule->getDefaultSeverity())->toBe('warning');
});

it('detects triple brace syntax', function () {
    $content = '{{{ $variable }}}';

    $errors = $this->rule->validate($content, '/test.blade.php');

    expect($errors)->toHaveCount(1);
    expect($errors[0]->message)->toContain('Triple-brace');
});

it('detects double escaping with e()', function () {
    $content = '{{ e($variable) }}';

    $errors = $this->rule->validate($content, '/test.blade.php');

    expect($errors)->toHaveCount(1);
    expect($errors[0]->message)->toContain('double escaping');
});

it('detects @else if with space', function () {
    $content = '@if($a)
        A
    @else if($b)
        B
    @endif';

    $errors = $this->rule->validate($content, '/test.blade.php');

    expect($errors)->toHaveCount(1);
    expect($errors[0]->message)->toContain('@elseif');
    expect($errors[0]->severity)->toBe('error');
});

it('detects deprecated str_ helpers', function () {
    $content = '{{ str_limit($text, 100) }}';

    $errors = $this->rule->validate($content, '/test.blade.php');

    expect($errors)->toHaveCount(1);
    expect($errors[0]->message)->toContain('Str::');
});

it('detects deprecated elixir helper', function () {
    $content = '<link href="{{ elixir(\'css/app.css\') }}">';

    $errors = $this->rule->validate($content, '/test.blade.php');

    expect($errors)->toHaveCount(1);
    expect($errors[0]->message)->toContain('mix()');
});

it('allows modern syntax', function () {
    $content = '{{ $variable }}
    @if($a)
        A
    @elseif($b)
        B
    @endif';

    $errors = $this->rule->validate($content, '/test.blade.php');

    expect($errors)->toBeEmpty();
});
