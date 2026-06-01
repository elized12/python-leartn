<?php

namespace App\Service\Course\Block\Validation;

class Video
{
    private const ALLOWED_PLATFORMS = ['youtube', 'vimeo', 'upload'];
    private const ALLOWED_WIDTHS = ['100%', '80%', '60%'];
    private const ALLOWED_RATIOS = ['16:9', '4:3', '1:1', '21:9'];


    public function validate(string $block): array
    {
        $errors = [];
        $blockArray = json_decode($block, true);

        if (!$blockArray || !is_array($blockArray)) {
            $errors[] = 'Неверный формат JSON данных';
            return $errors;
        }

        $platform = $blockArray['platform'] ?? 'youtube';

        if (!in_array($platform, $this::ALLOWED_PLATFORMS, true)) {
            $platforms = implode(', ', $this::ALLOWED_PLATFORMS);
            $errors[] = "Неподдерживаемая платформа видео. Доступные платформы: {$platforms}";
        }

        if (empty($blockArray['url'])) {
            $errors[] = 'Добавьте ссылку или загрузите видеофайл';
        } elseif (mb_strlen($blockArray['url']) > 500) {
            $errors[] = 'Ссылка на видео слишком длинная';
        } elseif ($platform !== 'upload') {
            try {
                if (!$this->validateUrl($blockArray['url'], $platform)) {
                    $errors[] = "Неверный формат ссылки для платформы {$platform}";
                }
            } catch (\ValueError $e) {
                $errors[] = "Ошибка определения платформы видео";
            }
        }

        if (isset($blockArray['width']) && !in_array($blockArray['width'], $this::ALLOWED_WIDTHS, true)) {
            $widths = implode(', ', $this::ALLOWED_WIDTHS);
            $errors[] = "Недопустимое значение ширины. Допустимые значения: {$widths}";
        }

        if (isset($blockArray['aspectRatio']) && !in_array($blockArray['aspectRatio'], $this::ALLOWED_RATIOS, true)) {
            $ratios = implode(', ', $this::ALLOWED_RATIOS);
            $errors[] = "Недопустимое соотношение сторон. Допустимые значения: {$ratios}";
        }

        return $errors;
    }

    private function validateUrl(string $url, string $platform): bool
    {
        $cleanUrl = trim($url);

        if (empty($cleanUrl)) {
            return false;
        }

        return match ($platform) {
            'youtube' => preg_match(
                '/^(https?:\/\/)?(www\.)?(youtube\.com|youtu\.be)\/(watch\?v=|embed\/|v\/|shorts\/)?([a-zA-Z0-9_-]{11})(\S*)?$/',
                $cleanUrl
            ) === 1,
            'vimeo' => preg_match(
                '/^(https?:\/\/)?(www\.)?vimeo\.com\/(\d+)(\S*)?$/',
                $cleanUrl
            ) === 1,
            default => false,
        };
    }
}
