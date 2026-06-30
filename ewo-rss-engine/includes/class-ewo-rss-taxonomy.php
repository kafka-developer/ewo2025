<?php
/**
 * Strategic taxonomy data layer: Strategic Domain → Subdomain → Keyword.
 *
 * Three custom tables hold the keyword-generation hierarchy. Keywords carry an
 * active toggle plus created/updated timestamps and link back to the
 * auto-generated feed via {@see EWO_RSS_Keyword_Feeds}. Static CRUD layer, in
 * the same spirit as {@see EWO_RSS_Audit_Log}.
 *
 * @package EWO_RSS_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Domains/subdomains/keywords storage and CRUD.
 */
class EWO_RSS_Taxonomy {
	const SCHEMA_OPTION  = 'ewo_rss_taxonomy_schema';
	const SCHEMA_VERSION = '2';

	/**
	 * Domains table name.
	 *
	 * @return string
	 */
	public static function domains_table() {
		global $wpdb;
		return $wpdb->prefix . 'ewo_rss_domains';
	}

	/**
	 * Subdomains table name.
	 *
	 * @return string
	 */
	public static function subdomains_table() {
		global $wpdb;
		return $wpdb->prefix . 'ewo_rss_subdomains';
	}

	/**
	 * Keywords table name.
	 *
	 * @return string
	 */
	public static function keywords_table() {
		global $wpdb;
		return $wpdb->prefix . 'ewo_rss_keywords';
	}

	/**
	 * Create/upgrade the taxonomy tables when needed.
	 */
	public static function maybe_install() {
		if ( self::SCHEMA_VERSION === get_option( self::SCHEMA_OPTION ) ) {
			return;
		}

		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$domains         = self::domains_table();
		$subdomains      = self::subdomains_table();
		$keywords        = self::keywords_table();

		$sql_domains = "CREATE TABLE $domains (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(191) NOT NULL DEFAULT '',
			description TEXT NOT NULL DEFAULT '',
			created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			updated_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY (id),
			KEY name (name)
		) $charset_collate;";

