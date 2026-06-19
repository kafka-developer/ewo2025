<?php
/**
 * Plugin Name: EWO RSS Engine
 * Description: Canonical content ingestion engine for EWO. Unified feed model, source attribution, native deduplication, feed health, import audit log, and a Feedzy compatibility layer.
 * Version: 0.4.0
 * Author: EWO
 * Text Domain: ewo-rss-engine
 * Requires at least: 6.0
 * Requires PHP: 7.4
 *
 * @package EWO_RSS_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'EWO_RSS_ENGINE_VERSION', '0.4.0' );
define( 'EWO_RSS_ENGINE_FILE', __FILE__ );
define( 'EWO_RSS_ENGINE_PATH', plugin_dir_path( __FILE__ ) );
define( 'EWO_RSS_ENGINE_URL', plugin_dir_url( __FILE__ ) );
define( 'EWO_RSS_ENGINE_CRON_HOOK', 'ewo_rss_engine_run' );

// Canonical data layer + services.
require_once EWO_RSS_ENGINE_PATH . 'includes/class-ewo-rss-meta.php';
require_once EWO_RSS_ENGINE_PATH . 'includes/class-ewo-rss-feed.php';
require_once EWO_RSS_ENGINE_PATH . 'includes/class-ewo-rss-audit-log.php';
require_once EWO_RSS_ENGINE_PATH . 'includes/class-ewo-rss-dedup.php';
require_once EWO_RSS_ENGINE_PATH . 'includes/class-ewo-rss-ingest.php';

// Components.
require_once EWO_RSS_ENGINE_PATH . 'includes/class-ewo-rss-logs.php';
require_once EWO_RSS_ENGINE_PATH . 'includes/class-ewo-rss-sources.php';
require_once EWO_RSS_ENGINE_PATH . 'includes/class-ewo-rss-thumbnails.php';
require_once EWO_RSS_ENGINE_PATH . 'includes/class-ewo-rss-importer.php';
require_once EWO_RSS_ENGINE_PATH . 'includes/class-ewo-rss-scheduler.php';
require_once EWO_RSS_ENGINE_PATH . 'includes/class-ewo-rss-admin.php';
require_once EWO_RSS_ENGINE_PATH . 'includes/class-ewo-rss-feedzy-bridge.php';
require_once EWO_RSS_ENGINE_PATH . 'includes/class-ewo-rss-frontend.php';
require_once EWO_RSS_ENGINE_PATH . 'includes/class-ewo-rss-migrate.php';
require_once EWO_RSS_ENGINE_PATH . 'includes/class-ewo-rss-admin-feeds.php';
require_once EWO_RSS_ENGINE_PATH . 'includes/class-ewo-rss-admin-tools.php';
require_once EWO_RSS_ENGINE_PATH . 'includes/class-ewo-rss-engine.php';

/**
 * Flush cached feed-visibility lists (called when feed status changes).
 */
function ewo_rss_engine_flush_feed_cache() {
	delete_transient( EWO_RSS_Frontend::TRANSIENT );
	delete_transient( 'ewo_feed_hidden_map' ); // Legacy key.
}

/**
 * Boot the plugin.
 */
function ewo_rss_engine_load() {
	$engine = new EWO_RSS_Engine();
	$engine->init();
}
add_action( 'plugins_loaded', 'ewo_rss_engine_load' );

/**
 * Activation: register the source CPT, flush rewrite, schedule cron.
 */
function ewo_rss_engine_activate() {
	$sources = new EWO_RSS_Sources();
	$sources->register_post_type();
	flush_rewrite_rules();

	EWO_RSS_Audit_Log::maybe_install();

	if ( ! wp_next_scheduled( EWO_RSS_ENGINE_CRON_HOOK ) ) {
		wp_schedule_event( time() + 300, 'hourly', EWO_RSS_ENGINE_CRON_HOOK );
	}
}
register_activation_hook( __FILE__, 'ewo_rss_engine_activate' );

/**
 * Deactivation: clear the scheduled cron.
 */
function ewo_rss_engine_deactivate() {
	wp_clear_scheduled_hook( EWO_RSS_ENGINE_CRON_HOOK );
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'ewo_rss_engine_deactivate' );
