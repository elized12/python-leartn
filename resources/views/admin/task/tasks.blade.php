@extends('admin.layout.admin')

@section('title', 'Управление задачами')

@section('content')
    <div class="header-actions">
        <a href="{{ route('admin.task-create.show') }}" class="btn btn-primary"
            style="margin-bottom:15px;text-decoration:none; ">
            <svg class="btn-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Добавить задачу
        </a>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Название</th>
                <th>Сложность</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tasks as $task)
                <tr>
                    <td>{{ $task->id }}</td>
                    <td>{{ $task->title }}</td>
                    <td>
                        @if($task->rating < 1200)
                            <span class="difficulty-easy">Легкая</span>
                        @elseif($task->rating < 1800)
                            <span class="difficulty-medium">Средняя</span>
                        @else
                            <span class="difficulty-hard">Сложная</span>
                        @endif
                    </td>
                    <td>
                        <a class="btn btn-sm btn-warning">
                            <svg class="btn-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                            Изменить
                        </a>
                        <form method="POST" style="display: inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">
                                <svg class="btn-icon" width="14" height="14" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2">
                                    </path>
                                </svg>
                                Удалить
                            </button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{ $tasks->links() }}
@endsection
