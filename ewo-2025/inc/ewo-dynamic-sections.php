<?php
/**
 * EWO Dynamic Homepage Sections — Section → Card → Target architecture.
 *
 * Sections and cards are stored in WordPress options.
 * Each card resolves to a URL at render time based on target_type.
 *
 * @package EWO_2025
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'EWO_2025_DYN_SECTIONS_OPTION', 'ewo_2025_dyn_sections' );
define( 'EWO_2025_DYN_CARDS_OPTION',    'ewo_2025_dyn_cards' );

/* ---------------------------------------------------------------------------
 * Built-in section stubs (existing hardcoded homepage sections).
 *
 * These appear in the admin dropdowns and list view but are rendered by
 * front-page.php directly, so ewo_2025_ds_render_all() skips them.
 * Cards assigned to these IDs are picked up by front-page.php at render time.
 * ------------------------------------------------------------------------- */

function ewo_2025_ds_builtin_sections() {
	return array(
		array(
			'id'            => 'builtin_latest_analysis',
			'title'         => 'Latest Analysis',
			'eyebrow'       => 'Intelligence',
			'display_order' => -40,
			'enabled'       => 1,
			'builtin'       => 1,
		),
		array(
			'id'            => 'builtin_strategic_playlists',
			'title'         => 'Strategic Playlists',
			'eyebrow'       => 'Curated Series',
			'display_order' => -30,
			'enabled'       => 1,
			'builtin'       => 1,
		),
		array(
			'id'            => 'builtin_featured_cards',
			'title'         => 'Featured Cards',
			'eyebrow'       => 'Featured',
			'display_order' => -20,
			'enabled'       => 1,
			'builtin'       => 1,
		),
		array(
			'id'            => 'builtin_custom_section',
			'title'         => 'Custom Section',
			'eyebrow'       => 'More',
			'display_order' => -10,
			'enabled'       => 1,
			'builtin'       => 1,
		),
	);
}

/* ---------------------------------------------------------------------------
 * Data helpers
 * ------------------------------------------------------------------------- */

function ewo_2025_ds_get_all_sections() {
	static $cache = null;
	if ( null === $cache ) {
		$saved = get_option( EWO_2025_DYN_SECTIONS_OPTION, array() );
		$cache = is_array( $saved ) ? $saved : array();
		usort( $cache, function ( $a, $b ) {
			return (int) ( $a['display_order'] ?? 0 ) - (int) ( $b['display_order'] ?? 0 );
		} );
	}
	return $cache;
}

function ewo_2025_ds_get_enabled_sections() {
	return array_values( array_filter( ewo_2025_ds_get_all_sections(), function ( $s ) {
		return ! empty( $s['enabled'] );
	} ) );
}

function ewo_2025_ds_get_section_by_id( $id ) {
	foreach ( ewo_2025_ds_get_all_sections() as $sec ) {
		if ( ( $sec['id'] ?? '' ) === $id ) {
			return $sec;
		}
	}
	return null;
}

function ewo_2025_ds_get_all_cards() {
	static $cache = null;
	if ( null === $cache ) {
		$saved = get_option( EWO_2025_DYN_CARDS_OPTION, array() );
		$cache = is_array( $saved ) ? $saved : array();
		usort( $cache, function ( $a, $b ) {
			return (int) ( $a['display_order'] ?? 0 ) - (int) ( $b['display_order'] ?? 0 );
		} );
	}
	return $cache;
}

function ewo_2025_ds_get_cards_for_section( $section_id, $enabled_only = true ) {
	return array_values( array_filter( ewo_2025_ds_get_all_cards(), function ( $c ) use ( $section_id, $enabled_only ) {
		if ( ( $c['section_id'] ?? '' ) !== $section_id ) {
			return false;
		}
		return ! $enabled_only || ! empty( $c['enabled'] );
	} ) );
}

function ewo_2025_ds_resolve_url( $card ) {
	$type  = $card['target_type']  ?? 'external';
	$value = $card['target_value'] ?? '';
	if ( '' === (string) $value ) {
		return '';
	}
	switch ( $type ) {
		case 'post':
		case 'page':
			$url = get_permalink( (int) $value );
			return $url ? (string) $url : '';
		case 'category':
			$url = get_category_link( (int) $value );
			return ( ! is_wp_error( $url ) ) ? (string) $url : '';
		default:
			return esc_url_raw( $value );
	}
}

function ewo_2025_ds_target_type_label( $type ) {
	$labels = array(
		'post'             => __( 'WordPress Post', 'ewo-2025' ),
		'page'             => __( 'WordPress Page', 'ewo-2025' ),
		'category'         => __( 'Category Archive', 'ewo-2025' ),
		'youtube_video'    => __( 'YouTube Video', 'ewo-2025' ),
		'youtube_playlist' => __( 'YouTube Playlist', 'ewo-2025' ),
		'substack'         => __( 'Substack URL', 'ewo-2025' ),
		'rss'              => __( 'RSS Article', 'ewo-2025' ),
		'external'         => __( 'External URL', 'ewo-2025' ),
	);
	return $labels[ $type ] ?? ucfirst( $type );
}

/* ---------------------------------------------------------------------------
 * Admin menus
 * ------------------------------------------------------------------------- */

function ewo_2025_ds_admin_menu() {
	add_submenu_page(
		'ewo-settings',
		__( 'Homepage Sections', 'ewo-2025' ),
		__( 'Homepage Sections', 'ewo-2025' ),
		'manage_options',
		'ewo-dyn-sections',
		'ewo_2025_ds_sections_page'
	);
	add_submenu_page(
		'ewo-settings',
		__( 'Homepage Cards', 'ewo-2025' ),
		__( 'Homepage Cards', 'ewo-2025' ),
		'manage_options',
		'ewo-dyn-cards',
		'ewo_2025_ds_cards_page'
	);
}
add_action( 'admin_menu', 'ewo_2025_ds_admin_menu' );

/* ---------------------------------------------------------------------------
 * Section save handler
 * ------------------------------------------------------------------------- */

function ewo_2025_ds_sec_handle_save() {
	check_admin_referer( 'ewo_ds_sec_save', 'ewo_ds_sec_nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Permission denied.', 'ewo-2025' ) );
	}

	$raw     = isset( $_POST['ewo_ds_sec'] ) && is_array( $_POST['ewo_ds_sec'] ) // phpcs:ignore WordPress.Security.NonceVerification
		? $_POST['ewo_ds_sec'] // phpcs:ignore WordPress.Security.NonceVerification
		: array();
	$edit_id = isset( $_POST['ewo_ds_sec_id'] ) // phpcs:ignore WordPress.Security.NonceVerification
		? sanitize_key( wp_unslash( $_POST['ewo_ds_sec_id'] ) ) // phpcs:ignore WordPress.Security.NonceVerification
		: '';
	$now = gmdate( 'Y-m-d H:i:s' );

	$section = array(
		'id'            => $edit_id ?: ( 'sec_' . uniqid() ),
		'title'         => sanitize_text_field( wp_unslash( $raw['title']       ?? '' ) ),
		'eyebrow'       => sanitize_text_field( wp_unslash( $raw['eyebrow']     ?? '' ) ),
		'description'   => sanitize_textarea_field( wp_unslash( $raw['description'] ?? '' ) ),
		'cta_text'      => sanitize_text_field( wp_unslash( $raw['cta_text']    ?? '' ) ),
		'cta_url'       => esc_url_raw( wp_unslash( $raw['cta_url'] ?? '' ) ),
		'display_order' => max( 0, min( 999, (int) ( $raw['display_order'] ?? 0 ) ) ),
		'enabled'       => ! empty( $raw['enabled'] ) ? 1 : 0,
		'created'       => $now,
		'updated'       => $now,
	);

	$all = get_option( EWO_2025_DYN_SECTIONS_OPTION, array() );
	if ( ! is_array( $all ) ) {
		$all = array();
	}

	if ( $edit_id ) {
		foreach ( $all as $i => $s ) {
			if ( ( $s['id'] ?? '' ) === $edit_id ) {
				$section['created'] = $s['created'] ?? $now;
				$all[ $i ]          = $section;
				break;
			}
		}
	} else {
		$all[] = $section;
	}

	update_option( EWO_2025_DYN_SECTIONS_OPTION, $all );
	wp_redirect( admin_url( 'admin.php?page=ewo-dyn-sections&saved=1' ) );
	exit;
}
add_action( 'admin_post_ewo_ds_sec_save', 'ewo_2025_ds_sec_handle_save' );

