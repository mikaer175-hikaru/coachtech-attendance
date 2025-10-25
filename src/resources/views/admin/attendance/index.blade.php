@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin-attendance-index.css') }}">
@endsection

@section('content')
    <section class="admin-attendance" aria-labelledby="attendance-heading">
        <h1 id="attendance-heading" class="admin-attendance__title">
            {{ $titleDate }}の勤怠
        </h1>

        {{-- 日付ナビ（左：前日 / 中央：日付 / 右：翌日） --}}
        <div class="admin-attendance__nav" role="navigation" aria-label="日付ナビゲーション">
            <a class="admin-attendance__nav-btn admin-attendance__nav-btn--prev"
               href="{{ route('admin.attendance.list', ['date' => $prevDate]) }}">
                ← 前日
            </a>

            <div class="admin-attendance__nav-center" aria-label="対象日">
                <span class="admin-attendance__nav-center-icon" aria-hidden="true">📅</span>
                <span class="admin-attendance__nav-center-date">{{ $targetDate }}</span>
            </div>

            <a class="admin-attendance__nav-btn admin-attendance__nav-btn--next"
               href="{{ route('admin.attendance.list', ['date' => $nextDate]) }}">
                翌日 →
            </a>
        </div>

        @if ($attendances->count() === 0)
            <p class="admin-attendance__empty">該当日の勤怠はありません。</p>
        @else
            <div class="admin-attendance__table-wrap" role="region" aria-label="勤怠一覧テーブル">
                <table class="admin-attendance__table">
                    <thead>
                        <tr>
                            <th>名前</th>
                            <th>出勤</th>
                            <th>退勤</th>
                            <th>休憩</th>
                            <th>合計</th>
                            <th>詳細</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($attendances as $attendance)
                        <tr>
                            <td>{{ $attendance->user->name ?? '' }}</td>

                            {{-- 出勤・退勤 --}}
                            <td>{{ $attendance->start_time?->format('H:i') ?: '' }}</td>
                            <td>{{ $attendance->end_time?->format('H:i')   ?: '' }}</td>

                            {{-- 休憩合計 --}}
                            <td>{{ $attendance->break_hm ?: '—' }}</td>

                            {{-- 実働合計 --}}
                            <td>{{ $attendance->worked_hm ?: '—' }}</td>

                            <td>
                                <a class="admin-attendance__detail"
                                href="{{ route('admin.attendance.show', $attendance) }}">詳細</a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
            </div>

            <nav class="admin-attendance__pagination" aria-label="ページネーション">
                {{ $attendances->links() }}
            </nav>
        @endif
    </section>
@endsection
