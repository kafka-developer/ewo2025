<?php
/**
 * EWO 2025 theme functions.
 *
 * @package EWO_2025
 */

if ( ! defined( 'EWO_THEME_VERSION' ) ) {
	define( 'EWO_THEME_VERSION', '0.3.7' );
}

if ( ! defined( 'EWO_2025_VERSION' ) ) {
	define( 'EWO_2025_VERSION', EWO_THEME_VERSION );
}

// Homepage content types and data providers.
require_once get_template_directory() . '/inc/ewo-content-types.php';
require_once get_template_directory() . '/inc/ewo-homepage.php';
require_once get_template_directory() . '/inc/ewo-social-links.php';
require_once get_template_directory() . '/inc/ewo-sidebar.php';

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
				/* translators: %s: Theme version number. */
				esc_html__( 'EWO Theme v%s', 'ewo-2025' ),
				esc_html( EWO_THEME_VERSION )
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