import { test as setup, expect } from '@playwright/test';
import { fileURLToPath } from 'url';
import { dirname, join } from 'path';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

const authFile = join(__dirname, 'fixtures', 'auth.json');

setup('authenticate', async ({ page }) => {
  await page.goto('/admin/login');
  
  await page.fill('input[type="email"]', process.env.TEST_USER_EMAIL || 'admin@example.com');
  await page.fill('input[type="password"]', process.env.TEST_USER_PASSWORD || 'password');
  
  await page.click('button[type="submit"]');
  
  await expect(page).toHaveURL(/\/admin/);
  
  await page.context().storageState({ path: authFile });
});
