<?php

declare(strict_types=1);

namespace Deinte\BladeValidator\Facades;

use Deinte\BladeValidator\BladeValidator as BladeValidatorService;
use Deinte\BladeValidator\Data\BladeValidationResult;
use Deinte\BladeValidator\Rules\BladeRuleInterface;
use Illuminate\Support\Facades\Facade;

/**
 * @method static BladeValidationResult validateFile(string $filePath)
 * @method static BladeValidationResult validateFiles(array $filePaths)
 * @method static BladeValidationResult validateDirectory(string $directory)
 * @method static BladeValidatorService addRule(BladeRuleInterface $rule)
 * @method static BladeValidatorService removeRule(string $ruleName)
 * @method static array getRules()
 *
 * @see \Deinte\BladeValidator\BladeValidator
 */
class BladeValidator extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return BladeValidatorService::class;
    }
}
