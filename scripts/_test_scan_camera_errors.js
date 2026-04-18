/**
 * Test the camera error pathways: simulate 3 failure modes and verify the
 * overlay shows precise, actionable messages (not generic ones).
 *
 * Modes covered:
 *   1. Insecure context        — pretends window.isSecureContext === false
 *   2. Permission denied       — getUserMedia rejects with NotAllowedError
 *   3. No camera device        — getUserMedia rejects with NotFoundError
 */
const { chromium } = require('playwright');

const BASE_URL    = process.env.WIZARD_BASE || 'https://tayseer.test';
const LOGIN_PATH  = '/user/login';
const WIZARD_PATH = '/customers/wizard/start';
const USERNAME    = 'osamaqazan89@gmail.com';
const PASSWORD    = 'AuditPwd2026!';

(async () => {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({ ignoreHTTPSErrors: true });
    const page = await context.newPage();
    const failures = [];
    const log  = (m) => console.log(`[error-paths] ${m}`);
    const fail = (m) => { failures.push(m); console.error(`  FAIL: ${m}`); };

    try {
        await page.goto(BASE_URL + LOGIN_PATH, { waitUntil: 'domcontentloaded' });
        await page.fill('#login-form-login',    USERNAME);
        await page.fill('#login-form-password', PASSWORD);
        await Promise.all([
            page.waitForLoadState('networkidle'),
            page.click('button[type="submit"]'),
        ]);

        for (let i = 0; i < 3; i++) {
            try {
                await page.goto(BASE_URL + WIZARD_PATH, { waitUntil: 'networkidle', timeout: 30000 });
                await page.waitForSelector('#cw-shell', { timeout: 10000 });
                break;
            } catch (e) { await page.waitForTimeout(800); }
        }
        log('wizard loaded');

        // ── Helper: open camera, expect error overlay, verify text. ──
        async function checkError(label, setup, expectInDetail) {
            log(`\n— ${label} —`);
            // Reset between scenarios.
            await page.evaluate(() => window.CWCamera && window.CWCamera.close && window.CWCamera.close());

            await page.evaluate(setup);
            await page.evaluate(() => {
                window.CWCamera.open({
                    scanUrl:   '/customers/wizard/scan',
                    csrfToken: document.querySelector('meta[name="csrf-token"]')?.content || '',
                    onError:   () => {},
                });
            });
            await page.waitForTimeout(600);

            const result = await page.evaluate(() => {
                const root = document.querySelector('[data-cwcam-root]');
                if (!root) return { mounted: false };
                const errVisible = !root.querySelector('[data-cwcam-error]')?.hidden;
                return {
                    mounted:    true,
                    errVisible: errVisible,
                    title:      root.querySelector('[data-cwcam-error-title]')?.textContent || '',
                    detail:     root.querySelector('[data-cwcam-error-detail]')?.textContent || '',
                    diag:       root.querySelector('[data-cwcam-diag]')?.textContent || '',
                    fallback:   !!root.querySelector('[data-cwcam-fallback]'),
                };
            });

            if (!result.mounted)    { fail(`${label}: overlay never mounted`); return; }
            if (!result.errVisible) { fail(`${label}: error overlay not visible`); return; }
            if (!result.title)      { fail(`${label}: title is empty`); return; }
            if (!result.detail)     { fail(`${label}: detail is empty`); return; }
            if (!result.fallback)   { fail(`${label}: fallback button missing`); return; }

            const haystack = result.title + ' ' + result.detail;
            for (const needle of expectInDetail) {
                if (!haystack.includes(needle)) {
                    fail(`${label}: expected text "${needle}" not found.\n   title=${result.title}\n   detail=${result.detail}`);
                }
            }
            log(`  title:  ${result.title}`);
            log(`  detail: ${result.detail.substring(0, 120)}…`);
            log(`  diag:   ${result.diag.substring(0, 80).replace(/\s+/g, ' ')}…`);
        }

        // ── Case 1: Insecure context. ──
        await checkError(
            'insecure context (HTTP)',
            () => {
                Object.defineProperty(window, 'isSecureContext', { value: false, configurable: true });
            },
            ['HTTPS', 'https://']
        );

        // Restore secure context.
        await page.evaluate(() => {
            Object.defineProperty(window, 'isSecureContext', { value: true, configurable: true });
        });

        // ── Case 2: NotAllowedError. ──
        await checkError(
            'permission denied',
            () => {
                navigator.mediaDevices.getUserMedia = () => Promise.reject(
                    Object.assign(new Error('denied'), { name: 'NotAllowedError' })
                );
                if (navigator.permissions) {
                    navigator.permissions.query = () => Promise.resolve({ state: 'prompt' });
                }
            },
            ['رفض', 'إذن']
        );

        // ── Case 3: NotFoundError. ──
        await checkError(
            'no camera device',
            () => {
                navigator.mediaDevices.getUserMedia = () => Promise.reject(
                    Object.assign(new Error('no device'), { name: 'NotFoundError' })
                );
            },
            ['كاميرا']
        );

    } catch (err) {
        fail(`unexpected error: ${err.message}\n${err.stack}`);
    } finally {
        await browser.close();
    }

    if (failures.length) {
        console.error(`\n[error-paths] ${failures.length} failure(s)`);
        process.exit(1);
    }
    console.log('\n[error-paths] all checks passed');
    process.exit(0);
})();
