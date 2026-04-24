/**
 * Jest Setup for Puppeteer E2E Tests
 */

// Set default timeout
jest.setTimeout(30000); // 30 seconds

// Global setup
global.APP_URL = process.env.APP_URL || 'http://localhost:8000';

// Helper to handle console errors
beforeAll(() => {
    // Suppress console warnings during tests
    jest.spyOn(console, 'warn').mockImplementation(() => {});
});

afterAll(() => {
    jest.restoreAllMocks();
});
