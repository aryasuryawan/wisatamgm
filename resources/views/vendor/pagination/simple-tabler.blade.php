@if ($paginator->hasPages())
    <nav aria-label="Pagination" dusk="pagination-simple">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="text-secondary small" dusk="pagination-info">
                {{ __('ui.pagination_showing', [
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                    'total' => $paginator->total(),
                ]) }}
            </div>

            <ul class="pagination pagination-sm mb-0">
                <li class="page-item {{ $paginator->onFirstPage() ? 'disabled' : '' }}">
                    <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev"
                       aria-label="@lang('pagination.previous')" dusk="pagination-previous">
                        <i class="ti ti-chevron-left"></i>
                    </a>
                </li>

                <li class="page-item disabled">
                    <span class="page-link">{{ $paginator->currentPage() }}</span>
                </li>

                <li class="page-item {{ $paginator->hasMorePages() ? '' : 'disabled' }}">
                    <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next"
                       aria-label="@lang('pagination.next')" dusk="pagination-next">
                        <i class="ti ti-chevron-right"></i>
                    </a>
                </li>
            </ul>
        </div>
    </nav>
@endif
