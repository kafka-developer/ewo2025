<?php
/**
 * EWO 2025 theme functions.
 *
 * @package EWO_2025
 */

/*
 * Strict build tracking.
 *
 * EWO_THEME_VERSION — semantic version, kept in sync with the "Version:" header in style.css.
 * EWO_THEME_BUILD   — build ID in the form YYYYMMDD-NN. MUST be bumped every time the
 *                     distributable ZIP is rebuilt (enforced by build-theme.sh). See CHANGELOG.md.
 */
if ( ! defined( 'EWO_THEME_VERSION' ) ) {
	define( 'EWO_THEME_VERSION', '0.3.8' );
}

if ( ! defined( 'EWO_THEME_BUILD' ) ) {
	define( 'EWO_THEME_BUILD', '20260626-22' );
}

if ( ! defined( 'EWO_2025_VERSION' ) ) {
	// Combined semantic version + build ID, used as the cache-busting token for CSS/JS assets.
	define( 'EWO_2025_VERSION', EWO_THEME_VERSION . '+' . EWO_THEME_BUILD );
}

// Homepage content types and data providers.
require_once get_template_directory() . '/inc/ewo-content-types.php';
require_once get_template_directory() . '/inc/ewo-homepage.php';
require_once get_template_directory() . '/inc/ewo-social-links.php';
require_once get_template_directory() . '/inc/ewo-sidebar.php';
require_once get_template_directory() . '/inc/ewo-book.php';
require_once get_template_directory() . '/inc/ewo-dynamic-sections.php';
require_once get_template_directory() . '/inc/ewo-feature-visibility.php';
require_once get_template_directory() . '/inc/ewo-homepage-settings.php';
require_once get_template_directory() . '/inc/ewo-custom-cards.php';

// Strategic Domains public page data layer.
require_once get_template_directory() . '/inc/ewo-sfd-data.php';

// Homepage Section Renderers and Section Order Manager.
require_once get_template_directory() . '/inc/ewo-section-renderers.php';
require_once get_template_directory() . '/inc/ewo-section-order.php';

if ( ! function_exists( 'ewo_2025_setup' ) ) {
	/**
	 * Set up theme defaults and supported WordPress features.
	 */
	function ewo_2025_setup() {
		load_theme_textdomain( 'ewo-2025', get_template_directory() . '/languages' );

		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support(
			'custom-logo',
			array(
				'height'      => 120,
				'width'       => 320,
				'flex-height' => true,
				'flex-width'  => true,
			)
		);
		add_theme_support(
			'html5',
			array(
				'comment-form',
				'comment-list',
				'gallery',
				'caption',
				'style',
				'script',
				'search-form',
			)
		);

		register_nav_menus(
			array(
				'primary' => esc_html__( 'Primary Menu', 'ewo-2025' ),
				'footer'  => esc_html__( 'Footer Menu', 'ewo-2025' ),
			)
		);
	}
}
add_action( 'after_setup_theme', 'ewo_2025_setup' );



/**
 * Fallback primary navigation when no WordPress menu is assigned.
 *
 * Uses WordPress-generated URLs and pages. A user-assigned Primary Menu always takes precedence.
 */
