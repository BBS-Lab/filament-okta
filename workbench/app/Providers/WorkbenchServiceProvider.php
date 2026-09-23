<?php

declare(strict_types=1);

namespace Workbench\App\Providers;

use Illuminate\Support\ServiceProvider;
use Workbench\App\Models\User;

/**
 * Configures the served workbench (composer serve) so the live Playwright
 * scenarios have a working app: the workbench user model, demo Okta credentials
 * (so the button redirects to the OIDC authorize endpoint), and a lenient
 * verified-email default.
 */
class WorkbenchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        config([
            'auth.providers.users.model' => User::class,
            'okta.require_verified_email' => false,
            'services.okta' => [
                'client_id' => 'demo-client-id',
                'client_secret' => 'demo-client-secret',
                'redirect' => env('APP_URL', 'http://127.0.0.1:8123').'/admin/okta/callback',
                'base_url' => 'https://example.okta.com',
            ],
        ]);
    }
}
