#!/usr/bin/env node
// ============================================================
// Claude Opus 4.8 Security Analyzer
// อ่านผลสแกนและวิเคราะห์ด้วย Claude Opus 4.8
// ใช้งาน: node claude_analyze.js <report.md>
// ============================================================

const fs = require('fs');
const path = require('path');
const https = require('https');

const REPORT_FILE = process.argv[2];
const REPORT_DIR = path.join(__dirname, '..', 'reports');
const TIMESTAMP = new Date().toISOString().replace(/[:.]/g, '-').slice(0, 19);
const OUTPUT_FILE = path.join(REPORT_DIR, `claude_analysis_${TIMESTAMP}.md`);

const colors = {
  reset: '\x1b[0m', bold: '\x1b[1m',
  red: '\x1b[31m', cyan: '\x1b[36m',
  green: '\x1b[32m', yellow: '\x1b[33m'
};

if (!REPORT_FILE || !fs.existsSync(REPORT_FILE)) {
  console.error(`${colors.red}Usage: node claude_analyze.js <report.md>${colors.reset}`);
  console.error(`${colors.red}File not found: ${REPORT_FILE}${colors.reset}`);
  process.exit(1);
}

console.log(`\n${colors.bold}${colors.cyan}╔══════════════════════════════════════════════╗${colors.reset}`);
console.log(`${colors.bold}${colors.cyan}║   Claude Opus 4.8 — AI Security Analysis     ║${colors.reset}`);
console.log(`${colors.bold}${colors.cyan}╚══════════════════════════════════════════════╝${colors.reset}\n`);

// ─── Read scan report ─────────────────────────────────────────
const reportContent = fs.readFileSync(REPORT_FILE, 'utf8');
const reportLines = reportContent.split('\n').length;
console.log(`${colors.cyan}[INFO]${colors.reset} Report: ${REPORT_FILE} (${reportLines} lines)`);

// ─── Truncate if too large ────────────────────────────────────
const MAX_CHARS = 80000;
const truncated = reportContent.length > MAX_CHARS;
const inputContent = truncated
  ? reportContent.slice(0, MAX_CHARS) + '\n\n[... report truncated for API limits ...]'
  : reportContent;

if (truncated) {
  console.log(`${colors.yellow}[WARN]${colors.reset} Report truncated to ${MAX_CHARS} chars for API`);
}

// ─── Build prompt ─────────────────────────────────────────────
const systemPrompt = `คุณคือ Security Engineer ผู้เชี่ยวชาญด้าน PHP และ JavaScript Security
โดยเฉพาะ Kotchasan PHP Framework (MVC, PSR standards) และ Now.js (Data Attributes framework)
ทั้งสองพัฒนาโดย goragodwiriya

งานของคุณคือวิเคราะห์ผลการ Security Scan และให้คำแนะนำที่:
1. เฉพาะเจาะจงกับ Kotchasan และ Now.js patterns
2. มี code fix ที่ใช้งานได้จริง
3. จัดลำดับความสำคัญในการแก้ไข
4. อธิบายเป็นภาษาไทยได้

ตอบกลับเป็น Markdown`;

const userPrompt = `นี่คือผลการ Security Scan ของ Kotchasan PHP Framework และ Now.js Framework:

---
${inputContent}
---

กรุณาวิเคราะห์และให้:

## 1. Executive Summary
สรุปภาพรวมความเสี่ยง และ risk score (0-10)

## 2. Critical Issues — แก้ทันที
แต่ละ issue ให้มี:
- คำอธิบายว่าทำไมถึงอันตราย
- โค้ดที่มีช่องโหว่ (ตัวอย่าง)
- โค้ดที่แก้แล้ว

## 3. Kotchasan-specific Recommendations
คำแนะนำเฉพาะสำหรับ pattern ของ Kotchasan เช่น Recordset, Input class, HMVC

## 4. Now.js-specific Recommendations  
คำแนะนำเฉพาะสำหรับ Data Attributes patterns, State management, Component system

## 5. Cross-framework Issues
จุดที่ PHP และ JS ทำงานร่วมกัน เช่น API endpoints, AJAX calls

## 6. Remediation Roadmap
แผนการแก้ไขแบบ step-by-step จัดลำดับตาม severity

## 7. Security Checklist
Checklist สำหรับ review ก่อน deploy`;

// ─── API key (env only — never hardcode credentials) ─────────
const API_KEY = process.env.ANTHROPIC_API_KEY;

