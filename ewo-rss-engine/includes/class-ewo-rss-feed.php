<?php
/**
 * Unified feed model: one status + health API over both importers' feeds.
 *
 * A "feed" is either a native `ewo_rss_source` or a Feedzy `feedzy_imports`
 * job. Status and health metrics are stored as meta on the feed post so the
 * front end and admin can treat all feeds the same way.
 *
 * @package EWO_RSS_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Feed status + health.
 */
class EWO_RSS_Feed {
	const NATIVE_CPT = 'ewo_rss_source';
	const FEEDZY_CPT = 'feedzy_imports';

	const STATUS_META = '_ewo_feed_status';

	const STATUS_ENABLED  = 'enabled';
	const STATUS_DISABLED = 'disabled';
	const STATUS_HIDDEN   = 'hidden';
	const STATUS_DELETED  = 'deleted';

	/* Health metrics (feed meta). */
	const M_LAST_SUCCESS  = '_ewo_feed_last_success';
	const M_LAST_FAILURE  = '_ewo_feed_last_failure';
	const M_CONSEC_FAILS  = '_ewo_feed_consecutive_failures';
	const M_ERROR_COUNT   = '_ewo_feed_error_count';
	const M_LAST_ERROR    = '_ewo_feed_last_error';
	const M_RESPONSE_MS   = '_ewo_feed_last_response_ms';
	const M_AVG_RESPONSE  = '_ewo_feed_avg_response_ms';
	const M_TOTAL_IMPORTS = '_ewo_feed_total_imported';

	const HEALTH_HEALTHY = 'healthy';
	const HEALTH_WARNING = 'warning';
	const HEALTH_FAILING = 'failing';

	/**
	 * Managed feed post types.
	 *
	 * @return string[]
	 */
	public static function types() {
		return array( self::NATIVE_CPT, self::FEEDZY_CPT );
	}

	/**
	 * Feed type for an ID: 'native', 'feedzy', or ''.
	 *
	 * @param int $feed_id Feed ID.
	 * @return string
	 */
	public static function type( $feed_id ) {
		$post = get_post( (int) $feed_id );
		if ( ! $post ) {
			return '';
		}
		if ( self::NATIVE_CPT === $post->post_type ) {
			return 'native';
		}
		if ( self::FEEDZY_CPT === $post->post_type ) {
			return 'feedzy';
		}
		return '';
	}

	/**
	 * All configured feed IDs (excludes Deleted/trashed).
	 *
	 * @param bool $include_deleted Include feeds marked deleted.
	 * @return int[]
	 */
	public static function all( $include_deleted = false ) {
		$ids = array();
		foreach ( self::types() as $cpt ) {
			if ( ! post_type_exists( $cpt ) ) {
				continue;
			}
			$ids = array_merge(
				$ids,
				get_posts(
					array(
						'post_type'      => $cpt,
						'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
						'posts_per_page' => -1,
						'fields'         => 'ids',
					)
				)
			);
		}

		$ids = array_map( 'intval', $ids );

		if ( ! $include_deleted ) {
			$ids = array_values(
				array_filter(
					$ids,
					static function ( $id ) {
						return self::STATUS_DELETED !== self::status( $id );
					}
				)
			);
		}

		return $ids;
	}

	/**
	 * Get the canonical status for a feed, deriving a default from legacy
	 * signals when not yet set.
	 *
	 * @param int $feed_id Feed ID.
	 * @return string
	 */
	public static function status( $feed_id ) {
		$status = (string) get_post_meta( (int) $feed_id, self::STATUS_META, true );
		if ( in_array( $status, array( self::STATUS_ENABLED, self::STATUS_DISABLED, self::STATUS_HIDDEN, self::STATUS_DELETED ), true ) ) {
			return $status;
		}

		return self::derive_status( $feed_id );
	}

