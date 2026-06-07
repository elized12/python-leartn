<?php

namespace App\Service\Course\Block\Validation;

class ExecutableCode implements BlockValidatorInterface
{
    private const REQUIRED_FIELDS = ['title', 'description', 'language', 'code'];
    private const ALLOWED_LANGUAGES = ['python'];

    public function validate(string $block): array
    {
        $errors = [];
        $blockArray = json_decode($block, true);

        if (!$blockArray) {
            return ['Неверный формат данных'];
        }

        foreach ($this::REQUIRED_FIELDS as $field) {
            if (empty($blockArray[$field])) {
                $errors[] = "Отсутствует поле: {$field}";
            }
        }

        if (!empty($errors)) {
            return $errors;
        }

        if (!in_array($blockArray['language'], $this::ALLOWED_LANGUAGES)) {
            $errors[] = 'Неподдерживаемый язык программирования';
        }

        if (mb_strlen($blockArray['title']) > 200) {
            $errors[] = 'Заголовок слишком длинный (макс. 200 символов)';
        }

        if (mb_strlen($blockArray['description']) > 1000) {
            $errors[] = 'Описание слишком длинное (макс. 1000 символов)';
        }

        $code = $blockArray['code'];
        if (strlen($code) > 10000) {
            $errors[] = 'Код слишком длинный (макс. 10000 символов)';
        }

        return $errors;
    }
}
