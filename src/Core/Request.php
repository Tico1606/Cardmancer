<?php

declare(strict_types=1);

namespace Cardmancer\Core;

final class Request
{
    /** @var array<string, mixed>|null */
    private ?array $body = null;

    public function __construct(
        private readonly string $method,
        private readonly string $uri,
        /** @var array<string, mixed> */
        private readonly array $query,
        /** @var array<string, mixed> */
        private readonly array $post,
        /** @var array<string, mixed> */
        private readonly array $files,
        private readonly string $rawBody = '',
        /** @var array<string, string> */
        private readonly array $headers = [],
    ) {
    }

    public static function capture(): self
    {
        $headers = [];

        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $header = str_replace('_', '-', strtolower(substr($key, 5)));
                $headers[$header] = (string) $value;
            }
        }

        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['content-type'] = (string) $_SERVER['CONTENT_TYPE'];
        }

        return new self(
            strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')),
            (string) ($_SERVER['REQUEST_URI'] ?? '/'),
            $_GET,
            $_POST,
            $_FILES,
            file_get_contents('php://input') ?: '',
            $headers,
        );
    }

    public function method(): string
    {
        $override = $this->post['_method'] ?? null;

        if ($this->method === 'POST' && is_string($override) && in_array(strtoupper($override), ['PUT', 'PATCH', 'DELETE'], true)) {
            return strtoupper($override);
        }

        return $this->method;
    }

    public function path(): string
    {
        $path = parse_url($this->uri, PHP_URL_PATH);

        return is_string($path) && $path !== '' ? '/' . trim($path, '/') : '/';
    }

    /** @return array<string, mixed> */
    public function query(): array
    {
        return $this->query;
    }

    public function queryValue(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    /** @return array<string, mixed> */
    public function body(): array
    {
        if ($this->body !== null) {
            return $this->body;
        }

        $contentType = strtolower($this->header('content-type', ''));

        if (str_contains($contentType, 'application/json') && $this->rawBody !== '') {
            $decoded = json_decode($this->rawBody, true);
            $this->body = is_array($decoded) ? $decoded : [];
        } else {
            $this->body = $this->post;
        }

        return $this->body;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body()[$key] ?? $default;
    }

    public function file(string $key): ?array
    {
        $file = $this->files[$key] ?? null;

        return is_array($file) ? $file : null;
    }

    public function header(string $name, ?string $default = null): ?string
    {
        return $this->headers[strtolower($name)] ?? $default;
    }

    public function isApi(): bool
    {
        return str_starts_with($this->path(), '/api/');
    }
}
