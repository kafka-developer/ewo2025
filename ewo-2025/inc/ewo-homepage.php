<?php
/**
 * Homepage data providers.
 *
 * @package EWO_2025
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Strategic-domain definitions: the six core intelligence buckets.
 *
 * `visual` maps to an existing .ewo-domain-card--{visual} background motif.
 *
 * @return array<int,array<string,mixed>>
 */
function ewo_2025_strategic_domain_config() {
	return array(
		array(
			'key'         => 'energy',
			'name'        => __( 'Energy Systems', 'ewo-2025' ),
			'description' => __( 'Supply chains, chokepoints, grids, and the leverage created by energy dependence.', 'ewo-2025' ),
			'visual'      => 'energy',
			'keywords'    => array( 'energy', 'oil', 'gas', 'lng', 'opec', 'pipeline', 'crude', 'refinery', 'refiner', 'grid', 'nuclear', 'coal', 'barrel', 'fuel', 'power plant', 'electricity', 'solar', 'renewable' ),
		),
		array(
			'key'         => 'trade',
			'name'        => __( 'Trade Networks', 'ewo-2025' ),
			'description' => __( 'Ports, corridors, tariffs, sanctions, and the routes that define commercial influence.', 'ewo-2025' ),
			'visual'      => 'trade',
			'keywords'    => array( 'trade', 'export', 'import', 'tariff', 'port', 'shipping', 'supply chain', 'sanction', 'container', 'freight', 'chokepoint', 'strait', 'canal', 'logistics', 'customs', 'embargo' ),
		),
		array(
			'key'         => 'finance',
			'name'        => __( 'Financial Power', 'ewo-2025' ),
			'description' => __( 'Capital flows, reserve assets, debt pressure, payment rails, and monetary coercion.', 'ewo-2025' ),
			'visual'      => 'finance',
			'keywords'    => array( 'dollar', 'currency', 'bond', 'debt', 'central bank', 'federal reserve', ' fed ', 'inflation', 'reserve', 'yuan', 'renminbi', 'payment', 'swift', 'capital', 'imf', 'bank', 'rate cut', 'rate hike', 'treasury', 'brics' ),
		),
		array(
			'key'         => 'technology',
			'name'        => __( 'Technology & Industry', 'ewo-2025' ),
			'description' => __( 'Semiconductors, AI, manufacturing, standards, and industrial advantage.', 'ewo-2025' ),
			'visual'      => 'technology',
			'keywords'    => array( 'chip', 'semiconductor', 'ai', 'artificial intelligence', 'technology', 'tech', 'manufacturing', 'industrial', 'data center', 'cyber', 'telecom', '5g', 'battery', 'ev', 'robot', 'software', 'cloud' ),
		),
		array(
			'key'         => 'security',
			'name'        => __( 'Security Architecture', 'ewo-2025' ),
			'description' => __( 'Force posture, deterrence, defense production, alliances, and escalation dynamics.', 'ewo-2025' ),
			'visual'      => 'military',
			'keywords'    => array( 'military', 'defense', 'defence', 'nato', 'war', 'missile', 'navy', 'troops', 'security', 'weapon', 'conflict', 'deterrence', 'alliance', 'army', 'strike', 'drone', 'nuclear weapon', 'ceasefire' ),
		),
		array(
			'key'         => 'resources',
			'name'        => __( 'Strategic Resources', 'ewo-2025' ),
			'description' => __( 'Critical minerals, food, water, and the commodities that underwrite power.', 'ewo-2025' ),
			'visual'      => 'institutions',
			'keywords'    => array( 'rare earth', 'lithium', 'copper', 'cobalt', 'mineral', 'metal', 'food', 'grain', 'wheat', 'water', 'commodity', 'uranium', 'nickel', 'critical material', 'fertilizer', 'agriculture' ),
		),
	);
}

/**
 * Build the Strategic Domains dataset by classifying recent imported posts
 * (RSS / Substack analysis) into the six domains, with a ranking score.
 *
 * Cached for 30 minutes; the cache is cleared whenever a post is saved.
 *
 * @return array<int,array<string,mixed>>
 */
