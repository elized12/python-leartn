@extends('layout.admin')

@section('title', 'Настройки BKT')

@section('content')
    <div class="admin-page-grid">
        <section class="admin-card">
            <h2>Параметры Bayesian Knowledge Tracing</h2>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('admin.knowledge-tracing.update') }}" method="POST" class="admin-form">
                @csrf
                @method('PUT')

                <div class="admin-stats-grid" style="grid-template-columns: repeat(2, minmax(0, 1fr));">
                    <label>
                        Prior
                        <input type="number" name="prior" min="0" max="1" step="0.01"
                            value="{{ old('prior', $settings['prior']) }}" required>
                        <span class="muted">Начальная вероятность знания новой категории.</span>
                    </label>

                    <label>
                        Learn
                        <input type="number" name="learn" min="0" max="1" step="0.01"
                            value="{{ old('learn', $settings['learn']) }}" required>
                        <span class="muted">Вероятность, что навык усилится после попытки.</span>
                    </label>

                    <label>
                        Guess
                        <input type="number" name="guess" min="0" max="1" step="0.01"
                            value="{{ old('guess', $settings['guess']) }}" required>
                        <span class="muted">Вероятность угадать правильный ответ без знания.</span>
                    </label>

                    <label>
                        Slip
                        <input type="number" name="slip" min="0" max="1" step="0.01"
                            value="{{ old('slip', $settings['slip']) }}" required>
                        <span class="muted">Вероятность ошибиться, даже если навык освоен.</span>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary">Сохранить параметры</button>
            </form>
        </section>

        <section class="admin-card">
            <h2>Как это работает</h2>

            <div class="admin-stack">
                <article class="admin-list-item compact">
                    <strong>Категория = навык</strong>
                    <p class="muted">Если задача связана с категориями «циклы» и «списки», BKT обновляет обе темы.</p>
                </article>

                <article class="admin-list-item compact">
                    <strong>Повторные отправки не накручивают прогресс</strong>
                    <p class="muted">Одна задача влияет на профиль знаний пользователя только один раз.</p>
                </article>

                <article class="admin-list-item compact">
                    <strong>Значения по умолчанию</strong>
                    <p class="muted">
                        Prior {{ $defaults['prior'] }},
                        Learn {{ $defaults['learn'] }},
                        Guess {{ $defaults['guess'] }},
                        Slip {{ $defaults['slip'] }}.
                    </p>
                </article>
            </div>
        </section>
    </div>
@endsection
