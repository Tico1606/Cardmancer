<?php

declare(strict_types=1);

namespace Cardmancer\Core;

final class Router
{
    /** @var list<array{method: string, pattern: string, regex: string, handler: callable}> */
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    public function put(string $path, callable $handler): void
    {
        $this->add('PUT', $path, $handler);
    }

    public function patch(string $path, callable $handler): void
    {
        $this->add('PATCH', $path, $handler);
    }

    public function delete(string $path, callable $handler): void
    {
        $this->add('DELETE', $path, $handler);
    }

    public function dispatch(Request $request): Response
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->method()) {
                continue;
            }

            if (preg_match($route['regex'], $request->path(), $matches) !== 1) {
                continue;
            }

            $params = [];

            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = $value;
                }
            }

            return ($route['handler'])($request, $params);
        }

        throw new HttpException('Rota não encontrada.', 404, 'not_found');
    }

    private function add(string $method, string $path, callable $handler): void
    {
        $normalized = '/' . trim($path, '/');
        $regex = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
            static fn (array $match): string => '(?P<' . $match[1] . '>[^/]+)',
            $normalized,
        );

        $this->routes[] = [
            'method' => $method,
            'pattern' => $normalized,
            'regex' => '#^' . $regex . '/?$#',
            'handler' => $handler,
        ];
    }
}