	/**
	 * Derive a status from legacy enable flags / post status / disable mode.
	 *
	 * @param int $feed_id Feed ID.
	 * @return string
	 */
	public static function derive_status( $feed_id ) {
		$post = get_post( (int) $feed_id );
		if ( ! $post ) {
			return self::STATUS_DELETED;
		}

		$mode = (string) get_post_meta( $feed_id, '_ewo_feed_disable_mode', true );

		if ( self::FEEDZY_CPT === $post->post_type ) {
			if ( 'publish' !== $post->post_status ) {
				return 'hide_existing' === $mode ? self::STATUS_HIDDEN : self::STATUS_DISABLED;
			}
			return self::STATUS_ENABLED;
		}

		// Native source.
		if ( 'publish' !== $post->post_status ) {
			return self::STATUS_DISABLED;
		}
		$enabled = '1' === (string) get_post_meta( $feed_id, '_ewo_rss_enabled', true );
		if ( ! $enabled ) {
			return 'hide_existing' === $mode ? self::STATUS_HIDDEN : self::STATUS_DISABLED;
		}

		return self::STATUS_ENABLED;
	}

	/**
	 * Set a feed's status (and keep legacy native flag in sync).
	 *
	 * @param int    $feed_id Feed ID.
	 * @param string $status  New status.
	 */
	public static function set_status( $feed_id, $status ) {
		if ( ! in_array( $status, array( self::STATUS_ENABLED, self::STATUS_DISABLED, self::STATUS_HIDDEN, self::STATUS_DELETED ), true ) ) {
			return;
		}

		update_post_meta( (int) $feed_id, self::STATUS_META, $status );

		// Keep the native importer's enable flag consistent.
		if ( 'native' === self::type( $feed_id ) ) {
			update_post_meta( (int) $feed_id, '_ewo_rss_enabled', self::import_allowed( $feed_id, $status ) ? '1' : '' );
		}

		if ( function_exists( 'ewo_rss_engine_flush_feed_cache' ) ) {
			ewo_rss_engine_flush_feed_cache();
		}
	}

	/**
	 * Whether imports are allowed for a feed (Enabled or Hidden keep importing).
	 *
	 * @param int         $feed_id Feed ID.
	 * @param string|null $status  Optional pre-resolved status.
	 * @return bool
	 */
	public static function import_allowed( $feed_id, $status = null ) {
		$status = null === $status ? self::status( $feed_id ) : $status;

		return in_array( $status, array( self::STATUS_ENABLED, self::STATUS_HIDDEN ), true );
	}

	/**
	 * Whether a feed's items are hidden on the front end (Disabled or Hidden).
	 *
	 * @param int $feed_id Feed ID.
	 * @return bool
	 */
	public static function is_frontend_hidden( $feed_id ) {
		return in_array( self::status( $feed_id ), array( self::STATUS_DISABLED, self::STATUS_HIDDEN, self::STATUS_DELETED ), true );
	}

	/**
	 * Feed display name.
	 *
	 * @param int $feed_id Feed ID.
	 * @return string
	 */
	public static function name( $feed_id ) {
		return get_the_title( (int) $feed_id );
	}

	/**
	 * Feed (RSS) URL.
	 *
	 * @param int $feed_id Feed ID.
	 * @return string
	 */
	public static function url( $feed_id ) {
		if ( 'native' === self::type( $feed_id ) ) {
			return (string) get_post_meta( $feed_id, '_ewo_rss_feed_url', true );
		}
		// Feedzy stores the source URL(s) in the `source` meta.
		$source = get_post_meta( $feed_id, 'source', true );
		if ( is_array( $source ) ) {
			$source = reset( $source );
		}
		return (string) $source;
	}

	/* ---------------------------------------------------------------------
	 * Health metrics
	 * ------------------------------------------------------------------- */

	/**
	 * Record a successful import run.
	 *
	 * @param int $feed_id      Feed ID.
	 * @param int $imported     Items imported this run.
	 * @param int $response_ms  Fetch time in ms.
	 */
	public static function record_success( $feed_id, $imported = 0, $response_ms = 0 ) {
		$feed_id = (int) $feed_id;
		update_post_meta( $feed_id, self::M_LAST_SUCCESS, current_time( 'mysql', true ) );
		update_post_meta( $feed_id, self::M_CONSEC_FAILS, 0 );
		update_post_meta( $feed_id, self::M_RESPONSE_MS, (int) $response_ms );
		self::update_avg_response( $feed_id, (int) $response_ms );

		$total = (int) get_post_meta( $feed_id, self::M_TOTAL_IMPORTS, true );
		update_post_meta( $feed_id, self::M_TOTAL_IMPORTS, $total + max( 0, (int) $imported ) );
	}

