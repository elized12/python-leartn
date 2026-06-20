<?php

namespace App\Service\Course\Block\Validation;

class Quiz implements BlockValidatorInterface
{
    public function validate(string $block): array
    {
        $errors = [];
        $blockArray = json_decode($block, true);

        if (!$blockArray) {
            return ['Неверный формат данных'];
        }

        if ($this->isBlank($blockArray['title'] ?? null)) {
            $errors[] = 'Требуется заголовок теста';
        } elseif (mb_strlen($blockArray['title']) > 200) {
            $errors[] = 'Заголовок не должен превышать 200 символов';
        }

        if (empty($blockArray['questions']) || !is_array($blockArray['questions'])) {
            $errors[] = 'Тест должен содержать вопросы';
        } else {
            $questions = $blockArray['questions'];

            if (count($questions) === 0) {
                $errors[] = 'Добавьте хотя бы один вопрос';
            }

            foreach ($questions as $index => $question) {
                $questionNumber = $index + 1;

                if ($this->isBlank($question['type'] ?? null)) {
                    $errors[] = "Вопрос {$questionNumber}: не указан тип";
                } elseif (!in_array($question['type'], ['single', 'multiple', 'text'])) {
                    $errors[] = "Вопрос {$questionNumber}: недопустимый тип вопроса";
                }

                if ($this->isBlank($question['question'] ?? null)) {
                    $errors[] = "Вопрос {$questionNumber}: текст вопроса не может быть пустым";
                } elseif (mb_strlen($question['question']) > 500) {
                    $errors[] = "Вопрос {$questionNumber}: текст слишком длинный (макс. 500 символов)";
                }

                if ($question['type'] === 'single' || $question['type'] === 'multiple') {
                    if (empty($question['options']) || !is_array($question['options']) || count($question['options']) === 0) {
                        $errors[] = "Вопрос {$questionNumber}: добавьте варианты ответов";
                    } else {
                        $hasCorrect = false;
                        foreach ($question['options'] as $optIndex => $option) {
                            $optNumber = $optIndex + 1;

                            if ($this->isBlank($option['text'] ?? null)) {
                                $errors[] = "Вопрос {$questionNumber}, вариант {$optNumber}: текст не может быть пустым";
                            } elseif (mb_strlen($option['text']) > 200) {
                                $errors[] = "Вопрос {$questionNumber}, вариант {$optNumber}: текст слишком длинный";
                            }

                            if (isset($option['correct']) && $option['correct']) {
                                $hasCorrect = true;
                            }
                        }

                        if (!$hasCorrect) {
                            $errors[] = "Вопрос {$questionNumber}: должен быть хотя бы один правильный ответ";
                        }

                        if ($question['type'] === 'single') {
                            $correctCount = 0;
                            foreach ($question['options'] as $option) {
                                if (isset($option['correct']) && $option['correct']) {
                                    $correctCount++;
                                }
                            }
                            if ($correctCount > 1) {
                                $errors[] = "Вопрос {$questionNumber}: для типа 'один ответ' должен быть только один правильный вариант";
                            }
                        }
                    }
                } elseif ($question['type'] === 'text') {
                    if (empty($question['options']) || !is_array($question['options']) || count($question['options']) === 0) {
                        $errors[] = "Вопрос {$questionNumber}: укажите правильный ответ";
                    } else {
                        $correctAnswer = null;
                        foreach ($question['options'] as $option) {
                            if (isset($option['correct']) && $option['correct'] && !$this->isBlank($option['text'] ?? null)) {
                                $correctAnswer = $option['text'];
                                break;
                            }
                        }

                        if (!$correctAnswer) {
                            $errors[] = "Вопрос {$questionNumber}: укажите правильный текстовый ответ";
                        } elseif (mb_strlen($correctAnswer) > 500) {
                            $errors[] = "Вопрос {$questionNumber}: правильный ответ слишком длинный";
                        }
                    }
                }
            }
        }

        return $errors;
    }

    private function isBlank(mixed $value): bool
    {
        return $value === null || trim((string) $value) === '';
    }
}
