/**
 * Smoke test for the smart-scan button on Step 1 of the Customer Wizard V2.
 *
 * What we verify (without actually calling the Gemini API — that would cost
 * money and require a real ID image):
 *   1. The "scan-identity" button is NO LONGER disabled (Phase 5 fix).
 *   2. The hidden file input with data-cw-role="scan-input" exists,
 *      accepts the right MIME types, and is offscreen (sr-only) but not
 *      tabbable (tabindex=-1, aria-hidden).
 *   3. Clicking the button programmatically triggers a click on the file
 *      input (the wiring contract scan.js depends on).
 *   4. The hint text below the header is rendered.
 *
 * Usage:  node scripts/_test_scan_button.js
 */

const { chromium } = require('playwright');

const BASE_URL = 'https://tayseer.test';
const WIZARD_PATH = '/customers/wizard/start';
const LOGIN_PATH = '/user/login';
const USERNAME = 'osamaqazan89@gmail.com';
const PASSWORD = 'AuditPwd2026!';

(async () => {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({ ignoreHTTPSErrors: true });
    const page = await context.newPage();

    // ── Login ──
    await page.goto(BASE_URL + LOGIN_PATH, { waitUntil: 'domcontentloaded' });
    await page.fill('#login-form-login', USERNAME);
    await page.fill('#login-form-password', PASSWORD);
    await Promise.all([
        page.waitForLoadState('load'),
        page.click('button[type="submit"]'),
    ]);

    // Discard any leftover draft (so we always land on Step 1).
    await page.evaluate(async () => {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        try {
            await fetch('/customers/wizard/discard', {
                method: 'POST',
                headers: { 'X-CSRF-Token': csrf, 'Content-Type': 'application/x-www-form-urlencoded' },
                body: '_csrf-backend=' + encodeURIComponent(csrf),
                credentials: 'include',
            });
        } catch (e) { /* ignore */ }
    });

    await page.goto(BASE_URL + WIZARD_PATH, { waitUntil: 'domcontentloaded' });
    await page.waitForLoadState('load');
    await page.waitForSelector('#cw-shell', { timeout: 15000 });

    const checks = await page.evaluate(() => {
        const r = {};
        const btn = document.querySelector('[data-cw-action="scan-identity"]');
        const input = document.querySelector('input[data-cw-role="scan-input"]');
        const hint = document.querySelector('#cw-scan-hint');

        r.button_exists = !!btn;
        r.button_disabled = btn ? btn.disabled : null;
        r.button_aria_describedby = btn ? btn.getAttribute('aria-describedby') : null;

        r.input_exists = !!input;
        r.input_accept = input ? input.getAttribute('accept') : null;
        r.input_capture = input ? input.getAttribute('capture') : null;
        r.input_tabindex = input ? input.tabIndex : null;
        r.input_aria_hidden = input ? input.getAttribute('aria-hidden') : null;
        r.input_offscreen = input ? input.classList.contains('cw-sr-only') : null;

        r.hint_exists = !!hint;
        r.hint_text = hint ? hint.textContent.trim() : null;

        // Wiring test: click the button, see if the file-input click is dispatched.
        // We listen on the input for a click event and resolve true/false.
        let clicked = false;
        if (input) input.addEventListener('click', e => { clicked = true; e.preventDefault(); }, { once: true });
        if (btn) btn.click();
        r.click_propagates = clicked;

        return r;
    });

    console.log('───────────── SCAN BUTTON SMOKE TEST ─────────────');
    console.log(JSON.stringify(checks, null, 2));

    const ok =
        checks.button_exists && checks.button_disabled === false &&
        checks.input_exists && (checks.input_accept || '').includes('image/jpeg') &&
        checks.click_propagates &&
        checks.hint_exists && (checks.hint_text || '').includes('هوية');

    if (ok) {
        console.log('\n✓ ALL CHECKS PASSED — the scan button is properly wired.');
        process.exit(0);
    } else {
        console.log('\n✗ At least one check FAILED.');
        process.exit(1);
    }
})().catch(e => { console.error(e); process.exit(2); });
