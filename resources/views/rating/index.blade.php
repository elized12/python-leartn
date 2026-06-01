@extends('layout.main')

@section('title', 'Рейтинг учеников')

@push('css')
    @vite(['resources/css/rating/rating.css'])
@endpush

@section('content')
    <div class="rating-page">
        <section class="rating-hero">
            <div>
                <span class="rating-kicker">Leaderboard</span>
                <h1>Рейтинг учеников</h1>
                <p>Очки начисляются за уникально решенные задачи. Чем сложнее задача и чем меньше ошибок до Accepted, тем больше вклад в рейтинг.</p>
            </div>

            @if($currentUserRating)
                <div class="my-rating-card">
                    <span>Ваше место</span>
                    <strong>#{{ $currentUserRating->rank }}</strong>
                    <p>{{ $currentUserRating->score }} очков · {{ $currentUserRating->solved_tasks }} задач</p>
                </div>
            @else
                <div class="my-rating-card">
                    <span>Ваше место</span>
                    <strong>—</strong>
                    <p>Решите первую задачу, чтобы попасть в рейтинг.</p>
                </div>
            @endif
        </section>

        @if($leaderboard->isNotEmpty())
            <section class="podium-grid" aria-label="Топ рейтинга">
                @foreach($topRatings as $rating)
                    <article class="podium-card rank-{{ $rating->rank }}">
                        <div class="rank-medal">#{{ $rating->rank }}</div>
                        <div class="podium-avatar">{{ mb_strtoupper(mb_substr($rating->user->name, 0, 1)) }}</div>
                        <a href="{{ route('user.profile', ['userId' => $rating->user->id]) }}">{{ $rating->user->name }}</a>
                        <strong>{{ $rating->score }} очков</strong>
                        <div class="podium-meta">
                            <span>{{ $rating->solved_tasks }} задач</span>
                            <span>{{ $rating->first_try_solutions }} с первой</span>
                        </div>
                    </article>
                @endforeach
            </section>

            <section class="rating-table-card">
                <div class="table-heading">
                    <div>
                        <span class="rating-kicker">Все участники</span>
                        <h2>Таблица рейтинга</h2>
                    </div>
                    <div class="formula-note">База: rating / 10 · штраф 8% за ошибку до Accepted</div>
                </div>

                <div class="rating-table">
                    <div class="rating-row rating-head">
                        <span>Место</span>
                        <span>Ученик</span>
                        <span>Очки</span>
                        <span>Задачи</span>
                        <span>Попытки</span>
                        <span>Уровни</span>
                    </div>

                    @foreach($leaderboard as $rating)
                        <div class="rating-row {{ auth()->id() === $rating->user->id ? 'is-current-user' : '' }}">
                            <span class="rank-cell">#{{ $rating->rank }}</span>
                            <a class="user-cell" href="{{ route('user.profile', ['userId' => $rating->user->id]) }}">
                                <span>{{ mb_strtoupper(mb_substr($rating->user->name, 0, 1)) }}</span>
                                <strong>{{ $rating->user->name }}</strong>
                            </a>
                            <span class="score-cell">{{ $rating->score }}</span>
                            <span>{{ $rating->solved_tasks }}</span>
                            <span>{{ $rating->average_attempts }} ср.</span>
                            <span class="difficulty-stack">
                                <b class="easy">{{ $rating->easy_solved }}</b>
                                <b class="medium">{{ $rating->medium_solved }}</b>
                                <b class="hard">{{ $rating->hard_solved }}</b>
                            </span>
                        </div>
                    @endforeach
                </div>
            </section>
        @else
            <section class="empty-rating">
                <i class="fas fa-ranking-star"></i>
                <h2>Рейтинг пока пуст</h2>
                <p>Первый Accepted сразу создаст первую строчку таблицы.</p>
                <a href="{{ route('tasks.show') }}">Перейти к задачам</a>
            </section>
        @endif
    </div>
@endsection
