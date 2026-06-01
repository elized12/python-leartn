<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course\Course;
use App\Models\Course\Statistics\CompletedLesson;
use App\Models\Course\Statistics\Participant;
use App\Models\Task\Attempt;
use App\Models\Task\Task;
use App\Models\User;
use App\Service\Admin\AdminDashboardStats;
use App\Service\Task\TaskStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

class AdminController extends Controller
{
    public function showMainPage(AdminDashboardStats $dashboardStats): View
    {
        $stats = $dashboardStats->counters();

        return view('admin.main', [
            'tasksCount' => Task::count(),
            'usersCount' => User::count(),
            'coursesCount' => Course::count(),
            'completedTasksCount' => Attempt::where('status', TaskStatus::COMPLETED->value)->count(),
            'attemptsTodayCount' => Attempt::whereDate('created_at', today())->count(),
            'startedCoursesCount' => $stats['started_courses'],
            'completedCoursesCount' => $stats['completed_courses'],
            'courseStarts' => $dashboardStats->courseStarts(),
            'latestActivity' => $this->latestActivity(),
        ]);
    }

    public function showTasksPage(): View
    {
        $tasks = Task::paginate(10);
        return view('admin.task.tasks', ['tasks' => $tasks]);
    }

    private function latestActivity(): Collection
    {
        $users = User::query()
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn(User $user) => [
                'type' => 'user',
                'title' => 'Новый пользователь',
                'message' => "{$user->name} зарегистрировался",
                'created_at' => $user->created_at,
            ]);

        $tasks = Task::query()
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn(Task $task) => [
                'type' => 'task',
                'title' => 'Новая задача',
                'message' => "Добавлена задача «{$task->title}»",
                'created_at' => $task->created_at,
            ]);

        $attempts = Attempt::query()
            ->with('user')
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn(Attempt $attempt) => [
                'type' => $attempt->status === TaskStatus::COMPLETED ? 'success' : 'attempt',
                'title' => $attempt->status === TaskStatus::COMPLETED ? 'Accepted' : 'Попытка решения',
                'message' => ($attempt->user?->name ?? 'Пользователь') . ' отправил решение',
                'created_at' => $attempt->created_at,
            ]);

        $startedCourses = Participant::query()
            ->with(['course', 'user'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn(Participant $participant) => [
                'type' => 'course',
                'title' => 'Курс начат',
                'message' => ($participant->user?->name ?? 'Пользователь') . ' начал курс «' . ($participant->course?->title ?? 'Курс') . '»',
                'created_at' => $participant->created_at,
            ]);

        $completedLessons = CompletedLesson::query()
            ->with(['lesson.course', 'user'])
            ->latest()
            ->limit(8)
            ->get()
            ->filter(fn(CompletedLesson $completedLesson) => $completedLesson->lesson?->course)
            ->filter(function (CompletedLesson $completedLesson) {
                $course = $completedLesson->lesson->course;
                $lessonIds = $course->lessons()->pluck('id');

                if ($lessonIds->isEmpty()) {
                    return false;
                }

                $completedCount = CompletedLesson::where('user_id', $completedLesson->user_id)
                    ->whereIn('course_lesson_id', $lessonIds)
                    ->count();

                return $completedCount >= $lessonIds->count();
            })
            ->map(fn(CompletedLesson $completedLesson) => [
                'type' => 'course_success',
                'title' => 'Курс пройден',
                'message' => ($completedLesson->user?->name ?? 'Пользователь') . ' прошел курс «' . $completedLesson->lesson->course->title . '»',
                'created_at' => $completedLesson->created_at,
            ]);

        return $users
            ->merge($tasks)
            ->merge($attempts)
            ->merge($startedCourses)
            ->merge($completedLessons)
            ->sortByDesc('created_at')
            ->take(10)
            ->values();
    }
}
