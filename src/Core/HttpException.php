<?php

declare(strict_types=1);

namespace Cardmancer\Core;

use RuntimeException;

final class HttpException extends RuntimeException
{
    /** @param array<string, string> $fields */
    public function __construct(
        string $message,
        private readonly int $status = 400,
        private readonly string $errorCode = 'request_error',
        private readonly array $fields = [],
    ) {
        parent::__construct($message);
    }

    public function status(): int
    {
        return $this->status;
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    /** @return array<string, string> */
    public function fields(): array
    {
        return $this->fields;
    }
}
