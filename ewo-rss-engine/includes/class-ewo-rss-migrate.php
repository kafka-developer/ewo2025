<?php
/**
 * One-time data migration to the canonical schema.
 *
 * Standardizes legacy meta keys, backfills structured source attribution,
 * content flags, dedup hashes and feed status, and installs the audit table.
 *
 * @package EWO_RSS_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Schema migration.
 */
class EWO_RSS_Migrate {
	const OPTION  = 'ewo_rss_schema_version';
	const VERSION = '1';

	/**
	 * Run migration once.
	 */
	public function maybe_migrate() {
		if ( self::VERSION === get_option( self::OPTION ) ) {
			return;
		}
		$this->run();
		update_option( self::OPTION, self::VERSION, false );
	}

	/**
	 * Execute the migration.
	 *
	 * @return array{posts:int,feeds:int} Counts processed.
	 */
	public function run() {
		EWO_RSS_Audit_Log::maybe_install();

		$posts = $this->migrate_posts();
		$feeds = $this->migrate_feeds();

		if ( function_exists( 'ewo_rss_engine_flush_feed_cache' ) ) {
			ewo_rss_engine_flush_feed_cache();
		}

		return array(
			'posts' => $posts,
			'feeds' => $feeds,
		);
	}

	/**
	 * Backfill canonical attribution + flags on every imported post.
	 *
	 * @return int Number of posts migrated.
	 */
	protected function migrate_posts() {
		$ids = get_posts(
			array(
				'post_type'      => 'any',
				'post_status'    => array( 'publish', 'pending', 'draft', 'future', 'private', 'trash' ),
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'relation' => 'OR',
					array(
						'key'     => '_ewo_rss_source_url',
						'compare' => 'EXISTS',
					),
					array(
						'key'     => '_ewo_rss_guid',
						'compare' => 'EXISTS',
					),
					array(
						'key'     => 'feedzy_item_url',
						'compare' => 'EXISTS',
					),
				),
			)
		);

		$count = 0;

		foreach ( $ids as $post_id ) {
			$is_feedzy = metadata_exists( 'post', $post_id, 'feedzy_job' );
			$importer  = $is_feedzy ? EWO_RSS_Meta::IMPORTER_FEEDZY : EWO_RSS_Meta::IMPORTER_NATIVE;

			$feed_id = $is_feedzy
				? (int) get_post_meta( $post_id, 'feedzy_job', true )
				: (int) get_post_meta( $post_id, '_ewo_rss_source_id', true );

			$article_url = $is_feedzy
				? (string) get_post_meta( $post_id, 'feedzy_item_url', true )
				: (string) get_post_meta( $post_id, '_ewo_rss_source_url', true );
			if ( '' === $article_url ) {
				$article_url = EWO_RSS_Meta::article_url( $post_id );
			}

			$guid = (string) get_post_meta( $post_id, '_ewo_rss_guid', true );
			if ( '' === $guid ) {
				$guid = $article_url;
			}

			EWO_RSS_Meta::stamp(
				$post_id,
				array(
					'feed_id'     => $feed_id,
					'feed_name'   => $feed_id ? get_the_title( $feed_id ) : '',
					'feed_url'    => $feed_id ? EWO_RSS_Feed::url( $feed_id ) : '',
					'article_url' => $article_url,
					'importer'    => $importer,
					'guid'        => $guid,
					'imported_at' => get_post_time( 'Y-m-d H:i:s', true, $post_id ),
				)
			);

			EWO_RSS_Meta::stamp_flags( $post_id, (string) get_post_field( 'post_content', $post_id ), $article_url );

			// Retire the legacy article-url key now that the canonical one exists.
			delete_post_meta( $post_id, '_ewo_rss_source_url' );
			delete_post_meta( $post_id, 'ewo_rss_source_url' );

			++$count;
		}

		return $count;
	}

	/**
	 * Set an explicit canonical status + initialize totals on every feed.
	 *
	 * @return int Number of feeds migrated.
	 */
	protected function migrate_feeds() {
		$count = 0;

		foreach ( EWO_RSS_Feed::all( true ) as $feed_id ) {
			if ( '' === (string) get_post_meta( $feed_id, EWO_RSS_Feed::STATUS_META, true ) ) {
				update_post_meta( $feed_id, EWO_RSS_Feed::STATUS_META, EWO_RSS_Feed::derive_status( $feed_id ) );
			}

			if ( '' === (string) get_post_meta( $feed_id, EWO_RSS_Feed::M_TOTAL_IMPORTS, true ) ) {
				$total = count(
					get_posts(
						array(
							'post_type'      => 'any',
							'post_status'    => array( 'publish', 'pending', 'draft', 'future', 'private' ),
							'posts_per_page' => -1,
							'fields'         => 'ids',
							'meta_key'       => EWO_RSS_Meta::FEED_ID,
							'meta_value'     => (int) $feed_id,
						)
					)
				);
				update_post_meta( $feed_id, EWO_RSS_Feed::M_TOTAL_IMPORTS, $total );
			}

			++$count;
		}

		return $count;
	}
}
