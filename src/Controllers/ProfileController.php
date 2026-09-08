<?php

declare(strict_types=1);

namespace Cardmancer\Controllers;

use Cardmancer\Core\HttpException;
use Cardmancer\Core\Request;
use Cardmancer\Core\Response;
use Cardmancer\Repositories\UserRepository;
use Cardmancer\Security\AuthSession;
use Cardmancer\Security\CsrfGuard;

final class ProfileController
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly AuthSession $session,
        private readonly CsrfGuard $csrf,
    ) {
    }

    public function update(Request $request): Response
    {
        $userId = $this->session->userId();

        if ($userId === null) {
            throw new HttpException('Sua sessão expirou. Entre novamente para continuar.', 401, 'auth_required');
        }

        $this->csrf->assert($request);

        $name = trim((string) $request->input('name', ''));
        $email = strtolower(trim((string) $request->input('email', '')));
        $newPassword = (string) $request->input('new_password', '');
        $confirmPassword = (string) $request->input('confirm_password', '');
        $fields = [];

        if ($name === '') {
            $fields['name'] = 'Informe seu nome.';
        } elseif (strlen($name) > 120) {
            $fields['name'] = 'Use no máximo 120 caracteres.';
        }

        if ($email === '') {
            $fields['email'] = 'Informe seu e-mail.';
        } elseif (strlen($email) > 190 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $fields['email'] = 'Informe um e-mail válido.';
        } else {
            $existing = $this->users->findByEmail($email);

            if ($existing !== null && (int) $existing['id'] !== $userId) {
                $fields['email'] = 'Este e-mail já está em uso.';
            }
        }

        if ($newPassword !== '' || $confirmPassword !== '') {
            if (strlen($newPassword) < 8) {
                $fields['new_password'] = 'Use pelo menos 8 caracteres.';
            }

            if ($newPassword !== $confirmPassword) {
                $fields['confirm_password'] = 'As senhas não coincidem.';
            }
        }

        if ($fields !== []) {
            throw new HttpException('Corrija os dados do perfil.', 422, 'validation_error', $fields);
        }

        $this->users->updateProfile($userId, $name, $email, $newPassword !== '' ? password_hash($newPassword, PASSWORD_DEFAULT) : null);
        $user = $this->users->findById($userId);

        if ($user === null) {
            $this->session->logout();

            throw new HttpException('Usuário não encontrado.', 404, 'user_not_found');
        }

        return Response::json(['user' => $this->publicUser($user)]);
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
