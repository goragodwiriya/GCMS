#!/usr/bin/env bash
# ============================================================
# Kotchasan PHP Security Scanner
# ใช้งาน: ./scan_php.sh <path-to-kotchasan>
# ============================================================

TARGET="${1:-.}"
REPORT_DIR="$(dirname "$0")/../reports"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
REPORT_FILE="$REPORT_DIR/kotchasan_php_$TIMESTAMP.json"
SUMMARY_FILE="$REPORT_DIR/kotchasan_php_$TIMESTAMP.md"

mkdir -p "$REPORT_DIR"

RED='\033[0;31m'
ORANGE='\033[0;33m'
YELLOW='\033[1;33m'
GREEN='\033[0;32m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m'

CRITICAL=0; HIGH=0; MEDIUM=0; LOW=0; INFO=0
declare -a FINDINGS=()

log() { echo -e "${CYAN}[SCAN]${NC} $1"; }
found() {
  local SEV="$1"; local MSG="$2"; local FILE="$3"; local LINE="$4"; local CODE="$5"
  case "$SEV" in
    CRITICAL) ((CRITICAL++)); COLOR=$RED ;;
    HIGH)     ((HIGH++));     COLOR=$ORANGE ;;
    MEDIUM)   ((MEDIUM++));   COLOR=$YELLOW ;;
    LOW)      ((LOW++));      COLOR=$GREEN ;;
    *)        ((INFO++));     COLOR=$CYAN ;;
  esac
  echo -e "${COLOR}[$SEV]${NC} $MSG"
  [ -n "$FILE" ] && echo -e "       ${BOLD}File:${NC} $FILE${LINE:+ (line $LINE)}"
  [ -n "$CODE" ] && echo -e "       ${BOLD}Code:${NC} ${CODE:0:120}"
  FINDINGS+=("{\"severity\":\"$SEV\",\"message\":\"$(echo $MSG | sed 's/"/\\"/g')\",\"file\":\"$(echo $FILE | sed 's/"/\\"/g')\",\"line\":\"$LINE\",\"code\":\"$(echo ${CODE:0:100} | sed 's/"/\\"/g')\"}")
}

print_header() {
  echo -e "\n${BOLD}${CYAN}╔══════════════════════════════════════════════╗${NC}"
  echo -e "${BOLD}${CYAN}║   Kotchasan PHP Security Scanner             ║${NC}"
  echo -e "${BOLD}${CYAN}╚══════════════════════════════════════════════╝${NC}\n"
  echo -e "Target: ${BOLD}$TARGET${NC}"
  echo -e "Time:   $(date)\n"
}

# ─── SECTION 1: SQL Injection ────────────────────────────────
scan_sql_injection() {
  log "Scanning SQL Injection..."

  # Raw query concatenation
  while IFS=: read -r FILE LINE CODE; do
    found "CRITICAL" "Raw SQL concatenation — SQL Injection risk" "$FILE" "$LINE" "$CODE"
  done < <(grep -rn --include="*.php" -E "(query|execute|db->)\s*\(\s*[\"'].*\\\$" "$TARGET" 2>/dev/null | head -20)

  # $_GET/$_POST ใน SQL โดยตรง
  while IFS=: read -r FILE LINE CODE; do
    found "CRITICAL" "User input directly in SQL query" "$FILE" "$LINE" "$CODE"
  done < <(grep -rn --include="*.php" -E "query.*(\\\$_GET|\\\$_POST|\\\$_REQUEST)" "$TARGET" 2>/dev/null | head -20)

  # ตรวจ Kotchasan Recordset pattern
  while IFS=: read -r FILE LINE CODE; do
    found "HIGH" "Possible unsafe Recordset usage" "$FILE" "$LINE" "$CODE"
  done < <(grep -rn --include="*.php" -E "->where\([\"'].*\\\$" "$TARGET" 2>/dev/null | head -20)
}

