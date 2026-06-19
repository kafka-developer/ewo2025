<?php
/**
 * Cron scheduling for unattended imports.
 *
 * @package EWO_RSS_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Binds the import routine to the WP-Cron hook.
 */
class EWO_RSS_Scheduler {

	/**
	 * Sources component.
	 *
	 * @var EWO_RSS_Sources
	 */
	protected $sources;

	/**
	 * Importer component.
	 *
	 * @var EWO_RSS_Importer
	 */
	protected $importer;

	/**
	 * Constructor.
	 *
	 * @param EWO_RSS_Sources  $sources  Sources component.
	 * @param EWO_RSS_Importer $importer Importer component.
	 */
	public function __construct( EWO_RSS_Sources $sources, EWO_RSS_Importer $importer ) {
		$this->sources  = $sources;
		$this->importer = $importer;
	}

	/**
	 * Register the cron callback.
	 */
	public function init() {
		add_action( EWO_RSS_ENGINE_CRON_HOOK, array( $this, 'run' ) );
	}

	/**
	 * Run the scheduled import of all active sources.
	 */
	public function run() {
		$this->importer->import_all();
	}
}
