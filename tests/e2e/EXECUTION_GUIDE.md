# Playwright E2E Tests - Execution Guide

## Prerequisites

1. **Lando environment running:**
   ```bash
   lando start
   # Verify app responds
   curl -k https://zuora-workflows.lndo.site
   ```

2. **Database accessible:**
   ```bash
   lando ssh -c "php artisan migrate:status"
   ```

3. **User exists in database:**
   ```bash
   lando artisan tinker --execute="echo User::first()?->email;"
   ```

## Step 1: Setup Authentication

Run authentication setup once to create persistent auth state:

```bash
npx playwright test --project=setup
```

**Expected output:**
- Creates `tests/e2e/fixtures/auth.json`
- Login successful
- Test passes

## Step 2: Run E2E Tests with Neuron Provider

```bash
# Default provider (neuron)
npm run test:e2e

# Or explicitly
AI_PROVIDER=neuron npm run test:e2e
```

## Step 3: Run E2E Tests with Laragent Provider

```bash
AI_PROVIDER=laragent npm run test:e2e
```

## Step 4: View Results

### HTML Report
```bash
npm run test:e2e:report
```

### Screenshots
```bash
ls tests/e2e/screenshots/
```

### Failure Artifacts
```bash
ls test-results/
```

## Interactive Debugging

Run tests in UI mode for debugging:

```bash
npm run test:e2e:ui
```

## Test Scenarios Covered

1. ✅ **Access chat threads list page**
   - Verifies navigation to /admin/chat-threads
   - Checks page heading visibility

2. ✅ **Create new chat thread and navigate**
   - Creates new thread or uses existing
   - Navigates to thread detail page

3. ✅ **Send message and receive AI response**
   - Fills message input
   - Sends message
   - Waits for AI response (60s timeout)
   - Captures screenshot

4. ✅ **Verify streaming response behavior**
   - Tests real-time streaming
   - Verifies loading indicator appears/disappears
   - Captures streaming completion screenshot

5. ✅ **View chat history and previous messages**
   - Loads existing thread
   - Verifies message history display
   - Screenshots chat history

6. ✅ **Retry failed message**
   - Tests retry button functionality
   - Verifies retry triggers new request

7. ✅ **View SQL query in collapsible section**
   - Expands SQL query section
   - Verifies SQL code visibility
   - Screenshots SQL display

8. ✅ **Input field disabled during AI processing**
   - Checks textarea disabled state
   - Prevents duplicate submissions

9. ✅ **Laragent provider scenario**
   - Same tests with AI_PROVIDER=laragent
   - Verifies provider switching works

## Environment Variables

Create `.env.testing` with:

```env
TEST_USER_EMAIL=rawsar@gmail.com
TEST_USER_PASSWORD=password
APP_URL=https://zuora-workflows.lndo.site
```

## Troubleshooting

### Tests fail with "element not found"
- Check Lando is running: `lando start`
- Verify URL is accessible: `curl -k https://zuora-workflows.lndo.site`
- Re-run auth setup: `npx playwright test --project=setup`

### Authentication fails
- Verify user exists: `lando artisan tinker --execute="User::first();"`
- Check credentials in `.env.testing`
- Delete old auth state: `rm tests/e2e/fixtures/auth.json`

### Database connection errors
- Check Lando services: `lando info`
- Restart Lando: `lando restart`
- Check database: `lando mariadb`

### Timeout errors (30s+)
- Increase timeout in `playwright.config.ts`: `timeout: 60000`
- Or per-test: `test.setTimeout(60000)`

## CI/CD Integration

For GitHub Actions or similar:

```yaml
- name: Install Playwright
  run: npx playwright install chromium

- name: Run E2E tests (neuron)
  run: npm run test:e2e
  env:
    AI_PROVIDER: neuron

- name: Run E2E tests (laragent)
  run: npm run test:e2e
  env:
    AI_PROVIDER: laragent

- name: Upload test results
  uses: actions/upload-artifact@v3
  if: always()
  with:
    name: playwright-report
    path: tests/reports/playwright-report
```

## Performance Expectations

When environment is stable:
- Authentication setup: ~5-10 seconds
- Each test: 5-30 seconds (depending on AI response time)
- Full suite (9 tests): ~3-5 minutes
- With both providers: ~6-10 minutes

## Visual Regression Testing

Screenshots are captured at key points:
- `ai-response-received.png` - After AI responds
- `streaming-complete.png` - After streaming ends
- `chat-history.png` - Chat history view
- `retry-functionality.png` - Retry button state
- `sql-query-visible.png` - SQL collapsible section
- `laragent-response.png` - Laragent provider response

Compare screenshots between:
- Neuron vs Laragent providers
- Before/after migrations
- Different browser environments
