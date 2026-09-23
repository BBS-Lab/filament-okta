<?php

declare(strict_types=1);

namespace Workbench\App\Providers;

use BBSLab\FilamentOkta\OktaPlugin;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;

/**
 * A minimal Filament panel that activates the Okta plugin, for exercising the
 * package end to end. It carries a custom brand name and primary colour so the
 * tests can prove the Okta button adopts the panel's branding.
 */
class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('admin')
            ->brandName('Acme Admin')
            ->colors(['primary' => Color::Rose])
            ->login()
            ->authGuard('web')
            ->middleware(['web'])
            ->plugin(OktaPlugin::make());
    }
}
