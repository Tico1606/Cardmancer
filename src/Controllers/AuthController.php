<?php

declare(strict_types=1);

namespace Cardmancer\Controllers;

use Cardmancer\Core\HttpException;
use Cardmancer\Core\Request;
use Cardmancer\Core\Response;
use Cardmancer\Repositories\UserRepository;
use Cardmancer\Security\AuthSession;
use Cardmancer\Security\CsrfGuard;

final class AuthController
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly AuthSession $session,
        private readonly CsrfGuard $csrf,
    ) {
    }

    public function login(Request $request): Response
    {
        $email = strtolower(trim((string) $request->input('email', '')));
        $password = (string) $request->input('password', '');
        $user = $email !== '' ? $this->users->findByEmail($email) : null;

        if ($user === null || $password === '' || !password_verify($password, (string) $user['password_hash'])) {
            throw new HttpException('E-mail ou senha inválidos.', 401, 'invalid_credentials');
        }

        $token = $this->session->login((int) $user['id']);

        return Response::json([
            'user' => $this->publicUser($user),
            'csrf_token' => $token,
        ]);
    }

    public function logout(Request $request): Response
    {
        if (!$this->session->isAuthenticated()) {
            return Response::json(['logged_out' => true]);
        }

        $this->csrf->assert($request);
        $this->session->logout();

        return Response::json(['logged_out' => true]);
    }

    public function session(): Response
    {
        $userId = $this->session->userId();

        if ($userId === null) {
            return Response::json(['authenticated' => false]);
        }

        $user = $this->users->findById($userId);

        if ($user === null) {
            $this->session->logout();

            return Response::json(['authenticated' => false]);
        }

        return Response::json([
            'authenticated' => true,
            'user' => $this->publicUser($user),
            'csrf_token' => $this->session->csrfToken(),
        ]);
    }

    /** @param array<string, mixed> $user @return array{id: int, name: string, email: string} */
    private function publicUser(array $user): array
    {
        return [
            'id' => (int) $user['id'],
            'name' => (string) $user['name'],
            'email' => (string) $user['email'],
        ];
    }
}
