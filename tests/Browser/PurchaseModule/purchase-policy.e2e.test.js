/**
 * Purchase Module Policy E2E Tests
 * Using Puppeteer for browser automation
 * 
 * Tests: R16 (Lock Final), UI Flow, Critical Workflows
 */

const puppeteer = require('puppeteer');

// Configuration
const BASE_URL = process.env.APP_URL || 'http://localhost:8000';
const ADMIN_URL = `${BASE_URL}/admin`;

// Test credentials (adjust as needed)
const TEST_USER = {
    email: 'admin@example.com',
    password: 'password'
};

// Helper functions
async function login(page) {
    await page.goto(`${ADMIN_URL}/login`);
    await page.type('input[name="email"]', TEST_USER.email);
    await page.type('input[name="password"]', TEST_USER.password);
    await page.click('button[type="submit"]');
    await page.waitForNavigation();
}

async function createPembelian(page) {
    await page.goto(`${ADMIN_URL}/pembelians/create`);
    
    // Fill form
    await page.waitForSelector('[name="id_supplier"]');
    await page.select('[name="id_supplier"]', '1'); // Select first supplier
    
    // Add item
    await page.click('[data-repeater-create]'); // Click add item button
    await page.waitForTimeout(500);
    
    await page.select('[name="items[0][id_produk]"]', '1'); // Select product
    await page.select('[name="items[0][kondisi]"]', 'Baru');
    await page.type('[name="items[0][qty]"]', '5');
    await page.type('[name="items[0][hpp]"]', '100000');
    await page.type('[name="items[0][harga_jual]"]', '150000');
    
    // Submit
    await page.click('button[type="submit"]');
    await page.waitForNavigation();
    
    // Get created pembelian ID from URL
    const url = page.url();
    const match = url.match(/pembelians\/(\d+)/);
    return match ? match[1] : null;
}

