@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/attendance-register.css') }}">
@endsection

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

        @php
            // 複数休憩：進行中の休憩（break_end が null）を取得
            $ongoingBreak = $attendance?->breaks?->firstWhere('break_end', null);

            // 単一休憩カラムでの進行中判定（後方互換）
            $singleBreakOngoing = $attendance?->break_start_time && !$attendance?->break_end_time;

            // どちらか片方でも「進行中」なら休憩中として扱う
            $isOnBreak = (bool) ($ongoingBreak || $singleBreakOngoing);
        @endphp

        {{-- ボタン表示ロジック --}}
        <div class="attendance__buttons">
            @if (!$attendance)
                {{-- 出勤前 --}}
                <form method="POST" action="{{ route('attendance.start') }}">
                    @csrf
                    <button class="btn btn--black">出勤</button>
                </form>

            @elseif ($attendance->end_time)
                {{-- 退勤後 --}}
                <p class="attendance__message">お疲れ様でした。</p>

            @elseif ($isOnBreak)
                {{-- 休憩中：休憩終了のみ --}}
                <form method="POST" action="{{ route('attendance.break.end') }}">
                    @csrf
                    <button class="btn btn--white">休憩戻</button>
                </form>

            @elseif ($attendance->start_time)
                {{-- 出勤中：退勤 / 休憩入 --}}
                <form method="POST" action="{{ route('attendance.end') }}">
                    @csrf
                    <button class="btn btn--black">退勤</button>
                </form>
                <form method="POST" action="{{ route('attendance.break.start') }}">
                    @csrf
                    <button class="btn btn--white">休憩入</button>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