function ewo_2025_primary_menu_fallback() {
	$items = array(
		array(
			'label' => __( 'Home', 'ewo-2025' ),
			'url'   => home_url( '/' ),
		),
		array(
			'label' => __( 'Analysis', 'ewo-2025' ),
			'slug'  => 'analysis',
		),
		array(
			'label' => __( 'Videos', 'ewo-2025' ),
			'slug'  => 'videos',
		),
		array(
			'label' => __( 'Podcast', 'ewo-2025' ),
			'slug'  => 'podcast',
		),
		array(
			'label' => __( 'Book', 'ewo-2025' ),
			'slug'  => 'book',
		),
		array(
			'label' => __( 'About', 'ewo-2025' ),
			'slug'  => 'about',
		),
	);

	echo '<ul id="primary-menu" class="primary-menu menu">';

	foreach ( $items as $item ) {
		$url      = isset( $item['url'] ) ? $item['url'] : '';
		$is_active = false;

		if ( '' === $url && isset( $item['slug'] ) ) {
			$page = get_page_by_path( $item['slug'] );
			$url  = $page ? get_permalink( $page ) : home_url( '/' . trim( $item['slug'], '/' ) . '/' );
			$is_active = is_page( $item['slug'] );
		} elseif ( isset( $item['url'] ) ) {
			$is_active = is_front_page() || is_home();
		}

		printf(
			'<li class="menu-item%3$s"><a href="%1$s">%2$s</a></li>',
			esc_url( $url ),
			esc_html( $item['label'] ),
			$is_active ? ' current-menu-item' : ''
		);
	}

	echo '</ul>';
}

/**
 * Enqueue theme assets.
 */
function ewo_2025_scripts() {
	wp_enqueue_style( 'ewo-2025-style', get_stylesheet_uri(), array(), EWO_2025_VERSION );
	wp_enqueue_style( 'ewo-2025-main', get_template_directory_uri() . '/assets/css/main.css', array( 'ewo-2025-style' ), EWO_2025_VERSION );
	wp_enqueue_script( 'ewo-2025-main', get_template_directory_uri() . '/assets/js/main.js', array(), EWO_2025_VERSION, true );
}
add_action( 'wp_enqueue_scripts', 'ewo_2025_scripts' );



/**
 * Display theme version in the admin bar for administrators.
 *
 * @param WP_Admin_Bar $wp_admin_bar Admin bar instance.
 */
function ewo_2025_admin_bar_theme_version( $wp_admin_bar ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$wp_admin_bar->add_node(
		array(
			'id'    => 'ewo-2025-theme-version',
			'title' => sprintf(
				/* translators: 1: Theme semantic version. 2: Build ID. */
				esc_html__( 'EWO Theme v%1$s / Build %2$s', 'ewo-2025' ),
				esc_html( EWO_THEME_VERSION ),
				esc_html( EWO_THEME_BUILD )
			),
			'href'  => admin_url( 'customize.php' ),
			'meta'  => array(
				'title' => esc_attr__( 'EWO 2025 theme version', 'ewo-2025' ),
			),
		)
	);
}
add_action( 'admin_bar_menu', 'ewo_2025_admin_bar_theme_version', 100 );

/**
 * Replace the default "Category: Analysis" archive title with a branded one.
 *
 * @param string $title Default archive title.
 * @return string
 */
function ewo_2025_archive_title( $title ) {
	if ( is_category( 'analysis' ) ) {
		return esc_html__( 'Latest Analysis', 'ewo-2025' );
	}

	return $title;
}
add_filter( 'get_the_archive_title', 'ewo_2025_archive_title' );

// Drop the "Category:" / "Tag:" etc. prefix from all archive titles.
add_filter( 'get_the_archive_title_prefix', '__return_empty_string' );

/**
 * Resolve the best card thumbnail URL for a post.
 *
 * Featured image first, then the first valid image found in the content,
 * then the EWO fallback image. Never returns an empty string.
 *
 * @param int|WP_Post|null $post Post.
 * @return string
 */
function ewo_2025_card_thumbnail_url( $post = null ) {
	$post = get_post( $post );

	if ( $post ) {
		if ( has_post_thumbnail( $post ) ) {
			$featured = get_the_post_thumbnail_url( $post, 'large' );
			if ( ewo_2025_is_valid_image_url( $featured ) ) {
				return $featured;
			}
		}

		$from_content = ewo_2025_first_content_image_url( $post->post_content );
		if ( '' !== $from_content ) {
			return $from_content;
		}
	}

	return ewo_2025_fallback_image_url();
}

