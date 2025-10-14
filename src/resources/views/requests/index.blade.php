@extends('layouts.app')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/requests.css') }}">
@endsection

@section('content')
<main class="request-list" aria-labelledby="request-list-heading">
    <h1 id="request-list-heading" class="request-list__heading">申請一覧</h1>

    {{-- タブ --}}
    @php $current = $tab === 'approved' ? 'approved' : 'pending'; @endphp
    <nav class="request-list__tabs" aria-label="申請タブ">
        <a class="request-list__tab {{ $current === 'pending' ? 'request-list__tab--active' : '' }}"
           href="{{ route('stamp_requests.index', ['tab' => 'pending']) }}"
           aria-current="{{ $current === 'pending' ? 'page' : 'false' }}">
            承認待ち
        </a>
        <a class="request-list__tab {{ $current === 'approved' ? 'request-list__tab--active' : '' }}"
           href="{{ route('stamp_requests.index', ['tab' => 'approved']) }}"
           aria-current="{{ $current === 'approved' ? 'page' : 'false' }}">
            承認済み
        </a>
    </nav>

    @php
        /** @var \Illuminate\Pagination\LengthAwarePaginator $list */
        $list = $current === 'approved' ? $approved : $pending;
    @endphp

    <div class="request-list__table-wrap" role="region" aria-label="申請一覧テーブル">
        <table class="request-table">
            <thead class="request-table__head">
                <tr class="request-table__row">
                    <th class="request-table__cell request-table__cell--head">状態</th>
                    @if($isAdmin)
                        <th class="request-table__cell request-table__cell--head">名前</th>
                    @endif
                    <th class="request-table__cell request-table__cell--head">対象日時</th>
                    <th class="request-table__cell request-table__cell--head">申請理由</th>
                    <th class="request-table__cell request-table__cell--head">
                        {{ $current === 'pending' ? '申請日時' : '承認日時' }}
                    </th>
                    <th class="request-table__cell request-table__cell--head">詳細</th>
                </tr>
            </thead>
            <tbody class="request-table__body">
                @forelse ($list as $req)
                    <tr class="request-table__row">
                        <td class="request-table__cell">
                            {{ $req->status_label ?? ($current === 'pending' ? '承認待ち' : '承認済み') }}
                        </td>

                        @if($isAdmin)
                            <td class="request-table__cell">
                                {{ $req->user?->name ?? optional(optional($req->attendance)->user)->name ?? '-' }}
                            </td>
                        @endif

                        <td class="request-table__cell">
                            @php $d = optional($req->attendance)->work_date; @endphp
                            {{ $d ? \Illuminate\Support\Carbon::parse($d)->format('Y/m/d') : '-' }}
                        </td>

                        <td class="request-table__cell request-table__cell--ellipsis" title="{{ $req->note }}">
                            {{ $req->note }}
                        </td>

                        <td class="request-table__cell">
                            {{ $current === 'pending'
                                ? optional($req->created_at)->format('Y/m/d')
                                : optional($req->approved_at)->format('Y/m/d') }}
                        </td>

                        <td class="request-table__cell">
                            @if($isAdmin)
                                {{-- 管理者：申請詳細（承認/却下）へ --}}
                                <a class="request-list__link" href="{{ route('admin.stamp_requests.show', $req) }}">詳細</a>
                            @else
                                {{-- 一般：勤怠詳細へ（FN033） --}}
                                <a class="request-list__link" href="{{ route('attendance.show', $req->attendance_id) }}">詳細</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr class="request-table__row">
                        <td class="request-table__cell" colspan="{{ $isAdmin ? 6 : 5 }}">
                            {{ $current === 'pending' ? '承認待ちの申請はありません。' : '承認済みの申請はありません。' }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <nav class="request-list__pagination" aria-label="ページネーション">
        {{ $list->onEachSide(1)->withQueryString()->links() }}
    </nav>
</main>
@endsection
