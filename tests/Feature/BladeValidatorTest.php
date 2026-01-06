<?php

declare(strict_types=1);

use Deinte\BladeValidator\BladeValidator;
use Deinte\BladeValidator\Data\BladeValidationResult;

beforeEach(function () {
    $this->validator = app(BladeValidator::class);
});

it('validates a correct blade file', function () {
    $result = $this->validator->validateFile(
        $this->getFixturePath('valid-template.blade.php')
    );

    expect($result->valid)->toBeTrue();
    expect($result->errors)->toBeEmpty();
    expect($result->filesChecked)->toBe(1);
});

it('returns error for non-existent file', function () {
    $result = $this->validator->validateFile(
        $this->getFixturePath('does-not-exist.blade.php')
    );

    expect($result->valid)->toBeFalse();
    expect($result->errors)->toHaveCount(1);
    expect($result->errors[0]->rule)->toBe('file-exists');
});

it('validates a directory of blade files', function () {
    $result = $this->validator->validateDirectory($this->getFixturesPath());

    expect($result->filesChecked)->toBeGreaterThan(0);
});

it('validates multiple files', function () {
    $files = [
        $this->getFixturePath('valid-template.blade.php'),
        $this->getFixturePath('invalid-directive-in-attribute.blade.php'),
    ];

    $result = $this->validator->validateFiles($files);

    expect($result->filesChecked)->toBe(2);
});

it('merges results correctly', function () {
    $result1 = new BladeValidationResult(valid: true, errors: [], filesChecked: 5);
    $result2 = new BladeValidationResult(valid: false, errors: [], filesChecked: 3);

    $merged = $result1->merge($result2);

    expect($merged->valid)->toBeFalse();
    expect($merged->filesChecked)->toBe(8);
});

it('can add and remove custom rules', function () {
    $validator = new BladeValidator;

    expect($validator->getRules())->toHaveKey('directive-in-component-attribute');

    $validator->removeRule('directive-in-component-attribute');

    expect($validator->getRules())->not->toHaveKey('directive-in-component-attribute');
});

it('groups errors by file', function () {
    $result = $this->validator->validateDirectory($this->getFixturesPath());

    $grouped = $result->errorsByFile();

    expect($grouped)->toBeArray();
});
