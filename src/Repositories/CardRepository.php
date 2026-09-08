<?php

declare(strict_types=1);

namespace Cardmancer\Repositories;

use PDO;

final class CardRepository
{
    private const SORT_COLUMNS = [
        'name' => 'name_en',
        'name_en' => 'name_en',
        'game' => 'game',
        'edition' => 'edition_id',
        'edition_id' => 'edition_id',
        'rarity' => 'rarity',
        'updated' => 'updated_at',
        'updated_at' => 'updated_at',
    ];

    public function __construct(private readonly PDO $connection)
    {
    }

    public function ensureUniqueNameConstraint(): void
    {
        $statement = $this->connection->query("SHOW INDEX FROM cards WHERE Key_name = 'cards_name_en_unique'");

        if ($statement !== false && $statement->fetch(PDO::FETCH_ASSOC) === false) {
            try {
                $this->connection->exec('ALTER TABLE cards ADD UNIQUE KEY cards_name_en_unique (name_en)');
            } catch (\PDOException $exception) {
                // Keep legacy databases with pre-existing duplicate names available.
                if ((int) ($exception->errorInfo[1] ?? 0) !== 1062) {
                    throw $exception;
                }
            }
        }
    }

    public function nameExists(string $name, ?int $exceptId = null): bool
    {
        $sql = 'SELECT 1 FROM cards WHERE name_en = :name';
        $parameters = ['name' => $name];

        if ($exceptId !== null) {
            $sql .= ' AND id <> :except_id';
            $parameters['except_id'] = $exceptId;
        }

        $sql .= ' LIMIT 1';
        $statement = $this->connection->prepare($sql);
        $statement->execute($parameters);

        return $statement->fetchColumn() !== false;
    }

    /** @param array<string, mixed> $filters @return array{items: list<array<string, mixed>>, total: int} */
    public function paginate(array $filters): array
    {
        $where = [];
        $parameters = [];

        $query = trim((string) ($filters['q'] ?? ''));

        if ($query !== '') {
            $where[] = '(name_en LIKE :query_en OR name_pt LIKE :query_pt)';
            $parameters['query_en'] = '%' . $query . '%';
            $parameters['query_pt'] = '%' . $query . '%';
        }

        $game = trim((string) ($filters['game'] ?? ''));

        if ($game !== '') {
            $where[] = 'game = :game';
            $parameters['game'] = $game;
        }

        $whereSql = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);
        $countStatement = $this->connection->prepare('SELECT COUNT(*) FROM cards' . $whereSql);
        $countStatement->execute($parameters);
        $total = (int) $countStatement->fetchColumn();

        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($filters['per_page'] ?? 10)));
        $offset = ($page - 1) * $perPage;
        $sort = self::SORT_COLUMNS[(string) ($filters['sort'] ?? 'updated')] ?? self::SORT_COLUMNS['updated'];
        $direction = strtoupper((string) ($filters['direction'] ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

        $statement = $this->connection->prepare(
            'SELECT id, name_en, name_pt, game, edition_id, rarity, image_source, image_value, created_at, updated_at FROM cards'
            . $whereSql
            . ' ORDER BY ' . $sort . ' ' . $direction . ', id DESC LIMIT :limit OFFSET :offset',
        );

        foreach ($parameters as $key => $value) {
            $statement->bindValue(':' . $key, $value);
        }

        $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();
        $items = $statement->fetchAll();

        return ['items' => is_array($items) ? $items : [], 'total' => $total];
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        $statement = $this->connection->prepare('SELECT id, name_en, name_pt, game, edition_id, rarity, image_source, image_value, created_at, updated_at FROM cards WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $card = $statement->fetch();

        return is_array($card) ? $card : null;
    }

    /** @param array<string, mixed> $card */
    public function create(array $card): int
    {
        $statement = $this->connection->prepare(
            'INSERT INTO cards (name_en, name_pt, game, edition_id, rarity, image_source, image_value)'
            . ' VALUES (:name_en, :name_pt, :game, :edition_id, :rarity, :image_source, :image_value)',
        );
        $statement->execute([
            'name_en' => $card['name_en'],
            'name_pt' => $card['name_pt'],
            'game' => $card['game'],
            'edition_id' => $card['edition_id'],
            'rarity' => $card['rarity'],
            'image_source' => $card['image_source'],
            'image_value' => $card['image_value'],
        ]);

        return (int) $this->connection->lastInsertId();
    }

    /** @param array<string, mixed> $card */
    public function update(int $id, array $card): bool
    {
        $statement = $this->connection->prepare(
            'UPDATE cards SET name_en = :name_en, name_pt = :name_pt, game = :game, edition_id = :edition_id,'
            . ' rarity = :rarity, image_source = :image_source, image_value = :image_value, updated_at = CURRENT_TIMESTAMP'
            . ' WHERE id = :id',
        );

        $statement->execute([
            'id' => $id,
            'name_en' => $card['name_en'],
            'name_pt' => $card['name_pt'],
            'game' => $card['game'],
            'edition_id' => $card['edition_id'],
            'rarity' => $card['rarity'],
            'image_source' => $card['image_source'],
            'image_value' => $card['image_value'],
        ]);

        return $statement->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $statement = $this->connection->prepare('DELETE FROM cards WHERE id = :id');
        $statement->execute(['id' => $id]);

        return $statement->rowCount() > 0;
    }

}
