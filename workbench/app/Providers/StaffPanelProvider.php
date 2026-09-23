<?php

declare(strict_types=1);

namespace Workbench\App\Providers;

use BBSLab\FilamentOkta\OktaPlugin;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;

/**
 * A second Filament panel activating the Okta plugin with a *different* Okta
 * configuration and branding than the admin panel — its own Socialite driver,
 * local-only logout, brand name and primary colour — to exercise several panels
 * running side by side.
 */
class StaffPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('staff')
            ->path('staff')
            ->brandName('Acme Staff')
            ->colors(['primary' => Color::Emerald])
            ->login()
            ->authGuard('web')
            ->middleware(['web'])
            ->plugin(
                OktaPlugin::make()
                    ->socialiteDriver('okta-staff')
                    ->ssoLogout(false),
            );
    }
}
