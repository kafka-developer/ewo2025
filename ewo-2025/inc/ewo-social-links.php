<?php
/** Shared EWO social / platform links. @package EWO_2025 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
const EWO_2025_SOCIAL_LINKS_OPTION = 'ewo_2025_social_links';

/**
 * Platform registry — single source of truth for SVG icons and metadata.
 *
 * Admin controls: url, enabled, header, footer, sidecard, sort_order
 * (all stored in EWO_2025_SOCIAL_LINKS_OPTION).
 *
 * Everything here is seed / fallback data used when admin has not yet saved:
 *   seed_url       — fallback URL when admin URL is blank.
 *   default_*      — default toggle values until admin saves.
 *   sort_order     — default display order; overrideable in admin.
 *   card / icon_row — footer placement capability (design property, not admin-editable).
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
		'youtube'     => array( 'label' => __( 'YouTube', 'ewo-2025' ),     'short' => 'YT', 'detail' => __( 'Watch & Subscribe', 'ewo-2025' ),  'class' => 'youtube',  'icon_key' => 'youtube',  'icon' => $icons['youtube'],  'opens_in_new_tab' => true,  'seed_url' => 'https://www.youtube.com/@emergingworldorder2025', 'sort_order' => 1,  'card' => true,  'icon_row' => true,  'default_enabled' => true, 'default_header' => true,  'default_footer' => true, 'default_sidecard' => true ),
		'substack'    => array( 'label' => __( 'Substack', 'ewo-2025' ),    'short' => 'SS', 'detail' => __( 'Read Analysis', 'ewo-2025' ),      'class' => 'substack', 'icon_key' => 'substack', 'icon' => $icons['substack'], 'opens_in_new_tab' => true,  'seed_url' => 'https://emergingworldorder.substack.com',        'sort_order' => 2,  'card' => true,  'icon_row' => false, 'default_enabled' => true, 'default_header' => true,  'default_footer' => true, 'default_sidecard' => true ),
		'spotify'     => array( 'label' => __( 'Spotify', 'ewo-2025' ),     'short' => 'SP', 'detail' => __( 'Listen to Podcasts', 'ewo-2025' ), 'class' => 'spotify',  'icon_key' => 'spotify',  'icon' => $icons['spotify'],  'opens_in_new_tab' => true,  'seed_url' => 'https://open.spotify.com/show/EmergingWorldOrder',  'sort_order' => 3,  'card' => true,  'icon_row' => false, 'default_enabled' => true, 'default_header' => true,  'default_footer' => true, 'default_sidecard' => true ),
		'tiktok'      => array( 'label' => __( 'TikTok', 'ewo-2025' ),      'short' => 'TT', 'detail' => __( 'Watch Clips', 'ewo-2025' ),        'class' => 'tiktok',   'icon_key' => 'tiktok',   'icon' => $icons['tiktok'],   'opens_in_new_tab' => true,  'seed_url' => 'https://www.tiktok.com/@emergingworldorder',       'sort_order' => 4,  'card' => true,  'icon_row' => false, 'default_enabled' => true, 'default_header' => true,  'default_footer' => true, 'default_sidecard' => false ),
		'x'           => array( 'label' => __( 'X (Twitter)', 'ewo-2025' ), 'short' => 'X',  'detail' => __( 'Latest Updates', 'ewo-2025' ),     'class' => 'x',        'icon_key' => 'x',        'icon' => $icons['x'],        'opens_in_new_tab' => true,  'seed_url' => 'https://x.com/EmergingWorldOrder',                 'sort_order' => 5,  'card' => true,  'icon_row' => true,  'default_enabled' => true, 'default_header' => true,  'default_footer' => true, 'default_sidecard' => true ),
		'amazon_book' => array( 'label' => __( 'Amazon', 'ewo-2025' ),      'short' => 'BK', 'detail' => __( 'Our Book', 'ewo-2025' ),           'class' => 'amazon',   'icon_key' => 'amazon',   'icon' => $icons['amazon'],   'opens_in_new_tab' => true,  'seed_url' => 'https://www.amazon.com',                           'sort_order' => 6,  'card' => true,  'icon_row' => false, 'default_enabled' => true, 'default_header' => true,  'default_footer' => true, 'default_sidecard' => true ),
		'rumble'      => array( 'label' => __( 'Rumble', 'ewo-2025' ),      'short' => 'RB', 'detail' => __( 'Watch on Rumble', 'ewo-2025' ),     'class' => 'rumble',   'icon_key' => 'rumble',   'icon' => $icons['rumble'],   'opens_in_new_tab' => true,  'seed_url' => '',                                                 'sort_order' => 7,  'card' => true,  'icon_row' => false, 'default_enabled' => true, 'default_header' => true,  'default_footer' => false, 'default_sidecard' => true ),
		'telegram'    => array( 'label' => __( 'Telegram', 'ewo-2025' ),    'short' => 'TG', 'detail' => __( 'Join the Channel', 'ewo-2025' ),    'class' => 'telegram', 'icon_key' => 'telegram', 'icon' => $icons['telegram'], 'opens_in_new_tab' => true,  'seed_url' => 'https://t.me/EmergingWorldOrder',                  'sort_order' => 8,  'card' => false, 'icon_row' => true,  'default_enabled' => true, 'default_header' => false, 'default_footer' => true, 'default_sidecard' => false ),
		'linkedin'    => array( 'label' => __( 'LinkedIn', 'ewo-2025' ),    'short' => 'IN', 'detail' => __( 'Connect', 'ewo-2025' ),            'class' => 'linkedin', 'icon_key' => 'linkedin', 'icon' => $icons['linkedin'], 'opens_in_new_tab' => true,  'seed_url' => 'https://www.linkedin.com/company/emerging-world-order', 'sort_order' => 9, 'card' => false, 'icon_row' => true,  'default_enabled' => true, 'default_header' => false, 'default_footer' => true, 'default_sidecard' => false ),
		'email'       => array( 'label' => __( 'Email', 'ewo-2025' ),       'short' => '@',  'detail' => __( 'Get in Touch', 'ewo-2025' ),       'class' => 'email',    'icon_key' => 'email',    'icon' => $icons['email'],    'opens_in_new_tab' => false, 'seed_url' => 'hello@emergingworldorder.com',                     'sort_order' => 10, 'card' => false, 'icon_row' => true,  'default_enabled' => true, 'default_header' => false, 'default_footer' => true, 'default_sidecard' => false ),
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
 * Resolved settings for one registry platform: url, enabled, header, footer,
 * sidecard, sort_order. Stored values override registry defaults.
 */
