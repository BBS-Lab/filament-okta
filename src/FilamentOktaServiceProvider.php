<?php

declare(strict_types=1);

namespace BBSLab\FilamentOkta;

use BBSLab\FilamentOkta\Support\FilamentOktaPanel;
use BBSLab\LaravelOkta\Contracts\OktaPanel;
use BBSLab\LaravelOkta\Support\NullOktaPanel;
use BBSLab\LaravelOkta\Support\OktaRoutes;
use Filament\Facades\Filament;
use Filament\Panel;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentOktaServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('filament-okta')
            ->hasTranslations()
            ->hasViews();
    }

    public function packageRegistered(): void
    {
        // Resolve the OktaPanel for the panel serving the current request. The
        // okta routes carry Filament's "panel:{id}" middleware, so the current
        // panel is set by the time the base controller resolves this binding.
        // Overrides the base default (NullOktaPanel).
        $this->app->bind(OktaPanel::class, function (): OktaPanel {
            $panel = Filament::getCurrentPanel();

            if ($panel instanceof Panel && $panel->hasPlugin(OktaPlugin::ID)) {
                $plugin = $panel->getPlugin(OktaPlugin::ID);

                if ($plugin instanceof OktaPlugin) {
                    return new FilamentOktaPanel($panel, $plugin);
                }
            }

            return new NullOktaPanel;
        });
    }

    public function packageBooted(): void
    {
        // Mount the okta routes for every panel that activates the plugin. Panels
        // are all registered by the boot phase, so this covers each one, and the
        // routes are plain (controller + string middleware) — safe under route:cache.
        foreach (Filament::getPanels() as $panel) {
            $plugin = $panel->hasPlugin(OktaPlugin::ID) ? $panel->getPlugin(OktaPlugin::ID) : null;

            if ($plugin instanceof OktaPlugin) {
                OktaRoutes::register(new FilamentOktaPanel($panel, $plugin));
            }
        }
    }
}
