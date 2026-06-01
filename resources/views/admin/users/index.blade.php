@extends('layout.admin')

@section('title', 'Пользователи')

@section('content')
    <div class="admin-hero">
        <div>
            <span class="eyebrow">Администрирование</span>
            <h2>Пользователи</h2>
            <p>Просматривайте профили, блокируйте доступ и удаляйте аккаунты.</p>
        </div>
    </div>

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

    <div class="stats-grid users-stats-grid">
        <div class="stat-card">
            <span class="stat-label">Всего пользователей</span>
            <strong>{{ $usersCount }}</strong>
        </div>
        <div class="stat-card accent-amber">
            <span class="stat-label">Заблокировано</span>
            <strong>{{ $blockedCount }}</strong>
        </div>
        <div class="stat-card accent-green">
            <span class="stat-label">Администраторов</span>
            <strong>{{ $adminsCount }}</strong>
        </div>
    </div>

    <section class="admin-card users-toolbar">
        <form action="{{ route('admin.users.index') }}" method="GET" class="users-filter-form">
            <label>
                Поиск
                <input type="text" name="search" value="{{ $search }}" placeholder="Имя или email">
            </label>

            <label>
                Статус
                <select name="status">
                    <option value="">Все</option>
                    <option value="active" @selected($status === 'active')>Активные</option>
                    <option value="blocked" @selected($status === 'blocked')>Заблокированные</option>
                </select>
            </label>

            <button type="submit" class="btn btn-primary">Найти</button>
            <a href="{{ route('admin.users.index') }}" class="btn btn-light">Сбросить</a>
        </form>
    </section>

    <table class="data-table users-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Пользователь</th>
                <th>Роль</th>
                <th>Статус</th>
                <th>Регистрация</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $user)
                <tr class="{{ $user->is_blocked ? 'is-blocked' : '' }}">
                    <td>{{ $user->id }}</td>
                    <td>
                        <div class="user-cell">
                            <div class="user-avatar-sm">{{ mb_substr($user->name, 0, 1) }}</div>
                            <div>
                                <strong>{{ $user->name }}</strong>
                                <span>{{ $user->email }}</span>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="status-badge {{ $user->is_admin ? 'is-admin' : '' }}">
                            {{ $user->is_admin ? 'Админ' : 'Пользователь' }}
                        </span>
                    </td>
                    <td>
                        <span class="status-badge {{ $user->is_blocked ? 'is-blocked' : 'is-active' }}">
                            {{ $user->is_blocked ? 'Заблокирован' : 'Активен' }}
                        </span>
                    </td>
                    <td>{{ $user->created_at?->format('d.m.Y H:i') }}</td>
                    <td>
                        <div class="users-actions">
                            <a href="{{ route('user.profile', $user) }}" class="btn btn-sm btn-primary" style="text-decoration:none;">
                                Профиль
                            </a>

                            <form action="{{ route('admin.users.toggle-block', $user) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="btn btn-sm {{ $user->is_blocked ? 'btn-success' : 'btn-warning' }}">
                                    {{ $user->is_blocked ? 'Разблокировать' : 'Заблокировать' }}
                                </button>
                            </form>

                            <form action="{{ route('admin.users.destroy', $user) }}" method="POST"
                                onsubmit="return confirm('Удалить пользователя {{ $user->name }}? Это действие нельзя отменить.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">Удалить</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="empty-state">Пользователи не найдены.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{ $users->links() }}
@endsection
