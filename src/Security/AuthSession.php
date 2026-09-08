<?php

declare(strict_types=1);

namespace Cardmancer\Security;

use Cardmancer\Core\Config;

final class AuthSession
{
    private bool $started = false;

    public function __construct(private readonly Config $config)
    {
    }

    public function start(): void
    {
        if ($this->started || session_status() === PHP_SESSION_ACTIVE) {
            $this->started = true;

            return;
        }

        session_name((string) $this->config->get('session_name', 'cardmancer_session'));
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => str_starts_with((string) $this->config->get('app_url'), 'https://'),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
        $this->started = true;
    }

    public function login(int $userId): string
    {
        $this->start();
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        return (string) $_SESSION['csrf_token'];
    }

    public function logout(): void
    {
        $this->start();
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'] ?? '',
                'secure' => (bool) $params['secure'],
                'httponly' => (bool) $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
        }

        session_destroy();
        $this->started = false;
    }

    public function userId(): ?int
    {
        $this->start();
        $id = $_SESSION['user_id'] ?? null;

        return is_numeric($id) ? (int) $id : null;
    }

    public function isAuthenticated(): bool
    {
        return $this->userId() !== null;
    }

    public function csrfToken(): string
    {
        $this->start();

        if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION['csrf_token'];
    }

    public function matchesCsrf(string $token): bool
    {
        return $token !== '' && hash_equals($this->csrfToken(), $token);
    }
}
