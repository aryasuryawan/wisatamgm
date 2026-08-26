<x-layouts.app>
    <x-slot:title>{{ __('ui.page_inventory') }}</x-slot>

    <x-slot:page_actions>
        @can('inventory.edit')
            <a href="{{ route('inventory.create') }}" class="btn btn-primary" dusk="create-movement">
                {{ __('ui.add_stock') }}
            </a>
        @endcan
    </x-slot:page_actions>

    {{-- Low Stock Alert --}}
    @if ($outOfStockProducts->isNotEmpty() || $lowStockProducts->isNotEmpty())
        <div class="mb-4">
            @if ($outOfStockProducts->isNotEmpty())
                <x-ui.alert type="danger" message="{{ $outOfStockProducts->count() }} {{ __('ui.out_of_stock') }}" />
            @endif
            @if ($lowStockProducts->isNotEmpty())
                <x-ui.alert type="warning" message="{{ $lowStockProducts->count() }} {{ __('ui.low_stock') }}" />
            @endif
        </div>
    @endif

    <x-ui.card dusk="inventory-card" :padded="false">
        <div class="card-header"><form method="GET" class="row g-2 w-100">
            <div class="col-md-4">
                <select name="product_id" class="form-select">
                    <option value="">{{ __('ui.all_products') }}</option>
                    @foreach ($products as $p)
                        <option value="{{ $p->id }}" @selected(request('product_id')==$p->id)>{{ $p->name }} ({{ $p->stock_quantity }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="type" class="form-select">
                    <option value="">{{ __('ui.all_types') }}</option>
                    <option value="in" @selected(request('type')==='in')>{{ __('ui.stock_in') }}</option>
                    <option value="out" @selected(request('type')==='out')>{{ __('ui.stock_out') }}</option>
                    <option value="adjustment" @selected(request('type')==='adjustment')>{{ __('ui.adjustment') }}</option>
                    <option value="opname" @selected(request('type')==='opname')>{{ __('ui.opname') }}</option>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-outline-primary">{{ __('ui.filter') }}</button>
            </div>
        </form></div>

        <div class="table-responsive">
            <table class="table card-table table-vcenter text-nowrap table-hover" dusk="stock-table">
                <thead>
                <tr>
                    <th>{{ __('ui.table_date') }}</th>
                    <th>{{ __('ui.table_product') }}</th>
                    <th>{{ __('ui.table_type') }}</th>
                    <th>{{ __('ui.table_qty') }}</th>
                    <th>{{ __('ui.table_final_stock') }}</th>
                    <th>{{ __('ui.table_note') }}</th>
                    <th>{{ __('ui.table_by') }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($movements as $m)
                    <tr>
                        <td>{{ $m->created_at->format('d M Y H:i') }}</td>
                        <td class="fw-semibold">{{ $m->product->name }}</td>
                        <td>
                            @php
                                $typeLabels = ['in'=>__('ui.stock_in'),'out'=>__('ui.stock_out'),'adjustment'=>__('ui.adjustment'),'opname'=>__('ui.opname')];
                                $typeColors = ['in'=>'success','out'=>'danger','adjustment'=>'warning text-dark','opname'=>'info text-dark'];
                            @endphp
                            <x-ui.badge color="{{ $typeColors[$m->type] }}">{{ $typeLabels[$m->type] ?? $m->type }}</x-ui.badge>
                        </td>
                        <td>{{ $m->qty > 0 ? '+' : '' }}{{ $m->qty }}</td>
                        <td>{{ $m->qty_after }}</td>
                        <td>{{ $m->notes ?: '-' }}</td>
                        <td>{{ $m->user?->name ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">{{ __('ui.empty_stock_movements') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $movements->links() }}</div>
    </x-ui.card>
</x-layouts.app>
