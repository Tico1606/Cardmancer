<?php

declare(strict_types=1);

namespace Cardmancer\Validation;

use Cardmancer\Core\HttpException;
use Cardmancer\Services\CatalogService;

final class CardValidator
{
    public function __construct(private readonly CatalogService $catalog)
    {
    }

    /** @param array<string, mixed> $input @return array<string, ?string> */
    public function validate(array $input, ?array $current = null): array
    {
        $errors = [];
        $nameEn = trim((string) ($input['name_en'] ?? ''));
        $namePtValue = trim((string) ($input['name_pt'] ?? ''));
        $namePt = $namePtValue === '' ? null : $namePtValue;
        $game = $this->catalog->normalizeGame((string) ($input['game'] ?? ''));
        $edition = trim((string) ($input['edition_id'] ?? ''));
        $rarity = trim((string) ($input['rarity'] ?? ''));

        if ($nameEn === '') {
            $errors['name_en'] = 'Informe o nome em inglês.';
        } elseif ($this->length($nameEn) > 180) {
            $errors['name_en'] = 'Use no máximo 180 caracteres.';
        }

        if ($namePt !== null && $this->length($namePt) > 180) {
            $errors['name_pt'] = 'Use no máximo 180 caracteres.';
        }

        if (!$this->catalog->hasGame($game)) {
            $errors['game'] = 'Escolha um jogo válido.';
        } else {
            if (!$this->catalog->isValidEdition($game, $edition)) {
                $errors['edition_id'] = 'A coleção não pertence ao jogo escolhido.';
            }

            if (!$this->catalog->isValidRarity($game, $rarity)) {
                $errors['rarity'] = 'A raridade não pertence ao jogo escolhido.';
            }
        }

        $imageSource = trim((string) ($input['image_source'] ?? ''));
        $imageValue = trim((string) ($input['image_value'] ?? ''));

        if ($imageSource !== '' && !in_array($imageSource, ['upload', 'url'], true)) {
            $errors['image_source'] = 'Escolha arquivo ou URL.';
        }

        $clearImage = (string) ($input['clear_image'] ?? '') === '1';

        if ($imageSource === 'url' && !$clearImage) {
            $imageValue = preg_replace_callback('/\Ahttps?:\/\//i', static fn (array $match): string => strtolower($match[0]), $imageValue) ?? $imageValue;

            if ($imageValue === '') {
                $errors['image_value'] = 'Informe a URL da imagem.';
            } elseif ($this->length($imageValue) > 500 || !$this->isHttpUrl($imageValue)) {
                $errors['image_value'] = 'Informe uma URL HTTP ou HTTPS válida.';
            }
        }

        if ($imageSource === 'upload' && !$clearImage && (($input['has_upload'] ?? false) !== true) && ($current === null || ($current['image_source'] ?? null) !== 'upload')) {
            $errors['image'] = 'Escolha uma imagem para continuar.';
        }

        if ($errors !== []) {
            throw new HttpException('Revise os campos destacados.', 422, 'validation_error', $errors);
        }

        return [
            'name_en' => $nameEn,
            'name_pt' => $namePt,
            'game' => $game,
            'edition_id' => $edition,
            'rarity' => $rarity,
            'image_source' => $imageSource === '' ? ($current['image_source'] ?? null) : $imageSource,
            'image_value' => $imageSource === '' ? ($current['image_value'] ?? null) : ($imageSource === 'url' ? $imageValue : null),
        ];
    }

    private function length(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
    }

    private function isHttpUrl(string $value): bool
    {
        $parts = parse_url($value);

        return is_array($parts)
            && in_array(strtolower((string) ($parts['scheme'] ?? '')), ['http', 'https'], true)
            && filter_var($value, FILTER_VALIDATE_URL) !== false;
    }
}
