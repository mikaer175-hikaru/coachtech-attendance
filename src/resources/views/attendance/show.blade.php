@extends('layouts.app')

@section('css')
  <link rel="stylesheet" href="{{ asset('css/attendance-detail.css') }}">
@endsection

@section('content')

<div class="attendance-detail">
    <h1 class="attendance-detail__heading">勤怠詳細</h1>

    @if (session('error'))
        <p class="attendance-detail__flash attendance-detail__flash--error">{{ session('error') }}</p>
    @endif
    @if (session('success'))
        <p class="attendance-detail__flash attendance-detail__flash--success">{{ session('success') }}</p>
    @endif

    @php
        $isPending = ($attendance->status ?? null) === 'pending';
        $disabled  = $isPending ? 'disabled' : '';
        $date = $attendance->work_date instanceof \Illuminate\Support\Carbon
            ? $attendance->work_date
            : \Illuminate\Support\Carbon::parse($attendance->work_date);
        $dateYear = $date->year . '年';
        $dateMonthDay = $date->isoFormat('M月D日');
    @endphp

    @if($isPending)
      <p class="attendance-detail__flash attendance-detail__flash--info">
        承認待ちのため修正はできません。
      </p>
    @endif

    {{-- フォーム開始：修正申請のPOST --}}
    <form method="POST" action="{{ route('stamp_requests.store', $attendance) }}">
        @csrf

        <section class="attendance-card">
            {{-- 名前 --}}
            <div class="attendance-card__row">
                <div class="attendance-card__th">名前</div>
                <div class="attendance-card__td">
                    {{ $attendance->user->name ?? '—' }}
                </div>
            </div>

            {{-- 日付 --}}
            <div class="attendance-card__row">
                <div class="attendance-card__th">日付</div>
                <div class="attendance-card__td attendance-card__td--split">
                    <span class="pill">{{ $dateYear }}</span>
                    <span class="pill">{{ $dateMonthDay }}</span>
                </div>
            </div>

            {{-- 出勤・退勤（編集可） --}}
            <div class="attendance-card__row">
                <div class="attendance-card__th">出勤・退勤</div>
                <div class="attendance-card__td attendance-card__td--range">
                    <input class="pill-input" type="text" name="start_time"
                           value="{{ old('start_time', $attendance->start_time?->format('H:i')) }}"
                           placeholder="HH:MM" {{ $disabled }}>
                    <span class="attendance-card__tilde">〜</span>
                    <input class="pill-input" type="text" name="end_time"
                           value="{{ old('end_time', $attendance->end_time?->format('H:i')) }}"
                           placeholder="HH:MM" {{ $disabled }}>
                </div>
            </div>
            @error('start_time')
                <p class="attendance-detail__flash attendance-detail__flash--error">{{ $message }}</p>
            @enderror
            @error('end_time')
                <p class="attendance-detail__flash attendance-detail__flash--error">{{ $message }}</p>
            @enderror

            {{-- 休憩（回数分＋空1行） --}}
            @foreach($breakRows as $i => $row)
                <div class="attendance-card__row">
                    <div class="attendance-card__th">休憩{{ $i + 1 }}</div>
                    <div class="attendance-card__td attendance-card__td--range">
                        <input class="pill-input" type="text" name="breaks[{{ $i }}][start]"
                               value="{{ old("breaks.$i.start", $row['start']) }}"
                               placeholder="HH:MM" {{ $disabled }}>
                        <span class="attendance-card__tilde">〜</span>
                        <input class="pill-input" type="text" name="breaks[{{ $i }}][end]"
                               value="{{ old("breaks.$i.end", $row['end']) }}"
                               placeholder="HH:MM" {{ $disabled }}>
                    </div>
                </div>
                @error("breaks.$i.start")
                    <p class="attendance-detail__flash attendance-detail__flash--error">{{ $message }}</p>
                @enderror
                @error("breaks.$i.end")
                    <p class="attendance-detail__flash attendance-detail__flash--error">{{ $message }}</p>
                @enderror
            @endforeach

            {{-- 備考（必須） --}}
            <div class="attendance-card__row attendance-card__row--stack">
                <div class="attendance-card__th">備考</div>
                <div class="attendance-card__td">
                    <textarea class="memo" name="note" {{ $disabled }} required>{{ old('note', $attendance->note) }}</textarea>
                </div>
            </div>
            @error('note')
                <p class="attendance-detail__flash attendance-detail__flash--error">{{ $message }}</p>
            @enderror
        </section>

        <div class="attendance-detail__actions">
            @if(!$isPending)
                <button type="submit" class="btn btn--primary">修正</button>
            @endif
        </div>
    </form>
</div>
@endsection