/* ---------------------------------------------------------------------------
 * Card save handler
 * ------------------------------------------------------------------------- */

function ewo_2025_ds_card_handle_save() {
	check_admin_referer( 'ewo_ds_card_save', 'ewo_ds_card_nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Permission denied.', 'ewo-2025' ) );
	}

	$raw     = isset( $_POST['ewo_ds_card'] ) && is_array( $_POST['ewo_ds_card'] ) // phpcs:ignore WordPress.Security.NonceVerification
		? $_POST['ewo_ds_card'] // phpcs:ignore WordPress.Security.NonceVerification
		: array();
	$edit_id = isset( $_POST['ewo_ds_card_id'] ) // phpcs:ignore WordPress.Security.NonceVerification
		? sanitize_key( wp_unslash( $_POST['ewo_ds_card_id'] ) ) // phpcs:ignore WordPress.Security.NonceVerification
		: '';
	$now = gmdate( 'Y-m-d H:i:s' );

	$valid_types = array( 'post', 'page', 'category', 'youtube_video', 'youtube_playlist', 'substack', 'rss', 'external' );
	$target_type = isset( $raw['target_type'] ) && in_array( $raw['target_type'], $valid_types, true )
		? $raw['target_type'] : 'external';

	// Consolidate target_value from the appropriate sub-field.
	if ( 'post' === $target_type ) {
		$target_value = isset( $raw['target_post_id'] ) ? (string) (int) $raw['target_post_id'] : '';
	} elseif ( 'page' === $target_type ) {
		$target_value = isset( $raw['target_page_id'] ) ? (string) (int) $raw['target_page_id'] : '';
	} elseif ( 'category' === $target_type ) {
		$target_value = isset( $raw['target_cat_id'] ) ? (string) (int) $raw['target_cat_id'] : '';
	} else {
		$target_value = isset( $raw['target_url'] ) ? esc_url_raw( wp_unslash( $raw['target_url'] ) ) : '';
	}

	$card = array(
		'id'            => $edit_id ?: ( 'card_' . uniqid() ),
		'section_id'    => sanitize_key( $raw['section_id'] ?? '' ),
		'title'         => sanitize_text_field( wp_unslash( $raw['title']       ?? '' ) ),
		'eyebrow'       => sanitize_text_field( wp_unslash( $raw['eyebrow']     ?? '' ) ),
		'description'   => sanitize_textarea_field( wp_unslash( $raw['description'] ?? '' ) ),
		'image_url'     => esc_url_raw( wp_unslash( $raw['image_url'] ?? '' ) ),
		'target_type'   => $target_type,
		'target_value'  => $target_value,
		'display_order' => max( 0, min( 999, (int) ( $raw['display_order'] ?? 0 ) ) ),
		'enabled'       => ! empty( $raw['enabled'] ) ? 1 : 0,
		'created'       => $now,
		'updated'       => $now,
	);

	$all = get_option( EWO_2025_DYN_CARDS_OPTION, array() );
	if ( ! is_array( $all ) ) {
		$all = array();
	}

	if ( $edit_id ) {
		foreach ( $all as $i => $c ) {
			if ( ( $c['id'] ?? '' ) === $edit_id ) {
				$card['created'] = $c['created'] ?? $now;
				$all[ $i ]       = $card;
				break;
			}
		}
	} else {
		$all[] = $card;
	}

	update_option( EWO_2025_DYN_CARDS_OPTION, $all );
	wp_redirect( admin_url( 'admin.php?page=ewo-dyn-cards&saved=1' ) );
	exit;
}
add_action( 'admin_post_ewo_ds_card_save', 'ewo_2025_ds_card_handle_save' );

/* ---------------------------------------------------------------------------
 * Homepage Sections admin page
 * ------------------------------------------------------------------------- */

