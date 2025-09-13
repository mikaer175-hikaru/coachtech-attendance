@extends('layouts.app')

@section('content')
<div class="attendance">
    <div class="attendance__status">
        {{-- 勤怠ステータス --}}
        <p class="attendance__status-label">{{ $status }}</p>

        {{-- 日付と時刻 --}}
        <p class="attendance__date">{{ $date }}</p>
        <p class="attendance__time">{{ $time }}</p>

        {{-- メッセージ --}}
        @if (session('success'))
            <p class="message message--success">{{ session('success') }}</p>
        @endif

        @if (session('error'))
            <p class="message message--error">{{ session('error') }}</p>
        @endif

        @if ($errors->any())
            <ul class="message message--error">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

        {{-- ボタン表示ロジック --}}
        <div class="attendance__buttons">
            @if (!$attendance)
                {{-- 出勤前 --}}
                <form method="POST" action="{{ route('attendance.start') }}">
                    @csrf
                    <button class="btn btn--black">出勤</button>
                </form>

            @elseif ($attendance->start_time && !$attendance->end_time && !$attendance->break_start_time)
                {{-- 出勤後：退勤・休憩開始ボタン --}}
                <form method="POST" action="{{ route('attendance.end') }}">
                    @csrf
                    <button class="btn btn--black">退勤</button>
                </form>
                <form method="POST" action="{{ route('attendance.break.start') }}">
                    @csrf
                    <button class="btn btn--white">休憩入</button>
                </form>

            @elseif ($attendance->break_start_time && !$attendance->break_end_time)
                {{-- 休憩中：休憩終了ボタンのみ --}}
                <form method="POST" action="{{ route('attendance.break.end') }}">
                    @csrf
                    <button class="btn btn--white">休憩戻</button>
                </form>

            @elseif ($attendance->end_time)
                {{-- 退勤後 --}}
                <p class="attendance__message">お疲れ様でした。</p>
            @endif
        </div>
    </div>
</div>
@endsection
