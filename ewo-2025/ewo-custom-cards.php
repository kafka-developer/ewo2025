<?php
/**
 * EWO Custom Homepage Cards — one-time migration + legacy frontend rendering.
 *
 * The ewo_2025_custom_cards option is migrated into ewo_2025_dyn_cards on first load
 * (guarded by ewo_cc_migrated_v1). After migration the old option is deleted and this
 * file only provides the rendering helpers (ewo_2025_cc_render_briefing_card etc.)
 * so any references in front-page.php degrade safely to no-ops if the option is gone.
 *
 * @package EWO_2025
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'EWO_2025_CUSTOM_CARDS_OPTION', 'ewo_2025_custom_cards' );

/* ---------------------------------------------------------------------------
 * One-time migration: ewo_2025_custom_cards → ewo_2025_dyn_cards
 * ------------------------------------------------------------------------- */

function ewo_2025_cc_migrate() {
	if ( get_option( 'ewo_cc_migrated_v1' ) ) {
		return;
	}

	$old = get_option( EWO_2025_CUSTOM_CARDS_OPTION, array() );
	if ( ! is_array( $old ) || empty( $old ) ) {
		update_option( 'ewo_cc_migrated_v1', 1, false );
		delete_option( EWO_2025_CUSTOM_CARDS_OPTION );
		return;
	}

	// Section name map: old flat key → builtin_ prefixed dyn_sections ID.
	$section_map = array(
		'latest_analysis'    => 'builtin_latest_analysis',
		'strategic_playlists' => 'builtin_strategic_playlists',
		'featured_cards'     => 'builtin_featured_cards',
		'custom_section'     => 'builtin_custom_section',
	);

	// link_type → target_type mapping (names are identical except for wp_page/wp_post).
	$type_map = array(
		'wp_page'  => 'page',
		'wp_post'  => 'post',
		'wp_category' => 'category',
	);

	$existing = get_option( EWO_2025_DYN_CARDS_OPTION, array() );
	if ( ! is_array( $existing ) ) {
		$existing = array();
	}

	$now = gmdate( 'Y-m-d H:i:s' );
	foreach ( $old as $card ) {
		$old_section = $card['section'] ?? '';
		$old_type    = $card['link_type'] ?? 'external';
		$converted   = array(
			'id'            => 'migrated_' . ( $card['id'] ?? uniqid() ),
			'section_id'    => $section_map[ $old_section ] ?? 'builtin_custom_section',
			'title'         => $card['title']        ?? '',
			'eyebrow'       => $card['eyebrow']      ?? '',
			'description'   => $card['description']  ?? '',
			'image_url'     => $card['image_url']    ?? '',
			'target_type'   => $type_map[ $old_type ] ?? $old_type,
			'target_value'  => $card['link_value']   ?? '',
			'display_order' => $card['display_order'] ?? 0,
			'enabled'       => $card['enabled']       ?? 1,
			'created'       => $card['created']       ?? $now,
			'updated'       => $now,
		);
		$existing[] = $converted;
	}

	update_option( EWO_2025_DYN_CARDS_OPTION, $existing );
	update_option( 'ewo_cc_migrated_v1', 1, false );
	delete_option( EWO_2025_CUSTOM_CARDS_OPTION );
}
add_action( 'init', 'ewo_2025_cc_migrate', 5 );

/* ---------------------------------------------------------------------------
 * Data helpers
 * ------------------------------------------------------------------------- */

function ewo_2025_cc_get_all() {
	static $cache = null;
	if ( null === $cache ) {
		$saved = get_option( EWO_2025_CUSTOM_CARDS_OPTION, array() );
		$cache = is_array( $saved ) ? $saved : array();
		usort( $cache, function ( $a, $b ) {
			return (int) ( $a['display_order'] ?? 0 ) - (int) ( $b['display_order'] ?? 0 );
		} );
	}
	return $cache;
}

function ewo_2025_cc_get_section( $section ) {
	return array_values( array_filter( ewo_2025_cc_get_all(), function ( $c ) use ( $section ) {
		return ! empty( $c['enabled'] ) && ( $c['section'] ?? '' ) === $section;
	} ) );
}

function ewo_2025_cc_resolve_url( $card ) {
	$type  = $card['link_type']  ?? 'external';
	$value = $card['link_value'] ?? '';
	if ( '' === (string) $value ) {
		return '';
	}
	switch ( $type ) {
		case 'wp_page':
		case 'wp_post':
			$url = get_permalink( (int) $value );
			return $url ? (string) $url : '';
		case 'wp_category':
			$url = get_category_link( (int) $value );
			return ( ! is_wp_error( $url ) ) ? (string) $url : '';
		default:
			return esc_url_raw( $value );
	}
}

