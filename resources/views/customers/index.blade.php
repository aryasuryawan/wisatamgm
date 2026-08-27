<x-layouts.app>
    <x-slot:title>{{ __('ui.customers') }}</x-slot>
    <x-slot:page_actions>
        @can('customers.create')
            <a href="{{ route('customers.create') }}" class="btn btn-primary" dusk="create-customer">
                <i class="ti ti-plus icon icon-2 me-1"></i>{{ __('ui.add') }} {{ __('ui.customers') }}
            </a>
        @endcan
    </x-slot:page_actions>

    <x-ui.card dusk="customers-card" :padded="false">
        <div class="card-header">
            <form method="GET" class="row g-2 w-100" dusk="search-form">
                <div class="col-md-3">
                    <input type="text" name="q" class="form-control" placeholder="{{ __('ui.search_customer') }}"
                           value="{{ request('q') }}" dusk="search-input">
                </div>
                <div class="col-md-2">
                    <select name="customer_type" class="form-select" dusk="filter-customer-type">
                        <option value="">{{ __('ui.all_customer_types') }}</option>
                        <option value="individual" @selected(request('customer_type') === 'individual')>{{ __('ui.customer_type_individual') }}</option>
                        <option value="corporate" @selected(request('customer_type') === 'corporate')>{{ __('ui.customer_type_corporate') }}</option>
                        <option value="organization" @selected(request('customer_type') === 'organization')>{{ __('ui.customer_type_organization') }}</option>
                        <option value="school" @selected(request('customer_type') === 'school')>{{ __('ui.customer_type_school') }}</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="segment" class="form-select" dusk="filter-segment">
                        <option value="">{{ __('ui.all_segments') }}</option>
                        @foreach (['VIP', 'Repeat', 'Baru'] as $seg)
                            <option value="{{ $seg }}" @selected(request('segment') === $seg)>{{ $seg }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="nationality" class="form-select" dusk="filter-nationality">
                        <option value="">{{ __('ui.all_nationalities') }}</option>
                        <option value="indonesia" @selected(request('nationality') === 'indonesia')>{{ __('ui.indonesia') }}</option>
                        <option value="international" @selected(request('nationality') === 'international')>{{ __('ui.international') }}</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">{{ __('ui.search') }}</button>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table card-table table-vcenter text-nowrap table-hover" dusk="customers-table">
                <thead>
                <tr>
                    <th>{{ __('ui.table_name') }}</th>
                    <th>{{ __('ui.phone') }}</th>
                    <th>{{ __('ui.customer_type') }}</th>
                    <th>{{ __('ui.nationality') }}</th>
                    <th>{{ __('ui.segment') }}</th>
                    <th>{{ __('ui.orders') }}</th>
                    <th>{{ __('ui.total_spent') }}</th>
                    <th class="text-end">{{ __('ui.action') }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($customers as $customer)
                    <tr dusk="customer-row-{{ $customer->id }}">
                        <td>
                            <a href="{{ route('customers.show', $customer) }}"
                               class="fw-semibold text-decoration-none" dusk="view-customer-{{ $customer->id }}">
                                {{ $customer->name }}
                            </a>
                        </td>
                        <td>{{ $customer->phone ?: '-' }}</td>
                        <td><x-ui.badge color="secondary">{{ __('ui.customer_type_'.$customer->customer_type) }}</x-ui.badge></td>
                        <td>
                            @if($customer->nationality_type === 'indonesia')
                                <x-ui.badge color="success">{{ __('ui.indonesia') }}</x-ui.badge>
                            @else
                                <x-ui.badge color="primary">{{ __('ui.international') }}</x-ui.badge>
                            @endif
                        </td>
                        <td>
                            @php $seg = $customer->segment; @endphp
                            <x-ui.badge :color="$seg === 'VIP' ? 'warning' : ($seg === 'Repeat' ? 'primary' : 'secondary')">
                                {{ $seg }}
                            </x-ui.badge>
                        </td>
                        <td>{{ $customer->total_orders }}</td>
                        <td>Rp {{ number_format((float) $customer->total_spent, 0, ',', '.') }}</td>
                        <td class="text-end">
                            @can('customers.edit')
                                <a href="{{ route('customers.edit', $customer) }}"
                                   class="btn btn-outline-primary btn-sm" dusk="edit-customer-{{ $customer->id }}">
                                    {{ __('ui.edit') }}
                                </a>
                            @endcan
                            @can('customers.delete')
                                <form method="POST" action="{{ route('customers.destroy', $customer) }}"
                                      class="d-inline" dusk="delete-customer-form-{{ $customer->id }}"
                                      onsubmit="return confirm('{{ __('ui.confirm_delete_customer') }}')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm"
                                            dusk="delete-customer-{{ $customer->id }}">{{ __('ui.delete') }}</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">{{ __('ui.empty_customers') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($customers->hasPages())
            <div class="card-footer">{{ $customers->links() }}</div>
        @endif
    </x-ui.card>
</x-layouts.app>
