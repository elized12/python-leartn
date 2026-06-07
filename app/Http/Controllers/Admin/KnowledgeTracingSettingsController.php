<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Service\KnowledgeTracingSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KnowledgeTracingSettingsController extends Controller
{
    public function index(KnowledgeTracingSettingsService $settingsService): View
    {
        return view('admin.knowledge-tracing.settings', [
            'settings' => $settingsService->settings(),
            'defaults' => $settingsService->defaults(),
        ]);
    }

    public function update(Request $request, KnowledgeTracingSettingsService $settingsService): RedirectResponse
    {
        $data = $request->validate([
            'prior' => 'required|numeric|min:0|max:1',
            'learn' => 'required|numeric|min:0|max:1',
            'guess' => 'required|numeric|min:0|max:1',
            'slip' => 'required|numeric|min:0|max:1',
        ]);

        $settingsService->save($data);

        return redirect()
            ->route('admin.knowledge-tracing.index')
            ->with('success', 'Параметры BKT сохранены');
    }
}