/* ---------------------------------------------------------------------------
 * Admin
 * ------------------------------------------------------------------------- */

// Admin page removed — card management is now handled by EWO Settings → Homepage Cards
// (ewo-dynamic-sections.php). Rendering helpers below remain so existing cards in
// ewo_2025_custom_cards continue to appear on the homepage without data migration.

/* ---- Save handler -------------------------------------------------------- */

function ewo_2025_cc_handle_save() {
	check_admin_referer( 'ewo_cc_save', 'ewo_cc_nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Permission denied.', 'ewo-2025' ) );
	}

	$raw = isset( $_POST['ewo_cc'] ) && is_array( $_POST['ewo_cc'] ) // phpcs:ignore WordPress.Security.NonceVerification
		? $_POST['ewo_cc'] // phpcs:ignore WordPress.Security.NonceVerification
		: array();

	$edit_id = isset( $_POST['ewo_cc_id'] ) // phpcs:ignore WordPress.Security.NonceVerification
		? sanitize_key( wp_unslash( $_POST['ewo_cc_id'] ) ) // phpcs:ignore WordPress.Security.NonceVerification
		: '';

	$valid_types    = array( 'external', 'youtube', 'substack', 'wp_page', 'wp_post', 'wp_category' );
	$valid_sections = array( 'latest_analysis', 'strategic_playlists', 'featured_cards', 'custom_section' );

	$link_type = isset( $raw['link_type'] ) && in_array( $raw['link_type'], $valid_types, true )
		? $raw['link_type'] : 'external';

	// Consolidate the link value from the appropriate sub-field.
	if ( 'wp_page' === $link_type ) {
		$link_value = isset( $raw['link_page_id'] ) ? (string) (int) $raw['link_page_id'] : '';
	} elseif ( 'wp_post' === $link_type ) {
		$link_value = isset( $raw['link_post_id'] ) ? (string) (int) $raw['link_post_id'] : '';
	} elseif ( 'wp_category' === $link_type ) {
		$link_value = isset( $raw['link_cat_id'] ) ? (string) (int) $raw['link_cat_id'] : '';
	} else {
		$link_value = isset( $raw['link_url'] ) ? esc_url_raw( wp_unslash( $raw['link_url'] ) ) : '';
	}

	$section = isset( $raw['section'] ) && in_array( $raw['section'], $valid_sections, true )
		? $raw['section'] : 'latest_analysis';

	$card = array(
		'id'            => $edit_id ?: ( 'cc_' . uniqid() ),
		'title'         => sanitize_text_field( wp_unslash( $raw['title'] ?? '' ) ),
		'eyebrow'       => sanitize_text_field( wp_unslash( $raw['eyebrow'] ?? '' ) ),
		'description'   => sanitize_textarea_field( wp_unslash( $raw['description'] ?? '' ) ),
		'image_url'     => esc_url_raw( wp_unslash( $raw['image_url'] ?? '' ) ),
		'button_text'   => sanitize_text_field( wp_unslash( $raw['button_text'] ?? '' ) ),
		'link_type'     => $link_type,
		'link_value'    => $link_value,
		'section'       => $section,
		'display_order' => max( 0, min( 999, (int) ( $raw['display_order'] ?? 0 ) ) ),
		'enabled'       => ! empty( $raw['enabled'] ) ? 1 : 0,
	);

	$all = get_option( EWO_2025_CUSTOM_CARDS_OPTION, array() );
	if ( ! is_array( $all ) ) {
		$all = array();
	}

	if ( $edit_id ) {
		$found = false;
		foreach ( $all as $i => $c ) {
			if ( ( $c['id'] ?? '' ) === $edit_id ) {
				$all[ $i ] = $card;
				$found     = true;
				break;
			}
		}
		if ( ! $found ) {
			$all[] = $card;
		}
	} else {
		$all[] = $card;
	}

	update_option( EWO_2025_CUSTOM_CARDS_OPTION, $all );

	wp_redirect( admin_url( 'admin.php?page=ewo-custom-cards&saved=1' ) );
	exit;
}
add_action( 'admin_post_ewo_cc_save', 'ewo_2025_cc_handle_save' );

/* ---- Page renderer ------------------------------------------------------- */

