<?php
/**
 * Captured-article "Sources" storage (custom table).
 *
 * A Source is one full-article capture produced from a keyword-generated feed.
 * Kept in its own table (not the `ewo_rss_source` feed CPT, which configures a
 * feed) because Sources carry extracted full-text plus domain/subdomain/keyword
 * attribution and a review status workflow that the post-based importer does
 * not model. Deduplicated by the same normalized URL hash the engine already
 * uses ({@see EWO_RSS_Meta::hash()}).
 *
 * @package EWO_RSS_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sources data layer.
 */
class EWO_RSS_Source_Store {
	const SCHEMA_OPTION  = 'ewo_rss_sources_schema';
	const SCHEMA_VERSION = '1';

	const STATUS_NEW      = 'new';
	const STATUS_REVIEWED = 'reviewed';
	const STATUS_IGNORED  = 'ignored';

	/**
	 * Table name.
	 *
	 * @return string
	 */
	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'ewo_rss_article_sources';
	}

	/**
	 * Valid status values.
	 *
	 * @return string[]
	 */
	public static function statuses() {
		return array( self::STATUS_NEW, self::STATUS_REVIEWED, self::STATUS_IGNORED );
	}

	/**
	 * Create/upgrade the table when needed.
	 */
	public static function maybe_install() {
		if ( self::SCHEMA_VERSION === get_option( self::SCHEMA_OPTION ) ) {
			return;
		}

		global $wpdb;
		$table           = self::table();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			title TEXT NOT NULL,
			url TEXT NOT NULL,
			url_hash CHAR(32) NOT NULL DEFAULT '',
			source_domain VARCHAR(191) NOT NULL DEFAULT '',
			domain_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			subdomain_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			keyword_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			feed_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			published_at DATETIME NULL,
			fetched_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			content LONGTEXT NULL,
			status VARCHAR(16) NOT NULL DEFAULT 'new',
			created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY (id),
			KEY url_hash (url_hash),
			KEY domain_id (domain_id),
			KEY subdomain_id (subdomain_id),
			KEY keyword_id (keyword_id),
			KEY status (status),
			KEY fetched_at (fetched_at)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		update_option( self::SCHEMA_OPTION, self::SCHEMA_VERSION, false );
	}

	/**
	 * Whether a Source already exists for a normalized URL hash.
	 *
	 * @param string $url_hash Normalized URL hash.
	 * @return bool
	 */
	public static function exists_by_hash( $url_hash ) {
		global $wpdb;
		self::maybe_install();
		$url_hash = (string) $url_hash;
		if ( '' === $url_hash ) {
			return false;
		}
		$table = self::table();
		$found = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE url_hash = %s LIMIT 1", $url_hash ) ); // phpcs:ignore WordPress.DB

		return ! empty( $found );
	}

	/**
	 * Insert a Source row.
	 *
	 * @param array<string,mixed> $data Source fields.
	 * @return int New ID, or 0.
	 */
	public static function insert( array $data ) {
		global $wpdb;
		self::maybe_install();

		$status = isset( $data['status'] ) ? (string) $data['status'] : self::STATUS_NEW;
		if ( ! in_array( $status, self::statuses(), true ) ) {
			$status = self::STATUS_NEW;
		}

		$published_at = isset( $data['published_at'] ) && '' !== $data['published_at']
			? (string) $data['published_at']
			: null;

		$row = array(
			'title'         => isset( $data['title'] ) ? sanitize_text_field( (string) $data['title'] ) : '',
			'url'           => isset( $data['url'] ) ? esc_url_raw( (string) $data['url'] ) : '',
			'url_hash'      => isset( $data['url_hash'] ) ? substr( (string) $data['url_hash'], 0, 32 ) : '',
			'source_domain' => isset( $data['source_domain'] ) ? sanitize_text_field( (string) $data['source_domain'] ) : '',
			'domain_id'     => isset( $data['domain_id'] ) ? (int) $data['domain_id'] : 0,
			'subdomain_id'  => isset( $data['subdomain_id'] ) ? (int) $data['subdomain_id'] : 0,
			'keyword_id'    => isset( $data['keyword_id'] ) ? (int) $data['keyword_id'] : 0,
			'feed_id'       => isset( $data['feed_id'] ) ? (int) $data['feed_id'] : 0,
			'published_at'  => $published_at,
			'fetched_at'    => current_time( 'mysql', true ),
			'content'       => isset( $data['content'] ) ? wp_kses_post( (string) $data['content'] ) : '',
			'status'        => $status,
			'created_at'    => current_time( 'mysql', true ),
		);

		$formats = array( '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s' );

		$wpdb->insert( self::table(), $row, $formats ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		return (int) $wpdb->insert_id;
	}

	/**
	 * Update a Source's review status.
	 *
	 * @param int    $id     Source ID.
	 * @param string $status One of the STATUS_* constants.
	 * @return bool
	 */
	public static function set_status( $id, $status ) {
		global $wpdb;
		self::maybe_install();
		$id = (int) $id;
		if ( $id <= 0 || ! in_array( $status, self::statuses(), true ) ) {
			return false;
		}

		return false !== $wpdb->update( self::table(), array( 'status' => $status ), array( 'id' => $id ), array( '%s' ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	/**
	 * Valid sortable columns.
	 *
	 * @return string[]
	 */
	public static function sortable_columns() {
		return array( 'published_at', 'fetched_at', 'source_domain' );
	}

	/**
	 * Query Sources with optional filters.
	 *
	 * @param array<string,mixed> $args Filters: domain_id, subdomain_id,
	 *                                   keyword_id, status, orderby, order,
	 *                                   limit, offset.
	 * @return array<int,object>
	 */
	public static function query( array $args = array() ) {
		global $wpdb;
		self::maybe_install();
		$table = self::table();

		$where  = array( '1=1' );
		$params = array();

		foreach ( array( 'domain_id', 'subdomain_id', 'keyword_id', 'feed_id' ) as $col ) {
			if ( ! empty( $args[ $col ] ) ) {
				$where[]  = "$col = %d";
				$params[] = (int) $args[ $col ];
			}
		}

		if ( ! empty( $args['status'] ) && in_array( $args['status'], self::statuses(), true ) ) {
			$where[]  = 'status = %s';
			$params[] = (string) $args['status'];
		}

		$limit  = isset( $args['limit'] ) ? max( 1, (int) $args['limit'] ) : 50;
		$offset = isset( $args['offset'] ) ? max( 0, (int) $args['offset'] ) : 0;

		// Sorting.
		$orderby = isset( $args['orderby'] ) && in_array( $args['orderby'], self::sortable_columns(), true )
			? $args['orderby']
			: 'fetched_at';
		$order   = isset( $args['order'] ) && 'ASC' === strtoupper( (string) $args['order'] ) ? 'ASC' : 'DESC';

		$sql      = "SELECT * FROM $table WHERE " . implode( ' AND ', $where ) . " ORDER BY $orderby $order, id DESC LIMIT %d OFFSET %d";
		$params[] = $limit;
		$params[] = $offset;

		return (array) $wpdb->get_results( $wpdb->prepare( $sql, $params ) ); // phpcs:ignore WordPress.DB
	}

	/**
	 * Count Sources matching optional filters.
	 *
	 * @param array<string,mixed> $args Filters: domain_id, subdomain_id, keyword_id, status.
	 * @return int
	 */
	public static function count( array $args = array() ) {
		global $wpdb;
		self::maybe_install();
		$table = self::table();

		$where  = array( '1=1' );
		$params = array();

		foreach ( array( 'domain_id', 'subdomain_id', 'keyword_id', 'feed_id' ) as $col ) {
			if ( ! empty( $args[ $col ] ) ) {
				$where[]  = "$col = %d";
				$params[] = (int) $args[ $col ];
			}
		}
		if ( ! empty( $args['status'] ) && in_array( $args['status'], self::statuses(), true ) ) {
			$where[]  = 'status = %s';
			$params[] = (string) $args['status'];
		}

		$sql = "SELECT COUNT(*) FROM $table WHERE " . implode( ' AND ', $where );

		if ( empty( $params ) ) {
			return (int) $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB
		}

		return (int) $wpdb->get_var( $wpdb->prepare( $sql, $params ) ); // phpcs:ignore WordPress.DB
	}
}
