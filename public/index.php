<?php

declare(strict_types=1);

use Cardmancer\Core\HttpException;
use Cardmancer\Core\Response;

$request = null;

if (getenv('APP_DEBUG') === '1') {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
}

try {
    $application = require dirname(__DIR__) . '/src/bootstrap.php';
    $request = Cardmancer\Core\Request::capture();
    $application->handle($request)->send();
} catch (HttpException $exception) {
    $requestPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
    $isApiRequest = $request?->isApi() ?? (is_string($requestPath) && str_starts_with($requestPath, '/api'));

    if ($isApiRequest) {
        Response::error($exception)->send();
    } else {
        Response::html('<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><title>Cardmancer</title><link rel="icon" href="/assets/images/card-games.png?v=2" type="image/png"><link rel="stylesheet" href="/assets/css/app.css"></head><body class="system-error"><main><p class="eyebrow">Cardmancer</p><h1>' . htmlspecialchars($exception->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</h1><a class="button button-primary" href="/cards">Voltar ao catálogo</a></main></body></html>', $exception->status())->send();
    }
} catch (Throwable $exception) {
    error_log($exception->__toString());
    $generic = new HttpException('Não foi possível concluir a solicitação.', 500, 'server_error');
    $requestPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
    $isApiRequest = $request?->isApi() ?? (is_string($requestPath) && str_starts_with($requestPath, '/api'));

    if ($isApiRequest) {
        Response::error($generic)->send();
    } else {
        Response::html('<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><title>Cardmancer</title><link rel="icon" href="/assets/images/card-games.png?v=2" type="image/png"><link rel="stylesheet" href="/assets/css/app.css"></head><body class="system-error"><main><p class="eyebrow">Cardmancer</p><h1>O catálogo está temporariamente indisponível.</h1><a class="button button-primary" href="/">Tentar novamente</a></main></body></html>', 500)->send();
    }
}
