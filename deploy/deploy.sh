#!/usr/bin/env bash
#
# YallaSpare production deploy script.
#
# Runs the full deploy sequence atomically enough that you can't ship stale
# code the way a manual "git pull; npm run build" can: it pulls, rebuilds,
# migrates, refreshes caches, restarts the worker, and — critically — VERIFIES
# the build manifest contains the expected entries before leaving maintenance
# mode. Any failure aborts and brings the site back up.
#
# Usage (on the server, as the deploy user):
#     bash deploy/deploy.sh
#
# Options via environment variables (defaults shown):
#     APP_DIR=/var/www/yallaspare      # project root (auto-detected from script)
#     BRANCH=main                      # branch to deploy
#     PHP_BIN=php                      # php CLI binary
#     WORKER=yallaspare-worker         # supervisor program name ('' to skip)
#     SKIP_MAINTENANCE=0               # 1 = don't toggle maintenance mode
#     FORCE=0                          # 1 = deploy even with a dirty working tree
#
set -euo pipefail

# --- Configuration -----------------------------------------------------------
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_DIR="${APP_DIR:-$(cd "${SCRIPT_DIR}/.." && pwd)}"
BRANCH="${BRANCH:-main}"
PHP_BIN="${PHP_BIN:-php}"
WORKER="${WORKER:-yallaspare-worker}"
SKIP_MAINTENANCE="${SKIP_MAINTENANCE:-0}"
FORCE="${FORCE:-0}"

# Vite entries that MUST appear in the built manifest. If npm run build ran
# against stale code (the classic failure), one of these is missing and we
# abort instead of shipping a half-updated frontend.
REQUIRED_MANIFEST_ENTRIES=(
    "resources/js/app.js"
    "resources/js/storefront.js"
    "resources/css/app.css"
)

# --- Helpers -----------------------------------------------------------------
log()  { printf '\033[1;34m==>\033[0m %s\n' "$*"; }
ok()   { printf '\033[1;32m  ✓\033[0m %s\n' "$*"; }
warn() { printf '\033[1;33m  !\033[0m %s\n' "$*"; }
die()  { printf '\033[1;31m  ✗ %s\033[0m\n' "$*" >&2; exit 1; }

MAINTENANCE_ON=0
cleanup() {
    local status=$?
    if [ "${MAINTENANCE_ON}" = "1" ]; then
        warn "Bringing the site back up after failure…"
        "${PHP_BIN}" artisan up >/dev/null 2>&1 || true
    fi
    if [ "${status}" -ne 0 ]; then
        printf '\033[1;31m\nDeploy FAILED (exit %s). Site restored; no further changes applied.\033[0m\n' "${status}" >&2
        if [ -n "${PREVIOUS_COMMIT:-}" ]; then
            printf 'To roll back code:  git reset --hard %s && bash deploy/deploy.sh\n' "${PREVIOUS_COMMIT}" >&2
        fi
    fi
}
trap cleanup EXIT

# --- Preflight ---------------------------------------------------------------
cd "${APP_DIR}"
log "Deploying ${BRANCH} in ${APP_DIR}"

[ -f artisan ] || die "No artisan file here — is APP_DIR correct?"
command -v git >/dev/null || die "git not found"
command -v "${PHP_BIN}" >/dev/null || die "php binary '${PHP_BIN}' not found"
git rev-parse --is-inside-work-tree >/dev/null 2>&1 || die "Not a git repository"

CURRENT_BRANCH="$(git rev-parse --abbrev-ref HEAD)"
[ "${CURRENT_BRANCH}" = "${BRANCH}" ] || die "On branch '${CURRENT_BRANCH}', expected '${BRANCH}'. Checkout ${BRANCH} first."

if [ "${FORCE}" != "1" ] && [ -n "$(git status --porcelain)" ]; then
    git status --short
    die "Working tree is dirty — commit/stash first, or run with FORCE=1."
fi

PREVIOUS_COMMIT="$(git rev-parse HEAD)"
ok "Preflight passed (current commit ${PREVIOUS_COMMIT:0:8})"

# --- Maintenance mode --------------------------------------------------------
if [ "${SKIP_MAINTENANCE}" != "1" ]; then
    log "Entering maintenance mode"
    "${PHP_BIN}" artisan down --retry=15 >/dev/null 2>&1 || true
    MAINTENANCE_ON=1
fi

