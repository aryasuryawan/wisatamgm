@php
$isEdit = isset($customer) && $customer?->exists;
$action = $isEdit ? route('customers.update', $customer) : route('customers.store');
@endphp

<form method="POST" action="{{ $action }}" dusk="customer-form">
    @csrf
    @if ($isEdit) @method('PUT') @endif

    <div class="row g-3">
        <div class="col-md-6">
            <x-ui.input name="name" :label="__('ui.name')" required :value="($customer ?? null)?->name" />
        </div>
        <div class="col-md-6">
            <x-ui.input name="phone" :label="__('ui.phone')" type="tel" :value="($customer ?? null)?->phone" />
        </div>
        <div class="col-md-6">
            <x-ui.input name="email" :label="__('ui.email')" type="email" :value="($customer ?? null)?->email" />
        </div>
        <div class="col-md-6">
            <x-ui.select name="nationality_type" :label="__('ui.nationality')" required
                         :options="['indonesia'=>__('ui.indonesia'),'international'=>__('ui.international')]"
                         :value="($customer ?? null)?->nationality_type ?? 'indonesia'" />
        </div>
        <div class="col-md-6">
            <x-ui.select name="source" :label="__('ui.source')" required
                         :options="['organic'=>__('ui.organic'),'ads'=>__('ui.ads'),'referral'=>__('ui.referral'),'walk_in'=>__('ui.walk_in'),'other'=>__('ui.other')]"
                         :value="($customer ?? null)?->source ?? 'other'" />
        </div>
        <div class="col-md-6">
            <x-ui.select name="customer_type" :label="__('ui.customer_type')" required
                         :options="['individual'=>__('ui.customer_type_individual'),'corporate'=>__('ui.customer_type_corporate'),'organization'=>__('ui.customer_type_organization'),'school'=>__('ui.customer_type_school')]"
                         :value="($customer ?? null)?->customer_type ?? 'individual'" />
        </div>
        <div class="col-12">
            <label class="form-label fw-semibold">{{ __('ui.note') }}</label>
            <textarea name="notes" rows="3" class="form-control" dusk="input-notes">{{ old('notes', ($customer ?? null)?->notes ?? '') }}</textarea>
        </div>
    </div>

    {{-- Preferences --}}
    <h6 class="mt-4 fw-semibold">{{ __('ui.preferences') }}</h6>
    <div class="row g-3">
        <div class="col-md-4">
            <x-ui.input name="preferences[allergies]" :label="__('ui.preferences_allergies')"
                        :value="($customer ?? null)?->getPreference('allergies')" />
        </div>
        <div class="col-md-4">
            <x-ui.select name="preferences[equipment_size]" :label="__('ui.preferences_equipment_size')"
                         :options="['XS'=>'XS','S'=>'S','M'=>'M','L'=>'L','XL'=>'XL','XXL'=>'XXL']"
                         :value="($customer ?? null)?->getPreference('equipment_size')" />
        </div>
        <div class="col-md-4">
            <x-ui.select name="preferences[experience_level]" :label="__('ui.preferences_experience_level')"
                         :options="['beginner'=>__('ui.beginner'),'intermediate'=>__('ui.intermediate'),'advanced'=>__('ui.advanced'),'divemaster'=>__('ui.divemaster'),'instructor'=>__('ui.instructor')]"
                         :value="($customer ?? null)?->getPreference('experience_level')" />
        </div>
    </div>

    {{-- Certifications --}}
    <h6 class="mt-4 fw-semibold">{{ __('ui.certifications') }}</h6>
    <div id="certs-wrapper" x-data="{ certs: {{ Js::from(($customer ?? null)?->certifications ?? collect()) }} }">
        <template x-for="(cert, i) in certs" :key="i">
            <div class="row g-2 mb-2 align-items-end">
                <div class="col-md-2">
                    <select :name="'certs['+i+'][agency]'" class="form-select form-select-sm" dusk="cert-agency">
                        <option value="">Agency</option>
                        @foreach(['PADI','SSI','NAUI','SDI','TDI'] as $a)
                            <option :value="'{{ $a }}'" x-text="'{{ $a }}'" :selected="cert.agency==='{{ $a }}'"></option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="text" :name="'certs['+i+'][level]'" class="form-control form-control-sm"
                           placeholder="Level" x-model="cert.level" dusk="cert-level">
                </div>
                <div class="col-md-2">
                    <input type="text" :name="'certs['+i+'][cert_number]'" class="form-control form-control-sm"
                           :placeholder="__('ui.cert_number')" x-model="cert.cert_number" dusk="cert-number">
                </div>
                <div class="col-md-2">
                    <input type="date" :name="'certs['+i+'][cert_date]'" class="form-control form-control-sm"
                           x-model="cert.cert_date" dusk="cert-date">
                </div>
                <div class="col-md-2">
                    <input type="date" :name="'certs['+i+'][expiry_date]'" class="form-control form-control-sm"
                           x-model="cert.expiry_date" dusk="cert-expiry">
                </div>
                <div class="col-auto">
                    <button type="button" class="btn btn-outline-danger btn-sm"
                            @click="certs.splice(i, 1)" dusk="remove-cert" aria-label="Hapus sertifikasi">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                             stroke-linejoin="round" class="icon icon-2">
                            <path d="M18 6l-12 12" />
                            <path d="M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </template>
        <button type="button" class="btn btn-outline-secondary btn-sm mt-2"
                @click="certs.push({agency:'',level:'',cert_number:'',cert_date:'',expiry_date:''})" dusk="add-cert">
            {{ __('ui.add_certification') }}
        </button>
    </div>

    <div class="d-flex gap-2 mt-4">
        <x-ui.button type="submit" variant="primary" dusk="save-customer">
            {{ $isEdit ? __('ui.save_changes') : __('ui.create') }}
        </x-ui.button>
        <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">{{ __('ui.cancel') }}</a>
    </div>
</form>
