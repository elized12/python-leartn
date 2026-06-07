<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Jobs\GenerateAiHint;
use App\Models\Task\Attempt;
use App\Models\Task\Category;
use App\Models\Task\Comment;
use App\Models\Task\Contest;
use App\Models\Task\ContestParticipant;
use App\Models\Task\File as TaskFile;
use App\Models\Task\Task;
use App\Service\MarkdownConverter;
use App\Service\Task\TaskStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class TaskController
{
    public function showTasksPage(Request $request): View
    {
        $user = Auth::user();
        $categorySlug = $request->query('category');
        $difficulty = $request->query('difficulty');
        $status = $request->query('status');
        $search = trim((string) $request->query('search', ''));

        $solvedTaskIds = Attempt::query()
            ->where('user_id', Auth::id())
            ->where('status', TaskStatus::COMPLETED->value)
            ->pluck('task_id')
            ->unique()
            ->values();

        $attemptedTaskIds = Attempt::query()
            ->where('user_id', Auth::id())
            ->where('status', '!=', TaskStatus::COMPLETED->value)
            ->pluck('task_id')
            ->unique()
            ->values();

        $tasks = Task::query()
            ->where('is_public', true)
            ->with('categories')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery
                        ->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('categories', fn($categoryQuery) => $categoryQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($categorySlug, function ($query) use ($categorySlug) {
                $query->whereHas('categories', fn($categoryQuery) => $categoryQuery->where('slug', $categorySlug));
            })
            ->when($difficulty === 'easy', fn($query) => $query->where('rating', '<', 1200))
            ->when($difficulty === 'medium', fn($query) => $query->whereBetween('rating', [1200, 1799]))
            ->when($difficulty === 'hard', fn($query) => $query->where('rating', '>=', 1800))
            ->when($status === 'solved', fn($query) => $query->whereIn('id', $solvedTaskIds))
            ->when($status === 'attempted', fn($query) => $query->whereIn('id', $attemptedTaskIds->diff($solvedTaskIds)))
            ->when($status === 'unsolved', fn($query) => $query->whereNotIn('id', $solvedTaskIds))
            ->orderBy('rating')
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString();

        $recommendedTask = Task::query()
            ->where('is_public', true)
            ->with('categories')
            ->whereNotIn('id', $solvedTaskIds)
            ->when($categorySlug, function ($query) use ($categorySlug) {
                $query->whereHas('categories', fn($categoryQuery) => $categoryQuery->where('slug', $categorySlug));
            })
            ->when($difficulty === 'easy', fn($query) => $query->where('rating', '<', 1200))
            ->when($difficulty === 'medium', fn($query) => $query->whereBetween('rating', [1200, 1799]))
            ->when($difficulty === 'hard', fn($query) => $query->where('rating', '>=', 1800))
            ->orderBy('rating')
            ->first();

        return view('task.tasks', [
            'tasks' => $tasks,
            'user' => $user,
            'categories' => Category::all(),
            'activeCategorySlug' => $categorySlug,
            'activeDifficulty' => $difficulty,
            'activeStatus' => $status,
            'search' => $search,
            'solvedTaskIds' => $solvedTaskIds,
            'attemptedTaskIds' => $attemptedTaskIds,
            'recommendedTask' => $recommendedTask,
        ]);
    }

    public function showTaskPage(Request $request, int $taskId): View
    {
        $task = Task::query()
            ->with(['categories', 'comments.user', 'files', 'environment'])
            ->find($taskId);

        if (!$task) {
            return view('task.task-not-found');
        }

        if (!$task->is_public && !Auth::user()?->is_admin) {
            abort(404);
        }

        $contest = null;
        $contestId = $request->integer('contest_id');
        if ($contestId) {
            $candidateContest = Contest::query()->with('tasks')->find($contestId);
            if (
                $candidateContest
                && $candidateContest->tasks->contains('id', $task->id)
                && ContestParticipant::where('contest_id', $candidateContest->id)->where('user_id', Auth::id())->exists()
            ) {
                $contest = $candidateContest;
            }
        }

        $attempts = Attempt::query()
            ->where('task_id', $taskId)
            ->where('user_id', Auth::id())
            ->when($contest, fn($query) => $query->where('contest_id', $contest->id))
            ->when(!$contest, fn($query) => $query->whereNull('contest_id'))
            ->latest()
            ->get();

        $hasSolvedTask = $attempts->contains(
            fn(Attempt $attempt) => $attempt->status === TaskStatus::COMPLETED
        );

        $bestByTime = $this->bestAcceptedAttempts($taskId)
            ->whereNotNull('execution_time_s')
            ->orderBy('execution_time_s')
            ->orderBy('peak_memory_usage_mb')
            ->latest()
            ->limit(3)
            ->get();

        $bestByMemory = $this->bestAcceptedAttempts($taskId)
            ->whereNotNull('peak_memory_usage_mb')
            ->orderBy('peak_memory_usage_mb')
            ->orderBy('execution_time_s')
            ->latest()
            ->limit(3)
            ->get();

        return view('task.task', [
            'task' => $task,
            'taskDescriptionHtml' => MarkdownConverter::convertToHtml($task->description ?? ''),
            'taskExampleHtml' => MarkdownConverter::convertToHtml($task->example ?? ''),
            'commentHtml' => $task->comments
                ->mapWithKeys(fn(Comment $comment) => [
                    $comment->id => MarkdownConverter::convertToHtml($comment->content ?? ''),
            ]),
            'attempts' => $attempts,
            'hasSolvedTask' => $hasSolvedTask,
            'bestByTime' => $bestByTime,
            'bestByMemory' => $bestByMemory,
            'publicTests' => $this->publicTests($task),
            'publicFiles' => $task->files->where('is_public', true)->values(),
            'editorConfig' => [
                'starterCode' => $task->starter_code ?? '',
                'libraries' => $task->environment?->editor_libraries ?? [],
                'files' => $task->files
                    ->where('is_public', true)
                    ->map(fn($file) => [
                        'name' => basename($file->file_path),
                        'path' => 'files/' . basename($file->file_path),
                    ])
                    ->values()
                    ->all(),
            ],
            'contest' => $contest,
        ]);
    }

    public function aiHint(Request $request, int $taskId): JsonResponse
    {
        $task = Task::query()
            ->with(['categories', 'files'])
            ->find($taskId);

        if (!$task) {
            return response()->json([
                'status' => false,
                'message' => 'task not found',
            ], 404);
        }

        $attempt = $this->failedAttemptForAiHint($request, $taskId);
        if (!$attempt) {
            return response()->json([
                'status' => false,
                'message' => 'ИИ-подсказка доступна только после неудачной отправки решения.',
            ], 422);
        }

        $requestId = (string) Str::uuid();

        GenerateAiHint::dispatch($taskId, $attempt->id, Auth::id(), $requestId);

        return response()->json([
            'status' => true,
            'state' => 'queued',
            'request_id' => $requestId,
            'attempt_id' => $attempt->id,
            'message' => 'Подсказка поставлена в очередь. Ожидаем свободную нейросеть.',
        ]);
    }

    public function downloadPublicFile(int $taskId, int $fileId): StreamedResponse|RedirectResponse
    {
        $file = TaskFile::query()
            ->where('task_id', $taskId)
            ->where('is_public', true)
            ->find($fileId);

        if (!$file || !Storage::exists($file->file_path)) {
            return redirect()
                ->route('task.solution', ['taskId' => $taskId])
                ->withErrors(['file' => 'Файл недоступен']);
        }

        return Storage::download($file->file_path, basename($file->file_path));
    }

    public function storeComment(Request $request, int $taskId): RedirectResponse
    {
        $request->validate([
            'content' => 'required|string|max:2000',
        ]);

        if (!Task::whereKey($taskId)->exists()) {
            return redirect()->route('tasks.show');
        }

        Comment::create([
            'task_id' => $taskId,
            'user_id' => Auth::id(),
            'content' => trim($request->input('content')),
        ]);

        return redirect()
            ->route('task.solution', ['taskId' => $taskId])
            ->with('success', 'Комментарий добавлен');
    }

    public function showAuthorSolution(int $taskId): JsonResponse
    {
        $task = Task::query()->find($taskId);
        if (!$task) {
            return response()->json([
                'status' => false,
                'message' => 'task not found',
            ], 404);
        }

        if (!$this->hasSolvedTask($taskId)) {
            return response()->json([
                'status' => false,
                'message' => 'Решение откроется после успешной отправки',
            ], 403);
        }

        return response()->json([
            'status' => true,
            'solution' => $task->reference_solution ?? '',
        ]);
    }

    private function hasSolvedTask(int $taskId): bool
    {
        return Attempt::query()
            ->where('task_id', $taskId)
            ->where('user_id', Auth::id())
            ->where('status', TaskStatus::COMPLETED->value)
            ->exists();
    }

    private function failedAttemptForAiHint(Request $request, int $taskId): ?Attempt
    {
        if (!$request->filled('attempt_id')) {
            return null;
        }

        $attemptQuery = Attempt::query()
            ->where('task_id', $taskId)
            ->where('user_id', Auth::id())
            ->whereNotIn('status', [
                TaskStatus::COMPLETED->value,
                TaskStatus::PENDING->value,
            ]);

        return $attemptQuery
            ->where('id', $request->integer('attempt_id'))
            ->latest()
            ->first();
    }

    private function bestAcceptedAttempts(int $taskId)
    {
        return Attempt::query()
            ->with('user')
            ->where('task_id', $taskId)
            ->where('status', TaskStatus::COMPLETED->value);
    }

    private function publicTests(Task $task): array
    {
        $tests = $task->tests['tests'] ?? [];
        if (!is_array($tests)) {
            return [];
        }

        return collect($tests)
            ->filter(fn($test) => is_array($test) && (bool) ($test['public'] ?? false))
            ->values()
            ->all();
    }
};
