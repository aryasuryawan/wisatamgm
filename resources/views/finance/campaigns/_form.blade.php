@php
$isEdit = isset($campaign) && $campaign?->exists;
$action = $isEdit ? route('marketing-campaigns.update', $campaign) : route('marketing-campaigns.store');
@endphp

<form method="POST" action="{{ $action }}" dusk="campaign-form">
    @csrf
    @if ($isEdit) @method('PUT') @endif

    <div class="row g-3">
        <div class="col-md-6">
            <x-ui.input name="name" :label="__('ui.name')" required :value="$campaign->name ?? null" />
        </div>
        <div class="col-md-3">
            <x-ui.select name="channel" :label="__('ui.channel')"
                         :options="[
                             'meta_ads' => 'Meta Ads',
                             'google_ads' => 'Google Ads',
                             'instagram' => 'Instagram',
                             'tiktok' => 'TikTok',
                             'flyer' => __('ui.channel_flyer'),
                             'other' => __('ui.other'),
                         ]"
                         :value="$campaign->channel ?? null"
                         :placeholder="__('ui.no_channel')" />
        </div>
        <div class="col-md-3">
            <x-ui.select name="branch_id" :label="__('ui.branch')"
                         :options="$branches->pluck('name','id')->all()"
                         :value="$campaign->branch_id ?? null"
                         :placeholder="__('ui.all_branches')" />
        </div>
        <div class="col-md-4">
            <x-ui.money name="budget" :label="__('ui.budget')" required
                        :value="$campaign->budget ?? null" />
        </div>
        <div class="col-md-4">
            <x-ui.input name="start_date" :label="__('ui.date_from')" type="date"
                        :value="($campaign ?? null)?->start_date?->format('Y-m-d')" />
        </div>
        <div class="col-md-4">
            <x-ui.input name="end_date" :label="__('ui.date_until')" type="date"
                        :value="($campaign ?? null)?->end_date?->format('Y-m-d')" />
        </div>
    </div>

    <div class="d-flex gap-2 mt-4">
        <x-ui.button type="submit" variant="primary" dusk="save-campaign">
            {{ $isEdit ? __('ui.save') : __('ui.create') }}
        </x-ui.button>
        <a href="{{ route('marketing-campaigns.index') }}" class="btn btn-outline-secondary">{{ __('ui.cancel') }}</a>
    </div>
</form>
