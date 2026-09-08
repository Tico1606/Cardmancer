<?php

declare(strict_types=1);

namespace Cardmancer\Core;

use Cardmancer\Controllers\AuthController;
use Cardmancer\Controllers\CardController;
use Cardmancer\Controllers\CatalogController;
use Cardmancer\Controllers\PageController;
use Cardmancer\Controllers\ProfileController;
use Cardmancer\Repositories\CardRepository;
use Cardmancer\Repositories\UserRepository;
use Cardmancer\Security\AuthSession;
use Cardmancer\Security\CsrfGuard;
use Cardmancer\Services\CatalogService;
use Cardmancer\Services\UploadService;
use Cardmancer\Validation\CardValidator;

final class Application
{
    private function __construct(
        private readonly Router $router,
    ) {
    }

    public static function create(Config $config): self
    {
        $database = new Database($config);
        $connection = $database->connection();
        $users = new UserRepository($connection);
        $users->ensureSeedCredentials();
        $cards = new CardRepository($connection);
        $cards->ensureUniqueNameConstraint();
        $catalog = new CatalogService($config);
        $session = new AuthSession($config);
        $csrf = new CsrfGuard($session);
        $view = new View($config);
        $router = new Router();

        $pages = new PageController($view, $session, $users);
        $auth = new AuthController($users, $session, $csrf);
        $profile = new ProfileController($users, $session, $csrf);
        $catalogController = new CatalogController($catalog);
        $cardController = new CardController($cards, $catalog, new CardValidator($catalog), new UploadService($config), $session, $csrf);

        $router->get('/', static fn (): Response => $pages->home());
        $router->get('/login', static fn (): Response => $pages->login());
        $router->get('/profile', static fn (): Response => $pages->profile());
        $router->get('/cards', static fn (Request $request): Response => $pages->cards($request));
        $router->get('/cards/{id}', static fn (Request $request, array $params): Response => $pages->cardDetail($request, $params));

        $router->post('/api/auth/login', static fn (Request $request): Response => $auth->login($request));
        $router->post('/api/auth/logout', static fn (Request $request): Response => $auth->logout($request));
        $router->get('/api/auth/session', static fn (): Response => $auth->session());
        $router->post('/api/profile', static fn (Request $request): Response => $profile->update($request));

        $router->get('/api/catalog/options', static fn (Request $request): Response => $catalogController->options($request));
        $router->get('/api/cards', static fn (Request $request): Response => $cardController->index($request));
        $router->get('/api/cards/{id}', static fn (Request $request, array $params): Response => $cardController->show($request, $params));
        $router->post('/api/cards', static fn (Request $request): Response => $cardController->store($request));
        $router->put('/api/cards/{id}', static fn (Request $request, array $params): Response => $cardController->update($request, $params));
        $router->patch('/api/cards/{id}', static fn (Request $request, array $params): Response => $cardController->update($request, $params));
        $router->delete('/api/cards/{id}', static fn (Request $request, array $params): Response => $cardController->destroy($request, $params));

        return new self($router);
    }

    public function handle(Request $request): Response
    {
        return $this->router->dispatch($request);
    }
}