# ─── SECTION 2: XSS ──────────────────────────────────────────
scan_xss() {
  log "Scanning XSS vulnerabilities..."

  while IFS=: read -r FILE LINE CODE; do
    found "CRITICAL" "echo of user input without escaping" "$FILE" "$LINE" "$CODE"
  done < <(grep -rn --include="*.php" -E "echo\s+(\\\$_GET|\\\$_POST|\\\$_REQUEST|\\\$_COOKIE)" "$TARGET" 2>/dev/null | head -20)

  while IFS=: read -r FILE LINE CODE; do
    found "HIGH" "print without htmlspecialchars — potential XSS" "$FILE" "$LINE" "$CODE"
  done < <(grep -rn --include="*.php" -E "print\s+\\\$" "$TARGET" 2>/dev/null | grep -v "htmlspecialchars\|Html::text\|strip_tags" | head -20)

  # ตรวจ Kotchasan Html helper usage
  while IFS=: read -r FILE LINE CODE; do
    found "MEDIUM" "Direct variable echo in template — verify escaping" "$FILE" "$LINE" "$CODE"
  done < <(grep -rn --include="*.php" -E "<\?=\s*\\\$(?!o->|this->)" "$TARGET" 2>/dev/null | grep -v "htmlspecialchars\|Html::" | head -20)
}

# ─── SECTION 3: CSRF ─────────────────────────────────────────
scan_csrf() {
  log "Scanning CSRF protection..."

  # ฟอร์ม POST ที่ไม่มี token
  PHP_FORMS=$(grep -rln --include="*.php" -E "method=[\"']post[\"']" "$TARGET" 2>/dev/null)
  for FILE in $PHP_FORMS; do
    if ! grep -q -i "csrf\|_token\|token\|nonce\|Input::isSafe\|isReferer" "$FILE" 2>/dev/null; then
      found "HIGH" "Form with POST method missing CSRF protection" "$FILE" "" ""
    fi
  done

  # Ajax POST ไม่มี token
  while IFS=: read -r FILE LINE CODE; do
    found "MEDIUM" "AJAX POST without visible token header" "$FILE" "$LINE" "$CODE"
  done < <(grep -rn --include="*.php" -E "ajax.*POST|fetch.*POST|XMLHttpRequest" "$TARGET" 2>/dev/null | grep -iv "token\|csrf\|nonce" | head -10)
}

# ─── SECTION 4: File Upload ──────────────────────────────────
scan_file_upload() {
  log "Scanning File Upload security..."

  while IFS=: read -r FILE LINE CODE; do
    found "CRITICAL" "File upload — verify extension & MIME validation" "$FILE" "$LINE" "$CODE"
  done < <(grep -rn --include="*.php" -E "\\\$_FILES\[" "$TARGET" 2>/dev/null | head -20)

  # move_uploaded_file โดยไม่ตรวจสอบ
  while IFS=: read -r FILE LINE CODE; do
    found "HIGH" "move_uploaded_file — confirm destination path validation" "$FILE" "$LINE" "$CODE"
  done < <(grep -rn --include="*.php" "move_uploaded_file" "$TARGET" 2>/dev/null | head -10)
}

# ─── SECTION 5: Session & Auth ───────────────────────────────
scan_session_auth() {
  log "Scanning Session & Authentication..."

  while IFS=: read -r FILE LINE CODE; do
    found "HIGH" "session_regenerate_id not called after login" "$FILE" "$LINE" "$CODE"
  done < <(grep -rln --include="*.php" -E "session_start|session_regenerate_id" "$TARGET" 2>/dev/null | xargs grep -l "login\|authenticate\|signin" 2>/dev/null | while read f; do grep -L "session_regenerate_id" "$f" 2>/dev/null; done | head -10 | while read f; do echo "$f::session_regenerate_id missing"; done)

  # Hardcoded credentials
  while IFS=: read -r FILE LINE CODE; do
    found "CRITICAL" "Possible hardcoded credentials" "$FILE" "$LINE" "$CODE"
  done < <(grep -rn --include="*.php" -E "(password|passwd|secret|api_key)\s*=\s*[\"'][^\"']{4,}[\"']" "$TARGET" 2>/dev/null | grep -iv "placeholder\|example\|test\|dummy\|config" | head -10)

  # MD5/SHA1 for passwords
  while IFS=: read -r FILE LINE CODE; do
    found "HIGH" "Weak password hashing (md5/sha1) — use password_hash()" "$FILE" "$LINE" "$CODE"
  done < <(grep -rn --include="*.php" -E "(md5|sha1)\s*\(\s*\\\$pass" "$TARGET" 2>/dev/null | head -10)
}