/**
 * EWO fallback card image URL.
 *
 * @return string
 */
function ewo_2025_fallback_image_url() {
	return get_template_directory_uri() . '/assets/images/ewo-banner.png';
}

/**
 * Extract the first valid image URL from post content.
 *
 * Supports src, data-src/data-lazy-src/data-original, srcset, and Substack CDN URLs.
 *
 * @param string $content Post content.
 * @return string
 */
function ewo_2025_first_content_image_url( $content ) {
	if ( ! is_string( $content ) || false === stripos( $content, '<img' ) ) {
		return '';
	}

	if ( ! preg_match_all( '/<img\b[^>]*>/i', $content, $tags ) ) {
		return '';
	}

	foreach ( $tags[0] as $tag ) {
		foreach ( array( 'data-src', 'data-lazy-src', 'data-original' ) as $attr ) {
			if ( preg_match( '/\b' . preg_quote( $attr, '/' ) . '\s*=\s*["\']([^"\']+)["\']/i', $tag, $m ) && ewo_2025_is_valid_image_url( $m[1] ) ) {
				return trim( $m[1] );
			}
		}

		if ( preg_match( '/\bsrcset\s*=\s*["\']([^"\']+)["\']/i', $tag, $m ) ) {
			$first = explode( ',', $m[1] );
			$first = trim( explode( ' ', trim( $first[0] ) )[0] );
			if ( ewo_2025_is_valid_image_url( $first ) ) {
				return $first;
			}
		}

		if ( preg_match( '/\bsrc\s*=\s*["\']([^"\']+)["\']/i', $tag, $m ) && ewo_2025_is_valid_image_url( $m[1] ) ) {
			return trim( $m[1] );
		}
	}

	return '';
}

/**
 * Validate an image URL: http(s) and either a real image extension or a known image CDN.
 *
 * @param string $url URL.
 * @return bool
 */
function ewo_2025_is_valid_image_url( $url ) {
	$url = trim( (string) $url );

	if ( '' === $url || 0 === stripos( $url, 'data:' ) ) {
		return false;
	}

	if ( ! preg_match( '#^https?://#i', $url ) ) {
		return false;
	}

	if ( preg_match( '#\.(jpe?g|png|gif|webp|avif)(\?.*)?$#i', $url ) ) {
		return true;
	}

	$cdns = array( 'substackcdn.com', 'substack-post-media', 'amazonaws.com', 'bucketeer-', 'cdn.substack.com', 'ytimg.com', 'img.youtube.com' );
	foreach ( $cdns as $cdn ) {
		if ( false !== stripos( $url, $cdn ) ) {
			return true;
		}
	}

	return false;
}

if ( ! function_exists( 'ewo_2025_substack_source_url' ) ) {
	/**
	 * Return the original imported Substack URL for a post.
	 *
	 * @param int|WP_Post|null $post Post.
	 * @return string
	 */
	function ewo_2025_substack_source_url( $post = null ) {
		$post = get_post( $post );

		if ( ! $post ) {
			return '';
		}

		foreach ( array( '_ewo_rss_article_url', 'feedzy_item_url', '_ewo_rss_source_url', 'ewo_rss_source_url', 'ewo_original_url', 'original_url', 'source_url' ) as $meta_key ) {
			$url = trim( (string) get_post_meta( $post->ID, $meta_key, true ) );

			if ( $url && false !== stripos( $url, 'substack.com' ) ) {
				return esc_url_raw( $url );
			}
		}

		return '';
	}
}

if ( ! function_exists( 'ewo_2025_subscriber_preview_text' ) ) {
	/**
	 * Return preview text without Substack's placeholder "Read more" copy.
	 *
	 * @param int|WP_Post|null $post Post.
	 * @return string
	 */
	function ewo_2025_subscriber_preview_text( $post = null ) {
		$post = get_post( $post );

		if ( ! $post ) {
			return '';
		}

		$content = wp_strip_all_tags( strip_shortcodes( $post->post_content ) );
		$content = preg_replace( '/\bRead more\b/i', '', $content );
		$content = preg_replace( '/\s+/', ' ', trim( (string) $content ) );

		return $content;
	}
}