	/**
	 * Record a failed import run.
	 *
	 * @param int    $feed_id     Feed ID.
	 * @param string $error       Error message.
	 * @param int    $response_ms Fetch time in ms.
	 */
	public static function record_failure( $feed_id, $error = '', $response_ms = 0 ) {
		$feed_id = (int) $feed_id;
		update_post_meta( $feed_id, self::M_LAST_FAILURE, current_time( 'mysql', true ) );
		update_post_meta( $feed_id, self::M_LAST_ERROR, sanitize_text_field( (string) $error ) );

		$consec = (int) get_post_meta( $feed_id, self::M_CONSEC_FAILS, true ) + 1;
		update_post_meta( $feed_id, self::M_CONSEC_FAILS, $consec );

		$errors = (int) get_post_meta( $feed_id, self::M_ERROR_COUNT, true ) + 1;
		update_post_meta( $feed_id, self::M_ERROR_COUNT, $errors );

		if ( $response_ms > 0 ) {
			update_post_meta( $feed_id, self::M_RESPONSE_MS, (int) $response_ms );
		}
	}

	/**
	 * Roll a simple moving average for response time.
	 *
	 * @param int $feed_id     Feed ID.
	 * @param int $response_ms Latest sample.
	 */
	protected static function update_avg_response( $feed_id, $response_ms ) {
		if ( $response_ms <= 0 ) {
			return;
		}
		$avg = (float) get_post_meta( $feed_id, self::M_AVG_RESPONSE, true );
		$avg = $avg > 0 ? ( ( $avg * 0.7 ) + ( $response_ms * 0.3 ) ) : $response_ms;
		update_post_meta( $feed_id, self::M_AVG_RESPONSE, round( $avg ) );
	}

	/**
	 * Increment the total-imported counter (used by the ingest pipeline).
	 *
	 * @param int $feed_id Feed ID.
	 * @param int $by      Increment.
	 */
	public static function add_imported( $feed_id, $by = 1 ) {
		$feed_id = (int) $feed_id;
		$total   = (int) get_post_meta( $feed_id, self::M_TOTAL_IMPORTS, true );
		update_post_meta( $feed_id, self::M_TOTAL_IMPORTS, $total + max( 0, (int) $by ) );
	}

	/**
	 * Health classification for a feed.
	 *
	 * @param int $feed_id Feed ID.
	 * @return string One of the HEALTH_* constants.
	 */
	public static function health( $feed_id ) {
		$consec = (int) get_post_meta( $feed_id, self::M_CONSEC_FAILS, true );

		if ( $consec >= 3 ) {
			return self::HEALTH_FAILING;
		}
		if ( $consec >= 1 ) {
			return self::HEALTH_WARNING;
		}

		return self::HEALTH_HEALTHY;
	}

	/**
	 * Full metrics snapshot for a feed.
	 *
	 * @param int $feed_id Feed ID.
	 * @return array<string,mixed>
	 */
	public static function metrics( $feed_id ) {
		$feed_id = (int) $feed_id;

		return array(
			'id'                   => $feed_id,
			'name'                 => self::name( $feed_id ),
			'url'                  => self::url( $feed_id ),
			'type'                 => self::type( $feed_id ),
			'status'               => self::status( $feed_id ),
			'health'               => self::health( $feed_id ),
			'last_success'         => (string) get_post_meta( $feed_id, self::M_LAST_SUCCESS, true ),
			'last_failure'         => (string) get_post_meta( $feed_id, self::M_LAST_FAILURE, true ),
			'consecutive_failures' => (int) get_post_meta( $feed_id, self::M_CONSEC_FAILS, true ),
			'error_count'          => (int) get_post_meta( $feed_id, self::M_ERROR_COUNT, true ),
			'last_error'           => (string) get_post_meta( $feed_id, self::M_LAST_ERROR, true ),
			'response_ms'          => (int) get_post_meta( $feed_id, self::M_RESPONSE_MS, true ),
			'avg_response_ms'      => (int) get_post_meta( $feed_id, self::M_AVG_RESPONSE, true ),
			'total_imported'       => (int) get_post_meta( $feed_id, self::M_TOTAL_IMPORTS, true ),
		);
	}
}
