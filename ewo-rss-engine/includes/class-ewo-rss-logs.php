<?php
/**
 * Import logs (stored in a capped option).
 *
 * @package EWO_RSS_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lightweight log store for import runs.
 */
class EWO_RSS_Logs {
	const OPTION = 'ewo_rss_engine_logs';
	const LIMIT  = 200;

	/**
	 * Record a log entry.
	 *
	 * @param array<string,mixed> $entry Log fields (source_id, source_name, found, created, skipped, errors, message).
	 */
	public static function add( $entry ) {
		$logs = self::all();

		array_unshift(
			$logs,
			array(
				'time'        => current_time( 'mysql' ),
				'source_id'   => isset( $entry['source_id'] ) ? (int) $entry['source_id'] : 0,
				'source_name' => isset( $entry['source_name'] ) ? (string) $entry['source_name'] : '',
				'found'       => isset( $entry['found'] ) ? (int) $entry['found'] : 0,
				'created'     => isset( $entry['created'] ) ? (int) $entry['created'] : 0,
				'skipped'     => isset( $entry['skipped'] ) ? (int) $entry['skipped'] : 0,
				'errors'      => isset( $entry['errors'] ) ? (int) $entry['errors'] : 0,
				'message'     => isset( $entry['message'] ) ? (string) $entry['message'] : '',
			)
		);

		if ( count( $logs ) > self::LIMIT ) {
			$logs = array_slice( $logs, 0, self::LIMIT );
		}

		update_option( self::OPTION, $logs, false );
	}

	/**
	 * Get all log entries (newest first).
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public static function all() {
		$logs = get_option( self::OPTION, array() );

		return is_array( $logs ) ? $logs : array();
	}

	/**
	 * Clear the log.
	 */
	public static function clear() {
		delete_option( self::OPTION );
	}
}
