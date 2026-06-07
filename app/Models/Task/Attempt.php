<?php

namespace App\Models\Task;

use App\Models\Ai\AiPromptTemplate;
use App\Service\Task\TaskStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attempt extends Model
{
    use HasFactory;

    protected $table = 'attempt_solution';

    protected $fillable = [
        'id',
        'status',
        'task_id',
        'contest_id',
        'user_id',
        'execution_time_s',
        'peak_memory_usage_mb',
        'description',
        'code',
        'prompt_template_id',
    ];

    protected $casts = [
        'status' => TaskStatus::class,
        'peak_memory_usage_mb' => 'float',
        'execution_time_s' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function contest()
    {
        return $this->belongsTo(Contest::class, 'contest_id');
    }

    public function promptTemplate()
    {
        return $this->belongsTo(AiPromptTemplate::class, 'prompt_template_id');
    }
}
