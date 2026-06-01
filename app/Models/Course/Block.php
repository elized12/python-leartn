<?php

namespace App\Models\Course;

use Illuminate\Database\Eloquent\Model;

class Block extends Model
{
    protected $table = 'lesson_block';

    protected $fillable = [
        'id',
        'course_lesson_id',
        'type',
        'order',
        'params',
    ];
}
