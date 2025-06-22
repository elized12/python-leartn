<?php

namespace App\Http\Controllers\Admin\Task;

use App\Http\Controllers\Controller;
use App\Models\Task\Task;
use App\Models\Task\Test;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaskController extends Controller
{
    public function showCreatePage(): View
    {
        return view('admin.task.task-create');
    }

    public function createTask(Request $request): RedirectResponse
    {
        $request->validate([
            'title' => 'required|string',
            'description' => 'required|string',
            'example' => 'string|nullable',
            'rating' => 'required|integer|min:0|max:5000'
        ]);

        try {
            return DB::transaction(function () use ($request) {
                $testCases = [];

                $inputKeys = array_filter(array_keys($request->all()), function ($key) {
                    return str_starts_with($key, 'test-case-input-');
                });

                foreach ($inputKeys as $inputKey) {
                    $testNumber = str_replace('test-case-input-', '', $inputKey);
                    $outputKey = 'test-case-output-' . $testNumber;

                    if (empty($request->input($inputKey))) {
                        throw new Exception('Поле input не может быть пустым');
                    }

                    if (empty($request->input($outputKey))) {
                        throw new Exception('Поле output не может быть пустым');
                    }

                    $testCases[] = [
                        'input' => $request->input($inputKey),
                        'output' => $request->input($outputKey)
                    ];
                }

                if (empty($testCases)) {
                    throw new Exception('Добавьте хотя бы один тестовый пример');
                }

                $allowedTags = '<p><br><strong><em><ul><ol><li><code><pre><h1>';

                $task = Task::create([
                    'title' => $request->input('title'),
                    'description' => strip_tags($request->input('description'), $allowedTags),
                    'example' => strip_tags($request->input('example'), $allowedTags),
                    'rating' => $request->input('rating')
                ]);

                $number = 1;
                foreach ($testCases as $testData) {
                    Test::create([
                        'task_id' => $task->id,
                        'input' =>  str_replace(["\r\n", "\r", "\\n"], "\n", $testData['input']),
                        'expected_output' => str_replace(["\r\n", "\r", "\\n"], "\n", $testData['output']),
                        'number' => $number++
                    ]);
                }

                return redirect()->route('admin.main.show')->with('success', 'Задача успешно создана!');
            });
        } catch (Exception $ex) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => $ex->getMessage()]);
        }
    }
}
