<?php
/**
 * Plugin Name: EWO Predictions
 * Description: Custom admin UI for managing EWO geopolitical, economic, and strategic predictions.
 * Version: 1.0.0
 * Author: EWO
 * Text Domain: ewo-predictions
 * Requires at least: 6.0
 * Requires PHP: 7.4
 *
 * @package EWO_Predictions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'EWO_PRED_VERSION', '1.0.0' );
define( 'EWO_PRED_FILE',    __FILE__ );
define( 'EWO_PRED_PATH',    plugin_dir_path( __FILE__ ) );
define( 'EWO_PRED_URL',     plugin_dir_url( __FILE__ ) );

require_once EWO_PRED_PATH . 'includes/class-ewo-predictions-db.php';
require_once EWO_PRED_PATH . 'includes/class-ewo-predictions-admin.php';

function ewo_predictions_load() {
	$admin = new EWO_Predictions_Admin();
	$admin->init();
}
add_action( 'plugins_loaded', 'ewo_predictions_load' );

register_activation_hook( __FILE__, function () {
	EWO_Predictions_DB::maybe_install();
} );
