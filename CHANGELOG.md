# Changelog

All notable changes to `bbs-lab/filament-okta` will be documented in this file.

## v2.0.0 - 2026-09-25

### ⚠️ Breaking

- Requires [`bbs-lab/laravel-okta` v2.0](https://github.com/BBS-Lab/laravel-okta/releases/tag/v2.0.0), which **changes the default Okta route paths** from `okta/*` to `authorization-code/*`:

  | Purpose | Before | After (default) |
  |---------|--------|-----------------|
  | Login | `{panel-path}/okta/login` | `{panel-path}/authorization-code/redirect` |
  | Callback (redirect URI) | `{panel-path}/okta/callback` | `{panel-path}/authorization-code/callback` |
  | Logout | `{panel-path}/okta/logout` | `{panel-path}/authorization-code/logout` |
  | Post-logout landing | `{panel-path}/okta/callback/logout` | `{panel-path}/authorization-code/callback/logout` |

  **Action required:** update each panel's **Sign-in** and **Sign-out redirect URIs** in the Okta admin console, or logins fail with a `redirect_uri` mismatch (400). An explicit `services.{driver}.redirect` still wins.

- The route **names are unchanged** (`filament-okta.{panel}.login`, `.callback`, `.logout`, `.callback.logout`), so the login button and the derived redirect URI keep working.

### ✨ Added

- **Per-panel route paths** via `OktaPlugin::make()->paths(login: …, callback: …, logout: …, callbackLogout: …)`. Only the arguments you pass are overridden; the rest fall back to the shared `config('okta.paths.*')` then the defaults above. `FilamentOktaPanel` exposes them through the new `OktaPanel::path()` seam.

## v1.0.0 - 2026-09-23

Okta SSO for Filament — the Filament adapter for [bbs-lab/laravel-okta](https://github.com/BBS-Lab/laravel-okta).

### ✨ Features

- `OktaPlugin`, activatable **per panel** (`$panel->plugin(OktaPlugin::make())`), so each panel can run its own Okta configuration (Socialite driver, SSO logout, verified-email, identifier) — options left unset fall back to the shared `config('okta.*')`.
- Adds a **Log In with Okta** button to the panel's login screen (via the `AUTH_LOGIN_FORM_AFTER` render hook) and mounts the base `okta/login`, `okta/callback`, `okta/logout`, `okta/callback/logout` routes for the panel under its path.
- Route names are panel-scoped (`filament-okta.{panelId}.*`) and the routes reuse the panel's own middleware (including `panel:{id}`), so `Filament::getCurrentPanel()` resolves the right panel per request and several panels run side by side.
- Reuses the base flow entirely: the Socialite driver, the login/callback/logout controller, the resolver and the `Okta` lifecycle hooks come from `bbs-lab/laravel-okta`.
- The Okta button is rendered with Filament's button component, so it inherits each panel's primary colour and branding.
- Denied/failed logins are surfaced as a Filament danger notification on the panel login screen.
- `OKTA_REDIRECT_URI` is optional — each panel derives its own redirect_uri from its `okta/callback` route (via `bbs-lab/laravel-okta`).

### 📦 Requirements

- PHP 8.2+, Laravel 11/12/13, Filament 5.
