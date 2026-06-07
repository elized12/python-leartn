<?php

namespace App\Service\Task;

use App\Service\Ai\OllamaSettingsService;
use App\Models\Task\Attempt;
use App\Models\Task\File as TaskFile;
use App\Models\Task\Task;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class AiHintService
{
    public function __construct(
        private readonly OllamaSettingsService $ollamaSettings
    ) {}

    public function generate(Task $task, Attempt $attempt): string
    {
        if (!config('ollama.enabled', true)) {
            throw new RuntimeException('ИИ-подсказки временно отключены.');
        }

        $this->bindPromptTemplate($attempt);

        $response = Http::timeout((int) config('ollama.timeout', 120))
            ->acceptJson()
            ->post(config('ollama.url'), $this->payload($task, $attempt, false));

        if (!$response->successful()) {
            throw new RuntimeException('Ollama не смогла сформировать подсказку. Проверьте, что сервер и модель запущены.');
        }

        $content = trim((string) data_get($response->json(), 'message.content', ''));

        if ($content === '') {
            throw new RuntimeException('Ollama вернула пустой ответ.');
        }

        return $content;
    }

    public function stream(Task $task, Attempt $attempt, callable $onChunk): void
    {
        if (!config('ollama.enabled', true)) {
            throw new RuntimeException('ИИ-подсказки временно отключены.');
        }

        $this->bindPromptTemplate($attempt);

        $client = new Client([
            'timeout' => (int) config('ollama.timeout', 120),
        ]);

        $response = $client->post(config('ollama.url'), [
            'json' => $this->payload($task, $attempt, true),
            'stream' => true,
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);

        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            throw new RuntimeException('Ollama не смогла сформировать подсказку. Проверьте, что сервер и модель запущены.');
        }

        $body = $response->getBody();
        $buffer = '';

        while (!$body->eof()) {
            $buffer .= $body->read(1024);

            while (($lineEnd = strpos($buffer, "\n")) !== false) {
                $line = trim(substr($buffer, 0, $lineEnd));
                $buffer = substr($buffer, $lineEnd + 1);

                if ($line === '') {
                    continue;
                }

                $payload = json_decode($line, true);
                if (!is_array($payload)) {
                    continue;
                }

                $content = (string) data_get($payload, 'message.content', '');
                if ($content !== '') {
                    $onChunk($content);
                }

                if ((bool) ($payload['done'] ?? false)) {
                    return;
                }
            }
        }
    }

    private function payload(Task $task, Attempt $attempt, bool $stream): array
    {
        $template = $this->ollamaSettings->activePromptTemplate();

        return [
            'model' => $this->ollamaSettings->currentModel(),
            'stream' => $stream,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $template->system_prompt,
                ],
                [
                    'role' => 'user',
                    'content' => $this->userPrompt($task, $attempt, $template->user_prompt),
                ],
            ],
            'options' => $this->ollamaSettings->currentOptions(),
        ];
    }

    private function userPrompt(Task $task, Attempt $attempt, string $template): string
    {
        $categories = $task->categories
            ? $task->categories->pluck('name')->filter()->implode(', ')
            : '';

        return $this->ollamaSettings->renderTemplate(
            $template,
            [
                'task_title' => $this->limitText($task->title ?? 'Без названия', 300),
                'task_categories' => $this->limitText($categories ?: 'Не указаны', 500),
                'task_description' => $this->limitText($task->description ?? '', 6000),
                'task_examples' => $this->limitText($task->example ?? '', 2500),
                'tests' => $this->limitText($this->formatPublicTests($task), 3000),
                'task_files' => $this->limitText($this->formatTaskFiles($task), 3500),
                'student_code' => $this->limitText($attempt->code ?? '', 6000),
                'check_status' => $attempt->status->value,
                'checker_message' => $this->limitText($attempt->description ?? '', 2500),
            ],
        );
    }

    private function bindPromptTemplate(Attempt $attempt): void
    {
        if ($attempt->prompt_template_id) {
            return;
        }

        $attempt->forceFill([
            'prompt_template_id' => $this->ollamaSettings->activePromptTemplate()->id,
        ])->saveQuietly();
    }

    private function formatPublicTests(Task $task): string
    {
        $tests = $task->tests['tests'] ?? [];
        if (!is_array($tests)) {
            return 'Публичные тесты не указаны.';
        }

        $publicTests = collect($tests)
            ->filter(fn($test) => is_array($test) && (bool) ($test['public'] ?? false))
            ->take(3)
            ->values();

        if ($publicTests->isEmpty()) {
            return 'Публичные тесты не указаны.';
        }

        return $publicTests
            ->map(function (array $test, int $index) {
                $input = (string) ($test['input'] ?? '');
                $expected = (string) ($test['expected'] ?? $test['expected_output'] ?? '');

                return sprintf(
                    "Тест #%d\nВвод:\n%s\nОжидаемый вывод:\n%s",
                    $test['number'] ?? ($index + 1),
                    $input === '' ? '[пустой ввод]' : $input,
                    $expected === '' ? '[пустой вывод]' : $expected,
                );
            })
            ->implode("\n\n");
    }

    private function formatTaskFiles(Task $task): string
    {
        $files = $task->files
            ->filter(fn(TaskFile $file) => $file->is_public)
            ->values();

        if ($files->isEmpty()) {
            return 'Публичные файлы не прикреплены.';
        }

        return $files
            ->map(function (TaskFile $file) {
                $name = basename($file->file_path);
                $visibility = $file->is_public ? 'публичный' : 'служебный';

                if (!Storage::exists($file->file_path)) {
                    return "- {$name} ({$visibility}): файл не найден в хранилище.";
                }

                $size = Storage::size($file->file_path);
                $summary = $this->fileSummary($file, $size);

                return "- {$name} ({$visibility}, {$this->formatBytes($size)})\n{$summary}";
            })
            ->implode("\n\n");
    }

    private function fileSummary(TaskFile $file, int $size): string
    {
        $name = basename($file->file_path);
        $extension = mb_strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $textExtensions = ['txt', 'csv', 'json', 'xml', 'yaml', 'yml', 'md', 'py', 'ini', 'cfg', 'log'];

        if ($size > 12 * 1024) {
            return 'Содержимое не отправлено: файл больше 12 KB.';
        }

        if (!in_array($extension, $textExtensions, true)) {
            return 'Содержимое не отправлено: файл не похож на текстовый.';
        }

        $content = (string) Storage::get($file->file_path);

        if (!mb_check_encoding($content, 'UTF-8')) {
            return 'Содержимое не отправлено: файл не в UTF-8 или бинарный.';
        }

        $content = trim($content);
        if ($content === '') {
            return 'Файл пустой.';
        }

        return "Короткое содержимое:\n" . $this->limitText($content, 1200);
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024) {
            return round($bytes / 1024 / 1024, 2) . ' MB';
        }

        if ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' B';
    }

    private function limitText(string $value, int $limit): string
    {
        $value = trim($value);

        if (mb_strlen($value) <= $limit) {
            return $value;
        }

        return mb_substr($value, 0, $limit) . "\n\n[Текст обрезан, потому что он слишком длинный]";
    }
}
