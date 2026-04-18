/**
 * Smoke test: Wizard scan camera mode wiring.
 *
 * What this test verifies (without granting real camera permission):
 *   1. /customers/wizard/start loads with status 200.
 *   2. scan-camera.css and scan-camera.js are loaded successfully (200).
 *   3. window.CWCamera exists and exposes isSupported/open/close.
 *   4. Clicking "scan-identity" tries to open the camera UI (overlay visible)
 *      OR transparently falls back to the file picker (still acceptable).
 *   5. The overlay's accessibility tree is well-formed (role=dialog, aria-modal).
 */
const { chromium } = require('playwright');

const BASE_URL    = process.env.WIZARD_BASE || 'https://tayseer.test';
const LOGIN_PATH  = '/user/login';
const WIZARD_PATH = '/customers/wizard/start';
const USERNAME    = process.env.WIZARD_USER || 'osamaqazan89@gmail.com';
const PASSWORD    = process.env.WIZARD_PASS || 'AuditPwd2026!';

(async () => {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        permissions: ['camera'],
        ignoreHTTPSErrors: true,
    });
    const page = await context.newPage();

    const failures = [];
    const log = (msg) => console.log(`[scan-camera] ${msg}`);
    const fail = (msg) => { failures.push(msg); console.error(`  FAIL: ${msg}`); };

    // Track asset loads to verify scan-camera.* delivers 200.
    const assetStatus = {};
    page.on('response', (resp) => {
        const url = resp.url();
        if (/scan-camera\.(js|css)/.test(url)) {
            assetStatus[url] = resp.status();
        }
    });

    try {
        // ── Login ──
        log('logging in…');
        await page.goto(BASE_URL + LOGIN_PATH, { waitUntil: 'domcontentloaded' });
        await page.fill('#login-form-login', USERNAME);
        await page.fill('#login-form-password', PASSWORD);
        await Promise.all([
            page.waitForLoadState('networkidle'),
            page.click('button[type="submit"]'),
        ]);

        // ── Navigate to wizard ──
        log(`navigating to ${WIZARD_PATH}…`);
        let loaded = false;
        for (let attempt = 1; attempt <= 3 && !loaded; attempt++) {
            try {
                await page.goto(BASE_URL + WIZARD_PATH, {
                    waitUntil: 'networkidle',
                    timeout: 30000,
                });
                await page.waitForSelector('#cw-shell', { timeout: 10000 });
                loaded = true;
            } catch (e) {
                log(`  attempt ${attempt} failed: ${e.message}`);
                await page.waitForTimeout(1000);
            }
        }
        if (!loaded) throw new Error('wizard never loaded');

        // ── Verify assets ──
        const expectAsset = (pattern) => {
            const url = Object.keys(assetStatus).find((u) => pattern.test(u));
            if (!url) {
                fail(`asset matching ${pattern} was never requested`);
                return;
            }
            if (assetStatus[url] !== 200) {
                fail(`asset ${url} returned status ${assetStatus[url]}`);
                return;
            }
            log(`  ok  ${url} → ${assetStatus[url]}`);
        };
        expectAsset(/scan-camera\.js/);
        expectAsset(/scan-camera\.css/);

        // ── Verify global API surface ──
        const camApi = await page.evaluate(() => {
            if (typeof window.CWCamera !== 'object' || !window.CWCamera) {
                return { exists: false };
            }
            return {
                exists:        true,
                hasIsSupported: typeof window.CWCamera.isSupported === 'function',
                hasOpen:        typeof window.CWCamera.open === 'function',
                hasClose:       typeof window.CWCamera.close === 'function',
                isSupported:    window.CWCamera.isSupported(),
            };
        });
        if (!camApi.exists)         fail('window.CWCamera is missing');
        if (!camApi.hasIsSupported) fail('CWCamera.isSupported is missing');
        if (!camApi.hasOpen)        fail('CWCamera.open is missing');
        if (!camApi.hasClose)       fail('CWCamera.close is missing');
        log(`  CWCamera ready (isSupported=${camApi.isSupported})`);

        // ── Verify scan button is enabled and primary ──
        const btn = await page.evaluate(() => {
            const el = document.querySelector('[data-cw-action="scan-identity"]');
            if (!el) return null;
            return {
                disabled:   el.disabled,
                classes:    el.className,
                describedBy: el.getAttribute('aria-describedby'),
            };
        });
        if (!btn)              fail('scan-identity button not found');
        if (btn && btn.disabled) fail('scan-identity button is disabled');
        if (btn && !/cw-btn--primary/.test(btn.classes)) {
            fail(`scan button class is "${btn.classes}" — expected to include cw-btn--primary`);
        }
        if (btn && btn.describedBy !== 'cw-scan-hint') {
            fail(`aria-describedby is "${btn.describedBy}" — expected "cw-scan-hint"`);
        }
        log('  scan button looks correct');

        // ── Click and check that EITHER the overlay opens OR file picker is invoked ──
        // We can't actually grant a real video stream in headless chromium without
        // --use-fake-ui-for-media-stream + --use-fake-device-for-media-stream flags,
        // but the overlay should still mount even if getUserMedia rejects.
        let filePickerTriggered = false;
        page.on('filechooser', () => { filePickerTriggered = true; });

        log('clicking scan button…');
        await page.click('[data-cw-action="scan-identity"]');
        await page.waitForTimeout(500);

        const overlayState = await page.evaluate(() => {
            const root = document.querySelector('[data-cwcam-root]');
            if (!root) return { mounted: false };
            return {
                mounted:    true,
                role:       root.getAttribute('role'),
                ariaModal:  root.getAttribute('aria-modal'),
                hasVideo:   !!root.querySelector('[data-cwcam-video]'),
                hasFrame:   !!root.querySelector('[data-cwcam-frame]'),
                hasClose:   !!root.querySelector('[data-cwcam-close]'),
            };
        });

        if (overlayState.mounted) {
            log('  camera overlay mounted');
            if (overlayState.role !== 'dialog')      fail('overlay role should be "dialog"');
            if (overlayState.ariaModal !== 'true')   fail('overlay aria-modal should be "true"');
            if (!overlayState.hasVideo)              fail('overlay missing <video>');
            if (!overlayState.hasFrame)              fail('overlay missing framing guide');
            if (!overlayState.hasClose)              fail('overlay missing close button');

            // Cleanup: close it.
            await page.evaluate(() => window.CWCamera && window.CWCamera.close());
        } else if (filePickerTriggered) {
            log('  camera unsupported → file picker fallback triggered (acceptable)');
        } else {
            fail('neither camera overlay nor file picker was triggered');
        }

    } catch (err) {
        fail(`unexpected error: ${err.message}\n${err.stack}`);
    } finally {
        await browser.close();
    }

    if (failures.length) {
        console.error(`\n[scan-camera] ${failures.length} failure(s)`);
        process.exit(1);
    } else {
        console.log('\n[scan-camera] all checks passed');
        process.exit(0);
    }
})();
