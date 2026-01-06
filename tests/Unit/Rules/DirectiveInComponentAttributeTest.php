<?php

declare(strict_types=1);

use Deinte\BladeValidator\Rules\DirectiveInComponentAttribute;

beforeEach(function () {
    $this->rule = new DirectiveInComponentAttribute;
});

it('has correct name', function () {
    expect($this->rule->getName())->toBe('directive-in-component-attribute');
});

it('has correct default severity', function () {
    expect($this->rule->getDefaultSeverity())->toBe('error');
});

it('detects @if directive in component attribute', function () {
    $content = '<x-button @if($active) color="primary" @endif />';

    $errors = $this->rule->validate($content, '/test.blade.php');

    expect($errors)->toHaveCount(1);
    expect($errors[0]->rule)->toBe('directive-in-component-attribute');
    expect($errors[0]->message)->toContain('@if');
});

it('allows @class directive in component attribute', function () {
    $content = '<x-button @class(["active" => $isActive]) />';

    $errors = $this->rule->validate($content, '/test.blade.php');

    expect($errors)->toBeEmpty();
});

it('allows @style directive in component attribute', function () {
    $content = '<x-button @style(["color: red" => $isRed]) />';

    $errors = $this->rule->validate($content, '/test.blade.php');

    expect($errors)->toBeEmpty();
});

it('detects multiple directives', function () {
    $content = '<x-card @if($show) visible @endif @foreach($items as $item) data="{{ $item }}" @endforeach />';

    $errors = $this->rule->validate($content, '/test.blade.php');

    expect($errors)->not->toBeEmpty();
});

it('ignores directives outside component tags', function () {
    $content = '@if($show) <div>content</div> @endif';

    $errors = $this->rule->validate($content, '/test.blade.php');

    expect($errors)->toBeEmpty();
});
