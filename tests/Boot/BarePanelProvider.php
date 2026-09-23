<?php

declare(strict_types=1);

namespace BBSLab\FilamentOkta\Tests\Boot;

use Filament\Panel;
use Filament\PanelProvider;

/**
 * A Filament panel that does NOT activate the Okta plugin, registered alongside
 * the admin/staff panels so packageBooted() must skip it when mounting routes.
 */
class BarePanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('bare')
            ->path('bare')
            ->login()
            ->authGuard('web')
            ->middleware(['web']);
    }
}
