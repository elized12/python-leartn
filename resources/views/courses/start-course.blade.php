@extends('layout.main')

@section('content')
    <div class="course-start-container">
        <div class="course-start-header">
            <div class="header-content">
                <div class="header-title">
                    <i class="fas fa-play-circle"></i>
                    <h1>Начать обучение</h1>
                </div>
                <p class="header-subtitle">Вы собираетесь начать прохождение курса. Прогресс будет сохраняться
                    автоматически.</p>
            </div>
        </div>

        <div class="course-start-content">
            <div class="course-start-card">
                <div class="course-card-header">
                    <div class="course-image">
                        <img src="{{ $course->cover_image ?? $tempImage }}" alt="{{ $course->title }}">
                        <div class="course-icon">
                            <i class="fab fa-python"></i>
                        </div>
                    </div>
                    <div class="course-card-info">
                        <div class="course-badge">
                            <span class="badge level {{ $course->difficulty }}">
                                @if ($course->difficulty == 'beginner')
                                    Для начинающих
                                @elseif ($course->difficulty == 'intermediate')
                                    Средний уровень
                                @else
                                    Для профессионалов
                                @endif
                            </span>
                        </div>
                        <h2>{{ $course->title }}</h2>
                        <p class="course-description">{{ $course->description }}</p>

                        <div class="course-meta-grid">
                            <div class="meta-item">
                                <div class="meta-icon">
                                    <i class="fas fa-book-open"></i>
                                </div>
                                <div class="meta-content">
                                    <div class="meta-label">Уроков</div>
                                    <div class="meta-value">{{ count($course->lessons) }}</div>
                                </div>
                            </div>

                            <div class="meta-item">
                                <div class="meta-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="meta-content">
                                    <div class="meta-label">Продолжительность</div>
                                    <div class="meta-value">{{ round($course->time_of_passage_hours) ?? '0' }} часов</div>
                                </div>
                            </div>

                            <div class="meta-item">
                                <div class="meta-icon">
                                    <i class="fas fa-graduation-cap"></i>
                                </div>
                                <div class="meta-content">
                                    <div class="meta-label">Сложность</div>
                                    <div class="meta-value">
                                        @if ($course->difficulty == 'beginner')
                                            Начальный
                                        @elseif ($course->difficulty == 'intermediate')
                                            Средний
                                        @else
                                            Продвинутый
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="meta-item">
                                <div class="meta-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="meta-content">
                                    <div class="meta-label">Студентов</div>
                                    <div class="meta-value">{{ $course->participants_count ?? '0' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="course-start-actions">
                    <form action="{{ route('course.join') }}" method="POST" id="startCourseForm">
                        @csrf
                        <div class="action-buttons">
                            <button type="submit" class="btn primary-btn start-course-btn">
                                <i class="fas fa-rocket"></i>
                                <span>Начать обучение</span>
                            </button>
                        </div>

                        <div class="start-hint">
                            <i class="fas fa-info-circle"></i>
                            <span>Начиная курс, вы подтверждаете согласие с сохранением вашего прогресса</span>
                        </div>

                        <input type="hidden" name="url" value="{{ $course->url }}">
                    </form>
                </div>
            </div>

            <div class="course-start-sidebar">
                <div class="sidebar-card">
                    <div class="sidebar-card-header">
                        <i class="fas fa-graduation-cap"></i>
                        <h4>Как проходит обучение</h4>
                    </div>
                    <div class="sidebar-card-body">
                        <div class="sidebar-step">
                            <div class="step-number">1</div>
                            <div class="step-content">
                                <div class="step-title">Изучайте теорию</div>
                                <div class="step-desc">Читайте материалы и смотрите примеры кода</div>
                            </div>
                        </div>

                        <div class="sidebar-step">
                            <div class="step-number">2</div>
                            <div class="step-content">
                                <div class="step-title">Практикуйтесь</div>
                                <div class="step-desc">Выполняйте задания и пишите код</div>
                            </div>
                        </div>

                        <div class="sidebar-step">
                            <div class="step-number">3</div>
                            <div class="step-content">
                                <div class="step-title">Проверяйте знания</div>
                                <div class="step-desc">Решайте тесты и проверяйте решения</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="sidebar-card">
                    <div class="sidebar-card-header">
                        <i class="fas fa-chart-line"></i>
                        <h4>Ваш прогресс</h4>
                    </div>
                    <div class="sidebar-card-body">
                        <div class="progress-widget">
                            <div class="progress-circle" id="progressCircle" data-progress="0">
                                <svg width="80" height="80" viewBox="0 0 36 36">
                                    <path d="M18 2.0845
                                                                            a 15.9155 15.9155 0 0 1 0 31.831
                                                                            a 15.9155 15.9155 0 0 1 0 -31.831" fill="none"
                                        stroke="#e2e8f0" stroke-width="3" />
                                    <path d="M18 2.0845
                                                                            a 15.9155 15.9155 0 0 1 0 31.831
                                                                            a 15.9155 15.9155 0 0 1 0 -31.831" fill="none"
                                        stroke="#6366f1" stroke-width="3" stroke-dasharray="0, 100" id="progressPath" />
                                </svg>
                                <div class="progress-text">0%</div>
                            </div>
                            <div class="progress-info">
                                <div class="progress-stat">
                                    <div class="stat-value">0</div>
                                    <div class="stat-label">из {{ count($course->lessons) }} уроков</div>
                                </div>
                                <div class="progress-hint">
                                    Прогресс будет обновляться по мере прохождения
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="sidebar-card">
                    <div class="sidebar-card-header">
                        <i class="fas fa-question-circle"></i>
                        <h4>Частые вопросы</h4>
                    </div>
                    <div class="sidebar-card-body">
                        <div class="faq-item">
                            <div class="faq-question">Можно ли проходить в своем темпе?</div>
                            <div class="faq-answer">Да, курс доступен 24/7, вы можете заниматься когда удобно</div>
                        </div>

                        <div class="faq-item">
                            <div class="faq-question">Нужны ли специальные знания?</div>
                            <div class="faq-answer">Курс подходит для начинающих, специальных знаний не требуется</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($hasStarted ?? false)
        <div class="course-already-started-modal">
            <div class="modal-overlay"></div>
            <div class="modal-content">
                <div class="modal-header">
                    <i class="fas fa-book-open"></i>
                    <h3>Продолжить обучение</h3>
                    <button class="modal-close">&times;</button>
                </div>
                <div class="modal-body">
                    <p>Вы уже начали этот курс ранее. Хотите продолжить с того места, где остановились?</p>
                    <div class="modal-progress">
                        <div class="progress-label">Ваш прогресс:</div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: {{ $progress }}%"></div>
                        </div>
                        <div class="progress-value">{{ $progress }}%</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="" class="btn primary-btn">
                        <i class="fas fa-play"></i>
                        Продолжить ({{ $progress }}%)
                    </a>
                    <button class="btn outline-btn restart-course-btn">
                        <i class="fas fa-redo"></i>
                        Начать заново
                    </button>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('css')
    @vite(['resources/css/courses/start-course.css'])
@endpush

@push('js')
    @vite(['resources/js/courses/start-course.js'])
@endpush