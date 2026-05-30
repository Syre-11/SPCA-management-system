#!/usr/bin/env node
/**
 * Builds a static site in dist/ for GitHub Pages from the PHP project.
 */
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '..');
const DIST = path.join(ROOT, 'dist');
const REPO_NAME = process.env.GITHUB_REPOSITORY?.split('/')[1] || 'SPCA-management-system';
const BASE_PATH = process.env.BASE_PATH ?? `/${REPO_NAME}/`;

const SKIP_DIRS = new Set([
  '.git',
  'dist',
  'node_modules',
  'scripts',
  'backups',
]);

const SKIP_FILES = new Set(['errors.log', '.gitignore']);

const HANDLER_ONLY = new Set([
  'login.php',
  'registeruser.php',
  'processupdateuser.php',
  'savepassword.php',
  'delete_user.php',
  'delete_animal.php',
  'delete.php',
  'deletedonation.php',
  'deletereport.php',
  'restoreReport.php',
  'logout.php',
  'update.php',
  'fetch_alerts.php',
  'get_all_kennels.php',
  'get_kennel_data.php',
  'allocate_kennel.php',
  'verifyidentity.php',
  'databaseconnection.php',
]);

function rmrf(dir) {
  if (fs.existsSync(dir)) fs.rmSync(dir, { recursive: true, force: true });
}

function ensureDir(p) {
  fs.mkdirSync(p, { recursive: true });
}

function copyFile(src, dest) {
  ensureDir(path.dirname(dest));
  fs.copyFileSync(src, dest);
}

function copyTree(srcDir, destDir) {
  if (!fs.existsSync(srcDir)) return;
  ensureDir(destDir);
  for (const ent of fs.readdirSync(srcDir, { withFileTypes: true })) {
    if (SKIP_DIRS.has(ent.name.toLowerCase())) continue;
    const s = path.join(srcDir, ent.name);
    const d = path.join(destDir, ent.name);
    if (ent.isDirectory()) copyTree(s, d);
    else if (!SKIP_FILES.has(ent.name.toLowerCase())) copyFile(s, d);
  }
}

function stripPhp(content) {
  const htmlStart = content.search(/<!DOCTYPE\s+html|<html[\s>]/i);
  let body = htmlStart >= 0 ? content.slice(htmlStart) : content;
  body = body.replace(/<\?php[\s\S]*?\?>/gi, '');
  body = body.replace(/<\?=[\s\S]*?\?>/gi, '');
  return body;
}

