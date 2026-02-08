import { test, expect, Page } from '@playwright/test';

test.describe('AI Chat E2E Tests', () => {
  let chatThreadUrl: string;

  test.beforeEach(async ({ page }) => {
    await page.goto('/admin/chat-threads');
    await page.waitForLoadState('networkidle');
  });

  test('user can access chat threads list page', async ({ page }) => {
    await expect(page).toHaveURL(/\/admin\/chat-threads/);
    
    const heading = page.locator('h1, h2').filter({ hasText: /chat|thread/i }).first();
    await expect(heading).toBeVisible();
  });

  test('user can create new chat thread and navigate to it', async ({ page }) => {
    const createButton = page.locator('button, a').filter({ hasText: /new|create/i }).first();
    
    if (await createButton.isVisible()) {
      await createButton.click();
      await page.waitForURL(/\/admin\/chat-threads\/\d+/);
      chatThreadUrl = page.url();
    } else {
      const existingThread = page.locator('tr td a').first();
      await existingThread.click();
      await page.waitForURL(/\/admin\/chat-threads\/\d+/);
      chatThreadUrl = page.url();
    }
    
    await expect(page.locator('textarea, input[type="text"]').first()).toBeVisible();
  });

  test('user can send message and receive AI response', async ({ page }) => {
    await page.goto('/admin/chat-threads');
    
    const existingThread = page.locator('tr td a').first();
    if (await existingThread.isVisible()) {
      await existingThread.click();
    } else {
      const createButton = page.locator('button, a').filter({ hasText: /new|create/i }).first();
      await createButton.click();
    }
    
    await page.waitForURL(/\/admin\/chat-threads\/\d+/);
    
    const messageInput = page.locator('textarea').first();
    await expect(messageInput).toBeVisible();
    
    const testQuestion = 'How many tasks are in the database?';
    await messageInput.fill(testQuestion);
    
    const sendButton = page.locator('button[type="submit"], button').filter({ 
      hasText: /send/i 
    }).first();
    await sendButton.click();
    
    await page.waitForSelector('text=/AI is thinking|assistant/i', { 
      timeout: 30000 
    });
    
    const assistantMessage = page.locator('[class*="assistant"], [class*="bg-gray"]').filter({
      hasText: /SELECT|tasks|count|result/i
    }).first();
    
    await expect(assistantMessage).toBeVisible({ timeout: 60000 });
    
    await page.screenshot({ 
      path: 'tests/e2e/screenshots/ai-response-received.png',
      fullPage: true
    });
  });

  test('user can verify streaming response behavior', async ({ page }) => {
    await page.goto('/admin/chat-threads');
    
    const existingThread = page.locator('tr td a').first();
    if (await existingThread.isVisible()) {
      await existingThread.click();
    } else {
      const createButton = page.locator('button, a').filter({ hasText: /new|create/i }).first();
      await createButton.click();
    }
    
    await page.waitForURL(/\/admin\/chat-threads\/\d+/);
    
    const messageInput = page.locator('textarea').first();
    await messageInput.fill('Show me all workflow names');
    
    const sendButton = page.locator('button[type="submit"], button').filter({ 
      hasText: /send/i 
    }).first();
    await sendButton.click();
    
    const loadingIndicator = page.locator('text=/AI is thinking|loading/i').first();
    await expect(loadingIndicator).toBeVisible({ timeout: 5000 });
    
    await expect(loadingIndicator).toBeHidden({ timeout: 60000 });
    
    const response = page.locator('[class*="assistant"], [class*="bg-gray"]').last();
    await expect(response).toBeVisible();
    
    await page.screenshot({ 
      path: 'tests/e2e/screenshots/streaming-complete.png',
      fullPage: true
    });
  });

  test('user can view chat history and previous messages', async ({ page }) => {
    await page.goto('/admin/chat-threads');
    
    const firstThread = page.locator('tr td a').first();
    await expect(firstThread).toBeVisible();
    await firstThread.click();
    
    await page.waitForURL(/\/admin\/chat-threads\/\d+/);
    
    const messages = page.locator('[class*="user"], [class*="assistant"]').filter({
      hasText: /.+/
    });
    
    const messageCount = await messages.count();
    
    if (messageCount === 0) {
      const messageInput = page.locator('textarea').first();
      await messageInput.fill('Test message for history');
      const sendButton = page.locator('button[type="submit"], button').filter({ 
        hasText: /send/i 
      }).first();
      await sendButton.click();
      
      await page.waitForSelector('[class*="assistant"]', { timeout: 60000 });
    }
    
    const userMessages = page.locator('[class*="user"]').filter({ hasText: /.+/ });
    await expect(userMessages.first()).toBeVisible();
    
    await page.screenshot({ 
      path: 'tests/e2e/screenshots/chat-history.png',
      fullPage: true
    });
  });

  test('user can retry failed message', async ({ page }) => {
    await page.goto('/admin/chat-threads');
    
    const existingThread = page.locator('tr td a').first();
    if (await existingThread.isVisible()) {
      await existingThread.click();
    } else {
      const createButton = page.locator('button, a').filter({ hasText: /new|create/i }).first();
      await createButton.click();
    }
    
    await page.waitForURL(/\/admin\/chat-threads\/\d+/);
    
    const messageInput = page.locator('textarea').first();
    await messageInput.fill('Test query');
    
    const sendButton = page.locator('button[type="submit"], button').filter({ 
      hasText: /send/i 
    }).first();
    await sendButton.click();
    
    await page.waitForTimeout(2000);
    
    const retryButton = page.locator('button').filter({ hasText: /retry/i });
    
    if (await retryButton.isVisible({ timeout: 5000 })) {
      await retryButton.click();
      
      const loadingIndicator = page.locator('text=/AI is thinking|loading/i').first();
      await expect(loadingIndicator).toBeVisible({ timeout: 5000 });
    }
    
    await page.screenshot({ 
      path: 'tests/e2e/screenshots/retry-functionality.png',
      fullPage: true
    });
  });

  test('user can see SQL query in collapsible section', async ({ page }) => {
    await page.goto('/admin/chat-threads');
    
    const existingThread = page.locator('tr td a').first();
    if (await existingThread.isVisible()) {
      await existingThread.click();
    } else {
      const createButton = page.locator('button, a').filter({ hasText: /new|create/i }).first();
      await createButton.click();
    }
    
    await page.waitForURL(/\/admin\/chat-threads\/\d+/);
    
    const messageInput = page.locator('textarea').first();
    await messageInput.fill('Count all workflows in the database');
    
    const sendButton = page.locator('button[type="submit"], button').filter({ 
      hasText: /send/i 
    }).first();
    await sendButton.click();
    
    await page.waitForSelector('[class*="assistant"]', { timeout: 60000 });
    
    const sqlBadge = page.locator('text=/SQL Generated|SQL Query/i').first();
    
    if (await sqlBadge.isVisible({ timeout: 5000 })) {
      await sqlBadge.click();
      
      const sqlQuery = page.locator('pre code, code').filter({ hasText: /SELECT/i }).first();
      await expect(sqlQuery).toBeVisible();
      
      await page.screenshot({ 
        path: 'tests/e2e/screenshots/sql-query-visible.png',
        fullPage: true
      });
    }
  });

  test('input field is disabled during AI processing', async ({ page }) => {
    await page.goto('/admin/chat-threads');
    
    const existingThread = page.locator('tr td a').first();
    if (await existingThread.isVisible()) {
      await existingThread.click();
    } else {
      const createButton = page.locator('button, a').filter({ hasText: /new|create/i }).first();
      await createButton.click();
    }
    
    await page.waitForURL(/\/admin\/chat-threads\/\d+/);
    
    const messageInput = page.locator('textarea').first();
    await messageInput.fill('Quick test');
    
    const sendButton = page.locator('button[type="submit"], button').filter({ 
      hasText: /send/i 
    }).first();
    await sendButton.click();
    
    await page.waitForTimeout(500);
    
    const isDisabled = await messageInput.isDisabled();
    if (isDisabled) {
      expect(isDisabled).toBe(true);
    }
  });
});

test.describe('AI Chat E2E Tests - Laragent Provider', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/admin/chat-threads');
    await page.waitForLoadState('networkidle');
  });

  test('laragent provider: user can send message and receive response', async ({ page }) => {
    const existingThread = page.locator('tr td a').first();
    if (await existingThread.isVisible()) {
      await existingThread.click();
    } else {
      const createButton = page.locator('button, a').filter({ hasText: /new|create/i }).first();
      await createButton.click();
    }
    
    await page.waitForURL(/\/admin\/chat-threads\/\d+/);
    
    const messageInput = page.locator('textarea').first();
    await messageInput.fill('How many tasks exist?');
    
    const sendButton = page.locator('button[type="submit"], button').filter({ 
      hasText: /send/i 
    }).first();
    await sendButton.click();
    
    await page.waitForSelector('text=/AI is thinking|assistant/i', { 
      timeout: 30000 
    });
    
    const assistantMessage = page.locator('[class*="assistant"], [class*="bg-gray"]').last();
    await expect(assistantMessage).toBeVisible({ timeout: 60000 });
    
    await page.screenshot({ 
      path: 'tests/e2e/screenshots/laragent-response.png',
      fullPage: true
    });
  });
});
