@php $s = $settings['business'] ?? []; @endphp

<form method="POST" action="{{ route('settings.update', ['tab' => 'business']) }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <input type="hidden" name="tab" value="business">

    <div class="row g-4">
        <div class="col-md-6">
            <x-ui.input name="business_name" label="{{ __('ui.business_name') }}"
                        :value="$s['business_name'] ?? config('app.name')" required dusk="input-business-name" />
        </div>
        <div class="col-md-6">
            <x-ui.input name="business_phone" label="{{ __('ui.business_phone') }}"
                        :value="$s['business_phone'] ?? ''" type="tel" dusk="input-business-phone" />
        </div>
        <div class="col-md-6">
            <x-ui.input name="business_email" label="{{ __('ui.business_email') }}"
                        :value="$s['business_email'] ?? ''" type="email" dusk="input-business-email" />
        </div>
        <div class="col-md-6">
            <x-ui.input name="business_website" label="{{ __('ui.business_website') }}"
                        :value="$s['business_website'] ?? ''" type="url" placeholder="https://" dusk="input-business-website" />
        </div>
        <div class="col-12">
            <x-ui.input name="business_address" label="{{ __('ui.business_address') }}"
                        :value="$s['business_address'] ?? ''" dusk="input-business-address" />
        </div>
        <div class="col-md-6">
            <x-ui.input name="business_city" label="{{ __('ui.business_city') }}"
                        :value="$s['business_city'] ?? ''" dusk="input-business-city" />
        </div>
        <div class="col-md-6">
            <x-ui.input name="business_npwp" label="{{ __('ui.business_npwp') }}"
                        :value="$s['business_npwp'] ?? ''" dusk="input-business-npwp" />
        </div>
        <div class="col-12">
            <label class="form-label">{{ __('ui.business_footer_note') }}</label>
            <textarea name="business_footer_note" class="form-control" rows="2" dusk="input-business-footer">{{ $s['business_footer_note'] ?? '' }}</textarea>
        </div>
        <div class="col-12">
            <label class="form-label">{{ __('ui.business_logo') }}</label>
            @if(!empty($s['business_logo']))
                <div class="mb-2">
                    <img src="{{ Storage::url($s['business_logo']) }}" alt="Logo" style="max-height:60px" dusk="current-logo">
                </div>
            @endif
            <input type="file" name="business_logo" class="form-control" accept="image/jpeg,image/png,image/webp" dusk="input-business-logo">
            <div class="form-hint">{{ __('ui.business_logo_hint') }}</div>
        </div>
    </div>

    <div class="mt-4">
        <x-ui.button type="submit" variant="primary" dusk="save-business">{{ __('ui.save_changes') }}</x-ui.button>
    </div>
</form>
