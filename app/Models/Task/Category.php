<?php

namespace App\Models\Task;

use App\Models\Course\Course;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $table = 'task_category';

    protected $fillable = [
        'name',
        'slug',
    ];

    public function tasks()
    {
        return $this->belongsToMany(Task::class, 'task_category_task', 'category_id', 'task_id');
    }

    public function courses()
    {
        return $this->belongsToMany(Course::class, 'course_category', 'category_id', 'course_id');
    }
}
