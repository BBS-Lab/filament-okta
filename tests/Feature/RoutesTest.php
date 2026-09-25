<?php

declare(strict_types=1);

use BBSLab\LaravelOkta\Facades\Okta;
use Filament\Notifications\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Contracts\Provider as SocialiteProvider;
use Laravel\Socialite\Contracts\User as OktaUserContract;
use Laravel\Socialite\Facades\Socialite;
use SocialiteProviders\Manager\OAuth2\User as SocialiteUser;
use Workbench\App\Models\User;

uses(RefreshDatabase::class);

it('registers the okta routes for the panel', function (): void {
    expect(Route::has('filament-okta.admin.login'))->toBeTrue()
        ->and(Route::has('filament-okta.admin.callback'))->toBeTrue()
        ->and(Route::has('filament-okta.admin.logout'))->toBeTrue()
        ->and(Route::has('filament-okta.admin.callback.logout'))->toBeTrue();
});

it('mounts the okta routes under the panel path', function (): void {
    expect(Route::getRoutes()->getByName('filament-okta.admin.login')->uri())
        ->toBe('admin/authorization-code/redirect');
});

it('redirects to okta to start the login', function (): void {
    $response = $this->get(route('filament-okta.admin.login'));

    $response->assertStatus(302);
    expect($response->headers->get('Location'))
        ->toContain('example.okta.com')
        ->toContain('authorize');
});

it('logs in a resolved user on callback and stores the id token', function (): void {
    $user = User::factory()->create(['email' => 'okta@example.com']);

    $oktaUser = (new SocialiteUser)->map(['email' => 'okta@example.com', 'name' => 'Okta User']);
    $oktaUser->setAccessTokenResponseBody(['id_token' => 'the-id-token']);

    fakeSocialiteUser($oktaUser);

    $this->get(route('filament-okta.admin.callback'))->assertRedirect();

    $this->assertAuthenticatedAs($user->fresh());
    expect(session('okta_authenticated'))->toBeTrue()
        ->and(session('okta_id_token'))->toBe('the-id-token');
});

it('lands on the panel after login', function (): void {
    User::factory()->create(['email' => 'home@example.com']);

    $oktaUser = Mockery::mock(OktaUserContract::class);
    $oktaUser->shouldReceive('getEmail')->andReturn('home@example.com');
    $oktaUser->shouldReceive('getId')->andReturn(null);
    fakeSocialiteUser($oktaUser);

    $this->get(route('filament-okta.admin.callback'))->assertRedirect(url('admin'));

    $this->assertAuthenticated();
});

it('runs the beforeLogin and afterLogin hooks on a successful login', function (): void {
    User::factory()->create(['email' => 'hooks@example.com']);

    $order = [];
    Okta::beforeLogin(function () use (&$order): void {
        $order[] = 'before';
    });
    Okta::afterLogin(function () use (&$order): void {
        $order[] = 'after';
    });

    $oktaUser = Mockery::mock(OktaUserContract::class);
    $oktaUser->shouldReceive('getEmail')->andReturn('hooks@example.com');
    $oktaUser->shouldReceive('getId')->andReturn(null);
    fakeSocialiteUser($oktaUser);

    $this->get(route('filament-okta.admin.callback'))->assertRedirect();

    $this->assertAuthenticated();
    expect($order)->toBe(['before', 'after']);
});

it('rejects an unknown user and shows a Filament error notification', function (): void {
    $oktaUser = Mockery::mock(OktaUserContract::class);
    $oktaUser->shouldReceive('getEmail')->andReturn('ghost@example.com');
    $oktaUser->shouldReceive('getId')->andReturn(null);
    fakeSocialiteUser($oktaUser);

    $this->get(route('filament-okta.admin.callback'))
        ->assertRedirect(route('filament.admin.auth.login'));

    // Surfaced natively via Filament's notifications, not a plain session flash.
    Notification::assertNotified((string) trans('okta::messages.not_allowed'));

    $this->assertGuest();
});

it('logs out via okta end-session when an id token is present', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->withSession(['okta_id_token' => 'the-id-token'])
        ->get(route('filament-okta.admin.logout'));

    $response->assertStatus(302);
    expect($response->headers->get('Location'))
        ->toContain('example.okta.com')
        ->toContain('/v1/logout')
        ->toContain('id_token_hint=the-id-token');
    $this->assertGuest();
});

it('logs out to the panel login when no id token is present', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('filament-okta.admin.logout'))
        ->assertRedirect(route('filament.admin.auth.login'));

    $this->assertGuest();
});

it('bounces the post-logout callback back to the panel login', function (): void {
    $this->get(route('filament-okta.admin.callback.logout'))
        ->assertRedirect(route('filament.admin.auth.login'));
});

/**
 * Swap the Socialite okta driver for a stub whose user() returns $oktaUser.
 */
function fakeSocialiteUser(OktaUserContract $oktaUser): void
{
    $provider = Mockery::mock(SocialiteProvider::class);
    $provider->shouldReceive('user')->andReturn($oktaUser);

    Socialite::shouldReceive('driver')->with('okta')->andReturn($provider);
}
