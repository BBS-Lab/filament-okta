<?php

declare(strict_types=1);

namespace BBSLab\FilamentOkta\Support;

use BBSLab\FilamentOkta\OktaPlugin;
use BBSLab\LaravelOkta\Contracts\OktaPanel;
use BBSLab\LaravelOkta\Enums\OktaRoute;
use Filament\Notifications\Notification;
use Filament\Panel;
use Illuminate\Http\Request;

/**
 * Bridges one Filament panel to the framework-agnostic Okta flow. Because the
 * plugin is activated per panel, each panel gets its own FilamentOktaPanel —
 * with its own guard, URLs, route name and Okta settings — so several panels can
 * run different Okta configurations side by side.
 */
class FilamentOktaPanel implements OktaPanel
{
    public function __construct(
        protected Panel $panel,
        protected OktaPlugin $plugin,
    ) {}

    public function guard(): ?string
    {
        return $this->panel->getAuthGuard();
    }

    public function loginUrl(): string
    {
        return $this->panel->getLoginUrl() ?? $this->panel->getUrl() ?? url('/');
    }

    public function homeUrl(Request $request): string
    {
        return $this->panel->getUrl() ?? url('/');
    }

    public function routePrefix(): string
    {
        return $this->panel->getPath();
    }

    public function routeName(): string
    {
        return 'filament-okta.'.$this->panel->getId();
    }

    public function middleware(): array
    {
        // Exactly Filament's own panel middleware: the session stack plus
        // "panel:{id}" (SetUpPanel), so Filament::getCurrentPanel() is set on
        // these routes and the per-panel OktaPanel resolves correctly.
        return $this->panel->getMiddleware();
    }

    public function socialiteDriver(): string
    {
        return $this->plugin->getSocialiteDriver();
    }

    public function path(OktaRoute $route): string
    {
        return $this->plugin->getPath($route);
    }

    public function flashError(string $message): void
    {
        // Rendered by the panel's notifications component on the login screen.
        Notification::make()->danger()->title($message)->send();
    }

    public function ssoLogout(): bool
    {
        return $this->plugin->getSsoLogout();
    }

    public function requireVerifiedEmail(): bool
    {
        return $this->plugin->getRequireVerifiedEmail();
    }

    public function identifierColumn(): ?string
    {
        return $this->plugin->getIdentifierColumn();
    }

    public function identifierUpdate(): bool
    {
        return $this->plugin->getIdentifierUpdate();
    }
}
