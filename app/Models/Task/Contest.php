<?php

namespace App\Models\Task;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contest extends Model
{
    use HasFactory;

    protected $table = 'contest';

    protected $fillable = [
        'title',
        'description',
        'starts_at',
        'duration_minutes',
        'ends_at',
        'is_active',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'duration_minutes' => 'integer',
        'is_active' => 'boolean',
    ];

    public function tasks()
    {
        return $this->belongsToMany(Task::class, 'contest_task', 'contest_id', 'task_id')
            ->withPivot('sort_order')
            ->orderBy('contest_task.sort_order')
            ->orderBy('task.id');
    }

    public function participants()
    {
        return $this->hasMany(ContestParticipant::class, 'contest_id');
    }

    public function attempts()
    {
        return $this->hasMany(Attempt::class, 'contest_id');
    }

    public function isStarted(): bool
    {
        return $this->starts_at === null || now()->greaterThanOrEqualTo($this->starts_at);
    }

    public function isFinished(): bool
    {
        return $this->ends_at !== null && now()->greaterThan($this->ends_at);
    }

    public function isRunning(): bool
    {
        return $this->is_active && $this->isStarted() && !$this->isFinished();
    }

    public function statusLabel(): string
    {
        if (!$this->is_active) {
            return 'Скрыт';
        }

        if (!$this->isStarted()) {
            return 'Скоро';
        }

        if ($this->isFinished()) {
            return 'Завершён';
        }

        return 'Идёт';
    }
}