// ─── Call Claude API ──────────────────────────────────────────
async function callClaudeAPI() {
  if (!API_KEY) {
    throw new Error('ANTHROPIC_API_KEY not set in environment');
  }

  console.log(`${colors.cyan}[API]${colors.reset} Calling Claude Opus 4.8...`);

  const payload = JSON.stringify({
    model: 'claude-opus-4-8',
    max_tokens: 8192,
    system: systemPrompt,
    messages: [{role: 'user', content: userPrompt}]
  });

  return new Promise((resolve, reject) => {
    const req = https.request({
      hostname: 'api.anthropic.com',
      path: '/v1/messages',
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'anthropic-version': '2023-06-01',
        'x-api-key': API_KEY,
        'Content-Length': Buffer.byteLength(payload)
      }
    }, (res) => {
      let data = '';
      res.on('data', chunk => {
        data += chunk;
        process.stdout.write('.');
      });
      res.on('end', () => {
        console.log('\n');
        try {
          const parsed = JSON.parse(data);
          if (parsed.error) {
            reject(new Error(parsed.error.message || JSON.stringify(parsed.error)));
          } else if (parsed.content && parsed.content[0]) {
            resolve(parsed.content[0].text);
          } else {
            reject(new Error('Unexpected API response: ' + JSON.stringify(parsed).slice(0, 200)));
          }
        } catch (e) {
          reject(new Error('JSON parse error: ' + e.message));
        }
      });
    });

    req.on('error', reject);
    req.write(payload);
    req.end();
  });
}

// ─── Main ─────────────────────────────────────────────────────
async function main() {
  let analysis;
  try {
    analysis = await callClaudeAPI();
    console.log(`${colors.green}[OK]${colors.reset} Analysis complete`);
  } catch (err) {
    // API key missing — generate template instead
    console.log(`${colors.yellow}[WARN]${colors.reset} API call failed: ${err.message}`);
    console.log(`${colors.yellow}[INFO]${colors.reset} Generating template report...\n`);

    // Count findings from report
    const criticalCount = (reportContent.match(/\[CRITICAL\]/g) || []).length;
    const highCount = (reportContent.match(/\[HIGH\]/g) || []).length;
    const mediumCount = (reportContent.match(/\[MEDIUM\]/g) || []).length;

    analysis = `# Claude Opus 4.8 — Security Analysis Template

> **หมายเหตุ:** ไม่พบ ANTHROPIC_API_KEY รัน script นี้หลังจากตั้งค่า key แล้ว
> \`export ANTHROPIC_API_KEY="your-key-here"\`
> จากนั้นรัน: \`node claude_analyze.js ${REPORT_FILE}\`

---

## 1. Executive Summary

จากผลการสแกน พบ:
- 🔴 Critical: **${criticalCount} issues**
- 🟠 High: **${highCount} issues**
- 🟡 Medium: **${mediumCount} issues**

**Risk Score: รอการวิเคราะห์โดย Claude Opus 4.8**

---

## 2. วิธีรัน AI Analysis

\`\`\`bash
# 1. ตั้งค่า API Key (อย่า commit key ลง repo เด็ดขาด)
export ANTHROPIC_API_KEY="sk-ant-..."

# 2. รันอีกครั้ง
node scripts/claude_analyze.js ${REPORT_FILE}
\`\`\`

---

## 3. Manual Prompt สำหรับ Claude.ai

นำ report นี้ไปวางใน Claude.ai พร้อม prompt:

\`\`\`
วิเคราะห์ผล Security Scan นี้สำหรับ Kotchasan PHP Framework และ Now.js Framework
ให้ code fix สำหรับ Critical issues และ remediation roadmap
\`\`\`

---

*รายงานฉบับนี้สร้างเมื่อ: ${new Date().toString()}*
`;
  }

  // ─── Save output ─────────────────────────────────────────
  const fullOutput = `# Claude Opus 4.8 Security Analysis
**Generated:** ${new Date().toString()}
**Source:** ${REPORT_FILE}

---

${analysis}

---
*Analysis by Claude Opus 4.8 (claude-opus-4-8)*
`;

  fs.writeFileSync(OUTPUT_FILE, fullOutput);

  console.log(`${colors.bold}${colors.green}══════════════ ANALYSIS COMPLETE ══════════════${colors.reset}`);
  console.log(`Output: ${OUTPUT_FILE}`);

  // Preview first 20 lines
  console.log(`\n${colors.cyan}Preview:${colors.reset}`);
  fullOutput.split('\n').slice(0, 20).forEach(line => console.log(line));
  console.log('...\n');
}

main().catch(e => {
  console.error(`${colors.red}Fatal error: ${e.message}${colors.reset}`);
  process.exit(1);
});
