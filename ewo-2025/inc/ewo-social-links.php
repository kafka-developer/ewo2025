<?php
/** Shared EWO social / platform links. @package EWO_2025 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
const EWO_2025_SOCIAL_LINKS_OPTION = 'ewo_2025_social_links';

/**
 * Platform registry — the single source of truth for platform metadata.
 *
 * Admin controls each link's `url` plus three per-link toggles stored in the
 * EWO_2025_SOCIAL_LINKS_OPTION option: `enabled` (master on/off), `footer`
 * (show in the footer) and `sidecard` (show in the sidebar Follow card). The
 * Footer and Side Card share the same URL — placement is per link.
 *
 * Everything else here is seed / fallback data:
 *  - seed_url        : used when the admin URL is blank, so the UI still renders.
 *  - card / icon_row : where the platform appears *within* the footer (a large
 *                      card, a small icon button, or both). Design property.
 *  - default_*       : default toggle values used until an admin saves.
 */
function ewo_2025_get_social_platforms() {
	$icons = array(
		'youtube'  => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.96-1.96C18.85 4 12 4 12 4s-6.85 0-8.58.46a2.78 2.78 0 0 0-1.96 1.96A29 29 0 0 0 1 12a29 29 0 0 0 .46 5.58 2.78 2.78 0 0 0 1.96 1.96C5.15 20 12 20 12 20s6.85 0 8.58-.46a2.78 2.78 0 0 0 1.96-1.96A29 29 0 0 0 23 12a29 29 0 0 0-.46-5.58Z"/><path d="m10 15 5.2-3L10 9v6Z" fill="#071426"/></svg>',
		'substack' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 3h16v2.6H4V3Zm0 4.4h16V10H4V7.4Zm0 4.4h16V21l-8-4.4L4 21v-9.2Z"/></svg>',
		'spotify'  => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm4.58 14.43a.76.76 0 0 1-1.04.25c-2.85-1.74-6.44-2.13-10.66-1.17a.76.76 0 0 1-.34-1.49c4.62-1.05 8.6-.6 11.79 1.35.36.22.47.7.25 1.06Zm1.22-2.72a.96.96 0 0 1-1.32.32c-3.26-2-8.24-2.58-12.1-1.41a.96.96 0 1 1-.56-1.84c4.42-1.34 9.91-.69 13.66 1.61.45.28.6.87.32 1.32Zm.1-2.84C14 8.56 7.58 8.35 3.86 9.48a1.15 1.15 0 1 1-.67-2.2c4.27-1.3 11.38-1.05 15.88 1.62a1.15 1.15 0 0 1-1.17 1.97Z"/></svg>',
		'x'        => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M17.58 3h3.05l-6.66 7.62L21.8 21h-6.13l-4.8-6.28L5.38 21H2.31l7.13-8.15L1.93 3h6.28l4.34 5.74L17.58 3Zm-1.07 16.17h1.69L7.29 4.73H5.48l11.03 14.44Z"/></svg>',
		'rumble'   => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7.1 3.2c-.85-.5-1.92.11-1.92 1.1v15.4c0 .99 1.07 1.6 1.92 1.1l13.17-7.7a1.28 1.28 0 0 0 0-2.2L7.1 3.2Zm2.08 5.05L15.6 12l-6.42 3.75v-7.5Z"/></svg>',
		'tiktok'   => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16.6 5.82A4.28 4.28 0 0 1 15.54 3h-3.1v12.4a2.6 2.6 0 1 1-2.6-2.6c.27 0 .52.04.77.11V9.74a5.85 5.85 0 0 0-.77-.05A5.7 5.7 0 1 0 15.54 15V8.9a7.3 7.3 0 0 0 4.27 1.37V7.16a4.28 4.28 0 0 1-3.21-1.34Z"/></svg>',
		'amazon'   => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4.2C5 3.54 5.54 3 6.2 3H20v15.8H6.45A1.45 1.45 0 0 0 5 20.25V4.2Zm2.3 1.3v10.9H18V5.5H7.3ZM4 20.25A2.75 2.75 0 0 1 6.75 17.5H20V21H6.75A2.75 2.75 0 0 1 4 20.25Z"/></svg>',
		'telegram' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21.94 4.3 18.9 19.1c-.23 1.02-.84 1.27-1.7.79l-4.7-3.46-2.27 2.18c-.25.25-.46.46-.94.46l.33-4.78 8.7-7.86c.38-.34-.08-.53-.59-.19L6.78 13.2l-4.64-1.45c-1.01-.32-1.03-1.01.21-1.5l18.14-7c.84-.31 1.58.2 1.45 1.05Z"/></svg>',
		'linkedin' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6.94 5A1.94 1.94 0 1 1 3.06 5a1.94 1.94 0 0 1 3.88 0ZM3.3 8.4h3.3V21H3.3V8.4Zm5.4 0h3.16v1.72h.05c.44-.83 1.52-1.7 3.12-1.7 3.34 0 3.96 2.2 3.96 5.05V21h-3.3v-5.58c0-1.33-.02-3.04-1.85-3.04-1.86 0-2.14 1.45-2.14 2.95V21H8.7V8.4Z"/></svg>',
		'email'    => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 5h18a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Zm1.4 2 7.6 5.3L19.6 7H4.4ZM20 8.5l-8 5.6L4 8.5V17h16V8.5Z"/></svg>',
	);
	return array(
		'youtube'     => array( 'label' => __( 'YouTube', 'ewo-2025' ),     'short' => 'YT', 'detail' => __( 'Watch & Subscribe', 'ewo-2025' ),  'class' => 'youtube',  'icon_key' => 'youtube',  'icon' => $icons['youtube'],  'opens_in_new_tab' => true,  'seed_url' => 'https://www.youtube.com/@emergingworldorder2025', 'sort_order' => 1,  'card' => true,  'icon_row' => true,  'default_enabled' => true, 'default_footer' => true, 'default_sidecard' => true ),
		'substack'    => array( 'label' => __( 'Substack', 'ewo-2025' ),    'short' => 'SS', 'detail' => __( 'Read Analysis', 'ewo-2025' ),      'class' => 'substack', 'icon_key' => 'substack', 'icon' => $icons['substack'], 'opens_in_new_tab' => true,  'seed_url' => 'https://emergingworldorder.substack.com',        'sort_order' => 2,  'card' => true,  'icon_row' => false, 'default_enabled' => true, 'default_footer' => true, 'default_sidecard' => true ),
		'spotify'     => array( 'label' => __( 'Spotify', 'ewo-2025' ),     'short' => 'SP', 'detail' => __( 'Listen to Podcasts', 'ewo-2025' ), 'class' => 'spotify',  'icon_key' => 'spotify',  'icon' => $icons['spotify'],  'opens_in_new_tab' => true,  'seed_url' => 'https://open.spotify.com/show/EmergingWorldOrder',  'sort_order' => 3,  'card' => true,  'icon_row' => false, 'default_enabled' => true, 'default_footer' => true, 'default_sidecard' => true ),
		'tiktok'      => array( 'label' => __( 'TikTok', 'ewo-2025' ),      'short' => 'TT', 'detail' => __( 'Watch Clips', 'ewo-2025' ),        'class' => 'tiktok',   'icon_key' => 'tiktok',   'icon' => $icons['tiktok'],   'opens_in_new_tab' => true,  'seed_url' => 'https://www.tiktok.com/@emergingworldorder',       'sort_order' => 4,  'card' => true,  'icon_row' => false, 'default_enabled' => true, 'default_footer' => true, 'default_sidecard' => false ),
		'x'           => array( 'label' => __( 'X (Twitter)', 'ewo-2025' ), 'short' => 'X',  'detail' => __( 'Latest Updates', 'ewo-2025' ),     'class' => 'x',        'icon_key' => 'x',        'icon' => $icons['x'],        'opens_in_new_tab' => true,  'seed_url' => 'https://x.com/EmergingWorldOrder',                 'sort_order' => 5,  'card' => true,  'icon_row' => true,  'default_enabled' => true, 'default_footer' => true, 'default_sidecard' => true ),
		'amazon_book' => array( 'label' => __( 'Amazon', 'ewo-2025' ),      'short' => 'BK', 'detail' => __( 'Our Book', 'ewo-2025' ),           'class' => 'amazon',   'icon_key' => 'amazon',   'icon' => $icons['amazon'],   'opens_in_new_tab' => true,  'seed_url' => 'https://www.amazon.com',                           'sort_order' => 6,  'card' => true,  'icon_row' => false, 'default_enabled' => true, 'default_footer' => true, 'default_sidecard' => true ),
		'telegram'    => array( 'label' => __( 'Telegram', 'ewo-2025' ),    'short' => 'TG', 'detail' => __( 'Join the Channel', 'ewo-2025' ),    'class' => 'telegram', 'icon_key' => 'telegram', 'icon' => $icons['telegram'], 'opens_in_new_tab' => true,  'seed_url' => 'https://t.me/EmergingWorldOrder',                  'sort_order' => 7,  'card' => false, 'icon_row' => true,  'default_enabled' => true, 'default_footer' => true, 'default_sidecard' => false ),
		'linkedin'    => array( 'label' => __( 'LinkedIn', 'ewo-2025' ),    'short' => 'IN', 'detail' => __( 'Connect', 'ewo-2025' ),            'class' => 'linkedin', 'icon_key' => 'linkedin', 'icon' => $icons['linkedin'], 'opens_in_new_tab' => true,  'seed_url' => 'https://www.linkedin.com/company/emerging-world-order', 'sort_order' => 8, 'card' => false, 'icon_row' => true,  'default_enabled' => true, 'default_footer' => true, 'default_sidecard' => false ),
		'email'       => array( 'label' => __( 'Email', 'ewo-2025' ),       'short' => '@',  'detail' => __( 'Get in Touch', 'ewo-2025' ),       'class' => 'email',    'icon_key' => 'email',    'icon' => $icons['email'],    'opens_in_new_tab' => false, 'seed_url' => 'hello@emergingworldorder.com',                     'sort_order' => 9,  'card' => false, 'icon_row' => true,  'default_enabled' => true, 'default_footer' => true, 'default_sidecard' => false ),
		'rumble'      => array( 'label' => __( 'Rumble', 'ewo-2025' ),      'short' => 'RB', 'detail' => __( 'Watch on Rumble', 'ewo-2025' ),     'class' => 'rumble',   'icon_key' => 'rumble',   'icon' => $icons['rumble'],   'opens_in_new_tab' => true,  'seed_url' => '',                                                 'sort_order' => 10, 'card' => true,  'icon_row' => false, 'default_enabled' => true, 'default_footer' => false, 'default_sidecard' => true ),
	);
}

