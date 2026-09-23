<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Browser (Pest v4) coverage of the pre-redirect login UX. The full SSO
 * round-trip cannot be driven end to end without a real Okta org, so these
 * scenarios assert the login screen renders the Okta button and that it targets
 * the panel's okta/login route (which starts the OIDC redirect).
 */
it('shows the Log In with Okta button on the admin panel login', function (): void {
    $page = visit(route('filament.admin.auth.login'));

    $page->assertSee('Log In with Okta')
        ->assertPresent('#filament-okta-login');
});

it('points the Okta button at the panel okta login route', function (): void {
    $page = visit(route('filament.admin.auth.login'));

    $page->assertAttribute('#filament-okta-login', 'href', route('filament-okta.admin.login'));
});

it('gives each panel its own Okta button target', function (): void {
    visit(route('filament.staff.auth.login'))
        ->assertAttribute('#filament-okta-login', 'href', route('filament-okta.staff.login'));
});

it('renders the visible Okta button on the staff panel login', function (): void {
    $page = visit(route('filament.staff.auth.login'));

    $page->assertSee('Log In with Okta')
        ->assertPresent('#filament-okta-login')
        ->assertAttributeContains('#filament-okta-login', 'href', '/staff/okta/login');
});
