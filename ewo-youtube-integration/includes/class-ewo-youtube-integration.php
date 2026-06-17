<?php
/**
 * Main plugin coordinator.
 *
 * @package EWO_YouTube_Integration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Coordinates plugin components.
 */
class EWO_YouTube_Integration {
	/**
	 * Initialize plugin hooks.
	 */
	public function init() {
		$post_types = new EWO_YouTube_Post_Types();
		$settings   = new EWO_YouTube_Settings();
		$admin      = new EWO_YouTube_Admin( $settings );
		$marquee    = new EWO_YouTube_Marquee();
		$shorts     = new EWO_YouTube_Shorts();
		$sync        = new EWO_YouTube_Sync();
		$bulk_import         = new EWO_YouTube_Bulk_Import();
		$playlist_management = new EWO_YouTube_Playlist_Management();
		$playlists           = new EWO_YouTube_Playlists();

		$post_types->init();
		$settings->init();
		$admin->init();
		$marquee->init();
		$shorts->init();
		$sync->init();
		$bulk_import->init();
		$playlist_management->init();
		$playlists->init();
	}
}
