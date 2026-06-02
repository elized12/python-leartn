<?php

namespace App\Http\Controllers;

use App\Events\AdminDashboardUpdated;
use App\Http\Controllers\Controller;
use App\Models\Course\Block;
use App\Models\Course\Course;
use App\Models\Course\Lesson;
use App\Models\Course\Statistics\CompletedLesson;
use App\Models\Course\Statistics\Participant;
use App\Models\Task\Category;
use App\Models\Task\Task;
use App\Service\Admin\AdminDashboardStats;
use App\Service\Course\Block\Validation\Factory;
use App\Service\Course\CourseConverter;
use App\Service\Course\Difficulty;
use App\Service\Course\TypeBlock;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CourseController extends Controller
{
    private const TEMP_DEFAULT_IMAGE_COURSE = 'https://images.unsplash.com/photo-1555066931-4365d14bab8c?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=80';
    private const COURSE_STATUS_DRAFT = 'draft';
    private const COURSE_STATUS_PUBLISHED = 'published';

    public function __construct(
        protected Factory $validationFactory,
        protected CourseConverter $converter
    ) {}

    public function showCoursesPage(Request $request): View
    {
        $filter = $request->query('filter');

        $popularCourses = Participant::select('course_id', DB::raw('COUNT(*) as participants_count'))
            ->groupBy('course_id')
            ->orderByDesc('participants_count')
            ->limit(3)
            ->get()
            ->map(function ($participant) {
                $course = Course::withCount('lessons')
                    ->where('status', self::COURSE_STATUS_PUBLISHED)
                    ->find($participant->course_id);
                if ($course) {
                    $course->participants_count = $participant->participants_count;
                    return $course;
                }
                return null;
            })
            ->filter();

        $userId = Auth::id();

        $courses = Course::query()
            ->select('course.*')
            ->with('categories')
            ->withCount('lessons')
            ->addSelect([
                'progress' => DB::query()
                    ->selectRaw('
                COALESCE(
                    ROUND(
                        (
                            SELECT COUNT(*)
                            FROM completed_lesson
                            LEFT JOIN course_lesson 
                                ON course_lesson.id = completed_lesson.course_lesson_id
                            WHERE course_lesson.course_id = course.id
                              AND completed_lesson.user_id = ?
                        ) / NULLIF(
                            (
                                SELECT COUNT(*)
                                FROM course_lesson
                                WHERE course_lesson.course_id = course.id
                            ), 
                            0
                        ) * 100,
                        2
                    ),
                    0
                )
            ', [$userId]),
            'count_participants' => DB::query()
                ->selectRaw('COALESCE(
                    (SELECT COUNT(*) FROM course_participant WHERE course_participant.course_id = course.id)
                )')
            ])
            ->where('status', self::COURSE_STATUS_PUBLISHED)
            ->when($filter === 'my', function ($query) use ($userId) {
                $query->whereHas('participants', function ($participantQuery) use ($userId) {
                    $participantQuery->where('user_id', $userId);
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        $countCourses = Participant::where('user_id', Auth::id())->count();
        $countCompletedLessons = CompletedLesson::where('user_id', Auth::id())->count();

        return view('courses.courses', [
            'courses' => $courses,
            'tempImage' => $this::TEMP_DEFAULT_IMAGE_COURSE,
            'popularCourses' => $popularCourses,
            'countCourses' => $countCourses,
            'countCompletedLessons' => $countCompletedLessons,
            'activeFilter' => $filter,
        ]);
    }

    public function showCreatePage(): View
    {
        return view('courses.create.create', [
            'isEdit' => false,
            'course' => null,
            'editorPayload' => null,
            'categories' => Category::query()->orderBy('name')->get(),
            'taskOptions' => $this->courseTaskOptions(),
        ]);
    }

    public function showDraftsPage(): View
    {
        $drafts = Course::withCount('lessons')
            ->where('status', self::COURSE_STATUS_DRAFT)
            ->when(!Auth::user()?->is_admin, fn($query) => $query->where('creator_id', Auth::id()))
            ->latest()
            ->paginate(20);

        return view('courses.drafts', [
            'drafts' => $drafts,
            'tempImage' => self::TEMP_DEFAULT_IMAGE_COURSE,
        ]);
    }

    public function showEditPage(Course $course): View
    {
        $this->ensureCanManageCourse($course);

        $course->load([
            'categories',
            'lessons' => fn($query) => $query->orderBy('order'),
            'lessons.blocks' => fn($query) => $query->orderBy('order'),
        ]);

        return view('courses.create.create', [
            'isEdit' => true,
            'course' => $course,
            'editorPayload' => $this->courseEditorPayload($course),
            'categories' => Category::query()->orderBy('name')->get(),
            'taskOptions' => $this->courseTaskOptions(),
        ]);
    }

    public function showPreviewCourse(string $url): View|RedirectResponse
    {
        $course = Course::with(['lessons', 'lessons.blocks'])
            ->withCount('lessons')
            ->where('url', '=', $url)
            ->where('status', self::COURSE_STATUS_PUBLISHED)
            ->first();

        if (!$course) {
            abort(404, 'Курс не найден');
        }

        $userId = Auth::id();

        $participant = Participant::where('user_id', $userId)
            ->where('course_id', $course->id)
            ->first();

        if ($participant) {
            return redirect()->route('course.show', ['courseName' => $url]);
        }


        $participantsCount = Participant::where('course_id', $course->id)->count();
        $course->participants_count = $participantsCount;

        $hasStarted = false;
        $progress = 0;

        return view('courses.start-course', [
            'course' => $course,
            'tempImage' => $this::TEMP_DEFAULT_IMAGE_COURSE,
            'hasStarted' => $hasStarted,
            'progress' => $progress
        ]);
    }

    public function joinCourse(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'url' => 'required|string|max:30'
        ]);

        $course = Course::where('url', $validated['url'])
            ->where('status', self::COURSE_STATUS_PUBLISHED)
            ->first();
        if (!$course) {
            abort(404, 'Курс не найден');
        }

        $particient = Participant::where('user_id', Auth::id())->where('course_id', $course->id)->first();
        if ($particient) {
            return redirect(route('course.show', ['courseName' => $course->url]));
        }

        Participant::create([
            'user_id' => Auth::id(),
            'course_id' => $course->id
        ]);

        event(new AdminDashboardUpdated(
            'course',
            'Курс начат',
            Auth::user()->name . " начал курс «{$course->title}»",
            app(AdminDashboardStats::class)->counters(),
            [
                'course_id' => $course->id,
                'course_title' => $course->title,
                'user_name' => Auth::user()->name,
            ],
        ));

        return  redirect(route('course.show', ['courseName' => $course->url]));
    }

    public function getLesson(int $lessonId): JsonResponse
    {
        $lesson = Lesson::query()
            ->with(['blocks' => fn($query) => $query->orderBy('order')])
            ->find($lessonId);
        if (!$lesson) {
            return response()->json([
                'status' => false,
                'message' => 'Не удалось найти урок'
            ], 404);
        }

        $json = $this->converter->convertLessonToJson($lesson);

        return response()->json([
            'status' => true,
            'lesson' => $json
        ]);
    }

    public function showCoursePage(string $url): View|RedirectResponse
    {
        $course = Course::with(['lessons' => fn($query) => $query->orderBy('order')])
            ->where('url', $url)
            ->where('status', self::COURSE_STATUS_PUBLISHED)
            ->first();
        if (!$course) {
            abort(404, 'Курс не найден');
        }

        if (!Participant::where('user_id', Auth::id())->where('course_id', $course->id)->first()) {
            return redirect(route('preview.course.show', ['courseName' => $course->url]));
        }

        $lessonIds = $course->lessons->pluck('id');
        $completedLessonIds = CompletedLesson::where('user_id', Auth::id())
            ->whereIn('course_lesson_id', $lessonIds)
            ->pluck('course_lesson_id')
            ->values();
        $lessonsCount = max($lessonIds->count(), 1);
        $progress = (int) round(($completedLessonIds->count() / $lessonsCount) * 100);

        return view('courses.course', [
            'course' => $course,
            'completedLessonIds' => $completedLessonIds,
            'completedLessonsCount' => $completedLessonIds->count(),
            'progress' => $progress,
        ]);
    }

    public function completeLesson(Lesson $lesson): JsonResponse
    {
        $course = $lesson->course;

        if (!$course || !Participant::where('user_id', Auth::id())->where('course_id', $course->id)->exists()) {
            return response()->json([
                'status' => false,
                'message' => 'Вы не записаны на этот курс',
            ], 403);
        }

        $completedLesson = CompletedLesson::firstOrCreate([
            'user_id' => Auth::id(),
            'course_lesson_id' => $lesson->id,
        ]);

        $lessonIds = $course->lessons()->pluck('id');
        $completedCount = CompletedLesson::where('user_id', Auth::id())
            ->whereIn('course_lesson_id', $lessonIds)
            ->count();
        $lessonsCount = max($lessonIds->count(), 1);
        $courseCompleted = $lessonIds->count() > 0 && $completedCount >= $lessonIds->count();

        if ($completedLesson->wasRecentlyCreated) {
            event(new AdminDashboardUpdated(
                $courseCompleted ? 'course_success' : 'course_progress',
                $courseCompleted ? 'Курс пройден' : 'Прогресс курса',
                $courseCompleted
                    ? Auth::user()->name . " прошел курс «{$course->title}»"
                    : Auth::user()->name . " прошел урок курса «{$course->title}»",
                app(AdminDashboardStats::class)->counters(),
                [
                    'course_id' => $course->id,
                    'course_title' => $course->title,
                    'user_name' => Auth::user()->name,
                    'completed_lessons' => $completedCount,
                    'lessons_count' => $lessonIds->count(),
                    'course_completed' => $courseCompleted,
                ],
            ));
        }

        return response()->json([
            'status' => true,
            'message' => 'Урок отмечен как пройден',
            'lesson_id' => $lesson->id,
            'completed_lessons' => $completedCount,
            'lessons_count' => $lessonIds->count(),
            'progress' => (int) round(($completedCount / $lessonsCount) * 100),
        ]);
    }

    public function createCourse(Request $request): JsonResponse
    {
        $isDraft = $request->input('status') === self::COURSE_STATUS_DRAFT;
        $validated = $request->validate($this->courseValidationRules($isDraft));

        $blockErrors = $isDraft ? [] : $this->validateLessonBlocks($validated['lessons']);

        if (!empty($blockErrors)) {
            return response()->json([
                'status' => false,
                'message' => 'Ошибки валидации блоков',
                'errors' => $blockErrors
            ], 422);
        }

        try {
            DB::beginTransaction();

            $mainInfo = $validated['mainInfo'];
            $course = Course::create([
                'creator_id' => Auth::id(),
                'title' => $mainInfo['title'] ?? 'Черновик курса',
                'description' => $mainInfo['description'] ?? '',
                'url' => filled($mainInfo['url'] ?? null) ? $mainInfo['url'] : $this->makeDraftUrl(),
                'difficulty' => $mainInfo['difficulty'] ?? Difficulty::Beginner->value,
                'time_of_passage_hours' => $mainInfo['time'] ?? 1,
                'intro_img_path' => $mainInfo['coverImage'] ?? null,
                'status' => $isDraft ? self::COURSE_STATUS_DRAFT : self::COURSE_STATUS_PUBLISHED,
            ]);

            $this->syncCourseLessons($course, $validated['lessons'] ?? []);
            $course->categories()->sync($mainInfo['categoryIds'] ?? []);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Курс успешно создан',
                'data' => [
                    'course_id' => $course->id,
                    'url' => $course->url,
                ]
            ], 201);
        } catch (\Exception $ex) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Ошибка при создании курса',
                'error' => config('app.debug') ? $ex->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function updateCourse(Request $request, Course $course): JsonResponse
    {
        $this->ensureCanManageCourse($course);

        $isDraft = $request->input('status') === self::COURSE_STATUS_DRAFT;
        $validated = $request->validate($this->courseValidationRules($isDraft, $course));

        $blockErrors = $isDraft ? [] : $this->validateLessonBlocks($validated['lessons']);

        if (!empty($blockErrors)) {
            return response()->json([
                'status' => false,
                'message' => 'Ошибки валидации блоков',
                'errors' => $blockErrors,
            ], 422);
        }

        try {
            DB::beginTransaction();

            $mainInfo = $validated['mainInfo'];
            $course->update([
                'title' => $mainInfo['title'] ?? $course->title ?? 'Черновик курса',
                'description' => $mainInfo['description'] ?? '',
                'url' => filled($mainInfo['url'] ?? null) ? $mainInfo['url'] : ($course->url ?: $this->makeDraftUrl()),
                'difficulty' => $mainInfo['difficulty'] ?? Difficulty::Beginner->value,
                'time_of_passage_hours' => $mainInfo['time'] ?? 1,
                'intro_img_path' => $mainInfo['coverImage'] ?? $course->intro_img_path,
                'status' => $isDraft ? self::COURSE_STATUS_DRAFT : self::COURSE_STATUS_PUBLISHED,
            ]);

            $course->lessons()->delete();
            $this->syncCourseLessons($course, $validated['lessons'] ?? []);
            $course->categories()->sync($mainInfo['categoryIds'] ?? []);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Курс успешно обновлён',
                'data' => [
                    'course_id' => $course->id,
                    'url' => $course->url,
                ],
            ]);
        } catch (\Exception $ex) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Ошибка при обновлении курса',
                'error' => config('app.debug') ? $ex->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function destroyCourse(Course $course): RedirectResponse
    {
        $this->ensureCanManageCourse($course);

        try {
            DB::beginTransaction();

            $courseTitle = $course->title;
            $course->categories()->detach();
            $course->delete();

            DB::commit();

            return back()->with('success', "Курс «{$courseTitle}» удалён");
        } catch (\Exception $ex) {
            DB::rollBack();

            return back()->with('error', config('app.debug') ? $ex->getMessage() : 'Не удалось удалить курс');
        }
    }

    public function uploadAsset(Request $request): JsonResponse
    {
        $uploadedFile = $request->file('file');

        if ($uploadedFile && !$uploadedFile->isValid()) {
            return response()->json([
                'status' => false,
                'message' => $this->uploadErrorMessage($uploadedFile->getError()),
                'errors' => [
                    'file' => [$this->uploadErrorMessage($uploadedFile->getError())],
                ],
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'type' => ['required', Rule::in(['image', 'video'])],
            'file' => [
                'required',
                'file',
                $request->input('type') === 'video' ? 'max:204800' : 'max:10240',
            ],
        ], [
            'file.required' => 'Выберите файл для загрузки.',
            'file.file' => 'Загружаемый объект должен быть файлом.',
            'file.max' => $request->input('type') === 'video'
                ? 'Видео должно быть не больше 200 МБ.'
                : 'Изображение должно быть не больше 10 МБ.',
        ]);

        $validator->after(function ($validator) use ($request) {
            $file = $request->file('file');
            $type = $request->input('type');

            if (!$file || !in_array($type, ['image', 'video'], true)) {
                return;
            }

            $extension = strtolower((string) $file->getClientOriginalExtension());
            $allowedExtensions = $type === 'video'
                ? ['mp4', 'm4v', 'mov', 'webm', 'ogg', 'ogv', 'avi', 'mpeg', 'mpg', 'mkv']
                : ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];

            if (!in_array($extension, $allowedExtensions, true)) {
                $validator->errors()->add(
                    'file',
                    $type === 'video'
                        ? 'Загрузите видео в формате MP4, M4V, MOV, WEBM, OGG, AVI, MPEG или MKV.'
                        : 'Загрузите изображение в формате JPG, PNG, WEBP, GIF или SVG.'
                );
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Файл не прошёл проверку.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $directory = $validated['type'] === 'video' ? 'courses/videos' : 'courses/images';
        $path = $request->file('file')->store($directory, 'public');

        return response()->json([
            'status' => true,
            'url' => Storage::disk('public')->url($path),
            'path' => $path,
        ]);
    }

    private function uploadErrorMessage(int $errorCode): string
    {
        return match ($errorCode) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Файл слишком большой для текущих настроек PHP. Увеличьте upload_max_filesize и post_max_size или запускайте проект через composer run serve.',
            UPLOAD_ERR_PARTIAL => 'Файл загрузился не полностью. Попробуйте загрузить его ещё раз.',
            UPLOAD_ERR_NO_TMP_DIR => 'На сервере не настроена временная папка для загрузок.',
            UPLOAD_ERR_CANT_WRITE => 'Сервер не смог записать файл на диск.',
            UPLOAD_ERR_EXTENSION => 'PHP-расширение остановило загрузку файла.',
            default => 'Файл не удалось загрузить. Проверьте размер файла и настройки PHP.',
        };
    }

    private function courseValidationRules(bool $isDraft, ?Course $course = null): array
    {
        $urlRule = Rule::unique('course', 'url');
        if ($course) {
            $urlRule->ignore($course->id);
        }

        return [
            'status' => ['nullable', Rule::in([self::COURSE_STATUS_DRAFT, self::COURSE_STATUS_PUBLISHED])],
            'mainInfo' => 'required|array',
            'mainInfo.title' => [$isDraft ? 'nullable' : 'required', 'string', 'max:100'],
            'mainInfo.description' => [$isDraft ? 'nullable' : 'required', 'string', 'max:255'],
            'mainInfo.url' => [$isDraft ? 'nullable' : 'required', 'string', 'max:30', $urlRule],
            'mainInfo.difficulty' => [$isDraft ? 'nullable' : 'required', Rule::in(Difficulty::getAllValues())],
            'mainInfo.time' => 'nullable|numeric|min:1|max:500',
            'mainInfo.coverImage' => 'nullable|string|max:500',
            'mainInfo.categoryIds' => 'nullable|array',
            'mainInfo.categoryIds.*' => 'integer|exists:task_category,id',

            'lessons' => $isDraft ? 'nullable|array' : 'required|array|min:1',
            'lessons.*.id' => 'required|integer',
            'lessons.*.title' => [$isDraft ? 'nullable' : 'required', 'string', 'max:255'],
            'lessons.*.order' => 'required|integer|min:0',
            'lessons.*.blocks' => 'nullable|array',
        ];
    }

    private function ensureCanManageCourse(Course $course): void
    {
        if (Auth::user()?->is_admin) {
            return;
        }

        if ((int) $course->creator_id !== (int) Auth::id()) {
            abort(403, 'Редактировать курс может только его создатель');
        }
    }

    private function syncCourseLessons(Course $course, array $lessons): void
    {
        foreach ($lessons as $index => $lessonData) {
            $lesson = Lesson::create([
                'course_id' => $course->id,
                'title' => filled($lessonData['title'] ?? null) ? $lessonData['title'] : 'Урок ' . ($index + 1),
                'order' => $lessonData['order'] ?? $index + 1,
            ]);

            foreach ($lessonData['blocks'] ?? [] as $blockData) {
                $blockDataArray = json_decode($blockData, true);
                Block::create([
                    'course_lesson_id' => $lesson->id,
                    'type' => $blockDataArray['type'] ?? 'text',
                    'params' => json_encode($blockDataArray['params'] ?? []),
                    'order' => (int) ($blockDataArray['order'] ?? 0),
                ]);
            }
        }
    }

    private function makeDraftUrl(): string
    {
        do {
            $url = 'draft-' . Str::lower(Str::random(10));
        } while (Course::where('url', $url)->exists());

        return $url;
    }

    private function validateLessonBlocks(array $lessons): array
    {
        $errors = [];

        foreach ($lessons as $lessonIndex => $lesson) {
            $lessonNumber = $lessonIndex + 1;

            foreach ($lesson['blocks'] ?? [] as $blockIndex => $block) {
                $block = json_decode($block, true);
                if (!$block) {
                    $errors[] = "Урок {$lessonNumber}, блок " . ($blockIndex + 1) . ": неверный формат";
                    continue;
                }


                $blockNumber = $blockIndex + 1;

                if (empty($block['type'])) {
                    $errors[] = "Урок {$lessonNumber}, блок {$blockNumber}: не указан тип блока";
                    continue;
                }

                $type = TypeBlock::tryFrom($block['type']);
                if (!$type) {
                    $errors[] = "Урок {$lessonNumber}, блок {$blockNumber}: неизвестный тип блока '{$block['type']}'";
                    continue;
                }

                if (!isset($block['params'])) {
                    $errors[] = "Урок {$lessonNumber}, блок {$blockNumber}: отсутствуют параметры блока";
                    continue;
                }

                try {
                    $validator = $this->validationFactory->create($type);
                    $blockJson = json_encode($block['params']);

                    if ($blockErrors = $validator->validate($blockJson)) {
                        foreach ($blockErrors as $error) {
                            $errors[] = "Урок {$lessonNumber}, блок {$blockNumber}: {$error}";
                        }
                    }
                } catch (\Exception $e) {
                    $errors[] = "Урок {$lessonNumber}, блок {$blockNumber}: ошибка валидации - " . $e->getMessage();
                }
            }
        }

        return $errors;
    }

    private function courseEditorPayload(Course $course): array
    {
        return [
            'mainInfo' => [
                'title' => $course->title,
                'description' => $course->description,
                'difficulty' => $course->difficulty,
                'url' => $course->url,
                'time' => $course->time_of_passage_hours,
                'coverImage' => $course->intro_img_path,
                'categoryIds' => $course->categories->pluck('id')->values(),
            ],
            'lessons' => $course->lessons->map(fn(Lesson $lesson) => [
                'id' => $lesson->id,
                'title' => $lesson->title,
                'order' => $lesson->order,
                'blocks' => $lesson->blocks->map(fn(Block $block) => [
                    'id' => $block->id,
                    'type' => $block->type,
                    'order' => $block->order,
                    'params' => json_decode($block->params, true) ?: [],
                ])->values(),
            ])->values(),
        ];
    }

    private function courseTaskOptions(): array
    {
        return Task::query()
            ->with('categories')
            ->orderBy('title')
            ->get()
            ->map(fn(Task $task) => [
                'id' => $task->id,
                'title' => $task->title,
                'rating' => $task->rating,
                'categories' => $task->categories->pluck('name')->values(),
                'url' => url("/task/solution/{$task->id}"),
            ])
            ->values()
            ->all();
    }
}
