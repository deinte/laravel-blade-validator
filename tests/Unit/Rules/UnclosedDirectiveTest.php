<?php

declare(strict_types=1);

use Deinte\BladeValidator\Rules\UnclosedDirective;

beforeEach(function () {
    $this->rule = new UnclosedDirective;
});

it('has correct name', function () {
    expect($this->rule->getName())->toBe('unclosed-directive');
});

it('has correct default severity', function () {
    expect($this->rule->getDefaultSeverity())->toBe('error');
});

it('detects unclosed @if directive', function () {
    $content = '@if($show)
        <div>content</div>';

    $errors = $this->rule->validate($content, '/test.blade.php');

    expect($errors)->toHaveCount(1);
    expect($errors[0]->rule)->toBe('unclosed-directive');
    expect($errors[0]->message)->toContain('@if');
});

it('allows properly closed @if directive', function () {
    $content = '@if($show)
        <div>content</div>
    @endif';

    $errors = $this->rule->validate($content, '/test.blade.php');

    expect($errors)->toBeEmpty();
});

it('detects unclosed @foreach', function () {
    $content = '@foreach($items as $item)
        {{ $item }}';

    $errors = $this->rule->validate($content, '/test.blade.php');

    expect($errors)->toHaveCount(1);
    expect($errors[0]->message)->toContain('@foreach');
});

it('handles nested directives correctly', function () {
    $content = '@if($outer)
        @foreach($items as $item)
            {{ $item }}
        @endforeach
    @endif';

    $errors = $this->rule->validate($content, '/test.blade.php');

    expect($errors)->toBeEmpty();
});

it('detects multiple unclosed directives', function () {
    $content = '@if($show)
        @foreach($items as $item)
            {{ $item }}';

    $errors = $this->rule->validate($content, '/test.blade.php');

    expect($errors)->toHaveCount(2);
});

it('allows @section with @show', function () {
    $content = '@section("content")
        <div>content</div>
    @show';

    $errors = $this->rule->validate($content, '/test.blade.php');

    expect($errors)->toBeEmpty();
});