function ewo_2025_get_platform_settings( $key ) {
	$platforms = ewo_2025_get_social_platforms();
	if ( empty( $platforms[ $key ] ) ) { return null; }
	$p         = $platforms[ $key ];
	$saved     = get_option( EWO_2025_SOCIAL_LINKS_OPTION, array() );
	$saved_val = ( is_array( $saved ) && isset( $saved[ $key ] ) ) ? $saved[ $key ] : null;
	$has_record = is_array( $saved_val );
	$raw        = $has_record ? $saved_val : array();
	if ( is_string( $saved_val ) && '' !== $saved_val ) { $raw['url'] = $saved_val; }

	$raw_url = isset( $raw['url'] ) ? trim( (string) $raw['url'] ) : '';
	$url     = '' === $raw_url ? (string) $p['seed_url'] : $raw_url;
	if ( 'email' === $key && '' !== $url && 0 !== strpos( $url, 'mailto:' ) && is_email( $url ) ) {
		$url = 'mailto:' . $url;
	}

	return array(
		'url'        => $url,
		'raw_url'    => $raw_url,
		'enabled'    => $has_record ? ! empty( $raw['enabled'] )    : ! empty( $p['default_enabled'] ),
		'header'     => $has_record && array_key_exists( 'header', $raw ) ? ! empty( $raw['header'] ) : ! empty( $p['default_header'] ),
		'footer'     => $has_record ? ! empty( $raw['footer'] )     : ! empty( $p['default_footer'] ),
		'sidecard'   => $has_record ? ! empty( $raw['sidecard'] )   : ! empty( $p['default_sidecard'] ),
		'sort_order' => ( $has_record && isset( $raw['sort_order'] ) ) ? (int) $raw['sort_order'] : (int) $p['sort_order'],
	);
}

/** Returns the _custom array from the stored option (user-added platforms). */
function ewo_2025_get_custom_platforms() {
	$saved  = get_option( EWO_2025_SOCIAL_LINKS_OPTION, array() );
	$custom = ( is_array( $saved ) && isset( $saved['_custom'] ) ) ? $saved['_custom'] : array();
	return is_array( $custom ) ? $custom : array();
}

/**
 * Resolved, active, sorted link records for a display surface:
 *   header | footer_card | footer_icon | sidecard
 *
 * Registry platforms are filtered by their surface flag; custom platforms
 * (user-added) only appear on the 'header' surface (they carry no SVG icon).
 * Disabled links and links with no URL are always dropped.
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
		} elseif ( 'footer' === $surface ) {
			$ok = $s['footer'];
		} elseif ( 'sidecard' === $surface ) {
			$ok = $s['sidecard'];
		} elseif ( 'header' === $surface ) {
			$ok = $s['header'];
		} else {
			$ok = false;
		}
		if ( ! $ok ) { continue; }

		$rows[] = array(
			'key'              => $key,
			'url'              => $s['url'],
			'platform_name'    => $p['label'],
			'label'            => $p['detail'],
			'short'            => $p['short'],
			'class'            => $p['class'],
			'icon'             => $p['icon'],
			'opens_in_new_tab' => ! empty( $p['opens_in_new_tab'] ),
			'sort_order'       => $s['sort_order'],
		);
	}

	// Custom platforms only appear in the header chip row.
	if ( 'header' === $surface ) {
		foreach ( ewo_2025_get_custom_platforms() as $cp ) {
			if ( empty( $cp['enabled'] ) || empty( $cp['url'] ) || empty( $cp['header'] ) ) { continue; }
			$rows[] = array(
				'key'              => $cp['id'] ?? '',
				'url'              => esc_url( $cp['url'] ),
				'platform_name'    => $cp['label'] ?? ( $cp['short'] ?? '' ),
				'label'            => $cp['detail'] ?? '',
				'short'            => $cp['short'] ?? '',
				'class'            => sanitize_html_class( strtolower( $cp['short'] ?? 'custom' ) ),
				'icon'             => '',
				'opens_in_new_tab' => true,
				'sort_order'       => (int) ( $cp['sort_order'] ?? 99 ),
			);
		}
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

/**
 * Render the header platform chips — all enabled header-surface platforms in
 * sort order. Replaces the old hardcoded ewo_2025_platform_links() call in
 * header.php so the list is fully admin-controlled.
 */
