<?php

declare(strict_types=1);

namespace Deinte\BladeValidator\Rules;

use Deinte\BladeValidator\Data\BladeValidationError;

/**
 * Detects potential exposure of sensitive data in Blade templates.
 *
 * Flagged patterns:
 * - {{ $user->password }}
 * - {{ config('app.key') }}
 * - {{ env('DB_PASSWORD') }}
 * - {{ $request->bearerToken() }}
 * - {!! $secret !!}
 */
class SensitiveDataExposure implements BladeRuleInterface
{
    /**
     * Default sensitive patterns to check.
     *
     * @var array<string>
     */
    private array $defaultPatterns = [
        'password',
        'passwd',
        'secret',
        'token',
        'api_key',
        'apiKey',
        'api-key',
        'credential',
        'private_key',
        'privateKey',
        'private-key',
        'ssn',
        'social_security',
        'credit_card',
        'creditCard',
        'card_number',
        'cardNumber',
        'cvv',
        'pin',
        'auth_token',
        'authToken',
        'access_token',
        'accessToken',
        'refresh_token',
        'refreshToken',
        'bearer',
        'jwt',
        'session_id',
        'sessionId',
    ];

    public function getName(): string
    {
        return 'sensitive-data-exposure';
    }

    public function getDescription(): string
    {
        return 'Detects potential exposure of sensitive data like passwords, tokens, and API keys in Blade output.';
    }

    public function getDefaultSeverity(): string
    {
        return 'error';
    }

    /**
     * @return array<BladeValidationError>
     */
    public function validate(string $content, string $filePath): array
    {
        $errors = [];

        // Get patterns from config or use defaults
        $patterns = config('blade-validator.sensitive_patterns', $this->defaultPatterns);

        // Check escaped output {{ }}
        $errors = array_merge($errors, $this->checkEchoStatements($content, $filePath, $patterns, false));

        // Check raw output {!! !!}
        $errors = array_merge($errors, $this->checkEchoStatements($content, $filePath, $patterns, true));

        // Check specific dangerous function calls
        $errors = array_merge($errors, $this->checkDangerousFunctions($content, $filePath));

        return $errors;
    }

    /**
     * Check echo statements for sensitive patterns.
     *
     * @param  array<string>  $patterns
     * @return array<BladeValidationError>
     */
    private function checkEchoStatements(string $content, string $filePath, array $patterns, bool $raw): array
    {
        $errors = [];

        $echoPattern = $raw
            ? '/\{!!\s*(.*?)\s*!!\}/s'
            : '/\{\{\s*(.*?)\s*\}\}/s';

        if (preg_match_all($echoPattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[1] as $match) {
                $expression = $match[0];
                $offset = $match[1];
                $lineNumber = substr_count(substr($content, 0, $offset), "\n") + 1;

                // Skip Blade comments
                if (str_starts_with(trim($expression), '--')) {
                    continue;
                }

                // Check for sensitive patterns
                foreach ($patterns as $pattern) {
                    if ($this->containsSensitivePattern($expression, $pattern)) {
                        $errors[] = new BladeValidationError(
                            file: $filePath,
                            line: $lineNumber,
                            rule: $this->getName(),
                            message: "Potential sensitive data exposure: expression contains '{$pattern}'. Avoid outputting sensitive data directly in templates.",
                            severity: $this->getDefaultSeverity(),
                        );
                        break; // Only report once per expression
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * Check for dangerous function calls that expose sensitive data.
     *
     * @return array<BladeValidationError>
     */
    private function checkDangerousFunctions(string $content, string $filePath): array
    {
        $errors = [];

        $dangerousCalls = [
            // env() calls with sensitive keys
            [
                'pattern' => '/\{\{[^}]*env\s*\(\s*[\'"](?:.*(?:PASSWORD|SECRET|KEY|TOKEN|CREDENTIAL)[^\'"]*)[\'"](?:\s*,\s*[^)]+)?\s*\)[^}]*\}\}/i',
                'message' => 'Exposing environment variable that may contain sensitive data. Use config() instead and never expose secrets in views.',
            ],
            // config() calls with sensitive keys
            [
                'pattern' => '/\{\{[^}]*config\s*\(\s*[\'"](?:app\.key|services\.[^\'"]*(secret|key|token)|mail\.password|database\.[^\'"]*(password|key))[\'"](?:\s*,\s*[^)]+)?\s*\)[^}]*\}\}/i',
                'message' => 'Exposing configuration value that may contain sensitive data.',
            ],
            // bearerToken() calls
            [
                'pattern' => '/\{\{[^}]*->bearerToken\s*\(\s*\)[^}]*\}\}/i',
                'message' => "Exposing bearer token directly in template. This is a security risk.",
            ],
            // getPassword() or similar
            [
                'pattern' => '/\{\{[^}]*->get(?:Password|Secret|Token|ApiKey|PrivateKey)\s*\(\s*\)[^}]*\}\}/i',
                'message' => 'Exposing sensitive data through getter method.',
            ],
            // Session sensitive data
            [
                'pattern' => '/\{\{[^}]*session\s*\(\s*[\'"](?:.*(?:password|secret|token|key)[^\'"]*)[\'"](?:\s*,\s*[^)]+)?\s*\)[^}]*\}\}/i',
                'message' => 'Exposing sensitive session data.',
            ],
            // Hash/encryption keys
            [
                'pattern' => '/\{\{[^}]*(?:Hash|Crypt|encrypt|decrypt)\s*::[^}]*\}\}/i',
                'message' => 'Direct usage of encryption/hashing functions in template output.',
            ],
        ];

        foreach ($dangerousCalls as $check) {
            if (preg_match_all($check['pattern'], $content, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $offset = $match[1];
                    $lineNumber = substr_count(substr($content, 0, $offset), "\n") + 1;

                    $errors[] = new BladeValidationError(
                        file: $filePath,
                        line: $lineNumber,
                        rule: $this->getName(),
                        message: $check['message'],
                        severity: $this->getDefaultSeverity(),
                    );
                }
            }
        }

        return $errors;
    }

    /**
     * Check if expression contains a sensitive pattern.
     */
    private function containsSensitivePattern(string $expression, string $pattern): bool
    {
        // Check as property access: ->password, ->secret, etc.
        if (preg_match('/->'.preg_quote($pattern, '/').'\b/i', $expression)) {
            return true;
        }

        // Check as array access: ['password'], ["secret"], etc.
        if (preg_match('/\[\s*[\'"]'.preg_quote($pattern, '/').'\s*[\'"]\]/i', $expression)) {
            return true;
        }

        // Check as variable: $password, $secret, etc.
        if (preg_match('/\$'.preg_quote($pattern, '/').'\b/i', $expression)) {
            return true;
        }

        // Check as method: getPassword(), getSecret(), etc.
        if (preg_match('/(?:get|fetch|retrieve|load)'.preg_quote(ucfirst($pattern), '/').'\s*\(/i', $expression)) {
            return true;
        }

        return false;
    }
}