if ( ! function_exists( 'ewo_2025_is_subscriber_only_post' ) ) {
	/**
	 * Detect subscriber-only Substack imports.
	 *
	 * @param int|WP_Post|null $post Post.
	 * @return bool
	 */
	function ewo_2025_is_subscriber_only_post( $post = null ) {
		$post = get_post( $post );

		if ( ! $post ) {
			return false;
		}

		// Prefer the flag detected at import time (no runtime guessing).
		$flag = get_post_meta( $post->ID, '_ewo_rss_is_subscriber_only', true );
		if ( '' !== $flag ) {
			return '1' === $flag;
		}

		if ( ! ewo_2025_substack_source_url( $post ) ) {
			return false;
		}

		$text = wp_strip_all_tags( strip_shortcodes( $post->post_content ) );

		if ( false !== stripos( $text, 'Read more' ) ) {
			return true;
		}

		return str_word_count( $text ) <= 80;
	}
}

/* ==========================================================================
   Strategic Domains public page routing
   ========================================================================== */

/**
 * Register the ewo_domain_slug query variable so WP passes it through.
 *
 * @param string[] $vars Registered query vars.
 * @return string[]
 */
function ewo_2025_sfd_query_vars( $vars ) {
	$vars[] = 'ewo_domain_slug';
	return $vars;
}
add_filter( 'query_vars', 'ewo_2025_sfd_query_vars' );

/**
 * Add a rewrite rule so /strategic-domains/{slug}/ resolves to the page
 * template with the ewo_domain_slug var set.
 *
 * Must run on 'init' so the main page already exists and has its ID.
 */
function ewo_2025_sfd_rewrite_rules() {
	$page = get_page_by_path( 'strategic-domains' );
	if ( $page ) {
		add_rewrite_rule(
			'^strategic-domains/([^/]+)/?$',
			'index.php?page_id=' . $page->ID . '&ewo_domain_slug=$matches[1]',
			'top'
		);
	}
}
add_action( 'init', 'ewo_2025_sfd_rewrite_rules' );

/**
 * Auto-create the /strategic-domains/ page on first activation so the URL
 * resolves immediately without manual setup in WP Admin → Pages.
 *
 * Tracked by a simple option so it only runs once.
 */
function ewo_2025_maybe_create_sfd_page() {
	if ( get_option( 'ewo_2025_sfd_page_v1' ) ) {
		return;
	}

	if ( ! get_page_by_path( 'strategic-domains' ) ) {
		wp_insert_post(
			array(
				'post_title'   => __( 'Strategic Domains', 'ewo-2025' ),
				'post_name'    => 'strategic-domains',
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => '',
			)
		);
		flush_rewrite_rules( false );
	}

	update_option( 'ewo_2025_sfd_page_v1', true, false );
}
add_action( 'after_setup_theme', 'ewo_2025_maybe_create_sfd_page' );

/**
 * Enqueue the strategic-domains stylesheet only on the relevant pages.
 */
function ewo_2025_sfd_enqueue_styles() {
	if ( is_page( 'strategic-domains' ) || '' !== (string) get_query_var( 'ewo_domain_slug' ) ) {
		wp_enqueue_style(
			'ewo-strategic-domains',
			get_template_directory_uri() . '/assets/css/strategic-domains.css',
			array( 'ewo-2025-main' ),
			EWO_2025_VERSION
		);
	}
}
add_action( 'wp_enqueue_scripts', 'ewo_2025_sfd_enqueue_styles' );

/* ==========================================================================
   Smart Feed public page
   ========================================================================== */

/**
 * Auto-create the /smart-feed/ page on first run.
 */
