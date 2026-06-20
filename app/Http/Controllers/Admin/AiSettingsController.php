<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Service\Ai\OllamaSettingsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class AiSettingsController extends Controller
{
    public function index(OllamaSettingsService $ollamaSettings): View
    {
        $models = collect();
        $connectionError = null;

        try {
            $models = $ollamaSettings->installedModels();
        } catch (Throwable $exception) {
            $connectionError = $exception->getMessage();
        }

        return view('admin.ai.settings', [
            'currentModel' => $ollamaSettings->currentModel(),
            'models' => $models,
            'connectionError' => $connectionError,
            'ollamaBaseUrl' => $ollamaSettings->baseUrl(),
            'ollamaChatUrl' => $ollamaSettings->chatUrl(),
            'systemPrompt' => $ollamaSettings->systemPrompt(),
            'userPrompt' => $ollamaSettings->userPromptTemplate(),
            'promptVariables' => $ollamaSettings->promptVariables(),
            'aiOptions' => $ollamaSettings->currentOptions(),
            'keepAlive' => $ollamaSettings->keepAlive(),
        ]);
    }

    public function update(Request $request, OllamaSettingsService $ollamaSettings): RedirectResponse
    {
        $data = $request->validate([
            'model' => ['required', 'string', 'max:120', 'regex:/^[a-zA-Z0-9_.:\/-]+$/'],
        ]);

        try {
            $ollamaSettings->warmModel($data['model']);
            $ollamaSettings->saveModel($data['model']);
        } catch (Throwable $exception) {
            return redirect()
                ->route('admin.ai-settings.index')
                ->withErrors(['model' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.ai-settings.index')
            ->with('success', "Модель подсказок изменена на {$data['model']} и загружена в память");
    }

    public function install(Request $request, OllamaSettingsService $ollamaSettings): RedirectResponse
    {
        $data = $request->validate([
            'model' => ['required', 'string', 'max:120', 'regex:/^[a-zA-Z0-9_.:\/-]+$/'],
            'make_active' => ['nullable', 'boolean'],
        ]);

        try {
            $ollamaSettings->pullModel($data['model']);
        } catch (Throwable $exception) {
            return redirect()
                ->route('admin.ai-settings.index')
                ->withErrors(['model' => $exception->getMessage()]);
        }

        if ($request->boolean('make_active')) {
            try {
                $ollamaSettings->warmModel($data['model']);
                $ollamaSettings->saveModel($data['model']);
            } catch (Throwable $exception) {
                return redirect()
                    ->route('admin.ai-settings.index')
                    ->withErrors(['model' => $exception->getMessage()]);
            }
        }

        return redirect()
            ->route('admin.ai-settings.index')
            ->with('success', $request->boolean('make_active')
                ? "Модель {$data['model']} установлена, выбрана для подсказок и загружена в память"
                : "Модель {$data['model']} установлена");
    }

    public function updatePrompt(Request $request, OllamaSettingsService $ollamaSettings): RedirectResponse
    {
        $request->merge([
            'temperature' => $this->normalizeDecimalInput($request->input('temperature')),
        ]);

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:120'],
            'system_prompt' => ['required', 'string', 'max:12000'],
            'user_prompt' => ['required', 'string', 'max:20000'],
            'temperature' => ['nullable', 'numeric', 'between:0,1'],
            'num_predict' => ['nullable', 'integer', 'min:1', 'max:8192'],
            'num_ctx' => ['nullable', 'integer', 'min:1', 'max:32768'],
        ]);

        $ollamaSettings->savePrompts(
            $data['system_prompt'],
            $data['user_prompt'],
            array_filter([
                'temperature' => isset($data['temperature']) ? (float) $data['temperature'] : null,
                'num_predict' => isset($data['num_predict']) ? (int) $data['num_predict'] : null,
                'num_ctx' => isset($data['num_ctx']) ? (int) $data['num_ctx'] : null,
            ], static fn($value) => $value !== null && $value !== ''),
            $data['name'] ?? null,
        );

        return redirect()
            ->route('admin.ai-settings.index')
            ->with('success', 'Промпт подсказок сохранён как новая версия в базе данных');
    }

    private function normalizeDecimalInput(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return $value;
        }

        return str_replace(',', '.', (string) $value);
    }
}
