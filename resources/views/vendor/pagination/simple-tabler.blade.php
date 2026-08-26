@if ($paginator->hasPages())
    <nav aria-label="Pagination" dusk="pagination-simple">
        <ul class="pagination mb-0">
            <li class="page-item {{ $paginator->onFirstPage() ? 'disabled' : '' }}">
                <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev"
                   aria-label="@lang('pagination.previous')" dusk="pagination-previous">&lsaquo;</a>
            </li>

            <li class="page-item disabled"><span class="page-link">{{ $paginator->currentPage() }}</span></li>

            <li class="page-item {{ $paginator->hasMorePages() ? '' : 'disabled' }}">
                <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next"
                   aria-label="@lang('pagination.next')" dusk="pagination-next">&rsaquo;</a>
            </li>
        </ul>
    </nav>
@endif
