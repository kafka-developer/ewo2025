<?php
/**
 * Strategic Domains public page — data layer.
 *
 * Fetches admin-created taxonomy data (domains → subdomains → keywords → sources)
 * from the EWO RSS Engine plugin tables. All functions guard against the plugin
 * being absent or not yet initialised.
 *
 * @package EWO_2025
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Human-readable relative time: "2h ago", "3d ago", or a short date.
 *
 * @param string $datetime MySQL datetime string (UTC).
 * @return string Empty string when input is empty/invalid.
 */
function ewo_2025_time_ago( $datetime ) {
	if ( ! $datetime || '0000-00-00 00:00:00' === $datetime ) {
		return '';
	}
	$ts = strtotime( $datetime );
	if ( ! $ts ) {
		return '';
	}
	$diff = time() - $ts;

	if ( $diff < 3600 ) {
		return max( 1, (int) round( $diff / 60 ) ) . 'm ago';
	}
	if ( $diff < 86400 ) {
		return (int) round( $diff / 3600 ) . 'h ago';
	}
	if ( $diff < 604800 ) {
		return (int) round( $diff / 86400 ) . 'd ago';
	}
	return (string) wp_date( 'M j', $ts );
}

/**
 * Return the structured dataset for the Strategic Domains index page.
 *
 * Cached per request in a static variable; callers that need a fresh copy
 * can pass $bust = true.
 *
 * @param bool $bust Force a cache-refresh.
 * @return array<int,array<string,mixed>>
 */
function ewo_2025_sfd_index_data( $bust = false ) {
	static $cache = null;

	if ( ! $bust && null !== $cache ) {
		return $cache;
	}

	if ( ! class_exists( 'EWO_RSS_Taxonomy' ) || ! class_exists( 'EWO_RSS_Source_Store' ) ) {
		$cache = array();
		return $cache;
	}

	$domains = EWO_RSS_Taxonomy::get_domains();
	$result  = array();

	foreach ( $domains as $domain ) {
		$domain_id  = (int) $domain->id;
		$subdomains = EWO_RSS_Taxonomy::get_subdomains( $domain_id );

		$keyword_count = 0;
		$feed_count    = 0;

		foreach ( $subdomains as $sub ) {
			$keywords       = EWO_RSS_Taxonomy::get_keywords( (int) $sub->id );
			$keyword_count += count( $keywords );

			foreach ( $keywords as $kw ) {
				if ( (int) $kw->feed_id > 0 ) {
					++$feed_count;
				}
			}
		}

		$sources = EWO_RSS_Source_Store::query(
			array(
				'domain_id' => $domain_id,
				'limit'     => 3,
				'orderby'   => 'fetched_at',
				'order'     => 'DESC',
			)
		);

		$result[] = array(
			'id'              => $domain_id,
			'name'            => $domain->name,
			'description'     => isset( $domain->description ) ? (string) $domain->description : '',
			'slug'            => sanitize_title( $domain->name ),
			'subdomain_count' => count( $subdomains ),
			'keyword_count'   => $keyword_count,
			'feed_count'      => $feed_count,
			'sources'         => $sources,
		);
	}

	$cache = $result;
	return $result;
}

/**
 * Return the full dataset for a single domain's detail page.
 *
 * Matches domain by slug (sanitize_title of domain name).
 *
 * @param string $slug URL slug to look up.
 * @return array<string,mixed>|null  Null when the domain is not found.
 */
function ewo_2025_sfd_detail_data( $slug ) {
	if ( ! class_exists( 'EWO_RSS_Taxonomy' ) ) {
		return null;
	}

	$slug   = sanitize_title( (string) $slug );
	$domain = null;

	foreach ( EWO_RSS_Taxonomy::get_domains() as $d ) {
		if ( sanitize_title( $d->name ) === $slug ) {
			$domain = $d;
			break;
		}
	}

	if ( ! $domain ) {
		return null;
	}

	$domain_id      = (int) $domain->id;
	$subdomains_out = array();

	foreach ( EWO_RSS_Taxonomy::get_subdomains( $domain_id ) as $sub ) {
		$sub_id   = (int) $sub->id;
		$keywords = EWO_RSS_Taxonomy::get_keywords( $sub_id );

		$kw_data = array();
		foreach ( $keywords as $kw ) {
			$feed_url = '';
			if ( (int) $kw->feed_id > 0 && class_exists( 'EWO_RSS_Feed' ) ) {
				$feed_url = (string) EWO_RSS_Feed::url( (int) $kw->feed_id );
			}

			$kw_data[] = array(
				'keyword'  => $kw->keyword,
				'active'   => (bool) $kw->active,
				'feed_url' => $feed_url,
			);
		}

		$sources = array();
		if ( class_exists( 'EWO_RSS_Source_Store' ) ) {
			$sources = EWO_RSS_Source_Store::query(
				array(
					'subdomain_id' => $sub_id,
					'limit'        => 6,
					'orderby'      => 'fetched_at',
					'order'        => 'DESC',
				)
			);
		}

		$subdomains_out[] = array(
			'id'       => $sub_id,
			'name'     => $sub->name,
			'keywords' => $kw_data,
			'sources'  => $sources,
		);
	}

	return array(
		'id'          => $domain_id,
		'name'        => $domain->name,
		'description' => isset( $domain->description ) ? (string) $domain->description : '',
		'slug'        => sanitize_title( $domain->name ),
		'subdomains'  => $subdomains_out,
	);
}

