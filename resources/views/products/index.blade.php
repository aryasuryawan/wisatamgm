<x-layouts.app>
    <x-slot:title>{{ __('ui.products') }}</x-slot>
    <x-slot:page_actions>
        @can('products.create')
            <a href="{{ route('products.create') }}" class="btn btn-primary" dusk="create-product">
                <i class="ti ti-plus icon icon-2 me-1"></i>{{ __('ui.add') }} {{ __('ui.products') }}
            </a>
        @endcan
    </x-slot:page_actions>

    <x-ui.card dusk="products-card" :padded="false">
        <div class="card-header">
            <form method="GET" class="row g-2 w-100">
                <div class="col-md-4">
                    <input type="text" name="q" class="form-control" placeholder="{{ __('ui.search_product') }}"
                           value="{{ request('q') }}" dusk="search-input">
                </div>
                <div class="col-md-4">
                    <select name="category_id" class="form-select" dusk="filter-category">
                        <option value="">{{ __('ui.all_categories') }}</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" @selected(request('category_id') == $cat->id)>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">{{ __('ui.filter') }}</button>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table card-table table-vcenter text-nowrap" dusk="products-table">
                <thead>
                <tr>
                    <th>{{ __('ui.table_name') }}</th>
                    <th>{{ __('ui.table_category') }}</th>
                    <th>{{ __('ui.table_price') }}</th>
                    <th>{{ __('ui.table_stock') }}</th>
                    <th>{{ __('ui.table_status') }}</th>
                    <th class="text-end">{{ __('ui.table_action') }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($products as $product)
                    <tr>
                        <td class="fw-semibold">{{ $product->name }}</td>
                        <td>
                            <x-ui.badge color="secondary">{{ $product->category?->name ?? '-' }}</x-ui.badge>
                        </td>
                        <td>Rp {{ number_format($product->base_price, 0, ',', '.') }}</td>
                        <td>{{ $product->stock_quantity }} {{ $product->unit }}</td>
                        <td>
                            <x-ui.badge :color="$product->is_active ? 'success' : 'secondary'">
                                {{ $product->is_active ? __('ui.active') : __('ui.inactive') }}
                            </x-ui.badge>
                        </td>
                        <td class="text-end">
                            @can('products.edit')
                                <a href="{{ route('products.edit', $product) }}" class="btn btn-outline-primary btn-sm"
                                   dusk="edit-product-{{ $product->id }}">{{ __('ui.edit') }}</a>
                            @endcan
                            @can('products.delete')
                                <form method="POST" action="{{ route('products.destroy', $product) }}"
                                      class="d-inline" onsubmit="return confirm('{{ __('ui.confirm_delete_product') }}')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm"
                                            dusk="delete-product-{{ $product->id }}">{{ __('ui.delete') }}</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">{{ __('ui.empty_products') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($products->hasPages())
            <div class="card-footer">{{ $products->links() }}</div>
        @endif
    </x-ui.card>
</x-layouts.app>