# ─── SECTION 6: Path Traversal ───────────────────────────────
scan_path_traversal() {
  log "Scanning Path Traversal..."

  while IFS=: read -r FILE LINE CODE; do
    found "CRITICAL" "include/require with user input — Path Traversal risk" "$FILE" "$LINE" "$CODE"
  done < <(grep -rn --include="*.php" -E "(include|require)(_once)?\s*\(.*(\\\$_GET|\\\$_POST|\\\$_REQUEST)" "$TARGET" 2>/dev/null | head -10)

  while IFS=: read -r FILE LINE CODE; do
    found "HIGH" "File operation with user-controlled path" "$FILE" "$LINE" "$CODE"
  done < <(grep -rn --include="*.php" -E "(file_get_contents|fopen|readfile|file)\s*\(.*\\\$_(GET|POST|REQUEST)" "$TARGET" 2>/dev/null | head -10)
}

# ─── SECTION 7: Sensitive Info Exposure ──────────────────────
scan_sensitive_exposure() {
  log "Scanning Sensitive Information Exposure..."

  while IFS=: read -r FILE LINE CODE; do
    found "HIGH" "Error display enabled — may expose server info" "$FILE" "$LINE" "$CODE"
  done < <(grep -rn --include="*.php" -E "display_errors\s*=\s*(1|On|True|true)" "$TARGET" 2>/dev/null | head -10)

  while IFS=: read -r FILE LINE CODE; do
    found "MEDIUM" "var_dump/print_r left in code — info leakage risk" "$FILE" "$LINE" "$CODE"
  done < <(grep -rn --include="*.php" -E "(var_dump|print_r|var_export)\s*\(" "$TARGET" 2>/dev/null | head -15)

  # phpinfo
  while IFS=: read -r FILE LINE CODE; do
    found "HIGH" "phpinfo() found — remove in production" "$FILE" "$LINE" "$CODE"
  done < <(grep -rn --include="*.php" "phpinfo()" "$TARGET" 2>/dev/null | head -5)
}

# ─── SECTION 8: Composer Dependencies ───────────────────────
scan_composer() {
  log "Scanning Composer dependencies..."

  COMPOSER_JSON=$(find "$TARGET" -name "composer.json" -not -path "*/vendor/*" 2>/dev/null | head -5)
  if [ -n "$COMPOSER_JSON" ]; then
    found "INFO" "composer.json found — run: composer audit" "$COMPOSER_JSON" "" ""
    if command -v composer &>/dev/null; then
      AUDIT_OUT=$(cd "$(dirname $COMPOSER_JSON)" && composer audit --no-interaction 2>&1)
      if echo "$AUDIT_OUT" | grep -qi "vulnerability\|CVE"; then
        found "HIGH" "Composer audit found vulnerabilities" "$COMPOSER_JSON" "" "$AUDIT_OUT"
      else
        found "INFO" "Composer audit passed" "$COMPOSER_JSON" "" ""
      fi
    else
      found "LOW" "composer not installed — skip dependency audit" "" "" ""
    fi
  else
    found "INFO" "No composer.json found" "" "" ""
  fi
}

# ─── SECTION 9: Kotchasan-specific checks ────────────────────
scan_kotchasan_specific() {
  log "Scanning Kotchasan-specific patterns..."

  # ตรวจ Input::get() usage
  while IFS=: read -r FILE LINE CODE; do
    found "INFO" "Input::get() usage — verify downstream sanitization" "$FILE" "$LINE" "$CODE"
  done < <(grep -rn --include="*.php" -E "Input::(get|post|request)\(" "$TARGET" 2>/dev/null | head -10)

  # ตรวจ datas/ directory permissions
  DATAS_DIR="$TARGET/datas"
  if [ -d "$DATAS_DIR" ]; then
    PERM=$(stat -c "%a" "$DATAS_DIR" 2>/dev/null || stat -f "%OLp" "$DATAS_DIR" 2>/dev/null)
    if [ "$PERM" = "777" ]; then
      found "MEDIUM" "datas/ is 777 (world-writable) — consider 755 or 775" "$DATAS_DIR" "" ""
    fi
  fi

  # ตรวจ config file accessibility
  for CFG in "settings/database.php" "settings/settings.php" "config.php"; do
    CFG_PATH="$TARGET/$CFG"
    if [ -f "$CFG_PATH" ]; then
      if grep -q -E "(password|passwd)\s*=\s*[\"'][^\"']+[\"']" "$CFG_PATH" 2>/dev/null; then
        found "MEDIUM" "DB credentials in config — ensure web-inaccessible" "$CFG_PATH" "" ""
      fi
    fi
  done
}

