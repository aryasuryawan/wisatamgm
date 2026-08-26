<x-layouts.app>
    <x-slot:title>{{ __('ui.campaigns') }}</x-slot>
    <x-slot:pretitle>{{ __('ui.finance_module') }}</x-slot:pretitle>

    <x-slot:page_actions>
        @can('marketing-campaigns.create')
            <a href="{{ route('marketing-campaigns.create') }}" class="btn btn-primary" dusk="create-campaign">
                + {{ __('ui.add_campaign') }}
            </a>
        @endcan
    </x-slot:page_actions>

    <x-ui.card dusk="campaigns-card" :padded="false">
        <div class="table-responsive">
            <table class="table card-table table-vcenter text-nowrap table-hover" dusk="campaigns-table">
                <thead>
                <tr>
                    <th>{{ __('ui.table_name') }}</th>
                    <th>{{ __('ui.table_channel') }}</th>
                    <th>{{ __('ui.table_branch') }}</th>
                    <th class="text-end">{{ __('ui.table_budget') }}</th>
                    <th class="text-end">{{ __('ui.table_spent') }}</th>
                    <th>{{ __('ui.table_period') }}</th>
                    <th class="text-end">{{ __('ui.table_action') }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($campaigns as $campaign)
                    @php
                        $spent = $campaign->totalSpent();
                        $pct = $campaign->budget > 0 ? (int) round($spent / $campaign->budget * 100) : 0;
                        $overBudget = $campaign->budget > 0 && $spent > $campaign->budget;
                    @endphp
                    <tr dusk="campaign-row-{{ $campaign->id }}">
                        <td class="fw-semibold">{{ $campaign->name }}</td>
                        <td>{{ $campaign->channel ?? '-' }}</td>
                        <td>{{ $campaign->branch?->name ?? __('ui.all_branches') }}</td>
                        <td class="text-end">Rp {{ number_format($campaign->budget, 0, ',', '.') }}</td>
                        <td class="text-end">
                            <span class="{{ $overBudget ? 'text-danger fw-semibold' : '' }}">Rp {{ number_format($spent, 0, ',', '.') }}</span>
                            @if ($overBudget)
                                <x-ui.badge color="danger" dusk="over-budget-badge">{{ __('ui.over_budget') }}</x-ui.badge>
                            @endif
                        </td>
                        <td class="text-muted small">
                            {{ $campaign->start_date?->format('d M Y') ?? '-' }} – {{ $campaign->end_date?->format('d M Y') ?? '-' }}
                            @if ($campaign->budget > 0)
                                <div class="progress progress-sm mt-1" style="min-width: 90px">
                                    <div class="progress-bar {{ $overBudget ? 'bg-danger' : 'bg-success' }}"
                                         style="width: {{ min(100, $pct) }}%"></div>
                                </div>
                            @endif
                        </td>
                        <td class="text-end">
                            @can('marketing-campaigns.edit')
                                <a href="{{ route('marketing-campaigns.edit', $campaign) }}" class="btn btn-outline-primary btn-sm"
                                   dusk="edit-campaign-{{ $campaign->id }}">{{ __('ui.edit') }}</a>
                            @endcan
                            @can('marketing-campaigns.delete')
                                <form method="POST" action="{{ route('marketing-campaigns.destroy', $campaign) }}"
                                      class="d-inline" onsubmit="return confirm('{{ __('ui.confirm_delete_campaign') }}')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm"
                                            dusk="delete-campaign-{{ $campaign->id }}">{{ __('ui.delete') }}</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">{{ __('ui.empty_campaigns') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $campaigns->links() }}</div>
    </x-ui.card>
</x-layouts.app>