# --- Pull code (fast-forward only, so a diverged history fails loudly) --------
log "Pulling latest code"
git fetch --prune origin "${BRANCH}"
git pull --ff-only origin "${BRANCH}"
NEW_COMMIT="$(git rev-parse HEAD)"
if [ "${NEW_COMMIT}" = "${PREVIOUS_COMMIT}" ]; then
    ok "Already up to date (${NEW_COMMIT:0:8}) — rebuilding anyway"
else
    ok "Updated ${PREVIOUS_COMMIT:0:8} → ${NEW_COMMIT:0:8}"
fi

# --- Dependencies (only when their lockfiles changed) ------------------------
changed() { ! git diff --quiet "${PREVIOUS_COMMIT}" "${NEW_COMMIT}" -- "$1" 2>/dev/null; }

if command -v composer >/dev/null; then
    if [ "${NEW_COMMIT}" = "${PREVIOUS_COMMIT}" ] || changed composer.lock || changed composer.json; then
        log "Installing PHP dependencies"
        composer install --no-dev --optimize-autoloader --no-interaction
        ok "composer install done"
    else
        ok "composer.lock unchanged — skipping"
    fi
else
    warn "composer not on PATH — skipping PHP dependency install"
fi

if command -v npm >/dev/null; then
    if [ ! -d node_modules ] || changed package-lock.json || changed package.json; then
        log "Installing JS dependencies"
        npm ci
        ok "npm ci done"
    else
        ok "package-lock.json unchanged — skipping npm ci"
    fi

    # Always rebuild: public/build is gitignored and cheap to regenerate.
    log "Building frontend assets"
    npm run build
    ok "npm run build done"
else
    die "npm not on PATH — cannot build frontend assets"
fi

# --- Verify the build actually contains what the blades will ask for ---------
log "Verifying build manifest"
MANIFEST="public/build/manifest.json"
[ -f "${MANIFEST}" ] || die "Build manifest missing at ${MANIFEST} — did npm run build fail?"
for entry in "${REQUIRED_MANIFEST_ENTRIES[@]}"; do
    grep -q "\"${entry}\"" "${MANIFEST}" || die "Manifest missing entry '${entry}' — build ran against stale code?"
done
ok "Manifest contains all ${#REQUIRED_MANIFEST_ENTRIES[@]} required entries"

# --- Database ----------------------------------------------------------------
log "Running migrations"
"${PHP_BIN}" artisan migrate --force
ok "Migrations up to date"

# --- Caches (rebuild so pulled blades/config/routes take effect) -------------
log "Refreshing caches"
"${PHP_BIN}" artisan storage:link >/dev/null 2>&1 || true
# Clear the application data cache right after migrations: App\Support\DbSchema
# caches positive table/column checks forever, so schema changes (and
# especially any manual `migrate:rollback`) require a cache:clear to be seen.
"${PHP_BIN}" artisan cache:clear
"${PHP_BIN}" artisan config:cache
"${PHP_BIN}" artisan route:cache
"${PHP_BIN}" artisan view:cache
"${PHP_BIN}" artisan event:cache >/dev/null 2>&1 || true
ok "config / route / view caches rebuilt"

# --- Restart the queue worker so it runs the new code ------------------------
if [ -n "${WORKER}" ] && command -v supervisorctl >/dev/null; then
    log "Restarting queue worker (${WORKER})"
    if sudo -n supervisorctl restart "${WORKER}" >/dev/null 2>&1; then
        ok "Worker restarted"
    elif supervisorctl restart "${WORKER}" >/dev/null 2>&1; then
        ok "Worker restarted"
    else
        # Fallback: signal Laravel to gracefully restart workers.
        "${PHP_BIN}" artisan queue:restart >/dev/null 2>&1 || true
        warn "supervisorctl unavailable — issued 'queue:restart' instead"
    fi
else
    "${PHP_BIN}" artisan queue:restart >/dev/null 2>&1 || true
    warn "No supervisor worker configured — issued 'queue:restart'"
fi

# --- Leave maintenance mode --------------------------------------------------
if [ "${MAINTENANCE_ON}" = "1" ]; then
    log "Leaving maintenance mode"
    "${PHP_BIN}" artisan up >/dev/null 2>&1
    MAINTENANCE_ON=0
fi

# --- Done --------------------------------------------------------------------
printf '\033[1;32m\nDeploy OK — now serving %s\033[0m\n' "${NEW_COMMIT:0:8}"
git --no-pager log --oneline -1
cat <<'EOF'

Verify:
  - https://yallaspare.com/user/home  →  Ctrl+U shows build/assets/storefront-*.js
  - https://yallaspare.com/build/manifest.json  →  lists resources/js/storefront.js
  - F12 → Console → no errors
EOF
