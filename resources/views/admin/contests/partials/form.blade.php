@php
    $selectedTaskIds = array_map('intval', old('task_ids', $contest?->tasks?->pluck('id')->all() ?? []));
@endphp

<div class="contest-form-grid">
    <label class="contest-field contest-field-wide">
        <span>Название</span>
        <input class="input" type="text" name="title" required value="{{ old('title', $contest?->title) }}"
            placeholder="Например: Весенний Python Challenge">
    </label>

    <label class="contest-field contest-field-wide">
        <span>Описание</span>
        <textarea class="input" name="description" rows="3"
            placeholder="Коротко опишите правила и цель контеста">{{ old('description', $contest?->description) }}</textarea>
    </label>

    <label class="contest-field">
        <span>Время запуска</span>
        <input class="input" type="datetime-local" name="starts_at"
            value="{{ old('starts_at', $contest?->starts_at?->format('Y-m-d\TH:i')) }}">
    </label>

    <label class="contest-field">
        <span>Длительность, минут</span>
        <input class="input" type="number" min="1" max="43200" name="duration_minutes" required
            value="{{ old('duration_minutes', $contest?->duration_minutes ?? 120) }}">
    </label>

    <div class="contest-field contest-field-wide">
        <span>Задачи контеста</span>
        <div class="contest-task-picker" data-selected-task-ids='@json($selectedTaskIds)'>
            <div class="contest-task-picker-main">
                <div class="contest-task-picker-toolbar">
                    <div class="contest-task-search">
                        <span aria-hidden="true">⌕</span>
                        <input type="search" class="contest-task-search-input" placeholder="Найти задачу по названию или ID">
                    </div>
                    <span class="contest-task-picked-count">0 выбрано</span>
                </div>

                <div class="contest-task-options">
                    @foreach($tasks as $task)
                        @php
                            $difficultyClass = $task->rating < 1200 ? 'easy' : ($task->rating < 1800 ? 'medium' : 'hard');
                        @endphp
                        <button type="button" class="contest-task-option"
                            data-task-id="{{ $task->id }}"
                            data-task-title="{{ e($task->title) }}"
                            data-task-rating="{{ $task->rating }}"
                            data-search="{{ mb_strtolower('#' . $task->id . ' ' . $task->title . ' ' . $task->rating) }}">
                            <span class="contest-task-option-main">
                                <strong>#{{ $task->id }} {{ $task->title }}</strong>
                                <small>Рейтинг {{ $task->rating }}</small>
                            </span>
                            <span class="contest-task-rating {{ $difficultyClass }}">{{ $task->rating }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="contest-task-picked-panel">
                <div class="contest-task-picked-header">
                    <strong>Выбранные задачи</strong>
                    <small>Порядок попадёт в контест</small>
                </div>
                <div class="contest-task-picked-list"></div>
                <div class="contest-task-hidden-inputs"></div>
            </div>
        </div>
        <small>Нажмите на задачу, чтобы добавить или убрать её из контеста.</small>
    </div>

    <label class="contest-toggle contest-field-wide">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $contest?->is_active ?? true))>
        <span>
            <strong>Активен</strong>
            <small>Контест виден пользователям и доступен для участия по расписанию.</small>
        </span>
    </label>
</div>