function ewo_2025_render_header_chips() {
	$links = ewo_2025_get_platform_surface_links( 'header' );
	if ( empty( $links ) ) { return; }
	echo '<div class="ewo-platform-links ewo-platform-links--header" aria-label="' . esc_attr__( 'EWO platform links', 'ewo-2025' ) . '">';
	foreach ( $links as $row ) {
		printf(
			'<a href="%1$s"%2$s aria-label="%3$s"><span aria-hidden="true">%4$s</span><span class="ewo-platform-links__label">%5$s</span></a>',
			esc_url( $row['url'] ),
			ewo_2025_link_target_attr( $row['opens_in_new_tab'] ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static literal.
			esc_attr( $row['platform_name'] ),
			esc_html( $row['short'] ),
			esc_html( $row['platform_name'] )
		);
	}
	echo '</div>';
}

/** Sanitize / validate the full platform links option. */
function ewo_2025_sanitize_social_links( $input ) {
	$input  = is_array( $input ) ? $input : array();
	$output = array();

	// Registry platforms.
	foreach ( ewo_2025_get_social_platforms() as $key => $p ) {
		$row = isset( $input[ $key ] ) ? $input[ $key ] : array();
		if ( is_string( $row ) ) { $row = array( 'url' => $row ); }
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
			'url'        => $url,
			'enabled'    => empty( $row['enabled'] )    ? 0 : 1,
			'header'     => empty( $row['header'] )     ? 0 : 1,
			'footer'     => empty( $row['footer'] )     ? 0 : 1,
			'sidecard'   => empty( $row['sidecard'] )   ? 0 : 1,
			'sort_order' => isset( $row['sort_order'] ) ? absint( $row['sort_order'] ) : (int) $p['sort_order'],
		);
	}

	// Custom (user-added) platforms stored under _custom.
	$custom_raw = ( isset( $input['_custom'] ) && is_array( $input['_custom'] ) ) ? $input['_custom'] : array();
	$custom_out = array();
	foreach ( $custom_raw as $cp ) {
		if ( ! is_array( $cp ) ) { continue; }
		$short = strtoupper( substr( sanitize_text_field( wp_unslash( $cp['short'] ?? '' ) ), 0, 5 ) );
		if ( '' === $short ) { continue; }
		$id      = ( ! empty( $cp['id'] ) ) ? sanitize_key( $cp['id'] ) : 'cst_' . time() . '_' . wp_rand( 100, 999 );
		$raw_url = isset( $cp['url'] ) ? trim( wp_unslash( $cp['url'] ) ) : '';
		$custom_out[] = array(
			'id'         => $id,
			'short'      => $short,
			'label'      => sanitize_text_field( wp_unslash( $cp['label'] ?? $short ) ),
			'detail'     => sanitize_text_field( wp_unslash( $cp['detail'] ?? '' ) ),
			'url'        => $raw_url ? esc_url_raw( $raw_url ) : '',
			'enabled'    => empty( $cp['enabled'] )    ? 0 : 1,
			'header'     => empty( $cp['header'] )     ? 0 : 1,
			'footer'     => empty( $cp['footer'] )     ? 0 : 1,
			'sidecard'   => empty( $cp['sidecard'] )   ? 0 : 1,
			'sort_order' => isset( $cp['sort_order'] ) ? absint( $cp['sort_order'] ) : 99,
		);
	}
	$output['_custom'] = $custom_out;

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

/** Back-compat: render chips for a specific keyed list (still used by any callers outside header.php). */
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

/* ============================================================
   Settings registration
   ============================================================ */

function ewo_2025_register_social_settings() {
	register_setting(
		'ewo_2025_social_links_group',
		EWO_2025_SOCIAL_LINKS_OPTION,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'ewo_2025_sanitize_social_links',
			'default'           => array(),
		)
	);
}
add_action( 'admin_init', 'ewo_2025_register_social_settings' );

/* ============================================================
   Admin page: handler for adding a new custom platform
   ============================================================ */

