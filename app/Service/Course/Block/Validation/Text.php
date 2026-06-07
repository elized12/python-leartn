<?php

namespace App\Service\Course\Block\Validation;

class Text implements BlockValidatorInterface
{
    public function validate(string $block): array
    {
        $errors = [];
        $blockArray = json_decode($block, true);

        if (!$blockArray) {
            return ['Неверный формат данных'];
        }

        if (empty($blockArray['content']) && empty($blockArray['markdown'])) {
            $errors[] = 'Текст не может быть пустым';
        } elseif (isset($blockArray['content']) && mb_strlen($blockArray['content']) > 10000) {
            $errors[] = 'Текст слишком длинный (макс. 10000 символов)';
        } elseif (isset($blockArray['markdown']) && mb_strlen($blockArray['markdown']) > 10000) {
            $errors[] = 'Текст слишком длинный (макс. 10000 символов)';
        }

        return $errors;
    }
}
