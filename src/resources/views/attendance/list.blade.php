@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/attendance-list.css') }}">
@endsection

@section('content')
<div class="attendance" role="region" aria-labelledby="att-title">
    {{-- 見出し --}}
    <h1 id="att-title" class="attendance__title">勤怠一覧</h1>

    {{-- 月切り替えナビ（管理者風：左/中央/右） --}}
    <div class="attendance__nav" role="navigation" aria-label="月ナビゲーション">
        <a class="attendance__nav-btn attendance__nav-btn--prev"
            href="{{ route('attendance.list', ['month' => $prevMonth]) }}">
            ← 前月
        </a>

        <div class="attendance__nav-center" aria-label="対象月">
            <span class="attendance__nav-center-icon" aria-hidden="true">📅</span>
            <span class="attendance__nav-center-date">
            {{ $currentMonthLabel }}
            </span>
        </div>

        <a class="attendance__nav-btn attendance__nav-btn--next"
            href="{{ route('attendance.list', ['month' => $nextMonth]) }}">
            翌月 →
        </a>
    </div>

    {{-- 勤怠テーブル（カードUI） --}}
    <table class="attendance__table">
        <thead>
            <tr>
                <th scope="col">日付</th>
                <th scope="col">出勤</th>
                <th scope="col">退勤</th>
                <th scope="col">休憩</th>
                <th scope="col">合計</th>
                <th scope="col">詳細</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $r)
                @php
                    $isToday = \Carbon\Carbon::parse($r['date'])->isToday();
                    $dash = '<span class="attendance__dash">—</span>';
                @endphp
                <tr class="attendance__row {{ $isToday ? 'attendance__row--today' : '' }}">
                    <td class="attendance__cell attendance__cell--date">
                        {{ \Carbon\Carbon::parse($r['date'])->locale('ja')->isoFormat('MM/DD(ddd)') }}
                    </td>
                    <td class="attendance__cell attendance__cell--start">
                        {!! $r['start_hm'] !== '' ? e($r['start_hm']) : $dash !!}
                    </td>
                    <td class="attendance__cell attendance__cell--end">
                        {!! $r['end_hm'] !== '' ? e($r['end_hm']) : $dash !!}
                    </td>
                    <td class="attendance__cell attendance__cell--break">
                        {!! $r['break_hm'] !== '' ? e($r['break_hm']) : $dash !!}
                    </td>
                    <td class="attendance__cell attendance__cell--worked">
                        {!! $r['worked_hm'] !== '' ? e($r['worked_hm']) : $dash !!}
                    </td>
                    <td class="attendance__cell attendance__cell--detail">
                        @if ($r['attendance_id'])
                            <a href="{{ route('attendance.show', $r['attendance_id']) }}" class="attendance__link">詳細</a>
                        @else
                            <span class="attendance__link attendance__link--disabled" aria-disabled="true">詳細</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td class="attendance__cell" colspan="6">勤怠データがありません。</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection

