@extends('admin.layouts.admin')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/staff-list.css') }}">
@endsection

@section('content')
    <section class="staff-list" aria-labelledby="staff-list-title">
        <div class="staff-list__header">
            <h1 id="staff-list-title" class="staff-list__heading">スタッフ一覧</h1>

            {{-- 月セレクタ（YYYY-MM）。変更→同画面リロード --}}
            <form class="staff-list__month-form" method="get" action="{{ route('admin.staff.list') }}">
                <label class="staff-list__month-label" for="month">対象月</label>
                <input
                    class="staff-list__month-input"
                    type="month"
                    id="month"
                    name="month"
                    value="{{ $month }}"
                    required
                    aria-label="対象月">
                <button class="staff-list__month-button" type="submit">変更</button>
            </form>
        </div>

        @if (session('success'))
            <p class="staff-list__flash staff-list__flash--success">{{ session('success') }}</p>
        @endif
        @if (session('error'))
            <p class="staff-list__flash staff-list__flash--error">{{ session('error') }}</p>
        @endif

        <section class="staff-list__table-wrap" aria-label="スタッフ一覧テーブル">
            <table class="staff-list__table">
                <thead class="staff-list__thead">
                    <tr class="staff-list__row">
                        <th class="staff-list__cell staff-list__cell--head">名前</th>
                        <th class="staff-list__cell staff-list__cell--head">メールアドレス</th>
                        <th class="staff-list__cell staff-list__cell--head staff-list__cell--action">月次勤怠</th>
                    </tr>
                </thead>
                <tbody class="staff-list__tbody">
                    @forelse ($users as $user)
                        <tr class="staff-list__row">
                            <td class="staff-list__cell">{{ $user->name }}</td>
                            <td class="staff-list__cell">{{ $user->email }}</td>
                            <td class="staff-list__cell staff-list__cell--action">
                                <a class="staff-list__detail-link"
                                   href="{{ url("/admin/attendance/staff/{$user->id}?month={$month}") }}">
                                    詳細
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr class="staff-list__row">
                            <td class="staff-list__cell" colspan="3">該当するスタッフがいません。</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <nav class="staff-list__pagination" aria-label="ページネーション">
            {{ $users->links() }}
        </nav>
    </section>
@endsection
