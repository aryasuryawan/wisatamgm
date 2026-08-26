@if ($paginator->hasPages())
    <nav aria-label="Pagination" dusk="pagination">
        <ul class="pagination mb-0">
            {{-- Previous --}}
            <li class="page-item {{ $paginator->onFirstPage() ? 'disabled' : '' }}">
                <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev"
                   aria-label="@lang('pagination.previous')" dusk="pagination-previous">&lsaquo;</a>
            </li>

            {{-- Page elements --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="page-item disabled"><span class="page-link">{{ $element }}</span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li class="page-item {{ $page == $paginator->currentPage() ? 'active' : '' }}"
                            dusk="pagination-page-{{ $page }}">
                            <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                        </li>
                    @endforeach
                @endif
            @endforeach

            {{-- Next --}}
            <li class="page-item {{ $paginator->hasMorePages() ? '' : 'disabled' }}">
                <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next"
                   aria-label="@lang('pagination.next')" dusk="pagination-next">&rsaquo;</a>
            </li>
        </ul>
    </nav>
@endif