/** Allowed SVG markup for wp_kses on platform icons. */
function ewo_2025_social_icon_kses() {
	return array(
		'svg'  => array( 'viewbox' => true, 'aria-hidden' => true ),
		'path' => array( 'd' => true, 'fill' => true, 'fill-rule' => true, 'clip-rule' => true ),
	);
}

/** Build the safe target/rel attribute string for a link row. */
function ewo_2025_link_target_attr( $opens_in_new_tab ) {
	return $opens_in_new_tab ? ' target="_blank" rel="noopener noreferrer"' : '';
}

/**
 * Resolved settings for one platform: url (with seed fallback + mailto), and
 * the enabled / footer / sidecard toggles. Toggle values come from a saved
 * record when present, otherwise from the registry defaults.
 */
function ewo_2025_get_platform_settings( $key ) {
	$platforms = ewo_2025_get_social_platforms();
	if ( empty( $platforms[ $key ] ) ) { return null; }
	$p     = $platforms[ $key ];
	$saved     = get_option( EWO_2025_SOCIAL_LINKS_OPTION, array() );
	$saved_val = ( is_array( $saved ) && isset( $saved[ $key ] ) ) ? $saved[ $key ] : null;
	// Only the new nested format carries explicit toggle values; a legacy flat
	// url string (or no record) means the toggles fall back to defaults.
	$has_record = is_array( $saved_val );
	$raw        = $has_record ? $saved_val : array();
	if ( is_string( $saved_val ) && '' !== $saved_val ) { $raw['url'] = $saved_val; }

	$raw_url = isset( $raw['url'] ) ? trim( (string) $raw['url'] ) : '';
	$url     = '' === $raw_url ? (string) $p['seed_url'] : $raw_url;
	if ( 'email' === $key && '' !== $url && 0 !== strpos( $url, 'mailto:' ) && is_email( $url ) ) {
		$url = 'mailto:' . $url;
	}

	return array(
		'url'      => $url,
		'raw_url'  => $raw_url,
		'enabled'  => $has_record ? ! empty( $raw['enabled'] )  : ! empty( $p['default_enabled'] ),
		'footer'   => $has_record ? ! empty( $raw['footer'] )   : ! empty( $p['default_footer'] ),
		'sidecard' => $has_record ? ! empty( $raw['sidecard'] ) : ! empty( $p['default_sidecard'] ),
	);
}

