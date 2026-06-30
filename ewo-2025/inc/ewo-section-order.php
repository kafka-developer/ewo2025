<?php
/**
 * Homepage Section Order Manager.
 *
 * Provides a drag-and-drop admin UI to reorder homepage sections and toggle
 * feature visibility for each section, all in one place.
 *
 * Option:  ewo_2025_section_order  (array: top/main/bottom => ordered key arrays)
 *
 * @package EWO_2025
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'EWO_2025_SECTION_ORDER_OPTION', 'ewo_2025_section_order' );

/* ==========================================================================
   Registry & order helpers
   ========================================================================== */

/**
 * Return the full section registry.
 *
 * Each entry: label, feature_key (null if no feature gate), group, default_order, render_cb.
 *
 * @return array<string,array{label:string,feature_key:string|null,group:string,default_order:int,render_cb:string}>
 */
function ewo_2025_so_registry() {
	return array(
		'hero'                => array(
			'label'         => __( 'Hero', 'ewo-2025' ),
			'feature_key'   => null,
			'group'         => 'top',
			'default_order' => 1,
			'render_cb'     => 'ewo_2025_render_section_hero',
		),
		'featured_analysis'   => array(
			'label'         => __( 'Featured Analysis', 'ewo-2025' ),
			'feature_key'   => 'youtube_slider',
			'group'         => 'main',
			'default_order' => 2,
			'render_cb'     => 'ewo_2025_render_section_featured_analysis',
		),
		'ewo_method'          => array(
			'label'         => __( 'The EWO Method', 'ewo-2025' ),
			'feature_key'   => null,
			'group'         => 'main',
			'default_order' => 3,
			'render_cb'     => 'ewo_2025_render_section_ewo_method',
		),
		'community_wall'      => array(
			'label'         => __( 'Community Wall', 'ewo-2025' ),
			'feature_key'   => 'community_wall',
			'group'         => 'main',
			'default_order' => 4,
			'render_cb'     => 'ewo_2025_render_section_community_wall',
		),
		'strategic_domains'   => array(
			'label'         => __( 'Strategic Domains', 'ewo-2025' ),
			'feature_key'   => 'strategic_domains',
			'group'         => 'main',
			'default_order' => 5,
			'render_cb'     => 'ewo_2025_render_section_strategic_domains',
		),
		'predictions'         => array(
			'label'         => __( 'Strategic Predictions', 'ewo-2025' ),
			'feature_key'   => 'predictions',
			'group'         => 'main',
			'default_order' => 6,
			'render_cb'     => 'ewo_2025_render_section_predictions',
		),
		'latest_analysis'     => array(
			'label'         => __( 'Latest Analysis', 'ewo-2025' ),
			'feature_key'   => 'latest_analysis',
			'group'         => 'main',
			'default_order' => 7,
			'render_cb'     => 'ewo_2025_render_section_latest_analysis',
		),
		'strategic_playlists' => array(
			'label'         => __( 'Strategic Playlists', 'ewo-2025' ),
			'feature_key'   => 'featured_videos',
			'group'         => 'main',
			'default_order' => 8,
			'render_cb'     => 'ewo_2025_render_section_strategic_playlists',
		),
		'featured_cards'      => array(
			'label'         => __( 'Featured Cards', 'ewo-2025' ),
			'feature_key'   => null,
			'group'         => 'main',
			'default_order' => 9,
			'render_cb'     => 'ewo_2025_render_section_featured_cards',
		),
		'custom_section'      => array(
			'label'         => __( 'Custom Section', 'ewo-2025' ),
			'feature_key'   => null,
			'group'         => 'main',
			'default_order' => 10,
			'render_cb'     => 'ewo_2025_render_section_custom_section',
		),
		'dynamic_sections'    => array(
			'label'         => __( 'Dynamic Sections', 'ewo-2025' ),
			'feature_key'   => null,
			'group'         => 'main',
			'default_order' => 11,
			'render_cb'     => 'ewo_2025_render_section_dynamic_sections',
		),
		'platform_network'    => array(
			'label'         => __( 'Platform Network', 'ewo-2025' ),
			'feature_key'   => 'platform_network',
			'group'         => 'bottom',
			'default_order' => 12,
			'render_cb'     => 'ewo_2025_render_section_platform_network',
		),
		'book_section'        => array(
			'label'         => __( 'Book Section', 'ewo-2025' ),
			'feature_key'   => 'book_section',
			'group'         => 'bottom',
			'default_order' => 13,
			'render_cb'     => 'ewo_2025_render_section_book_section',
		),
	);
}

