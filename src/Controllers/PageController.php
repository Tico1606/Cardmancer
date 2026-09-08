<?php

declare(strict_types=1);

namespace Cardmancer\Controllers;

use Cardmancer\Core\HttpException;
use Cardmancer\Core\Request;
use Cardmancer\Core\Response;
use Cardmancer\Core\View;
use Cardmancer\Repositories\UserRepository;
use Cardmancer\Security\AuthSession;

final class PageController
{
    public function __construct(
        private readonly View $view,
        private readonly AuthSession $session,
        private readonly UserRepository $users,
    ) {
    }

    public function home(): Response
    {
        return Response::redirect($this->session->isAuthenticated() ? '/cards' : '/login');
    }

    public function login(): Response
    {
        if ($this->session->isAuthenticated()) {
            return Response::redirect('/cards');
        }

        return Response::html($this->view->render('login'));
    }

    public function cards(Request $request): Response
    {
        $userId = $this->session->userId();

        if ($userId === null) {
            return Response::redirect('/login');
        }

        $user = $this->users->findById($userId);

        if ($user === null) {
            $this->session->logout();

            return Response::redirect('/login');
        }

        return Response::html($this->view->render('cards/index', [
            'user' => $user,
            'csrfToken' => $this->session->csrfToken(),
            'requestPath' => $request->path(),
        ]));
    }

    public function profile(): Response
    {
        $userId = $this->session->userId();

        if ($userId === null) {
            return Response::redirect('/login');
        }

        $user = $this->users->findById($userId);

        if ($user === null) {
            $this->session->logout();

            return Response::redirect('/login');
        }

        return Response::html($this->view->render('profile', [
            'user' => $user,
            'csrfToken' => $this->session->csrfToken(),
        ]));
    }

    /** @param array<string, string> $params */
    public function cardDetail(Request $request, array $params): Response
    {
        $userId = $this->session->userId();

        if ($userId === null) {
            return Response::redirect('/login');
        }

        $cardId = filter_var($params['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($cardId === false || $cardId === null) {
            throw new HttpException('Identificador de carta inválido.', 400, 'invalid_id');
        }

        $user = $this->users->findById($userId);

        if ($user === null) {
            $this->session->logout();

            return Response::redirect('/login');
        }

        return Response::html($this->view->render('cards/detail', [
            'user' => $user,
            'csrfToken' => $this->session->csrfToken(),
            'cardId' => (int) $cardId,
            'requestPath' => $request->path(),
        ]));
    }
}
