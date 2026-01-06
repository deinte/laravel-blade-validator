<?php

declare(strict_types=1);

arch('it will not use debugging functions')
    ->expect(['dd', 'dump', 'ray', 'var_dump', 'print_r'])
    ->not->toBeUsed();

arch('strict types are declared in all files')
    ->expect('Deinte\BladeValidator')
    ->toUseStrictTypes();

arch('rules implement the interface')
    ->expect('Deinte\BladeValidator\Rules')
    ->classes()
    ->toImplement('Deinte\BladeValidator\Rules\BladeRuleInterface');

arch('data classes are final')
    ->expect('Deinte\BladeValidator\Data')
    ->classes()
    ->toBeFinal();
