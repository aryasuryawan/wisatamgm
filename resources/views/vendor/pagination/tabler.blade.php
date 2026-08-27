@if ($paginator->hasPages())
    <nav aria-label="Pagination" dusk="pagination">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            {{-- Info: Menampilkan X–Y dari Z data --}}
            <div class="text-secondary small" dusk="pagination-info">
                {{ __('ui.pagination_showing', [
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                    'total' => $paginator->total(),
                ]) }}
            </div>

            {{-- Nomor halaman --}}
            <ul class="pagination pagination-sm mb-0">
                {{-- Previous --}}
                <li class="page-item {{ $paginator->onFirstPage() ? 'disabled' : '' }}">
                    <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev"
                       aria-label="@lang('pagination.previous')" dusk="pagination-previous">
                        <i class="ti ti-chevron-left"></i>
                    </a>
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
                       aria-label="@lang('pagination.next')" dusk="pagination-next">
                        <i class="ti ti-chevron-right"></i>
                    </a>
                </li>
            </ul>
        </div>
    </nav>
@endif
