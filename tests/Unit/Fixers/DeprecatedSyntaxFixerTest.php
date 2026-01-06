<?php

declare(strict_types=1);

use Deinte\BladeValidator\Fixers\DeprecatedSyntaxFixer;

beforeEach(function () {
    $this->fixer = new DeprecatedSyntaxFixer;
});

it('fixes triple braces', function () {
    $content = '{{{ $variable }}}';

    $result = $this->fixer->fix($content, '/test.blade.php');

    expect($result->modified)->toBeTrue();
    expect($result->content)->toBe('{{ $variable }}');
});

it('fixes double escaping', function () {
    $content = '{{ e($variable) }}';

    $result = $this->fixer->fix($content, '/test.blade.php');

    expect($result->content)->toBe('{{ $variable }}');
});

it('fixes else if spacing', function () {
    $content = '@if($a) A @else if($b) B @endif';

    $result = $this->fixer->fix($content, '/test.blade.php');

    expect($result->content)->toBe('@if($a) A @elseif($b) B @endif');
});

it('returns unchanged for valid syntax', function () {
    $content = '{{ $var }} @elseif($b)';

    $result = $this->fixer->fix($content, '/test.blade.php');

    expect($result->modified)->toBeFalse();
});
