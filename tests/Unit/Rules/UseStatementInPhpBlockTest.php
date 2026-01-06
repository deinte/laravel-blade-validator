<?php

declare(strict_types=1);

use Deinte\BladeValidator\Rules\UseStatementInPhpBlock;

beforeEach(function () {
    $this->rule = new UseStatementInPhpBlock;
});

it('has correct name', function () {
    expect($this->rule->getName())->toBe('use-statement-in-php-block');
});

it('has correct default severity', function () {
    expect($this->rule->getDefaultSeverity())->toBe('error');
});

it('detects use statement in @php block', function () {
    $content = '@php
        use App\Models\User;
        $users = User::all();
    @endphp';

    $errors = $this->rule->validate($content, '/test.blade.php');

    expect($errors)->toHaveCount(1);
    expect($errors[0]->rule)->toBe('use-statement-in-php-block');
});

it('allows fully qualified class names', function () {
    $content = '@php
        $user = \App\Models\User::find(1);
    @endphp';

    $errors = $this->rule->validate($content, '/test.blade.php');

    expect($errors)->toBeEmpty();
});

it('allows @use directive at file level', function () {
    $content = '@use(\'App\Models\User\')

    {{ $user->name }}';

    $errors = $this->rule->validate($content, '/test.blade.php');

    expect($errors)->toBeEmpty();
});

it('detects multiple use statements', function () {
    $content = '@php
        use App\Models\User;
        use App\Models\Post;
    @endphp';

    $errors = $this->rule->validate($content, '/test.blade.php');

    expect($errors)->toHaveCount(1); // One error per @php block
});
