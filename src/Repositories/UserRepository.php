<?php

declare(strict_types=1);

namespace Cardmancer\Repositories;

use PDO;

final class UserRepository
{
    public function __construct(private readonly PDO $connection)
    {
    }

    /** @return array<string, mixed>|null */
    public function findByEmail(string $email): ?array
    {
        $statement = $this->connection->prepare('SELECT id, name, email, password_hash FROM users WHERE email = :email LIMIT 1');
        $statement->execute(['email' => strtolower(trim($email))]);
        $user = $statement->fetch();

        return is_array($user) ? $user : null;
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        $statement = $this->connection->prepare('SELECT id, name, email FROM users WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $user = $statement->fetch();

        return is_array($user) ? $user : null;
    }

    public function updateProfile(int $id, string $name, string $email, ?string $passwordHash = null): bool
    {
        $parameters = [
            'id' => $id,
            'name' => $name,
            'email' => $email,
        ];
        $sql = 'UPDATE users SET name = :name, email = :email';

        if ($passwordHash !== null) {
            $sql .= ', password_hash = :password_hash';
            $parameters['password_hash'] = $passwordHash;
        }

        $statement = $this->connection->prepare($sql . ', updated_at = CURRENT_TIMESTAMP WHERE id = :id');
        $statement->execute($parameters);

        return $statement->rowCount() > 0;
    }

    public function ensureSeedCredentials(): void
    {
        $user = $this->findByEmail('admin@indigoplateau.local');

        if ($user === null) {
            $statement = $this->connection->prepare('INSERT INTO users (name, email, password_hash) VALUES (:name, :email, :password_hash)');
            $statement->execute([
                'name' => 'Administração',
                'email' => 'admin@indigoplateau.local',
                'password_hash' => password_hash('Indigo@123', PASSWORD_DEFAULT),
            ]);

            return;
        }

        if ((string) $user['name'] !== 'Administração') {
            $statement = $this->connection->prepare('UPDATE users SET name = :name WHERE id = :id');
            $statement->execute([
                'id' => (int) $user['id'],
                'name' => 'Administração',
            ]);
        }

        if ((string) $user['password_hash'] !== '__CARDMANCER_SEED_PASSWORD__') {
            return;
        }

        $statement = $this->connection->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :id');
        $statement->execute([
            'id' => (int) $user['id'],
            'password_hash' => password_hash('Indigo@123', PASSWORD_DEFAULT),
        ]);
    }
}
