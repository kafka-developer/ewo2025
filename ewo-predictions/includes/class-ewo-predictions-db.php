<?php
/**
 * Database layer for EWO Predictions.
 *
 * @package EWO_Predictions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EWO_Predictions_DB {

	const TABLE          = 'ewo_predictions';
	const SCHEMA_OPTION  = 'ewo_predictions_schema';
	const SCHEMA_VERSION = '2';

	const STATUS_ACTIVE   = 'active';
	const STATUS_TRACKING = 'tracking';
	const STATUS_HIT      = 'hit';
	const STATUS_MISSED   = 'missed';
	const STATUS_PARTIAL  = 'partial';
	const STATUS_ARCHIVED = 'archived';

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . self::TABLE;
	}

	public static function statuses() {
		return array( self::STATUS_ACTIVE, self::STATUS_TRACKING, self::STATUS_HIT, self::STATUS_MISSED, self::STATUS_PARTIAL, self::STATUS_ARCHIVED );
	}

	public static function maybe_install() {
		if ( self::SCHEMA_VERSION === get_option( self::SCHEMA_OPTION ) ) {
			return;
		}
		global $wpdb;
		$t  = self::table();
		$cc = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE $t (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			title VARCHAR(500) NOT NULL DEFAULT '',
			domain_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			subdomain_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			prediction_type VARCHAR(100) NOT NULL DEFAULT '',
			prediction_statement TEXT NOT NULL,
			rationale TEXT NOT NULL,
			confidence_score TINYINT(3) UNSIGNED NOT NULL DEFAULT 50,
			prediction_date DATE NULL,
			target_date DATE NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'active',
			outcome_notes TEXT NOT NULL,
			source_url VARCHAR(500) NOT NULL DEFAULT '',
			visibility VARCHAR(10) NOT NULL DEFAULT 'public',
			created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			updated_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY (id),
			KEY domain_id (domain_id),
			KEY subdomain_id (subdomain_id),
			KEY status (status),
			KEY confidence_score (confidence_score),
			KEY prediction_date (prediction_date),
			KEY visibility (visibility)
		) $cc;";
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
		update_option( self::SCHEMA_OPTION, self::SCHEMA_VERSION, false );
	}

	public static function insert( array $data ) {
		global $wpdb;
		self::maybe_install();
		$now = current_time( 'mysql', true );
		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			self::table(),
			array_merge( self::clean( $data ), array(
				'created_at' => $now,
				'updated_at' => $now,
			) ),
			self::formats()
		);
		return (int) $wpdb->insert_id;
	}

	public static function update( $id, array $data ) {
		global $wpdb;
		$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			self::table(),
			array_merge( self::clean( $data ), array( 'updated_at' => current_time( 'mysql', true ) ) ),
			array( 'id' => (int) $id ),
			self::formats(),
			array( '%d' )
		);
	}

	public static function delete( $id ) {
		global $wpdb;
		$wpdb->delete( self::table(), array( 'id' => (int) $id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	public static function get( $id ) {
		global $wpdb;
		$t  = self::table();
		$dt = $wpdb->prefix . 'ewo_rss_domains';
		$st = $wpdb->prefix . 'ewo_rss_subdomains';
		return $wpdb->get_row( $wpdb->prepare( // phpcs:ignore WordPress.DB
			"SELECT p.*, d.name AS domain_name, s.name AS subdomain_name
			 FROM $t p
			 LEFT JOIN $dt d ON d.id = p.domain_id
			 LEFT JOIN $st s ON s.id = p.subdomain_id
			 WHERE p.id = %d",
			(int) $id
		) );
	}

	public static function query( array $args = array() ) {
		global $wpdb;
		list( $where, $params ) = self::build_where( $args );
		$t   = self::table();
		$dt  = $wpdb->prefix . 'ewo_rss_domains';
		$st  = $wpdb->prefix . 'ewo_rss_subdomains';
		$by  = in_array( $args['orderby'] ?? 'id', array( 'id', 'title', 'confidence_score', 'prediction_date', 'target_date', 'status' ), true )
			? sanitize_key( $args['orderby'] ?? 'id' ) : 'id';
		$ord = ( ( $args['order'] ?? 'DESC' ) === 'ASC' ) ? 'ASC' : 'DESC';
		$lim = (int) ( $args['limit'] ?? 10 );
		$off = (int) ( $args['offset'] ?? 0 );

		$sql = "SELECT p.*, d.name AS domain_name, s.name AS subdomain_name
		        FROM $t p
		        LEFT JOIN $dt d ON d.id = p.domain_id
		        LEFT JOIN $st s ON s.id = p.subdomain_id
		        $where
		        ORDER BY p.$by $ord
		        LIMIT %d OFFSET %d";

		$params[] = $lim;
		$params[] = $off;

		return (array) $wpdb->get_results( $wpdb->prepare( $sql, $params ) ); // phpcs:ignore WordPress.DB
	}

	public static function count( array $args = array() ) {
		global $wpdb;
		list( $where, $params ) = self::build_where( $args );
		$t  = self::table();
		$dt = $wpdb->prefix . 'ewo_rss_domains';
		$st = $wpdb->prefix . 'ewo_rss_subdomains';
		$sql = "SELECT COUNT(*) FROM $t p
		        LEFT JOIN $dt d ON d.id = p.domain_id
		        LEFT JOIN $st s ON s.id = p.subdomain_id
		        $where";
		if ( empty( $params ) ) {
			return (int) $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB
		}
		return (int) $wpdb->get_var( $wpdb->prepare( $sql, $params ) ); // phpcs:ignore WordPress.DB
	}

	public static function metrics() {
		global $wpdb;
		$t   = self::table();
		$sql = "SELECT COUNT(*) AS total,
			SUM(status='active')         AS active,
			SUM(status='hit')            AS hit,
			SUM(status='missed')         AS missed,
			ROUND(AVG(confidence_score)) AS avg_conf
			FROM $t";
		$row = $wpdb->get_row( $sql ); // phpcs:ignore WordPress.DB
		return array(
			'total'    => (int) ( $row->total ?? 0 ),
			'active'   => (int) ( $row->active ?? 0 ),
			'hit'      => (int) ( $row->hit ?? 0 ),
			'missed'   => (int) ( $row->missed ?? 0 ),
			'avg_conf' => (int) ( $row->avg_conf ?? 0 ),
		);
	}

	public static function get_types() {
		global $wpdb;
		$t = self::table();
		$rows = $wpdb->get_col( "SELECT DISTINCT prediction_type FROM $t WHERE prediction_type != '' ORDER BY prediction_type ASC" ); // phpcs:ignore WordPress.DB
		return (array) $rows;
	}

	private static function build_where( array $args ) {
		$where  = array();
		$params = array();

		if ( ! empty( $args['domain_id'] ) ) {
			$where[]  = 'p.domain_id = %d';
			$params[] = (int) $args['domain_id'];
		}
		if ( ! empty( $args['subdomain_id'] ) ) {
			$where[]  = 'p.subdomain_id = %d';
			$params[] = (int) $args['subdomain_id'];
		}
		if ( ! empty( $args['prediction_type'] ) ) {
			$where[]  = 'p.prediction_type = %s';
			$params[] = (string) $args['prediction_type'];
		}
		if ( ! empty( $args['status'] ) && in_array( $args['status'], self::statuses(), true ) ) {
			$where[]  = 'p.status = %s';
			$params[] = (string) $args['status'];
		}
		if ( isset( $args['confidence_min'] ) && $args['confidence_min'] !== '' ) {
			$where[]  = 'p.confidence_score >= %d';
			$params[] = (int) $args['confidence_min'];
		}
		if ( isset( $args['confidence_max'] ) && $args['confidence_max'] !== '' ) {
			$where[]  = 'p.confidence_score <= %d';
			$params[] = (int) $args['confidence_max'];
		}
		if ( ! empty( $args['date_from'] ) ) {
			$where[]  = 'p.prediction_date >= %s';
			$params[] = sanitize_text_field( $args['date_from'] );
		}
		if ( ! empty( $args['date_to'] ) ) {
			$where[]  = 'p.prediction_date <= %s';
			$params[] = sanitize_text_field( $args['date_to'] );
		}
		if ( ! empty( $args['visibility'] ) && in_array( $args['visibility'], array( 'public', 'private' ), true ) ) {
			$where[]  = 'p.visibility = %s';
			$params[] = $args['visibility'];
		}

		$sql = $where ? 'WHERE ' . implode( ' AND ', $where ) : '';
		return array( $sql, $params );
	}

	private static function clean( array $data ) {
		return array(
			'title'                => substr( sanitize_text_field( $data['title'] ?? '' ), 0, 500 ),
			'domain_id'            => (int) ( $data['domain_id'] ?? 0 ),
			'subdomain_id'         => (int) ( $data['subdomain_id'] ?? 0 ),
			'prediction_type'      => substr( sanitize_text_field( $data['prediction_type'] ?? '' ), 0, 100 ),
			'prediction_statement' => sanitize_textarea_field( $data['prediction_statement'] ?? '' ),
			'rationale'            => sanitize_textarea_field( $data['rationale'] ?? '' ),
			'confidence_score'     => max( 0, min( 100, (int) ( $data['confidence_score'] ?? 50 ) ) ),
			'prediction_date'      => ! empty( $data['prediction_date'] ) ? sanitize_text_field( $data['prediction_date'] ) : null,
			'target_date'          => ! empty( $data['target_date'] ) ? sanitize_text_field( $data['target_date'] ) : null,
			'status'               => in_array( $data['status'] ?? '', self::statuses(), true ) ? $data['status'] : self::STATUS_ACTIVE,
			'outcome_notes'        => sanitize_textarea_field( $data['outcome_notes'] ?? '' ),
			'source_url'           => esc_url_raw( $data['source_url'] ?? '' ),
			'visibility'           => in_array( $data['visibility'] ?? 'public', array( 'public', 'private' ), true ) ? $data['visibility'] : 'public',
		);
	}

	private static function formats() {
		return array( '%s', '%d', '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' );
	}
}
