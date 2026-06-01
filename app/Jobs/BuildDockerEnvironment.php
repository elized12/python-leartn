<?php

namespace App\Jobs;

use App\Events\AdminDashboardUpdated;
use App\Models\Task\Attempt;
use App\Models\Task\Environment;
use App\Models\Task\Task;
use App\Models\User;
use App\Service\Task\TaskStatus;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class BuildDockerEnvironment implements ShouldQueue
{
    use Queueable;

    public int $timeout = 1200;

    public function __construct(
        public readonly string $name,
        public readonly string $dockerImageName,
        public readonly ?string $description,
        public readonly bool $isActive,
        public readonly string $buildContextPath,
        public readonly bool $deleteBuildContext = false,
        public readonly array $editorLibraries = [],
    ) {
        $this->onQueue(config('queue.names.docker_builds', 'docker-builds'));
    }

    public function handle(): void
    {
        $this->broadcast(
            'build',
            'Сборка Docker-образа',
            "Началась сборка окружения «{$this->name}»",
            ['image' => $this->dockerImageName, 'status' => 'running']
        );

        $result = Process::timeout($this->timeout)->run([
            'docker',
            'build',
            '-t',
            $this->dockerImageName,
            $this->absoluteBuildContextPath(),
        ]);

        if (!$result->successful()) {
            $this->broadcast(
                'build_error',
                'Сборка не удалась',
                "Docker-образ «{$this->dockerImageName}» не собрался",
                [
                    'image' => $this->dockerImageName,
                    'status' => 'failed',
                    'output' => mb_substr($result->errorOutput() ?: $result->output(), 0, 1200),
                ]
            );

            $this->cleanup();
            return;
        }

        Environment::updateOrCreate(
            ['docker_image_name' => $this->dockerImageName],
            [
                'name' => $this->name,
                'slug' => $this->uniqueSlug($this->name),
                'description' => $this->description,
                'editor_libraries' => $this->editorLibraries,
                'is_active' => $this->isActive,
            ]
        );

        $this->broadcast(
            'build_success',
            'Сборка завершена',
            "Окружение «{$this->name}» собрано и зарегистрировано",
            ['image' => $this->dockerImageName, 'status' => 'success']
        );

        $this->cleanup();
    }

    public function failed(Throwable $exception): void
    {
        $this->broadcast(
            'build_error',
            'Сборка прервана',
            "Сборка окружения «{$this->name}» завершилась ошибкой",
            [
                'image' => $this->dockerImageName,
                'status' => 'failed',
                'output' => mb_substr($exception->getMessage(), 0, 1200),
            ]
        );

        $this->cleanup();
    }

    private function absoluteBuildContextPath(): string
    {
        if (str_starts_with($this->buildContextPath, '/')) {
            return $this->buildContextPath;
        }

        return Storage::path($this->buildContextPath);
    }

    private function cleanup(): void
    {
        if ($this->deleteBuildContext) {
            Storage::deleteDirectory($this->buildContextPath);
        }
    }

    private function uniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name) ?: 'environment';
        $slug = $baseSlug;
        $index = 2;

        while (Environment::query()
            ->where('slug', $slug)
            ->where('docker_image_name', '!=', $this->dockerImageName)
            ->exists()) {
            $slug = "{$baseSlug}-{$index}";
            $index++;
        }

        return $slug;
    }

    private function broadcast(string $type, string $title, string $message, array $meta = []): void
    {
        event(new AdminDashboardUpdated(
            $type,
            $title,
            $message,
            [
                'users' => User::count(),
                'tasks' => Task::count(),
                'attempts_today' => Attempt::whereDate('created_at', today())->count(),
                'completed_tasks' => Attempt::where('status', TaskStatus::COMPLETED->value)->count(),
            ],
            $meta
        ));
    }
}
