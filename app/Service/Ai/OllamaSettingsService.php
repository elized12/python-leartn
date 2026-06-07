<?php

namespace App\Service\Ai;

use App\Models\Ai\AiPromptTemplate;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class OllamaSettingsService
{
    private const SETTINGS_PATH = 'settings/ollama.json';
    private const DEFAULT_SYSTEM_PROMPT = <<<'PROMPT'
Ты ИИ-помощник на образовательном сайте по Python для школьников.

Твоя задача — объяснять ошибки и давать понятные подсказки после неудачной отправки решения.

Правила:
- Отвечай только на русском языке.
- Не пиши готовое решение задачи полностью.
- Не переписывай весь код ученика.
- Не раскрывай полный алгоритм, если можно дать направление мягче.
- Можно показывать маленькие фрагменты кода до 2-3 строк, если без них трудно объяснить синтаксис.
- Если ошибка связана с вводом/выводом, отдельно обрати внимание на формат данных.
- Если в задаче есть файлы, учитывай их имена, назначение и короткое содержимое как часть условия.
- Не требуй от ученика открыть файл, если проблема видна по коду или сообщению проверки.
- Если ошибка может быть связана с чтением файла, путём файла, кодировкой или форматом данных, явно укажи это как направление проверки.
- Если содержимое файла не передано полностью, не придумывай его. Опирайся только на имя файла, размер и доступный фрагмент.
- Если ошибка логическая, помоги найти место рассуждения, где ответ может стать неверным.
- Будь технически точным, но объясняй простыми словами.

Формат ответа:
1. Что произошло
2. Где искать проблему
3. Что стоит проверить
4. Небольшая подсказка без готового решения
PROMPT;
    private const DEFAULT_USER_PROMPT = <<<'PROMPT'
Название задачи:
{{task_title}}

Темы задачи:
{{task_categories}}

Условие задачи:
{{task_description}}

Примеры из условия:
{{task_examples}}

Публичные тесты для понимания формата ввода и вывода:
{{tests}}

Файлы задачи:
{{task_files}}

Код ученика:
```python
{{student_code}}
```

Статус проверки:
{{check_status}}

Сообщение системы проверки:
{{checker_message}}

Сформируй подсказку для ученика. Не давай готовое решение и не пиши полный исправленный код.
PROMPT;

    public function currentModel(): string
    {
        return (string) ($this->settings()['model'] ?? config('ollama.model'));
    }
    
    public function activePromptTemplate(): AiPromptTemplate
    {
        $template = AiPromptTemplate::query()
            ->where('is_active', true)
            ->latest('id')
            ->first();

        if ($template instanceof AiPromptTemplate) {
            return $template;
        }

        return $this->ensureDefaultPromptTemplate();
    }

    public function currentOptions(): array
    {
        $template = $this->activePromptTemplate();

        return array_filter(
            array_merge(
                [
                    'temperature' => config('ollama.temperature', 0.2),
                    'num_predict' => config('ollama.num_predict', 550),
                    'num_ctx' => config('ollama.num_ctx', 8192),
                    'keep_alive' => config('ollama.keep_alive', '5m'),
                ],
                (array) ($template->parameters ?? [])
            ),
            static fn($value) => $value !== null && $value !== ''
        );
    }

    public function saveModel(string $model): void
    {
        $settings = $this->settings();
        $settings['model'] = $model;

        $this->saveSettings($settings);
    }

    public function systemPrompt(): string
    {
        return $this->activePromptTemplate()->system_prompt;
    }

    public function userPromptTemplate(): string
    {
        return $this->activePromptTemplate()->user_prompt;
    }

    public function savePrompts(string $systemPrompt, string $userPrompt, array $options = [], ?string $name = null): AiPromptTemplate
    {
        $settings = $this->settings();
        $settings['system_prompt'] = $systemPrompt;
        $settings['user_prompt'] = $userPrompt;
        $this->saveSettings($settings);

        $template = AiPromptTemplate::query()->create([
            'name' => $name ?: 'Prompt ' . now()->format('d.m.Y H:i'),
            'description' => 'Сохранено из панели администратора',
            'system_prompt' => $systemPrompt,
            'user_prompt' => $userPrompt,
            'parameters' => $this->mergeOptions($options),
            'is_active' => true,
            'is_default' => false,
        ]);

        AiPromptTemplate::query()
            ->where('id', '!=', $template->id)
            ->update(['is_active' => false]);

        return $template;
    }

    public function promptVariables(): array
    {
        return [
            'task_title' => 'Название задачи',
            'task_categories' => 'Темы/категории задачи через запятую',
            'task_description' => 'Условие задачи',
            'task_examples' => 'Примеры из описания задачи',
            'tests' => 'До 3 публичных тестов с вводом и ожидаемым выводом',
            'task_files' => 'Публичные файлы задачи: имена, размер и короткий фрагмент для небольших текстовых файлов',
            'student_code' => 'Код ученика из последней попытки',
            'check_status' => 'Статус проверки: Incorrect result, Time limit, Memory limit и т.д.',
            'checker_message' => 'Сообщение системы проверки или текст ошибки',
        ];
    }

    public function renderTemplate(string $template, array $variables): string
    {
        $replacements = [];

        foreach ($variables as $name => $value) {
            $replacements['{{' . $name . '}}'] = (string) $value;
        }

        return strtr($template, $replacements);
    }

    public function installedModels(): Collection
    {
        $response = Http::timeout(5)
            ->acceptJson()
            ->get($this->ollamaApiUrl('/api/tags'));

        if (!$response->successful()) {
            throw new RuntimeException('Не удалось получить список моделей Ollama.');
        }

        return collect($response->json('models', []))
            ->map(fn(array $model) => [
                'name' => (string) ($model['name'] ?? ''),
                'size' => (int) ($model['size'] ?? 0),
                'modified_at' => $model['modified_at'] ?? null,
            ])
            ->filter(fn(array $model) => $model['name'] !== '')
            ->sortBy('name')
            ->values();
    }

    public function pullModel(string $model): void
    {
        $response = Http::timeout((int) config('ollama.pull_timeout', 600))
            ->acceptJson()
            ->post($this->ollamaApiUrl('/api/pull'), [
                'name' => $model,
                'stream' => false,
            ]);

        if (!$response->successful()) {
            throw new RuntimeException('Не удалось установить модель. Проверьте название модели и подключение Ollama.');
        }
    }

    public function chatUrl(): string
    {
        return (string) config('ollama.url');
    }

    public function baseUrl(): string
    {
        return $this->ollamaBaseUrl();
    }

    private function settings(): array
    {
        if (!Storage::exists(self::SETTINGS_PATH)) {
            return [];
        }

        $settings = json_decode((string) Storage::get(self::SETTINGS_PATH), true);

        return is_array($settings) ? $settings : [];
    }

    private function ensureDefaultPromptTemplate(): AiPromptTemplate
    {
        $legacy = $this->settings();

        $template = AiPromptTemplate::query()
            ->where('is_default', true)
            ->orWhere('name', 'Default prompt')
            ->latest('id')
            ->first();

        if ($template instanceof AiPromptTemplate) {
            return $template;
        }

        return AiPromptTemplate::query()->create([
            'name' => 'Default prompt',
            'description' => 'Стандартный шаблон подсказок',
            'system_prompt' => (string) ($legacy['system_prompt'] ?? self::DEFAULT_SYSTEM_PROMPT),
            'user_prompt' => (string) ($legacy['user_prompt'] ?? self::DEFAULT_USER_PROMPT),
            'parameters' => $this->mergeOptions([]),
            'is_active' => true,
            'is_default' => true,
        ]);
    }

    private function mergeOptions(array $options): array
    {
        return array_filter(
            array_merge(
                [
                    'temperature' => config('ollama.temperature', 0.2),
                    'num_predict' => config('ollama.num_predict', 550),
                    'num_ctx' => config('ollama.num_ctx', 8192),
                    'keep_alive' => config('ollama.keep_alive', '5m'),
                ],
                $options
            ),
            static fn($value) => $value !== null && $value !== ''
        );
    }

    private function saveSettings(array $settings): void
    {
        Storage::put(self::SETTINGS_PATH, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private function ollamaApiUrl(string $path): string
    {
        return $this->ollamaBaseUrl() . $path;
    }

    private function ollamaBaseUrl(): string
    {
        $parts = parse_url((string) config('ollama.url'));
        $scheme = $parts['scheme'] ?? 'http';
        $host = $parts['host'] ?? '127.0.0.1';
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';

        return "{$scheme}://{$host}{$port}";
    }
}
