@extends('layout.main')

@section('content')
    <div class="course-main-container">
        <div class="courses-filter-section">
            <div class="filter-header">
                <div>
                    <h2>Черновики курсов</h2>
                    <p class="drafts-subtitle">Курсы, которые ещё не опубликованы и видны только в редакторе.</p>
                </div>
                <div class="filter-actions">
                    <a href="{{ route('courses.create.show') }}" class="btn primary-btn">
                        <i class="fas fa-plus"></i> Новый курс
                    </a>
                    <a href="{{ route('courses.show') }}" class="btn outline-btn">
                        <i class="fas fa-arrow-left"></i> Все курсы
                    </a>
                </div>
            </div>

            <div class="all-courses">
                @forelse ($drafts as $course)
                    <div class="course-card-horizontal draft-course-card">
                        <div class="course-image-sm">
                            <img src="{{ $course->intro_img_path ?: $tempImage }}" alt="{{ $course->title }}">
                            <div class="course-icon-sm">
                                <i class="fas fa-pencil"></i>
                            </div>
                        </div>
                        <div class="course-info">
                            <div class="course-header">
                                <h3 class="course-title">{{ $course->title }}</h3>
                                <div class="course-badges">
                                    <span class="badge draft">Черновик</span>
                                </div>
                            </div>
                            <p class="course-description">{{ $course->description ?: 'Описание пока не заполнено.' }}</p>
                            <div class="course-footer">
                                <div class="course-stats">
                                    <div class="stat">
                                        <i class="fas fa-book-open"></i>
                                        <span>{{ $course->lessons_count }} уроков</span>
                                    </div>
                                    <div class="stat">
                                        <i class="fas fa-clock"></i>
                                        <span>{{ $course->updated_at->format('d.m.Y H:i') }}</span>
                                    </div>
                                </div>
                                <div class="course-actions">
                                    @if(auth()->user()?->is_admin || (int) $course->creator_id === (int) auth()->id())
                                        <a href="{{ route('courses.edit.show', $course) }}" class="btn primary-btn-sm">
                                            <i class="fas fa-pen"></i> Продолжить
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="empty-drafts">
                        <i class="fas fa-folder-open"></i>
                        <h3>Черновиков пока нет</h3>
                        <p>Сохраните курс как черновик, и он появится здесь.</p>
                    </div>
                @endforelse
            </div>

            <div class="pagination-container">
                {{ $drafts->links() }}
            </div>
        </div>
    </div>
@endsection

@push('css')
    @vite(['resources/css/courses/courses.css'])
@endpush
