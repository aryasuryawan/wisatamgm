<x-layouts.app>
    <x-slot:title>{{ __('ui.equipment') }}</x-slot>

    <x-slot:page_actions>
        @can('equipment.create')
            <a href="{{ route('equipment.create') }}" class="btn btn-primary" dusk="create-equipment">
                + {{ __('ui.add') }} {{ __('ui.unit') }}
            </a>
        @endcan
    </x-slot:page_actions>

    <x-ui.card dusk="equipment-card" :padded="false">
        <div class="card-header"><form method="GET" class="row g-2 w-100">
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">{{ __('ui.all_status') }}</option>
                    <option value="available" @selected(request('status')==='available')>{{ __('ui.available') }}</option>
                    <option value="rented" @selected(request('status')==='rented')>{{ __('ui.rented') }}</option>
                    <option value="maintenance" @selected(request('status')==='maintenance')>{{ __('ui.maintenance') }}</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="branch_id" class="form-select">
                    <option value="">{{ __('ui.all_branches') }}</option>
                    @foreach ($branches as $b)
                        <option value="{{ $b->id }}" @selected(request('branch_id')==$b->id)>{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-outline-primary">{{ __('ui.filter') }}</button>
            </div>
        </form></div>

        <div class="table-responsive">
            <table class="table card-table table-vcenter text-nowrap table-hover" dusk="equipment-table">
                <thead>
                <tr>
                    <th>{{ __('ui.table_code') }}</th>
                    <th>{{ __('ui.table_product') }}</th>
                    <th>{{ __('ui.table_branch') }}</th>
                    <th>{{ __('ui.table_condition') }}</th>
                    <th>{{ __('ui.table_status') }}</th>
                    <th class="text-end">{{ __('ui.table_action') }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($units as $unit)
                    <tr>
                        <td class="fw-semibold font-monospace">{{ $unit->code }}</td>
                        <td>{{ $unit->product->name }}</td>
                        <td>{{ $unit->branch->name }}</td>
                        <td>
                            @php
                                $condColors = ['good'=>'success','fair'=>'warning text-dark','poor'=>'danger','damaged'=>'dark'];
                            @endphp
                            <x-ui.badge color="{{ $condColors[$unit->condition] ?? 'secondary' }}">{{ $unit->condition }}</x-ui.badge>
                        </td>
                        <td>
                            @php
                                $statusLabels = ['available'=>__('ui.available'),'rented'=>__('ui.rented'),'maintenance'=>__('ui.maintenance')];
                                $statusColors = ['available'=>'success','rented'=>'info text-dark','maintenance'=>'warning text-dark'];
                            @endphp
                            <x-ui.badge color="{{ $statusColors[$unit->status] ?? 'secondary' }}">{{ $statusLabels[$unit->status] ?? $unit->status }}</x-ui.badge>
                        </td>
                        <td class="text-end">
                            @can('equipment.edit')
                                <a href="{{ route('equipment.edit', $unit) }}" class="btn btn-outline-primary btn-sm"
                                   dusk="edit-equipment-{{ $unit->id }}">{{ __('ui.edit') }}</a>
                            @endcan
                            @can('equipment.delete')
                                <form method="POST" action="{{ route('equipment.destroy', $unit) }}"
                                      class="d-inline" onsubmit="return confirm('{{ __('ui.confirm_delete_equipment') }}')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm"
                                            dusk="delete-equipment-{{ $unit->id }}">{{ __('ui.delete') }}</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">{{ __('ui.empty_equipment') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $units->links() }}</div>
    </x-ui.card>
</x-layouts.app>
