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
        'time_limit_s',
        'memory_limit_b',
        'rating',
        'example'
    ];
}
