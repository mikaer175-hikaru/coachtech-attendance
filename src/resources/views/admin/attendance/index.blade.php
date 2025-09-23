@extends('admin.layouts.admin')

@section('content')
<link rel="stylesheet" href="{{ asset('css/admin-attendance-index.css') }}">

<section class="admin-attendance" aria-labelledby="attendance-heading">
    <h1 id="attendance-heading" class="admin-attendance__title">
        {{ $titleDate }}の勤怠
    </h1>

    {{-- 日付ナビ（左：前日 / 中央：日付 / 右：翌日） --}}
    <div class="admin-attendance__nav">
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
                        @php
                            $in   = $attendance->start_time ? \Carbon\Carbon::parse($attendance->start_time) : null;
                            $out  = $attendance->end_time   ? \Carbon\Carbon::parse($attendance->end_time)   : null;
                            $br   = (int)($attendance->break_minutes ?? 0);
                            $sum  = '';
                            if ($in && $out && $out->greaterThan($in)) {
                                $mins = max($out->diffInMinutes($in) - $br, 0);
                                $sum  = number_format($mins/60, 1); // 例：8.0
                            }
                        @endphp
                        <tr>
                            <td>{{ $attendance->user->name ?? '' }}</td>
                            <td>{{ $in  ? $in->format('H:i')  : '' }}</td>
                            <td>{{ $out ? $out->format('H:i') : '' }}</td>
                            <td>{{ $br > 0 ? floor($br/60).':'.str_pad($br%60, 2, '0', STR_PAD_LEFT) : '0:00' }}</td>
                            <td>{{ $sum }}</td>
                            <td>
                                <a class="admin-attendance__detail"
                                    href="{{ route('admin.attendance.show', $attendance->id) }}">詳細</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <nav class="admin-attendance__pagination" aria-label="ページネーション">
            {{ $attendances->links() }}
        </nav>
    @endif
</section>
@endsection
