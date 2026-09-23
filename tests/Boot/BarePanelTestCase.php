<?php

declare(strict_types=1);

namespace BBSLab\FilamentOkta\Tests\Boot;

use BBSLab\FilamentOkta\Tests\TestCase;
use Illuminate\Foundation\Application;

/**
 * Boots the package with an extra plugin-less panel present, so the packageBooted
 * loop iterates a panel whose plugin lookup yields null.
 */
class BarePanelTestCase extends TestCase
{
    /**
     * @param  Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [...parent::getPackageProviders($app), BarePanelProvider::class];
    }
}
