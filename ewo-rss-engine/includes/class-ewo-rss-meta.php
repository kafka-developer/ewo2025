<?php
/**
 * Canonical metadata schema for ingested content.
 *
 * Single source of truth for every `_ewo_rss_*` post-meta key, URL
 * normalization, duplicate hashing, and content-flag detection. All importers,
 * the front end, and downstream engines read/write through this class so the
 * data model stays consistent and migratable.
 *
 * @package EWO_RSS_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Canonical ingestion metadata.
 */
class EWO_RSS_Meta {

	/* Source attribution (item 2). */
	const FEED_ID     = '_ewo_rss_source_id';   // Source feed ID (kept; already canonical).
	const FEED_NAME   = '_ewo_rss_feed_name';   // Source feed name (cached).
	const FEED_URL    = '_ewo_rss_feed_url';    // Source feed (RSS) URL.
	const ARTICLE_URL = '_ewo_rss_article_url'; // Original article URL.
	const IMPORTED_AT = '_ewo_rss_imported_at'; // Import timestamp (GMT mysql).
	const IMPORTER    = '_ewo_rss_importer';    // Importer type.
	const GUID        = '_ewo_rss_guid';        // Feed item GUID.

	/* Deduplication (items 6, 7). */
	const NORMALIZED_URL = '_ewo_rss_normalized_url';
	const DUPLICATE_HASH = '_ewo_rss_duplicate_hash';

	/* Content flags detected at import (item 9). */
	const IS_SUBSCRIBER_ONLY = '_ewo_rss_is_subscriber_only';
	const IS_PAYWALLED       = '_ewo_rss_is_paywalled';
	const IS_TRUNCATED       = '_ewo_rss_is_truncated';

	/* Extensible bag for future engines (item 13). */
	const EXT = '_ewo_rss_ext';

	/* Importer types (item 7). */
	const IMPORTER_NATIVE = 'ewo_native';
	const IMPORTER_FEEDZY = 'feedzy';

	/* Legacy keys migrated away from (item 1). */
	const LEGACY_ARTICLE_URL_KEYS = array( '_ewo_rss_source_url', 'ewo_rss_source_url' );

	/**
	 * Persist full source attribution + dedup fields + flags on an item.
	 *
	 * @param int                 $post_id Imported post ID.
	 * @param array<string,mixed> $data    Attribution fields.
	 */
	public static function stamp( $post_id, array $data ) {
		$post_id = (int) $post_id;
		if ( $post_id <= 0 ) {
			return;
		}

		$map = array(
			self::FEED_ID     => isset( $data['feed_id'] ) ? (int) $data['feed_id'] : 0,
			self::FEED_NAME   => isset( $data['feed_name'] ) ? sanitize_text_field( (string) $data['feed_name'] ) : '',
			self::FEED_URL    => isset( $data['feed_url'] ) ? esc_url_raw( (string) $data['feed_url'] ) : '',
			self::ARTICLE_URL => isset( $data['article_url'] ) ? esc_url_raw( (string) $data['article_url'] ) : '',
			self::IMPORTER    => isset( $data['importer'] ) ? sanitize_key( (string) $data['importer'] ) : '',
			self::GUID        => isset( $data['guid'] ) ? (string) $data['guid'] : '',
		);

		foreach ( $map as $key => $value ) {
			if ( '' !== $value && 0 !== $value ) {
				update_post_meta( $post_id, $key, $value );
			}
		}

		$imported_at = isset( $data['imported_at'] ) && '' !== $data['imported_at']
			? (string) $data['imported_at']
			: get_post_time( 'Y-m-d H:i:s', true, $post_id );
		update_post_meta( $post_id, self::IMPORTED_AT, $imported_at );

		$article_url = isset( $data['article_url'] ) ? (string) $data['article_url'] : '';
		if ( '' !== $article_url ) {
			$normalized = self::normalize_url( $article_url );
			update_post_meta( $post_id, self::NORMALIZED_URL, $normalized );
			update_post_meta( $post_id, self::DUPLICATE_HASH, self::hash( $article_url ) );
		}
	}

	/**
	 * Detect and store content flags (subscriber/paywall/truncation).
	 *
	 * @param int    $post_id     Post ID.
	 * @param string $content     Raw item content/HTML.
	 * @param string $article_url Article URL (host hints).
	 * @return array{is_subscriber_only:bool,is_paywalled:bool,is_truncated:bool}
	 */
	public static function stamp_flags( $post_id, $content, $article_url = '' ) {
		$flags = self::detect_flags( $content, $article_url );

		update_post_meta( $post_id, self::IS_SUBSCRIBER_ONLY, $flags['is_subscriber_only'] ? '1' : '0' );
		update_post_meta( $post_id, self::IS_PAYWALLED, $flags['is_paywalled'] ? '1' : '0' );
		update_post_meta( $post_id, self::IS_TRUNCATED, $flags['is_truncated'] ? '1' : '0' );

		return $flags;
	}

