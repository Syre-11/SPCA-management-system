#!/usr/bin/env node
/** Embeds data/mockdb.json into js/spca-mock-seed.js for offline / GitHub Pages use. */
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const json = fs.readFileSync(path.join(root, 'data', 'mockdb.json'), 'utf8');
const out = `// Auto-generated from data/mockdb.json — run: npm run embed:seed\nwindow.SPCA_MOCK_SEED = ${json.trim()};\n`;
fs.writeFileSync(path.join(root, 'js', 'spca-mock-seed.js'), out);
console.log('Wrote js/spca-mock-seed.js');
