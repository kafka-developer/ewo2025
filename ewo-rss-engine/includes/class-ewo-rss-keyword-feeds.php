<?php
/**
 * Keyword → auto-generated feed → captured Sources.
 *
 * Each active keyword is mirrored to a native feed (the existing
 * `ewo_rss_source` CPT) whose URL is a Google News RSS search. Those feeds are
 * tagged `generated_by = keyword` and carry domain/subdomain/keyword
 * attribution meta, so they reuse the engine's feed-health, status, and the
 * same `fetch_feed()` retrieval the native importer uses. Items become Source
 * rows via {@see EWO_RSS_Source_Store} rather than WordPress posts.
 *
 * @package EWO_RSS_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Keyword feed generation + Source capture.
 */
class EWO_RSS_Keyword_Feeds {

	/* Feed-post meta marking a keyword-generated feed + its attribution. */
	const META_GENERATED_BY = '_ewo_rss_generated_by';
	const META_KEYWORD_ID   = '_ewo_rss_keyword_id';
	const META_SUBDOMAIN_ID = '_ewo_rss_subdomain_id';
	const META_DOMAIN_ID    = '_ewo_rss_strategic_domain_id';

	const GENERATED_BY = 'keyword';

	/* Items pulled per keyword feed per run. */
	const MAX_ITEMS = 25;

	/**
	 * Build the Google News RSS search URL for a keyword.
	 *
	 * @param string $keyword Keyword text.
	 * @return string
	 */
	public static function build_news_url( $keyword ) {
		return 'https://news.google.com/rss/search?q=' . rawurlencode( (string) $keyword ) . '&hl=en-US&gl=US&ceid=US:en';
	}

	/**
	 * Whether a feed post is keyword-generated.
	 *
	 * @param int $feed_id Feed (source) post ID.
	 * @return bool
	 */
	public static function is_keyword_feed( $feed_id ) {
		return self::GENERATED_BY === (string) get_post_meta( (int) $feed_id, self::META_GENERATED_BY, true );
	}

	/* ---------------------------------------------------------------------
	 * Feed sync (create / update / disable / delete)
	 * ------------------------------------------------------------------- */

	/**
	 * Ensure a keyword has exactly one matching feed; create/update/disable it
	 * to reflect the keyword's current text and active state.
	 *
	 * @param int|object $keyword Keyword ID or row.
	 * @return int Feed (source) post ID, or 0.
	 */
	public static function sync_keyword( $keyword ) {
		$ctx = EWO_RSS_Taxonomy::context_for_keyword( $keyword );
		$kw  = $ctx['keyword'];
		if ( ! $kw ) {
			return 0;
		}

		$feed_id = self::existing_feed_id( $kw );

		// Inactive keyword: keep the feed (and its Sources) but stop importing.
		if ( empty( $kw->active ) ) {
			if ( $feed_id > 0 ) {
				EWO_RSS_Feed::set_status( $feed_id, EWO_RSS_Feed::STATUS_DISABLED );
			}
			return $feed_id;
		}

		$domain_id    = $ctx['domain'] ? (int) $ctx['domain']->id : 0;
		$subdomain_id = $ctx['subdomain'] ? (int) $ctx['subdomain']->id : 0;
		$title        = self::feed_title( $ctx );
		$feed_url     = self::build_news_url( $kw->keyword );

		if ( $feed_id <= 0 ) {
			$feed_id = wp_insert_post(
				array(
					'post_type'   => EWO_RSS_Sources::POST_TYPE,
					'post_title'  => $title,
					'post_status' => 'publish',
				),
				true
			);
			if ( is_wp_error( $feed_id ) || ! $feed_id ) {
				return 0;
			}
			$feed_id = (int) $feed_id;
		} elseif ( get_the_title( $feed_id ) !== $title ) {
			wp_update_post(
				array(
					'ID'         => $feed_id,
					'post_title' => $title,
				)
			);
		}

		// Feed config + attribution meta.
		update_post_meta( $feed_id, EWO_RSS_Sources::META_FEED_URL, esc_url_raw( $feed_url ) );
		update_post_meta( $feed_id, EWO_RSS_Sources::META_ENABLED, '1' );
		update_post_meta( $feed_id, EWO_RSS_Sources::META_MAX_ITEMS, self::MAX_ITEMS );
		update_post_meta( $feed_id, self::META_GENERATED_BY, self::GENERATED_BY );
		update_post_meta( $feed_id, self::META_KEYWORD_ID, (int) $kw->id );
		update_post_meta( $feed_id, self::META_SUBDOMAIN_ID, $subdomain_id );
		update_post_meta( $feed_id, self::META_DOMAIN_ID, $domain_id );

		EWO_RSS_Feed::set_status( $feed_id, EWO_RSS_Feed::STATUS_ENABLED );

		// Persist the link back on the keyword row.
		if ( (int) $kw->feed_id !== $feed_id ) {
			EWO_RSS_Taxonomy::update_keyword( (int) $kw->id, array( 'feed_id' => $feed_id ) );
		}

		return $feed_id;
	}

