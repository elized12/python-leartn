<?php

namespace App\Models\Course;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lesson extends Model
{
    protected $table = 'course_lesson';

    protected $fillable = [
        'title',
        'order',
        'course_id'
    ];

    public function blocks(): HasMany
    {
        return $this->hasMany(Block::class, 'course_lesson_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }
}