/**
 * Resolved, active, sorted link records for a display surface:
 *   footer_card | footer_icon | sidecard
 * Disabled links, links with no URL, and links not placed on the surface are
 * dropped, so nothing broken or unwanted is rendered.
 */
function ewo_2025_get_platform_surface_links( $surface ) {
	$rows = array();
	foreach ( ewo_2025_get_social_platforms() as $key => $p ) {
		$s = ewo_2025_get_platform_settings( $key );
		if ( ! $s['enabled'] || '' === $s['url'] ) { continue; }
		if ( 'footer_card' === $surface ) {
			$ok = $s['footer'] && ! empty( $p['card'] );
		} elseif ( 'footer_icon' === $surface ) {
			$ok = $s['footer'] && ! empty( $p['icon_row'] );
		} elseif ( 'sidecard' === $surface ) {
			$ok = $s['sidecard'];
		} else {
			$ok = false;
		}
		if ( ! $ok ) { continue; }
		$rows[] = array(
			'key'              => $key,
			'url'              => $s['url'],
			'platform_name'    => $p['label'],
			'label'            => $p['detail'],
			'class'            => $p['class'],
			'icon'             => $p['icon'],
			'opens_in_new_tab' => ! empty( $p['opens_in_new_tab'] ),
			'sort_order'       => (int) $p['sort_order'],
		);
	}
	usort(
		$rows,
		static function ( $a, $b ) {
			return $a['sort_order'] <=> $b['sort_order'];
		}
	);
	return $rows;
}

