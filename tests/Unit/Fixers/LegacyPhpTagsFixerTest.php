<?php

declare(strict_types=1);

use Deinte\BladeValidator\Fixers\LegacyPhpTagsFixer;

beforeEach(function () {
    $this->fixer = new LegacyPhpTagsFixer;
});

it('fixes short echo tags', function () {
    $content = '<td><?= $payment->name ?></td>';

    $result = $this->fixer->fix($content, '/test.blade.php');

    expect($result->modified)->toBeTrue();
    expect($result->content)->toBe('<td>{{ $payment->name }}</td>');
});

it('fixes multiple short echo tags', function () {
    $content = '<td><?= $a ?></td><td><?= $b ?></td>';

    $result = $this->fixer->fix($content, '/test.blade.php');

    expect($result->content)->toBe('<td>{{ $a }}</td><td>{{ $b }}</td>');
    expect($result->changes)->toHaveCount(2);
});

it('fixes php echo to blade', function () {
    $content = '<td><?php echo $name; ?></td>';

    $result = $this->fixer->fix($content, '/test.blade.php');

    expect($result->content)->toBe('<td>{{ $name }}</td>');
});

it('returns unchanged for valid blade', function () {
    $content = '<td>{{ $name }}</td>';

    $result = $this->fixer->fix($content, '/test.blade.php');

    expect($result->modified)->toBeFalse();
});
