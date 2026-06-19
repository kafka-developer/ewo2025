<?php
/**
 * Ingestion pipeline — the canonical abstraction layer.
 *
 * Every importer (Feedzy compatibility layer, native, future) funnels items
 * through here, so downstream systems (Strategic Domains, Smart Feed, scoring,
 * prediction engines) depend only on the EWO RSS Engine data contract — never
 * on a specific importer. This is what makes Feedzy removable.
 *
 *   RSS Feed → Importer → EWO_RSS_Ingest → {normalize, dedup, attribute,
 *   detect flags, audit} → canonical post → downstream engines.
 *
 * @package EWO_RSS_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Canonical ingest pipeline.
 */
class EWO_RSS_Ingest {

	/**
	 * Pre-import gate: should this item be imported at all?
	 *
	 * @param string $article_url Article URL.
	 * @param int    $feed_id     Source feed ID.
	 * @return bool
	 */
	public static function should_import( $article_url, $feed_id ) {
		if ( (int) $feed_id > 0 && ! EWO_RSS_Feed::import_allowed( $feed_id ) ) {
			return false;
		}
		if ( '' !== (string) $article_url && EWO_RSS_Dedup::existing_post( $article_url, 0 ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Finalize an imported post: enforce feed status + dedup, then stamp
	 * canonical attribution and content flags.
	 *
	 * @param int                 $post_id Newly imported post ID.
	 * @param array<string,mixed> $item    Normalized item fields.
	 * @return string 'imported' | 'duplicate' | 'rejected_disabled' | 'invalid'
	 */
	public static function finalize( $post_id, array $item ) {
		$post_id = (int) $post_id;
		if ( $post_id <= 0 ) {
			return 'invalid';
		}

		$feed_id     = isset( $item['feed_id'] ) ? (int) $item['feed_id'] : 0;
		$importer    = isset( $item['importer'] ) ? (string) $item['importer'] : '';
		$article_url = isset( $item['article_url'] ) ? (string) $item['article_url'] : '';

		if ( $feed_id > 0 && ! EWO_RSS_Feed::import_allowed( $feed_id ) ) {
			wp_trash_post( $post_id );
			return 'rejected_disabled';
		}

		if ( '' !== $article_url && EWO_RSS_Dedup::existing_post( $article_url, $post_id ) ) {
			wp_trash_post( $post_id );
			return 'duplicate';
		}

		EWO_RSS_Meta::stamp(
			$post_id,
			array(
				'feed_id'     => $feed_id,
				'feed_name'   => isset( $item['feed_name'] ) && '' !== $item['feed_name'] ? $item['feed_name'] : EWO_RSS_Feed::name( $feed_id ),
				'feed_url'    => isset( $item['feed_url'] ) && '' !== $item['feed_url'] ? $item['feed_url'] : EWO_RSS_Feed::url( $feed_id ),
				'article_url' => $article_url,
				'importer'    => $importer,
				'guid'        => isset( $item['guid'] ) ? (string) $item['guid'] : '',
				'imported_at' => isset( $item['imported_at'] ) ? (string) $item['imported_at'] : '',
			)
		);

		$content = isset( $item['content'] ) ? (string) $item['content'] : (string) get_post_field( 'post_content', $post_id );
		EWO_RSS_Meta::stamp_flags( $post_id, $content, $article_url );

		return 'imported';
	}

	/**
	 * Record a completed import run (audit log + feed health + totals).
	 *
	 * @param int                 $feed_id     Feed ID.
	 * @param string              $importer    Importer type.
	 * @param string              $result      'success' | 'failure' | 'partial'.
	 * @param array<string,int>   $counts      imported/duplicates/errors.
	 * @param int                 $response_ms Fetch time (ms).
	 * @param string              $message     Optional message.
	 */
	public static function record_run( $feed_id, $importer, $result, array $counts = array(), $response_ms = 0, $message = '' ) {
		$imported   = isset( $counts['imported'] ) ? (int) $counts['imported'] : 0;
		$duplicates = isset( $counts['duplicates'] ) ? (int) $counts['duplicates'] : 0;
		$errors     = isset( $counts['errors'] ) ? (int) $counts['errors'] : 0;

		EWO_RSS_Audit_Log::record(
			array(
				'feed_id'         => $feed_id,
				'feed_name'       => EWO_RSS_Feed::name( $feed_id ),
				'importer'        => $importer,
				'result'          => $result,
				'imported_count'  => $imported,
				'duplicate_count' => $duplicates,
				'error_count'     => $errors,
				'response_ms'     => $response_ms,
				'message'         => $message,
			)
		);

		if ( 'failure' === $result ) {
			EWO_RSS_Feed::record_failure( $feed_id, $message, $response_ms );
		} else {
			EWO_RSS_Feed::record_success( $feed_id, $imported, $response_ms );
		}
	}
}
