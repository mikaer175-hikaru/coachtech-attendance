{{-- resources/views/admin/attendance/staff-index.blade.php --}}
@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin-staff-month.css') }}">
@endsection

@section('content')
    <section class="staff-month" aria-labelledby="staff-month-title">
        <header class="staff-month__header">
            <h1 id="staff-month-title" class="staff-month__title">{{ $user->name }}さんの勤怠</h1>

            <div class="staff-month__meta">
                <p class="staff-month__user">{{ $user->email }}</p>

                {{-- 月ナビ（前月／対象月／翌月） --}}
                <nav class="staff-month__nav" aria-label="月ナビゲーション">
                    <a class="staff-month__nav-btn"
                       href="{{ route('admin.attendance.staff.index', ['id' => $user->id, 'month' => $prev]) }}">‹ 前月</a>
                    <span class="staff-month__month" aria-label="対象月">{{ $month }}</span>
                    <a class="staff-month__nav-btn"
                       href="{{ route('admin.attendance.staff.index', ['id' => $user->id, 'month' => $next]) }}">翌月 ›</a>
                </nav>
            </div>
        </header>

        <div class="staff-month__table-wrapper" role="region" aria-label="月次勤怠テーブル">
            <table class="staff-month__table">
                <thead class="staff-month__thead">
                    <tr class="staff-month__tr">
                        <th class="staff-month__th staff-month__th--date">日付</th>
                        <th class="staff-month__th staff-month__th--time">出勤</th>
                        <th class="staff-month__th staff-month__th--time">退勤</th>
                        <th class="staff-month__th staff-month__th--time">休憩</th>
                        <th class="staff-month__th staff-month__th--time">合計</th>
                        <th class="staff-month__th staff-month__th--action">詳細</th>
                    </tr>
                </thead>
                <tbody class="staff-month__tbody">
                    @foreach ($rows as $r)
                        <tr class="staff-month__tr">
                            <td class="staff-month__td staff-month__td--date">{{ $r['date'] }}</td>
                            <td class="staff-month__td staff-month__td--time">{{ $r['start_hm'] }}</td>
                            <td class="staff-month__td staff-month__td--time">{{ $r['end_hm'] }}</td>
                            <td class="staff-month__td staff-month__td--time">{{ $r['break_hm'] }}</td>
                            <td class="staff-month__td staff-month__td--time">{{ $r['worked_hm'] }}</td>
                            <td class="staff-month__td staff-month__td--action">
                                @if ($r['attendance_id'])
                                    <a class="staff-month__detail"
                                       href="{{ route('admin.attendance.show', $r['attendance_id']) }}">詳細</a>
                                @else
                                    <span class="staff-month__detail staff-month__detail--disabled">詳細</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- CSV出力 --}}
        <div class="staff-month__actions">
            <a class="staff-month__csv"
               href="{{ route('admin.attendance.staff.csv', ['id' => $user->id, 'month' => $month]) }}">
                CSV出力
            </a>
        </div>
    </section>
@endsection
