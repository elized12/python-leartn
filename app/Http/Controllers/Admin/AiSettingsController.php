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
        ]);
    }

    public function update(Request $request, OllamaSettingsService $ollamaSettings): RedirectResponse
    {
        $data = $request->validate([
            'model' => ['required', 'string', 'max:120', 'regex:/^[a-zA-Z0-9_.:\/-]+$/'],
        ]);

        $ollamaSettings->saveModel($data['model']);

        return redirect()
            ->route('admin.ai-settings.index')
            ->with('success', "Модель подсказок изменена на {$data['model']}");
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
            $ollamaSettings->saveModel($data['model']);
        }

        return redirect()
            ->route('admin.ai-settings.index')
            ->with('success', $request->boolean('make_active')
                ? "Модель {$data['model']} установлена и выбрана для подсказок"
                : "Модель {$data['model']} установлена");
    }

    public function updatePrompt(Request $request, OllamaSettingsService $ollamaSettings): RedirectResponse
    {
        $data = $request->validate([
            'system_prompt' => ['required', 'string', 'max:12000'],
            'user_prompt' => ['required', 'string', 'max:20000'],
        ]);

        $ollamaSettings->savePrompts($data['system_prompt'], $data['user_prompt']);

        return redirect()
            ->route('admin.ai-settings.index')
            ->with('success', 'Промпт подсказок обновлен');
    }
}
