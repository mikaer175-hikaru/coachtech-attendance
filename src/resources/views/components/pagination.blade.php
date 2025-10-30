@if ($paginator->hasPages())
    @php
        // クエリ（検索キーワード・並び順・月など）を維持して遷移
        $query = request()->except('page');
    @endphp

    <nav class="pagination" role="navigation" aria-label="ページナビゲーション">
        {{-- 前へ --}}
        @if ($paginator->onFirstPage())
            <span class="pagination__prev pagination__prev--disabled" aria-disabled="true">前へ</span>
        @else
            <a
                href="{{ $paginator->appends($query)->previousPageUrl() }}"
                class="pagination__prev"
                rel="prev"
            >前へ</a>
        @endif

        {{-- ページ番号 --}}
        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="pagination__ellipsis" aria-hidden="true">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @php $pageUrl = $paginator->appends($query)->url($page); @endphp

                    @if ($page == $paginator->currentPage())
                        <span
                            class="pagination__page pagination__page--active"
                            aria-current="page"
                        >{{ $page }}</span>
                    @else
                        <a
                            href="{{ $pageUrl }}"
                            class="pagination__page"
                        >{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- 次へ --}}
        @if ($paginator->hasMorePages())
            <a
                href="{{ $paginator->appends($query)->nextPageUrl() }}"
                class="pagination__next"
                rel="next"
            >次へ</a>
        @else
            <span class="pagination__next pagination__next--disabled" aria-disabled="true">次へ</span>
        @endif
    </nav>
@endif
