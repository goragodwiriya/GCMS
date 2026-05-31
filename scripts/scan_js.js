#!/usr/bin/env node
// ============================================================
// Now.js JavaScript Security Scanner
// ใช้งาน: node scan_js.js <path-to-nowjs>
// ============================================================

const fs = require('fs');
const path = require('path');

const TARGET = process.argv[2] || '.';
const REPORT_DIR = path.join(__dirname, '..', 'reports');
const TIMESTAMP = new Date().toISOString().replace(/[:.]/g, '-').slice(0, 19);
const REPORT_JSON = path.join(REPORT_DIR, `nowjs_js_${TIMESTAMP}.json`);
const REPORT_MD   = path.join(REPORT_DIR, `nowjs_js_${TIMESTAMP}.md`);

if (!fs.existsSync(REPORT_DIR)) fs.mkdirSync(REPORT_DIR, { recursive: true });

// ─── Helpers ──────────────────────────────────────────────────
const colors = {
  reset: '\x1b[0m', bold: '\x1b[1m',
  red: '\x1b[31m', orange: '\x1b[33m',
  yellow: '\x1b[93m', green: '\x1b[32m',
  cyan: '\x1b[36m', white: '\x1b[37m'
};

const findings = [];
const counts = { CRITICAL: 0, HIGH: 0, MEDIUM: 0, LOW: 0, INFO: 0 };

function found(severity, message, file = '', line = '', code = '') {
  counts[severity] = (counts[severity] || 0) + 1;
  const colorMap = { CRITICAL: colors.red, HIGH: colors.orange, MEDIUM: colors.yellow, LOW: colors.green, INFO: colors.cyan };
  const c = colorMap[severity] || colors.white;
  console.log(`${c}[${severity}]${colors.reset} ${message}`);
  if (file) console.log(`       ${colors.bold}File:${colors.reset} ${file}${line ? ` (line ${line})` : ''}`);
  if (code) console.log(`       ${colors.bold}Code:${colors.reset} ${code.slice(0, 120)}`);
  findings.push({ severity, message, file, line: String(line), code: code.slice(0, 150) });
}

function log(msg) {
  console.log(`${colors.cyan}[SCAN]${colors.reset} ${msg}`);
}

// ─── File Walker ──────────────────────────────────────────────
function walkFiles(dir, exts = ['.js', '.html', '.htm', '.mjs', '.ts']) {
  const results = [];
  if (!fs.existsSync(dir)) return results;
  const entries = fs.readdirSync(dir, { withFileTypes: true });
  for (const entry of entries) {
    const fullPath = path.join(dir, entry.name);
    if (entry.isDirectory()) {
      if (!['node_modules', '.git', 'dist', 'build', 'coverage'].includes(entry.name)) {
        results.push(...walkFiles(fullPath, exts));
      }
    } else if (exts.some(e => entry.name.endsWith(e))) {
      results.push(fullPath);
    }
  }
  return results;
}

function scanFileForPatterns(file, patterns) {
  let content;
  try { content = fs.readFileSync(file, 'utf8'); } catch { return; }
  const lines = content.split('\n');
  for (const { regex, severity, message } of patterns) {
    lines.forEach((line, idx) => {
      if (regex.test(line)) {
        found(severity, message, file, idx + 1, line.trim());
      }
    });
  }
}