function ewo_2025_social_handle_add() {
	check_admin_referer( 'ewo_social_add' );
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Forbidden' ); }

	$short = strtoupper( substr( sanitize_text_field( wp_unslash( $_POST['short'] ?? '' ) ), 0, 5 ) );
	if ( '' === $short ) {
		wp_safe_redirect( admin_url( 'admin.php?page=ewo-settings&ewo_msg=short_required' ) );
		exit;
	}

	$new = array(
		'id'         => 'cst_' . time() . '_' . wp_rand( 100, 999 ),
		'short'      => $short,
		'label'      => sanitize_text_field( wp_unslash( $_POST['label'] ?? $short ) ),
		'detail'     => sanitize_text_field( wp_unslash( $_POST['detail'] ?? '' ) ),
		'url'        => esc_url_raw( wp_unslash( $_POST['url'] ?? '' ) ),
		'enabled'    => ! empty( $_POST['enabled'] )  ? 1 : 0,
		'header'     => ! empty( $_POST['header'] )   ? 1 : 0,
		'footer'     => ! empty( $_POST['footer'] )   ? 1 : 0,
		'sidecard'   => ! empty( $_POST['sidecard'] ) ? 1 : 0,
		'sort_order' => absint( $_POST['sort_order'] ?? 99 ),
	);

	$saved = get_option( EWO_2025_SOCIAL_LINKS_OPTION, array() );
	if ( ! is_array( $saved ) ) { $saved = array(); }
	if ( ! isset( $saved['_custom'] ) ) { $saved['_custom'] = array(); }
	$saved['_custom'][] = $new;
	update_option( EWO_2025_SOCIAL_LINKS_OPTION, ewo_2025_sanitize_social_links( $saved ) );

	wp_safe_redirect( admin_url( 'admin.php?page=ewo-settings&ewo_msg=added' ) );
	exit;
}
add_action( 'admin_post_ewo_social_add', 'ewo_2025_social_handle_add' );

/* ============================================================
   Admin page: menu + render
   ============================================================ */

function ewo_2025_social_settings_menu() {
	add_menu_page(
		__( 'EWO Settings', 'ewo-2025' ),
		__( 'EWO Settings', 'ewo-2025' ),
		'manage_options',
		'ewo-settings',
		'ewo_2025_render_social_settings_page',
		'dashicons-admin-generic',
		61
	);
	add_submenu_page(
		'ewo-settings',
		__( 'Social Links', 'ewo-2025' ),
		__( 'Social Links', 'ewo-2025' ),
		'manage_options',
		'ewo-settings',
		'ewo_2025_render_social_settings_page'
	);
}
add_action( 'admin_menu', 'ewo_2025_social_settings_menu' );

/** Returns merged registry + custom platforms sorted for the admin table. */
function ewo_2025_get_all_platforms_admin() {
	$items = array();

	foreach ( ewo_2025_get_social_platforms() as $key => $p ) {
		$s       = ewo_2025_get_platform_settings( $key );
		$items[] = array(
			'type'       => 'registry',
			'key'        => $key,
			'short'      => $p['short'],
			'label'      => $p['label'],
			'detail'     => $p['detail'],
			'seed_url'   => $p['seed_url'],
			'url'        => $s['raw_url'],
			'enabled'    => $s['enabled'],
			'header'     => $s['header'],
			'footer'     => $s['footer'],
			'sidecard'   => $s['sidecard'],
			'sort_order' => $s['sort_order'],
		);
	}

	$cidx = 0;
	foreach ( ewo_2025_get_custom_platforms() as $cp ) {
		$items[] = array(
			'type'       => 'custom',
			'key'        => $cp['id'] ?? ( 'cst_' . $cidx ),
			'cidx'       => $cidx,
			'short'      => $cp['short'] ?? '',
			'label'      => $cp['label'] ?? '',
			'detail'     => $cp['detail'] ?? '',
			'seed_url'   => '',
			'url'        => $cp['url'] ?? '',
			'enabled'    => ! empty( $cp['enabled'] ),
			'header'     => ! empty( $cp['header'] ),
			'footer'     => ! empty( $cp['footer'] ),
			'sidecard'   => ! empty( $cp['sidecard'] ),
			'sort_order' => (int) ( $cp['sort_order'] ?? 99 ),
		);
		$cidx++;
	}

	usort(
		$items,
		static function ( $a, $b ) {
			return $a['sort_order'] <=> $b['sort_order'];
		}
	);
	return $items;
}