# ─── Generate Reports ─────────────────────────────────────────
generate_report() {
  # JSON report
  echo "{" > "$REPORT_FILE"
  echo "  \"tool\": \"Kotchasan PHP Security Scanner\"," >> "$REPORT_FILE"
  echo "  \"target\": \"$TARGET\"," >> "$REPORT_FILE"
  echo "  \"timestamp\": \"$(date -Iseconds)\"," >> "$REPORT_FILE"
  echo "  \"summary\": {\"critical\":$CRITICAL,\"high\":$HIGH,\"medium\":$MEDIUM,\"low\":$LOW,\"info\":$INFO}," >> "$REPORT_FILE"
  echo "  \"findings\": [" >> "$REPORT_FILE"
  for i in "${!FINDINGS[@]}"; do
    if [ $i -lt $((${#FINDINGS[@]}-1)) ]; then
      echo "    ${FINDINGS[$i]}," >> "$REPORT_FILE"
    else
      echo "    ${FINDINGS[$i]}" >> "$REPORT_FILE"
    fi
  done
  echo "  ]" >> "$REPORT_FILE"
  echo "}" >> "$REPORT_FILE"

  # Markdown summary
  cat > "$SUMMARY_FILE" << MDEOF
# Kotchasan PHP Security Report
**Date:** $(date)
**Target:** $TARGET

## Summary
| Severity | Count |
|----------|-------|
| 🔴 Critical | $CRITICAL |
| 🟠 High | $HIGH |
| 🟡 Medium | $MEDIUM |
| 🟢 Low | $LOW |
| ℹ️ Info | $INFO |

## Findings

MDEOF

  for FINDING in "${FINDINGS[@]}"; do
    SEV=$(echo "$FINDING" | python3 -c "import sys,json; d=json.load(sys.stdin); print(d['severity'])" 2>/dev/null)
    MSG=$(echo "$FINDING" | python3 -c "import sys,json; d=json.load(sys.stdin); print(d['message'])" 2>/dev/null)
    FILE=$(echo "$FINDING" | python3 -c "import sys,json; d=json.load(sys.stdin); print(d['file'])" 2>/dev/null)
    case "$SEV" in
      CRITICAL) ICON="🔴" ;;
      HIGH) ICON="🟠" ;;
      MEDIUM) ICON="🟡" ;;
      LOW) ICON="🟢" ;;
      *) ICON="ℹ️" ;;
    esac
    echo "### $ICON [$SEV] $MSG" >> "$SUMMARY_FILE"
    [ -n "$FILE" ] && echo "**File:** \`$FILE\`" >> "$SUMMARY_FILE"
    echo "" >> "$SUMMARY_FILE"
  done
}

# ─── MAIN ─────────────────────────────────────────────────────
main() {
  print_header

  if [ ! -d "$TARGET" ]; then
    echo -e "${RED}Error: Target directory '$TARGET' not found${NC}"
    exit 1
  fi

  scan_sql_injection
  scan_xss
  scan_csrf
  scan_file_upload
  scan_session_auth
  scan_path_traversal
  scan_sensitive_exposure
  scan_composer
  scan_kotchasan_specific

  echo -e "\n${BOLD}${CYAN}═══════════════ SCAN SUMMARY ═══════════════${NC}"
  echo -e "${RED}  Critical : $CRITICAL${NC}"
  echo -e "${ORANGE}  High     : $HIGH${NC}"
  echo -e "${YELLOW}  Medium   : $MEDIUM${NC}"
  echo -e "${GREEN}  Low      : $LOW${NC}"
  echo -e "${CYAN}  Info     : $INFO${NC}"
  echo -e "${BOLD}${CYAN}════════════════════════════════════════════${NC}\n"

  generate_report

  echo -e "Reports saved:"
  echo -e "  JSON : $REPORT_FILE"
  echo -e "  MD   : $SUMMARY_FILE"
}

main
