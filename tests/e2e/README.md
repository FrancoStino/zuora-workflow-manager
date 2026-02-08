# End-to-End Testing with Playwright

## Setup

```bash
npm install
npx playwright install chromium
```

## Running Tests

### All tests (neuron provider)
```bash
npm run test:e2e
```

### With Laragent provider
```bash
AI_PROVIDER=laragent npm run test:e2e
```

### Interactive UI mode
```bash
npm run test:e2e:ui
```

### View HTML report
```bash
npm run test:e2e:report
```

## Test Scenarios

1. **Access chat threads list page**
2. **Create new chat thread**
3. **Send message and receive AI response**
4. **Verify streaming response behavior**
5. **View chat history**
6. **Retry failed messages**
7. **View SQL queries in collapsible sections**
8. **Input disabled during processing**

## Screenshots

Screenshots are automatically captured:
- `tests/e2e/screenshots/` - Visual regression screenshots
- On test failures - Automatic failure screenshots

## Configuration

- `playwright.config.ts` - Main configuration
- `.env.testing` - Test environment variables
- `tests/e2e/auth.setup.ts` - Authentication setup

## Notes

- Tests use headless Chromium by default
- Self-signed certificates are ignored for Lando
- Traces and videos recorded on failures
- HTML report generated after each run
