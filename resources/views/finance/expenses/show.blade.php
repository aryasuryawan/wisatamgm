<x-layouts.app>
    <x-slot:title>{{ __('ui.detail_expense') }}</x-slot>
    <x-slot:pretitle>{{ __('ui.finance_module') }}</x-slot:pretitle>

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h1 class="h4 fw-semibold mb-0">{{ __('ui.detail_expense') }}</h1>
        <div class="d-flex gap-2">
            @can('expenses.edit')
                <a href="{{ route('expenses.edit', $expense) }}" class="btn btn-primary btn-sm">{{ __('ui.edit') }}</a>
            @endcan
            <a href="{{ route('expenses.index') }}" class="btn btn-outline-secondary btn-sm">{{ __('ui.cancel') }}</a>
        </div>
    </div>

    <x-ui.card dusk="expense-detail-card">
        <div class="row g-3">
            <div class="col-md-3">
                <div class="text-muted small">{{ __('ui.table_date') }}</div>
                <div class="fw-semibold">{{ $expense->expense_date->format('d M Y') }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">{{ __('ui.table_category') }}</div>
                <div class="fw-semibold">{{ $expense->category->name }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">{{ __('ui.table_branch') }}</div>
                <div class="fw-semibold">{{ $expense->branch->name }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">{{ __('ui.table_amount') }}</div>
                <div class="fw-semibold text-danger fs-5">Rp {{ number_format($expense->amount, 0, ',', '.') }}</div>
            </div>
        </div>

        <hr class="my-3">

        <div class="row g-3">
            <div class="col-md-6">
                <div class="text-muted small">{{ __('ui.table_description') }}</div>
                <div class="fw-semibold">{{ $expense->description }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">{{ __('ui.campaigns') }}</div>
                <div class="fw-semibold">{{ $expense->campaign?->name ?? '-' }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">Dibuat</div>
                <div class="text-muted">{{ $expense->created_at->translatedFormat('d M Y H:i') }}</div>
            </div>
        </div>

        <hr class="my-3">
        <div class="text-muted small mb-2">{{ __('ui.proof_attachment') }}</div>
        @if ($expense->proof_path)
            <x-ui.proof-link :path="$expense->proof_path" preview />
        @else
            <div class="text-muted small">{{ __('ui.no_proof') }}</div>
            @can('expenses.edit')
                <a href="{{ route('expenses.edit', $expense) }}" class="btn btn-outline-primary btn-sm mt-2">
                    <i class="ti ti-upload icon icon-1"></i> {{ __('ui.view_current_proof') }}
                </a>
            @endcan
        @endif

        @if ($expense->ref_type)
            <hr class="my-3">
            <div>
                <x-ui.badge color="info">{{ __('ui.generated_auto') }}</x-ui.badge>
                <span class="text-muted small ms-1">{{ __('ui.auto_generated_from') }} {{ $expense->ref_type }}</span>
            </div>
        @endif
    </x-ui.card>
</x-layouts.app>
