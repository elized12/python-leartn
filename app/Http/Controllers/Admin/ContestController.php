<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Task\Contest;
use App\Models\Task\Task;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContestController extends Controller
{
    public function index(): View
    {
        return view('admin.contests.index', [
            'contests' => Contest::query()->withCount(['tasks', 'participants'])->latest('id')->paginate(10),
            'tasks' => Task::query()->where('is_public', true)->orderBy('rating')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'starts_at' => 'nullable|date',
            'duration_minutes' => 'required|integer|min:1|max:43200',
            'task_ids' => 'array',
            'task_ids.*' => 'integer|exists:task,id',
            'is_active' => 'boolean',
        ]);

        return DB::transaction(function () use ($data, $request) {
            $contest = Contest::create([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'starts_at' => $data['starts_at'] ?? null,
                'duration_minutes' => $data['duration_minutes'],
                'ends_at' => $this->endsAt($data['starts_at'] ?? null, (int) $data['duration_minutes']),
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]);

            $this->syncTasks($contest, (array) ($data['task_ids'] ?? []));

            return redirect()->route('admin.contests.index')->with('success', 'Контест создан');
        });
    }

    public function update(Request $request, Contest $contest): RedirectResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'starts_at' => 'nullable|date',
            'duration_minutes' => 'required|integer|min:1|max:43200',
            'task_ids' => 'array',
            'task_ids.*' => 'integer|exists:task,id',
            'is_active' => 'boolean',
        ]);

        return DB::transaction(function () use ($contest, $data) {
            $contest->update([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'starts_at' => $data['starts_at'] ?? null,
                'duration_minutes' => $data['duration_minutes'],
                'ends_at' => $this->endsAt($data['starts_at'] ?? null, (int) $data['duration_minutes']),
                'is_active' => (bool) ($data['is_active'] ?? false),
            ]);

            $this->syncTasks($contest, (array) ($data['task_ids'] ?? []));

            return redirect()->route('admin.contests.index')->with('success', 'Контест обновлён');
        });
    }

    public function startNow(Contest $contest): RedirectResponse
    {
        $contest->update([
            'starts_at' => now(),
            'ends_at' => now()->addMinutes(max(1, (int) $contest->duration_minutes)),
            'is_active' => true,
        ]);

        return redirect()->route('admin.contests.index')->with('success', 'Контест запущен');
    }

    public function destroy(Contest $contest): RedirectResponse
    {
        $contest->delete();

        return redirect()->route('admin.contests.index')->with('success', 'Контест удалён');
    }

    private function syncTasks(Contest $contest, array $taskIds): void
    {
        $sync = [];
        foreach (array_values(array_unique($taskIds)) as $index => $taskId) {
            $sync[(int) $taskId] = ['sort_order' => $index + 1];
        }

        $contest->tasks()->sync($sync);
    }

    private function endsAt(?string $startsAt, int $durationMinutes): ?\Illuminate\Support\Carbon
    {
        return $startsAt ? \Illuminate\Support\Carbon::parse($startsAt)->addMinutes($durationMinutes) : null;
    }
}
