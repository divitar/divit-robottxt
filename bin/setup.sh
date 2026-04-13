#!/usr/bin/env bash
# =============================================================================
# Divit RobotTXT — Local environment setup
#
# Runs once after "docker compose up -d" to install WordPress and activate
# the plugin. Safe to re-run: WP-CLI skips steps that are already done.
#
# Usage:
#   bash bin/setup.sh
# =============================================================================

set -euo pipefail

# ── Colour helpers ─────────────────────────────────────────────────────────────

GREEN="\033[0;32m"
YELLOW="\033[1;33m"
RED="\033[0;31m"
RESET="\033[0m"

info()    { echo -e "${GREEN}[setup]${RESET} $*"; }
warning() { echo -e "${YELLOW}[setup]${RESET} $*"; }
error()   { echo -e "${RED}[setup]${RESET} $*" >&2; }

# ── Load .env if present ───────────────────────────────────────────────────────

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(dirname "$SCRIPT_DIR")"

if [[ -f "$ROOT_DIR/.env" ]]; then
	# Export only lines that look like KEY=value (skip comments and blanks).
	set -a
	# shellcheck disable=SC1091
	source <(grep -E '^[A-Z_]+=.+' "$ROOT_DIR/.env")
	set +a
fi

WP_PORT="${WP_PORT:-8888}"
WP_URL="http://localhost:${WP_PORT}"
WP_TITLE="Divit RobotTXT – Dev"
WP_ADMIN_USER="${WP_ADMIN_USER:-admin}"
WP_ADMIN_PASSWORD="${WP_ADMIN_PASSWORD:-admin}"
WP_ADMIN_EMAIL="${WP_ADMIN_EMAIL:-admin@example.local}"

# Shorthand for running WP-CLI inside the container.
wpcli() {
	docker compose run --rm wpcli wp "$@"
}

# ── Wait for WordPress to become reachable ─────────────────────────────────────

info "Waiting for WordPress container to be ready…"
MAX_RETRIES=30
RETRY=0

until wpcli --info &>/dev/null; do
	RETRY=$((RETRY + 1))
	if [[ $RETRY -ge $MAX_RETRIES ]]; then
		error "WordPress did not become ready after ${MAX_RETRIES} attempts. Is 'docker compose up -d' running?"
		exit 1
	fi
	sleep 3
done

info "WordPress container is ready."

# ── Install WordPress (skipped if already installed) ──────────────────────────

if wpcli core is-installed 2>/dev/null; then
	warning "WordPress is already installed — skipping core install."
else
	info "Installing WordPress…"
	wpcli core install \
		--url="$WP_URL" \
		--title="$WP_TITLE" \
		--admin_user="$WP_ADMIN_USER" \
		--admin_password="$WP_ADMIN_PASSWORD" \
		--admin_email="$WP_ADMIN_EMAIL" \
		--skip-email
	info "WordPress installed."
fi

# ── Activate the plugin ────────────────────────────────────────────────────────

info "Activating Divit RobotTXT plugin…"
wpcli plugin activate divit-robottxt
info "Plugin activated."

# ── Set permalink structure (required for robots.txt hook to work) ─────────────

info "Setting permalink structure to /%postname%/…"
wpcli rewrite structure '/%postname%/' --hard
info "Permalinks updated."

# ── Optional: make site public ────────────────────────────────────────────────

info "Ensuring site is visible to search engines…"
wpcli option update blog_public 1
info "blog_public = 1."

# ── Done ───────────────────────────────────────────────────────────────────────

echo ""
info "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
info " Setup complete!"
info ""
info "  WordPress:   ${WP_URL}"
info "  Admin:       ${WP_URL}/wp-admin"
info "  User:        ${WP_ADMIN_USER}"
info "  Password:    ${WP_ADMIN_PASSWORD}"
info "  phpMyAdmin:  http://localhost:${PMA_PORT:-8081}"
info "  robots.txt:  ${WP_URL}/robots.txt"
info "  Plugin page: ${WP_URL}/wp-admin/options-general.php?page=divit-robottxt"
info "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
