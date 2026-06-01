<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Курс: Основы Python</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    @vite(['resources/css/courses/course.css', 'resources/css/courses/blocks.css', 'resources/css/shared/markdown.css'])

</head>

<body>
    <button class="mobile-menu-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <div class="sidebar-overlay" onclick="closeSidebar()"></div>

    <div class="course-container">

        <aside class="course-sidebar" id="courseSidebar">
            <div class="sidebar-header">
                <a href="{{ route('courses.show') }}" class="back-to-courses">
                    <i class="fas fa-arrow-left"></i>
                    Все курсы
                </a>
                <h1 class="course-title">{{ $course->title }}</h1>
                <div class="course-meta">
                    <span><i class="far fa-clock"></i> {{ $course->lessons->count() }} уроков</span>
                    <span><i class="fas fa-chart-line"></i>{{ $course->difficulty }}</span>
                </div>
                <div class="course-progress-panel">
                    <div class="course-progress-head">
                        <span>Прогресс</span>
                        <strong id="courseProgressText">{{ $progress }}%</strong>
                    </div>
                    <div class="course-progress-track">
                        <div class="course-progress-fill" id="courseProgressFill" style="width: {{ $progress }}%"></div>
                    </div>
                    <div class="course-progress-caption">
                        <span id="completedLessonsCount">{{ $completedLessonsCount }}</span> из {{ $course->lessons->count() }} уроков
                    </div>
                </div>
            </div>

            <div class="lessons-list">
                @foreach ($course->lessons as $lesson)
                    @php($isCompleted = $completedLessonIds->contains($lesson->id))
                    <div class="lesson-item {{ $isCompleted ? 'completed' : '' }}" data-lesson-id="{{ $lesson->id }}" onclick="selectLesson({{ $lesson->id }})">
                        <div class="lesson-header">
                            <div class="lesson-number">
                                @if($isCompleted)
                                    <i class="fas fa-check"></i>
                                @else
                                    {{ $loop->iteration }}
                                @endif
                            </div>
                            <div class="lesson-title">{{ $lesson->title }}</div>
                        </div>
                        <div class="lesson-meta">
                            <span class="lesson-status">{{ $isCompleted ? 'Пройден' : 'Не пройден' }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </aside>

        <main class="course-content">
            <header class="content-header">
                <div class="lesson-breadcrumb">
                    <span>Основы Python</span>
                    <i class="fas fa-chevron-right" style="font-size: 0.8rem;"></i>
                    <span>Урок 1</span>
                </div>
                <h1 class="lesson-main-title" id="lessonTitle"></h1>
                <div class="lesson-content-meta">
                    <button type="button" class="complete-lesson-btn" id="completeLessonBtn" disabled>
                        <i class="fas fa-check"></i>
                        <span>Урок пройден</span>
                    </button>
                </div>
            </header>

            <div class="lesson-content-main">
            </div>
        </main>
    </div>

    @vite(['resources/js/courses/course.js'])
    <script>
        window.courseProgress = {
            completedLessonIds: @json($completedLessonIds),
            lessonsCount: {{ $course->lessons->count() }},
        };
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/styles/github.min.css">
    <script src="https://cdn.jsdelivr.net/pyodide/v0.24.1/full/pyodide.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/highlight.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/languages/python.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/languages/bash.min.js"></script>
</body>

</html>
