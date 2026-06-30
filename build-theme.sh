#!/usr/bin/env bash
#
# build-theme.sh — package the EWO 2025 theme into a distributable ZIP.
#
# Strict build tracking is ENFORCED here:
#   * EWO_THEME_VERSION (functions.php) must match the Version: header in style.css.
#   * The build ID must be recorded in CHANGELOG.md.
#   * A version+build that has already been packaged will NOT be rebuilt — you must
#     bump EWO_THEME_BUILD (or EWO_THEME_VERSION) first.
#
# Usage:  ./build-theme.sh
#
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
THEME_DIR="$SCRIPT_DIR/ewo-2025"
FUNCTIONS="$THEME_DIR/functions.php"
STYLE="$THEME_DIR/style.css"
CHANGELOG="$THEME_DIR/CHANGELOG.md"

die() { printf 'ERROR: %s\n' "$1" >&2; exit 1; }

[ -f "$FUNCTIONS" ] || die "functions.php not found at $FUNCTIONS"
[ -f "$STYLE" ]     || die "style.css not found at $STYLE"
[ -f "$CHANGELOG" ] || die "CHANGELOG.md not found at $CHANGELOG"

# --- Extract the tracked version + build from functions.php ---------------------
extract_define() {
	# $1 = constant name -> echoes the single-quoted string value
	sed -nE "s/.*define\(\s*'$1'\s*,\s*'([^']+)'\s*\).*/\1/p" "$FUNCTIONS" | head -n1
}

VERSION="$(extract_define EWO_THEME_VERSION)"
BUILD="$(extract_define EWO_THEME_BUILD)"

[ -n "$VERSION" ] || die "Could not read EWO_THEME_VERSION from functions.php"
[ -n "$BUILD" ]   || die "Could not read EWO_THEME_BUILD from functions.php"

# --- Validate build ID format YYYYMMDD-NN ---------------------------------------
echo "$BUILD" | grep -qE '^[0-9]{8}-[0-9]{2}$' \
	|| die "EWO_THEME_BUILD ('$BUILD') must be of the form YYYYMMDD-NN (e.g. 20260624-01)."

# --- style.css Version header must match EWO_THEME_VERSION -----------------------
STYLE_VERSION="$(sed -nE 's/^[[:space:]]*Version:[[:space:]]*([^[:space:]]+).*/\1/p' "$STYLE" | head -n1)"
[ "$STYLE_VERSION" = "$VERSION" ] \
	|| die "style.css Version ('$STYLE_VERSION') != EWO_THEME_VERSION ('$VERSION'). Keep them in sync."

# --- CHANGELOG.md must mention this build ID ------------------------------------
grep -qF "$BUILD" "$CHANGELOG" \
	|| die "Build '$BUILD' is not recorded in CHANGELOG.md. Add an entry before building."

# --- Refuse to overwrite an already-packaged version+build ----------------------
ZIP_NAME="ewo-theme-v${VERSION}-build${BUILD}.zip"
ZIP_PATH="$SCRIPT_DIR/$ZIP_NAME"

if [ -e "$ZIP_PATH" ]; then
	die "$ZIP_NAME already exists. Bump EWO_THEME_BUILD (or EWO_THEME_VERSION) before rebuilding."
fi

# --- Build ----------------------------------------------------------------------
echo "Packaging EWO Theme v${VERSION} / Build ${BUILD}"
( cd "$SCRIPT_DIR" && zip -r -q "$ZIP_NAME" ewo-2025 \
	-x '*/.git/*' '*/.git' \
	-x '*/.claude/*' \
	-x '*/.DS_Store' \
	-x '*/node_modules/*' )

echo "Created $ZIP_PATH"
