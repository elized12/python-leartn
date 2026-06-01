<?php

namespace App\Service\Admin;

use App\Models\Course\Course;
use App\Models\Course\Statistics\Participant;
use App\Models\Task\Attempt;
use App\Models\Task\Task;
use App\Models\User;
use App\Service\Task\TaskStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AdminDashboardStats
{
    public function counters(): array
    {
        return [
            'users' => User::count(),
            'tasks' => Task::count(),
            'courses' => Course::count(),
            'attempts_today' => Attempt::whereDate('created_at', today())->count(),
            'completed_tasks' => Attempt::where('status', TaskStatus::COMPLETED->value)->count(),
            'started_courses' => Participant::count(),
            'completed_courses' => $this->completedCoursesCount(),
        ];
    }

    public function courseStarts(): Collection
    {
        return Course::query()
            ->withCount(['lessons', 'participants'])
            ->orderByDesc('participants_count')
            ->orderBy('title')
            ->limit(8)
            ->get()
            ->map(function (Course $course) {
                return [
                    'id' => $course->id,
                    'title' => $course->title,
                    'lessons_count' => (int) $course->lessons_count,
                    'started_count' => (int) $course->participants_count,
                    'completed_count' => $this->completedCoursesCount($course->id),
                ];
            });
    }

    public function completedCoursesCount(?int $courseId = null): int
    {
        $query = DB::table('course_participant as participant')
            ->whereExists(function ($subQuery) {
                $subQuery->selectRaw('1')
                    ->from('course_lesson')
                    ->whereColumn('course_lesson.course_id', 'participant.course_id');
            })
            ->whereRaw('(
                SELECT COUNT(*)
                FROM completed_lesson
                INNER JOIN course_lesson ON course_lesson.id = completed_lesson.course_lesson_id
                WHERE course_lesson.course_id = participant.course_id
                  AND completed_lesson.user_id = participant.user_id
            ) >= (
                SELECT COUNT(*)
                FROM course_lesson
                WHERE course_lesson.course_id = participant.course_id
            )');

        if ($courseId) {
            $query->where('participant.course_id', $courseId);
        }

        return (int) $query->count();
    }
}
