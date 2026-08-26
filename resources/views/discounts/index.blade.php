<x-layouts.app>
    <x-slot:title>{{ __('ui.page_discounts') }}</x-slot>

    <x-slot:page_actions>
        @can('discounts.create')
            <a href="{{ route('discounts.create') }}" class="btn btn-primary" dusk="create-discount">
                + {{ __('ui.add') }} {{ __('ui.discounts') }}
            </a>
        @endcan
    </x-slot:page_actions>

    <x-ui.card dusk="discounts-card" :padded="false">
        <div class="table-responsive">
            <table class="table card-table table-vcenter text-nowrap table-hover" dusk="discounts-table">
                <thead>
                <tr>
                    <th>{{ __('ui.code') }}</th>
                    <th>{{ __('ui.table_name') }}</th>
                    <th>{{ __('ui.type') }}</th>
                    <th>{{ __('ui.date') }}</th>
                    <th>{{ __('ui.usages') }}</th>
                    <th>{{ __('ui.table_status') }}</th>
                    <th class="text-end">{{ __('ui.action') }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($discounts as $discount)
                    <tr dusk="discount-row-{{ $discount->id }}">
                        <td class="fw-semibold"><code>{{ $discount->code }}</code></td>
                        <td>{{ $discount->name }}</td>
                        <td>
                            {{ __('ui.type_' . $discount->type) }}:
                            @if ($discount->type === 'percent')
                                {{ $discount->value }}%
                            @else
                                Rp {{ number_format((float) $discount->value, 0, ',', '.') }}
                            @endif
                        </td>
                        <td class="small">
                            {{ $discount->valid_from?->translatedFormat('d M y') ?? '-' }}
                            -
                            {{ $discount->valid_until?->translatedFormat('d M y') ?? '-' }}
                        </td>
                        <td>
                            {{ $discount->usages_count }}
                            @if ($discount->usage_limit) / {{ $discount->usage_limit }} @endif
                        </td>
                        <td>
                            <x-ui.badge :color="$discount->is_active ? 'bg-success' : 'bg-secondary'"
                                        dusk="discount-status-{{ $discount->id }}">
                                {{ $discount->is_active ? __('ui.active') : __('ui.inactive') }}
                            </x-ui.badge>
                        </td>
                        <td class="text-end">
                            @can('discounts.edit')
                                <a href="{{ route('discounts.edit', $discount) }}"
                                   class="btn btn-outline-primary btn-sm" dusk="edit-discount-{{ $discount->id }}">
                                    {{ __('ui.edit') }}
                                </a>
                            @endcan
                            @can('discounts.delete')
                                <form method="POST" action="{{ route('discounts.destroy', $discount) }}"
                                      class="d-inline"
                                      onsubmit="return confirm('{{ __('ui.confirm_delete_discount') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm"
                                            dusk="delete-discount-{{ $discount->id }}">
                                        {{ __('ui.delete') }}
                                    </button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">{{ __('ui.empty_discounts') }}</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer">{{ $discounts->links() }}</div>
    </x-ui.card>
</x-layouts.app>
