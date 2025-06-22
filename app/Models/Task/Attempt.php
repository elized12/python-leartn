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
        'peak_memory_usage_b',
        'description'
    ];

    protected $casts = [
        'status' => TaskStatus::class
    ];
}
