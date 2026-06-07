<?php

namespace App\Service\Course\Block\Validation;

class Divider implements BlockValidatorInterface
{
    private const ALLOWED_POSITIONS = ['left', 'center', 'right'];
    private const ALLOWED_TYPES = ['solid', 'dashed', 'dotted', 'double'];

    public function validate(string $block): array
    {
        $errors = [];
        $blockArray = json_decode($block, true);

        if (!$blockArray) {
            return ['Неверный формат JSON данных'];
        }

        if (isset($blockArray['type']) && !in_array($blockArray['type'], $this::ALLOWED_TYPES)) {
            $errors[] = 'Недопустимый тип линии разделителя';
        }

        if (isset($blockArray['thickness'])) {
            $thickness = $blockArray['thickness'];
            if (!is_numeric($thickness) || $thickness < 1 || $thickness > 6) {
                $errors[] = 'Толщина должна быть от 1 до 6 пикселей';
            }
        }

        if (isset($blockArray['margin'])) {
            $margin = $blockArray['margin'];
            if (!is_numeric($margin) || $margin < 8 || $margin > 64) {
                $errors[] = 'Отступ должен быть от 8 до 64 пикселей';
            }
        }

        if (isset($blockArray['labelPosition']) && !in_array(
            $blockArray['labelPosition'],
            $this::ALLOWED_POSITIONS
        )) {
            $errors[] = 'Недопустимое положение подписи';
        }

        if (isset($blockArray['label']) && mb_strlen($blockArray['label']) > 50) {
            $errors[] = 'Подпись не должна превышать 50 символов';
        }

        return $errors;
    }
}
