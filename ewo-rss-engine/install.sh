#!/usr/bin/env bash
#
# EWO RSS Engine — build & deploy installer.
#
# What it does:
#   1. Reads the version from the plugin header.
#   2. Builds a clean, distributable zip ( ../ewo-rss-engine-v<version>.zip ).
#   3. Deploys it into the running Dockerized WordPress (wp-content/plugins).
#   4. Sets www-data ownership and activates the plugin.
#
# Usage:
#   ./install.sh              # build + deploy + activate
#   BUILD_ONLY=1 ./install.sh # build the zip only, no deploy
#   WP_CONTAINER=name ./install.sh   # override the target container
#
set -euo pipefail

SLUG="ewo-rss-engine"
MAIN_FILE="$SLUG.php"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DIST_DIR="$(dirname "$SCRIPT_DIR")"
CONTAINER="${WP_CONTAINER:-wordpress-test-wordpress-1}"
WP_PLUGINS="/var/www/html/wp-content/plugins"
WP_LOAD="/var/www/html/wp-load.php"

# --- Read version from the plugin header -----------------------------------
VERSION="$(grep -iE '^[[:space:]]*\*[[:space:]]*Version:' "$SCRIPT_DIR/$MAIN_FILE" \
	| head -n1 | sed -E 's/.*Version:[[:space:]]*//I' | tr -d '\r' | xargs)"
if [ -z "$VERSION" ]; then
	echo "ERROR: could not read Version from $MAIN_FILE" >&2
	exit 1
fi

ZIP_NAME="$SLUG-v$VERSION.zip"
ZIP_PATH="$DIST_DIR/$ZIP_NAME"

# --- Build -----------------------------------------------------------------
echo "==> Building $ZIP_NAME (version $VERSION)"
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT
mkdir -p "$TMP/$SLUG"

rsync -a \
	--exclude '.git' \
	--exclude '.gitignore' \
	--exclude '.claude' \
	--exclude '.DS_Store' \
	--exclude 'node_modules' \
	--exclude 'install.sh' \
	--exclude '*.zip' \
	"$SCRIPT_DIR/" "$TMP/$SLUG/"

rm -f "$ZIP_PATH"
( cd "$TMP" && zip -rq "$ZIP_PATH" "$SLUG" )
echo "    built: $ZIP_PATH"

if [ "${BUILD_ONLY:-0}" = "1" ]; then
	echo "==> BUILD_ONLY set; deploy skipped."
	exit 0
fi

# --- Deploy ----------------------------------------------------------------
command -v docker >/dev/null 2>&1 || { echo "ERROR: docker not found." >&2; exit 1; }
if [ "$(docker inspect -f '{{.State.Running}}' "$CONTAINER" 2>/dev/null)" != "true" ]; then
	echo "ERROR: container '$CONTAINER' is not running." >&2
	echo "       Set WP_CONTAINER to the correct name and retry." >&2
	exit 1
fi

echo "==> Deploying into $CONTAINER:$WP_PLUGINS/$SLUG"
docker cp "$ZIP_PATH" "$CONTAINER:/tmp/$ZIP_NAME"

# Replace any existing copy, extract via PHP (no unzip dependency), fix ownership.
docker exec "$CONTAINER" sh -c "rm -rf '$WP_PLUGINS/$SLUG'"
docker exec "$CONTAINER" php -r '
	$zip = new ZipArchive();
	if ($zip->open($argv[1]) !== true) { fwrite(STDERR, "ERROR: cannot open zip\n"); exit(1); }
	if (! $zip->extractTo($argv[2])) { fwrite(STDERR, "ERROR: extract failed\n"); exit(1); }
	$zip->close();
	echo "    extracted\n";
' "/tmp/$ZIP_NAME" "$WP_PLUGINS"
docker exec "$CONTAINER" sh -c "chown -R www-data:www-data '$WP_PLUGINS/$SLUG' && rm -f '/tmp/$ZIP_NAME'"

# --- Activate --------------------------------------------------------------
echo "==> Activating plugin"
# FS_CHMOD_DIR/FILE are normally defined by wp-admin/includes/file.php, which a
# CLI bootstrap does not load. Some other plugins (e.g. feedzy) reference them on
# the init hook, so predefine WordPress' own default values to avoid a fatal.
docker exec "$CONTAINER" php -r '
	if (! defined("FS_CHMOD_DIR"))  { define("FS_CHMOD_DIR", 0755); }
	if (! defined("FS_CHMOD_FILE")) { define("FS_CHMOD_FILE", 0644); }
	require $argv[1];
	require_once ABSPATH . "wp-admin/includes/plugin.php";
	$plugin = "ewo-rss-engine/ewo-rss-engine.php";
	if (is_plugin_active($plugin)) { echo "    already active\n"; exit(0); }
	$result = activate_plugin($plugin);
	if (is_wp_error($result)) { fwrite(STDERR, "ERROR: " . $result->get_error_message() . "\n"); exit(1); }
	echo is_plugin_active($plugin) ? "    activated\n" : "    activation reported but not active\n";
' "$WP_LOAD"

echo "==> Done. EWO RSS Engine v$VERSION is deployed and active."
