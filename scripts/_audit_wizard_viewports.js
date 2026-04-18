/**
 * Multi-viewport responsive audit for the Customer Wizard V2.
 *
 * For each device size we:
 *   1. Navigate to /customers/wizard/start (logged in via stored cookies).
 *   2. Take a full-page screenshot at that exact viewport.
 *   3. Programmatically measure:
 *        - horizontal overflow (any element wider than viewport)
 *        - stepper readability (label visibility per step)
 *        - touch target sizes (min 44px)
 *        - sticky toolbar position
 *        - title wrapping behavior
 *
 * Usage:  node scripts/_audit_wizard_viewports.js
 * Output: ux-audit/responsive/<device>.png + <device>.json
 */

const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const BASE_URL = 'https://tayseer.test';
const WIZARD_PATH = '/customers/wizard/start';
const LOGIN_PATH = '/user/login';
const USERNAME = 'osamaqazan89@gmail.com';   // login field accepts email or username
const PASSWORD = 'AuditPwd2026!';            // set by scripts/_set_admin_test_pwd.php

const OUT_DIR = path.join(__dirname, '..', 'ux-audit', 'responsive');
fs.mkdirSync(OUT_DIR, { recursive: true });

const VIEWPORTS = [
    { name: '01-iphone-se',         w: 320,  h: 568,  label: 'iPhone SE (smallest)' },
    { name: '02-fold-closed',       w: 280,  h: 653,  label: 'Galaxy Z Fold5 (cover)' },
    { name: '03-iphone-13-mini',    w: 375,  h: 812,  label: 'iPhone 13 mini' },
    { name: '04-iphone-pro-max',    w: 414,  h: 896,  label: 'iPhone Pro Max' },
    { name: '05-fold-open',         w: 884,  h: 1104, label: 'Galaxy Z Fold5 (open)' },
    { name: '06-ipad-portrait',     w: 768,  h: 1024, label: 'iPad portrait' },
    { name: '07-ipad-landscape',    w: 1024, h: 768,  label: 'iPad landscape' },
    { name: '08-laptop',            w: 1366, h: 768,  label: 'Standard laptop' },
    { name: '09-desktop-fhd',       w: 1920, h: 1080, label: 'Desktop Full HD' },
    { name: '10-desktop-2k',        w: 2560, h: 1440, label: 'Desktop 2K' },
];

