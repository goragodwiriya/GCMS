#!/usr/bin/env bash
# ============================================================
# Master Security Scanner — Kotchasan + Now.js
# ใช้งาน: ./run_scan.sh <php-path> <js-path>
# ============================================================

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PHP_TARGET="${1:-./Kotchasan}"
JS_TARGET="${2:-./Now}"
REPORT_DIR="$SCRIPT_DIR/../reports"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
COMBINED_MD="$REPORT_DIR/full_security_report_$TIMESTAMP.md"
OPUS_REPORT="$REPORT_DIR/claude_analysis_$TIMESTAMP.md"

RED='\033[0;31m'; CYAN='\033[0;36m'; BOLD='\033[1m'; NC='\033[0m'; GREEN='\033[0;32m'

mkdir -p "$REPORT_DIR"

echo -e "${BOLD}${CYAN}"
cat << 'BANNER'
 ╔═══════════════════════════════════════════════════════╗
 ║       KOTCHASAN + NOW.JS SECURITY SCANNER             ║
 ║       Powered by Claude Opus 4.8 Analysis             ║
 ╚═══════════════════════════════════════════════════════╝
BANNER
echo -e "${NC}"

# ─── Step 1: PHP Scan ─────────────────────────────────────────
echo -e "${BOLD}${CYAN}▶ Step 1/3: Scanning Kotchasan PHP...${NC}"
if [ -d "$PHP_TARGET" ]; then
  bash "$SCRIPT_DIR/scan_php.sh" "$PHP_TARGET"
  PHP_JSON=$(ls -t "$REPORT_DIR"/kotchasan_php_*.json 2>/dev/null | head -1)
  echo -e "${GREEN}✓ PHP scan complete${NC}\n"
else
  echo -e "${RED}⚠ PHP target '$PHP_TARGET' not found — skipping${NC}\n"
  PHP_JSON=""
fi

# ─── Step 2: JS Scan ──────────────────────────────────────────
echo -e "${BOLD}${CYAN}▶ Step 2/3: Scanning Now.js...${NC}"
if [ -d "$JS_TARGET" ]; then
  node "$SCRIPT_DIR/scan_js.js" "$JS_TARGET"
  JS_JSON=$(ls -t "$REPORT_DIR"/nowjs_js_*.json 2>/dev/null | head -1)
  echo -e "${GREEN}✓ JS scan complete${NC}\n"
else
  echo -e "${RED}⚠ JS target '$JS_TARGET' not found — skipping${NC}\n"
  JS_JSON=""
fi

# ─── Step 3: Merge & Generate Combined Report ─────────────────
echo -e "${BOLD}${CYAN}▶ Step 3/3: Generating combined report...${NC}"

cat > "$COMBINED_MD" << HEADER
# Full Security Report — Kotchasan + Now.js
**Generated:** $(date)

---

HEADER

if [ -f "$REPORT_DIR/$(ls -t "$REPORT_DIR"/kotchasan_php_*.md 2>/dev/null | head -1 | xargs basename 2>/dev/null)" ]; then
  PHP_MD=$(ls -t "$REPORT_DIR"/kotchasan_php_*.md 2>/dev/null | head -1)
  cat "$PHP_MD" >> "$COMBINED_MD" 2>/dev/null
fi

echo -e "\n---\n" >> "$COMBINED_MD"

if [ -f "$REPORT_DIR/$(ls -t "$REPORT_DIR"/nowjs_js_*.md 2>/dev/null | head -1 | xargs basename 2>/dev/null)" ]; then
  JS_MD=$(ls -t "$REPORT_DIR"/nowjs_js_*.md 2>/dev/null | head -1)
  cat "$JS_MD" >> "$COMBINED_MD" 2>/dev/null
fi

# ─── Step 4: Claude Opus 4.8 AI Analysis (optional) ──────────
echo -e "\n${BOLD}${CYAN}▶ Optional: AI Analysis with Claude Opus 4.8${NC}"
echo -e "To send these findings to Claude Opus 4.8 for analysis, run:"
echo -e ""
echo -e "  ${BOLD}node $SCRIPT_DIR/claude_analyze.js $COMBINED_MD${NC}"
echo -e ""

# ─── Summary ──────────────────────────────────────────────────
echo -e "${BOLD}${GREEN}══════════════ SCAN COMPLETE ══════════════${NC}"
echo -e "Combined Report: $COMBINED_MD"
[ -n "$PHP_JSON" ] && echo -e "PHP JSON Data:   $PHP_JSON"
[ -n "$JS_JSON"  ] && echo -e "JS JSON Data:    $JS_JSON"
echo -e "${BOLD}${GREEN}═══════════════════════════════════════════${NC}\n"
