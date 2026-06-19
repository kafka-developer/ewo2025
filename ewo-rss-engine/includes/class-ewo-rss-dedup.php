<?php
/**
 * Deduplication service (first-class, cross-importer).
 *
 * Identity is the normalized article URL hash from {@see EWO_RSS_Meta}. Works
 * across every importer (Feedzy, native, future) because the hash is computed
 * from the article link, not an importer-specific GUID.
 *
 * @package EWO_RSS_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Duplicate detection and resolution.
 */
class EWO_RSS_Dedup {

	/**
	 * Find an existing imported post matching a URL's duplicate hash.
	 *
	 * @param string $article_url Article URL.
	 * @param int    $exclude     Post ID to exclude.
	 * @return int Matching post ID, or 0.
	 */
	public static function existing_post( $article_url, $exclude = 0 ) {
		$hash = EWO_RSS_Meta::hash( $article_url );
		if ( '' === $hash ) {
			return 0;
		}

		$found = get_posts(
			array(
				'post_type'      => 'any',
				'post_status'    => array( 'publish', 'pending', 'draft', 'future', 'private' ),
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'post__not_in'   => array( (int) $exclude ),
				'meta_key'       => EWO_RSS_Meta::DUPLICATE_HASH,
				'meta_value'     => $hash,
			)
		);

		return ! empty( $found ) ? (int) $found[0] : 0;
	}

	/**
	 * All imported post IDs (any importer).
	 *
	 * @return int[]
	 */
	public static function imported_post_ids() {
		return get_posts(
			array(
				'post_type'      => 'any',
				'post_status'    => array( 'publish', 'pending', 'draft', 'future', 'private' ),
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'relation' => 'OR',
					array(
						'key'     => EWO_RSS_Meta::ARTICLE_URL,
						'compare' => 'EXISTS',
					),
					array(
						'key'     => '_ewo_rss_source_url',
						'compare' => 'EXISTS',
					),
					array(
						'key'     => 'feedzy_item_url',
						'compare' => 'EXISTS',
					),
				),
			)
		);
	}

	/**
	 * Article URL for a post via canonical-then-legacy lookup.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	protected static function url_for( $post_id ) {
		return EWO_RSS_Meta::article_url( $post_id );
	}

	/**
	 * Build duplicate groups across the whole site (2+ posts per article).
	 *
	 * @return array<int,array<string,mixed>> Each: hash, canonical_id, members[].
	 */
	public static function groups() {
		$buckets = array();

		foreach ( self::imported_post_ids() as $post_id ) {
			$hash = EWO_RSS_Meta::hash( self::url_for( $post_id ) );
			if ( '' === $hash ) {
				continue;
			}
			$buckets[ $hash ][] = (int) $post_id;
		}

		$groups = array();
		foreach ( $buckets as $hash => $members ) {
			if ( count( $members ) < 2 ) {
				continue;
			}
			sort( $members );
			$canonical = self::choose_canonical( $members );

			$detail = array();
			foreach ( $members as $member ) {
				$detail[] = array(
					'id'        => $member,
					'title'     => get_the_title( $member ),
					'importer'  => (string) get_post_meta( $member, EWO_RSS_Meta::IMPORTER, true ),
					'feed_id'   => (int) get_post_meta( $member, EWO_RSS_Meta::FEED_ID, true ),
					'feed_name' => (string) get_post_meta( $member, EWO_RSS_Meta::FEED_NAME, true ),
					'status'    => get_post_status( $member ),
					'canonical' => ( $member === $canonical ),
				);
			}

			$groups[] = array(
				'hash'         => $hash,
				'canonical_id' => $canonical,
				'members'      => $detail,
			);
		}

		return $groups;
	}

	/**
	 * Choose which post to keep: prefer native imports, then the oldest.
	 *
	 * @param int[] $members Post IDs.
	 * @return int
	 */
	public static function choose_canonical( $members ) {
		sort( $members );

		foreach ( $members as $member ) {
			$importer = (string) get_post_meta( $member, EWO_RSS_Meta::IMPORTER, true );
			if ( EWO_RSS_Meta::IMPORTER_NATIVE === $importer || metadata_exists( 'post', $member, '_ewo_rss_guid' ) ) {
				return (int) $member;
			}
		}

		return (int) $members[0];
	}

	/**
	 * Resolve duplicates site-wide, trashing all but the canonical of each group.
	 *
	 * @param bool $dry_run When true, only report.
	 * @return int[] IDs trashed (or that would be).
	 */
	public static function resolve_all( $dry_run = false ) {
		$removed = array();

		foreach ( self::groups() as $group ) {
			foreach ( $group['members'] as $member ) {
				if ( $member['id'] !== $group['canonical_id'] ) {
					$removed[] = (int) $member['id'];
				}
			}
		}

		if ( ! $dry_run ) {
			foreach ( $removed as $post_id ) {
				wp_trash_post( $post_id );
			}
		}

		return $removed;
	}

	/**
	 * Merge a duplicate group to a chosen canonical post (trash the rest).
	 *
	 * @param int   $keep_id    Canonical post to keep.
	 * @param int[] $remove_ids Posts to trash.
	 * @return int Number trashed.
	 */
	public static function merge( $keep_id, $remove_ids ) {
		$count = 0;
		foreach ( (array) $remove_ids as $rid ) {
			$rid = (int) $rid;
			if ( $rid && $rid !== (int) $keep_id && wp_trash_post( $rid ) ) {
				++$count;
			}
		}

		return $count;
	}
}
