import { expect, test } from '@playwright/test';

// The full SSO round-trip needs a real Okta org, so these live scenarios cover
// the login screen and the start of the OIDC redirect (which is all the package
// controls before handing off to Okta).

test.describe('Okta login on a Filament panel', () => {
    test('the admin login screen shows the Okta button linking to the login route', async ({ page }) => {
        await page.goto('/admin/login');

        const button = page.locator('#filament-okta-login');

        await expect(button).toBeVisible();
        await expect(button).toContainText('Log In with Okta');
        await expect(button).toHaveAttribute('href', /\/admin\/authorization-code\/redirect$/);
    });

    test('the login route starts the Okta OIDC authorization redirect', async ({ request }) => {
        const response = await request.get('/admin/authorization-code/redirect', { maxRedirects: 0 });

        expect(response.status()).toBe(302);
        expect(response.headers()['location']).toContain('example.okta.com');
        expect(response.headers()['location']).toContain('/oauth2/v1/authorize');
        expect(response.headers()['location']).toContain('client_id=');
    });

    test('each panel keeps its own Okta button target', async ({ page }) => {
        await page.goto('/staff/login');

        await expect(page.locator('#filament-okta-login'))
            .toHaveAttribute('href', /\/staff\/authorization-code\/redirect$/);
    });

    test('the Okta button adopts each panel primary colour', async ({ page }) => {
        const bg = async (path: string): Promise<string> => {
            await page.goto(path);

            return page.locator('#filament-okta-login')
                .evaluate((el) => getComputedStyle(el).backgroundColor);
        };

        const admin = await bg('/admin/login'); // panel primary: Rose
        const staff = await bg('/staff/login'); // panel primary: Emerald

        // Each panel colours the button with its own primary — they differ...
        expect(admin).not.toBe(staff);
        expect(admin).toBeTruthy();
        // ...and it is no longer the previously hard-coded Okta blue.
        expect(admin).not.toBe('rgb(0, 125, 193)');
        expect(staff).not.toBe('rgb(0, 125, 193)');
    });
});