function ewo_2025_maybe_create_smart_feed_page() {
	if ( get_option( 'ewo_2025_sf_page_v1' ) ) {
		return;
	}
	if ( ! get_page_by_path( 'smart-feed' ) ) {
		wp_insert_post(
			array(
				'post_title'   => __( 'Smart Feed', 'ewo-2025' ),
				'post_name'    => 'smart-feed',
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => '',
			)
		);
		flush_rewrite_rules( false );
	}
	update_option( 'ewo_2025_sf_page_v1', true, false );
}
add_action( 'after_setup_theme', 'ewo_2025_maybe_create_smart_feed_page' );

/**
 * Enqueue Smart Feed stylesheet + script on both /smart-feed/ and /smartfeed/.
 */
function ewo_2025_sf_enqueue_assets() {
	if ( is_page( 'smart-feed' ) || is_page( 'smartfeed' ) ) {
		wp_enqueue_style(
			'ewo-smart-feed',
			get_template_directory_uri() . '/assets/css/smart-feed.css',
			array( 'ewo-2025-main' ),
			EWO_2025_VERSION
		);
		wp_enqueue_script(
			'ewo-smart-feed',
			get_template_directory_uri() . '/assets/js/smart-feed.js',
			array(),
			EWO_2025_VERSION,
			true
		);
	}
}
add_action( 'wp_enqueue_scripts', 'ewo_2025_sf_enqueue_assets' );

/* ==========================================================================
   Public Predictions page routing
   ========================================================================== */

/**
 * Register ewo_prediction_id query var for detail URLs.
 *
 * @param string[] $vars Registered vars.
 * @return string[]
 */
function ewo_2025_pred_query_vars( $vars ) {
	$vars[] = 'ewo_prediction_id';
	return $vars;
}
add_filter( 'query_vars', 'ewo_2025_pred_query_vars' );

/**
 * Rewrite rule: /predictions/{id}/ → page template with ewo_prediction_id.
 */
function ewo_2025_pred_rewrite_rules() {
	$page = get_page_by_path( 'predictions' );
	if ( $page ) {
		add_rewrite_rule(
			'^predictions/(\d+)/?$',
			'index.php?page_id=' . $page->ID . '&ewo_prediction_id=$matches[1]',
			'top'
		);
	}
}
add_action( 'init', 'ewo_2025_pred_rewrite_rules' );

/**
 * Auto-create the /predictions/ page on first load.
 */
function ewo_2025_maybe_create_predictions_page() {
	if ( get_option( 'ewo_2025_predictions_page_v1' ) ) {
		return;
	}
	if ( ! get_page_by_path( 'predictions' ) ) {
		wp_insert_post( array(
			'post_title'   => __( 'Strategic Predictions', 'ewo-2025' ),
			'post_name'    => 'predictions',
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_content' => '',
		) );
		flush_rewrite_rules( false );
	}
	update_option( 'ewo_2025_predictions_page_v1', true, false );
}
add_action( 'after_setup_theme', 'ewo_2025_maybe_create_predictions_page' );

/* ==========================================================================
   Public Community Wall page routing
   ========================================================================== */

/**
 * Register query vars for Community Wall detail and category URLs.
 *
 * @param string[] $vars Registered vars.
 * @return string[]
 */
function ewo_2025_cw_query_vars( $vars ) {
	$vars[] = 'ewo_cw_slug';
	$vars[] = 'ewo_cw_cat_slug';
	return $vars;
}
add_filter( 'query_vars', 'ewo_2025_cw_query_vars' );

/**
 * Rewrite rules for Community Wall URLs.
 *
 * Category rule is added last so it ends up FIRST in the rules array
 * (both use 'top', last-registered wins), avoiding the catch-all slug
 * rule from swallowing category URLs.
 */
