/**
 * lh-resumen.js — Ejecuta Lighthouse e imprime SOLO:
 *   - puntajes por categoría (performance, accessibility, best-practices, seo)
 *   - métricas: LCP, CLS, TBT, FCP, SI
 *   - auditorías con score < 1 (título y ahorro estimado)
 *
 * Uso:
 *   node lh-resumen.js <url> [preset]
 *   preset: mobile (default) | desktop
 */
'use strict';

const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

const url = process.argv[2];
const preset = process.argv[3] || 'mobile';

if (!url) {
  console.error('Uso: node lh-resumen.js <url> [mobile|desktop]');
  process.exit(1);
}

const outPath = path.resolve('./lh.json');

const chromeFlags = preset === 'desktop' ? '--chrome-flags="--headless=new"' : '--chrome-flags="--headless=new"';
const presetFlag = preset === 'desktop' ? '--preset=desktop' : '';

execSync(
  `npx lighthouse "${url}" --only-categories=performance,accessibility,best-practices,seo --output=json --output-path="${outPath}" --quiet ${chromeFlags} ${presetFlag}`,
  { stdio: 'inherit', encoding: 'utf8', maxBuffer: 50 * 1024 * 1024 }
);

const report = JSON.parse(fs.readFileSync(outPath, 'utf8'));
const cats = report.categories;
const audits = report.audits;

function fmtMs(ms) { return ms ? Math.round(ms) + ' ms' : 'n/d'; }
function fmtScore(s) { return s == null ? 'n/d' : (Math.round(s * 100)); }

console.log(`\n=== Lighthouse ${preset.toUpperCase()} — ${url} ===\n`);
console.log('Categorías:');
console.log(`  Performance : ${fmtScore(cats.performance?.score)} / 100`);
console.log(`  Accesibilidad: ${fmtScore(cats.accessibility?.score)} / 100`);
console.log(`  Buenas prácticas: ${fmtScore(cats['best-practices']?.score)} / 100`);
console.log(`  SEO         : ${fmtScore(cats.seo?.score)} / 100`);

console.log('\nMétricas clave:');
const metricMap = {
  'largest-contentful-paint': 'LCP',
  'cumulative-layout-shift': 'CLS',
  'total-blocking-time': 'TBT',
  'first-contentful-paint': 'FCP',
  'speed-index': 'SI'
};
for (const [auditId, label] of Object.entries(metricMap)) {
  const a = audits[auditId];
  console.log(`  ${label}: ${a ? fmtMs(a.numericValue) : 'n/d'}  (score: ${a ? fmtScore(a.score) : 'n/d'})`);
}

console.log('\nAuditorías con score < 1:');
let found = false;
for (const [id, a] of Object.entries(audits)) {
  if (a.score != null && a.score < 1) {
    const title = a.title || id;
    const details = a.details || {};
    const savings = details.overallSavingsMs != null
      ? `ahorro estimado: ${Math.round(details.overallSavingsMs)} ms`
      : details.estimatedBytesSavings != null
        ? `ahorro estimado: ${(details.estimatedBytesSavings / 1024).toFixed(1)} KB`
        : '';
    console.log(`  - ${title} ${savings}`);
    found = true;
  }
}
if (!found) {
  console('  (ninguna)');
}

console.log('\nJSON completo guardado en:', outPath, '\n');
