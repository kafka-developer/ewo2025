<?php
/** Shared EWO social links. @package EWO_2025 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
const EWO_2025_SOCIAL_LINKS_OPTION = 'ewo_2025_social_links';

function ewo_2025_get_social_platforms() {
	$icons = array(
		'youtube' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.96-1.96C18.85 4 12 4 12 4s-6.85 0-8.58.46a2.78 2.78 0 0 0-1.96 1.96A29 29 0 0 0 1 12a29 29 0 0 0 .46 5.58 2.78 2.78 0 0 0 1.96 1.96C5.15 20 12 20s6.85 0 8.58-.46a2.78 2.78 0 0 0 1.96-1.96A29 29 0 0 0 23 12a29 29 0 0 0-.46-5.58Z"/><path d="m10 15 5.2-3L10 9v6Z" fill="#071426"/></svg>',
		'substack' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 3h16v2.6H4V3Zm0 4.4h16V10H4V7.4Zm0 4.4h16V21l-8-4.4L4 21v-9.2Z"/></svg>',
		'spotify' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm4.58 14.43a.76.76 0 0 1-1.04.25c-2.85-1.74-6.44-2.13-10.66-1.17a.76.76 0 0 1-.34-1.49c4.62-1.05 8.6-.6 11.79 1.35.36.22.47.7.25 1.06Zm1.22-2.72a.96.96 0 0 1-1.32.32c-3.26-2-8.24-2.58-12.1-1.41a.96.96 0 1 1-.56-1.84c4.42-1.34 9.91-.69 13.66 1.61.45.28.6.87.32 1.32Zm.1-2.84C14 8.56 7.58 8.35 3.86 9.48a1.15 1.15 0 1 1-.67-2.2c4.27-1.3 11.38-1.05 15.88 1.62a1.15 1.15 0 0 1-1.17 1.97Z"/></svg>',
		'x' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M17.58 3h3.05l-6.66 7.62L21.8 21h-6.13l-4.8-6.28L5.38 21H2.31l7.13-8.15L1.93 3h6.28l4.34 5.74L17.58 3Zm-1.07 16.17h1.69L7.29 4.73H5.48l11.03 14.44Z"/></svg>',
		'rumble' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7.1 3.2c-.85-.5-1.92.11-1.92 1.1v15.4c0 .99 1.07 1.6 1.92 1.1l13.17-7.7a1.28 1.28 0 0 0 0-2.2L7.1 3.2Zm2.08 5.05L15.6 12l-6.42 3.75v-7.5Z"/></svg>',
		'amazon_book' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4.2C5 3.54 5.54 3 6.2 3H20v15.8H6.45A1.45 1.45 0 0 0 5 20.25V4.2Zm2.3 1.3v10.9H18V5.5H7.3ZM4 20.25A2.75 2.75 0 0 1 6.75 17.5H20V21H6.75A2.75 2.75 0 0 1 4 20.25Z"/></svg>',
	);
	return array(
		'youtube' => array( 'label' => __( 'YouTube', 'ewo-2025' ), 'short' => 'YT', 'detail' => __( 'Watch & Subscribe', 'ewo-2025' ), 'class' => 'youtube', 'icon' => $icons['youtube'] ),
		'substack' => array( 'label' => __( 'Substack', 'ewo-2025' ), 'short' => 'SS', 'detail' => __( 'Read Analysis', 'ewo-2025' ), 'class' => 'substack', 'icon' => $icons['substack'] ),
		'spotify' => array( 'label' => __( 'Spotify', 'ewo-2025' ), 'short' => 'SP', 'detail' => __( 'Listen to Podcasts', 'ewo-2025' ), 'class' => 'spotify', 'icon' => $icons['spotify'] ),
		'x' => array( 'label' => __( 'X / Twitter', 'ewo-2025' ), 'short' => 'X', 'detail' => __( 'Latest Updates', 'ewo-2025' ), 'class' => 'x', 'icon' => $icons['x'] ),
		'rumble' => array( 'label' => __( 'Rumble', 'ewo-2025' ), 'short' => 'RB', 'detail' => __( 'Watch on Rumble', 'ewo-2025' ), 'class' => 'rumble', 'icon' => $icons['rumble'] ),
		'amazon_book' => array( 'label' => __( 'Amazon Book', 'ewo-2025' ), 'short' => 'BK', 'detail' => __( 'Our Book', 'ewo-2025' ), 'class' => 'amazon', 'icon' => $icons['amazon_book'] ),
	);
}

function ewo_2025_sanitize_social_links( $input ) {
	$input = is_array( $input ) ? $input : array(); $output = array();
	foreach ( ewo_2025_get_social_platforms() as $key => $platform ) {
		$output[ $key ] = isset( $input[ $key ] ) ? esc_url_raw( trim( wp_unslash( $input[ $key ] ) ) ) : '';
	}
	return $output;
}
function ewo_2025_get_social_links() {
	$saved = get_option( EWO_2025_SOCIAL_LINKS_OPTION, array() );
	return ewo_2025_sanitize_social_links( is_array( $saved ) ? $saved : array() );
}
function ewo_2025_migrate_social_links() {
	if ( false !== get_option( EWO_2025_SOCIAL_LINKS_OPTION, false ) ) { return; }
	$links = array();
	foreach ( ewo_2025_get_social_platforms() as $key => $platform ) {
		$links[ $key ] = esc_url_raw( (string) get_theme_mod( 'ewo_2025_' . $key . '_url', '' ) );
		remove_theme_mod( 'ewo_2025_' . $key . '_url' );
	}
	add_option( EWO_2025_SOCIAL_LINKS_OPTION, $links );
}
add_action( 'after_setup_theme', 'ewo_2025_migrate_social_links', 5 );

function ewo_2025_get_platform_url( $platform ) {
	if ( 'newsletter' === $platform ) { return trim( (string) get_theme_mod( 'ewo_2025_newsletter_url', '' ) ); }
	$links = ewo_2025_get_social_links();
	return isset( $links[ $platform ] ) ? $links[ $platform ] : '';
}
function ewo_2025_has_platform_links( $keys = array() ) {
	foreach ( $keys as $key ) { if ( ewo_2025_get_platform_url( $key ) ) { return true; } }
	return false;
}
function ewo_2025_social_links( $keys = array(), $context = 'compact' ) {
	$platforms = ewo_2025_get_social_platforms(); $links = ewo_2025_get_social_links();
	$allowed = array( 'svg' => array( 'viewbox' => true, 'aria-hidden' => true ), 'path' => array( 'd' => true, 'fill' => true ) );
	foreach ( $keys as $key ) {
		if ( empty( $links[ $key ] ) || empty( $platforms[ $key ] ) ) { continue; }
		$p = $platforms[ $key ];
		if ( 'footer' === $context ) { ?>
			<a class="ewo-footer-platform ewo-footer-platform--<?php echo esc_attr( $p['class'] ); ?>" href="<?php echo esc_url( $links[ $key ] ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr( $p['label'] ); ?>"><span class="ewo-footer-platform__icon"><?php echo wp_kses( $p['icon'], $allowed ); ?></span><span class="ewo-footer-platform__text"><span class="ewo-footer-platform__name"><?php echo esc_html( $p['label'] ); ?></span><span class="ewo-footer-platform__label"><?php echo esc_html( $p['detail'] ); ?></span></span></a>
		<?php } elseif ( 'sidebar' === $context ) { ?>
			<a class="ewo-sidebar-follow__link ewo-sidebar-follow--<?php echo esc_attr( $p['class'] ); ?>" href="<?php echo esc_url( $links[ $key ] ); ?>" target="_blank" rel="noopener noreferrer"><span class="ewo-sidebar-follow__icon"><?php echo wp_kses( $p['icon'], $allowed ); ?></span><span class="ewo-sidebar-follow__name"><?php echo esc_html( $p['label'] ); ?></span></a>
		<?php } else { ?>
			<a href="<?php echo esc_url( $links[ $key ] ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr( $p['label'] ); ?>"><span aria-hidden="true"><?php echo esc_html( $p['short'] ); ?></span><span class="ewo-platform-links__label"><?php echo esc_html( $p['label'] ); ?></span></a>
		<?php }
	}
}
function ewo_2025_platform_links( $keys = array(), $class = 'ewo-platform-links' ) {
	if ( ! ewo_2025_has_platform_links( $keys ) ) { return; }
	echo '<div class="' . esc_attr( $class ) . '" aria-label="' . esc_attr__( 'EWO platform links', 'ewo-2025' ) . '">';
	ewo_2025_social_links( $keys ); echo '</div>';
}

function ewo_2025_register_social_settings() {
	register_setting( 'ewo_2025_social_links_group', EWO_2025_SOCIAL_LINKS_OPTION, array( 'type' => 'array', 'sanitize_callback' => 'ewo_2025_sanitize_social_links', 'default' => array() ) );
}
add_action( 'admin_init', 'ewo_2025_register_social_settings' );
function ewo_2025_social_settings_menu() {
	add_menu_page( __( 'EWO Settings', 'ewo-2025' ), __( 'EWO Settings', 'ewo-2025' ), 'manage_options', 'ewo-settings', 'ewo_2025_render_social_settings_page', 'dashicons-admin-generic', 61 );
	add_submenu_page( 'ewo-settings', __( 'Social Links', 'ewo-2025' ), __( 'Social Links', 'ewo-2025' ), 'manage_options', 'ewo-settings', 'ewo_2025_render_social_settings_page' );
}
add_action( 'admin_menu', 'ewo_2025_social_settings_menu' );
function ewo_2025_render_social_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) { return; } $links = ewo_2025_get_social_links(); ?>
	<div class="wrap"><h1><?php esc_html_e( 'EWO Settings — Social Links', 'ewo-2025' ); ?></h1><p><?php esc_html_e( 'These URLs power the homepage Follow EWO card and footer. Empty platforms are hidden.', 'ewo-2025' ); ?></p><form action="options.php" method="post"><?php settings_fields( 'ewo_2025_social_links_group' ); ?><table class="form-table" role="presentation">
	<?php foreach ( ewo_2025_get_social_platforms() as $key => $platform ) : ?><tr><th scope="row"><label for="ewo-social-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $platform['label'] . ' URL' ); ?></label></th><td><input class="regular-text" type="url" id="ewo-social-<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( EWO_2025_SOCIAL_LINKS_OPTION ); ?>[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $links[ $key ] ); ?>" placeholder="https://" /></td></tr><?php endforeach; ?>
	</table><?php submit_button(); ?></form></div><?php
}
