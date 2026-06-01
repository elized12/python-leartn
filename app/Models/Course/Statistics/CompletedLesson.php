<?php

namespace App\Models\Course\Statistics;

use App\Models\Course\Lesson;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompletedLesson extends Model
{
    protected $table = 'completed_lesson';

    protected $fillable = [
        'id',
        'user_id',
        'course_lesson_id'
    ];

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class, 'course_lesson_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
