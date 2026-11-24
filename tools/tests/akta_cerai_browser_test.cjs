const { chromium } = require('playwright');
const { spawn } = require('node:child_process');
const net = require('node:net');
const fs = require('node:fs');
const path = require('node:path');
const repo = path.resolve(__dirname, '../..');
const output = path.join(repo, 'output/playwright/akta-cerai');
const assert = (value, label) => { if (!value) throw new Error(label); console.log('PASS: ' + label); };
(async () => {
  const socket = net.createServer();
  await new Promise(resolve => socket.listen(0, '127.0.0.1', resolve));
  const port = socket.address().port;
  await new Promise(resolve => socket.close(resolve));
  const server = spawn(process.env.KASUARI_TEST_PHP || 'C:/xampp/php/php.exe', ['-S', '127.0.0.1:' + port, '-t', repo],
    { cwd: repo, windowsHide: true, env: { ...process.env, KASUARI_AKTA_PREVIEW: '1' }, stdio: 'ignore' });
  let browser;
  try {
    const base = 'http://127.0.0.1:' + port;
    for (let i = 0; i < 50; i++) {
      try { await fetch(base + '/assets/css/kasuari-modern.css'); break; } catch { await new Promise(r => setTimeout(r, 100)); }
    }
    browser = await chromium.launch({ headless: true, executablePath: process.env.KASUARI_TEST_CHROMIUM || undefined });
    fs.mkdirSync(output, { recursive: true });
    for (const [name, width, height] of [['desktop', 1366, 1000], ['mobile', 390, 1100]]) {
      const page = await browser.newPage({ viewport: { width, height } });
      const errors = [];
      page.on('pageerror', error => errors.push(error.message));
      await page.goto(base + '/tools/tests/fixtures/akta_preview.php', { waitUntil: 'networkidle' });
      assert(await page.locator('.ks-akta-fields').count() === 1, name + ': shared detail component rendered.');
      assert(await page.locator('.ks-akta-status').count() === 4, name + ': issued/missing/unsynced summaries rendered.');
      const overflow = await page.evaluate(() => document.documentElement.scrollWidth > innerWidth + 1);
      assert(!overflow, name + ': no horizontal document overflow.');
      const badges = await page.locator('.ks-akta-status .badge').evaluateAll(nodes => nodes.map(node => {
        const s = getComputedStyle(node);
        return { color: s.color, background: s.backgroundColor, fits: node.scrollWidth <= node.clientWidth + 1 };
      }));
      assert(badges.every(badge => badge.fits && badge.color !== badge.background), name + ': badge labels fit and remain visible.');
      assert(errors.length === 0, name + ': no JavaScript errors.');
      await page.screenshot({ path: path.join(output, name + '.png'), fullPage: true });
      await page.goto(base + '/tools/tests/fixtures/akta_preview.php?nonadmin=1');
      assert(await page.locator('.ks-akta-status').count() === 0 && !(await page.content()).includes('123/AC/2026/PA.Test'), name + ': non-admin response contains no akta metadata.');
      await page.close();
    }
  } finally {
    if (browser) await browser.close();
    server.kill();
    await new Promise(resolve => server.once('exit', resolve));
  }
})().catch(error => { console.error(error); process.exitCode = 1; });
