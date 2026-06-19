<?php
/**
 * Feedzy compatibility adapter.
 *
 * Feedzy is now only a temporary import layer: each item it creates is routed
 * through the canonical {@see EWO_RSS_Ingest} pipeline (dedup, attribution,
 * flags, audit), exactly like native imports. Feed status, front-end hiding,
 * dedup tooling and dashboards live in the shared engine classes, so Feedzy can
 * be removed later without touching downstream systems.
 *
 * @package EWO_RSS_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adapts Feedzy imports onto the EWO ingest pipeline.
 */
class EWO_RSS_Feedzy_Bridge {

	/**
	 * Per-job run tally for this request: job_id => counts.
	 *
	 * @var array<int,array<string,int>>
	 */
	protected $runs = array();

	/**
	 * Whether the shutdown flush is registered.
	 *
	 * @var bool
	 */
	protected $shutdown_registered = false;

	/**
	 * Register hooks.
	 */
	public function init() {
		add_action( 'feedzy_after_post_import', array( $this, 'on_post_import' ), 20, 3 );
	}

	/**
	 * Route a Feedzy-imported post through the canonical pipeline.
	 *
	 * @param int                 $post_id  New post ID.
	 * @param array<string,mixed> $item     Imported feed item.
	 * @param array<string,mixed> $settings Import settings (unused).
	 */
	public function on_post_import( $post_id, $item, $settings = array() ) {
		$post_id = (int) $post_id;
		if ( $post_id <= 0 ) {
			return;
		}

		$job_id      = (int) get_post_meta( $post_id, 'feedzy_job', true );
		$item        = is_array( $item ) ? $item : array();
		$article_url = ! empty( $item['item_url'] ) ? (string) $item['item_url'] : (string) get_post_meta( $post_id, 'feedzy_item_url', true );
		$guid        = ! empty( $item['item_id'] ) ? (string) $item['item_id'] : $article_url;

		$status = EWO_RSS_Ingest::finalize(
			$post_id,
			array(
				'feed_id'     => $job_id,
				'importer'    => EWO_RSS_Meta::IMPORTER_FEEDZY,
				'article_url' => $article_url,
				'guid'        => $guid,
			)
		);

		if ( ! isset( $this->runs[ $job_id ] ) ) {
			$this->runs[ $job_id ] = array(
				'imported'   => 0,
				'duplicates' => 0,
				'errors'     => 0,
			);
		}

		if ( 'imported' === $status ) {
			++$this->runs[ $job_id ]['imported'];
		} elseif ( 'duplicate' === $status ) {
			++$this->runs[ $job_id ]['duplicates'];
		} elseif ( 'rejected_disabled' === $status ) {
			++$this->runs[ $job_id ]['errors'];
		}

		$this->register_shutdown();
	}

	/**
	 * Register the per-request shutdown flush once.
	 */
	protected function register_shutdown() {
		if ( $this->shutdown_registered ) {
			return;
		}
		$this->shutdown_registered = true;
		add_action( 'shutdown', array( $this, 'flush_runs' ) );
	}

	/**
	 * Write one audit run per Feedzy job touched this request.
	 */
	public function flush_runs() {
		foreach ( $this->runs as $job_id => $counts ) {
			if ( (int) $job_id <= 0 ) {
				continue;
			}
			EWO_RSS_Ingest::record_run(
				(int) $job_id,
				EWO_RSS_Meta::IMPORTER_FEEDZY,
				'success',
				$counts,
				0,
				__( 'Feedzy import run', 'ewo-rss-engine' )
			);
		}
		$this->runs = array();
	}
}
