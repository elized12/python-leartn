<?php

namespace App\Service\Course\Block\Validation;

class Image
{
    private const ALLOWED_WIDTHS = ['100%', '80%', '60%', '40%'];
    private const ALLOWED_ALIGNMENTS = ['left', 'center', 'right'];

    public function validate(string $block): array
    {
        $errors = [];
        $blockArray = json_decode($block, true);

        if (!$blockArray || !is_array($blockArray)) {
            return ['Неверный формат данных изображения'];
        }

        if (empty($blockArray['src'])) {
            $errors[] = 'Загрузите изображение';
        } elseif (mb_strlen($blockArray['src']) > 500) {
            $errors[] = 'Ссылка на изображение слишком длинная';
        }

        if (isset($blockArray['alt']) && mb_strlen($blockArray['alt']) > 160) {
            $errors[] = 'Alt-текст не должен превышать 160 символов';
        }

        if (isset($blockArray['caption']) && mb_strlen($blockArray['caption']) > 255) {
            $errors[] = 'Подпись не должна превышать 255 символов';
        }

        if (isset($blockArray['width']) && !in_array($blockArray['width'], self::ALLOWED_WIDTHS, true)) {
            $errors[] = 'Недопустимая ширина изображения';
        }

        if (isset($blockArray['align']) && !in_array($blockArray['align'], self::ALLOWED_ALIGNMENTS, true)) {
            $errors[] = 'Недопустимое выравнивание изображения';
        }

        return $errors;
    }
}
