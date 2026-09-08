<?php

declare(strict_types=1);

namespace Cardmancer\Controllers;

use Cardmancer\Core\HttpException;
use Cardmancer\Core\Request;
use Cardmancer\Core\Response;
use Cardmancer\Services\CatalogService;

final class CatalogController
{
    public function __construct(private readonly CatalogService $catalog)
    {
    }

    public function options(Request $request): Response
    {
        $game = strtolower(trim((string) $request->queryValue('game', '')));

        if (!$this->catalog->hasGame($game)) {
            throw new HttpException('Escolha um jogo válido para carregar as opções.', 422, 'invalid_game');
        }

        return Response::json($this->catalog->options($game));
    }
}