/**
 * Return all feed items for the Smart Feed page, with full taxonomy context.
 *
 * Each item carries domain name/id, subdomain name, keyword text, publication
 * domain, published time, and a plain-text snippet from the stored content.
 *
 * @param int $limit Maximum source rows to fetch. Default 120.
 * @return array{domains:array<int,array<string,mixed>>,items:array<int,array<string,mixed>>}
 */
function ewo_2025_smart_feed_data( $limit = 120 ) {
	if ( ! class_exists( 'EWO_RSS_Taxonomy' ) || ! class_exists( 'EWO_RSS_Source_Store' ) ) {
		return array(
			'domains' => array(),
			'items'   => array(),
		);
	}

	// Build lookup maps so we avoid per-row queries.
	$domain_map    = array();
	$subdomain_map = array();
	$keyword_map   = array();
	$domains_raw   = EWO_RSS_Taxonomy::get_domains();

	foreach ( $domains_raw as $d ) {
		$domain_map[ (int) $d->id ] = $d->name;
	}
	foreach ( EWO_RSS_Taxonomy::get_subdomains() as $s ) {
		$subdomain_map[ (int) $s->id ] = $s->name;
	}
	foreach ( EWO_RSS_Taxonomy::get_keywords() as $k ) {
		$keyword_map[ (int) $k->id ] = $k->keyword;
	}

	$sources = EWO_RSS_Source_Store::query(
		array(
			'limit'   => (int) $limit,
			'orderby' => 'fetched_at',
			'order'   => 'DESC',
		)
	);

	$items = array();

	foreach ( $sources as $src ) {
		$domain_id = (int) $src->domain_id;
		if ( $domain_id <= 0 ) {
			continue;
		}

		// Extract plain text snippet from stored HTML content.
		$snippet = '';
		if ( ! empty( $src->content ) ) {
			$plain   = wp_strip_all_tags( (string) $src->content );
			$plain   = trim( preg_replace( '/\s+/', ' ', $plain ) );
			// Skip if it's just the article title repeated.
			if ( strlen( $plain ) > 40 && similar_text( strtolower( $plain ), strtolower( (string) $src->title ) ) / max( 1, strlen( $src->title ) ) < 0.85 ) {
				$snippet = wp_trim_words( $plain, 22, '…' );
			}
		}

		$ts_raw   = ! empty( $src->published_at ) && '0000-00-00 00:00:00' !== $src->published_at
			? $src->published_at
			: $src->fetched_at;

		$items[] = array(
			'id'             => (int) $src->id,
			'title'          => (string) $src->title,
			'url'            => (string) $src->url,
			'source_domain'  => (string) $src->source_domain,
			'time_raw'       => $ts_raw,
			'time_ago'       => ewo_2025_time_ago( $ts_raw ),
			'snippet'        => $snippet,
			'domain_id'      => $domain_id,
			'domain_name'    => isset( $domain_map[ $domain_id ] ) ? $domain_map[ $domain_id ] : '',
			'domain_slug'    => sanitize_title( isset( $domain_map[ $domain_id ] ) ? $domain_map[ $domain_id ] : '' ),
			'subdomain_name' => isset( $subdomain_map[ (int) $src->subdomain_id ] ) ? $subdomain_map[ (int) $src->subdomain_id ] : '',
			'keyword'        => isset( $keyword_map[ (int) $src->keyword_id ] ) ? $keyword_map[ (int) $src->keyword_id ] : '',
		);
	}

	$domains_out = array();
	foreach ( $domains_raw as $d ) {
		$domains_out[] = array(
			'id'   => (int) $d->id,
			'name' => $d->name,
			'slug' => sanitize_title( $d->name ),
		);
	}

	return array(
		'domains' => $domains_out,
		'items'   => $items,
	);
}
