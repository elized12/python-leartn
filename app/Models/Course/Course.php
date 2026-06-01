<?php

namespace App\Models\Course;

use App\Models\Course\Statistics\Participant;
use App\Models\Task\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    protected $table = 'course';

    protected $fillable = [
        'creator_id',
        'title',
        'description',
        'url',
        'difficulty',
        'time_of_passage_hours',
        'intro_img_path',
        'status',
    ];

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class, 'course_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class, 'course_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'course_category', 'course_id', 'category_id')
            ->withTimestamps();
    }
}
