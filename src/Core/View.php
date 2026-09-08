<?php

declare(strict_types=1);

namespace Cardmancer\Core;

final class View
{
    public function __construct(private readonly Config $config)
    {
    }

    /** @param array<string, mixed> $data */
    public function render(string $template, array $data = []): string
    {
        $path = $this->config->basePath() . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $template) . '.php';

        if (!is_file($path)) {
            throw new \RuntimeException('View não encontrada: ' . $template);
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $path;

        return (string) ob_get_clean();
    }
}