function ewo_2025_ds_sections_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Inline delete — also removes all cards belonging to the section.
	if (
		isset( $_GET['action'], $_GET['sec_id'] ) // phpcs:ignore WordPress.Security.NonceVerification
		&& 'delete' === sanitize_key( wp_unslash( $_GET['action'] ) ) // phpcs:ignore WordPress.Security.NonceVerification
	) {
		$sec_id = sanitize_key( wp_unslash( $_GET['sec_id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		if ( str_starts_with( $sec_id, 'builtin_' ) ) {
			wp_redirect( admin_url( 'admin.php?page=ewo-dyn-sections' ) );
			exit;
		}
		check_admin_referer( 'ewo_ds_sec_del_' . $sec_id );

		$all = get_option( EWO_2025_DYN_SECTIONS_OPTION, array() );
		if ( is_array( $all ) ) {
			$all = array_values( array_filter( $all, function ( $s ) use ( $sec_id ) {
				return ( $s['id'] ?? '' ) !== $sec_id;
			} ) );
			update_option( EWO_2025_DYN_SECTIONS_OPTION, $all );
		}
		// Remove orphaned cards.
		$cards = get_option( EWO_2025_DYN_CARDS_OPTION, array() );
		if ( is_array( $cards ) ) {
			$cards = array_values( array_filter( $cards, function ( $c ) use ( $sec_id ) {
				return ( $c['section_id'] ?? '' ) !== $sec_id;
			} ) );
			update_option( EWO_2025_DYN_CARDS_OPTION, $cards );
		}

		wp_redirect( admin_url( 'admin.php?page=ewo-dyn-sections&deleted=1' ) );
		exit;
	}

	$action  = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$sec_id  = isset( $_GET['sec_id'] ) ? sanitize_key( wp_unslash( $_GET['sec_id'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$saved   = isset( $_GET['saved']   ) && '1' === sanitize_text_field( wp_unslash( $_GET['saved']   ) ); // phpcs:ignore WordPress.Security.NonceVerification
	$deleted = isset( $_GET['deleted'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['deleted'] ) ); // phpcs:ignore WordPress.Security.NonceVerification

	$edit_section = null;
	if ( 'edit' === $action && $sec_id ) {
		$edit_section = ewo_2025_ds_get_section_by_id( $sec_id );
	}

	ewo_2025_ds_admin_css();
	echo '<div id="ewo-ds-page">';
	echo '<div class="ds-head">';
	echo '<h1>' . esc_html__( 'Homepage Sections', 'ewo-2025' ) . '</h1>';
	echo '<p>' . esc_html__( 'Create and manage dynamic homepage sections. Each section contains cards that link to any content type.', 'ewo-2025' ) . '</p>';
	echo '</div>';

	if ( $saved )   { echo '<div class="ds-notice">' . esc_html__( 'Section saved.', 'ewo-2025' ) . '</div>'; }
	if ( $deleted ) { echo '<div class="ds-notice">' . esc_html__( 'Section and its cards deleted.', 'ewo-2025' ) . '</div>'; }

	if ( 'add' === $action || 'edit' === $action ) {
		ewo_2025_ds_sections_render_form( $edit_section );
	} else {
		ewo_2025_ds_sections_render_list();
	}
	echo '</div>';
}

/* ---------------------------------------------------------------------------
 * Homepage Cards admin page
 * ------------------------------------------------------------------------- */

function ewo_2025_ds_cards_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Inline delete.
	if (
		isset( $_GET['action'], $_GET['card_id'] ) // phpcs:ignore WordPress.Security.NonceVerification
		&& 'delete' === sanitize_key( wp_unslash( $_GET['action'] ) ) // phpcs:ignore WordPress.Security.NonceVerification
	) {
		$card_id = sanitize_key( wp_unslash( $_GET['card_id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		check_admin_referer( 'ewo_ds_card_del_' . $card_id );

		$all = get_option( EWO_2025_DYN_CARDS_OPTION, array() );
		if ( is_array( $all ) ) {
			$all = array_values( array_filter( $all, function ( $c ) use ( $card_id ) {
				return ( $c['id'] ?? '' ) !== $card_id;
			} ) );
			update_option( EWO_2025_DYN_CARDS_OPTION, $all );
		}

		wp_redirect( admin_url( 'admin.php?page=ewo-dyn-cards&deleted=1' ) );
		exit;
	}

	$action  = isset( $_GET['action']  ) ? sanitize_key( wp_unslash( $_GET['action']  ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$card_id = isset( $_GET['card_id'] ) ? sanitize_key( wp_unslash( $_GET['card_id'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$saved   = isset( $_GET['saved']   ) && '1' === sanitize_text_field( wp_unslash( $_GET['saved']   ) ); // phpcs:ignore WordPress.Security.NonceVerification
	$deleted = isset( $_GET['deleted'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['deleted'] ) ); // phpcs:ignore WordPress.Security.NonceVerification

	$edit_card = null;
	if ( 'edit' === $action && $card_id ) {
		$all_cards = get_option( EWO_2025_DYN_CARDS_OPTION, array() );
		if ( is_array( $all_cards ) ) {
			foreach ( $all_cards as $c ) {
				if ( ( $c['id'] ?? '' ) === $card_id ) {
					$edit_card = $c;
					break;
				}
			}
		}
	}

	ewo_2025_ds_admin_css();
	echo '<div id="ewo-ds-page">';
	echo '<div class="ds-head">';
	echo '<h1>' . esc_html__( 'Homepage Cards', 'ewo-2025' ) . '</h1>';
	echo '<p>' . esc_html__( 'Create cards and assign them to sections. Each card links to a post, page, video, or any URL.', 'ewo-2025' ) . '</p>';
	echo '</div>';

	if ( $saved )   { echo '<div class="ds-notice">' . esc_html__( 'Card saved.', 'ewo-2025' ) . '</div>'; }
	if ( $deleted ) { echo '<div class="ds-notice">' . esc_html__( 'Card deleted.', 'ewo-2025' ) . '</div>'; }

	if ( 'add' === $action || 'edit' === $action ) {
		ewo_2025_ds_cards_render_form( $edit_card );
	} else {
		ewo_2025_ds_cards_render_list();
	}
	echo '</div>';
}

/* ---------------------------------------------------------------------------
 * Sections list view
 * ------------------------------------------------------------------------- */

function ewo_2025_ds_sections_render_list() {
	$all      = ewo_2025_ds_get_all_sections();
	$builtins = ewo_2025_ds_builtin_sections();
	$add_url  = admin_url( 'admin.php?page=ewo-dyn-sections&action=add' );
	?>
	<div class="ds-list-header">
		<a class="ds-btn-add" href="<?php echo esc_url( $add_url ); ?>"><?php esc_html_e( '+ Add New Section', 'ewo-2025' ); ?></a>
	</div>
	<?php if ( empty( $all ) ) : ?>
		<div class="ds-empty">
			<p><?php esc_html_e( 'No sections yet. Sections appear on the homepage in display order.', 'ewo-2025' ); ?></p>
			<a class="ds-btn-add" href="<?php echo esc_url( $add_url ); ?>"><?php esc_html_e( 'Create First Section', 'ewo-2025' ); ?></a>
		</div>
	<?php else : ?>
		<div class="ds-table-wrap">
			<table class="ds-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Order', 'ewo-2025' ); ?></th>
						<th><?php esc_html_e( 'Title', 'ewo-2025' ); ?></th>
						<th><?php esc_html_e( 'Eyebrow', 'ewo-2025' ); ?></th>
						<th><?php esc_html_e( 'CTA', 'ewo-2025' ); ?></th>
						<th><?php esc_html_e( 'Cards', 'ewo-2025' ); ?></th>
						<th><?php esc_html_e( 'Status', 'ewo-2025' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'ewo-2025' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					// Built-in sections first (read-only rows).
					foreach ( $builtins as $sec ) :
						$sid        = $sec['id'] ?? '';
						$cards_url  = admin_url( 'admin.php?page=ewo-dyn-cards&filter_section=' . rawurlencode( $sid ) );
						$card_count = count( ewo_2025_ds_get_cards_for_section( $sid, false ) );
					?>
					<tr class="ds-row-builtin">
						<td class="ds-col-order"><span class="ds-text-muted">—</span></td>
						<td class="ds-col-title">
							<?php echo esc_html( $sec['title'] ?? '' ); ?>
							<span class="ds-badge ds-badge--builtin"><?php esc_html_e( 'Built-in', 'ewo-2025' ); ?></span>
						</td>
						<td><?php echo esc_html( $sec['eyebrow'] ?? '' ); ?></td>
						<td><span class="ds-text-muted">—</span></td>
						<td>
							<a class="ds-link-muted" href="<?php echo esc_url( $cards_url ); ?>">
								<?php echo esc_html( (string) $card_count ); ?>
							</a>
						</td>
						<td><span class="ds-badge ds-badge--on"><?php esc_html_e( 'On', 'ewo-2025' ); ?></span></td>
						<td class="ds-col-actions ds-text-muted"><?php esc_html_e( 'Managed by theme', 'ewo-2025' ); ?></td>
					</tr>
					<?php endforeach; ?>
					<?php foreach ( $all as $sec ) :
						$sid       = $sec['id'] ?? '';
						$edit_url  = admin_url( 'admin.php?page=ewo-dyn-sections&action=edit&sec_id=' . rawurlencode( $sid ) );
						$del_url   = wp_nonce_url(
							admin_url( 'admin.php?page=ewo-dyn-sections&action=delete&sec_id=' . rawurlencode( $sid ) ),
							'ewo_ds_sec_del_' . $sid
						);
						$cards_url  = admin_url( 'admin.php?page=ewo-dyn-cards&filter_section=' . rawurlencode( $sid ) );
						$card_count = count( ewo_2025_ds_get_cards_for_section( $sid, false ) );
						$enabled    = ! empty( $sec['enabled'] );
						$has_cta    = ! empty( $sec['cta_text'] ) && ! empty( $sec['cta_url'] );
					?>
					<tr>
						<td class="ds-col-order"><?php echo esc_html( (string) ( $sec['display_order'] ?? 0 ) ); ?></td>
						<td class="ds-col-title"><?php echo esc_html( $sec['title'] ?? '' ); ?></td>
						<td><?php echo esc_html( $sec['eyebrow'] ?? '' ); ?></td>
						<td><?php echo $has_cta ? '<span class="ds-badge ds-badge--on">✓</span>' : '<span class="ds-text-muted">—</span>'; ?></td>
						<td>
							<a class="ds-link-muted" href="<?php echo esc_url( $cards_url ); ?>">
								<?php echo esc_html( (string) $card_count ); ?>
							</a>
						</td>
						<td>
							<span class="ds-badge ds-badge--<?php echo $enabled ? 'on' : 'off'; ?>">
								<?php echo $enabled ? esc_html__( 'On', 'ewo-2025' ) : esc_html__( 'Off', 'ewo-2025' ); ?>
							</span>
						</td>
						<td class="ds-col-actions">
							<a class="ds-link-edit" href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Edit', 'ewo-2025' ); ?></a>
							<a class="ds-link-delete" href="<?php echo esc_url( $del_url ); ?>" onclick="return confirm('<?php esc_attr_e( 'Delete this section and all its cards?', 'ewo-2025' ); ?>')"><?php esc_html_e( 'Delete', 'ewo-2025' ); ?></a>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<p class="ds-table-hint"><?php esc_html_e( 'Click the card count to view and add cards for a section. Toggle visibility in EWO Settings → Feature Visibility.', 'ewo-2025' ); ?></p>
	<?php endif;
}

/* ---------------------------------------------------------------------------
 * Section add / edit form
 * ------------------------------------------------------------------------- */

function ewo_2025_ds_sections_render_form( $section = null ) {
	$is_edit    = ! empty( $section );
	$form_title = $is_edit ? __( 'Edit Section', 'ewo-2025' ) : __( 'Add New Section', 'ewo-2025' );
	$back_url   = admin_url( 'admin.php?page=ewo-dyn-sections' );

	$v_title   = $is_edit ? ( $section['title']         ?? '' ) : '';
	$v_eyebrow = $is_edit ? ( $section['eyebrow']       ?? '' ) : '';
	$v_desc    = $is_edit ? ( $section['description']   ?? '' ) : '';
	$v_cta_txt = $is_edit ? ( $section['cta_text']      ?? '' ) : '';
	$v_cta_url = $is_edit ? ( $section['cta_url']       ?? '' ) : '';
	$v_order   = $is_edit ? (int) ( $section['display_order'] ?? 0 ) : 0;
	$v_enabled = ! $is_edit || ! empty( $section['enabled'] );
	?>
	<div class="ds-form-head">
		<a class="ds-back" href="<?php echo esc_url( $back_url ); ?>">&larr; <?php esc_html_e( 'All Sections', 'ewo-2025' ); ?></a>
		<h2><?php echo esc_html( $form_title ); ?></h2>
	</div>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="ewo_ds_sec_save">
		<input type="hidden" name="ewo_ds_sec_id" value="<?php echo esc_attr( $is_edit ? ( $section['id'] ?? '' ) : '' ); ?>">
		<?php wp_nonce_field( 'ewo_ds_sec_save', 'ewo_ds_sec_nonce' ); ?>
		<div class="ds-form-grid">

			<div class="ds-form-card ds-form-card--wide">
				<div class="ds-form-card__head"><h3><?php esc_html_e( 'Section Identity', 'ewo-2025' ); ?></h3></div>
				<div class="ds-field">
					<label for="ds-sec-title"><?php esc_html_e( 'Section Title *', 'ewo-2025' ); ?></label>
					<input class="ds-input" type="text" id="ds-sec-title" name="ewo_ds_sec[title]" value="<?php echo esc_attr( $v_title ); ?>" required placeholder="India Energy">
				</div>
				<div class="ds-field">
					<label for="ds-sec-eyebrow"><?php esc_html_e( 'Eyebrow Label', 'ewo-2025' ); ?></label>
					<p class="ds-desc"><?php esc_html_e( 'Small text above the title, e.g. "Intelligence", "Analysis".', 'ewo-2025' ); ?></p>
					<input class="ds-input" type="text" id="ds-sec-eyebrow" name="ewo_ds_sec[eyebrow]" value="<?php echo esc_attr( $v_eyebrow ); ?>" placeholder="Intelligence">
				</div>
				<div class="ds-field">
					<label for="ds-sec-desc"><?php esc_html_e( 'Description', 'ewo-2025' ); ?></label>
					<textarea class="ds-textarea" id="ds-sec-desc" name="ewo_ds_sec[description]" rows="3"><?php echo esc_textarea( $v_desc ); ?></textarea>
				</div>
			</div>

			<div class="ds-form-card">
				<div class="ds-form-card__head"><h3><?php esc_html_e( 'CTA Button', 'ewo-2025' ); ?></h3></div>
				<div class="ds-field">
					<label for="ds-sec-cta-txt"><?php esc_html_e( 'Button Text', 'ewo-2025' ); ?></label>
					<input class="ds-input" type="text" id="ds-sec-cta-txt" name="ewo_ds_sec[cta_text]" value="<?php echo esc_attr( $v_cta_txt ); ?>" placeholder="View All">
				</div>
				<div class="ds-field">
					<label for="ds-sec-cta-url"><?php esc_html_e( 'Button URL', 'ewo-2025' ); ?></label>
					<input class="ds-input" type="url" id="ds-sec-cta-url" name="ewo_ds_sec[cta_url]" value="<?php echo esc_attr( $v_cta_url ); ?>" placeholder="https://...">
				</div>
			</div>

			<div class="ds-form-card">
				<div class="ds-form-card__head"><h3><?php esc_html_e( 'Display', 'ewo-2025' ); ?></h3></div>
				<div class="ds-field">
					<label for="ds-sec-order"><?php esc_html_e( 'Display Order', 'ewo-2025' ); ?></label>
					<p class="ds-desc"><?php esc_html_e( 'Lower numbers appear first (0–999).', 'ewo-2025' ); ?></p>
					<input class="ds-input" type="number" id="ds-sec-order" name="ewo_ds_sec[display_order]" value="<?php echo esc_attr( (string) $v_order ); ?>" min="0" max="999">
				</div>
				<div class="ds-field ds-field--toggle">
					<label><?php esc_html_e( 'Enabled', 'ewo-2025' ); ?></label>
					<div class="ds-tw">
						<label class="ds-tgl">
							<input type="checkbox" name="ewo_ds_sec[enabled]" value="1"<?php checked( $v_enabled ); ?> onchange="dsToggleStatus(this,'ds-sec-status')">
							<span class="ds-sld"></span>
						</label>
						<span class="ds-ts<?php echo $v_enabled ? ' ds-ts--on' : ''; ?>" id="ds-sec-status">
							<?php echo $v_enabled ? esc_html__( 'Enabled', 'ewo-2025' ) : esc_html__( 'Disabled', 'ewo-2025' ); ?>
						</span>
					</div>
				</div>
			</div>

		</div>
		<div class="ds-form-actions">
			<button type="submit" class="ds-save">
				<?php echo $is_edit ? esc_html__( 'Update Section', 'ewo-2025' ) : esc_html__( 'Create Section', 'ewo-2025' ); ?>
			</button>
			<a class="ds-cancel" href="<?php echo esc_url( $back_url ); ?>"><?php esc_html_e( 'Cancel', 'ewo-2025' ); ?></a>
		</div>
	</form>
	<script>
	window.dsToggleStatus = function(el, labelId) {
		var label = document.getElementById(labelId);
		if (!label) return;
		if (el.checked) { label.textContent = '<?php echo esc_js( __( 'Enabled', 'ewo-2025' ) ); ?>'; label.classList.add('ds-ts--on'); }
		else            { label.textContent = '<?php echo esc_js( __( 'Disabled', 'ewo-2025' ) ); ?>'; label.classList.remove('ds-ts--on'); }
	};
	</script>
	<?php
}

/* ---------------------------------------------------------------------------
 * Cards list view
 * ------------------------------------------------------------------------- */

function ewo_2025_ds_cards_render_list() {
	$all_cards    = ewo_2025_ds_get_all_cards();
	$all_sections = ewo_2025_ds_get_all_sections();
	$add_url      = admin_url( 'admin.php?page=ewo-dyn-cards&action=add' );

	$filter_sec = isset( $_GET['filter_section'] ) // phpcs:ignore WordPress.Security.NonceVerification
		? sanitize_key( wp_unslash( $_GET['filter_section'] ) ) // phpcs:ignore WordPress.Security.NonceVerification
		: '';
	if ( $filter_sec ) {
		$all_cards = array_values( array_filter( $all_cards, function ( $c ) use ( $filter_sec ) {
			return ( $c['section_id'] ?? '' ) === $filter_sec;
		} ) );
	}

	$sec_name_map = array();
	foreach ( ewo_2025_ds_builtin_sections() as $s ) {
		$sec_name_map[ $s['id'] ?? '' ] = ( $s['title'] ?? '' ) . ' (Built-in)';
	}
	foreach ( $all_sections as $s ) {
		$sec_name_map[ $s['id'] ?? '' ] = $s['title'] ?? '';
	}
	?>
	<div class="ds-list-header">
		<a class="ds-btn-add" href="<?php echo esc_url( $add_url ); ?>"><?php esc_html_e( '+ Add New Card', 'ewo-2025' ); ?></a>
		<?php if ( $filter_sec && isset( $sec_name_map[ $filter_sec ] ) ) : ?>
			<span class="ds-filter-pill">
				<?php printf( esc_html__( 'Section: %s', 'ewo-2025' ), esc_html( $sec_name_map[ $filter_sec ] ) ); ?>
				&nbsp;<a href="<?php echo esc_url( admin_url( 'admin.php?page=ewo-dyn-cards' ) ); ?>">&times;</a>
			</span>
		<?php endif; ?>
		<?php if ( ! empty( $all_sections ) ) : ?>
			<a class="ds-link-muted" href="<?php echo esc_url( admin_url( 'admin.php?page=ewo-dyn-sections' ) ); ?>">
				<?php esc_html_e( '← Manage Sections', 'ewo-2025' ); ?>
			</a>
		<?php endif; ?>
	</div>
	<?php if ( empty( $all_cards ) ) : ?>
		<div class="ds-empty">
			<?php if ( empty( $all_sections ) ) : ?>
				<p><?php esc_html_e( 'Create a section first, then add cards to it.', 'ewo-2025' ); ?></p>
				<a class="ds-btn-add" href="<?php echo esc_url( admin_url( 'admin.php?page=ewo-dyn-sections&action=add' ) ); ?>"><?php esc_html_e( 'Create a Section', 'ewo-2025' ); ?></a>
			<?php else : ?>
				<p><?php esc_html_e( 'No cards yet. Cards appear inside their assigned section on the homepage.', 'ewo-2025' ); ?></p>
				<a class="ds-btn-add" href="<?php echo esc_url( $add_url ); ?>"><?php esc_html_e( 'Add First Card', 'ewo-2025' ); ?></a>
			<?php endif; ?>
		</div>
	<?php else : ?>
		<div class="ds-table-wrap">
			<table class="ds-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Order', 'ewo-2025' ); ?></th>
						<th><?php esc_html_e( 'Title', 'ewo-2025' ); ?></th>
						<th><?php esc_html_e( 'Section', 'ewo-2025' ); ?></th>
						<th><?php esc_html_e( 'Target', 'ewo-2025' ); ?></th>
						<th><?php esc_html_e( 'Status', 'ewo-2025' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'ewo-2025' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $all_cards as $card ) :
						$cid      = $card['id'] ?? '';
						$edit_url = admin_url( 'admin.php?page=ewo-dyn-cards&action=edit&card_id=' . rawurlencode( $cid ) );
						$del_url  = wp_nonce_url(
							admin_url( 'admin.php?page=ewo-dyn-cards&action=delete&card_id=' . rawurlencode( $cid ) ),
							'ewo_ds_card_del_' . $cid
						);
						$sec_name = $sec_name_map[ $card['section_id'] ?? '' ] ?? '—';
						$type_lbl = ewo_2025_ds_target_type_label( $card['target_type'] ?? 'external' );
						$enabled  = ! empty( $card['enabled'] );
					?>
					<tr>
						<td class="ds-col-order"><?php echo esc_html( (string) ( $card['display_order'] ?? 0 ) ); ?></td>
						<td class="ds-col-title"><?php echo esc_html( $card['title'] ?? '' ); ?></td>
						<td><?php echo esc_html( $sec_name ); ?></td>
						<td><?php echo esc_html( $type_lbl ); ?></td>
						<td>
							<span class="ds-badge ds-badge--<?php echo $enabled ? 'on' : 'off'; ?>">
								<?php echo $enabled ? esc_html__( 'On', 'ewo-2025' ) : esc_html__( 'Off', 'ewo-2025' ); ?>
							</span>
						</td>
						<td class="ds-col-actions">
							<a class="ds-link-edit" href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Edit', 'ewo-2025' ); ?></a>
							<a class="ds-link-delete" href="<?php echo esc_url( $del_url ); ?>" onclick="return confirm('<?php esc_attr_e( 'Delete this card?', 'ewo-2025' ); ?>')"><?php esc_html_e( 'Delete', 'ewo-2025' ); ?></a>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif;
}

/* ---------------------------------------------------------------------------
 * Card add / edit form
 * ------------------------------------------------------------------------- */

function ewo_2025_ds_cards_render_form( $card = null ) {
	$is_edit    = ! empty( $card );
	$form_title = $is_edit ? __( 'Edit Card', 'ewo-2025' ) : __( 'Add New Card', 'ewo-2025' );
	$back_url   = admin_url( 'admin.php?page=ewo-dyn-cards' );

	$v_title   = $is_edit ? ( $card['title']        ?? '' ) : '';
	$v_eyebrow = $is_edit ? ( $card['eyebrow']      ?? '' ) : '';
	$v_desc    = $is_edit ? ( $card['description']  ?? '' ) : '';
	$v_image   = $is_edit ? ( $card['image_url']    ?? '' ) : '';
	$v_sec_id  = $is_edit ? ( $card['section_id']   ?? '' ) : '';
	$v_ttype   = $is_edit ? ( $card['target_type']  ?? 'external' ) : 'external';
	$v_tvalue  = $is_edit ? ( $card['target_value'] ?? '' ) : '';
	$v_order   = $is_edit ? (int) ( $card['display_order'] ?? 0 ) : 0;
	$v_enabled = ! $is_edit || ! empty( $card['enabled'] );

	// Split stored target_value back into type-specific fields.
	$url_types = array( 'youtube_video', 'youtube_playlist', 'substack', 'rss', 'external' );
	$v_url     = in_array( $v_ttype, $url_types, true ) ? $v_tvalue : '';
	$v_post_id = 'post'     === $v_ttype ? (int) $v_tvalue : 0;
	$v_page_id = 'page'     === $v_ttype ? (int) $v_tvalue : 0;
	$v_cat_id  = 'category' === $v_ttype ? (int) $v_tvalue : 0;

	// Dropdown data — built-ins first, then user-created sections.
	$all_sections  = ewo_2025_ds_get_all_sections();
	$builtin_secs  = ewo_2025_ds_builtin_sections();
	$pages        = get_pages( array( 'sort_column' => 'post_title', 'sort_order' => 'ASC' ) ) ?: array();
	$posts        = get_posts( array(
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => 50,
		'orderby'        => 'date',
		'order'          => 'DESC',
	) );
	$categories = get_categories( array( 'hide_empty' => false ) );
	if ( ! $categories || is_wp_error( $categories ) ) {
		$categories = array();
	}

	$type_opts = array(
		'post'             => __( 'WordPress Post', 'ewo-2025' ),
		'page'             => __( 'WordPress Page', 'ewo-2025' ),
		'category'         => __( 'Category Archive', 'ewo-2025' ),
		'youtube_video'    => __( 'YouTube Video', 'ewo-2025' ),
		'youtube_playlist' => __( 'YouTube Playlist', 'ewo-2025' ),
		'substack'         => __( 'Substack URL', 'ewo-2025' ),
		'rss'              => __( 'RSS Article', 'ewo-2025' ),
		'external'         => __( 'External URL', 'ewo-2025' ),
	);
	$url_placeholders = array(
		'youtube_video'    => 'https://youtube.com/watch?v=...',
		'youtube_playlist' => 'https://youtube.com/playlist?list=...',
		'substack'         => 'https://yoursite.substack.com/p/...',
		'rss'              => 'https://example.com/article/',
		'external'         => 'https://example.com/',
	);
	?>
	<div class="ds-form-head">
		<a class="ds-back" href="<?php echo esc_url( $back_url ); ?>">&larr; <?php esc_html_e( 'All Cards', 'ewo-2025' ); ?></a>
		<h2><?php echo esc_html( $form_title ); ?></h2>
	</div>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="ewo_ds_card_save">
		<input type="hidden" name="ewo_ds_card_id" value="<?php echo esc_attr( $is_edit ? ( $card['id'] ?? '' ) : '' ); ?>">
		<?php wp_nonce_field( 'ewo_ds_card_save', 'ewo_ds_card_nonce' ); ?>
		<div class="ds-form-grid">

			<!-- Content -->
			<div class="ds-form-card">
				<div class="ds-form-card__head"><h3><?php esc_html_e( 'Card Content', 'ewo-2025' ); ?></h3></div>
				<div class="ds-field">
					<label for="ds-card-title"><?php esc_html_e( 'Card Title *', 'ewo-2025' ); ?></label>
					<input class="ds-input" type="text" id="ds-card-title" name="ewo_ds_card[title]" value="<?php echo esc_attr( $v_title ); ?>" required placeholder="Russian Oil Backup">
				</div>
				<div class="ds-field">
					<label for="ds-card-eyebrow"><?php esc_html_e( 'Eyebrow / Label', 'ewo-2025' ); ?></label>
					<input class="ds-input" type="text" id="ds-card-eyebrow" name="ewo_ds_card[eyebrow]" value="<?php echo esc_attr( $v_eyebrow ); ?>" placeholder="Energy">
				</div>
				<div class="ds-field">
					<label for="ds-card-desc"><?php esc_html_e( 'Description / Excerpt', 'ewo-2025' ); ?></label>
					<textarea class="ds-textarea" id="ds-card-desc" name="ewo_ds_card[description]" rows="4"><?php echo esc_textarea( $v_desc ); ?></textarea>
				</div>
				<div class="ds-field">
					<label for="ds-card-image"><?php esc_html_e( 'Featured Image URL', 'ewo-2025' ); ?></label>
					<input class="ds-input" type="url" id="ds-card-image" name="ewo_ds_card[image_url]" value="<?php echo esc_attr( $v_image ); ?>" placeholder="https://...">
				</div>
			</div>

			<!-- Target -->
			<div class="ds-form-card">
				<div class="ds-form-card__head"><h3><?php esc_html_e( 'Target', 'ewo-2025' ); ?></h3></div>
				<div class="ds-field">
					<label for="ds-card-ttype"><?php esc_html_e( 'Target Type', 'ewo-2025' ); ?></label>
					<select class="ds-select" id="ds-card-ttype" name="ewo_ds_card[target_type]" onchange="dsToggleTarget(this.value)">
						<?php foreach ( $type_opts as $val => $lbl ) : ?>
							<option value="<?php echo esc_attr( $val ); ?>"<?php selected( $v_ttype, $val ); ?>><?php echo esc_html( $lbl ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<!-- URL field for URL-based types -->
				<div class="ds-field" id="ds-target-url-wrap">
					<label for="ds-target-url"><?php esc_html_e( 'URL', 'ewo-2025' ); ?></label>
					<input class="ds-input" type="url" id="ds-target-url" name="ewo_ds_card[target_url]"
						value="<?php echo esc_attr( $v_url ); ?>"
						placeholder="<?php echo esc_attr( $url_placeholders[ $v_ttype ] ?? 'https://...' ); ?>">
				</div>

				<!-- WordPress Post -->
				<div class="ds-field" id="ds-target-post-wrap" style="display:none">
					<label for="ds-target-post"><?php esc_html_e( 'Select Post', 'ewo-2025' ); ?></label>
					<select class="ds-select" id="ds-target-post" name="ewo_ds_card[target_post_id]">
						<option value=""><?php esc_html_e( '— Select a post —', 'ewo-2025' ); ?></option>
						<?php foreach ( $posts as $pt ) : ?>
							<option value="<?php echo esc_attr( (string) $pt->ID ); ?>"<?php selected( $v_post_id, $pt->ID ); ?>>
								<?php echo esc_html( $pt->post_title ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>

				<!-- WordPress Page -->
				<div class="ds-field" id="ds-target-page-wrap" style="display:none">
					<label for="ds-target-page"><?php esc_html_e( 'Select Page', 'ewo-2025' ); ?></label>
					<select class="ds-select" id="ds-target-page" name="ewo_ds_card[target_page_id]">
						<option value=""><?php esc_html_e( '— Select a page —', 'ewo-2025' ); ?></option>
						<?php foreach ( $pages as $pg ) : ?>
							<option value="<?php echo esc_attr( (string) $pg->ID ); ?>"<?php selected( $v_page_id, $pg->ID ); ?>>
								<?php echo esc_html( $pg->post_title ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>

				<!-- Category -->
				<div class="ds-field" id="ds-target-cat-wrap" style="display:none">
					<label for="ds-target-cat"><?php esc_html_e( 'Select Category', 'ewo-2025' ); ?></label>
					<select class="ds-select" id="ds-target-cat" name="ewo_ds_card[target_cat_id]">
						<option value=""><?php esc_html_e( '— Select a category —', 'ewo-2025' ); ?></option>
						<?php foreach ( $categories as $cat ) : ?>
							<option value="<?php echo esc_attr( (string) $cat->term_id ); ?>"<?php selected( $v_cat_id, $cat->term_id ); ?>>
								<?php echo esc_html( $cat->name ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>

			<!-- Placement -->
			<div class="ds-form-card">
				<div class="ds-form-card__head"><h3><?php esc_html_e( 'Placement & Display', 'ewo-2025' ); ?></h3></div>
				<div class="ds-field">
					<label for="ds-card-section"><?php esc_html_e( 'Homepage Section *', 'ewo-2025' ); ?></label>
					<select class="ds-select" id="ds-card-section" name="ewo_ds_card[section_id]" required>
						<option value=""><?php esc_html_e( '— Select a section —', 'ewo-2025' ); ?></option>
						<optgroup label="<?php esc_attr_e( 'Existing Sections (Built-in)', 'ewo-2025' ); ?>">
							<?php foreach ( $builtin_secs as $sec ) : ?>
								<option value="<?php echo esc_attr( $sec['id'] ?? '' ); ?>"<?php selected( $v_sec_id, $sec['id'] ?? '' ); ?>>
									<?php echo esc_html( $sec['title'] ?? '' ); ?>
								</option>
							<?php endforeach; ?>
						</optgroup>
						<?php if ( ! empty( $all_sections ) ) : ?>
						<optgroup label="<?php esc_attr_e( 'Custom Sections', 'ewo-2025' ); ?>">
							<?php foreach ( $all_sections as $sec ) : ?>
								<option value="<?php echo esc_attr( $sec['id'] ?? '' ); ?>"<?php selected( $v_sec_id, $sec['id'] ?? '' ); ?>>
									<?php echo esc_html( $sec['title'] ?? '' ); ?>
								</option>
							<?php endforeach; ?>
						</optgroup>
						<?php endif; ?>
					</select>
				</div>
				<div class="ds-field">
					<label for="ds-card-order"><?php esc_html_e( 'Display Order', 'ewo-2025' ); ?></label>
					<p class="ds-desc"><?php esc_html_e( 'Within the section. Order 0 renders as the large featured card.', 'ewo-2025' ); ?></p>
					<input class="ds-input" type="number" id="ds-card-order" name="ewo_ds_card[display_order]" value="<?php echo esc_attr( (string) $v_order ); ?>" min="0" max="999">
				</div>
				<div class="ds-field ds-field--toggle">
					<label><?php esc_html_e( 'Enabled', 'ewo-2025' ); ?></label>
					<div class="ds-tw">
						<label class="ds-tgl">
							<input type="checkbox" name="ewo_ds_card[enabled]" value="1"<?php checked( $v_enabled ); ?> onchange="dsToggleStatus(this,'ds-card-status')">
							<span class="ds-sld"></span>
						</label>
						<span class="ds-ts<?php echo $v_enabled ? ' ds-ts--on' : ''; ?>" id="ds-card-status">
							<?php echo $v_enabled ? esc_html__( 'Enabled', 'ewo-2025' ) : esc_html__( 'Disabled', 'ewo-2025' ); ?>
						</span>
					</div>
				</div>
			</div>

		</div><!-- .ds-form-grid -->
		<div class="ds-form-actions">
			<button type="submit" class="ds-save">
				<?php echo $is_edit ? esc_html__( 'Update Card', 'ewo-2025' ) : esc_html__( 'Add Card', 'ewo-2025' ); ?>
			</button>
			<a class="ds-cancel" href="<?php echo esc_url( $back_url ); ?>"><?php esc_html_e( 'Cancel', 'ewo-2025' ); ?></a>
		</div>
	</form>
	<script>
	(function() {
		var urlTypes = ['youtube_video','youtube_playlist','substack','rss','external'];
		function dsToggleTarget(type) {
			document.getElementById('ds-target-url-wrap').style.display  = urlTypes.indexOf(type) >= 0 ? '' : 'none';
			document.getElementById('ds-target-post-wrap').style.display = type === 'post'     ? '' : 'none';
			document.getElementById('ds-target-page-wrap').style.display = type === 'page'     ? '' : 'none';
			document.getElementById('ds-target-cat-wrap').style.display  = type === 'category' ? '' : 'none';
		}
		window.dsToggleTarget = dsToggleTarget;
		dsToggleTarget(document.getElementById('ds-card-ttype').value);
	})();
	window.dsToggleStatus = function(el, labelId) {
		var lbl = document.getElementById(labelId);
		if (!lbl) return;
		if (el.checked) { lbl.textContent = '<?php echo esc_js( __( 'Enabled', 'ewo-2025' ) ); ?>'; lbl.classList.add('ds-ts--on'); }
		else            { lbl.textContent = '<?php echo esc_js( __( 'Disabled', 'ewo-2025' ) ); ?>'; lbl.classList.remove('ds-ts--on'); }
	};
	</script>
	<?php
}

/* ---------------------------------------------------------------------------
 * Admin CSS
 * ------------------------------------------------------------------------- */

function ewo_2025_ds_admin_css() {
	?>
	<style>
	:root{--ds-bg:#060f1e;--ds-surface:#0b1829;--ds-surface2:#0f2035;--ds-border:rgba(50,100,160,.3);--ds-border2:rgba(50,100,160,.14);--ds-gold:#d7a84b;--ds-gold-dk:#b08020;--ds-text:#dde8f5;--ds-muted:#6b88b5;--ds-white:#fff;--ds-green:#4ade80;--ds-red:#f87171;--ds-radius:8px}
	#ewo-ds-page{background:var(--ds-bg);min-height:100vh;padding:28px 24px 60px;color:var(--ds-text);font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}
	#ewo-ds-page *{box-sizing:border-box}
	.ds-head{margin-bottom:24px}
	.ds-head h1{color:var(--ds-white);font-size:1.5rem;font-weight:700;margin:0 0 6px}
	.ds-head p{color:var(--ds-muted);font-size:.85rem;margin:0}
	.ds-notice{background:rgba(215,168,75,.1);border:1px solid rgba(215,168,75,.4);border-radius:var(--ds-radius);color:var(--ds-gold);font-size:.82rem;font-weight:600;margin-bottom:20px;padding:10px 16px}
	.ds-list-header{align-items:center;display:flex;gap:12px;flex-wrap:wrap;margin-bottom:16px}
	.ds-filter-pill{background:var(--ds-surface);border:1px solid var(--ds-border);border-radius:20px;color:var(--ds-muted);font-size:.78rem;padding:4px 12px}
	.ds-filter-pill a{color:var(--ds-red);margin-left:4px;text-decoration:none}
	.ds-btn-add{background:var(--ds-gold);border-radius:var(--ds-radius);color:#0a0600;display:inline-block;font-size:.85rem;font-weight:700;padding:9px 20px;text-decoration:none}
	.ds-btn-add:hover{background:var(--ds-gold-dk);color:#0a0600}
	.ds-empty{background:var(--ds-surface);border:1px solid var(--ds-border);border-radius:var(--ds-radius);padding:40px;text-align:center}
	.ds-empty p{color:var(--ds-muted);margin:0 0 16px}
	.ds-table-wrap{overflow-x:auto}
	.ds-table{border-collapse:collapse;width:100%}
	.ds-table th,.ds-table td{border-bottom:1px solid var(--ds-border2);padding:10px 14px;text-align:left;vertical-align:middle}
	.ds-table th{color:var(--ds-gold);font-size:.72rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase}
	.ds-table td{color:var(--ds-text);font-size:.84rem}
	.ds-col-order{width:58px}
	.ds-col-title{font-weight:600;max-width:220px}
	.ds-col-actions{white-space:nowrap}
	.ds-table-hint{color:var(--ds-muted);font-size:.75rem;margin-top:10px}
	.ds-badge{border-radius:4px;font-size:.72rem;font-weight:700;padding:2px 8px;text-transform:uppercase}
	.ds-badge--on{background:rgba(74,222,128,.15);color:var(--ds-green)}
	.ds-badge--off{background:rgba(248,113,113,.15);color:var(--ds-red)}
	.ds-badge--builtin{background:rgba(107,136,181,.15);color:var(--ds-muted)}
	.ds-row-builtin td{opacity:.8}
	.ds-text-muted{color:var(--ds-muted)}
	.ds-text-gold{color:var(--ds-gold)}
	.ds-link-edit,.ds-link-delete,.ds-link-muted{font-size:.82rem;font-weight:600;text-decoration:none}
	.ds-link-edit{color:var(--ds-gold);margin-right:12px}
	.ds-link-delete{color:var(--ds-red)}
	.ds-link-muted{color:var(--ds-muted)}
	.ds-link-edit:hover{color:var(--ds-gold-dk)}
	.ds-link-delete:hover{opacity:.8}
	.ds-form-head{margin-bottom:20px}
	.ds-back{color:var(--ds-muted);font-size:.82rem;text-decoration:none}
	.ds-form-head h2{color:var(--ds-white);font-size:1.2rem;font-weight:700;margin:8px 0 0}
	.ds-form-grid{display:grid;gap:16px;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));margin-bottom:20px}
	.ds-form-card--wide{grid-column:1/-1}
	.ds-form-card{background:var(--ds-surface);border:1px solid var(--ds-border);border-radius:var(--ds-radius)}
	.ds-form-card__head{border-bottom:1px solid var(--ds-border2);padding:12px 18px}
	.ds-form-card__head h3{color:var(--ds-gold);font-size:.72rem;font-weight:700;letter-spacing:.12em;margin:0;text-transform:uppercase}
	.ds-field{border-bottom:1px solid var(--ds-border2);padding:14px 18px}
	.ds-field:last-child{border-bottom:0}
	.ds-field label{color:var(--ds-white);display:block;font-size:.85rem;font-weight:600;margin-bottom:6px}
	.ds-desc{color:var(--ds-muted);font-size:.75rem;margin:0 0 8px}
	.ds-input,.ds-select,.ds-textarea{background:var(--ds-surface2);border:1px solid var(--ds-border);border-radius:6px;color:var(--ds-text);font-size:.85rem;padding:8px 12px;width:100%}
	.ds-textarea{resize:vertical}
	.ds-input:focus,.ds-select:focus,.ds-textarea:focus{border-color:var(--ds-gold);outline:none}
	.ds-field--toggle{align-items:center;display:flex;justify-content:space-between}
	.ds-field--toggle label{margin-bottom:0}
	.ds-tw{align-items:center;display:flex;gap:8px}
	.ds-tgl{position:relative;display:inline-block;width:44px;height:24px}
	.ds-tgl input{opacity:0;width:0;height:0;position:absolute}
	.ds-sld{position:absolute;cursor:pointer;inset:0;background:rgba(50,100,160,.3);border-radius:24px;transition:background .2s}
	.ds-sld::before{position:absolute;content:"";height:18px;width:18px;left:3px;bottom:3px;background:var(--ds-muted);border-radius:50%;transition:transform .2s,background .2s}
	.ds-tgl input:checked+.ds-sld{background:rgba(74,222,128,.22)}
	.ds-tgl input:checked+.ds-sld::before{transform:translateX(20px);background:var(--ds-green)}
	.ds-ts{color:var(--ds-muted);font-size:.72rem;font-weight:600;min-width:54px;text-align:right;text-transform:uppercase}
	.ds-ts--on{color:var(--ds-green)}
	.ds-form-actions{align-items:center;display:flex;gap:16px;margin-top:8px}
	.ds-save{background:var(--ds-gold);border:0;border-radius:var(--ds-radius);color:#0a0600;cursor:pointer;font-size:.88rem;font-weight:700;padding:11px 28px;transition:background .15s}
	.ds-save:hover{background:var(--ds-gold-dk)}
	.ds-cancel{color:var(--ds-muted);font-size:.85rem;text-decoration:none}
	.ds-cancel:hover{color:var(--ds-white)}
	</style>
	<?php
}

/* ---------------------------------------------------------------------------
 * Frontend rendering
 * ------------------------------------------------------------------------- */

function ewo_2025_ds_render_all() {
	$sections = ewo_2025_ds_get_enabled_sections();
	foreach ( $sections as $section ) {
		// Skip built-in sections — front-page.php renders those directly.
		if ( ! empty( $section['builtin'] ) ) {
			continue;
		}
		$cards = ewo_2025_ds_get_cards_for_section( $section['id'], true );
		if ( ! empty( $cards ) ) {
			ewo_2025_ds_render_section( $section, $cards );
		}
	}
}

function ewo_2025_ds_render_section( $section, $cards ) {
	$sec_id  = $section['id']       ?? '';
	$title   = $section['title']    ?? '';
	$eyebrow = $section['eyebrow']  ?? '';
	$cta_txt = $section['cta_text'] ?? '';
	$cta_url = $section['cta_url']  ?? '';
	?>
	<section id="ds-<?php echo esc_attr( $sec_id ); ?>" class="ewo-section ewo-dyn-section">
		<div class="ewo-section__header">
			<div class="ewo-section__header-copy">
				<?php if ( $eyebrow ) : ?>
					<p class="ewo-kicker"><?php echo esc_html( $eyebrow ); ?></p>
				<?php endif; ?>
				<h2><?php echo esc_html( $title ); ?></h2>
			</div>
			<?php if ( $cta_txt && $cta_url ) : ?>
				<a class="ewo-button ewo-button--ghost ewo-section__cta" href="<?php echo esc_url( $cta_url ); ?>">
					<?php echo esc_html( $cta_txt ); ?>
				</a>
			<?php endif; ?>
		</div>
		<div class="ewo-article-grid ewo-analysis-grid">
			<?php
			$idx      = 0;
			$sec_open = false;
			foreach ( $cards as $card ) :
				$featured = ( 0 === $idx );
				if ( 1 === $idx ) {
					echo '<div class="ewo-analysis-grid__secondary">';
					$sec_open = true;
				}
				ewo_2025_ds_render_card( $card, $featured );
				++$idx;
			endforeach;
			if ( $sec_open ) {
				echo '</div>';
			}
			?>
		</div>
	</section>
	<?php
}

function ewo_2025_ds_render_card( $card, $featured = false ) {
	$url     = ewo_2025_ds_resolve_url( $card );
	$title   = $card['title']       ?? '';
	$eyebrow = $card['eyebrow']     ?? '';
	$desc    = $card['description'] ?? '';
	$img     = $card['image_url']   ?? '';
	$words   = $featured ? 34 : 18;

	$classes = 'ewo-article-card ewo-briefing-card ewo-briefing-card--dyn';
	if ( $featured ) {
		$classes .= ' ewo-briefing-card--featured';
	}
	?>
	<article class="<?php echo esc_attr( $classes ); ?>">
		<?php if ( $url ) : ?>
			<a class="ewo-briefing-card__media" href="<?php echo esc_url( $url ); ?>" aria-label="<?php echo esc_attr( $title ); ?>">
		<?php else : ?>
			<div class="ewo-briefing-card__media">
		<?php endif; ?>
			<?php if ( $img ) : ?>
				<img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy">
			<?php else : ?>
				<span class="ewo-briefing-card__placeholder" aria-hidden="true"></span>
			<?php endif; ?>
		<?php echo $url ? '</a>' : '</div>'; ?>
		<div class="ewo-briefing-card__body">
			<?php if ( $eyebrow ) : ?>
				<p class="ewo-card-meta"><span><?php echo esc_html( $eyebrow ); ?></span></p>
			<?php endif; ?>
			<h3>
				<?php if ( $url ) : ?><a href="<?php echo esc_url( $url ); ?>"><?php endif; ?>
				<?php echo esc_html( $title ); ?>
				<?php if ( $url ) : ?></a><?php endif; ?>
			</h3>
			<?php if ( $desc ) : ?>
				<p><?php echo esc_html( wp_trim_words( $desc, $words ) ); ?></p>
			<?php endif; ?>
			<?php if ( ! $featured && $url ) : ?>
				<a class="ewo-briefing-card__more" href="<?php echo esc_url( $url ); ?>"><?php esc_html_e( 'Read More', 'ewo-2025' ); ?> &rarr;</a>
			<?php endif; ?>
		</div>
	</article>
	<?php
}
