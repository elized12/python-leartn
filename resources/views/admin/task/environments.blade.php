@extends('layout.admin')

@section('title', 'Окружения выполнения')

@section('content')
    <div class="admin-page-grid">
        <section class="admin-card">
            <h2>Создать окружение</h2>

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

            <form action="{{ route('admin.environments.store') }}" method="POST" class="admin-form" enctype="multipart/form-data">
                @csrf

                <label>
                    Название
                    <input type="text" name="name" value="{{ old('name', 'Python 3.12 Judge') }}" required>
                </label>

                <label>
                    Docker image
                    <input type="text" name="docker_image_name" value="{{ old('docker_image_name', $defaultImage) }}" required>
                </label>

                <label>
                    Библиотеки редактора
                    <input type="text" name="editor_libraries" value="{{ old('editor_libraries') }}"
                        placeholder="pandas, numpy, openpyxl">
                    <span class="muted">
                        Эти библиотеки будут использоваться только для подсказок редактора.
                    </span>
                </label>

                <label>
                    Dockerfile для кастомного образа
                    <input type="file" name="dockerfile">
                    <span class="muted">
                        Если загрузить Dockerfile, система соберёт образ с указанным Docker image.
                    </span>
                </label>

                <label>
                    Описание
                    <textarea name="description" rows="4">{{ old('description', 'Стандартное окружение Python 3.12 с /usr/bin/time для измерения памяти.') }}</textarea>
                </label>

                <label class="checkbox-row">
                    <input type="checkbox" name="is_active" value="1" checked>
                    Активно
                </label>

                <button type="submit" class="btn btn-primary">Создать окружение</button>
            </form>

            <div class="admin-hint">
                Минимальный Dockerfile для Python:
                <code>FROM python:3.12-slim
RUN apt-get update && apt-get install -y --no-install-recommends time && rm -rf /var/lib/apt/lists/*
WORKDIR /workspace</code>
                Стандартный образ можно собрать командой:
                <code>php artisan judge:install-python</code>
                Образ для анализа данных:
                <code>php artisan judge:install-python-pandas</code>
            </div>

            <div class="preset-actions">
                <form action="{{ route('admin.environments.install-default') }}" method="POST" class="install-default-form">
                    @csrf
                    <button type="submit" class="btn btn-success">Собрать стандартный Python-образ</button>
                </form>

                <form action="{{ route('admin.environments.install-pandas') }}" method="POST" class="install-default-form">
                    @csrf
                    <button type="submit" class="btn btn-primary">Собрать Python pandas-образ</button>
                </form>
            </div>
        </section>

        <section class="admin-card">
            <h2>Окружения</h2>

            <div class="admin-stack">
                @forelse($environments as $environment)
                    <article class="admin-list-item">
                        <form action="{{ route('admin.environments.update', $environment) }}" method="POST" class="admin-form inline-edit-form">
                            @csrf
                            @method('PUT')

                            <div class="admin-list-header">
                                <strong>{{ $environment->name }}</strong>
                                <span>{{ $environment->tasks_count }} задач</span>
                            </div>

                            <label>
                                Название
                                <input type="text" name="name" value="{{ old("environment_{$environment->id}_name", $environment->name) }}" required>
                            </label>

                            <label>
                                Docker image
                                <input type="text" name="docker_image_name" value="{{ $environment->docker_image_name }}" required>
                            </label>

                            <label>
                                Библиотеки редактора
                                <input type="text" name="editor_libraries"
                                    value="{{ collect($environment->editor_libraries ?? [])->join(', ') }}"
                                    placeholder="pandas, numpy, openpyxl">
                            </label>

                            <label>
                                Описание
                                <textarea name="description" rows="3">{{ $environment->description }}</textarea>
                            </label>

                            <label class="checkbox-row">
                                <input type="checkbox" name="is_active" value="1" @checked($environment->is_active)>
                                Активно
                            </label>

                            <div class="admin-actions">
                                <button type="submit" class="btn btn-success">Сохранить</button>
                            </div>
                        </form>

                        <form action="{{ route('admin.environments.destroy', $environment) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            @if($environment->tasks_count > 0)
                                <p class="danger-hint">
                                    При удалении окружения будут удалены задачи, которые его используют: {{ $environment->tasks_count }}.
                                </p>
                            @endif
                            <button type="submit" class="btn btn-danger"
                                onclick="return confirm('Удалить окружение {{ $environment->name }}? Все задачи, которые используют это окружение, тоже будут удалены.')">
                                Удалить
                            </button>
                        </form>
                    </article>
                @empty
                    <p class="empty-state">Окружений пока нет.</p>
                @endforelse
            </div>
        </section>
    </div>
@endsection
