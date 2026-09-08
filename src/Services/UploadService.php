<?php

declare(strict_types=1);

namespace Cardmancer\Services;

use Cardmancer\Core\Config;
use Cardmancer\Core\HttpException;

final class UploadService
{
    private const MIME_EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public function __construct(private readonly Config $config)
    {
    }

    /** @param array<string, mixed> $file */
    public function store(array $file): string
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($error === UPLOAD_ERR_NO_FILE) {
            throw new HttpException('Escolha uma imagem para continuar.', 422, 'validation_error', ['image' => 'A imagem é obrigatória quando o envio por arquivo está ativo.']);
        }

        if ($error !== UPLOAD_ERR_OK) {
            throw new HttpException('Não foi possível receber a imagem.', 422, 'upload_error', ['image' => 'O envio foi interrompido. Tente novamente.']);
        }

        $size = (int) ($file['size'] ?? 0);
        $maxBytes = (int) $this->config->get('upload_max_bytes', 5 * 1024 * 1024);

        if ($size <= 0 || $size > $maxBytes) {
            throw new HttpException('A imagem ultrapassa o limite permitido.', 422, 'validation_error', ['image' => 'Use uma imagem JPG, PNG ou WebP de até 5 MB.']);
        }

        $temporaryPath = (string) ($file['tmp_name'] ?? '');

        if (!is_uploaded_file($temporaryPath) && !($this->config->get('app_env') === 'testing' && is_file($temporaryPath))) {
            throw new HttpException('O arquivo enviado não é válido.', 422, 'upload_error', ['image' => 'Envie o arquivo novamente.']);
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($temporaryPath);

        if (!is_string($mime) || !isset(self::MIME_EXTENSIONS[$mime]) || @getimagesize($temporaryPath) === false) {
            throw new HttpException('Formato de imagem não permitido.', 422, 'validation_error', ['image' => 'Use somente JPG, PNG ou WebP.']);
        }

        $directory = (string) $this->config->get('upload_dir');

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException('Não foi possível preparar o diretório de uploads.');
        }

        $filename = bin2hex(random_bytes(16)) . '.' . self::MIME_EXTENSIONS[$mime];
        $destination = $directory . DIRECTORY_SEPARATOR . $filename;

        if (!move_uploaded_file($temporaryPath, $destination)) {
            if ($this->config->get('app_env') === 'testing' && @rename($temporaryPath, $destination)) {
                return 'uploads/cards/' . $filename;
            }

            throw new \RuntimeException('Não foi possível salvar a imagem.');
        }

        return 'uploads/cards/' . $filename;
    }

    public function delete(?string $imageValue): void
    {
        if (!$this->isManagedPath($imageValue)) {
            return;
        }

        $path = rtrim((string) $this->config->get('base_path'), '/\\') . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $imageValue);

        if (is_file($path)) {
            @unlink($path);
        }
    }

    public function isManagedPath(?string $imageValue): bool
    {
        return is_string($imageValue)
            && str_starts_with($imageValue, 'uploads/cards/')
            && !str_contains($imageValue, '..')
            && preg_match('/\Auploads\/cards\/[a-zA-Z0-9_-]+\.(jpg|png|webp)\z/', $imageValue) === 1;
    }
}
