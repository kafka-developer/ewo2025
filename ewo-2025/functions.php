<?php
/**
 * EWO 2025 theme functions.
 *
 * @package EWO_2025
 */

if ( ! defined( 'EWO_THEME_VERSION' ) ) {
	define( 'EWO_THEME_VERSION', '0.3.1' );
}

if ( ! defined( 'EWO_2025_VERSION' ) ) {
	define( 'EWO_2025_VERSION', EWO_THEME_VERSION );
}

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
 * Return supported EWO platform settings.
 *
 * @return array<string, array<string, string>>
 */
function ewo_2025_get_platforms() {
	return array(
		'youtube'     => array(
			'label' => __( 'YouTube', 'ewo-2025' ),
			'short' => __( 'YT', 'ewo-2025' ),
		),
		'spotify'     => array(
			'label' => __( 'Spotify', 'ewo-2025' ),
			'short' => __( 'SP', 'ewo-2025' ),
		),
		'substack'    => array(
			'label' => __( 'Substack', 'ewo-2025' ),
			'short' => __( 'SS', 'ewo-2025' ),
		),
		'x'           => array(
			'label' => __( 'X', 'ewo-2025' ),
			'short' => __( 'X', 'ewo-2025' ),
		),
		'rumble'      => array(
			'label' => __( 'Rumble', 'ewo-2025' ),
			'short' => __( 'RB', 'ewo-2025' ),
		),
		'tiktok'      => array(
			'label' => __( 'TikTok', 'ewo-2025' ),
			'short' => __( 'TK', 'ewo-2025' ),
		),
		'amazon_book' => array(
			'label' => __( 'Amazon Book', 'ewo-2025' ),
			'short' => __( 'BK', 'ewo-2025' ),
		),
		'newsletter'  => array(
			'label' => __( 'Newsletter', 'ewo-2025' ),
			'short' => __( 'NL', 'ewo-2025' ),
		),
	);
}

/**
 * Register EWO platform URL settings in the Customizer.
 *
 * @param WP_Customize_Manager $wp_customize Customizer object.
 */
function ewo_2025_customize_register( $wp_customize ) {
	$wp_customize->add_section(
		'ewo_2025_platform_settings',
		array(
			'title'       => esc_html__( 'EWO Platform Settings', 'ewo-2025' ),
			'description' => esc_html__( 'Add platform URLs used across the header, footer, homepage CTAs, and social link areas. Empty URLs are hidden.', 'ewo-2025' ),
			'priority'    => 160,
		)
	);

	foreach ( ewo_2025_get_platforms() as $ewo_2025_key => $ewo_2025_platform ) {
		$ewo_2025_setting = 'ewo_2025_' . $ewo_2025_key . '_url';

		$wp_customize->add_setting(
			$ewo_2025_setting,
			array(
				'default'           => '',
				'sanitize_callback' => 'esc_url_raw',
			)
		);

		$wp_customize->add_control(
			$ewo_2025_setting,
			array(
				'label'       => sprintf(
					/* translators: %s: Platform name. */
					esc_html__( '%s URL', 'ewo-2025' ),
					$ewo_2025_platform['label']
				),
				'section'     => 'ewo_2025_platform_settings',
				'type'        => 'url',
				'input_attrs' => array(
					'placeholder' => 'https://',
				),
			)
		);
	}
}
add_action( 'customize_register', 'ewo_2025_customize_register' );

/**
 * Get a configured platform URL.
 *
 * @param string $platform Platform key.
 * @return string
 */
function ewo_2025_get_platform_url( $platform ) {
	$platforms = ewo_2025_get_platforms();

	if ( ! isset( $platforms[ $platform ] ) ) {
		return '';
	}

	return trim( (string) get_theme_mod( 'ewo_2025_' . $platform . '_url', '' ) );
}



/**
 * Determine whether any configured platform URLs exist.
 *
 * @param string[] $platform_keys Platform keys to check.
 * @return bool
 */
function ewo_2025_has_platform_links( $platform_keys = array() ) {
	foreach ( $platform_keys as $platform_key ) {
		if ( '' !== ewo_2025_get_platform_url( $platform_key ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Print configured platform links.
 *
 * @param string[] $platform_keys Platform keys to render.
 * @param string   $class_name    Block class name.
 */
function ewo_2025_platform_links( $platform_keys = array(), $class_name = 'ewo-platform-links' ) {
	$platforms = ewo_2025_get_platforms();
	$links     = array();

	foreach ( $platform_keys as $platform_key ) {
		$url = ewo_2025_get_platform_url( $platform_key );

		if ( '' === $url || ! isset( $platforms[ $platform_key ] ) ) {
			continue;
		}

		$links[] = array(
			'url'   => $url,
			'label' => $platforms[ $platform_key ]['label'],
			'short' => $platforms[ $platform_key ]['short'],
		);
	}

	if ( empty( $links ) ) {
		return;
	}
	?>
	<div class="<?php echo esc_attr( $class_name ); ?>" aria-label="<?php esc_attr_e( 'EWO platform links', 'ewo-2025' ); ?>">
		<?php foreach ( $links as $link ) : ?>
			<a href="<?php echo esc_url( $link['url'] ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr( $link['label'] ); ?>">
				<span aria-hidden="true"><?php echo esc_html( $link['short'] ); ?></span>
				<span class="ewo-platform-links__label"><?php echo esc_html( $link['label'] ); ?></span>
			</a>
		<?php endforeach; ?>
	</div>
	<?php
}
