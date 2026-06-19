<?php
/**
 * Plugin coordinator.
 *
 * @package EWO_RSS_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Wires the engine components together.
 */
class EWO_RSS_Engine {
	/**
	 * Initialize components.
	 */
	public function init() {
		$sources       = new EWO_RSS_Sources();
		$importer      = new EWO_RSS_Importer( $sources );
		$scheduler     = new EWO_RSS_Scheduler( $sources, $importer );
		$admin         = new EWO_RSS_Admin( $sources, $importer );
		$feedzy_bridge = new EWO_RSS_Feedzy_Bridge();
		$frontend      = new EWO_RSS_Frontend();
		$admin_feeds   = new EWO_RSS_Admin_Feeds();
		$admin_tools   = new EWO_RSS_Admin_Tools();
		$migrate       = new EWO_RSS_Migrate();

		$sources->init();
		$scheduler->init();
		$admin->init();
		$feedzy_bridge->init();
		$frontend->init();
		$admin_feeds->init();
		$admin_tools->init();

		// One-time schema migration (admin context).
		add_action( 'admin_init', array( $migrate, 'maybe_migrate' ) );
	}
}
