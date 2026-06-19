<?php
/**
 * Custom content types that back homepage sections.
 *
 * - Community Posts (ewo_community_post): short, X-style updates authored on the
 *   site and shown on the homepage Community section.
 * - Strategic Predictions (ewo_prediction): forecast cards for the Predictions section.
 *
 * Slugs are intentionally distinct from the EWO YouTube plugin's `ewo_community`
 * and `ewo_playlist` types to avoid collisions.
 *
 * @package EWO_2025
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const EWO_2025_COMMUNITY_CPT  = 'ewo_community_post';
const EWO_2025_PREDICTION_CPT = 'ewo_prediction';

/**
 * Register the Community Post and Strategic Prediction post types.
 */
function ewo_2025_register_content_types() {
	register_post_type(
		EWO_2025_COMMUNITY_CPT,
		array(
			'labels'        => array(
				'name'               => __( 'Community Wall', 'ewo-2025' ),
				'singular_name'      => __( 'Community Post', 'ewo-2025' ),
				'menu_name'          => __( 'Community Wall', 'ewo-2025' ),
				'add_new_item'       => __( 'Add Community Post', 'ewo-2025' ),
				'edit_item'          => __( 'Edit Community Post', 'ewo-2025' ),
				'new_item'           => __( 'New Community Post', 'ewo-2025' ),
				'view_item'          => __( 'View Community Post', 'ewo-2025' ),
				'search_items'       => __( 'Search Community Posts', 'ewo-2025' ),
				'not_found'          => __( 'No community posts yet.', 'ewo-2025' ),
				'not_found_in_trash' => __( 'No community posts in Trash.', 'ewo-2025' ),
			),
			'public'        => true,
			'has_archive'   => false,
			'show_in_rest'  => true,
			'menu_icon'     => 'dashicons-format-status',
			'menu_position' => 26,
			'supports'      => array( 'title', 'editor', 'thumbnail', 'author' ),
			'rewrite'       => array( 'slug' => 'community' ),
		)
	);

	register_post_type(
		EWO_2025_PREDICTION_CPT,
		array(
			'labels'        => array(
				'name'               => __( 'Predictions', 'ewo-2025' ),
				'singular_name'      => __( 'Prediction', 'ewo-2025' ),
				'menu_name'          => __( 'Predictions', 'ewo-2025' ),
				'add_new_item'       => __( 'Add Prediction', 'ewo-2025' ),
				'edit_item'          => __( 'Edit Prediction', 'ewo-2025' ),
				'new_item'           => __( 'New Prediction', 'ewo-2025' ),
				'view_item'          => __( 'View Prediction', 'ewo-2025' ),
				'search_items'       => __( 'Search Predictions', 'ewo-2025' ),
				'not_found'          => __( 'No predictions yet.', 'ewo-2025' ),
				'not_found_in_trash' => __( 'No predictions in Trash.', 'ewo-2025' ),
			),
			'public'        => true,
			'has_archive'   => false,
			'show_in_rest'  => true,
			'menu_icon'     => 'dashicons-chart-line',
			'menu_position' => 27,
			'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail' ),
			'rewrite'       => array( 'slug' => 'predictions' ),
		)
	);
}
add_action( 'init', 'ewo_2025_register_content_types' );

/**
 * Flush rewrite rules once after these post types are introduced or changed,
 * so single permalinks resolve without a manual Settings → Permalinks save.
 */
function ewo_2025_maybe_flush_content_type_rewrites() {
	$flag    = 'ewo_2025_cpt_rewrites';
	$current = '1'; // Bump when CPT rewrite rules change.

	if ( get_option( $flag ) !== $current ) {
		flush_rewrite_rules( false );
		update_option( $flag, $current, false );
	}
}
add_action( 'init', 'ewo_2025_maybe_flush_content_type_rewrites', 99 );
