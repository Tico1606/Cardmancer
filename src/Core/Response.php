<?php

declare(strict_types=1);

namespace Cardmancer\Core;

final class Response
{
    /** @param array<string, string> $headers */
    private function __construct(
        private readonly int $status,
        private readonly array $headers,
        private readonly string $body,
    ) {
    }

    public static function html(string $body, int $status = 200): self
    {
        return new self($status, ['Content-Type' => 'text/html; charset=UTF-8'], $body);
    }

    /** @param array<string, mixed> $data @param array<string, mixed> $meta */
    public static function json(array $data = [], int $status = 200, array $meta = []): self
    {
        $payload = [
            'data' => $data,
            'error' => null,
            'meta' => $meta,
        ];

        return new self(
            $status,
            ['Content-Type' => 'application/json; charset=UTF-8', 'Cache-Control' => 'no-store'],
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
        );
    }

    /** @param array<string, string> $headers */
    public static function error(HttpException $exception): self
    {
        $error = [
            'code' => $exception->errorCode(),
            'message' => $exception->getMessage(),
        ];

        if ($exception->fields() !== []) {
            $error['fields'] = $exception->fields();
        }

        $payload = ['data' => null, 'error' => $error, 'meta' => []];

        return new self(
            $exception->status(),
            ['Content-Type' => 'application/json; charset=UTF-8', 'Cache-Control' => 'no-store'],
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
        );
    }

    public static function redirect(string $location, int $status = 302): self
    {
        return new self($status, ['Location' => $location], '');
    }

    public function send(): void
    {
        http_response_code($this->status);

        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }

        echo $this->body;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function body(): string
    {
        return $this->body;
    }
}
