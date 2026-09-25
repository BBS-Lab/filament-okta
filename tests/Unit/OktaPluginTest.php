<?php

declare(strict_types=1);

use BBSLab\FilamentOkta\OktaPlugin;
use BBSLab\LaravelOkta\Enums\OktaRoute;

it('has the expected id', function (): void {
    expect(OktaPlugin::make()->getId())->toBe('filament-okta');
});

it('defaults the socialite driver to okta and overrides it', function (): void {
    expect(OktaPlugin::make()->getSocialiteDriver())->toBe('okta')
        ->and(OktaPlugin::make()->socialiteDriver('okta-admin')->getSocialiteDriver())->toBe('okta-admin');
});

it('falls back to config for behaviour when the plugin leaves it unset', function (): void {
    config([
        'okta.sso_logout' => false,
        'okta.require_verified_email' => true,
        'okta.identifier.column' => 'okta_id',
        'okta.identifier.update' => false,
    ]);

    $plugin = OktaPlugin::make();

    expect($plugin->getSsoLogout())->toBeFalse()
        ->and($plugin->getRequireVerifiedEmail())->toBeTrue()
        ->and($plugin->getIdentifierColumn())->toBe('okta_id')
        ->and($plugin->getIdentifierUpdate())->toBeFalse();
});

it('overrides behaviour per plugin, ignoring the global config', function (): void {
    config([
        'okta.sso_logout' => true,
        'okta.require_verified_email' => false,
        'okta.identifier.column' => null,
        'okta.identifier.update' => true,
    ]);

    $plugin = OktaPlugin::make()
        ->ssoLogout(false)
        ->requireVerifiedEmail(true)
        ->identifierColumn('provider_id')
        ->identifierUpdate(false);

    expect($plugin->getSsoLogout())->toBeFalse()
        ->and($plugin->getRequireVerifiedEmail())->toBeTrue()
        ->and($plugin->getIdentifierColumn())->toBe('provider_id')
        ->and($plugin->getIdentifierUpdate())->toBeFalse();
});

it('treats an empty identifier column as none', function (): void {
    config(['okta.identifier.column' => null]);

    expect(OktaPlugin::make()->identifierColumn('')->getIdentifierColumn())->toBeNull();
});

it('reads the identifier column straight from config when the plugin leaves it unset', function (): void {
    config(['okta.identifier.column' => 'provider_id']);

    expect(OktaPlugin::make()->getIdentifierColumn())->toBe('provider_id');
});

it('treats an empty-string config identifier column as none', function (): void {
    config(['okta.identifier.column' => '']);

    expect(OktaPlugin::make()->getIdentifierColumn())->toBeNull();
});

it('casts truthy non-boolean config behaviour values to real booleans', function (): void {
    // Config may carry a non-boolean truthy value (e.g. env() returning "1");
    // the getters must return a strict boolean, not the raw config value.
    config([
        'okta.sso_logout' => 1,
        'okta.require_verified_email' => 1,
        'okta.identifier.update' => 1,
    ]);

    $plugin = OktaPlugin::make();

    expect($plugin->getSsoLogout())->toBeTrue()
        ->and($plugin->getRequireVerifiedEmail())->toBeTrue()
        ->and($plugin->getIdentifierUpdate())->toBeTrue();
});

it('casts falsy non-boolean config behaviour values to real booleans', function (): void {
    config([
        'okta.sso_logout' => 0,
        'okta.require_verified_email' => 0,
        'okta.identifier.update' => 0,
    ]);

    $plugin = OktaPlugin::make();

    expect($plugin->getSsoLogout())->toBeFalse()
        ->and($plugin->getRequireVerifiedEmail())->toBeFalse()
        ->and($plugin->getIdentifierUpdate())->toBeFalse();
});

it('defaults every behaviour flag to true when the config key is absent', function (): void {
    // Wipe the okta config node entirely so each getter falls back to its own
    // hard-coded default (true) rather than a merged config value.
    config(['okta' => []]);

    $plugin = OktaPlugin::make();

    expect($plugin->getSsoLogout())->toBeTrue()
        ->and($plugin->getRequireVerifiedEmail())->toBeTrue()
        ->and($plugin->getIdentifierUpdate())->toBeTrue()
        ->and($plugin->getIdentifierColumn())->toBeNull();
});

it('defaults each okta route path to the enum default', function (): void {
    config(['okta' => []]);

    $plugin = OktaPlugin::make();

    expect($plugin->getPath(OktaRoute::Login))->toBe('authorization-code/redirect')
        ->and($plugin->getPath(OktaRoute::Callback))->toBe('authorization-code/callback')
        ->and($plugin->getPath(OktaRoute::Logout))->toBe('authorization-code/logout')
        ->and($plugin->getPath(OktaRoute::CallbackLogout))->toBe('authorization-code/callback/logout');
});

it('overrides only the paths passed, leaving the rest at their default (per panel)', function (): void {
    $plugin = OktaPlugin::make()->paths(login: 'sso/go', callback: 'sso/back');

    expect($plugin->getPath(OktaRoute::Login))->toBe('sso/go')
        ->and($plugin->getPath(OktaRoute::Callback))->toBe('sso/back')
        // logout / callback_logout were not passed → untouched defaults.
        ->and($plugin->getPath(OktaRoute::Logout))->toBe('authorization-code/logout')
        ->and($plugin->getPath(OktaRoute::CallbackLogout))->toBe('authorization-code/callback/logout');
});

it('applies a per-panel override to the logout and post-logout paths too', function (): void {
    $plugin = OktaPlugin::make()->paths(logout: 'sso/out', callbackLogout: 'sso/out/done');

    expect($plugin->getPath(OktaRoute::Logout))->toBe('sso/out')
        ->and($plugin->getPath(OktaRoute::CallbackLogout))->toBe('sso/out/done');
});

it('prefers a per-panel path over the shared config, and config over the default', function (): void {
    config(['okta.paths.login' => 'config/login']);

    // Unset on the plugin → shared config wins; set on the plugin → the plugin wins.
    expect(OktaPlugin::make()->getPath(OktaRoute::Login))->toBe('config/login')
        ->and(OktaPlugin::make()->paths(login: 'panel/login')->getPath(OktaRoute::Login))->toBe('panel/login');
});

it('trims surrounding slashes and falls back to the default when a value is empty', function (): void {
    config(['okta.paths.callback' => '', 'okta.paths.logout' => '/x/y/']);

    $plugin = OktaPlugin::make()->paths(login: '/panel/login/');

    expect($plugin->getPath(OktaRoute::Login))->toBe('panel/login')                    // trimmed per-panel value
        ->and($plugin->getPath(OktaRoute::Callback))->toBe('authorization-code/callback') // empty config → default
        ->and($plugin->getPath(OktaRoute::Logout))->toBe('x/y');                        // trimmed config value
});