	/**
	 * Re-sync every keyword's feed (used after bulk changes).
	 */
	public static function sync_all() {
		foreach ( EWO_RSS_Taxonomy::get_keywords() as $kw ) {
			self::sync_keyword( $kw );
		}
	}

	/**
	 * Trash a keyword's generated feed.
	 *
	 * @param int $feed_id Feed (source) post ID.
	 */
	public static function delete_feed( $feed_id ) {
		$feed_id = (int) $feed_id;
		if ( $feed_id <= 0 || ! get_post( $feed_id ) ) {
			return;
		}
		EWO_RSS_Feed::set_status( $feed_id, EWO_RSS_Feed::STATUS_DELETED );
		wp_trash_post( $feed_id );
	}

	/**
	 * Locate the existing feed for a keyword: stored feed_id first, then a meta
	 * lookup so we never create a second feed for the same keyword.
	 *
	 * @param object $kw Keyword row.
	 * @return int Feed post ID, or 0.
	 */
	protected static function existing_feed_id( $kw ) {
		$feed_id = (int) $kw->feed_id;
		if ( $feed_id > 0 ) {
			$post = get_post( $feed_id );
			if ( $post && EWO_RSS_Sources::POST_TYPE === $post->post_type && 'trash' !== $post->post_status ) {
				return $feed_id;
			}
		}

		$found = get_posts(
			array(
				'post_type'      => EWO_RSS_Sources::POST_TYPE,
				'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => self::META_KEYWORD_ID, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => (int) $kw->id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);

		return ! empty( $found ) ? (int) $found[0] : 0;
	}

	/**
	 * Human-readable feed title: "Domain › Subdomain: keyword".
	 *
	 * @param array{keyword:?object,subdomain:?object,domain:?object} $ctx Context.
	 * @return string
	 */
	protected static function feed_title( $ctx ) {
		$parts = array();
		if ( $ctx['domain'] ) {
			$parts[] = $ctx['domain']->name;
		}
		if ( $ctx['subdomain'] ) {
			$parts[] = $ctx['subdomain']->name;
		}
		$prefix = $parts ? implode( ' / ', $parts ) . ': ' : '';

		return sprintf( '%s%s', $prefix, $ctx['keyword']->keyword );
	}

	/* ---------------------------------------------------------------------
	 * Fetching → Sources
	 * ------------------------------------------------------------------- */

	/**
	 * Fetch one keyword's feed (syncing it first), capturing Sources.
	 *
	 * @param int $keyword_id Keyword ID.
	 * @return array<string,int> found/created/skipped/errors.
	 */
	public static function fetch_keyword( $keyword_id ) {
		$feed_id = self::sync_keyword( (int) $keyword_id );
		if ( $feed_id <= 0 ) {
			return self::empty_result();
		}
		return self::import_feed( $feed_id );
	}

	/**
	 * Fetch every active keyword feed under a subdomain.
	 *
	 * @param int $subdomain_id Subdomain ID.
	 * @return array<string,int> Aggregate totals.
	 */
	public static function fetch_subdomain( $subdomain_id ) {
		$totals = self::empty_result();
		foreach ( EWO_RSS_Taxonomy::get_keywords( (int) $subdomain_id ) as $kw ) {
			if ( empty( $kw->active ) ) {
				continue;
			}
			self::add_result( $totals, self::fetch_keyword( (int) $kw->id ) );
		}
		return $totals;
	}

	/**
	 * Fetch all active keyword feeds (cron + "Fetch All" button).
	 *
	 * @return array<string,int> Aggregate totals.
	 */
	public static function fetch_all_active() {
		$totals = self::empty_result();
		foreach ( EWO_RSS_Taxonomy::get_active_keywords() as $kw ) {
			self::add_result( $totals, self::fetch_keyword( (int) $kw->id ) );
		}
		return $totals;
	}

	/**
	 * Import a single keyword feed: fetch RSS, extract full articles, store
	 * Sources. Reuses {@see fetch_feed()} and the engine's run logging.
	 *
	 * @param int $feed_id Feed (source) post ID.
	 * @return array<string,int> found/created/skipped/errors.
	 */
	public static function import_feed( $feed_id ) {
		$feed_id = (int) $feed_id;
		$result  = self::empty_result();

		if ( ! EWO_RSS_Feed::import_allowed( $feed_id ) ) {
			return $result;
		}

		$feed_url = (string) get_post_meta( $feed_id, EWO_RSS_Sources::META_FEED_URL, true );
		if ( '' === $feed_url ) {
			EWO_RSS_Ingest::record_run( $feed_id, EWO_RSS_Meta::IMPORTER_KEYWORD, 'failure', array( 'errors' => 1 ), 0, __( 'No feed URL configured.', 'ewo-rss-engine' ) );
			$result['errors'] = 1;
			return $result;
		}

		$keyword_id   = (int) get_post_meta( $feed_id, self::META_KEYWORD_ID, true );
		$subdomain_id = (int) get_post_meta( $feed_id, self::META_SUBDOMAIN_ID, true );
		$domain_id    = (int) get_post_meta( $feed_id, self::META_DOMAIN_ID, true );

		$start       = microtime( true );
		$feed        = fetch_feed( $feed_url );
		$response_ms = (int) round( ( microtime( true ) - $start ) * 1000 );

		if ( is_wp_error( $feed ) ) {
			EWO_RSS_Ingest::record_run(
				$feed_id,
				EWO_RSS_Meta::IMPORTER_KEYWORD,
				'failure',
				array( 'errors' => 1 ),
				$response_ms,
				sprintf( /* translators: %s: error message. */ __( 'Feed error: %s', 'ewo-rss-engine' ), $feed->get_error_message() )
			);
			$result['errors'] = 1;
			return $result;
		}

		$max   = min( self::MAX_ITEMS, (int) $feed->get_item_quantity() );
		$items = $feed->get_items( 0, $max );

		foreach ( $items as $item ) {
			++$result['found'];

			$article_url = (string) $item->get_permalink();
			$hash        = EWO_RSS_Meta::hash( $article_url );

			if ( '' === $hash || EWO_RSS_Source_Store::exists_by_hash( $hash ) ) {
				++$result['skipped'];
				continue;
			}

			$extracted = EWO_RSS_Article_Extractor::extract( $article_url );

			$content = $extracted['ok'] ? $extracted['content'] : self::fallback_content( $item );

			$source_domain = self::source_domain( $item );
			if ( '' === $source_domain ) {
				$source_domain = '' !== $extracted['source_domain'] ? $extracted['source_domain'] : EWO_RSS_Article_Extractor::domain( $article_url );
			}

			$published_at = $item->get_gmdate( 'Y-m-d H:i:s' );

			$source_id = EWO_RSS_Source_Store::insert(
				array(
					'title'         => wp_strip_all_tags( (string) $item->get_title() ),
					'url'           => $article_url,
					'url_hash'      => $hash,
					'source_domain' => $source_domain,
					'domain_id'     => $domain_id,
					'subdomain_id'  => $subdomain_id,
					'keyword_id'    => $keyword_id,
					'feed_id'       => $feed_id,
					'published_at'  => $published_at ? $published_at : '',
					'content'       => $content,
					'status'        => EWO_RSS_Source_Store::STATUS_NEW,
				)
			);

			if ( $source_id > 0 ) {
				++$result['created'];
			} else {
				++$result['errors'];
			}
		}

		update_post_meta( $feed_id, EWO_RSS_Sources::META_LAST_RUN, current_time( 'mysql' ) );

		EWO_RSS_Ingest::record_run(
			$feed_id,
			EWO_RSS_Meta::IMPORTER_KEYWORD,
			'success',
			array(
				'imported'   => $result['created'],
				'duplicates' => $result['skipped'],
				'errors'     => $result['errors'],
			),
			$response_ms,
			__( 'Keyword feed fetched.', 'ewo-rss-engine' )
		);

		return $result;
	}

	/* ---------------------------------------------------------------------
	 * Helpers
	 * ------------------------------------------------------------------- */

	/**
	 * Publisher domain from a Google News item's <source url> element.
	 *
	 * @param SimplePie_Item $item Feed item.
	 * @return string
	 */
	protected static function source_domain( $item ) {
		$tags = $item->get_item_tags( '', 'source' );
		if ( empty( $tags ) || ! is_array( $tags ) ) {
			return '';
		}
		$url = isset( $tags[0]['attribs']['']['url'] ) ? (string) $tags[0]['attribs']['']['url'] : '';

		return '' !== $url ? EWO_RSS_Article_Extractor::domain( $url ) : '';
	}

	/**
	 * Fallback content (RSS description/content) when extraction fails.
	 *
	 * @param SimplePie_Item $item Feed item.
	 * @return string
	 */
	protected static function fallback_content( $item ) {
		$content = (string) $item->get_content();
		if ( '' === $content ) {
			$content = (string) $item->get_description();
		}
		return wp_kses_post( $content );
	}

	/**
	 * Zeroed result array.
	 *
	 * @return array<string,int>
	 */
	protected static function empty_result() {
		return array(
			'found'   => 0,
			'created' => 0,
			'skipped' => 0,
			'errors'  => 0,
		);
	}

	/**
	 * Accumulate one result into a running total.
	 *
	 * @param array<string,int> $totals Running total (by reference).
	 * @param array<string,int> $add    Result to add.
	 */
	protected static function add_result( array &$totals, array $add ) {
		foreach ( array( 'found', 'created', 'skipped', 'errors' ) as $key ) {
			$totals[ $key ] += isset( $add[ $key ] ) ? (int) $add[ $key ] : 0;
		}
	}
}
