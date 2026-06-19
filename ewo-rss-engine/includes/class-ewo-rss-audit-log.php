<?php
/**
 * Persistent import audit log (custom table).
 *
 * One row per import run, traceable per feed and importer. Chosen over the
 * capped-option log for scalability and queryable reporting.
 *
 * @package EWO_RSS_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Import-run audit log.
 */
class EWO_RSS_Audit_Log {
	const SCHEMA_OPTION  = 'ewo_rss_audit_schema';
	const SCHEMA_VERSION = '1';

	/**
	 * Fully-qualified table name.
	 *
	 * @return string
	 */
	public static function table() {
		global $wpdb;

		return $wpdb->prefix . 'ewo_rss_import_log';
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
			feed_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			feed_name VARCHAR(255) NOT NULL DEFAULT '',
			importer VARCHAR(32) NOT NULL DEFAULT '',
			result VARCHAR(16) NOT NULL DEFAULT '',
			imported_count INT NOT NULL DEFAULT 0,
			duplicate_count INT NOT NULL DEFAULT 0,
			error_count INT NOT NULL DEFAULT 0,
			response_ms INT NOT NULL DEFAULT 0,
			message TEXT NULL,
			created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY (id),
			KEY feed_id (feed_id),
			KEY importer (importer),
			KEY created_at (created_at)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		update_option( self::SCHEMA_OPTION, self::SCHEMA_VERSION, false );
	}

	/**
	 * Record an import run.
	 *
	 * @param array<string,mixed> $run Run fields.
	 * @return int Inserted row ID.
	 */
	public static function record( array $run ) {
		global $wpdb;
		self::maybe_install();

		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			self::table(),
			array(
				'feed_id'         => isset( $run['feed_id'] ) ? (int) $run['feed_id'] : 0,
				'feed_name'       => isset( $run['feed_name'] ) ? substr( (string) $run['feed_name'], 0, 255 ) : '',
				'importer'        => isset( $run['importer'] ) ? sanitize_key( (string) $run['importer'] ) : '',
				'result'          => isset( $run['result'] ) ? sanitize_key( (string) $run['result'] ) : '',
				'imported_count'  => isset( $run['imported_count'] ) ? (int) $run['imported_count'] : 0,
				'duplicate_count' => isset( $run['duplicate_count'] ) ? (int) $run['duplicate_count'] : 0,
				'error_count'     => isset( $run['error_count'] ) ? (int) $run['error_count'] : 0,
				'response_ms'     => isset( $run['response_ms'] ) ? (int) $run['response_ms'] : 0,
				'message'         => isset( $run['message'] ) ? (string) $run['message'] : '',
				'created_at'      => current_time( 'mysql', true ),
			),
			array( '%d', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%s', '%s' )
		);

		return (int) $wpdb->insert_id;
	}

	/**
	 * Recent runs.
	 *
	 * @param int $limit   Max rows.
	 * @param int $feed_id Optional feed filter.
	 * @return array<int,object>
	 */
	public static function recent( $limit = 50, $feed_id = 0 ) {
		global $wpdb;
		self::maybe_install();
		$table = self::table();
		$limit = max( 1, (int) $limit );

		if ( $feed_id > 0 ) {
			return $wpdb->get_results( // phpcs:ignore WordPress.DB
				$wpdb->prepare( "SELECT * FROM $table WHERE feed_id = %d ORDER BY id DESC LIMIT %d", (int) $feed_id, $limit )
			);
		}

		return $wpdb->get_results( // phpcs:ignore WordPress.DB
			$wpdb->prepare( "SELECT * FROM $table ORDER BY id DESC LIMIT %d", $limit )
		);
	}

	/**
	 * Aggregate run counts per importer.
	 *
	 * @return array<string,array<string,int>>
	 */
	public static function importer_stats() {
		global $wpdb;
		self::maybe_install();
		$table = self::table();

		$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB
			"SELECT importer, COUNT(*) runs, SUM(imported_count) imported, SUM(duplicate_count) duplicates, SUM(error_count) errors
			 FROM $table GROUP BY importer"
		);

		$stats = array();
		foreach ( (array) $rows as $row ) {
			$stats[ $row->importer ] = array(
				'runs'       => (int) $row->runs,
				'imported'   => (int) $row->imported,
				'duplicates' => (int) $row->duplicates,
				'errors'     => (int) $row->errors,
			);
		}

		return $stats;
	}
}
