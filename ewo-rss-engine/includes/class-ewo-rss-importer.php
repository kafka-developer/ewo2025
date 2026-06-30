<?php
/**
 * Native RSS importer: feed → posts, routed through the canonical ingest pipeline.
 *
 * @package EWO_RSS_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fetches feeds and creates posts via {@see EWO_RSS_Ingest}.
 */
class EWO_RSS_Importer {

	/**
	 * Sources component.
	 *
	 * @var EWO_RSS_Sources
	 */
	protected $sources;

	/**
	 * Thumbnails component.
	 *
	 * @var EWO_RSS_Thumbnails
	 */
	protected $thumbnails;

	/**
	 * Constructor.
	 *
	 * @param EWO_RSS_Sources         $sources    Sources component.
	 * @param EWO_RSS_Thumbnails|null $thumbnails Thumbnails component.
	 */
	public function __construct( EWO_RSS_Sources $sources, EWO_RSS_Thumbnails $thumbnails = null ) {
		$this->sources    = $sources;
		$this->thumbnails = $thumbnails instanceof EWO_RSS_Thumbnails ? $thumbnails : new EWO_RSS_Thumbnails();
	}

	/**
	 * Import every active source.
	 *
	 * @return array<string,int> Aggregate totals.
	 */
	public function import_all() {
		$totals = array(
			'sources' => 0,
			'found'   => 0,
			'created' => 0,
			'skipped' => 0,
			'errors'  => 0,
		);

		foreach ( $this->sources->get_active_sources() as $source_id ) {
			// Keyword-generated feeds capture Sources on their own 30-minute
			// schedule (see EWO_RSS_Keyword_Feeds); skip them in the hourly run.
			if ( EWO_RSS_Keyword_Feeds::is_keyword_feed( $source_id ) ) {
				continue;
			}

			$result = $this->import_source( $source_id );

			++$totals['sources'];
			$totals['found']   += $result['found'];
			$totals['created'] += $result['created'];
			$totals['skipped'] += $result['skipped'];
			$totals['errors']  += $result['errors'];
		}

		return $totals;
	}

	/**
	 * Import a single source.
	 *
	 * @param int $source_id Source post ID.
	 * @return array<string,int> Per-source totals.
	 */
	public function import_source( $source_id ) {
		// Keyword-generated feeds extract full-article Sources instead of
		// creating posts; route them through the keyword pipeline.
		if ( EWO_RSS_Keyword_Feeds::is_keyword_feed( $source_id ) ) {
			return EWO_RSS_Keyword_Feeds::import_feed( $source_id );
		}

		$result   = array(
			'found'   => 0,
			'created' => 0,
			'skipped' => 0,
			'errors'  => 0,
		);
		$settings = $this->sources->get_settings( $source_id );

		if ( '' === $settings['feed_url'] ) {
			EWO_RSS_Ingest::record_run( $source_id, EWO_RSS_Meta::IMPORTER_NATIVE, 'failure', array( 'errors' => 1 ), 0, __( 'No feed URL configured.', 'ewo-rss-engine' ) );
			$result['errors'] = 1;
			return $result;
		}

		// Disabled/Deleted feeds do not fetch.
		if ( ! EWO_RSS_Feed::import_allowed( $source_id ) ) {
			return $result;
		}

		$start       = microtime( true );
		$feed        = fetch_feed( $settings['feed_url'] );
		$response_ms = (int) round( ( microtime( true ) - $start ) * 1000 );

		if ( is_wp_error( $feed ) ) {
			EWO_RSS_Ingest::record_run(
				$source_id,
				EWO_RSS_Meta::IMPORTER_NATIVE,
				'failure',
				array( 'errors' => 1 ),
				$response_ms,
				sprintf(
					/* translators: %s: error message. */
					__( 'Feed error: %s', 'ewo-rss-engine' ),
					$feed->get_error_message()
				)
			);
			$result['errors'] = 1;
			return $result;
		}

		$max   = min( $settings['max_items'], (int) $feed->get_item_quantity() );
		$items = $feed->get_items( 0, $max );

		foreach ( $items as $item ) {
			++$result['found'];

			$article_url = (string) $item->get_permalink();
			$guid        = (string) $item->get_id();
			if ( '' === $guid ) {
				$guid = $article_url;
			}

			if ( ! EWO_RSS_Ingest::should_import( $article_url, $source_id ) ) {
				++$result['skipped'];
				continue;
			}

			$post_id = $this->create_post( $item, $settings );
			if ( is_wp_error( $post_id ) || 0 === $post_id ) {
				++$result['errors'];
				continue;
			}

			$content = (string) $item->get_content();
			if ( '' === $content ) {
				$content = (string) $item->get_description();
			}

			$status = EWO_RSS_Ingest::finalize(
				$post_id,
				array(
					'feed_id'     => (int) $settings['id'],
					'feed_name'   => $settings['name'],
					'feed_url'    => $settings['feed_url'],
					'article_url' => $article_url,
					'importer'    => EWO_RSS_Meta::IMPORTER_NATIVE,
					'guid'        => $guid,
					'imported_at' => $item->get_gmdate( 'Y-m-d H:i:s' ) ? $item->get_gmdate( 'Y-m-d H:i:s' ) : '',
					'content'     => $content,
				)
			);

			if ( 'imported' === $status ) {
				++$result['created'];
				$image_url = $this->thumbnails->get_image_url( $item );
				if ( '' !== $image_url ) {
					$this->thumbnails->set_featured_image( $post_id, $image_url );
				}
			} elseif ( 'duplicate' === $status ) {
				++$result['skipped'];
			} else {
				++$result['errors'];
			}
		}

		$this->sources->mark_run( $source_id );

		EWO_RSS_Ingest::record_run(
			$source_id,
			EWO_RSS_Meta::IMPORTER_NATIVE,
			'success',
			array(
				'imported'   => $result['created'],
				'duplicates' => $result['skipped'],
				'errors'     => $result['errors'],
			),
			$response_ms,
			__( 'Import complete.', 'ewo-rss-engine' )
		);

		return $result;
	}

	/**
	 * Insert a post from a feed item. Canonical attribution is stamped later
	 * by {@see EWO_RSS_Ingest::finalize()}.
	 *
	 * @param SimplePie_Item      $item     Feed item.
	 * @param array<string,mixed> $settings Source settings.
	 * @return int|WP_Error New post ID, or error.
	 */
	protected function create_post( $item, $settings ) {
		$content = (string) $item->get_content();
		if ( '' === $content ) {
			$content = (string) $item->get_description();
		}

		$postarr = array(
			'post_title'   => wp_strip_all_tags( (string) $item->get_title() ),
			'post_content' => $content,
			'post_excerpt' => wp_strip_all_tags( (string) $item->get_description() ),
			'post_status'  => $settings['post_status'],
			'post_type'    => 'post',
		);

		$gmt_date = $item->get_gmdate( 'Y-m-d H:i:s' );
		if ( $gmt_date ) {
			$postarr['post_date_gmt'] = $gmt_date;
			$postarr['post_date']     = get_date_from_gmt( $gmt_date );
		}

		if ( $settings['category'] > 0 ) {
			$postarr['post_category'] = array( $settings['category'] );
		}

		$post_id = wp_insert_post( wp_slash( $postarr ), true );

		return is_wp_error( $post_id ) ? $post_id : (int) $post_id;
	}
}
