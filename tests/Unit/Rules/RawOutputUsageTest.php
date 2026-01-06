<?php

declare(strict_types=1);

use Deinte\BladeValidator\Rules\RawOutputUsage;

beforeEach(function () {
    $this->rule = new RawOutputUsage;
});

it('has correct name', function () {
    expect($this->rule->getName())->toBe('raw-output-usage');
});

it('detects raw output usage', function () {
    $content = '{!! $userInput !!}';

    $errors = $this->rule->validate($content, '/test.blade.php');

    expect($errors)->toHaveCount(1);
    expect($errors[0]->rule)->toBe('raw-output-usage');
});

it('flags high risk patterns', function () {
    $content = '{!! request()->input("content") !!}';

    $errors = $this->rule->validate($content, '/test.blade.php');

    expect($errors)->toHaveCount(1);
    expect($errors[0]->message)->toContain('HIGH RISK');
});

it('allows ignored patterns with security comment', function () {
    $content = '{{-- @security-ignore raw-output --}}
{!! $safeHtml !!}';

    $errors = $this->rule->validate($content, '/test.blade.php');

    expect($errors)->toBeEmpty();
});

it('detects multiple raw outputs', function () {
    $content = '{!! $html1 !!} {!! $html2 !!}';

    $errors = $this->rule->validate($content, '/test.blade.php');

    expect($errors)->toHaveCount(2);
});
