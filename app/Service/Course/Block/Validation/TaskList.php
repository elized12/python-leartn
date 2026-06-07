<?php

namespace App\Service\Course\Block\Validation;

use App\Models\Task\Task;

class TaskList implements BlockValidatorInterface
{
    public function validate(string $block): array
    {
        $errors = [];
        $blockArray = json_decode($block, true);

        if (!$blockArray) {
            return ['Неверный формат данных'];
        }

        if (!empty($blockArray['title']) && mb_strlen($blockArray['title']) > 120) {
            $errors[] = 'Заголовок блока задач не должен превышать 120 символов';
        }

        if (empty($blockArray['tasks']) || !is_array($blockArray['tasks'])) {
            return ['Добавьте хотя бы одну задачу'];
        }

        $taskIds = collect($blockArray['tasks'])
            ->pluck('id')
            ->filter(fn($id) => is_numeric($id))
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        if ($taskIds->isEmpty()) {
            return ['Добавьте хотя бы одну задачу'];
        }

        if ($taskIds->count() !== count($blockArray['tasks'])) {
            $errors[] = 'В списке задач есть некорректные или повторяющиеся задачи';
        }

        $existingCount = Task::query()
            ->whereIn('id', $taskIds)
            ->count();

        if ($existingCount !== $taskIds->count()) {
            $errors[] = 'Одна или несколько задач не найдены';
        }

        return $errors;
    }
}