function ewo_2025_cc_render_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Inline delete — nonce checked via check_admin_referer.
	if (
		isset( $_GET['action'], $_GET['cc_id'] ) // phpcs:ignore WordPress.Security.NonceVerification
		&& 'delete' === sanitize_key( wp_unslash( $_GET['action'] ) ) // phpcs:ignore WordPress.Security.NonceVerification
	) {
		$cc_id = sanitize_key( wp_unslash( $_GET['cc_id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		check_admin_referer( 'ewo_cc_delete_' . $cc_id );
		$all = get_option( EWO_2025_CUSTOM_CARDS_OPTION, array() );
		if ( is_array( $all ) ) {
			$all = array_values( array_filter( $all, function ( $c ) use ( $cc_id ) {
				return ( $c['id'] ?? '' ) !== $cc_id;
			} ) );
			update_option( EWO_2025_CUSTOM_CARDS_OPTION, $all );
		}
		wp_redirect( admin_url( 'admin.php?page=ewo-custom-cards&deleted=1' ) );
		exit;
	}

	$action  = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$cc_id   = isset( $_GET['cc_id'] )  ? sanitize_key( wp_unslash( $_GET['cc_id'] ) )  : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$saved   = isset( $_GET['saved'] )  && '1' === sanitize_text_field( wp_unslash( $_GET['saved'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
	$deleted = isset( $_GET['deleted'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['deleted'] ) ); // phpcs:ignore WordPress.Security.NonceVerification

	$edit_card = null;
	if ( 'edit' === $action && $cc_id ) {
		$all = get_option( EWO_2025_CUSTOM_CARDS_OPTION, array() );
		if ( is_array( $all ) ) {
			foreach ( $all as $c ) {
				if ( ( $c['id'] ?? '' ) === $cc_id ) {
					$edit_card = $c;
					break;
				}
			}
		}
	}

	ewo_2025_cc_admin_css();

	echo '<div id="ewo-cc-page">';

	echo '<div class="cc-head">';
	echo '<h1>' . esc_html__( 'Custom Homepage Cards', 'ewo-2025' ) . '</h1>';
	echo '<p>' . esc_html__( 'Create cards that appear in homepage sections alongside or in place of auto-generated content.', 'ewo-2025' ) . '</p>';
	echo '</div>';

	if ( $saved ) {
		echo '<div class="cc-notice">' . esc_html__( 'Card saved.', 'ewo-2025' ) . '</div>';
	}
	if ( $deleted ) {
		echo '<div class="cc-notice">' . esc_html__( 'Card deleted.', 'ewo-2025' ) . '</div>';
	}

	if ( 'add' === $action || 'edit' === $action ) {
		ewo_2025_cc_render_form( $edit_card );
	} else {
		ewo_2025_cc_render_list();
	}

	echo '</div>';
}

/* ---- List view ----------------------------------------------------------- */

function ewo_2025_cc_render_list() {
	$all     = ewo_2025_cc_get_all();
	$add_url = admin_url( 'admin.php?page=ewo-custom-cards&action=add' );

	$section_labels = array(
		'latest_analysis'     => __( 'Latest Analysis', 'ewo-2025' ),
		'strategic_playlists' => __( 'Strategic Playlists', 'ewo-2025' ),
		'featured_cards'      => __( 'Featured Cards', 'ewo-2025' ),
		'custom_section'      => __( 'Custom Section', 'ewo-2025' ),
	);
	$type_labels = array(
		'external'    => 'External',
		'youtube'     => 'YouTube',
		'substack'    => 'Substack',
		'wp_page'     => 'WP Page',
		'wp_post'     => 'WP Post',
		'wp_category' => 'Category',
	);
	?>
	<div class="cc-list-header">
		<a class="cc-btn-add" href="<?php echo esc_url( $add_url ); ?>"><?php esc_html_e( '+ Add New Card', 'ewo-2025' ); ?></a>
	</div>
	<?php if ( empty( $all ) ) : ?>
		<div class="cc-empty">
			<p><?php esc_html_e( 'No custom cards yet. Add your first card to get started.', 'ewo-2025' ); ?></p>
			<a class="cc-btn-add" href="<?php echo esc_url( $add_url ); ?>"><?php esc_html_e( 'Add Your First Card', 'ewo-2025' ); ?></a>
		</div>
	<?php else : ?>
		<div class="cc-table-wrap">
			<table class="cc-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Order', 'ewo-2025' ); ?></th>
						<th><?php esc_html_e( 'Title', 'ewo-2025' ); ?></th>
						<th><?php esc_html_e( 'Section', 'ewo-2025' ); ?></th>
						<th><?php esc_html_e( 'Link Type', 'ewo-2025' ); ?></th>
						<th><?php esc_html_e( 'Status', 'ewo-2025' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'ewo-2025' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $all as $card ) :
						$cid      = $card['id'] ?? '';
						$edit_url = admin_url( 'admin.php?page=ewo-custom-cards&action=edit&cc_id=' . rawurlencode( $cid ) );
						$del_url  = wp_nonce_url(
							admin_url( 'admin.php?page=ewo-custom-cards&action=delete&cc_id=' . rawurlencode( $cid ) ),
							'ewo_cc_delete_' . $cid
						);
						$sec_label  = $section_labels[ $card['section'] ?? '' ] ?? esc_html( $card['section'] ?? '' );
						$type_label = $type_labels[ $card['link_type'] ?? '' ] ?? '';
						$enabled    = ! empty( $card['enabled'] );
					?>
					<tr>
						<td class="cc-col-order"><?php echo esc_html( (string) ( $card['display_order'] ?? 0 ) ); ?></td>
						<td class="cc-col-title"><?php echo esc_html( $card['title'] ?? '' ); ?></td>
						<td><?php echo esc_html( $sec_label ); ?></td>
						<td><?php echo esc_html( $type_label ); ?></td>
						<td>
							<?php if ( $enabled ) : ?>
								<span class="cc-badge cc-badge--on"><?php esc_html_e( 'Enabled', 'ewo-2025' ); ?></span>
							<?php else : ?>
								<span class="cc-badge cc-badge--off"><?php esc_html_e( 'Disabled', 'ewo-2025' ); ?></span>
							<?php endif; ?>
						</td>
						<td class="cc-col-actions">
							<a class="cc-link-edit" href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Edit', 'ewo-2025' ); ?></a>
							<a class="cc-link-delete" href="<?php echo esc_url( $del_url ); ?>" onclick="return confirm('<?php esc_attr_e( 'Delete this card?', 'ewo-2025' ); ?>')"><?php esc_html_e( 'Delete', 'ewo-2025' ); ?></a>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif;
}

/* ---- Add / Edit form ----------------------------------------------------- */

function ewo_2025_cc_render_form( $card = null ) {
	$is_edit    = ! empty( $card );
	$form_title = $is_edit ? __( 'Edit Card', 'ewo-2025' ) : __( 'Add New Card', 'ewo-2025' );
	$back_url   = admin_url( 'admin.php?page=ewo-custom-cards' );

	$v_title   = $is_edit ? ( $card['title']         ?? '' ) : '';
	$v_eyebrow = $is_edit ? ( $card['eyebrow']        ?? '' ) : '';
	$v_desc    = $is_edit ? ( $card['description']    ?? '' ) : '';
	$v_image   = $is_edit ? ( $card['image_url']      ?? '' ) : '';
	$v_btn     = $is_edit ? ( $card['button_text']    ?? '' ) : '';
	$v_ltype   = $is_edit ? ( $card['link_type']      ?? 'external' ) : 'external';
	$v_lvalue  = $is_edit ? ( $card['link_value']     ?? '' ) : '';
	$v_section = $is_edit ? ( $card['section']        ?? 'latest_analysis' ) : 'latest_analysis';
	$v_order   = $is_edit ? (int) ( $card['display_order'] ?? 0 ) : 0;
	$v_enabled = ! $is_edit || ! empty( $card['enabled'] );

	// Split link value back into type-specific fields.
	$v_url     = in_array( $v_ltype, array( 'external', 'youtube', 'substack' ), true ) ? $v_lvalue : '';
	$v_page_id = 'wp_page'     === $v_ltype ? (int) $v_lvalue : 0;
	$v_post_id = 'wp_post'     === $v_ltype ? (int) $v_lvalue : 0;
	$v_cat_id  = 'wp_category' === $v_ltype ? (int) $v_lvalue : 0;

	// Fetch dropdown data.
	$pages = get_pages( array( 'sort_column' => 'post_title', 'sort_order' => 'ASC' ) );
	if ( ! $pages ) {
		$pages = array();
	}
	$posts = get_posts( array(
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

	$section_opts = array(
		'latest_analysis'     => __( 'Latest Analysis (§7)', 'ewo-2025' ),
		'strategic_playlists' => __( 'Strategic Playlists (§8)', 'ewo-2025' ),
		'featured_cards'      => __( 'Featured Cards (standalone section after §8)', 'ewo-2025' ),
		'custom_section'      => __( 'Custom Section (standalone section after Featured Cards)', 'ewo-2025' ),
	);
	$type_opts = array(
		'external'    => __( 'External URL', 'ewo-2025' ),
		'youtube'     => __( 'YouTube URL', 'ewo-2025' ),
		'substack'    => __( 'Substack URL', 'ewo-2025' ),
		'wp_page'     => __( 'WordPress Page', 'ewo-2025' ),
		'wp_post'     => __( 'WordPress Post', 'ewo-2025' ),
		'wp_category' => __( 'Category Archive', 'ewo-2025' ),
	);
	$url_placeholders = array(
		'external' => 'https://example.com/article',
		'youtube'  => 'https://youtube.com/watch?v=...',
		'substack' => 'https://yoursite.substack.com/p/...',
	);
	?>
	<div class="cc-form-head">
		<a class="cc-back" href="<?php echo esc_url( $back_url ); ?>">&larr; <?php esc_html_e( 'All Cards', 'ewo-2025' ); ?></a>
		<h2><?php echo esc_html( $form_title ); ?></h2>
	</div>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="ewo_cc_save">
		<input type="hidden" name="ewo_cc_id" value="<?php echo esc_attr( $is_edit ? ( $card['id'] ?? '' ) : '' ); ?>">
		<?php wp_nonce_field( 'ewo_cc_save', 'ewo_cc_nonce' ); ?>

		<div class="cc-form-grid">

			<!-- Card Content -->
			<div class="cc-form-card">
				<div class="cc-form-card__head"><h3><?php esc_html_e( 'Card Content', 'ewo-2025' ); ?></h3></div>

				<div class="cc-field">
					<label for="cc-title"><?php esc_html_e( 'Card Title *', 'ewo-2025' ); ?></label>
					<input class="cc-input" type="text" id="cc-title" name="ewo_cc[title]" value="<?php echo esc_attr( $v_title ); ?>" required>
				</div>

				<div class="cc-field">
					<label for="cc-eyebrow"><?php esc_html_e( 'Eyebrow / Label', 'ewo-2025' ); ?></label>
					<p class="cc-desc"><?php esc_html_e( 'Small category text above the title, e.g. "Briefing", "Analysis".', 'ewo-2025' ); ?></p>
					<input class="cc-input" type="text" id="cc-eyebrow" name="ewo_cc[eyebrow]" value="<?php echo esc_attr( $v_eyebrow ); ?>">
				</div>

				<div class="cc-field">
					<label for="cc-desc"><?php esc_html_e( 'Description / Excerpt', 'ewo-2025' ); ?></label>
					<textarea class="cc-textarea" id="cc-desc" name="ewo_cc[description]" rows="4"><?php echo esc_textarea( $v_desc ); ?></textarea>
				</div>

				<div class="cc-field">
					<label for="cc-image"><?php esc_html_e( 'Featured Image URL', 'ewo-2025' ); ?></label>
					<input class="cc-input" type="url" id="cc-image" name="ewo_cc[image_url]" value="<?php echo esc_attr( $v_image ); ?>" placeholder="https://...">
				</div>

				<div class="cc-field">
					<label for="cc-btn"><?php esc_html_e( 'Button / Link Text', 'ewo-2025' ); ?></label>
					<input class="cc-input" type="text" id="cc-btn" name="ewo_cc[button_text]" value="<?php echo esc_attr( $v_btn ); ?>" placeholder="<?php esc_attr_e( 'Read More', 'ewo-2025' ); ?>">
				</div>
			</div>

			<!-- Link Target -->
			<div class="cc-form-card">
				<div class="cc-form-card__head"><h3><?php esc_html_e( 'Link Target', 'ewo-2025' ); ?></h3></div>

				<div class="cc-field">
					<label for="cc-ltype"><?php esc_html_e( 'Link Type', 'ewo-2025' ); ?></label>
					<select class="cc-select" id="cc-ltype" name="ewo_cc[link_type]" onchange="ccToggleLinkTarget(this.value)">
						<?php foreach ( $type_opts as $val => $lbl ) : ?>
							<option value="<?php echo esc_attr( $val ); ?>"<?php selected( $v_ltype, $val ); ?>><?php echo esc_html( $lbl ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<!-- URL field (external / youtube / substack) -->
				<div class="cc-field" id="cc-url-wrap">
					<label for="cc-link-url"><?php esc_html_e( 'URL', 'ewo-2025' ); ?></label>
					<input class="cc-input" type="url" id="cc-link-url" name="ewo_cc[link_url]"
						value="<?php echo esc_attr( $v_url ); ?>"
						placeholder="<?php echo esc_attr( $url_placeholders[ $v_ltype ] ?? 'https://...' ); ?>">
				</div>

				<!-- WordPress Page dropdown -->
				<div class="cc-field" id="cc-page-wrap" style="display:none">
					<label for="cc-link-page"><?php esc_html_e( 'WordPress Page', 'ewo-2025' ); ?></label>
					<select class="cc-select" id="cc-link-page" name="ewo_cc[link_page_id]">
						<option value=""><?php esc_html_e( '— Select a page —', 'ewo-2025' ); ?></option>
						<?php foreach ( $pages as $pg ) : ?>
							<option value="<?php echo esc_attr( (string) $pg->ID ); ?>"<?php selected( $v_page_id, $pg->ID ); ?>>
								<?php echo esc_html( $pg->post_title ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>

				<!-- WordPress Post dropdown -->
				<div class="cc-field" id="cc-post-wrap" style="display:none">
					<label for="cc-link-post"><?php esc_html_e( 'WordPress Post', 'ewo-2025' ); ?></label>
					<select class="cc-select" id="cc-link-post" name="ewo_cc[link_post_id]">
						<option value=""><?php esc_html_e( '— Select a post —', 'ewo-2025' ); ?></option>
						<?php foreach ( $posts as $pt ) : ?>
							<option value="<?php echo esc_attr( (string) $pt->ID ); ?>"<?php selected( $v_post_id, $pt->ID ); ?>>
								<?php echo esc_html( $pt->post_title ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>

				<!-- Category Archive dropdown -->
				<div class="cc-field" id="cc-cat-wrap" style="display:none">
					<label for="cc-link-cat"><?php esc_html_e( 'Category', 'ewo-2025' ); ?></label>
					<select class="cc-select" id="cc-link-cat" name="ewo_cc[link_cat_id]">
						<option value=""><?php esc_html_e( '— Select a category —', 'ewo-2025' ); ?></option>
						<?php foreach ( $categories as $cat ) : ?>
							<option value="<?php echo esc_attr( (string) $cat->term_id ); ?>"<?php selected( $v_cat_id, $cat->term_id ); ?>>
								<?php echo esc_html( $cat->name ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>

			<!-- Placement & Display -->
			<div class="cc-form-card">
				<div class="cc-form-card__head"><h3><?php esc_html_e( 'Placement & Display', 'ewo-2025' ); ?></h3></div>

				<div class="cc-field">
					<label for="cc-section"><?php esc_html_e( 'Homepage Section', 'ewo-2025' ); ?></label>
					<p class="cc-desc"><?php esc_html_e( 'The section this card appears in. Section mode (auto/custom/mixed) is set in EWO Settings → Homepage Settings.', 'ewo-2025' ); ?></p>
					<select class="cc-select" id="cc-section" name="ewo_cc[section]">
						<?php foreach ( $section_opts as $val => $lbl ) : ?>
							<option value="<?php echo esc_attr( $val ); ?>"<?php selected( $v_section, $val ); ?>><?php echo esc_html( $lbl ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="cc-field">
					<label for="cc-order"><?php esc_html_e( 'Display Order', 'ewo-2025' ); ?></label>
					<p class="cc-desc"><?php esc_html_e( 'Lower numbers appear first (0–999).', 'ewo-2025' ); ?></p>
					<input class="cc-input" type="number" id="cc-order" name="ewo_cc[display_order]" value="<?php echo esc_attr( (string) $v_order ); ?>" min="0" max="999">
				</div>

				<div class="cc-field cc-field--toggle">
					<label><?php esc_html_e( 'Enabled', 'ewo-2025' ); ?></label>
					<div class="cc-tw">
						<label class="cc-tgl">
							<input type="checkbox" name="ewo_cc[enabled]" value="1"<?php checked( $v_enabled ); ?> onchange="ccToggleStatus(this)">
							<span class="cc-sld"></span>
						</label>
						<span class="cc-ts<?php echo $v_enabled ? ' cc-ts--on' : ''; ?>" id="cc-enabled-label">
							<?php echo $v_enabled ? esc_html__( 'Enabled', 'ewo-2025' ) : esc_html__( 'Disabled', 'ewo-2025' ); ?>
						</span>
					</div>
				</div>
			</div>

		</div><!-- .cc-form-grid -->

		<div class="cc-form-actions">
			<button type="submit" class="cc-save">
				<?php echo $is_edit ? esc_html__( 'Update Card', 'ewo-2025' ) : esc_html__( 'Add Card', 'ewo-2025' ); ?>
			</button>
			<a class="cc-cancel" href="<?php echo esc_url( $back_url ); ?>"><?php esc_html_e( 'Cancel', 'ewo-2025' ); ?></a>
		</div>
	</form>

	<script>
	(function() {
		var urlTypes = ['external', 'youtube', 'substack'];
		function ccToggleLinkTarget(type) {
			document.getElementById('cc-url-wrap').style.display  = urlTypes.indexOf(type) >= 0 ? '' : 'none';
			document.getElementById('cc-page-wrap').style.display = type === 'wp_page'     ? '' : 'none';
			document.getElementById('cc-post-wrap').style.display = type === 'wp_post'     ? '' : 'none';
			document.getElementById('cc-cat-wrap').style.display  = type === 'wp_category' ? '' : 'none';
		}
		window.ccToggleLinkTarget = ccToggleLinkTarget;
		ccToggleLinkTarget(document.getElementById('cc-ltype').value);

		window.ccToggleStatus = function(el) {
			var label = document.getElementById('cc-enabled-label');
			if (el.checked) {
				label.textContent = '<?php echo esc_js( __( 'Enabled', 'ewo-2025' ) ); ?>';
				label.classList.add('cc-ts--on');
			} else {
				label.textContent = '<?php echo esc_js( __( 'Disabled', 'ewo-2025' ) ); ?>';
				label.classList.remove('cc-ts--on');
			}
		};
	})();
	</script>
	<?php
}

/* ---- Admin CSS ----------------------------------------------------------- */

function ewo_2025_cc_admin_css() {
	?>
	<style>
	:root{--cc-bg:#060f1e;--cc-surface:#0b1829;--cc-surface2:#0f2035;--cc-border:rgba(50,100,160,.3);--cc-border2:rgba(50,100,160,.14);--cc-gold:#d7a84b;--cc-gold-dk:#b08020;--cc-text:#dde8f5;--cc-muted:#6b88b5;--cc-white:#fff;--cc-green:#4ade80;--cc-red:#f87171;--cc-radius:8px}
	#ewo-cc-page{background:var(--cc-bg);min-height:100vh;padding:28px 24px 60px;color:var(--cc-text);font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}
	#ewo-cc-page *{box-sizing:border-box}
	.cc-head{margin-bottom:24px}
	.cc-head h1{color:var(--cc-white);font-size:1.5rem;font-weight:700;margin:0 0 6px}
	.cc-head p{color:var(--cc-muted);font-size:.85rem;margin:0}
	.cc-notice{background:rgba(215,168,75,.1);border:1px solid rgba(215,168,75,.4);border-radius:var(--cc-radius);color:var(--cc-gold);font-size:.82rem;font-weight:600;margin-bottom:20px;padding:10px 16px}
	.cc-list-header{margin-bottom:16px}
	.cc-btn-add{background:var(--cc-gold);border-radius:var(--cc-radius);color:#0a0600;display:inline-block;font-size:.85rem;font-weight:700;padding:9px 20px;text-decoration:none}
	.cc-btn-add:hover{background:var(--cc-gold-dk);color:#0a0600}
	.cc-empty{background:var(--cc-surface);border:1px solid var(--cc-border);border-radius:var(--cc-radius);padding:40px;text-align:center}
	.cc-empty p{color:var(--cc-muted);margin:0 0 16px}
	.cc-table-wrap{overflow-x:auto}
	.cc-table{border-collapse:collapse;width:100%}
	.cc-table th,.cc-table td{border-bottom:1px solid var(--cc-border2);padding:10px 14px;text-align:left}
	.cc-table th{color:var(--cc-gold);font-size:.72rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase}
	.cc-table td{color:var(--cc-text);font-size:.84rem}
	.cc-col-title{font-weight:600;max-width:220px}
	.cc-col-actions{white-space:nowrap}
	.cc-badge{border-radius:4px;font-size:.72rem;font-weight:700;padding:2px 8px;text-transform:uppercase}
	.cc-badge--on{background:rgba(74,222,128,.15);color:var(--cc-green)}
	.cc-badge--off{background:rgba(248,113,113,.15);color:var(--cc-red)}
	.cc-link-edit,.cc-link-delete{font-size:.82rem;font-weight:600;text-decoration:none}
	.cc-link-edit{color:var(--cc-gold);margin-right:12px}
	.cc-link-delete{color:var(--cc-red)}
	.cc-link-edit:hover{color:var(--cc-gold-dk)}
	.cc-link-delete:hover{opacity:.8}
	.cc-form-head{margin-bottom:20px}
	.cc-back{color:var(--cc-muted);font-size:.82rem;text-decoration:none}
	.cc-back:hover{color:var(--cc-muted)}
	.cc-form-head h2{color:var(--cc-white);font-size:1.2rem;font-weight:700;margin:8px 0 0}
	.cc-form-grid{display:grid;gap:16px;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));margin-bottom:20px}
	.cc-form-card{background:var(--cc-surface);border:1px solid var(--cc-border);border-radius:var(--cc-radius)}
	.cc-form-card__head{border-bottom:1px solid var(--cc-border2);padding:12px 18px}
	.cc-form-card__head h3{color:var(--cc-gold);font-size:.72rem;font-weight:700;letter-spacing:.12em;margin:0;text-transform:uppercase}
	.cc-field{border-bottom:1px solid var(--cc-border2);padding:14px 18px}
	.cc-field:last-child{border-bottom:0}
	.cc-field label{color:var(--cc-white);display:block;font-size:.85rem;font-weight:600;margin-bottom:6px}
	.cc-desc{color:var(--cc-muted);font-size:.75rem;margin:0 0 8px}
	.cc-input,.cc-select,.cc-textarea{background:var(--cc-surface2);border:1px solid var(--cc-border);border-radius:6px;color:var(--cc-text);font-size:.85rem;padding:8px 12px;width:100%}
	.cc-textarea{resize:vertical}
	.cc-input:focus,.cc-select:focus,.cc-textarea:focus{border-color:var(--cc-gold);outline:none}
	.cc-field--toggle{align-items:center;display:flex;justify-content:space-between}
	.cc-field--toggle label{margin-bottom:0}
	.cc-tw{align-items:center;display:flex;gap:8px}
	.cc-tgl{position:relative;display:inline-block;width:44px;height:24px}
	.cc-tgl input{opacity:0;width:0;height:0;position:absolute}
	.cc-sld{position:absolute;cursor:pointer;inset:0;background:rgba(50,100,160,.3);border-radius:24px;transition:background .2s}
	.cc-sld::before{position:absolute;content:"";height:18px;width:18px;left:3px;bottom:3px;background:var(--cc-muted);border-radius:50%;transition:transform .2s,background .2s}
	.cc-tgl input:checked+.cc-sld{background:rgba(74,222,128,.22)}
	.cc-tgl input:checked+.cc-sld::before{transform:translateX(20px);background:var(--cc-green)}
	.cc-ts{color:var(--cc-muted);font-size:.72rem;font-weight:600;min-width:54px;text-align:right;text-transform:uppercase}
	.cc-ts--on{color:var(--cc-green)}
	.cc-form-actions{align-items:center;display:flex;gap:16px;margin-top:8px}
	.cc-save{background:var(--cc-gold);border:0;border-radius:var(--cc-radius);color:#0a0600;cursor:pointer;font-size:.88rem;font-weight:700;padding:11px 28px;transition:background .15s}
	.cc-save:hover{background:var(--cc-gold-dk)}
	.cc-cancel{color:var(--cc-muted);font-size:.85rem;text-decoration:none}
	.cc-cancel:hover{color:var(--cc-white)}
	</style>
	<?php
}

/* ---------------------------------------------------------------------------
 * Frontend render helpers
 * ------------------------------------------------------------------------- */

/**
 * Render a custom card using the Latest Analysis briefing card layout.
 *
 * @param array $card     Card data.
 * @param bool  $featured True for the featured (large, first) card style.
 */
function ewo_2025_cc_render_briefing_card( $card, $featured = false ) {
	$url   = ewo_2025_cc_resolve_url( $card );
	$title = $card['title']       ?? '';
	$eye   = $card['eyebrow']     ?? __( 'Custom', 'ewo-2025' );
	$desc  = $card['description'] ?? '';
	$img   = $card['image_url']   ?? '';
	$btn   = $card['button_text'] ?? __( 'Read More', 'ewo-2025' );
	$words = $featured ? 34 : 18;

	$classes = 'ewo-article-card ewo-briefing-card ewo-briefing-card--custom';
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
			<p class="ewo-card-meta"><span><?php echo esc_html( $eye ); ?></span></p>
			<h3>
				<?php if ( $url ) : ?><a href="<?php echo esc_url( $url ); ?>"><?php endif; ?>
				<?php echo esc_html( $title ); ?>
				<?php if ( $url ) : ?></a><?php endif; ?>
			</h3>
			<?php if ( $desc ) : ?>
				<p><?php echo esc_html( wp_trim_words( $desc, $words ) ); ?></p>
			<?php endif; ?>
			<?php if ( ! $featured && $url && $btn ) : ?>
				<a class="ewo-briefing-card__more" href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $btn ); ?> &rarr;</a>
			<?php endif; ?>
		</div>
	</article>
	<?php
}

/**
 * Render a custom card using the Strategic Playlists card layout.
 *
 * @param array $card Card data.
 */
function ewo_2025_cc_render_playlist_card( $card ) {
	$url   = ewo_2025_cc_resolve_url( $card );
	$title = $card['title']       ?? '';
	$desc  = $card['description'] ?? '';
	$img   = $card['image_url']   ?? '';
	$btn   = $card['button_text'] ?? __( 'View Playlist', 'ewo-2025' );
	?>
	<article class="ewo-youtube-playlists__card ewo-youtube-playlists__card--custom">
		<div class="ewo-youtube-playlists__thumb">
			<?php if ( $img ) : ?>
				<img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy">
			<?php else : ?>
				<span class="ewo-youtube-playlists__placeholder" aria-hidden="true"></span>
			<?php endif; ?>
		</div>
		<div class="ewo-youtube-playlists__body">
			<h3><?php echo esc_html( $title ); ?></h3>
			<?php if ( $desc ) : ?>
				<p><?php echo esc_html( wp_trim_words( $desc, 24 ) ); ?></p>
			<?php endif; ?>
			<?php if ( $url ) : ?>
				<a class="ewo-youtube-playlists__button" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer">
					<?php echo esc_html( $btn ); ?>
				</a>
			<?php endif; ?>
		</div>
	</article>
	<?php
}
