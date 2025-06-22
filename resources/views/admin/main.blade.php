@extends('admin.layout.admin')

@section('title', 'Главная')

@section('content')
    <div class="stats-grid">

        @if(session('success'))
            <div class="alert alert-success">
                <button type="button" class="close-alert">&times;</button>
                {{ session('success') }}
            </div>
        @endif

        <div class="stat-card">
            <h3>Всего задач</h3>
            <p>{{ $tasksCount ?? 0 }}</p>
        </div>
        <div class="stat-card">
            <h3>Активных пользователей</h3>
            <p>{{ $activeUsersCount ?? 0 }}</p>
        </div>
        <div class="stat-card">
            <h3>Завершенных курсов</h3>
            <p>{{ $completedCoursesCount ?? 0 }}</p>
        </div>
    </div>

    <h2>Последняя активность</h2>
    <table class="data-table">
        <thead>
            <tr>
                <th>Пользователь</th>
                <th>Действие</th>
                <th>Дата</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
@endsection
