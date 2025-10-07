@if ($paginator->hasPages())
<nav class="c-pagination" role="navigation" aria-label="Pagination">
    <ul class="c-pagination__list">
        {{-- Prev --}}
        @if ($paginator->onFirstPage())
            <li class="c-pagination__item c-pagination__item--disabled" aria-disabled="true">
                <span class="c-pagination__link" aria-hidden="true">‹</span>
            </li>
        @else
            <li class="c-pagination__item">
                <a class="c-pagination__link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="前のページ">‹</a>
            </li>
        @endif

        {{-- Page numbers --}}
        @foreach ($elements as $element)
            @if (is_string($element))
                <li class="c-pagination__item c-pagination__item--ellipsis" aria-disabled="true">
                    <span class="c-pagination__link">{{ $element }}</span>
                </li>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <li class="c-pagination__item c-pagination__item--active" aria-current="page">
                            <span class="c-pagination__link">{{ $page }}</span>
                        </li>
                    @else
                        <li class="c-pagination__item">
                            <a class="c-pagination__link" href="{{ $url }}">{{ $page }}</a>
                        </li>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next --}}
        @if ($paginator->hasMorePages())
            <li class="c-pagination__item">
                <a class="c-pagination__link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="次のページ">›</a>
            </li>
        @else
            <li class="c-pagination__item c-pagination__item--disabled" aria-disabled="true">
                <span class="c-pagination__link" aria-hidden="true">›</span>
            </li>
        @endif
    </ul>

    @if ($paginator->firstItem())
        <p class="c-pagination__summary">
            Showing {{ $paginator->firstItem() }} to {{ $paginator->lastItem() }} of {{ $paginator->total() }} results
        </p>
    @endif
</nav>
@endif
