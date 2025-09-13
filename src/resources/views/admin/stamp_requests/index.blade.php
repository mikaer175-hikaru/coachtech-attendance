@extends('admin.layouts.admin')

@section('content')
<section class="request-list">
    <h1 class="request-list__heading">申請一覧</h1>

    {{-- タブ（サーバーサイド切替。リンクで ?tab= を変更） --}}
    <nav class="request-list__tabs" aria-label="申請タブ">
        @php $current = $tab ?? 'pending'; @endphp
        <a class="request-list__tab {{ $current==='pending' ? 'request-list__tab--active' : '' }}"
            href="{{ route('requests.index', ['tab' => 'pending']) }}"
            aria-current="{{ $current==='pending' ? 'page' : 'false' }}">
            承認待ち
        </a>
        <a class="request-list__tab {{ $current==='approved' ? 'request-list__tab--active' : '' }}"
            href="{{ route('requests.index', ['tab' => 'approved']) }}"
            aria-current="{{ $current==='approved' ? 'page' : 'false' }}">
            承認済み
        </a>
    </nav>

    @php
        $isPending = ($current === 'pending');
        /** @var \Illuminate\Pagination\LengthAwarePaginator $list */
        $list = $isPending ? $pending : $approved;
    @endphp

    <div class="request-list__table-wrap">
        <table class="request-table">
            <thead class="request-table__head">
                <tr class="request-table__row">
                    <th class="request-table__cell request-table__cell--head">状態</th>
                    <th class="request-table__cell request-table__cell--head">名前</th>
                    <th class="request-table__cell request-table__cell--head">対象日時</th>
                    <th class="request-table__cell request-table__cell--head">申請理由</th>
                    <th class="request-table__cell request-table__cell--head">申請日時</th>
                    <th class="request-table__cell request-table__cell--head">詳細</th>
                </tr>
            </thead>
            <tbody class="request-table__body">
            @forelse ($list as $req)
                <tr class="request-table__row">
                    <td class="request-table__cell">
                        {{ $isPending ? '承認待ち' : '承認済み' }}
                    </td>
                    <td class="request-table__cell">
                        {{ optional(optional($req->attendance)->user)->name ?? '-' }}
                    </td>
                    <td class="request-table__cell">
                        @php
                            $d = optional($req->attendance)->work_date;
                        @endphp
                        {{ $d ? \Illuminate\Support\Carbon::parse($d)->format('Y/m/d') : '-' }}
                    </td>
                    <td class="request-table__cell request-table__cell--ellipsis" title="{{ $req->reason }}">
                        {{ $req->reason }}
                    </td>
                    <td class="request-table__cell">
                        @if ($isPending)
                            {{ optional($req->created_at)->format('Y/m/d') }}
                        @else
                            {{ optional($req->approved_at)->format('Y/m/d') }}
                        @endif
                    </td>
                    <td class="request-table__cell">
                        @if ($req->attendance_id)
                            <a class="request-list__link"
                                href="{{ route('admin.attendance.show', ['id' => $req->attendance_id]) }}">
                                詳細
                            </a>
                        @else
                            <span class="request-list__link request-list__link--disabled" aria-disabled="true">詳細</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr class="request-table__row">
                    <td class="request-table__cell" colspan="6">
                        {{ $isPending ? '承認待ちの申請はありません。' : '承認済みの申請はありません。' }}
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{-- ページネーション --}}
    <div class="request-list__pagination">
        {{ $list->onEachSide(1)->withQueryString()->links() }}
    </div>
</section>
@endsection
