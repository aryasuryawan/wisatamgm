@php
$isEdit = isset($expense) && $expense?->exists;
$action = $isEdit ? route('expenses.update', $expense) : route('expenses.store');
@endphp

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" dusk="expense-form">
    @csrf
    @if ($isEdit) @method('PUT') @endif

    <div class="row g-3">
        <div class="col-md-4">
            <x-ui.select name="branch_id" :label="__('ui.branch')" required
                         :options="$branches->pluck('name','id')->all()"
                         :value="$expense->branch_id ?? null" />
        </div>
        <div class="col-md-4">
            <x-ui.select name="expense_category_id" :label="__('ui.category')" required
                         :options="$categories->pluck('name','id')->all()"
                         :value="$expense->expense_category_id ?? null" />
        </div>
        <div class="col-md-4">
            <x-ui.money name="amount" :label="__('ui.amount')" required
                        :value="$expense->amount ?? null" />
        </div>
        <div class="col-md-8">
            <x-ui.input name="description" :label="__('ui.description')" required
                        :value="$expense->description ?? null" />
        </div>
        <div class="col-md-4">
            <x-ui.input name="expense_date" :label="__('ui.date')" type="date" required
                        :value="old('expense_date', ($expense ?? null)?->expense_date?->format('Y-m-d') ?? now()->format('Y-m-d'))" />
        </div>
        <div class="col-md-6">
            <x-ui.select name="marketing_campaign_id" :label="__('ui.campaign')"
                         :options="$campaigns->pluck('name','id')->all()"
                         :value="$expense->marketing_campaign_id ?? null"
                         :placeholder="__('ui.no_campaign')" />
        </div>
        <div class="col-md-6">
            <label for="proof" class="form-label fw-semibold">{{ __('ui.proof_attachment') }}</label>
            <input type="file" id="proof" name="proof" accept=".jpg,.jpeg,.png,.webp,.pdf"
                   class="form-control @if(($errors ?? null)?->has('proof')) is-invalid @endif"
                   dusk="input-proof">
            <div class="form-text">{{ __('ui.proof_hint') }}</div>
            @isset($expense)
                @if ($expense->proof_path)
                    <a href="{{ \Storage::url($expense->proof_path) }}" target="_blank" class="small d-inline-block mt-1" dusk="current-proof">
                        {{ __('ui.view_current_proof') }}
                    </a>
                @endif
            @endisset
            @error('proof') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="d-flex gap-2 mt-4">
        <x-ui.button type="submit" variant="primary" dusk="save-expense">
            {{ $isEdit ? __('ui.save') : __('ui.create') }}
        </x-ui.button>
        <a href="{{ route('expenses.index') }}" class="btn btn-outline-secondary">{{ __('ui.cancel') }}</a>
    </div>
</form>