/** Render the footer platform cards (footer + card capability). */
function ewo_2025_render_footer_platform_cards() {
	$allowed = ewo_2025_social_icon_kses();
	foreach ( ewo_2025_get_platform_surface_links( 'footer_card' ) as $row ) {
		printf(
			'<a class="ewo-footer-platform ewo-footer-platform--%1$s" href="%2$s"%3$s aria-label="%4$s"><span class="ewo-footer-platform__icon">%5$s</span><span class="ewo-footer-platform__text"><span class="ewo-footer-platform__name">%6$s</span><span class="ewo-footer-platform__label">%7$s</span></span></a>',
			esc_attr( $row['class'] ),
			esc_url( $row['url'] ),
			ewo_2025_link_target_attr( $row['opens_in_new_tab'] ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static literal.
			esc_attr( $row['platform_name'] . ' — ' . $row['label'] ),
			wp_kses( $row['icon'], $allowed ),
			esc_html( $row['platform_name'] ),
			esc_html( $row['label'] )
		);
	}
}

/** Render the small icon-only footer social row (footer + icon_row capability). */
function ewo_2025_render_footer_icons() {
	$links = ewo_2025_get_platform_surface_links( 'footer_icon' );
	if ( empty( $links ) ) { return; }
	$allowed = ewo_2025_social_icon_kses();
	echo '<div class="site-footer__social-row" aria-label="' . esc_attr__( 'EWO social links', 'ewo-2025' ) . '">';
	foreach ( $links as $row ) {
		printf(
			'<a class="ewo-footer-icon ewo-footer-icon--%1$s" href="%2$s"%3$s aria-label="%4$s"><span class="ewo-footer-icon__glyph">%5$s</span></a>',
			esc_attr( $row['class'] ),
			esc_url( $row['url'] ),
			ewo_2025_link_target_attr( $row['opens_in_new_tab'] ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static literal.
			esc_attr( $row['platform_name'] ),
			wp_kses( $row['icon'], $allowed )
		);
	}
	echo '</div>';
}

/** Render the sidebar Follow card links (sidecard placement). Returns bool. */
function ewo_2025_render_sidecard_links() {
	$links = ewo_2025_get_platform_surface_links( 'sidecard' );
	if ( empty( $links ) ) { return false; }
	$allowed = ewo_2025_social_icon_kses();
	foreach ( $links as $row ) {
		printf(
			'<a class="ewo-sidebar-follow__link ewo-sidebar-follow--%1$s" href="%2$s"%3$s aria-label="%4$s"><span class="ewo-sidebar-follow__icon">%5$s</span><span class="ewo-sidebar-follow__name">%6$s</span></a>',
			esc_attr( $row['class'] ),
			esc_url( $row['url'] ),
			ewo_2025_link_target_attr( $row['opens_in_new_tab'] ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static literal.
			esc_attr( $row['platform_name'] ),
			wp_kses( $row['icon'], $allowed ),
			esc_html( $row['platform_name'] )
		);
	}
	return true;
}

function ewo_2025_sanitize_social_links( $input ) {
	$input  = is_array( $input ) ? $input : array();
	$output = array();
	foreach ( ewo_2025_get_social_platforms() as $key => $p ) {
		$row = isset( $input[ $key ] ) ? $input[ $key ] : array();
		if ( is_string( $row ) ) { $row = array( 'url' => $row ); } // Back-compat.
		if ( ! is_array( $row ) ) { $row = array(); }
		$raw_url = isset( $row['url'] ) ? trim( wp_unslash( $row['url'] ) ) : '';
		if ( '' === $raw_url ) {
			$url = '';
		} elseif ( 'email' === $key && 0 !== strpos( $raw_url, 'mailto:' ) && is_email( $raw_url ) ) {
			$url = sanitize_email( $raw_url );
		} else {
			$url = esc_url_raw( $raw_url );
		}
		$output[ $key ] = array(
			'url'      => $url,
			'enabled'  => empty( $row['enabled'] ) ? 0 : 1,
			'footer'   => empty( $row['footer'] ) ? 0 : 1,
			'sidecard' => empty( $row['sidecard'] ) ? 0 : 1,
		);
	}
	return $output;
}

/** Back-compat: map of platform key => resolved URL. */
function ewo_2025_get_social_links() {
	$out = array();
	foreach ( ewo_2025_get_social_platforms() as $key => $p ) {
		$s           = ewo_2025_get_platform_settings( $key );
		$out[ $key ] = $s ? $s['url'] : '';
	}
	return $out;
}

function ewo_2025_migrate_social_links() {
	if ( false !== get_option( EWO_2025_SOCIAL_LINKS_OPTION, false ) ) { return; }
	$links = array();
	foreach ( ewo_2025_get_social_platforms() as $key => $platform ) {
		$links[ $key ] = array( 'url' => esc_url_raw( (string) get_theme_mod( 'ewo_2025_' . $key . '_url', '' ) ) );
		remove_theme_mod( 'ewo_2025_' . $key . '_url' );
	}
	add_option( EWO_2025_SOCIAL_LINKS_OPTION, $links );
}
add_action( 'after_setup_theme', 'ewo_2025_migrate_social_links', 5 );

/** Resolve a single platform URL (seed fallback + mailto handling). */
function ewo_2025_get_platform_url( $platform ) {
	if ( 'newsletter' === $platform ) { return trim( (string) get_theme_mod( 'ewo_2025_newsletter_url', '' ) ); }
	$s = ewo_2025_get_platform_settings( $platform );
	return $s ? $s['url'] : '';
}
function ewo_2025_has_platform_links( $keys = array() ) {
	foreach ( $keys as $key ) { if ( ewo_2025_get_platform_url( $key ) ) { return true; } }
	return false;
}
function ewo_2025_social_links( $keys = array(), $context = 'compact' ) {
	$platforms = ewo_2025_get_social_platforms();
	$allowed   = ewo_2025_social_icon_kses();
	foreach ( $keys as $key ) {
		if ( empty( $platforms[ $key ] ) ) { continue; }
		$s = ewo_2025_get_platform_settings( $key );
		if ( ! $s['enabled'] || '' === $s['url'] ) { continue; }
		$p      = $platforms[ $key ];
		$url    = $s['url'];
		$target = ewo_2025_link_target_attr( ! empty( $p['opens_in_new_tab'] ) );
		if ( 'footer' === $context ) { ?>
			<a class="ewo-footer-platform ewo-footer-platform--<?php echo esc_attr( $p['class'] ); ?>" href="<?php echo esc_url( $url ); ?>"<?php echo $target; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static literal. ?> aria-label="<?php echo esc_attr( $p['label'] ); ?>"><span class="ewo-footer-platform__icon"><?php echo wp_kses( $p['icon'], $allowed ); ?></span><span class="ewo-footer-platform__text"><span class="ewo-footer-platform__name"><?php echo esc_html( $p['label'] ); ?></span><span class="ewo-footer-platform__label"><?php echo esc_html( $p['detail'] ); ?></span></span></a>
		<?php } elseif ( 'sidebar' === $context ) { ?>
			<a class="ewo-sidebar-follow__link ewo-sidebar-follow--<?php echo esc_attr( $p['class'] ); ?>" href="<?php echo esc_url( $url ); ?>"<?php echo $target; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static literal. ?>><span class="ewo-sidebar-follow__icon"><?php echo wp_kses( $p['icon'], $allowed ); ?></span><span class="ewo-sidebar-follow__name"><?php echo esc_html( $p['label'] ); ?></span></a>
		<?php } else { ?>
			<a href="<?php echo esc_url( $url ); ?>"<?php echo $target; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static literal. ?> aria-label="<?php echo esc_attr( $p['label'] ); ?>"><span aria-hidden="true"><?php echo esc_html( $p['short'] ); ?></span><span class="ewo-platform-links__label"><?php echo esc_html( $p['label'] ); ?></span></a>
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
	if ( ! current_user_can( 'manage_options' ) ) { return; }
	$option = EWO_2025_SOCIAL_LINKS_OPTION;
	?>
	<div class="wrap ewo-social-settings">
		<h1><?php esc_html_e( 'EWO Settings — Social Links', 'ewo-2025' ); ?></h1>
		<p><?php esc_html_e( 'One URL per platform powers the footer and the sidebar Follow card. Use the toggles to choose where each link appears. Turn a link Off to hide it everywhere. A blank URL falls back to the seed default shown below the field; a link with no URL and no seed default is hidden.', 'ewo-2025' ); ?></p>
		<style>
			.ewo-social-settings .ewo-toggle{display:inline-flex;align-items:center;gap:8px;margin-right:18px;font-weight:600}
			.ewo-social-settings .ewo-switch{position:relative;display:inline-block;width:42px;height:22px;flex:0 0 auto}
			.ewo-social-settings .ewo-switch input{position:absolute;opacity:0;width:0;height:0}
			.ewo-social-settings .ewo-switch span{position:absolute;inset:0;cursor:pointer;background:#c3c4c7;border-radius:999px;transition:background .15s}
			.ewo-social-settings .ewo-switch span::before{content:"";position:absolute;height:16px;width:16px;left:3px;top:3px;background:#fff;border-radius:50%;transition:transform .15s}
			.ewo-social-settings .ewo-switch input:checked+span{background:#2271b1}
			.ewo-social-settings .ewo-switch input:checked+span::before{transform:translateX(20px)}
			.ewo-social-settings .ewo-place{display:inline-flex;align-items:center;gap:6px;margin-right:16px}
			.ewo-social-settings td .description{margin-top:6px}
			.ewo-social-settings .ewo-row-disabled td{opacity:.55}
		</style>
		<form action="options.php" method="post">
			<?php settings_fields( 'ewo_2025_social_links_group' ); ?>
			<table class="form-table" role="presentation">
				<?php foreach ( ewo_2025_get_social_platforms() as $key => $platform ) :
					$s    = ewo_2025_get_platform_settings( $key );
					$base = $option . '[' . $key . ']';
					?>
					<tr class="<?php echo $s['enabled'] ? '' : 'ewo-row-disabled'; ?>">
						<th scope="row"><label for="ewo-social-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $platform['label'] ); ?></label><br><span class="description"><?php echo esc_html( $platform['detail'] ); ?></span></th>
						<td>
							<input class="regular-text" type="<?php echo 'email' === $key ? 'text' : 'url'; ?>" id="ewo-social-<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $base ); ?>[url]" value="<?php echo esc_attr( isset( $s['raw_url'] ) ? $s['raw_url'] : $s['url'] ); ?>" placeholder="<?php echo esc_attr( $platform['seed_url'] ? $platform['seed_url'] : 'https://' ); ?>" />
							<?php if ( ! empty( $platform['seed_url'] ) ) : ?><p class="description"><?php printf( /* translators: %s: seed URL. */ esc_html__( 'Seed default: %s', 'ewo-2025' ), esc_html( $platform['seed_url'] ) ); ?></p><?php endif; ?>
							<p>
								<label class="ewo-toggle"><span class="ewo-switch"><input type="checkbox" name="<?php echo esc_attr( $base ); ?>[enabled]" value="1" <?php checked( $s['enabled'] ); ?>><span></span></span><?php esc_html_e( 'Enabled', 'ewo-2025' ); ?></label>
								<label class="ewo-place"><input type="checkbox" name="<?php echo esc_attr( $base ); ?>[footer]" value="1" <?php checked( $s['footer'] ); ?>><?php esc_html_e( 'Footer', 'ewo-2025' ); ?></label>
								<label class="ewo-place"><input type="checkbox" name="<?php echo esc_attr( $base ); ?>[sidecard]" value="1" <?php checked( $s['sidecard'] ); ?>><?php esc_html_e( 'Side Card', 'ewo-2025' ); ?></label>
							</p>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