function ewo_2025_cw_rewrite_rules() {
	$page = get_page_by_path( 'community-wall' );
	if ( ! $page ) {
		return;
	}
	$pid = $page->ID;
	// Post slug rule (added first → will be lower in priority).
	add_rewrite_rule(
		'^community-wall/([^/]+)/?$',
		'index.php?page_id=' . $pid . '&ewo_cw_slug=$matches[1]',
		'top'
	);
	// Category rule (added second → prepended above post slug rule).
	add_rewrite_rule(
		'^community-wall/category/([^/]+)/?$',
		'index.php?page_id=' . $pid . '&ewo_cw_cat_slug=$matches[1]',
		'top'
	);
}
add_action( 'init', 'ewo_2025_cw_rewrite_rules' );

/**
 * Auto-create the /community-wall/ page on first load.
 */
function ewo_2025_maybe_create_community_wall_page() {
	if ( get_option( 'ewo_2025_cw_page_v1' ) ) {
		return;
	}
	if ( ! get_page_by_path( 'community-wall' ) ) {
		wp_insert_post( array(
			'post_title'   => __( 'Community Wall', 'ewo-2025' ),
			'post_name'    => 'community-wall',
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_content' => '',
		) );
		flush_rewrite_rules( false );
	}
	update_option( 'ewo_2025_cw_page_v1', true, false );
}
add_action( 'after_setup_theme', 'ewo_2025_maybe_create_community_wall_page' );

/**
 * Enqueue Community Wall CSS on /community-wall/ and /community-wall/{slug}/.
 */
function ewo_2025_cw_enqueue_styles() {
	if ( is_page( 'community-wall' ) || '' !== (string) get_query_var( 'ewo_cw_slug' ) || '' !== (string) get_query_var( 'ewo_cw_cat_slug' ) ) {
		wp_enqueue_style(
			'ewo-community-wall-front',
			get_template_directory_uri() . '/assets/css/community-wall.css',
			array( 'ewo-2025-main' ),
			EWO_2025_VERSION
		);
	}
}
add_action( 'wp_enqueue_scripts', 'ewo_2025_cw_enqueue_styles' );

/* ==========================================================================
   End Community Wall routing
   ========================================================================== */

/**
 * Enqueue predictions CSS on /predictions/ and /predictions/{id}/.
 */
function ewo_2025_pred_enqueue_styles() {
	if ( is_page( 'predictions' ) || '' !== (string) get_query_var( 'ewo_prediction_id' ) ) {
		wp_enqueue_style(
			'ewo-predictions-front',
			get_template_directory_uri() . '/assets/css/predictions.css',
			array( 'ewo-2025-main' ),
			EWO_2025_VERSION
		);
	}
}
add_action( 'wp_enqueue_scripts', 'ewo_2025_pred_enqueue_styles' );

/* ==========================================================================
   Book public page
   ========================================================================== */

/**
 * Auto-create the /book/ page so the menu item resolves immediately.
 */
function ewo_2025_maybe_create_book_page() {
	if ( get_option( 'ewo_2025_book_page_v1' ) ) {
		return;
	}
	if ( ! get_page_by_path( 'book' ) ) {
		wp_insert_post(
			array(
				'post_title'   => __( 'Book', 'ewo-2025' ),
				'post_name'    => 'book',
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => '',
			)
		);
		flush_rewrite_rules( false );
	}
	update_option( 'ewo_2025_book_page_v1', true, false );
}
add_action( 'after_setup_theme', 'ewo_2025_maybe_create_book_page' );

/**
 * Enqueue book page stylesheet only on /book/.
 */
function ewo_2025_book_enqueue_styles() {
	if ( is_page( 'book' ) ) {
		wp_enqueue_style(
			'ewo-book-front',
			get_template_directory_uri() . '/assets/css/book.css',
			array( 'ewo-2025-main' ),
			EWO_2025_VERSION
		);
	}
}
add_action( 'wp_enqueue_scripts', 'ewo_2025_book_enqueue_styles' );