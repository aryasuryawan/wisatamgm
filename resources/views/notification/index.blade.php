<x-layouts.app>
    <x-slot:title>{{ __('ui.notifications') }}</x-slot>
    <x-slot:pretitle>{{ __('ui.notification_module') }}</x-slot:pretitle>

    <div class="row row-deck row-cards">
        <div class="col-lg-6">
            <x-ui.card :title="__('ui.whatsapp_logs')" :padded="false">
                <div class="table-responsive">
                    <table class="table card-table table-vcenter text-nowrap" dusk="whatsapp-logs-table">
                        <thead>
                        <tr>
                            <th>{{ __('ui.table_phone') }}</th>
                            <th>{{ __('ui.wa_type') }}</th>
                            <th>{{ __('ui.table_status') }}</th>
                            <th>{{ __('ui.date') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($whatsappLogs as $log)
                            @php
                                $statusColors = ['queued' => 'warning', 'sent' => 'success', 'failed' => 'danger'];
                                $statusLabels = ['queued' => __('ui.status_queued'), 'sent' => __('ui.status_sent'), 'failed' => __('ui.status_failed')];
                            @endphp
                            <tr dusk="wa-log-{{ $log->id }}">
                                <td class="fw-semibold">{{ $log->phone }}</td>
                                <td class="text-secondary">{{ $log->type }}</td>
                                <td>
                                    <x-ui.badge color="{{ $statusColors[$log->status] ?? 'secondary' }}">
                                        {{ $statusLabels[$log->status] ?? $log->status }}
                                    </x-ui.badge>
                                    @if ($log->status === 'failed')
                                        <span class="d-block text-danger small" title="{{ $log->error_message }}">{{ \Illuminate\Support\Str::limit($log->error_message, 40) }}</span>
                                    @endif
                                </td>
                                <td class="text-muted small">{{ $log->created_at->format('d M H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">{{ __('ui.empty_whatsapp_logs') }}</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </x-ui.card>
        </div>

        <div class="col-lg-6">
            <x-ui.card :title="__('ui.email_logs')" :padded="false">
                <div class="table-responsive">
                    <table class="table card-table table-vcenter text-nowrap" dusk="email-logs-table">
                        <thead>
                        <tr>
                            <th>{{ __('ui.email') }}</th>
                            <th>{{ __('ui.email_subject') }}</th>
                            <th>{{ __('ui.table_status') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($emailLogs as $log)
                            @php
                                $statusColors = ['queued' => 'warning', 'sent' => 'success', 'failed' => 'danger'];
                                $statusLabels = ['queued' => __('ui.status_queued'), 'sent' => __('ui.status_sent'), 'failed' => __('ui.status_failed')];
                            @endphp
                            <tr dusk="email-log-{{ $log->id }}">
                                <td class="fw-semibold">{{ $log->email }}</td>
                                <td class="text-secondary">{{ $log->subject }}</td>
                                <td>
                                    <x-ui.badge color="{{ $statusColors[$log->status] ?? 'secondary' }}">
                                        {{ $statusLabels[$log->status] ?? $log->status }}
                                    </x-ui.badge>
                                    @if ($log->status === 'failed')
                                        <span class="d-block text-danger small">{{ \Illuminate\Support\Str::limit($log->error_message, 40) }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-4">{{ __('ui.empty_email_logs') }}</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </x-ui.card>
        </div>
    </div>
</x-layouts.app>