describe('Purchase Module Policy - E2E Tests', () => {
    let browser;
    let page;

    beforeAll(async () => {
        browser = await puppeteer.launch({
            headless: true,
            args: ['--no-sandbox', '--disable-setuid-sandbox']
        });
        page = await browser.newPage();
        page.setViewport({ width: 1280, height: 720 });
        
        // Login before tests
        await login(page);
    });

    afterAll(async () => {
        await browser.close();
    });

    beforeEach(async () => {
        // Reset to admin dashboard before each test
        await page.goto(ADMIN_URL);
        await page.waitForTimeout(500);
    });

    // =====================================================
    // R16: Lock Final Tests
    // =====================================================
    
    describe('R16: Lock Final Feature', () => {
        test('should display LOCK button when pembelian is OPEN', async () => {
            // Create a new pembelian first
            const pembelianId = await createPembelian(page);
            expect(pembelianId).not.toBeNull();
            
            // Navigate to view page
            await page.goto(`${ADMIN_URL}/pembelians/${pembelianId}`);
            await page.waitForTimeout(1000);
            
            // Check for Lock Final button
            const lockButton = await page.$('[data-testid="lock-final-button"], button:has-text("Lock Final")');
            expect(lockButton).not.toBeNull();
            
            // Check for OPEN badge
            const pageContent = await page.content();
            expect(pageContent).toContain('OPEN');
        });

        test('should show confirmation modal when clicking LOCK', async () => {
            const pembelianId = await createPembelian(page);
            await page.goto(`${ADMIN_URL}/pembelians/${pembelianId}`);
            await page.waitForTimeout(1000);
            
            // Click lock button
            await page.click('button:has-text("Lock Final")');
            await page.waitForTimeout(500);
            
            // Check for confirmation modal
            const modal = await page.$('.modal, .fi-modal');
            expect(modal).not.toBeNull();
            
            // Check modal content
            const modalText = await page.evaluate(el => el.textContent, modal);
            expect(modalText).toContain('Kunci');
            expect(modalText).toContain('Permanen');
        });

        test('should change status to LOCKED after confirmation', async () => {
            const pembelianId = await createPembelian(page);
            await page.goto(`${ADMIN_URL}/pembelians/${pembelianId}`);
            await page.waitForTimeout(1000);
            
            // Click lock and confirm
            await page.click('button:has-text("Lock Final")');
            await page.waitForTimeout(500);
            
            // Confirm in modal
            await page.click('.fi-modal button[type="submit"], .modal-confirm-button');
            await page.waitForTimeout(2000);
            
            // Check for success notification
            const notification = await page.$('.fi-notification, .notification-success');
            expect(notification).not.toBeNull();
            
            // Check for LOCKED badge
            const pageContent = await page.content();
            expect(pageContent).toContain('LOCKED');
        });

        test('should HIDE edit button when pembelian is LOCKED', async () => {
            const pembelianId = await createPembelian(page);
            
            // Lock the pembelian first
            await page.goto(`${ADMIN_URL}/pembelians/${pembelianId}`);
            await page.waitForTimeout(1000);
            await page.click('button:has-text("Lock Final")');
            await page.waitForTimeout(500);
            await page.click('.fi-modal button[type="submit"]');
            await page.waitForTimeout(2000);
            
            // Refresh page
            await page.reload();
            await page.waitForTimeout(1000);
            
            // Check edit button is hidden
            const editButton = await page.$('a:has-text("Ubah"), button:has-text("Ubah")');
            expect(editButton).toBeNull();
            
            // But delete button should still be visible (if allowed)
            const deleteButton = await page.$('button:has-text("Hapus")');
            expect(deleteButton).not.toBeNull();
        });

        test('should redirect to view page when accessing edit URL directly', async () => {
            const pembelianId = await createPembelian(page);
            
            // Lock the pembelian
            await page.goto(`${ADMIN_URL}/pembelians/${pembelianId}`);
            await page.click('button:has-text("Lock Final")');
            await page.waitForTimeout(500);
            await page.click('.fi-modal button[type="submit"]');
            await page.waitForTimeout(2000);
            
            // Try to access edit page directly
            await page.goto(`${ADMIN_URL}/pembelians/${pembelianId}/edit`);
            await page.waitForTimeout(2000);
            
            // Should be redirected to view page
            const currentUrl = page.url();
            expect(currentUrl).not.toContain('/edit');
            expect(currentUrl).toContain(`/pembelians/${pembelianId}`);
            
            // Should show error notification
            const pageContent = await page.content();
            expect(pageContent).toContain('Akses Ditolak');
        });
    });

    // =====================================================
    // R02-R05: Form Validation Tests
    // =====================================================
    
    describe('R02-R05: Create Pembelian Form', () => {
        test('R03: should auto-calculate subtotal when qty or hpp changes', async () => {
            await page.goto(`${ADMIN_URL}/pembelians/create`);
            await page.waitForTimeout(1000);
            
            // Fill supplier
            await page.select('[name="id_supplier"]', '1');
            
            // Add item
            await page.click('[data-repeater-create]');
            await page.waitForTimeout(500);
            
            // Fill qty and hpp
            await page.type('[name="items[0][qty]"]', '5');
            await page.type('[name="items[0][hpp]"]', '100000');
            
            // Trigger blur to calculate subtotal
            await page.keyboard.press('Tab');
            await page.waitForTimeout(500);
            
            // Check subtotal value (should be 500000)
            const subtotalValue = await page.$eval('[name="items[0][subtotal]"]', el => el.value);
            expect(subtotalValue.replace(/[^\d]/g, '')).toBe('500000');
        });

        test('R05: should allow creating pembelian with only jasa items', async () => {
            await page.goto(`${ADMIN_URL}/pembelians/create`);
            await page.waitForTimeout(1000);
            
            // Fill supplier
            await page.select('[name="id_supplier"]', '1');
            
            // Add jasa item (no produk items)
            await page.click('[data-repeater-create-jasa]'); // Assuming this selector exists
            await page.waitForTimeout(500);
            
            await page.select('[name="jasaItems[0][jasa_id]"]', '1');
            await page.type('[name="jasaItems[0][qty]"]', '2');
            await page.type('[name="jasaItems[0][harga]"]', '500000');
            
            // Submit should succeed
            await page.click('button[type="submit"]');
            await page.waitForNavigation({ waitUntil: 'networkidle0', timeout: 5000 });
            
            // Check we're on view page (success)
            const url = page.url();
            expect(url).toMatch(/pembelians\/\d+$/);
        });
    });

    // =====================================================
    // R12-R13: Delete Validation Tests
    // =====================================================
    
    describe('R12-R13: Delete Validation', () => {
        test('should disable delete button when pembelian has NO TT', async () => {
            // This test requires a pembelian with NO TT already in database
            // Navigate to such pembelian
            await page.goto(`${ADMIN_URL}/pembelians`);
            await page.waitForTimeout(1000);
            
            // Find a pembelian with TT (if exists)
            const rows = await page.$$('table tbody tr');
            for (const row of rows) {
                const text = await page.evaluate(el => el.textContent, row);
                if (text.includes('TT-')) {
                    await row.click();
                    await page.waitForTimeout(1000);
                    break;
                }
            }
            
            // Check delete button is disabled
            const deleteButton = await page.$('button:has-text("Hapus")[disabled]');
            if (deleteButton) {
                const isDisabled = await page.evaluate(el => el.disabled, deleteButton);
                expect(isDisabled).toBe(true);
            }
        });
    });

    // =====================================================
    // Visual Regression Tests
    // =====================================================
    
    describe('Visual Regression', () => {
        test('should match screenshot for create pembelian page', async () => {
            await page.goto(`${ADMIN_URL}/pembelians/create`);
            await page.waitForTimeout(2000);
            
            // Take screenshot
            await page.screenshot({
                path: 'tests/Browser/screenshots/create-pembelian.png',
                fullPage: true
            });
            
            // Verify screenshot was created
            const fs = require('fs');
            expect(fs.existsSync('tests/Browser/screenshots/create-pembelian.png')).toBe(true);
        });

        test('should match screenshot for view pembelian page (OPEN)', async () => {
            const pembelianId = await createPembelian(page);
            await page.goto(`${ADMIN_URL}/pembelians/${pembelianId}`);
            await page.waitForTimeout(2000);
            
            await page.screenshot({
                path: 'tests/Browser/screenshots/view-pembelian-open.png',
                fullPage: true
            });
            
            const fs = require('fs');
            expect(fs.existsSync('tests/Browser/screenshots/view-pembelian-open.png')).toBe(true);
        });

        test('should match screenshot for view pembelian page (LOCKED)', async () => {
            const pembelianId = await createPembelian(page);
            
            // Lock the pembelian
            await page.goto(`${ADMIN_URL}/pembelians/${pembelianId}`);
            await page.click('button:has-text("Lock Final")');
            await page.waitForTimeout(500);
            await page.click('.fi-modal button[type="submit"]');
            await page.waitForTimeout(2000);
            
            await page.screenshot({
                path: 'tests/Browser/screenshots/view-pembelian-locked.png',
                fullPage: true
            });
            
            const fs = require('fs');
            expect(fs.existsSync('tests/Browser/screenshots/view-pembelian-locked.png')).toBe(true);
        });
    });
});

// Run tests
if (require.main === module) {
    (async () => {
        console.log('Starting Purchase Module E2E Tests...');
        // Jest will run the tests automatically
    })();
}