function ewo_2025_render_social_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) { return; }

	// Handle GET delete of a custom platform.
	if (
		isset( $_GET['action'], $_GET['id'] ) &&
		'delete_cust' === sanitize_key( $_GET['action'] )
	) {
		$id = sanitize_key( $_GET['id'] );
		check_admin_referer( 'ewo_sl_del_' . $id );
		$saved = get_option( EWO_2025_SOCIAL_LINKS_OPTION, array() );
		if ( is_array( $saved ) && isset( $saved['_custom'] ) ) {
			$saved['_custom'] = array_values(
				array_filter( $saved['_custom'], static function ( $cp ) use ( $id ) {
					return ( $cp['id'] ?? '' ) !== $id;
				} )
			);
		}
		update_option( EWO_2025_SOCIAL_LINKS_OPTION, $saved );
		wp_safe_redirect( admin_url( 'admin.php?page=ewo-settings&ewo_msg=deleted' ) );
		exit;
	}

	// Compute stats.
	$all        = ewo_2025_get_all_platforms_admin();
	$total      = count( $all );
	$n_enabled  = count( array_filter( $all, static fn( $r ) => $r['enabled'] ) );
	$n_header   = count( array_filter( $all, static fn( $r ) => $r['enabled'] && $r['header'] ) );
	$n_footer   = count( array_filter( $all, static fn( $r ) => $r['enabled'] && $r['footer'] ) );
	$n_sidecard = count( array_filter( $all, static fn( $r ) => $r['enabled'] && $r['sidecard'] ) );
	$n_custom   = count( ewo_2025_get_custom_platforms() );

	$msg = sanitize_key( $_GET['ewo_msg'] ?? '' );
	$option = EWO_2025_SOCIAL_LINKS_OPTION;
	?>
