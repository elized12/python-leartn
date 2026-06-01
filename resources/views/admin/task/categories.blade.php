@extends('layout.admin')

@section('title', 'Категории задач')

@section('content')
    <div class="admin-page-grid">
        <section class="admin-card">
            <h2>Создать категорию</h2>

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

            <form action="{{ route('admin.categories.store') }}" method="POST" class="admin-form">
                @csrf

                <label>
                    Название
                    <input type="text" name="name" value="{{ old('name') }}" placeholder="два указателя" required>
                </label>

                <button type="submit" class="btn btn-primary">Создать категорию</button>
            </form>
        </section>

        <section class="admin-card">
            <h2>Категории</h2>

            <div class="admin-stack">
                @forelse($categories as $category)
                    <article class="admin-list-item compact">
                        <form action="{{ route('admin.categories.update', $category) }}" method="POST" class="admin-form inline-row-form">
                            @csrf
                            @method('PUT')

                            <label>
                                Название
                                <input type="text" name="name" value="{{ $category->name }}" required>
                            </label>

                            <span class="muted">{{ $category->tasks_count }} задач</span>

                            <button type="submit" class="btn btn-success">Сохранить</button>
                        </form>

                        <form action="{{ route('admin.categories.destroy', $category) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">Удалить</button>
                        </form>
                    </article>
                @empty
                    <p class="empty-state">Категорий пока нет.</p>
                @endforelse
            </div>
        </section>
    </div>
@endsection
