@extends('layout.main')

@section('content')
    <div class="course-main-container">

        <div class="courses-hero">
            <div class="hero-content">
                <h1>Курсы по программированию</h1>
                <p class="hero-subtitle">Изучайте программирование шаг за шагом через интерактивные уроки и практические
                    задания</p>
                <div class="hero-stats">
                    <div class="stat">
                        <div class="stat-number">{{ $countCourses }}</div>
                        <div class="stat-label">Курсов</div>
                    </div>
                    <div class="stat">
                        <div class="stat-number">{{ $countCompletedLessons }}</div>
                        <div class="stat-label">Уроков</div>
                    </div>
                </div>
            </div>
            <div class="hero-illustration">
                <i class="fas fa-graduation-cap"></i>
            </div>
        </div>

        @if(!$popularCourses->empty())
            <div class="featured-courses">
                <div class="section-header">
                    <h2>Популярные курсы</h2>
                </div>

                <div class="courses-grid" id="coursesView">
                    @foreach ($popularCourses as $course)
                        <div class="course-card">
                            <div class="course-badge">
                                <span class="badge level {{ $course->difficulty }}">
                                    @if ($course->difficulty == 'beginner')
                                        Для начинающих
                                    @elseif ($course->diffculty == 'intermediate')
                                        Средний уровень
                                    @else
                                        Для профессионалов
                                    @endif</span>
                            </div>
                            <div class="course-image">
                                <img src="{{ $tempImage }}">
                                <div class="course-icon">
                                    <i class="fas fa-python"></i>
                                </div>
                            </div>
                            <div class="course-content">
                                <h3 class="course-title">{{ $course->title }}</h3>
                                <p class="course-description">{{ $course->description }}
                                </p>
                                <div class="course-meta">
                                    <div class="meta-item">
                                        <i class="fas fa-book-open"></i>
                                        <span>{{ $course->lessons_count }} уроков</span>
                                    </div>
                                    <div class="meta-item">
                                        <i class="fas fa-clock"></i>
                                        <span>{{ $course->time_of_passage_hours }} часа</span>
                                    </div>
                                    <div class="meta-item">
                                        <i class="fas fa-users"></i>
                                        <span>{{ $course->participants_count }} студентов</span>
                                    </div>
                                </div>
                                <div class="course-progress">
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: 0%"></div>
                                    </div>
                                    <span class="progress-text">Не начат</span>
                                </div>
                                <a href="{{ route('preview.course.show', ['courseName' => $course->url]) }}"
                                    class="btn outline-btn full-width">
                                    <i class="fas fa-info-circle"></i> Подробнее
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>

            </div>
        @endif

        @if(session('success'))
            <div class="course-flash-message success">
                <i class="fas fa-check-circle"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="course-flash-message error">
                <i class="fas fa-exclamation-circle"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <div class="courses-filter-section">
            <div class="filter-header">
                <h2>{{ $activeFilter === 'my' ? 'Мои курсы' : 'Все курсы' }}</h2>
                <div class="filter-actions">
                    <a href="{{ route('courses.show') }}"
                        class="btn {{ $activeFilter === 'my' ? 'outline-btn' : 'primary-btn' }}"
                        style="margin-right: 15px;">
                        <i class="fas fa-layer-group"></i> Все
                    </a>
                    <a href="{{ route('courses.show', ['filter' => 'my']) }}"
                        class="btn {{ $activeFilter === 'my' ? 'primary-btn' : 'outline-btn' }}"
                        style="margin-right: 15px;">
                        <i class="fas fa-user-graduate"></i> Мои курсы
                    </a>
                    @if(auth()->user()?->is_admin)
                    @if(auth()->user()?->is_admin)
                        <a href="{{ route('courses.create.show') }}" class="btn primary-btn" style="margin-right: 15px;">
                            <i class="fas fa-plus"></i> Создать курс
                        </a>
                        <a href="{{ route('courses.drafts.show') }}" class="btn outline-btn" style="margin-right: 15px;">
                            <i class="fas fa-folder-open"></i> Черновики
                        </a>
                    @endif
                    @endif
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Поиск курсов...">
                    </div>
                </div>
            </div>

            <div class="all-courses">
                @forelse ($courses as $course)
                    <div class="course-card-horizontal">
                        <div class="course-image-sm">
                            <img src="{{ $tempImage }}" alt="Python">
                            <div class="course-icon-sm">
                                <i class="fab fa-python"></i>
                            </div>
                        </div>
                        <div class="course-info">
                            <div class="course-header">
                                <h3 class="course-title">{{ $course->title }}</h3>
                                <div class="course-badges">
                                    <span class="badge level {{ $course->difficulty }}">
                                        @if ($course->difficulty == 'beginner')
                                            Для начинающих
                                        @elseif ($course->diffculty == 'intermediate')
                                            Средний уровень
                                        @else
                                            Для профессионалов
                                        @endif
                                    </span>
                                    <span class="badge popular">Популярный</span>
                                </div>
                            </div>
                            <p class="course-description">{{ $course->description }}
                            </p>
                            @if($course->categories->isNotEmpty())
                                <div class="course-topic-list">
                                    @foreach($course->categories as $category)
                                        <span>{{ $category->name }}</span>
                                    @endforeach
                                </div>
                            @endif
                            <div class="course-footer">
                                <div class="course-stats">
                                    <div class="stat">
                                        <i class="fas fa-book-open"></i>
                                        <span>{{ $course->lessons_count }} урока</span>
                                    </div>
                                    <div class="stat">
                                        <i class="fas fa-clock"></i>
                                        <span>{{ round($course->time_of_passage_hours) }} часа</span>
                                    </div>
                                    <div class="stat">
                                        <i class="fas fa-users"></i>
                                        <span>{{ round($course->count_participants) }} студентов</span>
                                    </div>
                                </div>
                                <div class="course-actions">
                                    <div class="course-progress-sm">
                                        <div class="progress-bar-sm">
                                            <div class="progress-fill-sm" style="width: {{ $course->progress . '%' }}"></div>
                                        </div>
                                        <span>{{ round($course->progress) }}%</span>
                                    </div>
                                    <a href="{{ route('preview.course.show', ['courseName' => $course->url]) }}"
                                        class="btn primary-btn-sm">
                                        <i class="fas fa-play"></i> Открыть
                                    </a>
                                    @if(auth()->user()?->is_admin || (int) $course->creator_id === (int) auth()->id())
                                        <a href="{{ route('courses.edit.show', $course) }}" class="btn outline-btn-sm">
                                            <i class="fas fa-pen"></i> Редактировать
                                        </a>
                                        <form action="{{ route('courses.destroy', $course) }}" method="POST"
                                            onsubmit="return confirm('Удалить курс? Это действие нельзя отменить.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn danger-btn-sm">
                                                <i class="fas fa-trash"></i> Удалить
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="empty-drafts">
                        <i class="fas fa-book-open"></i>
                        <h3>{{ $activeFilter === 'my' ? 'Вы пока не записаны на курсы' : 'Курсов пока нет' }}</h3>
                        <p>{{ $activeFilter === 'my' ? 'Откройте любой курс и начните обучение, чтобы он появился здесь.' : 'Создайте первый опубликованный курс.' }}
                        </p>
                    </div>
                @endforelse
            </div>

            <div class="pagination-container">
                <div class="pagination-wrapper">
                    <nav>
                        <ul class="pagination">
                            {{ $courses->links() }}
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('css')
    @vite(['resources/css/courses/courses.css'])
@endpush

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const progressBars = document.querySelectorAll('.progress-fill');
            progressBars.forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0%';
                setTimeout(() => {
                    bar.style.width = width;
                }, 300);
            });
        });
    </script>
@endpush
