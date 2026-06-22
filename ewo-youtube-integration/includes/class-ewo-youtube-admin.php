<?php
/**
 * Admin menu registration.
 *
 * @package EWO_YouTube_Integration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds the EWO YouTube admin menu.
 */
class EWO_YouTube_Admin {
	/**
	 * Settings renderer.
	 *
	 * @var EWO_YouTube_Settings
	 */
	private $settings;

	/**
	 * Constructor.
	 *
	 * @param EWO_YouTube_Settings $settings Settings renderer.
	 */
	public function __construct( EWO_YouTube_Settings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Initialize hooks.
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_menu', array( $this, 'reorder_submenu' ), 999 );
	}

	/**
	 * Enforce the visible EWO YouTube submenu order after every submenu has
	 * been registered. This is the single source of truth for ordering.
	 */
	public function reorder_submenu() {
		global $submenu;

		if ( empty( $submenu['ewo-youtube'] ) ) {
			return;
		}

		$order = array(
			'ewo-youtube-add-video',     // Videos.
			'ewo-youtube-shorts',        // Shorts.
			'ewo-youtube-add-community', // Community Posts.
			'ewo-youtube-playlists',     // Playlists.
			'ewo-youtube',               // Settings.
			'ewo-youtube-bulk-import',   // Bulk Import.
		);
		$rank  = array_flip( $order );

		usort(
			$submenu['ewo-youtube'],
			static function ( $a, $b ) use ( $rank ) {
				$rank_a = isset( $rank[ $a[2] ] ) ? $rank[ $a[2] ] : 999;
				$rank_b = isset( $rank[ $b[2] ] ) ? $rank[ $b[2] ] : 999;

				return $rank_a <=> $rank_b;
			}
		);
	}

	/**
	 * Register admin menu pages.
	 */
	public function register_menu() {
		add_menu_page(
			esc_html__( 'EWO YouTube', 'ewo-youtube-integration' ),
			esc_html__( 'EWO YouTube', 'ewo-youtube-integration' ),
			'manage_options',
			'ewo-youtube',
			array( $this->settings, 'render_page' ),
			'dashicons-video-alt3',
			30
		);

		add_submenu_page(
			'ewo-youtube',
			esc_html__( 'YouTube Settings', 'ewo-youtube-integration' ),
			esc_html__( 'Settings', 'ewo-youtube-integration' ),
			'manage_options',
			'ewo-youtube',
			array( $this->settings, 'render_page' ),
			50
		);
	}
}