function ewo_2025_strategic_domains() {
	$cache_key = 'ewo_2025_strategic_domains_v1';
	$cached    = get_transient( $cache_key );

	if ( is_array( $cached ) ) {
		return $cached;
	}

	$config  = ewo_2025_strategic_domain_config();
	$buckets = array();
	foreach ( $config as $domain ) {
		$buckets[ $domain['key'] ] = array();
	}

	$posts = get_posts(
		array(
			'post_type'        => 'post',
			'post_status'      => 'publish',
			'posts_per_page'   => 120,
			'orderby'          => 'date',
			'order'            => 'DESC',
			'suppress_filters' => false,
			'no_found_rows'    => true,
		)
	);

	$now = time();

	foreach ( $posts as $post ) {
		$haystack = ' ' . strtolower( wp_strip_all_tags( $post->post_title ) ) . ' ';
		$best_key = '';
		$best_hit = 0;

		foreach ( $config as $domain ) {
			$hits = 0;
			foreach ( $domain['keywords'] as $keyword ) {
				if ( false !== strpos( $haystack, strtolower( $keyword ) ) ) {
					++$hits;
				}
			}
			if ( $hits > $best_hit ) {
				$best_hit = $hits;
				$best_key = $domain['key'];
			}
		}

		if ( '' === $best_key || $best_hit < 1 ) {
			continue;
		}

		$age_days  = max( 0, ( $now - (int) get_post_time( 'U', true, $post ) ) / DAY_IN_SECONDS );
		$recency   = max( 0.0, 1.0 - ( $age_days / 30.0 ) );
		$relevance = ( $best_hit * 1.0 ) + $recency;

		$buckets[ $best_key ][] = array(
			'title'     => get_the_title( $post ),
			'url'       => get_permalink( $post ),
			'hits'      => $best_hit,
			'relevance' => $relevance,
		);
	}

	$domains = array();

	foreach ( $config as $domain ) {
		$items = $buckets[ $domain['key'] ];

		usort(
			$items,
			static function ( $a, $b ) {
				if ( $a['relevance'] === $b['relevance'] ) {
					return 0;
				}
				return ( $a['relevance'] < $b['relevance'] ) ? 1 : -1;
			}
		);

		$items     = array_slice( $items, 0, 5 );
		$headlines = array();
		$prev      = 10.0;

		foreach ( $items as $index => $item ) {
			$raw   = 9.8 - ( $index * 0.3 ) + min( 0.15, 0.03 * $item['hits'] );
			$score = round( $raw, 1 );

			if ( $score >= $prev ) {
				$score = round( $prev - 0.1, 1 );
			}
			$score = max( 8.4, $score );
			$prev  = $score;

			$headlines[] = array(
				'title' => $item['title'],
				'url'   => $item['url'],
				'score' => number_format( $score, 1 ),
			);
		}

		$domains[] = array(
			'key'         => $domain['key'],
			'name'        => $domain['name'],
			'description' => $domain['description'],
			'visual'      => $domain['visual'],
			'headlines'   => $headlines,
		);
	}

	set_transient( $cache_key, $domains, 30 * MINUTE_IN_SECONDS );

	return $domains;
}

/**
 * Clear the Strategic Domains cache when content changes.
 *
 * @param int $post_id Saved post ID.
 */
function ewo_2025_clear_strategic_domains_cache( $post_id ) {
	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}
	delete_transient( 'ewo_2025_strategic_domains_v1' );
}
add_action( 'save_post_post', 'ewo_2025_clear_strategic_domains_cache' );
add_action( 'deleted_post', 'ewo_2025_clear_strategic_domains_cache' );

/**
 * Return a configured follower/subscriber count label for a platform, if set.
 *
 * @param string $platform Platform key (youtube, spotify, x, substack, ...).
 * @return string Display string (e.g. "350K") or empty.
 */
function ewo_2025_platform_follower_count( $platform ) {
	return trim( (string) get_theme_mod( 'ewo_2025_' . $platform . '_count', '' ) );
}
