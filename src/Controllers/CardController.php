<?php

declare(strict_types=1);

namespace Cardmancer\Controllers;

use Cardmancer\Core\HttpException;
use Cardmancer\Core\Request;
use Cardmancer\Core\Response;
use Cardmancer\Repositories\CardRepository;
use Cardmancer\Security\AuthSession;
use Cardmancer\Security\CsrfGuard;
use Cardmancer\Services\CatalogService;
use Cardmancer\Services\UploadService;
use Cardmancer\Validation\CardValidator;

final class CardController
{
    public function __construct(
        private readonly CardRepository $cards,
        private readonly CatalogService $catalog,
        private readonly CardValidator $validator,
        private readonly UploadService $uploads,
        private readonly AuthSession $session,
        private readonly CsrfGuard $csrf,
    ) {
    }

    public function index(Request $request): Response
    {
        $this->assertAuthenticated();
        $filters = $request->query();
        $game = trim((string) ($filters['game'] ?? ''));

        if ($game !== '' && !$this->catalog->hasGame($game)) {
            throw new HttpException('O filtro de jogo informado é inválido.', 422, 'invalid_game');
        }

        if ($game !== '') {
            $filters['game'] = $this->catalog->normalizeGame($game);
        }

        $result = $this->cards->paginate($filters);
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($filters['per_page'] ?? 10)));
        $totalPages = max(1, (int) ceil($result['total'] / $perPage));

        return Response::json(
            ['items' => array_map(fn (array $card): array => $this->present($card), $result['items'])],
            200,
            [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $result['total'],
                'total_pages' => $totalPages,
                'sort' => (string) ($filters['sort'] ?? 'updated'),
                'direction' => strtoupper((string) ($filters['direction'] ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC',
                'selected' => isset($filters['selected']) && is_scalar($filters['selected']) ? (int) $filters['selected'] : null,
            ],
        );
    }

    public function show(Request $request, array $params): Response
    {
        $this->assertAuthenticated();
        $card = $this->findCard($params);

        return Response::json(['item' => $this->present($card)]);
    }

    public function store(Request $request): Response
    {
        $this->assertAuthenticated();
        $this->csrf->assert($request);
        $input = $request->body();
        $file = $request->file('image');
        $input['has_upload'] = $this->hasUpload($file);
        $card = $this->validator->validate($input);
        $this->assertNameAvailable((string) $card['name_en']);
        $storedImage = null;

        try {
            if ($card['image_source'] === 'upload') {
                $storedImage = $this->uploads->store($file ?? []);
                $card['image_value'] = $storedImage;
            }

            $id = $this->cards->create($card);
        } catch (\Throwable $exception) {
            if ($storedImage !== null) {
                $this->uploads->delete($storedImage);
            }

            $this->throwDuplicateNameError($exception);
            throw $exception;
        }

        $created = $this->cards->find($id);

        return Response::json(['item' => $this->present($created ?? $card)], 201);
    }

    public function update(Request $request, array $params): Response
    {
        $this->assertAuthenticated();
        $this->csrf->assert($request);
        $id = $this->idFromParams($params);
        $current = $this->cards->find($id);

        if ($current === null) {
            throw new HttpException('Carta não encontrada.', 404, 'card_not_found');
        }

        $input = $request->body();
        $file = $request->file('image');
        $hasUpload = $this->hasUpload($file);
        $input['has_upload'] = $hasUpload;
        $card = $this->validator->validate($input, $current);
        $this->assertNameAvailable((string) $card['name_en'], $id);
        $storedImage = null;

        if ((string) ($input['clear_image'] ?? '') === '1') {
            $card['image_source'] = null;
            $card['image_value'] = null;
        }

        if ($card['image_source'] === 'upload' && !$hasUpload && !$card['image_value'] && $current['image_source'] === 'upload' && $current['image_value'] !== null) {
            $card['image_value'] = $current['image_value'];
        }

        try {
            if ($card['image_source'] === 'upload' && $hasUpload) {
                $storedImage = $this->uploads->store($file ?? []);
                $card['image_value'] = $storedImage;
            }

            $this->cards->update($id, $card);
        } catch (\Throwable $exception) {
            if ($storedImage !== null) {
                $this->uploads->delete($storedImage);
            }

            $this->throwDuplicateNameError($exception);
            throw $exception;
        }

        if ($storedImage !== null && $current['image_value'] !== $storedImage) {
            $this->uploads->delete((string) $current['image_value']);
        } elseif (($card['image_source'] === 'url' || $card['image_source'] === null) && $current['image_source'] === 'upload') {
            $this->uploads->delete((string) $current['image_value']);
        }

        $updated = $this->cards->find($id);

        return Response::json(['item' => $this->present($updated ?? $card)]);
    }

    public function destroy(Request $request, array $params): Response
    {
        $this->assertAuthenticated();
        $this->csrf->assert($request);
        $id = $this->idFromParams($params);
        $card = $this->cards->find($id);

        if ($card === null) {
            throw new HttpException('Carta não encontrada.', 404, 'card_not_found');
        }

        if (!$this->cards->delete($id)) {
            throw new HttpException('Não foi possível excluir a carta.', 409, 'delete_failed');
        }

        $this->uploads->delete((string) $card['image_value']);

        return Response::json(['deleted' => true, 'id' => $id]);
    }

    private function assertAuthenticated(): void
    {
        if ($this->session->userId() === null) {
            throw new HttpException('Sua sessão expirou. Entre novamente para continuar.', 401, 'auth_required');
        }
    }

    private function assertNameAvailable(string $name, ?int $exceptId = null): void
    {
        if ($this->cards->nameExists($name, $exceptId)) {
            throw new HttpException('Já existe uma carta com esse nome.', 422, 'validation_error', [
                'name_en' => 'Já existe uma carta com esse nome.',
            ]);
        }
    }

    private function throwDuplicateNameError(\Throwable $exception): void
    {
        if ($exception instanceof \PDOException && (int) ($exception->errorInfo[1] ?? 0) === 1062) {
            throw new HttpException('Já existe uma carta com esse nome.', 422, 'validation_error', [
                'name_en' => 'Já existe uma carta com esse nome.',
            ]);
        }
    }

    /** @param array<string, string> $params @return array<string, mixed> */
    private function findCard(array $params): array
    {
        $card = $this->cards->find($this->idFromParams($params));

        if ($card === null) {
            throw new HttpException('Carta não encontrada.', 404, 'card_not_found');
        }

        return $card;
    }

    /** @param array<string, string> $params */
    private function idFromParams(array $params): int
    {
        $id = filter_var($params['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($id === false || $id === null) {
            throw new HttpException('Identificador de carta inválido.', 400, 'invalid_id');
        }

        return (int) $id;
    }

    /** @param array<string, mixed>|null $file */
    private function hasUpload(?array $file): bool
    {
        return is_array($file) && (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    }

    /** @param array<string, mixed> $card @return array<string, mixed> */
    private function present(array $card): array
    {
        $imageValue = (string) ($card['image_value'] ?? '');
        $isRemoteImage = preg_match('/\Ahttps?:\/\//i', $imageValue) === 1;
        $imageUrl = $imageValue === ''
            ? '/assets/images/card-fallback.svg'
            : ($isRemoteImage ? $imageValue : '/' . ltrim($imageValue, '/'));

        return [
            'id' => (int) $card['id'],
            'name_en' => (string) $card['name_en'],
            'name_pt' => $card['name_pt'] !== null ? (string) $card['name_pt'] : null,
            'game' => (string) $card['game'],
            'game_label' => $this->catalog->gameLabel((string) $card['game']),
            'edition_id' => (string) $card['edition_id'],
            'edition_label' => $this->catalog->editionLabel((string) $card['game'], (string) $card['edition_id']),
            'rarity' => (string) $card['rarity'],
            'image_source' => $card['image_source'] !== null ? (string) $card['image_source'] : null,
            'image_value' => $imageValue !== '' ? $imageValue : null,
            'image_url' => $imageUrl,
            'created_at' => (string) $card['created_at'],
            'updated_at' => (string) $card['updated_at'],
        ];
    }
}
