<x-layouts.app>
    <x-slot:title>{{ __('ui.expenses') }}</x-slot>
    <x-slot:pretitle>{{ __('ui.finance_module') }}</x-slot:pretitle>

    <x-slot:page_actions>
        @can('marketing-campaigns.create')
            <a href="{{ route('marketing-campaigns.index') }}" class="btn btn-outline-secondary" dusk="nav-campaigns-link">
                <i class="ti ti-speakerphone icon icon-1"></i> {{ __('ui.campaigns') }}
            </a>
        @endcan
        @can('expenses.create')
            <a href="{{ route('expenses.create') }}" class="btn btn-primary" dusk="create-expense">
                + {{ __('ui.add_expense') }}
            </a>
        @endcan
    </x-slot:page_actions>

    <x-ui.card dusk="expenses-card" :padded="false">
        <div class="card-header"><form method="GET" class="row g-2 w-100">
            <div class="col-md-2">
                <select name="branch_id" class="form-select">
                    <option value="">{{ __('ui.all_branches') }}</option>
                    @foreach ($branches as $b)
                        <option value="{{ $b->id }}" @selected(request('branch_id')==$b->id)>{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="category_id" class="form-select">
                    <option value="">{{ __('ui.all_categories') }}</option>
                    @foreach ($categories as $c)
                        <option value="{{ $c->id }}" @selected(request('category_id')==$c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control" title="{{ __('ui.date_from') }}">
            </div>
            <div class="col-md-2">
                <input type="date" name="date_until" value="{{ request('date_until') }}" class="form-control" title="{{ __('ui.date_until') }}">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-outline-primary">{{ __('ui.filter') }}</button>
            </div>
        </form></div>

        <div class="table-responsive">
            <table class="table card-table table-vcenter text-nowrap table-hover" dusk="expenses-table">
                <thead>
                <tr>
                    <th>{{ __('ui.table_date') }}</th>
                    <th>{{ __('ui.table_description') }}</th>
                    <th>{{ __('ui.table_category') }}</th>
                    <th>{{ __('ui.table_branch') }}</th>
                    <th>{{ __('ui.table_campaign') }}</th>
                    <th class="text-end">{{ __('ui.table_amount') }}</th>
                    <th class="text-end">{{ __('ui.table_action') }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($expenses as $expense)
                    <tr dusk="expense-row-{{ $expense->id }}">
                        <td class="text-muted">{{ $expense->expense_date->format('d M Y') }}</td>
                        <td class="fw-semibold">
                            {{ $expense->description }}
                            @if ($expense->proof_path)
                                <a href="{{ \Storage::url($expense->proof_path) }}" target="_blank" title="{{ __('ui.proof_attachment') }}"
                                   class="ms-1 text-decoration-none" dusk="proof-link-{{ $expense->id }}">
                                    <i class="ti ti-paperclip icon icon-1"></i>
                                </a>
                            @endif
                            @if ($expense->ref_type)
                                <x-ui.badge color="info" dusk="generated-badge">{{ __('ui.generated_auto') }}</x-ui.badge>
                            @endif
                        </td>
                        <td>{{ $expense->category->name }}</td>
                        <td>{{ $expense->branch->name }}</td>
                        <td>{{ $expense->campaign?->name ?? '-' }}</td>
                        <td class="text-end fw-semibold">Rp {{ number_format($expense->amount, 0, ',', '.') }}</td>
                        <td class="text-end">
                            <a href="{{ route('expenses.show', $expense) }}"
                               class="btn btn-outline-secondary btn-sm" dusk="view-expense-{{ $expense->id }}">{{ __('ui.detail') }}</a>
                            @can('expenses.edit')
                                <a href="{{ route('expenses.edit', $expense) }}" class="btn btn-outline-primary btn-sm"
                                   dusk="edit-expense-{{ $expense->id }}">{{ __('ui.edit') }}</a>
                            @endcan
                            @can('expenses.delete')
                                <form method="POST" action="{{ route('expenses.destroy', $expense) }}"
                                      class="d-inline" onsubmit="return confirm('{{ __('ui.confirm_delete_expense') }}')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm"
                                            dusk="delete-expense-{{ $expense->id }}">{{ __('ui.delete') }}</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">{{ __('ui.empty_expenses') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center">
            <span class="fw-semibold">
                {{ __('ui.total') }}:
                <span class="text-danger">Rp {{ number_format($totalAmount, 0, ',', '.') }}</span>
            </span>
            {{ $expenses->links() }}
        </div>
    </x-ui.card>
</x-layouts.app>
