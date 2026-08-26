<x-layouts.app>
    <x-slot:title>{{ __('ui.page_stock_in') }}</x-slot>
    <h1 class="h4 fw-semibold mb-3">{{ __('ui.page_stock_in') }}</h1>

    <x-ui.card>
        <form method="POST" action="{{ route('inventory.store') }}" dusk="stock-form">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <x-ui.select name="product_id" :label="__('ui.product')" required
                                 :options="$products->pluck('name','id')->all()" :placeholder="__('ui.select_product')" />
                </div>
                <div class="col-md-3">
                    <x-ui.select name="type" :label="__('ui.type')" required
                                 :options="['in'=>__('ui.stock_type_in'),'adjustment'=>__('ui.stock_type_adjustment')]" />
                </div>
                <div class="col-md-3">
                    <x-ui.input name="qty" :label="__('ui.quantity')" type="number" min="1" required value="1" />
                </div>
                <div class="col-12">
                    <x-ui.input name="notes" :label="__('ui.note')" />
                </div>
            </div>
            <div class="d-flex gap-2 mt-4">
                <x-ui.button type="submit" variant="primary" dusk="save-stock">{{ __('ui.save') }}</x-ui.button>
                <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary">{{ __('ui.cancel') }}</a>
            </div>
        </form>
    </x-ui.card>
</x-layouts.app>
