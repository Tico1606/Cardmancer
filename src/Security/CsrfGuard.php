<?php

declare(strict_types=1);

namespace Cardmancer\Security;

use Cardmancer\Core\HttpException;
use Cardmancer\Core\Request;

final class CsrfGuard
{
    public function __construct(private readonly AuthSession $session)
    {
    }

    public function assert(Request $request): void
    {
        $token = $request->header('x-csrf-token') ?? (string) $request->input('_csrf', '');

        if (!$this->session->matchesCsrf($token)) {
            throw new HttpException('A sessão de segurança expirou. Atualize a página e tente novamente.', 419, 'csrf_mismatch');
        }
    }
}
