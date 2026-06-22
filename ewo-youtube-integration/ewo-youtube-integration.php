<?php
/**
 * Plugin Name: EWO YouTube Integration
 * Description: Adds YouTube-related content types and admin settings for EWO.
 * Version: 0.2.7
 * Author: EWO
 * Text Domain: ewo-youtube-integration
 * Requires at least: 6.0
 * Requires PHP: 7.4
 *
 * @package EWO_YouTube_Integration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'EWO_YOUTUBE_INTEGRATION_VERSION', '0.2.7' );
define( 'EWO_YOUTUBE_INTEGRATION_FILE', __FILE__ );
define( 'EWO_YOUTUBE_INTEGRATION_PATH', plugin_dir_path( __FILE__ ) );
define( 'EWO_YOUTUBE_INTEGRATION_URL', plugin_dir_url( __FILE__ ) );

require_once EWO_YOUTUBE_INTEGRATION_PATH . 'includes/class-ewo-youtube-integration.php';
require_once EWO_YOUTUBE_INTEGRATION_PATH . 'includes/class-ewo-youtube-post-types.php';
require_once EWO_YOUTUBE_INTEGRATION_PATH . 'includes/class-ewo-youtube-meta-boxes.php';
require_once EWO_YOUTUBE_INTEGRATION_PATH . 'includes/class-ewo-youtube-settings.php';
require_once EWO_YOUTUBE_INTEGRATION_PATH . 'includes/class-ewo-youtube-admin.php';
require_once EWO_YOUTUBE_INTEGRATION_PATH . 'includes/class-ewo-youtube-sync.php';
require_once EWO_YOUTUBE_INTEGRATION_PATH . 'includes/class-ewo-youtube-bulk-import.php';
require_once EWO_YOUTUBE_INTEGRATION_PATH . 'includes/class-ewo-youtube-playlist-management.php';
require_once EWO_YOUTUBE_INTEGRATION_PATH . 'includes/class-ewo-youtube-video-management.php';
require_once EWO_YOUTUBE_INTEGRATION_PATH . 'includes/class-ewo-youtube-community-management.php';
require_once EWO_YOUTUBE_INTEGRATION_PATH . 'includes/class-ewo-youtube-shorts-management.php';
require_once EWO_YOUTUBE_INTEGRATION_PATH . 'includes/class-ewo-youtube-marquee.php';
require_once EWO_YOUTUBE_INTEGRATION_PATH . 'includes/class-ewo-youtube-shorts.php';
require_once EWO_YOUTUBE_INTEGRATION_PATH . 'includes/class-ewo-youtube-playlists.php';

/**
 * Initialize the plugin.
 */
function ewo_youtube_integration_load() {
	$plugin = new EWO_YouTube_Integration();
	$plugin->init();
}
add_action( 'plugins_loaded', 'ewo_youtube_integration_load' );

/**
 * Add a settings link on the Plugins page.
 *
 * @param string[] $links Plugin action links.
 * @return string[]
 */
function ewo_youtube_integration_action_links( $links ) {
	$settings_link = sprintf(
		'<a href="%1$s">%2$s</a>',
		esc_url( admin_url( 'admin.php?page=ewo-youtube' ) ),
		esc_html__( 'Settings', 'ewo-youtube-integration' )
	);

	array_unshift( $links, $settings_link );

	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'ewo_youtube_integration_action_links' );

/**
 * Render the YouTube video marquee.
 *
 * @return string
 */
function ewo_youtube_marquee() {
	$marquee = new EWO_YouTube_Marquee();

	return $marquee->render();
}

/**
 * Render the YouTube Shorts grid.
 *
 * @return string
 */
function ewo_youtube_shorts() {
	$shorts = new EWO_YouTube_Shorts();

	return $shorts->render();
}

/**
 * Render the YouTube playlists grid.
 *
 * @return string
 */
function ewo_youtube_playlists() {
	$playlists = new EWO_YouTube_Playlists();

	return $playlists->render();
}

/**
 * Flush rewrite rules when the plugin is activated.
 */
function ewo_youtube_integration_activate() {
	$post_types = new EWO_YouTube_Post_Types();
	$post_types->register();

	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'ewo_youtube_integration_activate' );

/**
 * Flush rewrite rules when the plugin is deactivated.
 */
function ewo_youtube_integration_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'ewo_youtube_integration_deactivate' );
