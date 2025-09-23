@extends('admin.layouts.admin')

@section('content')
<link rel="stylesheet" href="{{ asset('css/attendance-detail-admin.css') }}">

<article class="detail">
    <header class="detail__header">
        <h1 class="detail__title">
            <span class="detail__title-bar"></span>
            勤怠詳細
        </h1>
    </header>

    @if (session('success'))
        <p class="detail__flash detail__flash--success">{{ session('success') }}</p>
    @endif
    @if ($errors->any())
        <ul class="detail__flash detail__flash--error">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form method="POST" action="{{ route('admin.attendance.update', $attendance) }}" class="detail__card">
        @csrf
        @method('PATCH')

        {{-- 名前 --}}
        <div class="detail__row">
            <div class="detail__th">名前</div>
            <div class="detail__td detail__td--center">
                {{ $attendance->user->name ?? '—' }}
            </div>
        </div>

        {{-- 日付（年／月日を左右に分けるUI） --}}
        <div class="detail__row">
            <div class="detail__th">日付</div>
            <div class="detail__td detail__td--split">
                @php
                    $d = \Carbon\Carbon::parse($attendance->date);
                @endphp
                <span class="detail__date-y">{{ $d->year }}年</span>
                <span class="detail__date-md">{{ $d->format('n月j日') }}</span>
            </div>
        </div>

        {{-- 出勤・退勤 --}}
        <div class="detail__row">
            <div class="detail__th">出勤・退勤</div>
            <div class="detail__td detail__td--range">
                <input type="time" name="start_time" class="detail__time"
                    value="{{ old('start_time', $attendance->start_time ? substr($attendance->start_time,0,5) : '') }}">
                <span class="detail__tilde">〜</span>
                <input type="time" name="end_time" class="detail__time"
                    value="{{ old('end_time', $attendance->end_time ? substr($attendance->end_time,0,5) : '') }}">
            </div>
        </div>

        {{-- 休憩1 --}}
        <div class="detail__row">
            <div class="detail__th">休憩</div>
            <div class="detail__td detail__td--range">
                <input type="time" name="break_start_time" class="detail__time"
                    value="{{ old('break_start_time', $attendance->break_start_time ? substr($attendance->break_start_time,0,5) : '') }}">
                <span class="detail__tilde">〜</span>
                <input type="time" name="break_end_time" class="detail__time"
                    value="{{ old('break_end_time', $attendance->break_end_time ? substr($attendance->break_end_time,0,5) : '') }}">
            </div>
        </div>

        {{-- 備考 --}}
        <div class="detail__row">
            <div class="detail__th">備考</div>
            <div class="detail__td">
                <textarea name="note" rows="3" class="detail__textarea"
                    placeholder="電車遅延のため など">{{ old('note', $attendance->note) }}</textarea>
            </div>
        </div>

        <div class="detail__actions">
            <button type="submit" class="detail__button">修正</button>
        </div>
    </form>
</article>
@endsection
