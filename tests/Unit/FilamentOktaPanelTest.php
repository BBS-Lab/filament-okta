<?php

declare(strict_types=1);

use BBSLab\FilamentOkta\OktaPlugin;
use BBSLab\FilamentOkta\Support\FilamentOktaPanel;
use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Http\Request;

function oktaPanelFor(string $panelId): FilamentOktaPanel
{
    $panel = Filament::getPanel($panelId);
    /** @var OktaPlugin $plugin */
    $plugin = $panel->getPlugin(OktaPlugin::ID);

    return new FilamentOktaPanel($panel, $plugin);
}

it('maps the panel guard, path, route name and driver', function (): void {
    $panel = oktaPanelFor('admin');

    expect($panel->guard())->toBe('web')
        ->and($panel->routePrefix())->toBe('admin')
        ->and($panel->routeName())->toBe('filament-okta.admin')
        ->and($panel->socialiteDriver())->toBe('okta');
});

it('points at the panel login and home urls', function (): void {
    $panel = oktaPanelFor('admin');

    expect($panel->loginUrl())->toBe(route('filament.admin.auth.login'))
        ->and($panel->homeUrl(Request::create('/admin')))->toBe(url('admin'));
});

it('falls back to the panel home url for login when the panel has no login route', function (): void {
    // A panel without ->login() has no login URL, so loginUrl() must fall back to
    // the panel home URL — not to the application root — before url('/').
    $panel = Panel::make()->id('nologin')->path('portal');
    $okta = new FilamentOktaPanel($panel, OktaPlugin::make());

    expect($panel->getLoginUrl())->toBeNull()
        ->and($okta->loginUrl())->toBe(url('portal'))
        ->and($okta->loginUrl())->not->toBe(url('/'))
        ->and($okta->homeUrl(Request::create('/portal')))->toBe(url('portal'));
});

it('maps the staff panel guard, path, route name and driver', function (): void {
    $panel = oktaPanelFor('staff');

    expect($panel->guard())->toBe('web')
        ->and($panel->routePrefix())->toBe('staff')
        ->and($panel->routeName())->toBe('filament-okta.staff')
        ->and($panel->socialiteDriver())->toBe('okta-staff')
        ->and($panel->loginUrl())->toBe(route('filament.staff.auth.login'))
        ->and($panel->homeUrl(Request::create('/staff')))->toBe(url('staff'));
});

it('reuses the staff panel middleware including its own panel-context middleware', function (): void {
    expect(oktaPanelFor('staff')->middleware())
        ->toContain('panel:staff')
        ->toContain('web')
        ->not->toContain('panel:admin');
});

it('reuses the panel middleware including the panel-context middleware', function (): void {
    expect(oktaPanelFor('admin')->middleware())
        ->toContain('panel:admin')
        ->toContain('web');
});

it('carries each panel own okta configuration', function (): void {
    config([
        'okta.require_verified_email' => true,
        'okta.identifier.column' => 'okta_id',
        'okta.identifier.update' => false,
    ]);

    // admin uses defaults (so its behaviour falls back to the shared config);
    // staff overrides the driver and disables SSO logout.
    $admin = oktaPanelFor('admin');

    expect($admin->socialiteDriver())->toBe('okta')
        ->and($admin->ssoLogout())->toBeTrue()
        ->and($admin->requireVerifiedEmail())->toBeTrue()
        ->and($admin->identifierColumn())->toBe('okta_id')
        ->and($admin->identifierUpdate())->toBeFalse()
        ->and(oktaPanelFor('staff')->socialiteDriver())->toBe('okta-staff')
        ->and(oktaPanelFor('staff')->ssoLogout())->toBeFalse()
        ->and(oktaPanelFor('staff')->routeName())->toBe('filament-okta.staff');
});
