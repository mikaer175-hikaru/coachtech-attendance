@if ($paginator->hasPages())
    @php
        // 検索やソートなどのクエリは維持（不要ならこの1行は消してOK）
        $query = request()->except('page');
    @endphp

    <nav class="c-pagination" role="navigation" aria-label="ページナビゲーション">
        <ul class="c-pagination__list">
            {{-- 前へ --}}
            @if ($paginator->onFirstPage())
                <li class="c-pagination__item c-pagination__item--disabled">
                    <span class="c-pagination__link" aria-disabled="true">前へ</span>
                </li>
            @else
                <li class="c-pagination__item">
                    <a href="{{ $paginator->appends($query)->previousPageUrl() }}" class="c-pagination__link" rel="prev">
                        前へ
                    </a>
                </li>
            @endif

            {{-- ページ番号 --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="c-pagination__item c-pagination__item--ellipsis" aria-hidden="true">
                        <span class="c-pagination__link">{{ $element }}</span>
                    </li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="c-pagination__item c-pagination__item--active">
                                <span class="c-pagination__link" aria-current="page">{{ $page }}</span>
                            </li>
                        @else
                            <li class="c-pagination__item">
                                <a href="{{ $paginator->appends($query)->url($page) }}" class="c-pagination__link">{{ $page }}</a>
                            </li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- 次へ --}}
            @if ($paginator->hasMorePages())
                <li class="c-pagination__item">
                    <a href="{{ $paginator->appends($query)->nextPageUrl() }}" class="c-pagination__link" rel="next">
                        次へ
                    </a>
                </li>
            @else
                <li class="c-pagination__item c-pagination__item--disabled">
                    <span class="c-pagination__link" aria-disabled="true">次へ</span>
                </li>
            @endif
        </ul>

        @if (method_exists($paginator, 'firstItem') && $paginator->firstItem() !== null)
            <p class="c-pagination__summary">
                {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} / 全{{ $paginator->total() }}件
            </p>
        @endif
    </nav>
@endif
