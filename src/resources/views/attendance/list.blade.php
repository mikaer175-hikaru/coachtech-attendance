@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/attendance-list.css') }}">
@endsection

@section('content')
<div class="attendance" role="region" aria-labelledby="att-title">
    {{-- 見出し --}}
    <h2 id="att-title" class="attendance__title">勤怠一覧</h2>

    {{-- 月切り替えナビ（白いピル型バー） --}}
    <nav class="attendance__nav" aria-label="月切り替え">
        <form method="GET" action="{{ route('attendance.list') }}" class="attendance__nav-form">
            {{-- 矢印はCSSの::beforeで出すのでテキストは「前月」「翌月」だけにしておく --}}
            <button type="submit"
                    name="month"
                    value="{{ $prevMonth }}"
                    class="attendance__nav-btn attendance__nav-btn--prev"
                    aria-label="前月へ">
                前月
            </button>

            {{-- 年月はtime要素で意味付け（YYYY-MMフォーマットをdatetimeに入れる） --}}
            <span class="attendance__nav-current" aria-live="polite">
                <time datetime="{{ $currentMonth }}">{{ $currentMonth }}</time>
            </span>

            <button type="submit"
                    name="month"
                    value="{{ $nextMonth }}"
                    class="attendance__nav-btn attendance__nav-btn--next"
                    aria-label="翌月へ">
                翌月
            </button>
        </form>
    </nav>

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
                        {{ \Carbon\Carbon::parse($r['date'])->isoFormat('MM/DD(ddd)') }}
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

