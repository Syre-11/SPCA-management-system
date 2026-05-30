#!/usr/bin/env node
/**
 * Validates static build: broken CSS/image links and missing assets.
 */
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const DIST = path.join(__dirname, '..', 'dist');

const SKIP_URL = /^(https?:|data:|mailto:|javascript:|#)/i;
const SKIP_FILES = /\(old-do_not use\)/i;
const HANDLER_STUBS = new Set([
  'login.html',
  'registeruser.html',
  'deletedonation.html',
  'restoreReport.html',
  'delete_animal.html',
  'delete_user.html',
  'update.html',
  'verifyidentity.html',
  'processupdateuser.html',
  'savepassword.html',
]);

function walk(dir, files = []) {
  for (const ent of fs.readdirSync(dir, { withFileTypes: true })) {
    const full = path.join(dir, ent.name);
    if (ent.isDirectory()) walk(full, files);
    else if (/\.html?$/i.test(ent.name) && !SKIP_FILES.test(ent.name)) files.push(full);
  }
  return files;
}

function extractUrls(html) {
  const urls = [];
  const re = /(?:href|src)=["']([^"']+)["']/gi;
  let m;
  while ((m = re.exec(html))) urls.push(m[1]);
  return urls;
}

function resolveUrl(fromFile, url) {
  if (SKIP_URL.test(url)) return null;
  const clean = url.split('?')[0].split('#')[0];
  if (!clean || clean.endsWith('=') || clean.endsWith('/') || /\?[^"']*=$/.test(url)) return null;
  const base = path.basename(clean).toLowerCase();
  if (HANDLER_STUBS.has(base)) return null;
  if (/^uploads\/?$/i.test(clean)) return null;
  const dir = path.dirname(fromFile);
  return path.normalize(path.join(dir, clean));
}

function main() {
  if (!fs.existsSync(DIST)) {
    console.error('dist/ not found. Run: npm run build:static');
    process.exit(1);
  }

  const htmlFiles = walk(DIST);
  const missing = [];
  let checked = 0;

  for (const file of htmlFiles) {
    const html = fs.readFileSync(file, 'utf8');
    for (const url of extractUrls(html)) {
      const resolved = resolveUrl(file, url);
      if (!resolved) continue;
      checked++;
      if (!fs.existsSync(resolved)) {
        missing.push({
          page: path.relative(DIST, file),
          url,
          resolved: path.relative(DIST, resolved),
        });
      }
    }
  }

  if (missing.length) {
    console.error(`\n❌ ${missing.length} broken asset link(s):\n`);
    for (const m of missing) {
      console.error(`  ${m.page}`);
      console.error(`    → ${m.url} (expected: ${m.resolved})\n`);
    }
    process.exit(1);
  }

  console.log(`✅ Validated ${htmlFiles.length} HTML pages, ${checked} asset references — all OK`);
}

main();
