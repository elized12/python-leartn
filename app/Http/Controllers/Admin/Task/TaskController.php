<?php

namespace App\Http\Controllers\Admin\Task;

use App\Events\AdminDashboardUpdated;
use App\Http\Controllers\Controller;
use App\Models\Task\Attempt;
use App\Models\Task\Category;
use App\Models\Task\Environment;
use App\Models\Task\File as TaskFile;
use App\Models\Task\Task;
use App\Models\Task\Test;
use App\Models\User;
use App\Service\Task\CodeJudgeService;
use App\Service\Task\TaskStatus;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TaskController extends Controller
{
    private const TASK_DIR = 'judge/tasks';

    public function showCreatePage(): View
    {
        return view('admin.task.task-create', [
            'environments' => Environment::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }

    public function createTask(Request $request): RedirectResponse
    {
        $request->validate($this->validationRules());

        $storedPaths = [];

        try {
            return DB::transaction(function () use ($request, &$storedPaths) {
                $testsPayload = $this->readTestsPayload($request);
                $referenceSolution = $this->readReferenceSolution($request);

                $task = Task::create([
                    'title' => $request->input('title'),
                    'description' => trim($request->input('description')),
                    'example' => trim((string) $request->input('example')),
                    'reference_solution' => $referenceSolution,
                    'starter_code' => trim((string) $request->input('starter_code')),
                    'rating' => $request->input('rating'),
                    'time_limit_s' => $request->input('time_limit_s'),
                    'memory_limit_mb' => $request->input('memory_limit_mb'),
                    'is_public' => (bool) $request->input('is_public', true),
                    'environment_id' => $request->input('execution_environment_id'),
                    'tests' => $testsPayload,
                ]);

                $taskDir = self::TASK_DIR . "/{$task->id}";

                if ($request->hasFile('runner_file')) {
                    $task->runner_file_path = $request->file('runner_file')->storeAs($taskDir, 'runner.py');
                    $storedPaths[] = $task->runner_file_path;
                }

                if ($request->hasFile('checker_file')) {
                    $task->checker_file_path = $request->file('checker_file')->storeAs($taskDir, 'checker.py');
                    $storedPaths[] = $task->checker_file_path;
                }

                $task->save();

                foreach ($request->file('task_files', []) as $index => $uploadedFile) {
                    $fileName = basename($uploadedFile->getClientOriginalName());
                    $filePath = $uploadedFile->storeAs("{$taskDir}/files", $fileName);
                    $storedPaths[] = $filePath;

                    TaskFile::create([
                        'task_id' => $task->id,
                        'file_path' => $filePath,
                        'is_public' => $this->uploadedTaskFileIsPublic($request, $index),
                    ]);
                }

                foreach ($testsPayload['tests'] as $index => $testData) {
                    Test::create([
                        'task_id' => $task->id,
                        'input' => $this->normalizeNewLines($testData['input'] ?? ''),
                        'expected_output' => $this->normalizeNewLines($testData['expected'] ?? ''),
                        'number' => $testData['number'] ?? $index + 1,
                    ]);
                }

                $task->categories()->sync($request->input('category_ids', []));

                $judgeResult = app(CodeJudgeService::class)->run($task->fresh(['environment', 'files']), $referenceSolution);

                if (!$judgeResult->isAccepted()) {
                    throw new Exception('Авторское решение не прошло проверку: ' . $judgeResult->description);
                }

                event(new AdminDashboardUpdated(
                    'task',
                    'Новая задача',
                    "Добавлена задача «{$task->title}»",
                    [
                        'users' => User::count(),
                        'tasks' => Task::count(),
                        'attempts_today' => Attempt::whereDate('created_at', today())->count(),
                        'completed_tasks' => Attempt::where('status', TaskStatus::COMPLETED->value)->count(),
                    ],
                    [
                        'task_id' => $task->id,
                        'task_title' => $task->title,
                    ],
                ));

                return redirect()->route('admin.main.show')->with('success', 'Задача успешно создана!');
            });
        } catch (Exception $ex) {
            foreach ($storedPaths as $path) {
                Storage::delete($path);
            }

            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => $ex->getMessage()]);
        }
    }

    public function showEditPage(Task $task): View
    {
        $task->load(['categories', 'files']);

        return view('admin.task.task-create', [
            'task' => $task,
            'environments' => Environment::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }

    public function updateTask(Request $request, Task $task): RedirectResponse
    {
        $task->load('categories');
        $request->validate($this->validationRules($task));

        $storedPaths = [];

        try {
            return DB::transaction(function () use ($request, $task, &$storedPaths) {
                $testsPayload = $this->readTestsPayload($request);
                $referenceSolution = $this->readReferenceSolution($request);
                $taskDir = self::TASK_DIR . "/{$task->id}";
                $oldRunnerPath = $task->runner_file_path;
                $oldCheckerPath = $task->checker_file_path;
                $pathsToDelete = [];

                $task->fill([
                    'title' => $request->input('title'),
                    'description' => trim($request->input('description')),
                    'example' => trim((string) $request->input('example')),
                    'reference_solution' => $referenceSolution,
                    'starter_code' => trim((string) $request->input('starter_code')),
                    'rating' => $request->input('rating'),
                    'time_limit_s' => $request->input('time_limit_s'),
                    'memory_limit_mb' => $request->input('memory_limit_mb'),
                    'is_public' => (bool) $request->input('is_public', true),
                    'environment_id' => $request->input('execution_environment_id'),
                    'tests' => $testsPayload,
                ]);

                if ($request->input('runner_mode') === 'runner') {
                    if ($request->hasFile('runner_file')) {
                        $task->runner_file_path = $request->file('runner_file')
                            ->storeAs($taskDir, 'runner_' . now()->format('YmdHis') . '.py');
                        $storedPaths[] = $task->runner_file_path;
                        if ($oldRunnerPath && $oldRunnerPath !== $task->runner_file_path) {
                            $pathsToDelete[] = $oldRunnerPath;
                        }
                    }
                } else {
                    $task->runner_file_path = null;
                    if ($oldRunnerPath) {
                        $pathsToDelete[] = $oldRunnerPath;
                    }
                }

                if ($request->input('checker_type') === 'custom') {
                    if ($request->hasFile('checker_file')) {
                        $task->checker_file_path = $request->file('checker_file')
                            ->storeAs($taskDir, 'checker_' . now()->format('YmdHis') . '.py');
                        $storedPaths[] = $task->checker_file_path;
                        if ($oldCheckerPath && $oldCheckerPath !== $task->checker_file_path) {
                            $pathsToDelete[] = $oldCheckerPath;
                        }
                    }
                } else {
                    $task->checker_file_path = null;
                    if ($oldCheckerPath) {
                        $pathsToDelete[] = $oldCheckerPath;
                    }
                }

                $task->save();

                foreach ($this->existingTaskFileVisibility($request) as $fileId => $isPublic) {
                    TaskFile::query()
                        ->where('task_id', $task->id)
                        ->whereKey($fileId)
                        ->update(['is_public' => $isPublic]);
                }

                foreach ($request->file('task_files', []) as $index => $uploadedFile) {
                    $fileName = basename($uploadedFile->getClientOriginalName());
                    $filePath = $uploadedFile->storeAs("{$taskDir}/files", $fileName);
                    $storedPaths[] = $filePath;

                    TaskFile::updateOrCreate(
                        [
                            'task_id' => $task->id,
                            'file_path' => $filePath,
                        ],
                        [
                            'is_public' => $this->uploadedTaskFileIsPublic($request, $index),
                        ]
                    );
                }

                Test::where('task_id', $task->id)->delete();
                foreach ($testsPayload['tests'] as $index => $testData) {
                    Test::create([
                        'task_id' => $task->id,
                        'input' => $this->normalizeNewLines($testData['input'] ?? ''),
                        'expected_output' => $this->normalizeNewLines($testData['expected'] ?? ''),
                        'number' => $testData['number'] ?? $index + 1,
                    ]);
                }

                $task->categories()->sync($request->input('category_ids', []));

                $judgeResult = app(CodeJudgeService::class)->run($task->fresh(['environment', 'files']), $referenceSolution);
                if (!$judgeResult->isAccepted()) {
                    throw new Exception('Авторское решение не прошло проверку: ' . $judgeResult->description);
                }

                foreach (array_unique($pathsToDelete) as $path) {
                    Storage::delete($path);
                }

                event(new AdminDashboardUpdated(
                    'task',
                    'Задача изменена',
                    "Обновлена задача «{$task->title}»",
                    [
                        'users' => User::count(),
                        'tasks' => Task::count(),
                        'attempts_today' => Attempt::whereDate('created_at', today())->count(),
                        'completed_tasks' => Attempt::where('status', TaskStatus::COMPLETED->value)->count(),
                    ],
                    [
                        'task_id' => $task->id,
                        'task_title' => $task->title,
                    ],
                ));

                return redirect()
                    ->route('admin.tasks.show')
                    ->with('success', 'Задача успешно обновлена!');
            });
        } catch (Exception $ex) {
            foreach ($storedPaths as $path) {
                Storage::delete($path);
            }

            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => $ex->getMessage()]);
        }
    }

    private function validationRules(?Task $task = null): array
    {
        $runnerFileRules = ['nullable', 'file', 'max:5120', 'extensions:py'];
        if (!$task || !$task->runner_file_path) {
            array_unshift($runnerFileRules, 'required_if:runner_mode,runner');
        }

        $checkerFileRules = ['nullable', 'file', 'max:5120', 'extensions:py'];
        if (!$task || !$task->checker_file_path) {
            array_unshift($checkerFileRules, 'required_if:checker_type,custom');
        }

        return [
            'title' => 'required|string',
            'description' => 'required|string',
            'example' => 'string|nullable',
            'category_ids' => 'required|array|min:1',
            'category_ids.*' => 'integer|exists:task_category,id',
            'rating' => 'required|integer|min:0|max:5000',
            'runner_mode' => 'required|in:solution,runner',
            'checker_type' => 'required|in:standard,custom',
            'runner_file' => $runnerFileRules,
            'checker_file' => $checkerFileRules,
            'task_files' => 'array|nullable',
            'task_files.*' => 'file|max:10420',
            'task_files_visibility' => 'array|nullable',
            'task_files_visibility.*' => 'in:public,private',
            'existing_task_files_visibility' => 'array|nullable',
            'existing_task_files_visibility.*' => 'in:public,private',
            'execution_environment_id' => 'required|integer|exists:environment,id',
            'tests_json_file' => 'nullable|file|max:10240|extensions:json',
            'tests_json_content' => 'required_without:tests_json_file|string',
            'reference_solution' => 'required_without:reference_solution_file|nullable|string',
            'reference_solution_file' => 'nullable|file|max:5120|extensions:py',
            'starter_code' => 'nullable|string|max:20000',
            'time_limit_s' => 'required|numeric|min:0.1|max:30',
            'memory_limit_mb' => 'required|integer|min:64|max:2048',
            'is_public' => 'boolean',
        ];
    }

    private function readTestsPayload(Request $request): array
    {
        $rawJson = $request->hasFile('tests_json_file')
            ? file_get_contents($request->file('tests_json_file')->getRealPath())
            : $request->input('tests_json_content');

        $payload = json_decode($rawJson, true);
        if (!is_array($payload) || !isset($payload['tests']) || !is_array($payload['tests'])) {
            throw new Exception('tests.json должен содержать объект с массивом tests');
        }

        if (empty($payload['tests'])) {
            throw new Exception('Добавьте хотя бы один тест');
        }

        foreach ($payload['tests'] as $index => &$test) {
            if (!is_array($test)) {
                throw new Exception('Каждый тест должен быть JSON-объектом');
            }

            if (!array_key_exists('expected', $test)) {
                throw new Exception('В тесте #' . ($index + 1) . ' отсутствует поле expected');
            }

            $test['number'] = (int) ($test['number'] ?? $index + 1);
            $test['input'] = $this->normalizeNewLines((string) ($test['input'] ?? ''));
            $test['expected'] = $this->normalizeNewLines((string) $test['expected']);
            $test['public'] = (bool) ($test['public'] ?? false);
        }

        return $payload;
    }

    private function uploadedTaskFileIsPublic(Request $request, int $index): bool
    {
        return $request->input("task_files_visibility.{$index}", 'public') === 'public';
    }

    private function existingTaskFileVisibility(Request $request): array
    {
        return collect($request->input('existing_task_files_visibility', []))
            ->mapWithKeys(fn(string $visibility, int|string $fileId) => [(int) $fileId => $visibility === 'public'])
            ->all();
    }

    private function readReferenceSolution(Request $request): string
    {
        if ($request->hasFile('reference_solution_file')) {
            return file_get_contents($request->file('reference_solution_file')->getRealPath());
        }

        return (string) $request->input('reference_solution');
    }

    private function normalizeNewLines(string $value): string
    {
        return str_replace(["\r\n", "\r", "\\n"], "\n", $value);
    }
}
