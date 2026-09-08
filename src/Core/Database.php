<?php

declare(strict_types=1);

namespace Cardmancer\Core;

use PDO;
use PDOException;

final class Database
{
    private ?PDO $pdo = null;

    public function __construct(private readonly Config $config)
    {
    }

    public function connection(): PDO
    {
        if ($this->pdo instanceof PDO) {
            return $this->pdo;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $this->config->get('db_host'),
            $this->config->get('db_port'),
            $this->config->get('db_name'),
        );

        $lastException = null;

        for ($attempt = 0; $attempt < 15; $attempt++) {
            try {
                $this->pdo = new PDO($dsn, (string) $this->config->get('db_user'), (string) $this->config->get('db_password'), [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);

                return $this->pdo;
            } catch (PDOException $exception) {
                $lastException = $exception;
                usleep(500000);
            }
        }

        throw new \RuntimeException('Não foi possível conectar ao banco de dados.', 0, $lastException);
    }

    public function transaction(callable $callback): mixed
    {
        $connection = $this->connection();
        $connection->beginTransaction();

        try {
            $result = $callback($connection);
            $connection->commit();

            return $result;
        } catch (\Throwable $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            throw $exception;
        }
    }
}
