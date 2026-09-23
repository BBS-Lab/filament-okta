{{-- The "Log In with Okta" button injected after a Filament panel's login form.
     Rendered with Filament's own button component so it inherits the panel's
     branding — the primary colour, radius, typography and dark mode. --}}
<div class="fi-okta-login" style="margin-top: 1.5rem;">
    <x-filament::button
        tag="a"
        :href="$url"
        color="primary"
        size="lg"
        id="filament-okta-login"
        style="width: 100%; justify-content: center;"
    >
        {{ trans('filament-okta::messages.log_in_with_okta') }}
    </x-filament::button>
</div>
