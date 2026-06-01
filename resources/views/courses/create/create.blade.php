@extends('layout.main')

@section('content')
    @php
        $isEdit = $isEdit ?? false;
    @endphp

    <div class="course-create-container">
        <div class="course-create-header">
            <div class="header-content">
                <div class="header-title">
                    <i class="fas {{ $isEdit ? 'fa-pen-to-square' : 'fa-plus-circle' }}"></i>
                    <h1>{{ $isEdit ? 'Редактирование курса' : 'Создание нового курса' }}</h1>
                </div>
                <p class="header-subtitle">
                    {{ $isEdit ? 'Изменяйте уроки и проверяйте результат в предпросмотре перед сохранением' : 'Заполните основную информацию о курсе, чтобы начать добавлять уроки' }}
                </p>
            </div>

            <div class="header-actions">
                <a class="btn outline-btn" href="{{ route('courses.drafts.show') }}">
                    <i class="fas fa-folder-open"></i> Черновики
                </a>
                <button class="btn outline-btn" id="previewBtn">
                    <i class="fas fa-eye"></i> Предпросмотр
                </button>
                <button class="btn success-btn" id="saveDraftBtn">
                    <i class="fas fa-save"></i> Сохранить черновик
                </button>
                <button class="btn primary-btn" id="publishBtn">
                    <i class="fas {{ $isEdit ? 'fa-check' : 'fa-rocket' }}"></i> {{ $isEdit ? 'Сохранить изменения' : 'Опубликовать курс' }}
                </button>
            </div>
        </div>

        <div class="course-create-main">
            <div class="lessons-sidebar">
                <div class="sidebar-header">
                    <div class="sidebar-title">
                        <i class="fas fa-book"></i>
                        <h3>Уроки курса</h3>
                    </div>
                    <div class="lesson-item lesson-main-info active" id="lessonMainInfo" data-lesson-id="0">
                        <div class="lesson-item-header">
                            <div class="lesson-icon">
                                <i class="fas fa-info-circle"></i>
                            </div>
                            <div class="lesson-details">
                                <div class="lesson-title">Основная информация</div>
                                <div class="lesson-status">Обязательно</div>
                            </div>
                        </div>
                    </div>
                    <div class="sidebar-actions">
                        <button class="btn outline-btn full-width-btn" id="addLessonBtn">
                            <i class="fas fa-plus"></i> Добавить урок
                        </button>
                    </div>
                </div>

                <div class="lessons-list" id="lessonsList">
                </div>
            </div>

            <div class="course-editor" id="courseEditor">
                <div class="editor-section active" id="courseInfoSection">
                    <div class="course-info-header">
                        <div class="section-title">
                            <i class="fas fa-info-circle"></i>
                            <h2>Основная информация о курсе</h2>
                        </div>
                        <p class="section-description">
                            Эта информация будет отображаться на главной странице курса и поможет студентам понять, о чем
                            этот курс
                        </p>
                    </div>

                    <div class="course-info-editor">
                        <div class="form-section">
                            <div class="form-section-header">
                                <h3 class="section-title">Название и описание</h3>
                                <div class="required-badge">Обязательно</div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">
                                    Название курса
                                    <span class="required">*</span>
                                </label>
                                <div class="form-hint">Придумайте короткое и ёмкое название, которое отражает суть курса
                                </div>
                                <textarea class="title-input" name="courseTitle" maxlength="100" minlength="2"
                                    placeholder="Например: Основы Python для начинающих"></textarea>
                                <div class="character-counter">
                                    <span id="titleCount">0</span>/100 символов
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Краткое описание</label>
                                <div class="form-hint">Краткое описание, которое будет отображаться в карточке курса
                                    (максимум 250 символов)</div>
                                <textarea class="title-input" maxlength="250" minlength="2" name="courseDescription"
                                    placeholder="Опишите, чему научатся студенты после прохождения курса..."></textarea>
                                <div class="character-counter">
                                    <span id="descriptionCount">0</span>/250 символов
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <div class="form-section-header">
                                <h3 class="section-title">Технические детали</h3>
                                <div class="optional-badge">Опционально</div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Уровень сложности</label>
                                    <div class="form-hint">Выберите подходящий уровень сложности для вашего курса</div>
                                    <div class="difficulty-select">
                                        <div class="difficulty-option active" data-value="beginner">
                                            <div class="difficulty-header">
                                                <span class="difficulty-dot beginner"></span>
                                                <span class="difficulty-name">Для начинающих</span>
                                            </div>
                                            <div class="difficulty-desc">Подходит для новичков без опыта</div>
                                        </div>
                                        <div class="difficulty-option" data-value="intermediate">
                                            <div class="difficulty-header">
                                                <span class="difficulty-dot intermediate"></span>
                                                <span class="difficulty-name">Средний уровень</span>
                                            </div>
                                            <div class="difficulty-desc">Требуются базовые знания</div>
                                        </div>
                                        <div class="difficulty-option" data-value="advanced">
                                            <div class="difficulty-header">
                                                <span class="difficulty-dot advanced"></span>
                                                <span class="difficulty-name">Продвинутый</span>
                                            </div>
                                            <div class="difficulty-desc">Для опытных пользователей</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Продолжительность</label>
                                    <div class="form-hint">Примерное время прохождения курса</div>
                                    <div class="duration-selector">
                                        <div class="duration-input">
                                            <button class="duration-btn minus" type="button">-</button>
                                            <div class="duration-display">
                                                <input type="number" class="duration-value" value="10" min="1" max="500"
                                                    name="courseTime">
                                                <span class="duration-label">часов</span>
                                            </div>
                                            <button class="duration-btn plus" type="button">+</button>
                                        </div>
                                        <div class="duration-hint">От 1 до 500 часов</div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">URL-адрес курса</label>
                                <div class="form-hint">Уникальный адрес для вашего курса. Можно оставить пустым для
                                    автогенерации</div>
                                <div class="slug-input-wrapper">
                                    <div class="slug-prefix">{{ config('app.url') }}/courses/</div>
                                    <input type="text" class="slug-field" placeholder="nazvanie-kursa" id="courseUrl"
                                        maxlength="20">
                                    <button class="btn-sm generate-slug-btn" type="button">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                </div>
                                <div class="character-counter">
                                    <span id="urlCount">0</span>/20 символов
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Темы курса</label>
                                <div class="form-hint">
                                    Выберите категории задач, с которыми связан курс. Потом по ним можно советовать курс ученикам.
                                </div>
                                <div class="course-category-grid">
                                    @forelse($categories as $category)
                                        <label class="course-category-option">
                                            <input type="checkbox" name="courseCategoryIds[]" value="{{ $category->id }}">
                                            <span>{{ $category->name }}</span>
                                        </label>
                                    @empty
                                        <p class="form-hint">Категорий пока нет. Создайте их в админ-панели задач.</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <div class="form-section-header">
                                <h3 class="section-title">Визуальное оформление</h3>
                                <div class="optional-badge">Рекомендуется</div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Обложка курса</label>
                                <div class="form-hint">Загрузите изображение, которое будет отображаться на главной странице
                                    курса. Рекомендуемый размер: 1200×600px</div>

                                <div class="cover-upload-area">
                                    <div class="cover-upload-content">
                                        <div class="cover-preview" id="coverPreview">
                                            <div class="cover-placeholder">
                                                <div class="placeholder-icon">
                                                    <i class="fas fa-cloud-upload-alt"></i>
                                                </div>
                                                <div class="placeholder-text">
                                                    <h4>Перетащите сюда изображение</h4>
                                                    <p>или нажмите для выбора файла</p>
                                                </div>
                                                <div class="placeholder-format">
                                                    <span>JPG, PNG или GIF</span>
                                                    <span>Макс. размер: 5MB</span>
                                                </div>
                                            </div>

                                            <div class="cover-image-preview">
                                            </div>
                                        </div>

                                        <div class="cover-upload-actions">
                                            <button class="btn outline-btn" type="button" id="selectCoverBtn">
                                                <i class="fas fa-folder-open"></i> Выбрать файл
                                            </button>
                                            <button class="btn outline-btn" type="button" id="removeCoverBtn">
                                                <i class="fas fa-trash"></i> Удалить
                                            </button>
                                        </div>
                                    </div>

                                    <input type="file" id="coverImage" accept="image/*" hidden>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="editor-section" id="lessonEditorSection">
                    <div class="lesson-editor">
                        <div class="blocks-container" id="blocksContainer">
                            <div class="empty-blocks-state">
                                <div class="empty-icon">
                                    <i class="fas fa-stream"></i>
                                </div>
                                <h3>Начните создавать урок</h3>
                                <p>Добавьте первый блок, чтобы начать создавать контент</p>
                                <button class="btn primary-btn" id="addFirstBlockBtn">
                                    <i class="fas fa-plus"></i> Добавить первый блок
                                </button>
                            </div>

                        </div>

                        @include('components.toolbar-block')
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="course-preview-modal" id="coursePreviewModal" aria-hidden="true">
        <div class="course-preview-backdrop" data-preview-close></div>
        <div class="course-preview-dialog" role="dialog" aria-modal="true" aria-labelledby="coursePreviewTitle">
            <div class="course-preview-header">
                <div>
                    <span class="course-preview-kicker">Предпросмотр курса</span>
                    <h2 id="coursePreviewTitle">Курс</h2>
                </div>
                <button class="course-preview-close" type="button" data-preview-close aria-label="Закрыть предпросмотр">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="course-preview-layout">
                <aside class="course-preview-sidebar">
                    <div class="course-preview-cover" id="previewCover">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <p id="previewDescription"></p>
                    <div class="course-preview-meta">
                        <span id="previewDifficulty"></span>
                        <span id="previewTime"></span>
                    </div>
                    <div class="course-preview-lessons" id="previewLessons"></div>
                </aside>

                <main class="course-preview-content">
                    <h3 id="previewLessonTitle"></h3>
                    <div class="course-preview-blocks" id="previewBlocks"></div>
                </main>
            </div>
        </div>
    </div>

    <script type="application/json" id="course-editor-payload">@json($editorPayload)</script>
    <script>
        window.courseTaskOptions = @json($taskOptions ?? []);
        window.courseEditorMode = {
            isEdit: @json($isEdit),
            updateUrl: @json($isEdit && $course ? route('courses.update', $course) : null),
        };
    </script>
@endsection

@push('css')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @vite(['resources/css/courses/create.css', 'resources/css/courses/blocks.css', 'resources/css/shared/markdown.css', 'resources/css/components/courses/toolbar-block.css'])
@endpush

@push('js')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/styles/github.min.css">
    <script src="https://cdn.jsdelivr.net/pyodide/v0.24.1/full/pyodide.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/highlight.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/languages/python.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/languages/bash.min.js"></script>
    @vite(['resources/js/courses/create.js'])
@endpush
