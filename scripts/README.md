# 🔐 Kotchasan + Now.js Security Scanner

Automated security scan tool สำหรับ Kotchasan PHP Framework และ Now.js JavaScript Framework

## 📁 โครงสร้าง

```
security-scanner/
├── scripts/
│   ├── scan_php.sh          # PHP/Kotchasan scanner
│   ├── scan_js.js           # JS/Now.js scanner
│   ├── run_scan.sh          # Master runner (รันทั้งคู่พร้อมกัน)
│   └── claude_analyze.js    # ส่งผลให้ Claude Opus 4.8 วิเคราะห์
├── reports/                 # ผลการสแกน (auto-generated)
│   ├── kotchasan_php_*.json
│   ├── kotchasan_php_*.md
│   ├── nowjs_js_*.json
│   ├── nowjs_js_*.md
│   ├── full_security_report_*.md
│   └── claude_analysis_*.md
└── README.md
```

## 🚀 วิธีใช้งาน

### 1. รันทั้งหมดพร้อมกัน (แนะนำ)

```bash
chmod +x scripts/run_scan.sh scripts/scan_php.sh
bash scripts/run_scan.sh ./Kotchasan ./Now
```

### 2. รัน PHP scan อย่างเดียว

```bash
bash scripts/scan_php.sh ./Kotchasan
```

### 3. รัน JS scan อย่างเดียว

```bash
node scripts/scan_js.js ./Now
```

### 4. วิเคราะห์ด้วย Claude Opus 4.8

```bash
# ตั้งค่า API Key ก่อน
export ANTHROPIC_API_KEY="sk-ant-..."

# รัน AI analysis
node scripts/claude_analyze.js reports/full_security_report_*.md
```

## 🔍 สิ่งที่ตรวจสอบ

### PHP (Kotchasan)
| หมวด | รายละเอียด |
|------|-----------|
| 🔴 SQL Injection | Raw query concat, $_GET/$_POST ใน SQL, Recordset patterns |
| 🔴 XSS | echo ไม่ escape, print โดยตรง, template output |
| 🟠 CSRF | Form POST ไม่มี token, AJAX ไม่มี header |
| 🟠 File Upload | $_FILES validation, move_uploaded_file |
| 🟠 Auth/Session | session_regenerate_id, hardcoded credentials, weak hashing |
| 🟠 Path Traversal | include/require จาก user input |
| 🟡 Info Exposure | display_errors, var_dump, phpinfo() |
| 🟡 Kotchasan-specific | datas/ permissions, config file access, Input::get() |

### JavaScript (Now.js)
| หมวด | รายละเอียด |
|------|-----------|
| 🔴 XSS | innerHTML, eval(), document.write, dangerouslySetInnerHTML |
| 🔴 Data Attribute XSS | javascript: URI, script ใน data-content |
| 🔴 Prototype Pollution | __proto__, constructor.prototype, unsafe merge |
| 🔴 JWT | algorithm:none, ignoreExpiration |
| 🟠 Sensitive Data | API keys, passwords, tokens ใน code |
| 🟠 CORS/Fetch | wildcard CORS, credentials:include |
| 🟡 Now.js-specific | setState patterns, component templates, router |
| 🟡 Dependencies | package.json known vulnerable versions |

## 📊 ตัวอย่าง Output

```
[SCAN] Scanning SQL Injection...
[CRITICAL] Raw SQL concatenation — SQL Injection risk
       File: modules/index/models/index.php (line 45)
       Code: $db->query("SELECT * FROM users WHERE id=" . $_GET['id'])

[SCAN] Scanning XSS vulnerabilities...
[HIGH] echo of user input without escaping
       File: modules/admin/views/list.php (line 12)
       Code: echo $_GET['search'];

═══════════════ SCAN SUMMARY ═══════════════
  Critical : 3
  High     : 7
  Medium   : 12
  Low      : 2
  Info     : 5
════════════════════════════════════════════
```

## ⚙️ Requirements

- **PHP Scanner:** bash, grep, (optional: composer)
- **JS Scanner:** Node.js 14+
- **AI Analysis:** Node.js + `ANTHROPIC_API_KEY`

## 🔄 Integration กับ Claude Code

```bash
# ใน Claude Code terminal
claude --model claude-opus-4-8

# แล้วบอก Claude ว่า:
# "อ่านไฟล์ reports/full_security_report_*.md แล้วช่วย refactor โค้ดใน kotchasan/"
```
