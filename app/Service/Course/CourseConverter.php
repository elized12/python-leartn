<?php

namespace App\Service\Course;

use App\Models\Course\Lesson;
use App\Models\Task\Attempt;
use App\Models\Task\Task;
use App\Service\Task\TaskStatus;
use Illuminate\Support\Facades\Auth;

class CourseConverter
{
    public function convertLessonToJson(Lesson $lesson): string
    {
        $blocks = [];
        foreach ($lesson->blocks as $block) {
            $params = json_decode($block->params, true) ?: [];

            if ($block->type === TypeBlock::TaskList->value) {
                $params = $this->hydrateTaskListParams($params);
            }

            $blocks[] = [
                'type' => $block->type,
                'order' => $block->order,
                'params' => $params
            ];
        }

        $lessonJson = [
            'id' => $lesson->id,
            'title' => $lesson->title,
            'order' => $lesson->order,
            'blocks' => $blocks
        ];

        return json_encode($lessonJson);
    }

    private function hydrateTaskListParams(array $params): array
    {
        $taskIds = collect($params['tasks'] ?? [])
            ->pluck('id')
            ->filter(fn($id) => is_numeric($id))
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        if ($taskIds->isEmpty()) {
            $params['tasks'] = [];
            return $params;
        }

        $tasks = Task::query()
            ->whereIn('id', $taskIds)
            ->get()
            ->keyBy('id');

        $solvedTaskIds = Attempt::query()
            ->where('user_id', Auth::id())
            ->where('status', TaskStatus::COMPLETED->value)
            ->whereIn('task_id', $taskIds)
            ->pluck('task_id')
            ->map(fn($id) => (int) $id)
            ->all();

        $params['tasks'] = $taskIds
            ->map(function (int $taskId) use ($tasks, $solvedTaskIds) {
                $task = $tasks->get($taskId);

                if (!$task) {
                    return null;
                }

                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'rating' => $task->rating,
                    'url' => url("/task/solution/{$task->id}"),
                    'solved' => in_array($task->id, $solvedTaskIds, true),
                ];
            })
            ->filter()
            ->values()
            ->all();

        return $params;
    }
};
