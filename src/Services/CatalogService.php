<?php

declare(strict_types=1);

namespace Cardmancer\Services;

use Cardmancer\Core\Config;

final class CatalogService
{
    /** @var array<string, array<string, mixed>> */
    private array $editions;

    /** @var array<string, list<string>> */
    private array $rarities;

    /** @var array<string, list<array{id: string, name: string}>> */
    private array $remoteEditions = [];

    public function __construct(Config $config)
    {
        $editions = json_decode((string) file_get_contents($config->basePath() . '/data/editions.json'), true);
        $rarities = json_decode((string) file_get_contents($config->basePath() . '/data/rarities.json'), true);
        $this->editions = is_array($editions) ? $editions : [];
        $this->rarities = is_array($rarities) ? $rarities : [];
    }

    /** @return list<string> */
    public function games(): array
    {
        return array_keys($this->editions);
    }

    public function hasGame(string $game): bool
    {
        $game = $this->normalizeGame($game);

        return array_key_exists($game, $this->editions) && array_key_exists($game, $this->rarities);
    }

    public function normalizeGame(string $game): string
    {
        $game = strtolower(trim($game));

        return match ($game) {
            'yu-gi-oh', 'yu-gi-oh!', 'yugioh' => 'yugioh',
            default => $game,
        };
    }

    /** @return array{game: string, label: string, editions: list<array{id: string, name: string}>, rarities: list<string>} */
    public function options(string $game): array
    {
        $game = $this->normalizeGame($game);

        if (!$this->hasGame($game)) {
            throw new \InvalidArgumentException('Jogo inválido.');
        }

        $editions = $this->externalEditions($game);

        return [
            'game' => $game,
            'label' => (string) $this->editions[$game]['label'],
            'editions' => $this->mergeEditions($editions, $this->editions[$game]['editions']),
            'rarities' => $this->rarities[$game],
        ];
    }

    public function isValidEdition(string $game, string $edition): bool
    {
        foreach ($this->options($game)['editions'] as $item) {
            if ($item['id'] === $edition) {
                return true;
            }
        }

        return false;
    }

    public function isValidRarity(string $game, string $rarity): bool
    {
        return in_array($rarity, $this->options($game)['rarities'], true);
    }

    public function gameLabel(string $game): string
    {
        $game = $this->normalizeGame($game);

        return $this->hasGame($game) ? (string) $this->editions[$game]['label'] : $game;
    }

    public function editionLabel(string $game, string $edition): string
    {
        $game = $this->normalizeGame($game);

        if (!$this->hasGame($game)) {
            return $edition;
        }

        foreach ($this->editions[$game]['editions'] as $item) {
            if ($item['id'] === $edition) {
                return $item['name'];
            }
        }

        foreach ($this->options($game)['editions'] as $item) {
            if ($item['id'] === $edition) {
                return $item['name'];
            }
        }

        return $edition;
    }

    /** @return list<array{id: string, name: string}> */
    private function externalEditions(string $game): array
    {
        if (array_key_exists($game, $this->remoteEditions)) {
            return $this->remoteEditions[$game];
        }

        $editions = match ($game) {
            'magic' => $this->fetchMagicEditions(),
            'pokemon' => $this->fetchPokemonEditions(),
            'yugioh' => $this->fetchYuGiOhEditions(),
            default => [],
        };

        $this->remoteEditions[$game] = $editions;

        return $editions;
    }

    /** @return list<array{id: string, name: string}> */
    private function fetchMagicEditions(): array
    {
        $payload = $this->fetchJson('https://api.scryfall.com/sets');

        return $this->normalizeRemoteEditions(is_array($payload) ? ($payload['data'] ?? []) : [], 'code', 'name');
    }

    /** @return list<array{id: string, name: string}> */
    private function fetchPokemonEditions(): array
    {
        $payload = $this->fetchJson('https://api.pokemontcg.io/v2/sets');

        return $this->normalizeRemoteEditions(is_array($payload) ? ($payload['data'] ?? []) : [], 'id', 'name');
    }

    /** @return list<array{id: string, name: string}> */
    private function fetchYuGiOhEditions(): array
    {
        $payload = $this->fetchJson('https://db.ygoprodeck.com/api/v7/cardsets.php');

        return $this->normalizeRemoteEditions($payload ?? [], 'set_code', 'set_name');
    }

    /** @return array<string, mixed>|null */
    private function fetchJson(string $url): ?array
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 8,
                'ignore_errors' => true,
                'header' => "Accept: application/json\r\nUser-Agent: Cardmancer/1.0\r\n",
            ],
        ]);
        $body = @file_get_contents($url, false, $context);

        if ($body === false) {
            return null;
        }

        $payload = json_decode($body, true);

        return is_array($payload) ? $payload : null;
    }

    /** @param array<int, mixed> $items @return list<array{id: string, name: string}> */
    private function normalizeRemoteEditions(array $items, string $idKey, string $nameKey): array
    {
        $editions = [];
        $used = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $name = trim((string) ($item[$nameKey] ?? ''));
            $id = strtolower(trim((string) ($item[$idKey] ?? '')));

            if ($name === '') {
                continue;
            }

            $id = $id !== '' ? $id : $this->slug($name);

            if (isset($used[$id])) {
                if ($used[$id] === $name) {
                    continue;
                }

                $id .= '-' . $this->slug($name);
            }

            $baseId = $id;
            $suffix = 2;

            while (isset($used[$id])) {
                $id = $baseId . '-' . $suffix;
                $suffix++;
            }

            $used[$id] = $name;
            $editions[] = ['id' => $id, 'name' => $name];
        }

        return $editions;
    }

    /** @param list<array{id: string, name: string}> $remote @param list<array{id: string, name: string}> $fallback @return list<array{id: string, name: string}> */
    private function mergeEditions(array $remote, array $fallback): array
    {
        $editions = [];

        foreach ([...$remote, ...$fallback] as $edition) {
            $id = trim((string) ($edition['id'] ?? ''));
            $name = trim((string) ($edition['name'] ?? ''));

            if ($id !== '' && $name !== '' && !isset($editions[$id])) {
                $editions[$id] = ['id' => $id, 'name' => $name];
            }
        }

        return array_values($editions);
    }

    private function slug(string $value): string
    {
        $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($value)) ?? '';

        return trim($slug, '-') ?: 'collection';
    }
}
