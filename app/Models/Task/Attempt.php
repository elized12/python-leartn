<?php

namespace App\Models\Task;

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
        'user_id',
        'execution_time_s',
        'peak_memory_usage_mb',
        'description',
        'code',
    ];

    protected $casts = [
        'status' => TaskStatus::class
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }
}
