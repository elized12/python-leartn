<?php

namespace App\Http\Controllers\Admin\Task;

use App\Http\Controllers\Controller;
use App\Jobs\BuildDockerEnvironment;
use App\Models\Task\Environment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EnvironmentController extends Controller
{
    public function index(): View
    {
        return view('admin.task.environments', [
            'environments' => Environment::query()
                ->withCount('tasks')
                ->orderBy('name')
                ->get(),
            'defaultImage' => 'python-learn/judge-python:3.12',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'docker_image_name' => 'required|string|max:255|unique:environment,docker_image_name',
            'editor_libraries' => 'nullable|string|max:1000',
            'dockerfile' => 'nullable|file|max:5120',
            'is_active' => 'nullable|boolean',
        ]);

        if ($request->hasFile('dockerfile')) {
            $buildDir = $this->storeUploadedDockerfile($request);

            BuildDockerEnvironment::dispatch(
                $data['name'],
                $data['docker_image_name'],
                $data['description'] ?? null,
                $request->boolean('is_active'),
                $buildDir,
                true,
                $this->parseEditorLibraries($data['editor_libraries'] ?? null)
            );

            return redirect()
                ->route('admin.environments.index')
                ->with('success', 'Сборка Docker-образа отправлена в очередь. О результате сообщим в админ-панели.');
        }

        Environment::create([
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($data['name']),
            'description' => $data['description'] ?? null,
            'docker_image_name' => $data['docker_image_name'],
            'editor_libraries' => $this->parseEditorLibraries($data['editor_libraries'] ?? null),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('admin.environments.index')
            ->with('success', $request->hasFile('dockerfile')
                ? 'Dockerfile собран, окружение создано'
                : 'Окружение создано');
    }

    public function installDefault(): RedirectResponse
    {
        BuildDockerEnvironment::dispatch(
            'Python 3.12 Judge',
            'python-learn/judge-python:3.12',
            'Стандартное окружение Python 3.12 с /usr/bin/time для измерения памяти.',
            true,
            base_path('docker/judge-python'),
            false,
            []
        );

        return redirect()
            ->route('admin.environments.index')
            ->with('success', 'Сборка стандартного Python-образа отправлена в очередь');
    }

    public function installPandas(): RedirectResponse
    {
        BuildDockerEnvironment::dispatch(
            'Python 3.12 Pandas Judge',
            'python-learn/judge-python-pandas:3.12',
            'Окружение Python 3.12 для анализа данных: pandas, numpy, openpyxl, pyarrow и /usr/bin/time.',
            true,
            base_path('docker/judge-python-pandas'),
            false,
            ['pandas', 'numpy', 'openpyxl', 'pyarrow']
        );

        return redirect()
            ->route('admin.environments.index')
            ->with('success', 'Сборка Python pandas-образа отправлена в очередь');
    }

    public function update(Request $request, Environment $environment): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'docker_image_name' => 'required|string|max:255|unique:environment,docker_image_name,' . $environment->id,
            'editor_libraries' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
        ]);

        $environment->update([
            'name' => $data['name'],
            'slug' => $environment->name === $data['name'] ? $environment->slug : $this->uniqueSlug($data['name'], $environment->id),
            'description' => $data['description'] ?? null,
            'docker_image_name' => $data['docker_image_name'],
            'editor_libraries' => $this->parseEditorLibraries($data['editor_libraries'] ?? null),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('admin.environments.index')
            ->with('success', 'Окружение обновлено');
    }

    public function destroy(Environment $environment): RedirectResponse
    {
        $deletedTasksCount = 0;

        DB::transaction(function () use ($environment, &$deletedTasksCount) {
            $tasks = $environment->tasks()->get();
            $deletedTasksCount = $tasks->count();

            foreach ($tasks as $task) {
                Storage::deleteDirectory("judge/tasks/{$task->id}");
                $task->delete();
            }

            $environment->delete();
        });

        return redirect()
            ->route('admin.environments.index')
            ->with('success', $deletedTasksCount > 0
                ? "Окружение удалено вместе с задачами: {$deletedTasksCount}"
                : 'Окружение удалено');
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name) ?: 'environment';
        $slug = $baseSlug;
        $index = 2;

        while (Environment::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = "{$baseSlug}-{$index}";
            $index++;
        }

        return $slug;
    }

    private function storeUploadedDockerfile(Request $request): string
    {
        $buildDir = 'judge/dockerfiles/' . Str::uuid()->toString();
        Storage::makeDirectory($buildDir);

        $dockerfilePath = "{$buildDir}/Dockerfile";
        Storage::put($dockerfilePath, file_get_contents($request->file('dockerfile')->getRealPath()));

        return $buildDir;
    }

    private function parseEditorLibraries(?string $libraries): array
    {
        if (!$libraries) {
            return [];
        }

        return collect(preg_split('/[,;\n]+/', $libraries))
            ->map(fn(string $library) => trim($library))
            ->filter()
            ->unique(fn(string $library) => mb_strtolower($library))
            ->values()
            ->all();
    }
}