/**
 * Build the default order structure from the registry.
 *
 * @return array{top:string[],main:string[],bottom:string[]}
 */
function ewo_2025_so_default_order() {
	$registry = ewo_2025_so_registry();
	$groups   = array( 'top' => array(), 'main' => array(), 'bottom' => array() );

	// Separate by group and sort by default_order within each group.
	foreach ( $registry as $key => $s ) {
		$g = isset( $groups[ $s['group'] ] ) ? $s['group'] : 'main';
		$groups[ $g ][] = array( 'key' => $key, 'order' => $s['default_order'] );
	}

	$result = array( 'top' => array(), 'main' => array(), 'bottom' => array() );
	foreach ( $groups as $g => $items ) {
		usort( $items, function ( $a, $b ) {
			return $a['order'] - $b['order'];
		} );
		$result[ $g ] = wp_list_pluck( $items, 'key' );
	}

	return $result;
}

/**
 * Get the current section order.
 *
 * - Loads from option; falls back to defaults when not set.
 * - Filters out keys removed from the registry.
 * - Appends new registry keys (not in saved) to the bottom of their group.
 *
 * @return array{top:string[],main:string[],bottom:string[]}
 */
function ewo_2025_so_get_order() {
	$registry = ewo_2025_so_registry();
	$saved    = get_option( EWO_2025_SECTION_ORDER_OPTION, null );

	if ( ! is_array( $saved ) || empty( $saved ) ) {
		return ewo_2025_so_default_order();
	}

	$defaults = ewo_2025_so_default_order();
	$result   = array( 'top' => array(), 'main' => array(), 'bottom' => array() );

	// Populate from saved, skipping keys no longer in the registry.
	foreach ( array( 'top', 'main', 'bottom' ) as $g ) {
		$saved_group = isset( $saved[ $g ] ) && is_array( $saved[ $g ] ) ? $saved[ $g ] : array();
		foreach ( $saved_group as $key ) {
			$key = sanitize_key( $key );
			if ( isset( $registry[ $key ] ) ) {
				$result[ $g ][] = $key;
			}
		}
	}

	// Append any registry keys that are not yet in the saved order.
	$all_saved = array_merge( $result['top'], $result['main'], $result['bottom'] );
	foreach ( $registry as $key => $s ) {
		if ( ! in_array( $key, $all_saved, true ) ) {
			$g = isset( $result[ $s['group'] ] ) ? $s['group'] : 'main';
			$result[ $g ][] = $key;
		}
	}

	// Hero must always be in top.
	if ( ! in_array( 'hero', $result['top'], true ) ) {
		array_unshift( $result['top'], 'hero' );
		// Remove from other groups if it snuck in.
		$result['main']   = array_values( array_diff( $result['main'], array( 'hero' ) ) );
		$result['bottom'] = array_values( array_diff( $result['bottom'], array( 'hero' ) ) );
	}

	return $result;
}

/* ==========================================================================
   Admin handlers
   ========================================================================== */

/**
 * Handle save form submission.
 */
