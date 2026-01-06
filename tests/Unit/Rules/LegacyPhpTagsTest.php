<?php

declare(strict_types=1);

use Deinte\BladeValidator\Rules\LegacyPhpTags;

beforeEach(function () {
    $this->rule = new LegacyPhpTags;
});

it('has correct name', function () {
    expect($this->rule->getName())->toBe('legacy-php-tags');
});

it('has correct default severity', function () {
    expect($this->rule->getDefaultSeverity())->toBe('error');
});

it('detects short echo tag', function () {
    $content = '<td><?= $payment->name ?></td>';

    $errors = $this->rule->validate($content, '/test.blade.php');

    expect($errors)->toHaveCount(1);
    expect($errors[0]->rule)->toBe('legacy-php-tags');
    expect($errors[0]->message)->toContain('<?= ?>');
    expect($errors[0]->message)->toContain('{{ $payment->name }}');
});

it('detects multiple short echo tags', function () {
    $content = '<td><?= $payment->name ?></td>
        <td><?= $payment->iban ?></td>
        <td><?= $payment->amount ?></td>';

    $errors = $this->rule->validate($content, '/test.blade.php');

    expect($errors)->toHaveCount(3);
});

it('detects php tags with echo', function () {
    $content = '<td><?php echo $payment->type; ?></td>';

    $errors = $this->rule->validate($content, '/test.blade.php');

    expect($errors)->toHaveCount(1);
    expect($errors[0]->message)->toContain('{{ $payment->type }}');
});

it('detects php tags with complex code', function () {
    $content = '<td><?php
        $amount = $payment->amount;
        echo "€" . number_format($amount, 2);
    ?></td>';

    $errors = $this->rule->validate($content, '/test.blade.php');

    expect($errors)->toHaveCount(1);
    expect($errors[0]->message)->toContain('@php');
});

it('allows blade syntax', function () {
    $content = '<td>{{ $payment->name }}</td>
        <td>{{ $payment->iban }}</td>
        @php
            $amount = $payment->amount;
        @endphp';

    $errors = $this->rule->validate($content, '/test.blade.php');

    expect($errors)->toBeEmpty();
});

it('detects short tags with method calls', function () {
    $content = '<?= \App\Http\Controllers\HelperController::factuurgetal($payment->amount) ?>';

    $errors = $this->rule->validate($content, '/test.blade.php');

    expect($errors)->toHaveCount(1);
});

it('detects short tags with string concatenation', function () {
    $content = '<?= "€" . number_format($amount, 2) ?>';

    $errors = $this->rule->validate($content, '/test.blade.php');

    expect($errors)->toHaveCount(1);
});
