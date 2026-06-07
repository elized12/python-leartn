<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Service\KnowledgeTracingService;
use App\Service\KnowledgeTracingSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KnowledgeTracingSettingsController extends Controller
{
    public function index(
        Request $request,
        KnowledgeTracingSettingsService $settingsService,
        KnowledgeTracingService $knowledgeTracingService
    ): View {
        $selectedUser = null;
        $knowledgeProfile = collect();
        $recommendedTasks = collect();
        $selectedUserId = $request->integer('user_id') ?: null;

        if ($selectedUserId) {
            $selectedUser = User::query()->find($selectedUserId);

            if ($selectedUser) {
                $knowledgeProfile = $knowledgeTracingService->getUserKnowledgeProfile($selectedUser);
                $recommendedTasks = $knowledgeTracingService->getRecommendedTasks($selectedUser, 5);
            }
        }

        return view('admin.knowledge-tracing.settings', [
            'settings' => $settingsService->settings(),
            'defaults' => $settingsService->defaults(),
            'users' => User::query()->orderBy('name')->limit(300)->get(['id', 'name', 'email']),
            'selectedUser' => $selectedUser,
            'selectedUserId' => $selectedUserId,
            'knowledgeProfile' => $knowledgeProfile,
            'recommendedTasks' => $recommendedTasks,
        ]);
    }

    public function update(Request $request, KnowledgeTracingSettingsService $settingsService): RedirectResponse
    {
        $data = $request->validate([
            'prior' => 'required|numeric|min:0|max:1',
            'learn' => 'required|numeric|min:0|max:1',
            'guess' => 'required|numeric|min:0|max:1',
            'slip' => 'required|numeric|min:0|max:1',
            'easy_rating_max' => 'required|integer|min:0|max:5000|lt:medium_rating_max',
            'medium_rating_max' => 'required|integer|min:0|max:5000',
            'easy_mastery_cap' => 'required|numeric|min:0|max:1|lte:medium_mastery_cap',
            'medium_mastery_cap' => 'required|numeric|min:0|max:1|lte:hard_mastery_cap',
            'hard_mastery_cap' => 'required|numeric|min:0|max:1',
        ]);

        $settingsService->save($data);

        return redirect()
            ->route('admin.knowledge-tracing.index')
            ->with('success', 'Параметры BKT сохранены');
    }
}