function ewo_2025_so_handle_save() {
	check_admin_referer( 'ewo_so_save', 'ewo_so_nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Permission denied.', 'ewo-2025' ) );
	}

	$registry = ewo_2025_so_registry();

	// Sanitize section key arrays from POST.
	$raw_main   = isset( $_POST['ewo_so_main_order'] ) && is_array( $_POST['ewo_so_main_order'] )
		? $_POST['ewo_so_main_order'] // phpcs:ignore WordPress.Security.NonceVerification
		: array();
	$raw_bottom = isset( $_POST['ewo_so_bottom_order'] ) && is_array( $_POST['ewo_so_bottom_order'] )
		? $_POST['ewo_so_bottom_order'] // phpcs:ignore WordPress.Security.NonceVerification
		: array();

	$main_order   = array();
	$bottom_order = array();

	foreach ( $raw_main as $k ) {
		$k = sanitize_key( $k );
		if ( isset( $registry[ $k ] ) && 'main' === $registry[ $k ]['group'] ) {
			$main_order[] = $k;
		}
	}
	foreach ( $raw_bottom as $k ) {
		$k = sanitize_key( $k );
		if ( isset( $registry[ $k ] ) && 'bottom' === $registry[ $k ]['group'] ) {
			$bottom_order[] = $k;
		}
	}

	// Append any main/bottom keys missing from the submitted order.
	foreach ( $registry as $key => $s ) {
		if ( 'main' === $s['group'] && ! in_array( $key, $main_order, true ) ) {
			$main_order[] = $key;
		}
		if ( 'bottom' === $s['group'] && ! in_array( $key, $bottom_order, true ) ) {
			$bottom_order[] = $key;
		}
	}

	$new_order = array(
		'top'    => array( 'hero' ),
		'main'   => $main_order,
		'bottom' => $bottom_order,
	);
	update_option( EWO_2025_SECTION_ORDER_OPTION, $new_order );

	// Update only homepage-section feature-visibility keys (merge with existing).
	$raw_fv = isset( $_POST['ewo_fv'] ) && is_array( $_POST['ewo_fv'] ) // phpcs:ignore WordPress.Security.NonceVerification
		? $_POST['ewo_fv'] // phpcs:ignore WordPress.Security.NonceVerification
		: array();

	// Collect the feature_keys managed by this page.
	$managed_fv_keys = array();
	foreach ( $registry as $s ) {
		if ( ! empty( $s['feature_key'] ) ) {
			$managed_fv_keys[] = $s['feature_key'];
		}
	}
	$managed_fv_keys = array_unique( $managed_fv_keys );

	$existing_fv = get_option( EWO_2025_FEATURE_VIS_OPTION, array() );
	if ( ! is_array( $existing_fv ) ) {
		$existing_fv = array();
	}

	foreach ( $managed_fv_keys as $fv_key ) {
		$existing_fv[ sanitize_key( $fv_key ) ] = isset( $raw_fv[ $fv_key ] ) ? 1 : 0;
	}
	update_option( EWO_2025_FEATURE_VIS_OPTION, $existing_fv );

	wp_redirect( admin_url( 'admin.php?page=ewo-section-order&saved=1' ) );
	exit;
}
add_action( 'admin_post_ewo_so_save', 'ewo_2025_so_handle_save' );

/**
 * Handle reset form submission — deletes the saved order option.
 */
function ewo_2025_so_handle_reset() {
	check_admin_referer( 'ewo_so_reset', 'ewo_so_reset_nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Permission denied.', 'ewo-2025' ) );
	}

	delete_option( EWO_2025_SECTION_ORDER_OPTION );

	wp_redirect( admin_url( 'admin.php?page=ewo-section-order&reset=1' ) );
	exit;
}
add_action( 'admin_post_ewo_so_reset', 'ewo_2025_so_handle_reset' );

/* ==========================================================================
   Admin menu
   ========================================================================== */

/**
 * Register the Section Order submenu page.
 */
function ewo_2025_so_admin_menu() {
	add_submenu_page(
		'ewo-settings',
		__( 'Section Order', 'ewo-2025' ),
		__( 'Section Order', 'ewo-2025' ),
		'manage_options',
		'ewo-section-order',
		'ewo_2025_so_render_page'
	);
}
add_action( 'admin_menu', 'ewo_2025_so_admin_menu' );

/* ==========================================================================
   Admin page render
   ========================================================================== */

/**
 * Render the Section Order admin page.
 */
