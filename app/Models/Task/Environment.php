<?php

namespace App\Models\Task;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Environment extends Model
{
    use HasFactory;

    protected $table = 'environment';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'docker_image_name',
        'editor_libraries',
        'is_active'
    ];

    protected $casts = [
        'editor_libraries' => 'array',
        'is_active' => 'boolean',
    ];

    public function tasks()
    {
        return $this->hasMany(Task::class, 'environment_id');
    }
}
