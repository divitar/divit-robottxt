#!/usr/bin/env bash
# =============================================================================
# Divit RobotTXT — Distribution package builder
#
# Produces a clean, WP.org-ready zip from the plugin/ directory.
# Output: dist/divit-robottxt-{version}.zip
#
# Usage:
#   bash bin/build.sh           # build current version
#   bash bin/build.sh --dry-run # list files that would be packaged
# =============================================================================

set -euo pipefail

# ── Colour helpers ─────────────────────────────────────────────────────────────

GREEN="\033[0;32m"
YELLOW="\033[1;33m"
RED="\033[0;31m"
CYAN="\033[0;36m"
BOLD="\033[1m"
RESET="\033[0m"

info()    { echo -e "${GREEN}[build]${RESET} $*"; }
step()    { echo -e "${CYAN}[build]${RESET} $*"; }
warning() { echo -e "${YELLOW}[build]${RESET} $*"; }
error()   { echo -e "${RED}[build]${RESET} $*" >&2; }

# ── Paths ─────────────────────────────────────────────────────────────────────

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(dirname "$SCRIPT_DIR")"
PLUGIN_DIR="$ROOT_DIR/plugin"
DIST_DIR="$ROOT_DIR/dist"
SLUG="divit-robottxt"
MAIN_FILE="$PLUGIN_DIR/$SLUG.php"

# ── Options ───────────────────────────────────────────────────────────────────

DRY_RUN=false
for arg in "$@"; do
  [[ "$arg" == "--dry-run" ]] && DRY_RUN=true
done

# ── Preflight checks ──────────────────────────────────────────────────────────

if [[ ! -f "$MAIN_FILE" ]]; then
  error "Main plugin file not found: $MAIN_FILE"
  exit 1
fi

if ! command -v zip &>/dev/null; then
  error "'zip' command not found. Install it with: sudo apt install zip"
  exit 1
fi

# ── Read version from plugin header ──────────────────────────────────────────

VERSION=$(grep -m1 '^ \* Version:' "$MAIN_FILE" | sed 's/.*Version:[[:space:]]*//' | tr -d '[:space:]')

if [[ -z "$VERSION" ]]; then
  error "Could not read Version from $MAIN_FILE"
  exit 1
fi

PACKAGE_NAME="$SLUG-$VERSION"
ZIP_FILE="$DIST_DIR/$PACKAGE_NAME.zip"

info "Plugin:  $SLUG"
info "Version: $VERSION"
info "Output:  $ZIP_FILE"
echo ""

# ── Files included in the package ─────────────────────────────────────────────
#
# Everything inside plugin/ is included EXCEPT:
#   - Hidden files/dirs (dot-files)
#   - .po source files (translators use these locally; WP.org uses .mo + .pot)
#
# NOTE: .mo and .pot ARE included so the plugin ships with working translations
#       and a template for new translators.

EXCLUDE_PATTERNS=(
  "*/.DS_Store"
  "*/.gitignore"
  "*/.gitkeep"
  "*.orig"
  "*.bak"
  "*.swp"
  "*.swo"
)

# ── Dry run: just list what would be packaged ─────────────────────────────────

if [[ "$DRY_RUN" == true ]]; then
  step "Dry run — files that would be packaged:"
  echo ""

  while IFS= read -r -d '' file; do
    rel="${file#$PLUGIN_DIR/}"
    echo "  $SLUG/$rel"
  done < <(find "$PLUGIN_DIR" -type f \
    ! -name ".DS_Store" \
    ! -name "*.orig" \
    ! -name "*.bak" \
    ! -name "*.swp" \
    ! -name "*.swo" \
    -print0 | sort -z)

  echo ""
  warning "Dry run complete. No files were written."
  exit 0
fi

# ── Build ─────────────────────────────────────────────────────────────────────

step "Creating dist directory…"
mkdir -p "$DIST_DIR"

# Remove previous build of the same version to avoid zip appending
if [[ -f "$ZIP_FILE" ]]; then
  warning "Removing existing $ZIP_FILE"
  rm -f "$ZIP_FILE"
fi

step "Building package in a temporary directory…"
TMP_DIR=$(mktemp -d)
trap 'rm -rf "$TMP_DIR"' EXIT

# Copy plugin/ into tmp/<slug>/ so the zip root is the plugin folder
TMP_PLUGIN="$TMP_DIR/$SLUG"
cp -r "$PLUGIN_DIR" "$TMP_PLUGIN"

# Remove unwanted files from the temp copy
find "$TMP_PLUGIN" -name ".DS_Store" -delete
find "$TMP_PLUGIN" -name "*.orig"    -delete
find "$TMP_PLUGIN" -name "*.bak"     -delete
find "$TMP_PLUGIN" -name "*.swp"     -delete
find "$TMP_PLUGIN" -name "*.swo"     -delete

step "Creating zip archive…"
(
  cd "$TMP_DIR"
  zip -r "$ZIP_FILE" "$SLUG" -x "*.DS_Store"
)

# ── Summary ───────────────────────────────────────────────────────────────────

ZIP_SIZE=$(du -sh "$ZIP_FILE" | cut -f1)
FILE_COUNT=$(unzip -l "$ZIP_FILE" | tail -1 | awk '{print $2}')

echo ""
echo -e "${BOLD}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${RESET}"
info " Build complete!"
info ""
info "  Package : $ZIP_FILE"
info "  Size    : $ZIP_SIZE"
info "  Files   : $FILE_COUNT"
echo -e "${BOLD}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${RESET}"
echo ""
step "Contents:"
unzip -l "$ZIP_FILE" | awk 'NR>3 && NF>1 {printf "  %s\n", $NF}' | head -50
