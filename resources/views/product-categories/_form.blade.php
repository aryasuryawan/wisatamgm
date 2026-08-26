@php
$isEdit = isset($category) && $category?->exists;
$action = $isEdit ? route('product-categories.update', $category) : route('product-categories.store');
@endphp

<form method="POST" action="{{ $action }}" dusk="category-form">
    @csrf
    @if ($isEdit) @method('PUT') @endif

    <div class="row g-3">
        <div class="col-md-6">
            <x-ui.input name="name" :label="__('ui.name')" required :value="$category->name ?? null" />
        </div>
        <div class="col-md-6">
            <x-ui.input name="type_slug" :label="__('ui.code')" required :value="$category->type_slug ?? null"
                        placeholder="e.g. wisata, jasa, makanan" />
        </div>
        <div class="col-md-6">
            <x-ui.input name="sort_order" :label="__('ui.table_sort')" type="number" min="0" required
                        :value="$category->sort_order ?? 0" />
        </div>
        @if ($isEdit)
            <div class="col-md-6">
                <x-ui.checkbox name="is_active" :label="__('ui.active')" :checked="$category->is_active" />
            </div>
        @endif
    </div>

    <div class="d-flex gap-2 mt-4">
        <x-ui.button type="submit" variant="primary" dusk="save-category">
            {{ $isEdit ? __('ui.save_changes') : __('ui.create') }}
        </x-ui.button>
        <a href="{{ route('product-categories.index') }}" class="btn btn-outline-secondary">{{ __('ui.cancel') }}</a>
    </div>
</form>
