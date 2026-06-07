<?php

namespace App\Service\Course\Block\Validation;

class InfoBox implements BlockValidatorInterface
{
    public function validate(string $block): array
    {
        $errors = [];
        $blockArray = json_decode($block, true);

        if (!$blockArray) {
            return ['Ошибка данных'];
        }

        return $errors;
    }
}
