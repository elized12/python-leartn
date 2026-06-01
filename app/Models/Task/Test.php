<?php

namespace App\Models\Task;

use Illuminate\Database\Eloquent\Model;

class Test extends Model
{
    protected $table = 'task_test';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'task_id',
        'input',
        'expected_output',
        'number'
    ];

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }
}
