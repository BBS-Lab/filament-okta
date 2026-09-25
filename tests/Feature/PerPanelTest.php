<?php

declare(strict_types=1);

use BBSLab\FilamentOkta\OktaPlugin;
use BBSLab\FilamentOkta\Support\FilamentOktaPanel;
use BBSLab\LaravelOkta\Contracts\OktaPanel;
use BBSLab\LaravelOkta\Support\NullOktaPanel;
use BBSLab\LaravelOkta\Support\OktaRoutes;
use Filament\Contracts\Plugin;
use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Contracts\Provider as SocialiteProvider;
use Laravel\Socialite\Contracts\User as OktaUserContract;
use Laravel\Socialite\Facades\Socialite;
use Workbench\App\Models\User;

uses(RefreshDatabase::class);

it('mounts a distinct okta route set for each panel that activates the plugin', function (): void {
    expect(Route::has('filament-okta.admin.login'))->toBeTrue()
        ->and(Route::has('filament-okta.staff.login'))->toBeTrue()
        ->and(Route::getRoutes()->getByName('filament-okta.staff.login')->uri())->toBe('staff/authorization-code/redirect');
});

it('mounts a panel okta routes at the paths its plugin configures (per panel)', function (): void {
    $plugin = OktaPlugin::make()->paths(login: 'sso/go', callback: 'sso/back');
    $panel = Panel::make()->id('pathpanel')->path('backoffice')->plugin($plugin);

    OktaRoutes::register(new FilamentOktaPanel($panel, $plugin));

    $uri = fn (string $name): string => (string) collect(Route::getRoutes()->getRoutes())
        ->first(fn ($route) => $route->getName() === $name)?->uri();

    expect($uri('filament-okta.pathpanel.login'))->toBe('backoffice/sso/go')
        ->and($uri('filament-okta.pathpanel.callback'))->toBe('backoffice/sso/back')
        // an unset path still lands on the default under this panel's path
        ->and($uri('filament-okta.pathpanel.logout'))->toBe('backoffice/authorization-code/logout');
});

it('starts login through each panel own socialite driver', function (): void {
    $provider = Mockery::mock(SocialiteProvider::class);
    $provider->shouldReceive('redirect')->andReturn(redirect('https://staff.okta.example/authorize'));
    Socialite::shouldReceive('driver')->with('okta-staff')->andReturn($provider);

    $this->get(route('filament-okta.staff.login'))
        ->assertRedirect('https://staff.okta.example/authorize');
});

it('does a local-only logout for a panel with sso logout disabled', function (): void {
    // staff disables sso_logout, so even with an id token it must not hit Okta's
    // end-session — proving the panel config, not a global, drives the behaviour.
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['okta_id_token' => 'the-id-token'])
        ->get(route('filament-okta.staff.logout'))
        ->assertRedirect(route('filament.staff.auth.login'));

    $this->assertGuest();
});

it('resolves a null okta panel when no filament panel is current', function (): void {
    expect(app(OktaPanel::class))->toBeInstanceOf(NullOktaPanel::class);
});

it('resolves the filament okta panel for the current panel', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('staff'));

    $resolved = app(OktaPanel::class);

    expect($resolved)->toBeInstanceOf(FilamentOktaPanel::class)
        ->and($resolved->routeName())->toBe('filament-okta.staff')
        ->and($resolved->socialiteDriver())->toBe('okta-staff');
});

it('resolves a null okta panel when the current panel registers a non-okta plugin under the okta id', function (): void {
    // A foreign plugin claiming the okta plugin id must not be mistaken for the
    // real OktaPlugin: the binding guards with an instanceof check and falls back
    // to the null panel rather than wiring the flow to an incompatible plugin.
    $imposter = new class implements Plugin
    {
        public function getId(): string
        {
            return OktaPlugin::ID;
        }

        public function register(Panel $panel): void {}

        public function boot(Panel $panel): void {}
    };

    $panel = Panel::make()->id('imposter')->path('imposter')->plugin($imposter);
    Filament::setCurrentPanel($panel);

    expect($panel->getPlugin(OktaPlugin::ID))->toBe($imposter)
        ->and(app(OktaPanel::class))->toBeInstanceOf(NullOktaPanel::class);
});

it('adds the Log In with Okta button to the panel login screen', function (): void {
    $this->get(route('filament.admin.auth.login'))
        ->assertOk()
        ->assertSee('Log In with Okta')
        ->assertSee(route('filament-okta.admin.login'), false);
});

it('derives a distinct redirect_uri for each panel from its own callback route', function (): void {
    // Neither panel configures a redirect — each must derive its own from its path,
    // so no OKTA_REDIRECT_URI is needed and the two never collide.
    config(['services.okta.redirect' => null, 'services.okta-staff.redirect' => null]);

    $admin = (string) $this->get(route('filament-okta.admin.login'))->headers->get('Location');
    $staff = (string) $this->get(route('filament-okta.staff.login'))->headers->get('Location');

    expect($admin)->toContain('redirect_uri='.urlencode(route('filament-okta.admin.callback')))
        ->and($staff)->toContain('redirect_uri='.urlencode(route('filament-okta.staff.callback')))
        // The derived callbacks genuinely differ (admin path vs staff path)...
        ->and(route('filament-okta.admin.callback'))->not->toBe(route('filament-okta.staff.callback'))
        // ...and each panel targets its own Okta org.
        ->and($admin)->toContain('example.okta.com')
        ->and($staff)->toContain('staff.example.okta.com');
});

it('logs a user in independently through each panel, landing on that panel', function (): void {
    User::factory()->create(['email' => 'multi@example.com']);

    $mockUser = function (): OktaUserContract {
        $user = Mockery::mock(OktaUserContract::class);
        $user->shouldReceive('getEmail')->andReturn('multi@example.com');
        $user->shouldReceive('getId')->andReturn(null);

        return $user;
    };

    $admin = Mockery::mock(SocialiteProvider::class);
    $admin->shouldReceive('user')->andReturn($mockUser());
    Socialite::shouldReceive('driver')->with('okta')->andReturn($admin);

    $this->get(route('filament-okta.admin.callback'))->assertRedirect(url('admin'));
    $this->assertAuthenticated();

    $staff = Mockery::mock(SocialiteProvider::class);
    $staff->shouldReceive('user')->andReturn($mockUser());
    Socialite::shouldReceive('driver')->with('okta-staff')->andReturn($staff);

    $this->get(route('filament-okta.staff.callback'))->assertRedirect(url('staff'));
    $this->assertAuthenticated();
});
