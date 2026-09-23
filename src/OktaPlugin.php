<?php

declare(strict_types=1);

namespace BBSLab\FilamentOkta;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;

/**
 * The Filament plugin activating Okta SSO on a panel:
 *
 *   $panel->plugin(OktaPlugin::make());
 *
 * It is activatable per panel, so each panel can carry its own Okta
 * configuration (driver, SSO logout, verified-email, identifier). Any option
 * left unset falls back to the shared config('okta.*') of bbs-lab/laravel-okta.
 * The plugin adds the "Log In with Okta" button to the panel's login screen; the
 * routes are mounted by the service provider once every panel is registered.
 */
class OktaPlugin implements Plugin
{
    public const ID = 'filament-okta';

    protected string $socialiteDriver = 'okta';

    protected ?bool $ssoLogout = null;

    protected ?bool $requireVerifiedEmail = null;

    protected ?string $identifierColumn = null;

    protected ?bool $identifierUpdate = null;

    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return static::ID;
    }

    public function register(Panel $panel): void
    {
        // Add the Okta button to this panel's login screen. The route is resolved
        // lazily at render time, by when the panel id and routes are final.
        $panel->renderHook(
            PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
            fn (): View => view('filament-okta::login-button', [
                'url' => route('filament-okta.'.$panel->getId().'.login'),
            ]),
        );
    }

    public function boot(Panel $panel): void {}

    public function socialiteDriver(string $driver): static
    {
        $this->socialiteDriver = $driver;

        return $this;
    }

    public function ssoLogout(bool $condition = true): static
    {
        $this->ssoLogout = $condition;

        return $this;
    }

    public function requireVerifiedEmail(bool $condition = true): static
    {
        $this->requireVerifiedEmail = $condition;

        return $this;
    }

    public function identifierColumn(?string $column): static
    {
        $this->identifierColumn = $column;

        return $this;
    }

    public function identifierUpdate(bool $condition = true): static
    {
        $this->identifierUpdate = $condition;

        return $this;
    }

    public function getSocialiteDriver(): string
    {
        return $this->socialiteDriver;
    }

    public function getSsoLogout(): bool
    {
        return $this->ssoLogout ?? (bool) config('okta.sso_logout', true);
    }

    public function getRequireVerifiedEmail(): bool
    {
        return $this->requireVerifiedEmail ?? (bool) config('okta.require_verified_email', true);
    }

    public function getIdentifierColumn(): ?string
    {
        $column = $this->identifierColumn ?? config('okta.identifier.column');

        return is_string($column) && $column !== '' ? $column : null;
    }

    public function getIdentifierUpdate(): bool
    {
        return $this->identifierUpdate ?? (bool) config('okta.identifier.update', true);
    }
}
