@php
$isEdit = isset($discount) && $discount?->exists;
$action = $isEdit ? route('discounts.update', $discount) : route('discounts.store');
$selectedScope = $isEdit && $discount->category_scope
    ? \App\Models\ProductCategory::whereIn('type_slug', $discount->category_scope)->pluck('id')->all()
    : [];
@endphp

<form method="POST" action="{{ $action }}" dusk="discount-form">
    @csrf
    @if ($isEdit) @method('PUT') @endif

    <div class="row g-3">
        <div class="col-md-4">
            <x-ui.input name="code" :label="__('ui.code')" required :value="($discount ?? null)?->code"
                        placeholder="HEMAT10" />
        </div>
        <div class="col-md-8">
            <x-ui.input name="name" :label="__('ui.table_name')" required :value="($discount ?? null)?->name" />
        </div>
        <div class="col-md-4">
            <x-ui.select name="type" :label="__('ui.type')" required
                         :options="['nominal' => __('ui.type_nominal'), 'percent' => __('ui.type_percent')]"
                         :value="($discount ?? null)?->type ?? 'percent'" dusk="select-type" />
        </div>
        <div class="col-md-4">
            <x-ui.money name="value" :label="__('ui.price')" required
                        :value="isset($discount) ? (string) (int) $discount->value : old('value', '10')" />
        </div>
        <div class="col-md-4">
            <x-ui.select name="branch_id" :label="__('ui.branch')"
                         :options="$branches->pluck('name', 'id')->all()"
                         :value="($discount ?? null)?->branch_id"
                         placeholder="-- {{ __('ui.all_branches') }} --" />
        </div>
        <div class="col-md-3">
            <x-ui.input name="valid_from" :label="__('ui.valid_from')" type="date"
                        :value="($discount ?? null)?->valid_from?->format('Y-m-d') ?? now()->toDateString()" />
        </div>
        <div class="col-md-3">
            <x-ui.input name="valid_until" :label="__('ui.valid_until')" type="date"
                        :value="($discount ?? null)?->valid_until?->format('Y-m-d') ?? now()->addMonths(3)->toDateString()" />
        </div>
        <div class="col-md-3">
            <x-ui.input name="usage_limit" :label="__('ui.usage_limit')" type="number" min="1" step="1"
                        :value="($discount ?? null)?->usage_limit" />
        </div>
        <div class="col-md-3">
            <x-ui.input name="usage_limit_per_customer" :label="__('ui.usage_limit_per_customer')" type="number" min="1" step="1"
                        :value="($discount ?? null)?->usage_limit_per_customer" />
        </div>
        <div class="col-12">
            <label class="form-label fw-semibold">{{ __('ui.category_scope') }}</label>
            <div class="d-flex gap-3 flex-wrap" dusk="category-scope">
                @foreach ($categories as $category)
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="category_scope[]"
                               value="{{ $category->id }}" id="scope-{{ $category->id }}"
                               @checked(in_array($category->id, $selectedScope, true))>
                        <label class="form-check-label" for="scope-{{ $category->id }}">{{ $category->name }}</label>
                    </div>
                @endforeach
            </div>
            <div class="form-text">{{ __('ui.all_categories') }} jika semua dicentang.</div>
        </div>
        @if ($isEdit)
            <div class="col-md-4">
                <x-ui.checkbox name="is_active" :label="__('ui.active')" :checked="$discount->is_active" />
            </div>
        @endif
    </div>

    <div class="d-flex gap-2 mt-4">
        <x-ui.button type="submit" variant="primary" dusk="save-discount">
            {{ $isEdit ? __('ui.save_changes') : __('ui.create') }}
        </x-ui.button>
        <a href="{{ route('discounts.index') }}" class="btn btn-outline-secondary">{{ __('ui.cancel') }}</a>
    </div>
</form>
