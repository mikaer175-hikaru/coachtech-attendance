@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/attendance-detail.css') }}">
@endsection

@section('content')
<div class="attendance-detail">
    <h1 class="attendance-detail__heading">勤怠詳細</h1>

    @if (session('error'))
        <p class="attendance-detail__flash attendance-detail__flash--error">
            {{ session('error') }}
        </p>
    @endif

    @if (session('success'))
        <p class="attendance-detail__flash attendance-detail__flash--success">
            {{ session('success') }}
        </p>
    @endif

    @php
        $disabled = $isPending ? 'disabled' : '';

        $date = $attendance->work_date instanceof \Illuminate\Support\Carbon
            ? $attendance->work_date
            : \Illuminate\Support\Carbon::parse($attendance->work_date);
    @endphp

    @if ($isPending)
        <p class="attendance-detail__flash attendance-detail__flash--info">
            承認待ちのため修正はできません。
        </p>
    @endif

    {{-- フォーム開始：修正申請のPOST --}}
    <form method="POST" action="{{ route('stamp_requests.store', ['attendance' => $attendance->id]) }}" autocomplete="off">
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

            {{-- 出勤・退勤（承認待ちは申請値が入る） --}}
            <div class="attendance-card__row">
                <div class="attendance-card__th">出勤・退勤</div>
                <div class="attendance-card__td attendance-card__td--range">
                    @php
                        $startVal = $isPending ? ($displayStart ?? '') : old('start_time', $displayStart ?? '');
                        $endVal   = $isPending ? ($displayEnd   ?? '') : old('end_time',   $displayEnd   ?? '');
                    @endphp

                    <input class="pill-input" type="time" name="start_time"
                        value="{{ $startVal }}" {{ $isPending ? 'disabled' : '' }} autocomplete="off" step="60">
                    <span class="attendance-card__tilde">〜</span>
                    <input class="pill-input" type="time" name="end_time"
                        value="{{ $endVal }}" {{ $isPending ? 'disabled' : '' }} autocomplete="off" step="60">
                </div>
            </div>
            @error('start_time')
                <p class="attendance-detail__flash attendance-detail__flash--error">{{ $message }}</p>
            @enderror
            @error('end_time')
                <p class="attendance-detail__flash attendance-detail__flash--error">{{ $message }}</p>
            @enderror

            {{-- 休憩（回数分＋空1行） --}}
            @foreach ($breakRows as $i => $row)
                <div class="attendance-card__row">
                    <div class="attendance-card__th">休憩{{ $i + 1 }}</div>
                    <div class="attendance-card__td attendance-card__td--range">
                        @php
                            $bStart = $isPending ? ($row['start'] ?? '') : old("breaks.$i.start", $row['start'] ?? '');
                            $bEnd   = $isPending ? ($row['end']   ?? '') : old("breaks.$i.end",   $row['end']   ?? '');
                        @endphp
                        <input class="pill-input" type="time" name="breaks[{{ $i }}][start]"
                                value="{{ $bStart }}" {{ $isPending ? 'disabled' : '' }} autocomplete="off" step="60">
                        〜
                        <input class="pill-input" type="time" name="breaks[{{ $i }}][end]"
                                value="{{ $bEnd }}" {{ $isPending ? 'disabled' : '' }} autocomplete="off" step="60">
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
                    @php
                        $noteVal = $isPending ? ($attendance->note ?? '') : old('note', $attendance->note);
                    @endphp
                    <textarea class="memo" name="note" placeholder="電車遅延のため など" {{ $isPending ? 'disabled' : '' }} required>{{ $noteVal }}</textarea>
                </div>
            </div>
            @error('note')
                <p class="attendance-detail__flash attendance-detail__flash--error">{{ $message }}</p>
            @enderror
        </section>

        {{-- 申請ボタン --}}
        <div class="attendance-detail__actions">
            @if (! $isPending)
                <button type="submit" class="btn btn--primary">修正</button>
            @endif
        </div>
    </form>
</div>
@endsection
