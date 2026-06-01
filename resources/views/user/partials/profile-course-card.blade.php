@php
    $difficultyLabel = match ($course->difficulty) {
        'beginner' => 'Для начинающих',
        'intermediate' => 'Средний уровень',
        default => 'Для профессионалов',
    };
@endphp

<article class="profile-course-card">
    <div class="course-card-main">
        <div class="course-cover" aria-hidden="true">
            @if($course->intro_img_path)
                <img src="{{ $course->intro_img_path }}" alt="">
            @else
                <span>Py</span>
            @endif
        </div>
        <div>
            <div class="course-topline">
                <span class="course-level {{ $course->difficulty }}">{{ $difficultyLabel }}</span>
                <span>{{ $course->completed_lessons_count }} из {{ $course->lessons_count }} уроков</span>
            </div>
            <h3>{{ $course->title }}</h3>
            <p>{{ $course->description }}</p>
            @if($course->categories->isNotEmpty())
                <div class="course-tags">
                    @foreach($course->categories->take(3) as $category)
                        <span>{{ $category->name }}</span>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="course-card-footer">
        <div class="progress-wrap" aria-label="Прогресс курса {{ $course->progress }}%">
            <div class="progress-bar">
                <span style="width: {{ min($course->progress, 100) }}%"></span>
            </div>
            <strong>{{ $course->progress }}%</strong>
        </div>
        <a href="{{ route('course.show', ['courseName' => $course->url]) }}">Продолжить</a>
    </div>
</article>
