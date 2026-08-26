@php
$isEdit = isset($product) && $product?->exists;
$action = $isEdit ? route('products.update', $product) : route('products.store');
@endphp

<form method="POST" action="{{ $action }}" dusk="product-form">
    @csrf
    @if ($isEdit) @method('PUT') @endif

    <div class="row g-3">
        <div class="col-md-6">
            <x-ui.input name="name" :label="__('ui.name')" required :value="$product->name ?? null" />
        </div>
        <div class="col-md-6">
            <x-ui.select name="category_id" :label="__('ui.category')" required
                         :options="$categories->pluck('name','id')->all()"
                         :value="$product->category_id ?? null" />
        </div>
        <div class="col-md-4">
            <x-ui.money name="base_price" :label="__('ui.base_price')" required
                        :value="isset($product) ? (string) (int) $product->base_price : old('base_price')" />
        </div>
        <div class="col-md-4">
            <x-ui.input name="unit" :label="__('ui.unit')" required :value="$product->unit ?? 'pcs'" />
        </div>
        <div class="col-md-4">
            <x-ui.input name="stock_quantity" :label="__('ui.stock')" type="number" min="0"
                        required :value="$product->stock_quantity ?? 0" />
        </div>
        @if ($isEdit)
            <div class="col-12">
                <x-ui.checkbox name="is_active" :label="__('ui.product_active')" :checked="$product->is_active" />
            </div>
        @endif
    </div>

    <div class="d-flex gap-2 mt-4">
        <x-ui.button type="submit" variant="primary" dusk="save-product">
            {{ $isEdit ? __('ui.save') : __('ui.create') }}
        </x-ui.button>
        <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">{{ __('ui.cancel') }}</a>
    </div>
</form>