(async () => {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        ignoreHTTPSErrors: true,
        bypassCSP: true,
    });
    const page = await context.newPage();

    // ── 1. Log in once. ──
    await page.goto(BASE_URL + LOGIN_PATH, { waitUntil: 'networkidle' });
    await page.fill('#login-form-login', USERNAME);
    const pwd = await page.locator('#login-form-password').count();
    if (pwd > 0) await page.fill('#login-form-password', PASSWORD);
    await Promise.all([
        page.waitForLoadState('networkidle'),
        page.click('button[type="submit"]'),
    ]);

    const results = [];

    for (const vp of VIEWPORTS) {
        await page.setViewportSize({ width: vp.w, height: vp.h });
        try {
            await page.goto(BASE_URL + WIZARD_PATH, { waitUntil: 'domcontentloaded', timeout: 30000 });
        } catch (e) {
            // networkidle/aborts can happen on first nav; retry once.
            await page.waitForTimeout(800);
            await page.goto(BASE_URL + WIZARD_PATH, { waitUntil: 'domcontentloaded', timeout: 30000 });
        }
        await page.waitForLoadState('load').catch(() => {});
        await page.waitForSelector('#cw-shell', { timeout: 15000 });
        await page.waitForTimeout(400); // let CSS settle

        // ── 2. Programmatic measurements (run in page context). ──
        const metrics = await page.evaluate(() => {
            const r = {
                vw: window.innerWidth,
                vh: window.innerHeight,
                docScrollW: document.documentElement.scrollWidth,
                hOverflow: document.documentElement.scrollWidth > window.innerWidth
                    ? document.documentElement.scrollWidth - window.innerWidth : 0,
                shell: null,
                stepper: null,
                steps: [],
                touchTargets: [],
                titleWrap: null,
                navStuck: null,
                overflowingChildren: [],
            };

            const shell = document.querySelector('#cw-shell');
            if (shell) {
                const rect = shell.getBoundingClientRect();
                r.shell = { w: Math.round(rect.width), h: Math.round(rect.height) };
            }

            const stepperList = document.querySelector('.cw-stepper__list');
            if (stepperList) {
                r.stepper = {
                    scrollW: stepperList.scrollWidth,
                    clientW: stepperList.clientWidth,
                    overflow: stepperList.scrollWidth > stepperList.clientWidth,
                };
            }

            document.querySelectorAll('[data-cw-step]').forEach(s => {
                const rect = s.getBoundingClientRect();
                const label = s.querySelector('.cw-step__label');
                const labelStyle = label ? window.getComputedStyle(label) : null;
                r.steps.push({
                    n: s.getAttribute('data-cw-step'),
                    w: Math.round(rect.width),
                    h: Math.round(rect.height),
                    isCurrent: s.getAttribute('aria-current') === 'step',
                    labelVisible: labelStyle ? labelStyle.display !== 'none' : false,
                });
            });

            // ── Touch target audit (WCAG 2.5.8 — ≥24px, target ≥44px). ──
            document.querySelectorAll('#cw-shell button, #cw-shell a').forEach(el => {
                if (el.offsetParent === null) return;
                const rect = el.getBoundingClientRect();
                if (rect.width === 0 || rect.height === 0) return;
                const min = Math.min(rect.width, rect.height);
                if (min < 44) {
                    r.touchTargets.push({
                        text: (el.innerText || el.getAttribute('aria-label') || '').trim().slice(0, 30),
                        w: Math.round(rect.width),
                        h: Math.round(rect.height),
                        cls: el.className.split(' ').slice(0, 2).join('.'),
                    });
                }
            });

            const title = document.querySelector('.cw-header__title');
            if (title) {
                const rect = title.getBoundingClientRect();
                const lh = parseFloat(window.getComputedStyle(title).lineHeight);
                r.titleWrap = {
                    h: Math.round(rect.height),
                    lineHeight: lh,
                    estimatedLines: Math.round(rect.height / lh),
                };
            }

            const nav = document.querySelector('.cw-nav');
            if (nav) {
                const rect = nav.getBoundingClientRect();
                r.navStuck = {
                    bottom: Math.round(window.innerHeight - rect.bottom),
                    inViewport: rect.top < window.innerHeight && rect.bottom > 0,
                };
            }

            // Identify any element wider than the viewport (responsive break culprits).
            document.querySelectorAll('#cw-shell *').forEach(el => {
                if (el.offsetParent === null) return;
                const rect = el.getBoundingClientRect();
                if (rect.width > window.innerWidth + 1) {
                    r.overflowingChildren.push({
                        tag: el.tagName.toLowerCase(),
                        cls: el.className.split(' ').slice(0, 2).join('.'),
                        w: Math.round(rect.width),
                    });
                }
            });

            return r;
        });

        // ── 3. Capture full-page screenshot at the actual viewport. ──
        const png = path.join(OUT_DIR, vp.name + '.png');
        await page.screenshot({ path: png, fullPage: true });

        const json = path.join(OUT_DIR, vp.name + '.json');
        fs.writeFileSync(json, JSON.stringify({ viewport: vp, metrics }, null, 2));

        // ── 4. Quick verdict. ──
        const issues = [];
        if (metrics.hOverflow > 0) issues.push(`H-overflow: ${metrics.hOverflow}px`);
        if (metrics.stepper && metrics.stepper.overflow) issues.push('stepper-overflow');
        if (metrics.touchTargets.length > 0) issues.push(`${metrics.touchTargets.length} small-target(s)`);
        if (metrics.overflowingChildren.length > 0) issues.push(`${metrics.overflowingChildren.length} overflowing element(s)`);
        if (metrics.titleWrap && metrics.titleWrap.estimatedLines > 3) issues.push(`title wraps ${metrics.titleWrap.estimatedLines} lines`);

        const verdict = issues.length === 0 ? 'OK' : ('ISSUES: ' + issues.join(', '));
        console.log(
            `[${vp.w}x${vp.h}] ${vp.label.padEnd(28)}  ${verdict}`
        );

        results.push({ viewport: vp, verdict, issues, metrics });
    }

    fs.writeFileSync(
        path.join(OUT_DIR, '_summary.json'),
        JSON.stringify(results, null, 2)
    );

    await browser.close();
    console.log(`\nDone. Output → ${OUT_DIR}`);
})().catch(err => { console.error(err); process.exit(1); });
