@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
@endsection

@section('content')
<div class="attendance">
    <h2 class="attendance__title">勤怠一覧</h2>

    {{-- 月切り替えナビ --}}
    <div class="attendance__nav">
        <form method="GET" action="{{ route('attendance.list') }}" class="attendance__nav-form">
            <button type="submit" name="month" value="{{ $prevMonth }}" class="attendance__nav-btn">← 前月</button>
            <span class="attendance__nav-current">
                <i class="fa-solid fa-calendar-days"></i> {{ $currentMonth }}
            </span>
            <button type="submit" name="month" value="{{ $nextMonth }}" class="attendance__nav-btn">翌月 →</button>
        </form>
    </div>

    {{-- 勤怠テーブル --}}
    <table class="attendance__table">
        <thead>
            <tr>
                <th>日付</th>
                <th>出勤</th>
                <th>退勤</th>
                <th>休憩</th>
                <th>合計</th>
                <th>詳細</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($attendances as $attendance)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($attendance->work_date)->format('m/d(D)') }}</td>
                    <td>{{ $attendance->start_time ? $attendance->start_time->format('H:i') : 'ー' }}</td>
                    <td>{{ $attendance->end_time ? $attendance->end_time->format('H:i') : 'ー' }}</td>
                    <td>{{ $attendance->break_duration ? $attendance->break_duration . '分' : 'ー' }}</td>
                    <td>{{ $attendance->formatted_total_duration ?? 'ー' }}</td>
                    <td><a href="{{ route('attendance.show', $attendance->id) }}" class="attendance__link">詳細</a></td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">勤怠データがありません。</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
