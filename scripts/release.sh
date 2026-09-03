#!/usr/bin/env bash
#
# Racster release script - run ON THE SERVER from anywhere.
#
# Usage:
#   bash scripts/release.sh
#
# Environment overrides (optional):
#   BRANCH=master        branch to release (default: master)
#   SKIP_MAINTENANCE=1   do not toggle maintenance mode
#   SKIP_BUILD=1         skip npm ci + npm run build
#
# Guarantees:
#   - Dependency installs never mutate lock files:
#       composer install (uses composer.lock as-is)
#       npm ci           (uses package-lock.json as-is)
#   - Only fast-forward pulls: if the server copy diverged from origin
#     the script stops instead of creating a merge state.
#
set -euo pipefail

BRANCH="${BRANCH:-master}"

# Always operate on the project root (parent of this script)
cd "$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

step() { printf '\n==> %s\n' "$*"; }

fail() { printf '\n[RELEASE FAILED] %s\n' "$*" >&2; exit 1; }

# --- Preflight ---------------------------------------------------------------

step "Preflight checks"

[ -f .env ] || fail ".env not found in $(pwd)"
[ -f composer.lock ] || fail "composer.lock not found"
[ -f package-lock.json ] || fail "package-lock.json not found"
git rev-parse --is-inside-work-tree >/dev/null 2>&1 || fail "$(pwd) is not a git repository"
command -v php >/dev/null 2>&1 || fail "php not found in PATH"
command -v composer >/dev/null 2>&1 || fail "composer not found in PATH"

echo "Releasing branch : $BRANCH"
echo "Project root     : $(pwd)"
echo "PHP              : $(php -r 'echo PHP_VERSION;')"
echo "Composer         : $(composer --version 2>/dev/null | head -1)"

# --- Maintenance mode --------------------------------------------------------

if [ "${SKIP_MAINTENANCE:-0}" != "1" ]; then
	step "Enabling maintenance mode"
	php artisan down --retry=15 || true
	trap 'php artisan up || true' EXIT
fi

# --- Pull latest code --------------------------------------------------------

step "Updating code from origin/$BRANCH"
git fetch origin "$BRANCH"
git checkout "$BRANCH" 2>/dev/null || git checkout -B "$BRANCH" "origin/$BRANCH"
# Fast-forward only: never merge, never rewrite server-side changes
git pull --ff-only origin "$BRANCH"
echo "Released commit  : $(git log -1 --format='%h %s')"

# --- PHP dependencies --------------------------------------------------------

step "Installing PHP dependencies (composer install, lock file untouched)"
composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader

# --- Web assets --------------------------------------------------------------

if [ "${SKIP_BUILD:-0}" != "1" ]; then
	step "Building web assets (npm ci, lock file untouched)"

	command -v npm >/dev/null 2>&1 || fail "npm not found in PATH (or set SKIP_BUILD=1)"

	# npm ci always removes node_modules and installs exactly what
	# package-lock.json pins - it never writes to package-lock.json
	npm ci --no-audit --no-fund
	npm run build
else
	step "Skipping web asset build (SKIP_BUILD=1)"
fi

# --- Database ----------------------------------------------------------------

step "Running migrations"
php artisan migrate --force

# --- Public storage link -----------------------------------------------------

step "Ensuring public/storage link"
[ -e public/storage ] || php artisan storage:link

# --- Framework caches --------------------------------------------------------

# NOTE: deliberately NOT running config:cache / route:cache / artisan optimize.
# This app calls env() at runtime (Stripe keys in controllers) and defines
# closure routes, both of which break when config/routes are cached.
step "Refreshing framework caches"
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
php artisan view:cache

# --- Queue workers -----------------------------------------------------------

step "Signaling queue workers to restart"
php artisan queue:restart

# --- Done --------------------------------------------------------------------

step "Release complete: $(git log -1 --format='%h %s')"
