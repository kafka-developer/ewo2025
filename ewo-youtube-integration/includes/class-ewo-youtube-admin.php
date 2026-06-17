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
			array( $this->settings, 'render_page' )
		);
	}
}
