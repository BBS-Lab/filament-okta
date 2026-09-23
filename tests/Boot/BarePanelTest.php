<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

it('boots without mounting okta routes for a panel that does not activate the plugin', function (): void {
    // If packageBooted() failed to skip the plugin-less panel it would try to
    // build a FilamentOktaPanel with a null plugin and blow up at boot, so simply
    // reaching this assertion proves the guard holds.
    expect(Route::has('filament-okta.admin.login'))->toBeTrue()
        ->and(Route::has('filament-okta.staff.login'))->toBeTrue()
        ->and(Route::has('filament-okta.bare.login'))->toBeFalse();
});

it('still serves the bare panel own login screen without an okta button', function (): void {
    $this->get(route('filament.bare.auth.login'))
        ->assertOk()
        ->assertDontSee('Log In with Okta');
});
