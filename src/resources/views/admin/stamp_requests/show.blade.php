@extends('admin.layouts.admin')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin-requests.css') }}">
@endsection

@section('content')

<section class="request-detail">
    <h1 class="request-detail__heading">勤怠詳細</h1>

    {{-- フラッシュ --}}
    @if(session('success'))
        <p class="request-detail__flash request-detail__flash--success">{{ session('success') }}</p>
    @endif
    @if(session('error'))
        <p class="request-detail__flash request-detail__flash--error">{{ session('error') }}</p>
    @endif

    <div class="request-detail__card">
        <table class="request-table">
            <tbody>
                <tr class="request-table__row">
                    <th class="request-table__cell request-table__cell--head">名前</th>
                    <td class="request-table__cell">{{ $user->name ?? '-' }}</td>
                </tr>
                <tr class="request-table__row">
                    <th class="request-table__cell request-table__cell--head">日付</th>
                    <td class="request-table__cell">
                        {{ optional($attendance->work_date)->format('Y年n月j日') ?? '-' }}
                    </td>
                </tr>
                <tr class="request-table__row">
                    <th class="request-table__cell request-table__cell--head">出勤・退勤</th>
                    <td class="request-table__cell">
                        {{ optional($attendance->start_time)->format('H:i') ?? '—' }} 〜
                        {{ optional($attendance->end_time)->format('H:i') ?? '—' }}
                    </td>
                </tr>

                @php
                    $breaks = is_array($req->new_breaks ?? null) ? $req->new_breaks : [];
                    $minRows = 2; $pad = max(0, $minRows - count($breaks));
                @endphp

                @foreach($breaks as $i => $b)
                    <tr class="request-table__row">
                        <th class="request-table__cell request-table__cell--head">休憩{{ $i ? $i+1 : '' }}</th>
                        <td class="request-table__cell">
                            {{ $b['start'] ?? '—' }} 〜 {{ $b['end'] ?? '—' }}
                        </td>
                    </tr>
                @endforeach
                @for($i=0; $i<$pad; $i++)
                    <tr class="request-table__row">
                        <th class="request-table__cell request-table__cell--head">休憩{{ ($i === 0 && empty($breaks)) ? '' : count($breaks)+$i+1 }}</th>
                        <td class="request-table__cell">— 〜 —</td>
                    </tr>
                @endfor

                <tr class="request-table__row">
                    <th class="request-table__cell request-table__cell--head">備考</th>
                    <td class="request-table__cell">{{ $req->note ?: '—' }}</td>
                </tr>
            </tbody>
        </table>

        <div class="request-detail__actions">
            @if ($isApproved)
                <button class="request-detail__button request-detail__button--disabled" disabled>
                    承認済み
                </button>
            @else
                <form method="POST" action="{{ route('admin.stamp_requests.approve', $req) }}">
                    @csrf
                    <button type="submit" class="request-detail__button">承認</button>
                </form>
            @endif
        </div>
    </div>
</section>
@endsection
