<?php

declare(strict_types=1);

namespace Cardmancer\Core;

final class Config
{
    /** @param array<string, mixed> $values */
    private function __construct(private readonly array $values)
    {
    }

    public static function load(string $basePath): self
    {
        self::loadDotEnv($basePath . DIRECTORY_SEPARATOR . '.env');

        $env = static function (string $key, mixed $default = null): mixed {
            $value = getenv($key);

            if ($value === false || $value === '') {
                return $default;
            }

            return $value;
        };

        $appUrl = (string) $env('APP_URL', 'http://localhost:8080');

        return new self([
            'base_path' => $basePath,
            'app_env' => (string) $env('APP_ENV', 'local'),
            'app_debug' => self::toBool($env('APP_DEBUG', '0')),
            'app_url' => rtrim($appUrl, '/'),
            'session_name' => (string) $env('APP_SESSION_NAME', 'cardmancer_session'),
            'db_host' => (string) $env('DB_HOST', '127.0.0.1'),
            'db_port' => (int) $env('DB_PORT', 3306),
            'db_name' => (string) $env('DB_NAME', 'cardmancer'),
            'db_user' => (string) $env('DB_USER', 'cardmancer'),
            'db_password' => (string) $env('DB_PASSWORD', 'cardmancer'),
            'upload_max_bytes' => (int) $env('UPLOAD_MAX_BYTES', 5 * 1024 * 1024),
            'upload_dir' => $basePath . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'cards',
        ]);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->values[$key] ?? $default;
    }

    public function basePath(): string
    {
        return (string) $this->values['base_path'];
    }

    public function isDebug(): bool
    {
        return (bool) $this->values['app_debug'];
    }

    public function isProduction(): bool
    {
        return $this->get('app_env') === 'production';
    }

    private static function toBool(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? false;
    }

    private static function loadDotEnv(string $path): void
    {
        if (!is_file($path) || !is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            $value = trim($value, "\"'");

            if ($key !== '' && getenv($key) === false) {
                putenv($key . '=' . $value);
            }
        }
    }
}
