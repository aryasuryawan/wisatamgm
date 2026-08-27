@php
    $current = app()->getLocale();
    $currentUrl = request()->url();
@endphp

<div class="btn-group btn-group-sm" role="group" aria-label="{{ __('Language') }}" dusk="language-switcher">
    <a href="{{ $currentUrl }}?lang=id"
       class="btn btn-sm {{ $current === 'id' ? 'btn-primary' : 'btn-outline-secondary' }}"
       dusk="lang-id" title="Bahasa Indonesia">
        <i class="ti ti-language icon icon-2 me-1"></i>ID
    </a>
    <a href="{{ $currentUrl }}?lang=en"
       class="btn btn-sm {{ $current === 'en' ? 'btn-primary' : 'btn-outline-secondary' }}"
       dusk="lang-en" title="English">
        EN
    </a>
</div>
