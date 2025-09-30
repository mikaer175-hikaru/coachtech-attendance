@extends('layouts.app')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/requests.css') }}">
@endsection

@section('content')
<main class="request-list" aria-labelledby="page-title">
    <h1 id="page-title" class="request-list__heading">申請一覧</h1>

    {{-- タブ --}}
    <nav class="request-list__tabs" aria-label="申請の種類">
        <a href="{{ route('stamp_requests.index', ['tab' => 'pending']) }}"
           class="request-list__tab {{ $tab === 'pending' ? 'request-list__tab--active' : '' }}">
           承認待ち
        </a>
        <a href="{{ route('stamp_requests.index', ['tab' => 'approved']) }}"
           class="request-list__tab {{ $tab === 'approved' ? 'request-list__tab--active' : '' }}">
           承認済み
        </a>
    </nav>

    {{-- 承認待ちリスト（HTMLは常に出力。見た目だけ切替） --}}
    <section class="request-list__section {{ $tab === 'approved' ? 'u-hidden' : '' }}" aria-labelledby="pending-title">
        <h2 id="pending-title" class="request-list__section-title">承認待ち</h2>

        @forelse ($pending as $item)
            <article class="request-card">
                <div class="request-card__meta">
                    <p class="request-card__id">#{{ $item->id }}</p>
                    <p class="request-card__date">申請日：{{ optional($item->created_at)->format('Y/m/d H:i') }}</p>
                    <p class="request-card__status request-card__status--pending">承認待ち</p>
                </div>
                <div class="request-card__body">
                    <p class="request-card__note">備考：{{ $item->note }}</p>
                    @isset($item->type)<p class="request-card__type">種別：{{ $item->type }}</p>@endisset
                    @isset($item->reason)<p class="request-card__reason">理由：{{ $item->reason }}</p>@endisset
                </div>
                <div class="request-card__actions">
                    {{-- 申請詳細へ（→ コントローラが勤怠詳細に302） --}}
                    <a class="request-card__link" href="{{ route('attendance.show', $item->attendance_id) }}">詳細</a>
                </div>
            </article>
        @empty
            <p class="request-list__empty">承認待ちの申請はありません。</p>
        @endforelse

        <div class="request-list__pager">
            {{ $pending->appends(['approved_page' => request('approved_page'), 'tab' => 'pending'])->links() }}
        </div>
    </section>

    {{-- 承認済みリスト（HTMLは常に出力。見た目だけ切替） --}}
    <section class="request-list__section {{ $tab === 'pending' ? 'u-hidden' : '' }}" aria-labelledby="approved-title">
        <h2 id="approved-title" class="request-list__section-title">承認済み</h2>

        @forelse ($approved as $item)
            <article class="request-card">
                <div class="request-card__meta">
                    <p class="request-card__id">#{{ $item->id }}</p>
                    <p class="request-card__date">申請日：{{ optional($item->created_at)->format('Y/m/d H:i') }}</p>
                    <p class="request-card__status request-card__status--approved">承認済み</p>
                </div>
                <div class="request-card__body">
                    <p class="request-card__note">備考：{{ $item->note }}</p>
                    @isset($item->type)<p class="request-card__type">種別：{{ $item->type }}</p>@endisset
                    @isset($item->reason)<p class="request-card__reason">理由：{{ $item->reason }}</p>@endisset
                </div>
                <div class="request-card__actions">
                    <a class="request-card__link" href="{{ route('attendance.show', $item->attendance_id) }}">詳細</a>
                </div>
            </article>
        @empty
            <p class="request-list__empty">承認済みの申請はありません。</p>
        @endforelse

        <div class="request-list__pager">
            {{ $approved->appends(['pending_page' => request('pending_page'), 'tab' => 'approved'])->links() }}
        </div>
    </section>
</main>
@endsection