		$sql_subdomains = "CREATE TABLE $subdomains (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			domain_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			name VARCHAR(191) NOT NULL DEFAULT '',
			created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			updated_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY (id),
			KEY domain_id (domain_id),
			KEY name (name)
		) $charset_collate;";

		$sql_keywords = "CREATE TABLE $keywords (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			subdomain_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			keyword VARCHAR(191) NOT NULL DEFAULT '',
			active TINYINT(1) NOT NULL DEFAULT 1,
			feed_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			updated_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY (id),
			KEY subdomain_id (subdomain_id),
			KEY active (active),
			KEY feed_id (feed_id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql_domains );
		dbDelta( $sql_subdomains );
		dbDelta( $sql_keywords );

		update_option( self::SCHEMA_OPTION, self::SCHEMA_VERSION, false );
	}

	/* ---------------------------------------------------------------------
	 * Domains
	 * ------------------------------------------------------------------- */

	/**
	 * All domains, ordered by name.
	 *
	 * @return array<int,object>
	 */
	public static function get_domains() {
		global $wpdb;
		self::maybe_install();
		$table = self::domains_table();
		return (array) $wpdb->get_results( "SELECT * FROM $table ORDER BY name ASC" ); // phpcs:ignore WordPress.DB
	}

	/**
	 * A single domain.
	 *
	 * @param int $id Domain ID.
	 * @return object|null
	 */
	public static function get_domain( $id ) {
		global $wpdb;
		self::maybe_install();
		$table = self::domains_table();
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", (int) $id ) ); // phpcs:ignore WordPress.DB
	}

	/**
	 * Insert a domain.
	 *
	 * @param string $name        Domain name.
	 * @param string $description Optional description.
	 * @return int New ID, or 0.
	 */
	public static function add_domain( $name, $description = '' ) {
		global $wpdb;
		self::maybe_install();
		$name = self::clean_name( $name );
		if ( '' === $name ) {
			return 0;
		}
		$now = current_time( 'mysql', true );
		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			self::domains_table(),
			array(
				'name'        => $name,
				'description' => sanitize_textarea_field( (string) $description ),
				'created_at'  => $now,
				'updated_at'  => $now,
			),
			array( '%s', '%s', '%s', '%s' )
		);
		return (int) $wpdb->insert_id;
	}

	/**
	 * Delete a domain plus its subdomains and keywords (and their feeds).
	 *
	 * @param int $id Domain ID.
	 */
	public static function delete_domain( $id ) {
		global $wpdb;
		$id = (int) $id;
		foreach ( self::get_subdomains( $id ) as $sub ) {
			self::delete_subdomain( (int) $sub->id );
		}
		$wpdb->delete( self::domains_table(), array( 'id' => $id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	/* ---------------------------------------------------------------------
	 * Subdomains
	 * ------------------------------------------------------------------- */

	/**
	 * Subdomains for a domain (or all when domain is 0).
	 *
	 * @param int $domain_id Domain ID, or 0 for all.
	 * @return array<int,object>
	 */
	public static function get_subdomains( $domain_id = 0 ) {
		global $wpdb;
		self::maybe_install();
		$table     = self::subdomains_table();
		$domain_id = (int) $domain_id;
		if ( $domain_id > 0 ) {
			return (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table WHERE domain_id = %d ORDER BY name ASC", $domain_id ) ); // phpcs:ignore WordPress.DB
		}
		return (array) $wpdb->get_results( "SELECT * FROM $table ORDER BY name ASC" ); // phpcs:ignore WordPress.DB
	}

	/**
	 * A single subdomain.
	 *
	 * @param int $id Subdomain ID.
	 * @return object|null
	 */
	public static function get_subdomain( $id ) {
		global $wpdb;
		self::maybe_install();
		$table = self::subdomains_table();
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", (int) $id ) ); // phpcs:ignore WordPress.DB
	}

	/**
	 * Insert a subdomain under a domain.
	 *
	 * @param int    $domain_id Parent domain ID.
	 * @param string $name      Subdomain name.
	 * @return int New ID, or 0.
	 */
	public static function add_subdomain( $domain_id, $name ) {
		global $wpdb;
		self::maybe_install();
		$domain_id = (int) $domain_id;
		$name      = self::clean_name( $name );
		if ( $domain_id <= 0 || '' === $name || ! self::get_domain( $domain_id ) ) {
			return 0;
		}
		$now = current_time( 'mysql', true );
		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			self::subdomains_table(),
			array(
				'domain_id'  => $domain_id,
				'name'       => $name,
				'created_at' => $now,
				'updated_at' => $now,
			),
			array( '%d', '%s', '%s', '%s' )
		);
		return (int) $wpdb->insert_id;
	}

	/**
	 * Delete a subdomain plus its keywords (and their feeds).
	 *
	 * @param int $id Subdomain ID.
	 */
	public static function delete_subdomain( $id ) {
		global $wpdb;
		$id = (int) $id;
		foreach ( self::get_keywords( $id ) as $kw ) {
			self::delete_keyword( (int) $kw->id );
		}
		$wpdb->delete( self::subdomains_table(), array( 'id' => $id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	/* ---------------------------------------------------------------------
	 * Keywords
	 * ------------------------------------------------------------------- */

	/**
	 * Keywords for a subdomain (or all when subdomain is 0).
	 *
	 * @param int $subdomain_id Subdomain ID, or 0 for all.
	 * @return array<int,object>
	 */
	public static function get_keywords( $subdomain_id = 0 ) {
		global $wpdb;
		self::maybe_install();
		$table        = self::keywords_table();
		$subdomain_id = (int) $subdomain_id;
		if ( $subdomain_id > 0 ) {
			return (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table WHERE subdomain_id = %d ORDER BY keyword ASC", $subdomain_id ) ); // phpcs:ignore WordPress.DB
		}
		return (array) $wpdb->get_results( "SELECT * FROM $table ORDER BY keyword ASC" ); // phpcs:ignore WordPress.DB
	}

	/**
	 * A single keyword.
	 *
	 * @param int $id Keyword ID.
	 * @return object|null
	 */
	public static function get_keyword( $id ) {
		global $wpdb;
		self::maybe_install();
		$table = self::keywords_table();
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", (int) $id ) ); // phpcs:ignore WordPress.DB
	}

	/**
	 * All active keyword rows across every domain/subdomain.
	 *
	 * @return array<int,object>
	 */
	public static function get_active_keywords() {
		global $wpdb;
		self::maybe_install();
		$table = self::keywords_table();
		return (array) $wpdb->get_results( "SELECT * FROM $table WHERE active = 1 ORDER BY keyword ASC" ); // phpcs:ignore WordPress.DB
	}

	/**
	 * Insert a keyword under a subdomain.
	 *
	 * @param int    $subdomain_id Parent subdomain ID.
	 * @param string $keyword      Keyword text.
	 * @param bool   $active       Active toggle.
	 * @return int New ID, or 0.
	 */
	public static function add_keyword( $subdomain_id, $keyword, $active = true ) {
		global $wpdb;
		self::maybe_install();
		$subdomain_id = (int) $subdomain_id;
		$keyword      = self::clean_name( $keyword );
		if ( $subdomain_id <= 0 || '' === $keyword || ! self::get_subdomain( $subdomain_id ) ) {
			return 0;
		}
		$now = current_time( 'mysql', true );
		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			self::keywords_table(),
			array(
				'subdomain_id' => $subdomain_id,
				'keyword'      => $keyword,
				'active'       => $active ? 1 : 0,
				'feed_id'      => 0,
				'created_at'   => $now,
				'updated_at'   => $now,
			),
			array( '%d', '%s', '%d', '%d', '%s', '%s' )
		);
		return (int) $wpdb->insert_id;
	}

	/**
	 * Update a keyword's text and/or active state, bumping updated_at.
	 *
	 * @param int                 $id     Keyword ID.
	 * @param array<string,mixed> $fields Allowed: keyword, active, feed_id.
	 * @return bool
	 */
	public static function update_keyword( $id, array $fields ) {
		global $wpdb;
		self::maybe_install();
		$id = (int) $id;
		if ( $id <= 0 ) {
			return false;
		}

		$data    = array( 'updated_at' => current_time( 'mysql', true ) );
		$formats = array( '%s' );

		if ( array_key_exists( 'keyword', $fields ) ) {
			$data['keyword'] = self::clean_name( $fields['keyword'] );
			$formats[]       = '%s';
		}
		if ( array_key_exists( 'active', $fields ) ) {
			$data['active'] = ! empty( $fields['active'] ) ? 1 : 0;
			$formats[]      = '%d';
		}
		if ( array_key_exists( 'feed_id', $fields ) ) {
			$data['feed_id'] = (int) $fields['feed_id'];
			$formats[]       = '%d';
		}

		return false !== $wpdb->update( self::keywords_table(), $data, array( 'id' => $id ), $formats, array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	/**
	 * Delete a keyword and its auto-generated feed.
	 *
	 * @param int $id Keyword ID.
	 */
	public static function delete_keyword( $id ) {
		global $wpdb;
		$id = (int) $id;
		$kw = self::get_keyword( $id );
		if ( $kw && (int) $kw->feed_id > 0 && class_exists( 'EWO_RSS_Keyword_Feeds' ) ) {
			EWO_RSS_Keyword_Feeds::delete_feed( (int) $kw->feed_id );
		}
		$wpdb->delete( self::keywords_table(), array( 'id' => $id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	/* ---------------------------------------------------------------------
	 * Helpers
	 * ------------------------------------------------------------------- */

	/**
	 * Resolve the subdomain + domain context for a keyword.
	 *
	 * @param int|object $keyword Keyword ID or row.
	 * @return array{keyword:?object,subdomain:?object,domain:?object}
	 */
	public static function context_for_keyword( $keyword ) {
		$kw = is_object( $keyword ) ? $keyword : self::get_keyword( (int) $keyword );
		if ( ! $kw ) {
			return array(
				'keyword'   => null,
				'subdomain' => null,
				'domain'    => null,
			);
		}
		$subdomain = self::get_subdomain( (int) $kw->subdomain_id );
		$domain    = $subdomain ? self::get_domain( (int) $subdomain->domain_id ) : null;

		return array(
			'keyword'   => $kw,
			'subdomain' => $subdomain,
			'domain'    => $domain,
		);
	}

	/**
	 * Sanitize a name/keyword string.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	protected static function clean_name( $value ) {
		$value = sanitize_text_field( (string) $value );
		return trim( mb_substr( $value, 0, 191 ) );
	}
}
