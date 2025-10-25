{{-- resources/views/admin/attendance/show.blade.php --}}
@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/attendance-detail-admin.css') }}">
@endsection

@section('content')
    <article class="detail" aria-labelledby="detail-title">
        <header class="detail__header">
            <h1 id="detail-title" class="detail__title">
                <span class="detail__title-bar"></span>
                勤怠詳細
            </h1>
        </header>

        @if (session('success'))
            <p class="detail__flash detail__flash--success">{{ session('success') }}</p>
        @endif

        @if ($errors->any())
            <ul class="detail__flash detail__flash--error" role="alert">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

        <form method="POST" action="{{ route('admin.attendance.update', $attendance) }}" class="detail__card" novalidate>
            @csrf
            @method('PATCH')

            {{-- 名前 --}}
            <div class="detail__row">
                <div class="detail__th">名前</div>
                <div class="detail__td detail__td--center">
                    {{ $attendance->user->name ?? '—' }}
                </div>
            </div>

            {{-- 日付 --}}
            <div class="detail__row">
                <div class="detail__th">日付</div>
                <div class="detail__td detail__td--split">
                    <span class="detail__date--y">{{ $dateYear }}</span>
                    <span class="detail__date--md">{{ $dateMonthDay }}</span>
                </div>
            </div>

            {{-- 出勤・退勤 --}}
            <div class="detail__row">
                <div class="detail__th">出勤・退勤</div>
                <div class="detail__td detail__td--range">
                    <input type="time" name="start_time" class="detail__time"
                        value="{{ old('start_time', optional($attendance->start_time)->format('H:i')) }}">
                    <span class="detail__tilde">〜</span>
                    <input type="time" name="end_time" class="detail__time"
                        value="{{ old('end_time', optional($attendance->end_time)->format('H:i')) }}">
                </div>
            </div>

            {{-- 休憩（回数分＋空1行） --}}
            @foreach ($breakRows as $i => $row)
                <div class="detail__row">
                    <div class="detail__th">休憩{{ $i + 1 }}</div>
                    <div class="detail__td detail__td--range">
                        <input class="detail__time" type="time" name="breaks[{{ $i }}][start]"
                            value="{{ $row['start'] }}" step="60" autocomplete="off">
                        <span class="detail__tilde">〜</span>
                        <input class="detail__time" type="time" name="breaks[{{ $i }}][end]"
                            value="{{ $row['end'] }}" step="60" autocomplete="off">
                    </div>
                </div>
            @endforeach

            {{-- 備考 --}}
            <div class="detail__row">
                <div class="detail__th">備考</div>
                <div class="detail__td">
                    <textarea name="note" rows="3" class="detail__textarea" placeholder="電車遅延のため など">{{ old('note', $attendance->note) }}</textarea>
                </div>
            </div>

            <div class="detail__actions">
                <button type="submit" class="detail__button">修正</button>
            </div>
        </form>
    </article>
@endsection
