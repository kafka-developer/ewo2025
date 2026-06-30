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
	 * Register the cron callbacks and self-heal the keyword schedule.
	 */
	public function init() {
		add_action( EWO_RSS_ENGINE_CRON_HOOK, array( $this, 'run' ) );
		add_action( EWO_RSS_ENGINE_KEYWORDS_CRON_HOOK, array( $this, 'run_keywords' ) );

		// Already-active installs (no re-activation) get the 30-min schedule here.
		if ( ! wp_next_scheduled( EWO_RSS_ENGINE_KEYWORDS_CRON_HOOK ) ) {
			wp_schedule_event( time() + 300, EWO_RSS_ENGINE_KEYWORDS_SCHEDULE, EWO_RSS_ENGINE_KEYWORDS_CRON_HOOK );
		}
	}

	/**
	 * Run the scheduled import of all active (non-keyword) sources.
	 */
	public function run() {
		$this->importer->import_all();
	}

	/**
	 * Run the scheduled fetch of all active keyword feeds (every 30 minutes).
	 */
	public function run_keywords() {
		EWO_RSS_Keyword_Feeds::fetch_all_active();
	}
}