function ewo_2025_so_render_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	wp_enqueue_script( 'jquery-ui-sortable' );

	$registry  = ewo_2025_so_registry();
	$order     = ewo_2025_so_get_order();

	// Load current feature-visibility for toggle display.
	$fv_saved    = get_option( EWO_2025_FEATURE_VIS_OPTION, array() );
	$fv_defaults = array_fill_keys(
		array_filter( wp_list_pluck( $registry, 'feature_key' ) ),
		1
	);
	$fv_settings = is_array( $fv_saved )
		? array_merge( $fv_defaults, $fv_saved )
		: $fv_defaults;

	$saved_msg = isset( $_GET['saved'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['saved'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
	$reset_msg = isset( $_GET['reset'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['reset'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
	?>
	<style>
	:root{--so-bg:#060f1e;--so-surface:#0b1829;--so-border:rgba(50,100,160,.3);--so-border2:rgba(50,100,160,.14);--so-gold:#d7a84b;--so-gold-dk:#b08020;--so-text:#dde8f5;--so-muted:#6b88b5;--so-white:#fff;--so-green:#4ade80;--so-radius:8px;--so-drag-bg:#0f2040}
	#ewo-so-page{background:var(--so-bg);min-height:100vh;padding:28px 24px 60px;color:var(--so-text);font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}
	#ewo-so-page *{box-sizing:border-box}
	.so-head{margin-bottom:24px}
	.so-head h1{color:var(--so-white);font-size:1.5rem;font-weight:700;margin:0 0 6px}
	.so-head p{color:var(--so-muted);font-size:.85rem;margin:0}
	.so-notice{border-radius:var(--so-radius);font-size:.82rem;font-weight:600;margin-bottom:20px;padding:10px 16px}
	.so-notice--saved{background:rgba(215,168,75,.1);border:1px solid rgba(215,168,75,.4);color:var(--so-gold)}
	.so-notice--reset{background:rgba(74,222,128,.08);border:1px solid rgba(74,222,128,.3);color:var(--so-green)}
	.so-group{margin-bottom:28px}
	.so-group__head{color:var(--so-gold);font-size:.72rem;font-weight:700;letter-spacing:.12em;margin:0 0 10px;text-transform:uppercase}
	.so-card{background:var(--so-surface);border:1px solid var(--so-border);border-radius:var(--so-radius)}
	.so-list{list-style:none;margin:0;padding:0}
	.so-item{align-items:center;border-bottom:1px solid var(--so-border2);display:flex;gap:12px;padding:12px 16px}
	.so-item:last-child{border-bottom:0}
	.so-item--fixed{opacity:.5}
	.so-item.ui-sortable-helper{background:var(--so-drag-bg);border:1px solid var(--so-border);border-radius:var(--so-radius);box-shadow:0 4px 18px rgba(0,0,0,.5)}
	.so-handle{color:var(--so-muted);cursor:grab;flex-shrink:0;font-size:1.1rem;line-height:1;padding:0 4px;user-select:none}
	.so-handle--fixed{cursor:default;opacity:.3}
	.so-handle:active{cursor:grabbing}
	.so-label{color:var(--so-white);flex:1;font-size:.88rem;font-weight:600}
	.so-badge{background:rgba(50,100,160,.25);border-radius:4px;color:var(--so-muted);font-size:.7rem;font-weight:600;letter-spacing:.05em;margin-left:8px;padding:2px 7px}
	.so-arrows{display:flex;flex-direction:column;gap:2px;flex-shrink:0}
	.so-arrow{background:rgba(50,100,160,.18);border:1px solid var(--so-border);border-radius:4px;color:var(--so-muted);cursor:pointer;font-size:.75rem;line-height:1;padding:3px 7px;transition:background .15s,color .15s}
	.so-arrow:hover{background:rgba(50,100,160,.35);color:var(--so-white)}
	.so-fv{align-items:center;display:flex;flex-shrink:0;gap:6px}
	.so-tgl{position:relative;display:inline-block;width:40px;height:22px}
	.so-tgl input{opacity:0;width:0;height:0;position:absolute}
	.so-sld{position:absolute;cursor:pointer;inset:0;background:rgba(50,100,160,.3);border-radius:22px;transition:background .2s}
	.so-sld::before{position:absolute;content:"";height:16px;width:16px;left:3px;bottom:3px;background:var(--so-muted);border-radius:50%;transition:transform .2s,background .2s}
	.so-tgl input:checked+.so-sld{background:rgba(74,222,128,.22)}
	.so-tgl input:checked+.so-sld::before{transform:translateX(18px);background:var(--so-green)}
	.so-ts{color:var(--so-muted);font-size:.68rem;font-weight:600;min-width:48px;text-align:right;text-transform:uppercase}
	.so-ts--on{color:var(--so-green)}
	.so-actions{display:flex;gap:12px;margin-top:24px;flex-wrap:wrap;align-items:center}
	.so-save{background:var(--so-gold);border:0;border-radius:var(--so-radius);color:#0a0600;cursor:pointer;font-size:.88rem;font-weight:700;padding:11px 28px;transition:background .15s}
	.so-save:hover{background:var(--so-gold-dk)}
	.so-reset-form{margin:0}
	.so-reset{background:transparent;border:1px solid var(--so-border);border-radius:var(--so-radius);color:var(--so-muted);cursor:pointer;font-size:.82rem;font-weight:600;padding:10px 20px;transition:border-color .15s,color .15s}
	.so-reset:hover{border-color:var(--so-text);color:var(--so-text)}
	</style>
	<script>
	jQuery(function($){
		function soToggle(el){
			var fv=el.closest('.so-fv');
			var ts=fv.find('.so-ts');
			if(el.checked){ts.text('Enabled').addClass('so-ts--on');}
			else{ts.text('Disabled').removeClass('so-ts--on');}
		}
		$('.so-tgl input').on('change',function(){soToggle($(this));});

		function makeList(id){
			$('#'+id).sortable({
				handle:'.so-handle',
				placeholder:'so-item',
				forcePlaceholderSize:true,
				tolerance:'pointer'
			});
		}
		makeList('so-main-list');
		makeList('so-bottom-list');

		function moveItem($item,dir){
			if(dir==='up'){
				var $prev=$item.prev('.so-item');
				if($prev.length){$prev.before($item);}
			} else {
				var $next=$item.next('.so-item');
				if($next.length){$next.after($item);}
			}
		}
		$(document).on('click','.so-arrow',function(e){
			e.preventDefault();
			var $btn=$(this);
			var dir=$btn.data('dir');
			var $item=$btn.closest('.so-item');
			moveItem($item,dir);
		});

		$('#so-main-form').on('submit',function(){
			var $mainInputs=$('#so-main-hidden');
			var $botInputs=$('#so-bottom-hidden');
			$mainInputs.empty();
			$botInputs.empty();
			$('#so-main-list .so-item').each(function(){
				var key=$(this).data('key');
				$mainInputs.append('<input type="hidden" name="ewo_so_main_order[]" value="'+key+'">');
			});
			$('#so-bottom-list .so-item').each(function(){
				var key=$(this).data('key');
				$botInputs.append('<input type="hidden" name="ewo_so_bottom_order[]" value="'+key+'">');
			});
		});
	});
	</script>

	<div id="ewo-so-page">
		<div class="so-head">
			<h1><?php esc_html_e( 'Section Order', 'ewo-2025' ); ?></h1>
			<p><?php esc_html_e( 'Drag sections to reorder them on the homepage. Toggle the switch to enable or disable sections that have a feature gate.', 'ewo-2025' ); ?></p>
		</div>

		<?php if ( $saved_msg ) : ?>
			<div class="so-notice so-notice--saved"><?php esc_html_e( 'Section order and visibility saved.', 'ewo-2025' ); ?></div>
		<?php endif; ?>
		<?php if ( $reset_msg ) : ?>
			<div class="so-notice so-notice--reset"><?php esc_html_e( 'Section order reset to default.', 'ewo-2025' ); ?></div>
		<?php endif; ?>

		<form id="so-main-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="ewo_so_save">
			<?php wp_nonce_field( 'ewo_so_save', 'ewo_so_nonce' ); ?>
			<div id="so-main-hidden"></div>
			<div id="so-bottom-hidden"></div>

			<?php // TOP GROUP — fixed, not draggable. ?>
			<div class="so-group">
				<div class="so-group__head"><?php esc_html_e( 'Top (Fixed)', 'ewo-2025' ); ?></div>
				<div class="so-card">
					<ul class="so-list">
						<?php foreach ( $order['top'] as $key ) :
							if ( ! isset( $registry[ $key ] ) ) { continue; }
							$s = $registry[ $key ];
						?>
						<li class="so-item so-item--fixed" data-key="<?php echo esc_attr( $key ); ?>">
							<span class="so-handle so-handle--fixed">&#9776;</span>
							<span class="so-label">
								<?php echo esc_html( $s['label'] ); ?>
								<span class="so-badge"><?php esc_html_e( 'Fixed · Always First', 'ewo-2025' ); ?></span>
							</span>
						</li>
						<?php endforeach; ?>
					</ul>
				</div>
			</div>

			<?php // MAIN COLUMN — sortable. ?>
			<div class="so-group">
				<div class="so-group__head"><?php esc_html_e( 'Main Column', 'ewo-2025' ); ?></div>
				<div class="so-card">
					<ul class="so-list" id="so-main-list">
						<?php foreach ( $order['main'] as $key ) :
							if ( ! isset( $registry[ $key ] ) ) { continue; }
							$s    = $registry[ $key ];
							$fkey = $s['feature_key'];
							$fon  = $fkey ? ! empty( $fv_settings[ $fkey ] ) : null;
						?>
						<li class="so-item" data-key="<?php echo esc_attr( $key ); ?>">
							<span class="so-handle">&#9776;</span>
							<span class="so-label"><?php echo esc_html( $s['label'] ); ?></span>
							<?php if ( null !== $fkey ) : ?>
							<div class="so-fv">
								<label class="so-tgl" title="<?php echo esc_attr( $s['label'] ); ?>">
									<input type="checkbox"
										name="ewo_fv[<?php echo esc_attr( $fkey ); ?>]"
										value="1"
										<?php checked( $fon ); ?>>
									<span class="so-sld"></span>
								</label>
								<span class="so-ts<?php echo $fon ? ' so-ts--on' : ''; ?>">
									<?php echo $fon ? esc_html__( 'Enabled', 'ewo-2025' ) : esc_html__( 'Disabled', 'ewo-2025' ); ?>
								</span>
							</div>
							<?php endif; ?>
							<div class="so-arrows">
								<button type="button" class="so-arrow" data-dir="up" title="<?php esc_attr_e( 'Move up', 'ewo-2025' ); ?>">&#9650;</button>
								<button type="button" class="so-arrow" data-dir="down" title="<?php esc_attr_e( 'Move down', 'ewo-2025' ); ?>">&#9660;</button>
							</div>
						</li>
						<?php endforeach; ?>
					</ul>
				</div>
			</div>

			<?php // BOTTOM GROUP — sortable. ?>
			<div class="so-group">
				<div class="so-group__head"><?php esc_html_e( 'Bottom (Full Width)', 'ewo-2025' ); ?></div>
				<div class="so-card">
					<ul class="so-list" id="so-bottom-list">
						<?php foreach ( $order['bottom'] as $key ) :
							if ( ! isset( $registry[ $key ] ) ) { continue; }
							$s    = $registry[ $key ];
							$fkey = $s['feature_key'];
							$fon  = $fkey ? ! empty( $fv_settings[ $fkey ] ) : null;
						?>
						<li class="so-item" data-key="<?php echo esc_attr( $key ); ?>">
							<span class="so-handle">&#9776;</span>
							<span class="so-label"><?php echo esc_html( $s['label'] ); ?></span>
							<?php if ( null !== $fkey ) : ?>
							<div class="so-fv">
								<label class="so-tgl" title="<?php echo esc_attr( $s['label'] ); ?>">
									<input type="checkbox"
										name="ewo_fv[<?php echo esc_attr( $fkey ); ?>]"
										value="1"
										<?php checked( $fon ); ?>>
									<span class="so-sld"></span>
								</label>
								<span class="so-ts<?php echo $fon ? ' so-ts--on' : ''; ?>">
									<?php echo $fon ? esc_html__( 'Enabled', 'ewo-2025' ) : esc_html__( 'Disabled', 'ewo-2025' ); ?>
								</span>
							</div>
							<?php endif; ?>
							<div class="so-arrows">
								<button type="button" class="so-arrow" data-dir="up" title="<?php esc_attr_e( 'Move up', 'ewo-2025' ); ?>">&#9650;</button>
								<button type="button" class="so-arrow" data-dir="down" title="<?php esc_attr_e( 'Move down', 'ewo-2025' ); ?>">&#9660;</button>
							</div>
						</li>
						<?php endforeach; ?>
					</ul>
				</div>
			</div>

			<div class="so-actions">
				<button type="submit" class="so-save"><?php esc_html_e( 'Save Section Order', 'ewo-2025' ); ?></button>
			</div>
		</form>

		<form class="so-reset-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="ewo_so_reset">
			<?php wp_nonce_field( 'ewo_so_reset', 'ewo_so_reset_nonce' ); ?>
			<div class="so-actions" style="margin-top:12px">
				<button type="submit" class="so-reset"><?php esc_html_e( 'Reset to Default Order', 'ewo-2025' ); ?></button>
			</div>
		</form>
	</div>
	<?php
}
