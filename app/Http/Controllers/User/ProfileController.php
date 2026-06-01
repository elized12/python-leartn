<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Course\Course;
use App\Models\Course\Statistics\CompletedLesson;
use App\Models\Course\Statistics\Participant;
use App\Models\Task\Attempt;
use App\Models\User;
use App\Service\Rating\UserRatingService;
use App\Service\Task\TaskStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    public function showProfilePage(int $userId, UserRatingService $ratingService): View
    {
        $user = User::find($userId);
        if (!$user) {
            return view('user.not-found-profile');
        }

        $latestAttemptsIds = Attempt::selectRaw('MAX(id) as id')
            ->where('status', TaskStatus::COMPLETED->value)
            ->where('user_id', $userId)
            ->groupBy('task_id')
            ->pluck('id');

        $tasksCompleted = Attempt::query()
            ->with(['task.categories'])
            ->whereIn('id', $latestAttemptsIds)
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get();

        $tasksCompletedCount = Attempt::query()
            ->where('status', TaskStatus::COMPLETED->value)
            ->where('user_id', $userId)
            ->select('task_id')
            ->distinct('task_id')
            ->count();

        $attemptsCount = Attempt::query()
            ->where('user_id', $userId)
            ->count();

        $joinedCourses = $this->joinedCoursesWithProgress($userId);
        $completedCourses = $joinedCourses
            ->filter(fn(Course $course) => $course->lessons_count > 0 && $course->progress >= 100)
            ->values();

        $activeCourses = $joinedCourses
            ->reject(fn(Course $course) => $course->lessons_count > 0 && $course->progress >= 100)
            ->values();

        $userRating = $ratingService->leaderboard()->first(
            fn($rating) => (int) $rating->user->id === (int) $userId
        );

        return view('user.profile', [
            'user' => $user,
            'tasksCompleted' => $tasksCompleted,
            'tasksCompletedCount' => $tasksCompletedCount,
            'attemptsCount' => $attemptsCount,
            'joinedCourses' => $joinedCourses,
            'completedCourses' => $completedCourses,
            'activeCourses' => $activeCourses,
            'userRating' => $userRating,
        ]);
    }

    private function joinedCoursesWithProgress(int $userId)
    {
        return Course::query()
            ->select('course.*')
            ->with('categories')
            ->withCount('lessons')
            ->addSelect([
                'completed_lessons_count' => CompletedLesson::query()
                    ->selectRaw('COUNT(*)')
                    ->join('course_lesson', 'course_lesson.id', '=', 'completed_lesson.course_lesson_id')
                    ->whereColumn('course_lesson.course_id', 'course.id')
                    ->where('completed_lesson.user_id', $userId),
                'joined_at' => Participant::query()
                    ->select('created_at')
                    ->whereColumn('course_participant.course_id', 'course.id')
                    ->where('course_participant.user_id', $userId)
                    ->limit(1),
            ])
            ->where('status', 'published')
            ->whereIn('course.id', Participant::query()
                ->select('course_id')
                ->where('user_id', $userId))
            ->orderByDesc(DB::raw('joined_at'))
            ->get()
            ->map(function (Course $course) {
                $lessonsCount = max((int) $course->lessons_count, 1);
                $completedCount = (int) $course->completed_lessons_count;
                $course->progress = (int) round(($completedCount / $lessonsCount) * 100);

                return $course;
            });
    }
}
