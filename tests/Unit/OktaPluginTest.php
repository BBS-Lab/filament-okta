<?php

declare(strict_types=1);

use BBSLab\FilamentOkta\OktaPlugin;

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