	/**
	 * Heuristic content-flag detection.
	 *
	 * @param string $content     Content.
	 * @param string $article_url Article URL.
	 * @return array{is_subscriber_only:bool,is_paywalled:bool,is_truncated:bool}
	 */
	public static function detect_flags( $content, $article_url = '' ) {
		$text  = wp_strip_all_tags( strip_shortcodes( (string) $content ) );
		$text  = trim( preg_replace( '/\s+/', ' ', $text ) );
		$words = str_word_count( $text );

		$subscriber_markers = array(
			'this post is for paid subscribers',
			'this post is for paying subscribers',
			'for paid subscribers',
			'subscribe to read',
			'subscribe to keep reading',
			'become a paid subscriber',
			'paid subscribers only',
		);
		$paywall_markers    = array( 'paywall', 'subscribe to continue', 'subscribers only', 'unlock this' );
		$truncation_markers = array( 'read more', 'continue reading', 'keep reading', '[…]', '…' );

		$haystack = strtolower( $text );

		$is_subscriber_only = false;
		foreach ( $subscriber_markers as $needle ) {
			if ( false !== strpos( $haystack, $needle ) ) {
				$is_subscriber_only = true;
				break;
			}
		}

		$is_truncated = $words > 0 && $words <= 80;
		foreach ( $truncation_markers as $needle ) {
			if ( false !== strpos( $haystack, strtolower( $needle ) ) ) {
				$is_truncated = true;
				break;
			}
		}

		$is_paywalled = $is_subscriber_only;
		foreach ( $paywall_markers as $needle ) {
			if ( false !== strpos( $haystack, $needle ) ) {
				$is_paywalled = true;
				break;
			}
		}

		// Substack truncated previews are effectively subscriber-gated.
		if ( $is_truncated && '' !== $article_url && false !== stripos( $article_url, 'substack.com' ) ) {
			$is_subscriber_only = true;
			$is_paywalled       = true;
		}

		return array(
			'is_subscriber_only' => $is_subscriber_only,
			'is_paywalled'       => $is_paywalled,
			'is_truncated'       => $is_truncated,
		);
	}

	/**
	 * Normalize a URL for duplicate comparison: lower-case host, drop scheme,
	 * `www.`, fragments, trailing slash, and tracking/UTM query parameters;
	 * remaining params are sorted for stability.
	 *
	 * @param string $url URL.
	 * @return string
	 */
	public static function normalize_url( $url ) {
		$url = trim( (string) $url );
		if ( '' === $url ) {
			return '';
		}

		$parts = wp_parse_url( $url );

		if ( empty( $parts['host'] ) ) {
			$bare = preg_replace( '#^https?://#i', '', strtolower( $url ) );
			$bare = preg_replace( '#^www\.#', '', (string) $bare );
			$bare = strtok( (string) $bare, '#' );
			return rtrim( (string) $bare, '/' );
		}

		$host = preg_replace( '#^www\.#', '', strtolower( $parts['host'] ) );
		$path = isset( $parts['path'] ) ? rtrim( $parts['path'], '/' ) : '';

		$query = '';
		if ( ! empty( $parts['query'] ) ) {
			parse_str( $parts['query'], $params );
			foreach ( array_keys( $params ) as $key ) {
				if ( self::is_tracking_param( $key ) ) {
					unset( $params[ $key ] );
				}
			}
			ksort( $params );
			if ( ! empty( $params ) ) {
				$query = '?' . http_build_query( $params );
			}
		}

		return $host . $path . $query;
	}

	/**
	 * Stable duplicate hash for a URL.
	 *
	 * @param string $url URL.
	 * @return string md5 hash, or empty string.
	 */
	public static function hash( $url ) {
		$normalized = self::normalize_url( $url );

		return '' !== $normalized ? md5( $normalized ) : '';
	}

	/**
	 * Whether a query parameter is a tracking/analytics parameter.
	 *
	 * @param string $key Parameter name.
	 * @return bool
	 */
	public static function is_tracking_param( $key ) {
		$key = strtolower( $key );

		if ( 0 === strpos( $key, 'utm_' ) ) {
			return true;
		}

		$tracking = array(
			'gclid',
			'fbclid',
			'mc_cid',
			'mc_eid',
			'igshid',
			'yclid',
			'msclkid',
			'_hsenc',
			'_hsmi',
			'ref',
			'ref_src',
			'source',
			'cmpid',
			'spm',
		);

		return in_array( $key, $tracking, true );
	}

	/**
	 * Read the canonical article URL for a post, falling back to legacy keys.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	public static function article_url( $post_id ) {
		$url = (string) get_post_meta( $post_id, self::ARTICLE_URL, true );
		if ( '' !== $url ) {
			return $url;
		}

		foreach ( self::LEGACY_ARTICLE_URL_KEYS as $key ) {
			$legacy = (string) get_post_meta( $post_id, $key, true );
			if ( '' !== $legacy ) {
				return $legacy;
			}
		}

		// Last resort: a Feedzy import.
		return (string) get_post_meta( $post_id, 'feedzy_item_url', true );
	}

	/**
	 * Store an extensible metadata value (future engines).
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key     Sub-key (e.g. 'ai_rank', 'domain', 'smart_score').
	 * @param mixed  $value   Value.
	 */
	public static function set_ext( $post_id, $key, $value ) {
		$ext = self::get_ext( $post_id );
		$ext[ sanitize_key( $key ) ] = $value;
		update_post_meta( $post_id, self::EXT, wp_json_encode( $ext ) );
	}

	/**
	 * Read the extensible metadata bag.
	 *
	 * @param int $post_id Post ID.
	 * @return array<string,mixed>
	 */
	public static function get_ext( $post_id ) {
		$raw = get_post_meta( $post_id, self::EXT, true );
		if ( is_array( $raw ) ) {
			return $raw;
		}
		$decoded = json_decode( (string) $raw, true );

		return is_array( $decoded ) ? $decoded : array();
	}
}
