<?php

namespace App\Models\Task;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $table = 'task';

    protected $fillable = [
        'id',
        'title',
        'description',
        'reference_solution',
        'starter_code',
        'time_limit_s',
        'memory_limit_mb',
        'rating',
        'example',
        'is_public',
        'runner_file_path',
        'checker_file_path',
        'environment_id',
        'tests'
    ];

    protected $casts = [
        'tests' => 'array',
        'time_limit_s' => 'float',
        'memory_limit_mb' => 'integer',
        'is_public' => 'boolean',
    ];

    public function environment()
    {
        return $this->belongsTo(Environment::class, 'environment_id');
    }

    public function files()
    {
        return $this->hasMany(File::class, 'task_id');
    }

    public function testCases()
    {
        return $this->hasMany(Test::class, 'task_id')->orderBy('number');
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'task_category_task', 'task_id', 'category_id')
            ->orderBy('name');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class, 'task_id')->latest();
    }

    public function contests()
    {
        return $this->belongsToMany(Contest::class, 'contest_task', 'task_id', 'contest_id');
    }
}