// ─── SECTION 1: XSS ───────────────────────────────────────────
function scanXSS() {
  log('Scanning XSS vulnerabilities...');
  const files = walkFiles(TARGET, ['.js', '.html', '.htm', '.ts', '.mjs']);

  const patterns = [
    { regex: /innerHTML\s*=\s*(?!['"`]<(?:div|span|p|br))/,        severity: 'CRITICAL', message: 'innerHTML assignment — XSS risk' },
    { regex: /outerHTML\s*=/,                                         severity: 'CRITICAL', message: 'outerHTML assignment — XSS risk' },
    { regex: /document\.write\s*\(/,                                  severity: 'CRITICAL', message: 'document.write() — XSS risk' },
    { regex: /\beval\s*\(/,                                           severity: 'CRITICAL', message: 'eval() usage — code injection risk' },
    { regex: /new\s+Function\s*\(/,                                   severity: 'HIGH',     message: 'new Function() — dynamic code execution' },
    { regex: /insertAdjacentHTML\s*\(/,                               severity: 'HIGH',     message: 'insertAdjacentHTML — verify sanitization' },
    { regex: /\$\([^)]+\)\.html\s*\(/,                                severity: 'HIGH',     message: 'jQuery .html() with dynamic content' },
    { regex: /dangerouslySetInnerHTML/,                                severity: 'HIGH',     message: 'dangerouslySetInnerHTML — React XSS risk' },
    { regex: /location\.href\s*=.*\+/,                                severity: 'MEDIUM',   message: 'Open redirect via string concat in href' },
  ];

  files.forEach(f => scanFileForPatterns(f, patterns));
}

// ─── SECTION 2: Data Attribute XSS (Now.js specific) ─────────
function scanNowJsDataAttributes() {
  log('Scanning Now.js Data Attribute vulnerabilities...');
  const files = walkFiles(TARGET, ['.html', '.htm', '.js']);

  const patterns = [
    { regex: /data-html\s*=\s*["'][^"']*\{/,           severity: 'HIGH',   message: 'data-html with template expression — sanitize output' },
    { regex: /data-content\s*=\s*["'][^"']*<script/i,  severity: 'CRITICAL', message: 'Script tag in data-content attribute' },
    { regex: /data-action\s*=\s*["']javascript:/i,      severity: 'CRITICAL', message: 'javascript: URI in data-action — XSS' },
    { regex: /data-url\s*=\s*["']javascript:/i,         severity: 'CRITICAL', message: 'javascript: URI in data-url — XSS' },
    { regex: /data-render\s*=/,                          severity: 'MEDIUM',  message: 'data-render detected — verify HTML sanitization' },
    { regex: /data-template\s*=/,                        severity: 'MEDIUM',  message: 'data-template — validate template injection risk' },
    { regex: /data-bind\s*=\s*["'][^"']*\+/,            severity: 'MEDIUM',  message: 'data-bind with concatenation — verify escaping' },
  ];

  files.forEach(f => scanFileForPatterns(f, patterns));
}

// ─── SECTION 3: Prototype Pollution ───────────────────────────
function scanPrototypePollution() {
  log('Scanning Prototype Pollution...');
  const files = walkFiles(TARGET, ['.js', '.mjs', '.ts']);

  const patterns = [
    { regex: /\[['"`]__proto__['"`]\]/,                 severity: 'CRITICAL', message: '__proto__ assignment — prototype pollution' },
    { regex: /\[['"`]constructor['"`]\]\s*\[/,          severity: 'HIGH',     message: 'constructor.prototype access — pollution risk' },
    { regex: /Object\.assign\s*\(\s*[a-zA-Z]+,\s*req/,  severity: 'HIGH',     message: 'Object.assign with user-controlled source' },
    { regex: /\.merge\s*\([^)]*req\./,                  severity: 'HIGH',     message: 'Deep merge with user input — pollution risk' },
    { regex: /for\s*\(\s*\w+\s+in\s+\w+\s*\)\s*\{/,    severity: 'MEDIUM',   message: 'for...in loop — check hasOwnProperty guard' },
  ];

  files.forEach(f => scanFileForPatterns(f, patterns));
}

// ─── SECTION 4: Sensitive Data ────────────────────────────────
function scanSensitiveData() {
  log('Scanning Sensitive Data Exposure...');
  const files = walkFiles(TARGET, ['.js', '.ts', '.mjs', '.html', '.json', '.env']);

  const patterns = [
    { regex: /(?:api[_-]?key|apikey)\s*[:=]\s*['"`][a-zA-Z0-9\-_]{16,}/i,  severity: 'CRITICAL', message: 'Hardcoded API key' },
    { regex: /(?:secret|private[_-]?key)\s*[:=]\s*['"`][^'"`\s]{8,}/i,      severity: 'CRITICAL', message: 'Hardcoded secret/private key' },
    { regex: /(?:password|passwd)\s*[:=]\s*['"`][^'"`\s]{4,}/i,             severity: 'HIGH',     message: 'Hardcoded password' },
    { regex: /(?:bearer|token)\s*[:=]\s*['"`][a-zA-Z0-9\-_\.]{20,}/i,       severity: 'HIGH',     message: 'Hardcoded token' },
    { regex: /localStorage\.setItem\s*\(.*(?:token|secret|password)/i,       severity: 'HIGH',     message: 'Sensitive data in localStorage' },
    { regex: /sessionStorage\.setItem\s*\(.*(?:token|secret|password)/i,     severity: 'MEDIUM',   message: 'Sensitive data in sessionStorage' },
    { regex: /console\.log\s*\(.*(?:password|secret|token|key)/i,            severity: 'MEDIUM',   message: 'Sensitive data in console.log' },
  ];

  files.forEach(f => scanFileForPatterns(f, patterns));
}

// ─── SECTION 5: CORS & Fetch Security ────────────────────────
function scanCORSAndFetch() {
  log('Scanning CORS & Fetch security...');
  const files = walkFiles(TARGET, ['.js', '.ts', '.mjs']);

  const patterns = [
    { regex: /Access-Control-Allow-Origin.*\*/,                        severity: 'HIGH',   message: 'CORS wildcard (*) — restrict in production' },
    { regex: /credentials\s*:\s*['"`]include['"`]/,                    severity: 'MEDIUM', message: 'fetch credentials:include — verify CORS policy' },
    { regex: /fetch\s*\(\s*[a-zA-Z_$][a-zA-Z0-9_$]*\s*[,)]/,          severity: 'MEDIUM', message: 'fetch with variable URL — verify sanitization' },
    { regex: /XMLHttpRequest.*open\([^,]+,\s*[a-zA-Z_$][a-zA-Z0-9_$]/, severity: 'MEDIUM', message: 'XHR with variable URL — verify input' },
    { regex: /mode\s*:\s*['"`]no-cors['"`]/,                           severity: 'LOW',    message: 'no-cors mode hides errors — reconsider' },
  ];

  files.forEach(f => scanFileForPatterns(f, patterns));
}

// ─── SECTION 6: JWT & Auth ────────────────────────────────────
function scanJWTAuth() {
  log('Scanning JWT & Auth...');
  const files = walkFiles(TARGET, ['.js', '.ts', '.mjs']);

  const patterns = [
    { regex: /atob\s*\(\s*.*split\s*\(\s*['"]\.['"]\s*\)\s*\[1\]\s*\)/, severity: 'HIGH',   message: 'Manual JWT decode without verification — use library' },
    { regex: /algorithm\s*:\s*['"`]none['"`]/i,                          severity: 'CRITICAL', message: 'JWT algorithm:none — authentication bypass' },
    { regex: /jwt\.verify.*ignoreExpiration\s*:\s*true/,                 severity: 'HIGH',   message: 'JWT ignoreExpiration:true — tokens never expire' },
    { regex: /localStorage.*(?:jwt|token|auth)/i,                        severity: 'MEDIUM', message: 'JWT in localStorage — use httpOnly cookie instead' },
    { regex: /verify\s*=\s*false|ssl_verify\s*=\s*false/i,               severity: 'HIGH',   message: 'SSL/TLS verification disabled' },
  ];

  files.forEach(f => scanFileForPatterns(f, patterns));
}

// ─── SECTION 7: package.json ──────────────────────────────────
function scanPackageJson() {
  log('Scanning package.json dependencies...');

  const pkgPaths = [];
  function findPkg(dir) {
    if (!fs.existsSync(dir)) return;
    const entries = fs.readdirSync(dir, { withFileTypes: true });
    for (const e of entries) {
      if (e.isDirectory() && !['node_modules', '.git'].includes(e.name)) {
        findPkg(path.join(dir, e.name));
      } else if (e.name === 'package.json' && !dir.includes('node_modules')) {
        pkgPaths.push(path.join(dir, e.name));
      }
    }
  }
  findPkg(TARGET);

  if (pkgPaths.length === 0) {
    found('INFO', 'No package.json found', '', '', '');
    return;
  }

  for (const pkgPath of pkgPaths) {
    try {
      const pkg = JSON.parse(fs.readFileSync(pkgPath, 'utf8'));
      const allDeps = { ...pkg.dependencies, ...pkg.devDependencies };
      found('INFO', `Found package.json — run: npm audit`, pkgPath, '', '');

      const knownVulnerable = {
        'lodash': '<4.17.21',
        'minimist': '<1.2.6',
        'node-fetch': '<2.6.7',
        'axios': '<0.21.2',
        'marked': '<4.0.0',
        'jsonwebtoken': '<9.0.0',
        'qs': '<6.10.3',
        'express': '<4.19.0',
      };

      for (const [dep, safeVersion] of Object.entries(knownVulnerable)) {
        if (allDeps[dep]) {
          found('MEDIUM', `Dependency '${dep}' — verify version is ${safeVersion}`, pkgPath, '', `Current: ${allDeps[dep]}`);
        }
      }
    } catch (e) {
      found('LOW', `Could not parse package.json`, pkgPath, '', e.message);
    }
  }
}

// ─── SECTION 8: Now.js State/Component patterns ──────────────
function scanNowJsPatterns() {
  log('Scanning Now.js-specific patterns...');
  const files = walkFiles(TARGET, ['.js', '.mjs']);

  const patterns = [
    { regex: /Now\.setState\s*\(\s*JSON\.parse/,        severity: 'HIGH',   message: 'setState with JSON.parse — validate input' },
    { regex: /Now\.component\s*\([^)]+,\s*[a-zA-Z]/,   severity: 'INFO',   message: 'Component registration — verify template escaping' },
    { regex: /data\s*\+\s*=\s*.*innerHTML/,             severity: 'HIGH',   message: 'Appending innerHTML with data concat — XSS risk' },
    { regex: /Now\.router\s*\([^)]*req\./,              severity: 'MEDIUM', message: 'Router with request param — verify path validation' },
    { regex: /fetch.*then.*innerHTML\s*=/,              severity: 'HIGH',   message: 'Fetch response → innerHTML — sanitize first' },
    { regex: /getState\s*\(\s*\)\s*\.\s*[a-zA-Z]+\s*\+\s*/, severity: 'MEDIUM', message: 'State concatenation — verify XSS escaping' },
  ];

  files.forEach(f => scanFileForPatterns(f, patterns));
}

// ─── Generate Reports ─────────────────────────────────────────
function generateReports() {
  const report = {
    tool: 'Now.js JavaScript Security Scanner',
    target: TARGET,
    timestamp: new Date().toISOString(),
    summary: counts,
    total: Object.values(counts).reduce((a, b) => a + b, 0),
    findings
  };

  fs.writeFileSync(REPORT_JSON, JSON.stringify(report, null, 2));

  const sevIcon = { CRITICAL: '🔴', HIGH: '🟠', MEDIUM: '🟡', LOW: '🟢', INFO: 'ℹ️' };
  let md = `# Now.js JavaScript Security Report\n`;
  md += `**Date:** ${new Date().toString()}\n`;
  md += `**Target:** ${TARGET}\n\n`;
  md += `## Summary\n| Severity | Count |\n|----------|-------|\n`;
  for (const [sev, icon] of Object.entries(sevIcon)) {
    md += `| ${icon} ${sev} | ${counts[sev] || 0} |\n`;
  }
  md += `\n## Findings\n\n`;
  for (const f of findings) {
    md += `### ${sevIcon[f.severity] || 'ℹ️'} [${f.severity}] ${f.message}\n`;
    if (f.file) md += `**File:** \`${f.file}\`${f.line ? ` (line ${f.line})` : ''}\n`;
    if (f.code) md += `\`\`\`\n${f.code}\n\`\`\`\n`;
    md += '\n';
  }

  fs.writeFileSync(REPORT_MD, md);
}

// ─── MAIN ─────────────────────────────────────────────────────
function main() {
  console.log(`\n${colors.bold}${colors.cyan}╔══════════════════════════════════════════════╗${colors.reset}`);
  console.log(`${colors.bold}${colors.cyan}║   Now.js JavaScript Security Scanner         ║${colors.reset}`);
  console.log(`${colors.bold}${colors.cyan}╚══════════════════════════════════════════════╝${colors.reset}\n`);
  console.log(`Target: ${colors.bold}${TARGET}${colors.reset}`);
  console.log(`Time:   ${new Date().toString()}\n`);

  if (!fs.existsSync(TARGET)) {
    console.error(`${colors.red}Error: Target '${TARGET}' not found${colors.reset}`);
    process.exit(1);
  }

  scanXSS();
  scanNowJsDataAttributes();
  scanPrototypePollution();
  scanSensitiveData();
  scanCORSAndFetch();
  scanJWTAuth();
  scanPackageJson();
  scanNowJsPatterns();

  console.log(`\n${colors.bold}${colors.cyan}═══════════════ SCAN SUMMARY ═══════════════${colors.reset}`);
  console.log(`${colors.red}  Critical : ${counts.CRITICAL || 0}${colors.reset}`);
  console.log(`${colors.orange}  High     : ${counts.HIGH || 0}${colors.reset}`);
  console.log(`${colors.yellow}  Medium   : ${counts.MEDIUM || 0}${colors.reset}`);
  console.log(`${colors.green}  Low      : ${counts.LOW || 0}${colors.reset}`);
  console.log(`${colors.cyan}  Info     : ${counts.INFO || 0}${colors.reset}`);
  console.log(`${colors.bold}${colors.cyan}════════════════════════════════════════════${colors.reset}\n`);

  generateReports();

  console.log(`Reports saved:`);
  console.log(`  JSON : ${REPORT_JSON}`);
  console.log(`  MD   : ${REPORT_MD}`);
}

main();
