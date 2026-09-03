const { defineConfig, devices } = require('@playwright/test');

const outputDir = process.env.PLAYWRIGHT_OUTPUT_DIR || '/workspace/artifacts/manual';
const baseURL = process.env.FDSHOP_BASE_URL || 'http://joomla';

module.exports = defineConfig({
  testDir: './tests',
  outputDir,
  fullyParallel: false,
  forbidOnly: true,
  retries: 0,
  workers: 1,
  reporter: [['line']],
  timeout: 30_000,
  expect: { timeout: 5_000 },
  use: {
    ...devices['Desktop Chrome'],
    browserName: 'chromium',
    baseURL,
    headless: process.env.PW_HEADLESS !== '0',
    screenshot: 'only-on-failure',
    trace: 'retain-on-failure',
    video: 'off',
    launchOptions: {
      args: [`--unsafely-treat-insecure-origin-as-secure=${baseURL}`],
    },
    actionTimeout: 10_000,
    navigationTimeout: 15_000,
  },
  projects: [{
    name: 'chromium',
    use: { ...devices['Desktop Chrome'] },
  }],
});
