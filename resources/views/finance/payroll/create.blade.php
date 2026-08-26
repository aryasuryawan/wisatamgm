<x-layouts.app>
    <x-slot:title>{{ __('ui.add_period') }}</x-slot>
    <x-slot:pretitle>{{ __('ui.payroll_module') }}</x-slot:pretitle>

    <x-ui.card dusk="payroll-create-card">
        <form method="POST" action="{{ route('payroll.store') }}" dusk="period-form">
            @csrf
            <div class="row g-3">
                <div class="col-md-4">
                    <x-ui.select name="branch_id" :label="__('ui.branch')" required
                                 :options="$branches->pluck('name','id')->all()" />
                </div>
                <div class="col-md-4">
                    <x-ui.input name="period_start" :label="__('ui.period_start')" type="date" required
                                :value="old('period_start', now()->subMonth()->startOfMonth()->format('Y-m-d'))" />
                </div>
                <div class="col-md-4">
                    <x-ui.input name="period_end" :label="__('ui.period_end')" type="date" required
                                :value="old('period_end', now()->subMonth()->endOfMonth()->format('Y-m-d'))" />
                </div>
                <div class="col-12">
                    <label for="notes" class="form-label fw-semibold">{{ __('ui.note') }}</label>
                    <textarea name="notes" id="notes" rows="2" class="form-control"
                              dusk="input-notes">{{ old('notes') }}</textarea>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <x-ui.button type="submit" variant="primary" dusk="save-period">{{ __('ui.create') }}</x-ui.button>
                <a href="{{ route('payroll.index') }}" class="btn btn-outline-secondary">{{ __('ui.cancel') }}</a>
            </div>
        </form>
    </x-ui.card>
</x-layouts.app>