<div class="ewo-sl-wrap">
<style>
.ewo-sl-wrap *{box-sizing:border-box}
.ewo-sl-wrap{background:#060f1e;color:#dde8f5;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;min-height:100vh;padding:0 0 80px}
.ewo-sl-page-header{background:#0b1829;border-bottom:1px solid rgba(50,100,160,.28);padding:28px 32px 24px}
.ewo-sl-page-header h1{color:#fff;font-size:1.45rem;font-weight:700;margin:0 0 6px}
.ewo-sl-page-header p{color:#6b88b5;font-size:.85rem;margin:0}
.ewo-sl-inner{padding:28px 32px 0}
.ewo-sl-notice{background:rgba(45,184,122,.12);border:1px solid rgba(45,184,122,.3);border-radius:6px;color:#2db87a;font-size:.82rem;font-weight:600;margin-bottom:20px;padding:10px 16px}
.ewo-sl-notice--error{background:rgba(224,84,84,.12);border-color:rgba(224,84,84,.3);color:#e05454}
.ewo-sl-stats{display:grid;grid-template-columns:repeat(5,1fr);gap:12px;margin-bottom:28px}
.ewo-sl-stat{background:#0b1829;border:1px solid rgba(50,100,160,.28);border-radius:8px;padding:14px 16px}
.ewo-sl-stat__num{color:#fff;font-size:1.6rem;font-weight:800;line-height:1}
.ewo-sl-stat__label{color:#6b88b5;font-size:.72rem;font-weight:600;letter-spacing:.08em;margin-top:4px;text-transform:uppercase}
.ewo-sl-section-head{align-items:center;border-bottom:1px solid rgba(50,100,160,.18);display:flex;gap:12px;justify-content:space-between;margin-bottom:16px;padding-bottom:12px}
.ewo-sl-section-title{color:#8aaad4;font-size:.68rem;font-weight:800;letter-spacing:.12em;margin:0;text-transform:uppercase}
.ewo-sl-table-wrap{background:#0b1829;border:1px solid rgba(50,100,160,.28);border-radius:10px;overflow:hidden;margin-bottom:28px}
.ewo-sl-table{border-collapse:collapse;width:100%}
.ewo-sl-table th{background:#060f1e;border-bottom:1px solid rgba(50,100,160,.28);color:#6b88b5;font-size:.65rem;font-weight:800;letter-spacing:.1em;padding:10px 12px;text-align:left;text-transform:uppercase;white-space:nowrap}
.ewo-sl-table th.center{text-align:center}
.ewo-sl-table td{border-bottom:1px solid rgba(50,100,160,.14);color:#dde8f5;font-size:.82rem;padding:10px 12px;vertical-align:middle}
.ewo-sl-table tr:last-child td{border-bottom:0}
.ewo-sl-table tr.custom-row td{background:rgba(215,168,75,.04)}
.ewo-sl-table tr.disabled-row td{opacity:.55}
.ewo-sl-short-badge{align-items:center;background:rgba(0,163,255,.1);border:1px solid rgba(0,163,255,.22);border-radius:50%;color:#d7a84b;display:inline-flex;font-size:.62rem;font-weight:800;height:28px;justify-content:center;letter-spacing:.06em;width:28px}
.ewo-sl-input{background:#0f2035;border:1px solid rgba(50,100,160,.4);border-radius:5px;color:#dde8f5;font-size:.8rem;padding:5px 9px;width:100%}
.ewo-sl-input:focus{border-color:#d7a84b;outline:none}
.ewo-sl-url-input{min-width:200px}
.ewo-sl-order-input{text-align:center;width:52px}
.ewo-sl-chk-cell{text-align:center}
.ewo-sl-chk-cell input[type=checkbox]{accent-color:#d7a84b;cursor:pointer;height:15px;width:15px}
.ewo-sl-tag{background:rgba(139,170,212,.12);border-radius:4px;color:#8aaad4;font-size:.6rem;font-weight:700;letter-spacing:.06em;padding:2px 6px;text-transform:uppercase;white-space:nowrap}
.ewo-sl-tag--custom{background:rgba(215,168,75,.12);color:#d7a84b}
.ewo-sl-delete-link{color:#e05454;font-size:.72rem;font-weight:600;text-decoration:none;white-space:nowrap}
.ewo-sl-delete-link:hover{color:#ff6b6b}
.ewo-sl-btn{background:#d7a84b;border:0;border-radius:6px;color:#0a0600;cursor:pointer;font-size:.8rem;font-weight:700;letter-spacing:.03em;padding:10px 22px;transition:background 140ms}
.ewo-sl-btn:hover{background:#b08020}
.ewo-sl-btn--outline{background:transparent;border:1px solid rgba(50,100,160,.4);color:#8aaad4}
.ewo-sl-btn--outline:hover{border-color:#d7a84b;color:#d7a84b}
.ewo-sl-btn--sm{font-size:.74rem;padding:7px 14px}
.ewo-sl-save-row{padding:16px 0 0;display:flex;gap:10px;align-items:center}
.ewo-sl-add-grid{display:grid;grid-template-columns:80px 1fr 1fr 1fr;gap:10px;margin-bottom:12px}
.ewo-sl-add-toggles{align-items:center;display:flex;gap:18px;flex-wrap:wrap;margin-bottom:14px}
.ewo-sl-toggle-label{align-items:center;display:flex;gap:6px;color:#8aaad4;cursor:pointer;font-size:.8rem;font-weight:600}
.ewo-sl-toggle-label input{accent-color:#d7a84b;height:14px;width:14px}
.ewo-sl-field-label{color:#6b88b5;display:block;font-size:.7rem;font-weight:700;letter-spacing:.07em;margin-bottom:4px;text-transform:uppercase}
.ewo-sl-add-wrap{background:#0b1829;border:1px solid rgba(50,100,160,.28);border-radius:10px;padding:20px 22px}
.ewo-sl-add-title{color:#fff;font-size:.95rem;font-weight:700;margin:0 0 14px}
@media(max-width:900px){.ewo-sl-stats{grid-template-columns:repeat(3,1fr)}.ewo-sl-add-grid{grid-template-columns:1fr 1fr}}
@media(max-width:640px){.ewo-sl-stats{grid-template-columns:1fr 1fr}.ewo-sl-add-grid{grid-template-columns:1fr}.ewo-sl-inner{padding:18px 16px 0}.ewo-sl-page-header{padding:20px 16px 16px}}
</style>

<div class="ewo-sl-page-header">
	<h1><?php esc_html_e( 'Social &amp; Platform Links', 'ewo-2025' ); ?></h1>
	<p><?php esc_html_e( 'Manage the platform chips shown in the site header, footer cards, and sidebar. Enabled + Header = visible in the header chip row. Use Sort Order to reorder.', 'ewo-2025' ); ?></p>
</div>

<div class="ewo-sl-inner">

<?php if ( 'added' === $msg ) : ?>
	<div class="ewo-sl-notice"><?php esc_html_e( 'Platform added.', 'ewo-2025' ); ?></div>
<?php elseif ( 'deleted' === $msg ) : ?>
	<div class="ewo-sl-notice"><?php esc_html_e( 'Platform deleted.', 'ewo-2025' ); ?></div>
<?php elseif ( 'short_required' === $msg ) : ?>
	<div class="ewo-sl-notice ewo-sl-notice--error"><?php esc_html_e( 'Short code is required.', 'ewo-2025' ); ?></div>
<?php endif; ?>

<?php if ( isset( $_GET['settings-updated'] ) ) : ?>
	<div class="ewo-sl-notice"><?php esc_html_e( 'Settings saved.', 'ewo-2025' ); ?></div>
<?php endif; ?>

<!-- Stats -->
<div class="ewo-sl-stats">
	<div class="ewo-sl-stat"><div class="ewo-sl-stat__num"><?php echo esc_html( $total ); ?></div><div class="ewo-sl-stat__label"><?php esc_html_e( 'Total Platforms', 'ewo-2025' ); ?></div></div>
	<div class="ewo-sl-stat"><div class="ewo-sl-stat__num"><?php echo esc_html( $n_enabled ); ?></div><div class="ewo-sl-stat__label"><?php esc_html_e( 'Enabled', 'ewo-2025' ); ?></div></div>
	<div class="ewo-sl-stat"><div class="ewo-sl-stat__num"><?php echo esc_html( $n_header ); ?></div><div class="ewo-sl-stat__label"><?php esc_html_e( 'Header Chips', 'ewo-2025' ); ?></div></div>
	<div class="ewo-sl-stat"><div class="ewo-sl-stat__num"><?php echo esc_html( $n_footer ); ?></div><div class="ewo-sl-stat__label"><?php esc_html_e( 'Footer', 'ewo-2025' ); ?></div></div>
	<div class="ewo-sl-stat"><div class="ewo-sl-stat__num"><?php echo esc_html( $n_custom ); ?></div><div class="ewo-sl-stat__label"><?php esc_html_e( 'Custom Added', 'ewo-2025' ); ?></div></div>
</div>

<!-- Main edit form -->
<div class="ewo-sl-section-head">
	<h2 class="ewo-sl-section-title"><?php esc_html_e( 'Manage Platforms', 'ewo-2025' ); ?></h2>
</div>

<form method="post" action="options.php">
	<?php settings_fields( 'ewo_2025_social_links_group' ); ?>

	<div class="ewo-sl-table-wrap">
		<table class="ewo-sl-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Order', 'ewo-2025' ); ?></th>
					<th><?php esc_html_e( 'Short', 'ewo-2025' ); ?></th>
					<th><?php esc_html_e( 'Platform', 'ewo-2025' ); ?></th>
					<th><?php esc_html_e( 'URL', 'ewo-2025' ); ?></th>
					<th class="center"><?php esc_html_e( 'Header', 'ewo-2025' ); ?></th>
					<th class="center"><?php esc_html_e( 'Footer', 'ewo-2025' ); ?></th>
					<th class="center"><?php esc_html_e( 'Side', 'ewo-2025' ); ?></th>
					<th class="center"><?php esc_html_e( 'On', 'ewo-2025' ); ?></th>
					<th></th>
				</tr>
			</thead>
			<tbody>
			<?php
			$custom_form_idx = 0;
			foreach ( $all as $row ) :
				$is_custom = 'custom' === $row['type'];
				$key       = $row['key'];
				if ( $is_custom ) {
					$base = $option . '[_custom][' . $custom_form_idx . ']';
					$custom_form_idx++;
				} else {
					$base = $option . '[' . $key . ']';
				}
				$row_class = ( ! $row['enabled'] ? 'disabled-row ' : '' ) . ( $is_custom ? 'custom-row' : '' );
				?>
				<tr class="<?php echo esc_attr( trim( $row_class ) ); ?>">
					<td>
						<input type="number" name="<?php echo esc_attr( $base ); ?>[sort_order]"
							value="<?php echo esc_attr( $row['sort_order'] ); ?>"
							min="0" max="999"
							class="ewo-sl-input ewo-sl-order-input">
					</td>
					<td>
						<?php if ( $is_custom ) : ?>
							<input type="hidden" name="<?php echo esc_attr( $base ); ?>[id]" value="<?php echo esc_attr( $key ); ?>">
							<input type="text" name="<?php echo esc_attr( $base ); ?>[short]"
								value="<?php echo esc_attr( $row['short'] ); ?>"
								maxlength="5"
								class="ewo-sl-input"
								style="width:52px;text-align:center;text-transform:uppercase"
								placeholder="YT">
						<?php else : ?>
							<span class="ewo-sl-short-badge"><?php echo esc_html( $row['short'] ); ?></span>
						<?php endif; ?>
					</td>
					<td>
						<?php if ( $is_custom ) : ?>
							<input type="text" name="<?php echo esc_attr( $base ); ?>[label]"
								value="<?php echo esc_attr( $row['label'] ); ?>"
								class="ewo-sl-input"
								style="min-width:110px"
								placeholder="<?php esc_attr_e( 'Platform name', 'ewo-2025' ); ?>">
							<input type="text" name="<?php echo esc_attr( $base ); ?>[detail]"
								value="<?php echo esc_attr( $row['detail'] ); ?>"
								class="ewo-sl-input"
								style="min-width:110px;margin-top:4px"
								placeholder="<?php esc_attr_e( 'Detail / tagline', 'ewo-2025' ); ?>">
						<?php else : ?>
							<span style="font-weight:600"><?php echo esc_html( $row['label'] ); ?></span>
							<br><span class="ewo-sl-tag"><?php esc_html_e( 'Built-in', 'ewo-2025' ); ?></span>
						<?php endif; ?>
					</td>
					<td>
						<input type="url" name="<?php echo esc_attr( $base ); ?>[url]"
							value="<?php echo esc_attr( $row['url'] ); ?>"
							class="ewo-sl-input ewo-sl-url-input"
							placeholder="<?php echo esc_attr( $row['seed_url'] ?: 'https://' ); ?>">
						<?php if ( ! $is_custom && $row['seed_url'] ) : ?>
							<span style="color:#6b88b5;font-size:.68rem;display:block;margin-top:3px">
								<?php esc_html_e( 'Seed:', 'ewo-2025' ); ?> <?php echo esc_html( $row['seed_url'] ); ?>
							</span>
						<?php endif; ?>
					</td>
					<td class="ewo-sl-chk-cell">
						<input type="checkbox" name="<?php echo esc_attr( $base ); ?>[header]" value="1" <?php checked( $row['header'] ); ?>>
					</td>
					<td class="ewo-sl-chk-cell">
						<input type="checkbox" name="<?php echo esc_attr( $base ); ?>[footer]" value="1" <?php checked( $row['footer'] ); ?>>
					</td>
					<td class="ewo-sl-chk-cell">
						<input type="checkbox" name="<?php echo esc_attr( $base ); ?>[sidecard]" value="1" <?php checked( $row['sidecard'] ); ?>>
					</td>
					<td class="ewo-sl-chk-cell">
						<input type="checkbox" name="<?php echo esc_attr( $base ); ?>[enabled]" value="1" <?php checked( $row['enabled'] ); ?>>
					</td>
					<td>
						<?php if ( $is_custom ) :
							$del_url = wp_nonce_url(
								admin_url( 'admin.php?page=ewo-settings&action=delete_cust&id=' . rawurlencode( $key ) ),
								'ewo_sl_del_' . $key
							);
							?>
							<a href="<?php echo esc_url( $del_url ); ?>"
								class="ewo-sl-delete-link"
								onclick="return confirm('<?php esc_attr_e( 'Delete this platform?', 'ewo-2025' ); ?>')">
								<?php esc_html_e( '&#10005; Delete', 'ewo-2025' ); ?>
							</a>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>

	<div class="ewo-sl-save-row">
		<?php submit_button( __( 'Save All Changes', 'ewo-2025' ), 'primary', 'submit', false, array( 'class' => 'ewo-sl-btn' ) ); ?>
		<span style="color:#6b88b5;font-size:.78rem"><?php esc_html_e( 'Saves URLs, toggles, and display order for all platforms above.', 'ewo-2025' ); ?></span>
	</div>
</form>

<!-- Add New Platform -->
<div style="margin-top:36px">
<div class="ewo-sl-section-head">
	<h2 class="ewo-sl-section-title"><?php esc_html_e( 'Add New Platform', 'ewo-2025' ); ?></h2>
</div>
<div class="ewo-sl-add-wrap">
	<p class="ewo-sl-add-title"><?php esc_html_e( '+ New Custom Platform', 'ewo-2025' ); ?></p>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'ewo_social_add' ); ?>
		<input type="hidden" name="action" value="ewo_social_add">

		<div class="ewo-sl-add-grid">
			<div>
				<label class="ewo-sl-field-label" for="sl-new-short"><?php esc_html_e( 'Short Code *', 'ewo-2025' ); ?></label>
				<input id="sl-new-short" type="text" name="short" maxlength="5" required
					class="ewo-sl-input"
					style="text-transform:uppercase"
					placeholder="IG">
				<span style="color:#6b88b5;font-size:.68rem;display:block;margin-top:3px"><?php esc_html_e( 'Max 5 chars', 'ewo-2025' ); ?></span>
			</div>
			<div>
				<label class="ewo-sl-field-label" for="sl-new-label"><?php esc_html_e( 'Platform Name', 'ewo-2025' ); ?></label>
				<input id="sl-new-label" type="text" name="label"
					class="ewo-sl-input" placeholder="<?php esc_attr_e( 'Instagram', 'ewo-2025' ); ?>">
			</div>
			<div>
				<label class="ewo-sl-field-label" for="sl-new-detail"><?php esc_html_e( 'Detail / Tagline', 'ewo-2025' ); ?></label>
				<input id="sl-new-detail" type="text" name="detail"
					class="ewo-sl-input" placeholder="<?php esc_attr_e( 'Follow on Instagram', 'ewo-2025' ); ?>">
			</div>
			<div>
				<label class="ewo-sl-field-label" for="sl-new-url"><?php esc_html_e( 'URL', 'ewo-2025' ); ?></label>
				<input id="sl-new-url" type="url" name="url"
					class="ewo-sl-input" placeholder="https://">
			</div>
		</div>

		<div class="ewo-sl-add-toggles">
			<label class="ewo-sl-toggle-label">
				<input type="checkbox" name="enabled" value="1" checked>
				<?php esc_html_e( 'Enabled', 'ewo-2025' ); ?>
			</label>
			<label class="ewo-sl-toggle-label">
				<input type="checkbox" name="header" value="1" checked>
				<?php esc_html_e( 'Header chip', 'ewo-2025' ); ?>
			</label>
			<label class="ewo-sl-toggle-label">
				<input type="checkbox" name="footer" value="1">
				<?php esc_html_e( 'Footer', 'ewo-2025' ); ?>
			</label>
			<label class="ewo-sl-toggle-label">
				<input type="checkbox" name="sidecard" value="1">
				<?php esc_html_e( 'Side Card', 'ewo-2025' ); ?>
			</label>
			<div style="margin-left:auto;display:flex;align-items:center;gap:8px">
				<label class="ewo-sl-field-label" for="sl-new-order" style="margin:0"><?php esc_html_e( 'Order:', 'ewo-2025' ); ?></label>
				<input id="sl-new-order" type="number" name="sort_order" value="99" min="0" max="999"
					class="ewo-sl-input" style="width:60px;text-align:center">
			</div>
		</div>

		<button type="submit" class="ewo-sl-btn"><?php esc_html_e( '+ Add Platform', 'ewo-2025' ); ?></button>
	</form>
</div>
</div>

</div><!-- .ewo-sl-inner -->
</div><!-- .ewo-sl-wrap -->
	<?php
}
