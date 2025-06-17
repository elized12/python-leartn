<?php

namespace App\Models\Task;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Test extends Model
{
    use HasFactory;

    protected $table = 'task_test';

    protected $fillable = [
        'id',
        'task_id',
        'input',
        'expected_output',
        'number'
    ];
}