function fixLinks(content, relDepth) {
  const prefix = relDepth > 0 ? '../'.repeat(relDepth) : '';
  let c = content.replace(/\.php\b/gi, '.html');
  c = c.replace(/href=["']([^"']*)["']/gi, (match, url) => {
    if (/^(https?:|#|mailto:|javascript:)/i.test(url)) return match;
    if (url.startsWith('/') || url.includes('://')) return match;
    return `href="${prefix}${url}"`;
  });
  c = c.replace(/action=["']([^"']*)["']/gi, (match, url) => {
    if (/^(https?:|#|javascript:)/i.test(url)) return match;
    const handler = url.split('/').pop()?.toLowerCase().replace(/\.html$/, '.php');
    if (HANDLER_ONLY.has(handler) || /login\.html$/i.test(url)) {
      return 'action="#"';
    }
    if (!url.includes('://') && !url.startsWith('/')) {
      return `action="${prefix}${url.replace(/\.php$/i, '.html')}"`;
    }
    return match;
  });
  return c;
}

function injectBootstrap(html, depth) {
  const base = depth > 0 ? '../'.repeat(depth) : '';
  const meta = `<meta name="spca-base" content="${BASE_PATH}">`;
  const scripts = `
<script src="${base}js/spca-bootstrap.js" defer></script>`;
  let out = html;
  if (!out.includes('name="spca-base"')) {
    out = out.replace(/<head([^>]*)>/i, `<head$1>\n  ${meta}`);
  }
  if (!out.includes('spca-bootstrap.js')) {
    out = out.replace(/<\/body>/i, `${scripts}\n</body>`);
  }
  return out;
}

function depthFromRoot(filePath) {
  const rel = path.relative(DIST, filePath);
  const parts = rel.split(path.sep);
  return Math.max(0, parts.length - 2);
}

function folderDepth(relToDist) {
  const dir = path.dirname(relToDist);
  if (dir === '.' || dir === '') return 0;
  return dir.split(path.sep).filter(Boolean).length;
}

function processHtmlContent(content, relToDist) {
  const depth = folderDepth(relToDist);
  content = fixLinks(content, depth);
  content = injectBootstrap(content, depth);
  return content;
}

function convertPhpFile(srcPath, destPath) {
  const base = path.basename(srcPath).toLowerCase();
  if (HANDLER_ONLY.has(base)) return false;

  let content = fs.readFileSync(srcPath, 'utf8');
  if (!/<!DOCTYPE|<html/i.test(content)) return false;

  const relToDist = path.relative(DIST, destPath.replace(/\.php$/i, '.html'));
  content = stripPhp(content);
  content = processHtmlContent(content, relToDist);
  ensureDir(path.dirname(destPath));
  fs.writeFileSync(destPath.replace(/\.php$/i, '.html'), content, 'utf8');
  return true;
}

function convertHtmlFile(srcPath, destPath) {
  let content = fs.readFileSync(srcPath, 'utf8');
  const relToDist = path.relative(DIST, destPath);
  content = processHtmlContent(content, relToDist);
  ensureDir(path.dirname(destPath));
  fs.writeFileSync(destPath, content, 'utf8');
}

function walkPhp(dir, rel = '') {
  for (const ent of fs.readdirSync(dir, { withFileTypes: true })) {
    if (SKIP_DIRS.has(ent.name)) continue;
    const relPath = path.join(rel, ent.name);
    const full = path.join(dir, ent.name);
    if (ent.isDirectory()) {
      if (ent.name.toLowerCase() === 'backups') continue;
      walkPhp(full, relPath);
    } else if (ent.name.toLowerCase().endsWith('.php')) {
      const dest = path.join(DIST, relPath);
      convertPhpFile(full, dest);
    }
  }
}

function main() {
  console.log('Building static site → dist/');
  console.log('Base path:', BASE_PATH);
  rmrf(DIST);
  ensureDir(DIST);

  copyTree(path.join(ROOT, 'css'), path.join(DIST, 'css'));
  copyTree(path.join(ROOT, 'images'), path.join(DIST, 'images'));
  copyTree(path.join(ROOT, 'js'), path.join(DIST, 'js'));
  copyTree(path.join(ROOT, 'data'), path.join(DIST, 'data'));
  copyTree(path.join(ROOT, 'About us'), path.join(DIST, 'About us'));

  for (const sub of [
    'Adopt and Volunteer',
    'animal_intake_system',
    'Cruelty Reports',
    'DONATIONS',
    'medicalrecords',
    'registerUser',
    'Notifications',
  ]) {
    const cssDir = path.join(ROOT, sub);
    if (!fs.existsSync(cssDir)) continue;
    for (const ent of fs.readdirSync(cssDir, { withFileTypes: true })) {
      if (ent.isFile() && /\.(css|png|jpg|jpeg|gif|svg|webp)$/i.test(ent.name)) {
        copyFile(
          path.join(cssDir, ent.name),
          path.join(DIST, sub, ent.name)
        );
      }
    }
  }

  function walkHtml(dir, rel = '') {
    if (!fs.existsSync(dir)) return;
    for (const ent of fs.readdirSync(dir, { withFileTypes: true })) {
      if (SKIP_DIRS.has(ent.name) || ent.name.toLowerCase() === 'backups') continue;
      const relPath = path.join(rel, ent.name);
      const full = path.join(dir, ent.name);
      if (ent.isDirectory()) walkHtml(full, relPath);
      else if (/\.html?$/i.test(ent.name)) {
        convertHtmlFile(full, path.join(DIST, relPath));
      }
    }
  }
  walkHtml(ROOT);

  let index = fs.readFileSync(path.join(DIST, 'frontPage.html'), 'utf8');
  fs.writeFileSync(path.join(DIST, 'index.html'), index, 'utf8');

  walkPhp(ROOT);

  fs.writeFileSync(path.join(DIST, '.nojekyll'), '');
  fs.writeFileSync(
    path.join(DIST, 'README-DEPLOY.txt'),
    `Static build for GitHub Pages.\nBase path: ${BASE_PATH}\nDemo logins: see README.md\n`,
    'utf8'
  );

  console.log('Done. Output:', DIST);
}

main();
