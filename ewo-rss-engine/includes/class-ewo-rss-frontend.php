<?php
/**
 * Front-end visibility: hide imported posts whose feed is Disabled/Hidden/Deleted.
 *
 * Status-driven and importer-agnostic — it reads the unified feed model, so it
 * covers native and Feedzy imports identically. Non-imported posts are untouched.
 *
 * @package EWO_RSS_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Front-end query filter.
 */
class EWO_RSS_Frontend {
	const TRANSIENT = 'ewo_rss_hidden_feeds';

	/**
	 * Register hooks.
	 */
	public function init() {
		add_action( 'pre_get_posts', array( $this, 'filter' ) );
	}

	/**
	 * Feed IDs whose content is hidden on the front end.
	 *
	 * @return int[]
	 */
	public static function hidden_feed_ids() {
		$cached = get_transient( self::TRANSIENT );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$hidden = array();
		foreach ( EWO_RSS_Feed::all( true ) as $feed_id ) {
			if ( EWO_RSS_Feed::is_frontend_hidden( $feed_id ) ) {
				$hidden[] = (int) $feed_id;
			}
		}

		set_transient( self::TRANSIENT, $hidden, HOUR_IN_SECONDS );

		return $hidden;
	}

	/**
	 * Exclude hidden-feed posts from front-end post queries.
	 *
	 * @param WP_Query $query Query.
	 */
	public function filter( $query ) {
		if ( is_admin() || ! $query instanceof WP_Query ) {
			return;
		}

		$post_type = $query->get( 'post_type' );
		if ( empty( $post_type ) ) {
			$post_type = 'post';
		}
		$types = (array) $post_type;
		if ( ! in_array( 'post', $types, true ) && ! in_array( 'any', $types, true ) ) {
			return;
		}

		$hidden = self::hidden_feed_ids();
		if ( empty( $hidden ) ) {
			return;
		}

		// Keep posts that are not from a hidden feed, by either canonical or
		// legacy Feedzy linkage. Non-imported posts (no link meta) are kept.
		$exclude = array(
			'relation' => 'AND',
			array(
				'relation' => 'OR',
				array(
					'key'     => EWO_RSS_Meta::FEED_ID,
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => EWO_RSS_Meta::FEED_ID,
					'value'   => $hidden,
					'compare' => 'NOT IN',
				),
			),
			array(
				'relation' => 'OR',
				array(
					'key'     => 'feedzy_job',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => 'feedzy_job',
					'value'   => $hidden,
					'compare' => 'NOT IN',
				),
			),
		);

		$existing = $query->get( 'meta_query' );
		if ( ! empty( $existing ) ) {
			$query->set( 'meta_query', array( 'relation' => 'AND', $existing, $exclude ) );
		} else {
			$query->set( 'meta_query', array( $exclude ) );
		}
	}
}
