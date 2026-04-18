/**
 * Live test for /customers/wizard/scan endpoint:
 *   1. Login.
 *   2. POST without a file → expect { ok:false, error:"لم يتم استلام ملف..." }.
 *   3. POST a tiny invalid file → expect graceful error message.
 *
 * We DO NOT POST a real ID image (Gemini API costs); that path is verified by
 * unit-style code review of the response mapping.
 */

const { chromium } = require('playwright');

const BASE_URL = 'https://tayseer.test';
const LOGIN_PATH = '/user/login';
const USERNAME = 'osamaqazan89@gmail.com';
const PASSWORD = 'AuditPwd2026!';

(async () => {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({ ignoreHTTPSErrors: true });
    const page = await context.newPage();

    await page.goto(BASE_URL + LOGIN_PATH, { waitUntil: 'domcontentloaded' });
    await page.fill('#login-form-login', USERNAME);
    await page.fill('#login-form-password', PASSWORD);
    await Promise.all([
        page.waitForLoadState('networkidle'),
        page.click('button[type="submit"]'),
    ]);
    // Navigate to wizard with retry so cookies settle.
    for (let i = 0; i < 3; i++) {
        try {
            await page.goto(BASE_URL + '/customers/wizard/start', { waitUntil: 'networkidle', timeout: 25000 });
            const cnt = await page.locator('#cw-shell').count();
            if (cnt > 0) break;
        } catch (e) { /* retry */ }
    }
    console.log('Landed on:', page.url());
    const hasShell = await page.locator('#cw-shell').count();
    if (!hasShell) {
        console.error('Login session not preserved — aborting');
        process.exit(3);
    }

    // Test 1: POST without file
    const noFile = await page.evaluate(async () => {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const fd = new FormData();
        fd.append('_csrf-backend', csrf);
        const r = await fetch('/customers/wizard/scan', {
            method: 'POST',
            headers: { 'X-CSRF-Token': csrf, 'X-Requested-With': 'XMLHttpRequest' },
            body: fd,
            credentials: 'include',
        });
        const text = await r.text();
        let body;
        try { body = JSON.parse(text); } catch (e) { body = { raw: text.slice(0, 400) }; }
        return { status: r.status, body, csrf_len: csrf.length };
    });
    console.log('TEST 1 — POST without file:');
    console.log(JSON.stringify(noFile, null, 2));

    // Test 2: POST a tiny non-image text file
    const badFile = await page.evaluate(async () => {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const fd = new FormData();
        fd.append('_csrf-backend', csrf);
        const blob = new Blob(['not an image'], { type: 'text/plain' });
        fd.append('file', blob, 'fake.txt');
        const r = await fetch('/customers/wizard/scan', {
            method: 'POST',
            headers: { 'X-CSRF-Token': csrf },
            body: fd,
            credentials: 'include',
        });
        return { status: r.status, body: await r.json() };
    });
    console.log('\nTEST 2 — POST text file (wrong MIME):');
    console.log(JSON.stringify(badFile, null, 2));

    // Test 3: GET (should be rejected by VerbFilter — POST only)
    const wrongVerb = await page.evaluate(async () => {
        const r = await fetch('/customers/wizard/scan', { credentials: 'include' });
        return { status: r.status };
    });
    console.log('\nTEST 3 — GET (wrong verb):');
    console.log(JSON.stringify(wrongVerb, null, 2));

    const ok =
        noFile.status === 200 && noFile.body.ok === false && /استلام/.test(noFile.body.error || '') &&
        badFile.status === 200 && badFile.body.ok === false && /غير مدعوم/.test(badFile.body.error || '') &&
        (wrongVerb.status === 405 || wrongVerb.status === 400);

    console.log(ok ? '\n✓ ALL ENDPOINT CHECKS PASSED' : '\n✗ Endpoint check failed');
    process.exit(ok ? 0 : 1);
})().catch(e => { console.error(e); process.exit(2); });
