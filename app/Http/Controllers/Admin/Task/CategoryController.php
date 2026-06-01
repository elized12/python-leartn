<?php

namespace App\Http\Controllers\Admin\Task;

use App\Http\Controllers\Controller;
use App\Models\Task\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.task.categories', [
            'categories' => Category::query()
                ->withCount('tasks')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:task_category,name',
        ]);

        Category::create([
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($data['name']),
        ]);

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Категория создана');
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:task_category,name,' . $category->id,
        ]);

        $category->update([
            'name' => $data['name'],
            'slug' => $category->name === $data['name'] ? $category->slug : $this->uniqueSlug($data['name'], $category->id),
        ]);

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Категория обновлена');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $category->tasks()->detach();
        $category->delete();

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Категория удалена');
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name) ?: 'category';
        $slug = $baseSlug;
        $index = 2;

        while (Category::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = "{$baseSlug}-{$index}";
            $index++;
        }

        return $slug;
    }
}
