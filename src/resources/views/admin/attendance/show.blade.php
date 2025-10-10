{{-- resources/views/admin/attendance/show.blade.php --}}
@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/attendance-detail-admin.css') }}">
@endsection

@section('content')
    <article class="detail" aria-labelledby="detail-title">
        <header class="detail__header">
            <h1 id="detail-title" class="detail__title">
                <span class="detail__title-bar"></span>
                勤怠詳細
            </h1>
        </header>

        @if (session('success'))
            <p class="detail__flash detail__flash--success">{{ session('success') }}</p>
        @endif

        @if ($errors->any())
            <ul class="detail__flash detail__flash--error">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

        @php
            // 先頭の休憩（複数のうち1件目をフォームに出す）
            $firstBreak = $attendance->breaks->sortBy('break_start')->first();
        @endphp

        <form method="POST" action="{{ route('admin.attendance.update', $attendance) }}" class="detail__card" novalidate>
            @csrf
            @method('PATCH')

            {{-- 名前 --}}
            <div class="detail__row">
                <div class="detail__th">名前</div>
                <div class="detail__td detail__td--center">
                    {{ $attendance->user->name ?? '—' }}
                </div>
            </div>

            {{-- 日付（年／月日） --}}
            <div class="detail__row">
                <div class="detail__th">日付</div>
                <div class="detail__td detail__td--split">
                    @php($d = optional($attendance->work_date))
                    <span class="detail__date-y">{{ $d?->year }}年</span>
                    <span class="detail__date-md">{{ $d?->format('n月j日') }}</span>
                </div>
            </div>

            {{-- 出勤・退勤 --}}
            <div class="detail__row">
                <div class="detail__th">出勤・退勤</div>
                <div class="detail__td detail__td--range">
                    <input type="time" name="start_time" class="detail__time"
                           value="{{ old('start_time', optional($attendance->start_time)->format('H:i')) }}">
                    <span class="detail__tilde">〜</span>
                    <input type="time" name="end_time" class="detail__time"
                           value="{{ old('end_time', optional($attendance->end_time)->format('H:i')) }}">
                </div>
            </div>

            {{-- 休憩：複数行 --}}
            @php
                // old優先、なければ既存breaks→配列化。ゼロ件なら空行を1つ。
                $breakRows = old('breaks', $attendance->breaks->sortBy('break_start')->map(function($b){
                    return [
                        'start' => optional($b->break_start)->format('H:i'),
                        'end'   => optional($b->break_end)->format('H:i'),
                    ];
                })->toArray());
                if (empty($breakRows)) $breakRows = [['start' => null, 'end' => null]];
            @endphp

            <div class="detail__row">
                <div class="detail__th">休憩</div>

                <div class="detail__td">
                    <div id="break-rows" class="break-list">
                        @foreach ($breakRows as $i => $row)
                            <div class="break-row" data-index="{{ $i }}">
                                <div class="detail__td--range">
                                    <input type="time" name="breaks[{{ $i }}][start]" class="detail__time"
                                        value="{{ $row['start'] }}">
                                    <span class="detail__tilde">〜</span>
                                    <input type="time" name="breaks[{{ $i }}][end]" class="detail__time"
                                        value="{{ $row['end'] }}">
                                    @if ($i > 0)
                                        <button type="button" class="break-row__remove" aria-label="この休憩を削除">−</button>
                                    @endif
                                </div>

                                {{-- 行ごとのバリデーションエラー表示 --}}
                                @if ($errors->has("breaks.$i.start") || $errors->has("breaks.$i.end"))
                                    <div class="detail__error">
                                        {{ $errors->first("breaks.$i.start") ?: $errors->first("breaks.$i.end") }}
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <button type="button" id="break-add" class="break-row__add">＋ 休憩を追加</button>
                </div>
            </div>

            {{-- 行テンプレ（非表示） --}}
            <template id="break-row-template">
                <div class="break-row" data-index="__INDEX__">
                    <div class="detail__td--range">
                        <input type="time" name="breaks[__INDEX__][start]" class="detail__time" value="">
                        <span class="detail__tilde">〜</span>
                        <input type="time" name="breaks[__INDEX__][end]" class="detail__time" value="">
                        <button type="button" class="break-row__remove" aria-label="この休憩を削除">−</button>
                    </div>
                </div>
            </template>

            @push('scripts')
            <script>
                (function () {
                    const $list = document.getElementById('break-rows');
                    const $add  = document.getElementById('break-add');
                    const $tpl  = document.getElementById('break-row-template').innerHTML.trim();

                    function nextIndex() {
                        const rows = $list.querySelectorAll('.break-row');
                        let max = -1;
                        rows.forEach(r => { max = Math.max(max, Number(r.dataset.index)); });
                        return max + 1;
                    }

                    $add?.addEventListener('click', () => {
                        const idx = nextIndex();
                        const html = $tpl.replaceAll('__INDEX__', String(idx));
                        const wrapper = document.createElement('div');
                        wrapper.innerHTML = html;
                        const row = wrapper.firstElementChild;
                        $list.appendChild(row);
                    });

                    $list?.addEventListener('click', (e) => {
                        if (e.target.closest('.break-row__remove')) {
                            const row = e.target.closest('.break-row');
                            row?.remove();
                        }
                    });
                })();
            </script>
            @endpush


            {{-- 備考 --}}
            <div class="detail__row">
                <div class="detail__th">備考</div>
                <div class="detail__td">
                    <textarea name="note" rows="3" class="detail__textarea" placeholder="電車遅延のため など">{{ old('note', $attendance->note) }}</textarea>
                </div>
            </div>

            <div class="detail__actions">
                <button type="submit" class="detail__button">修正</button>
            </div>
        </form>
    </article>
@endsection
