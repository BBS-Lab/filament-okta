<?php

declare(strict_types=1);

namespace BBSLab\FilamentOkta\Tests;

use BBSLab\FilamentOkta\FilamentOktaServiceProvider;
use BBSLab\LaravelOkta\LaravelOktaServiceProvider;
use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\QueryBuilder\QueryBuilderServiceProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Event;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as Orchestra;
use SocialiteProviders\Manager\ServiceProvider as SocialiteManagerServiceProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Okta\Provider;
use Workbench\App\Models\User;
use Workbench\App\Providers\AdminPanelProvider;
use Workbench\App\Providers\StaffPanelProvider;

abstract class TestCase extends Orchestra
{
    use WithWorkbench;

    /**
     * @param  Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            // Filament's stack (auto-discovered in a real app; listed explicitly in
            // the isolated harness so the `filament` container binding exists).
            BladeIconsServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            LivewireServiceProvider::class,
            SupportServiceProvider::class,
            ActionsServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            NotificationsServiceProvider::class,
            SchemasServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            QueryBuilderServiceProvider::class,
            FilamentServiceProvider::class,
            // socialiteproviders/manager is a deferred provider, so register it
            // explicitly — it binds the Socialite factory and dispatches SocialiteWasCalled.
            SocialiteManagerServiceProvider::class,
            // The framework-agnostic base (auto-discovered in a real app): owns the
            // Socialite driver, controller, resolver and facade hooks.
            LaravelOktaServiceProvider::class,
            FilamentOktaServiceProvider::class,
            // The workbench Filament panels that activate the Okta plugin — a second
            // one (staff) with a different Okta configuration proves several panels
            // run side by side. (For `composer serve`, the same panels plus the
            // WorkbenchServiceProvider are listed in testbench.yaml.)
            AdminPanelProvider::class,
            StaffPanelProvider::class,
        ];
    }

    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        // Point auth — and therefore the default resolver — at the workbench user.
        $app['config']->set('auth.providers.users.model', User::class);

        // Most tests exercise matching/routing, not email verification.
        $app['config']->set('okta.require_verified_email', false);

        // Fake Okta credentials so the Socialite okta driver can be built.
        $app['config']->set('services.okta', [
            'client_id' => 'test-client-id',
            'client_secret' => 'test-client-secret',
            'redirect' => 'https://app.test/admin/authorization-code/callback',
            'base_url' => 'https://example.okta.com',
        ]);

        // The staff panel uses its own Okta application (a distinct driver with its
        // own org), so a consumer would register that driver themselves. No
        // 'redirect' — it must be derived from the staff panel's callback route.
        $app['config']->set('services.okta-staff', [
            'client_id' => 'staff-client-id',
            'client_secret' => 'staff-client-secret',
            'base_url' => 'https://staff.example.okta.com',
        ]);

        Event::listen(function (SocialiteWasCalled $event): void {
            $event->extendSocialite('okta-staff', Provider::class);
        });
    }
}
