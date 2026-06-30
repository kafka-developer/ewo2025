<?php
/**
 * Plugin Name: EWO Community Wall
 * Description: Custom admin UI and frontend pages for managing Community Wall posts.
 * Version: 1.0.0
 * Author: EWO
 * Text Domain: ewo-community-wall
 * Requires at least: 6.0
 * Requires PHP: 7.4
 *
 * @package EWO_Community_Wall
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'EWO_CW_VERSION', '1.0.0' );
define( 'EWO_CW_FILE',    __FILE__ );
define( 'EWO_CW_PATH',    plugin_dir_path( __FILE__ ) );
define( 'EWO_CW_URL',     plugin_dir_url( __FILE__ ) );

require_once EWO_CW_PATH . 'includes/class-ewo-community-admin.php';

function ewo_community_wall_load() {
	$admin = new EWO_Community_Admin();
	$admin->init();
}
add_action( 'plugins_loaded', 'ewo_community_wall_load' );
