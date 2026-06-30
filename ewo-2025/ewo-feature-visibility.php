<?php
/**
 * Feature Visibility Manager — central on/off controls for all frontend sections.
 *
 * Option: ewo_2025_feature_visibility (array of feature_key => 0|1)
 * All features default to enabled so the current site layout is preserved on first load.
 *
 * @package EWO_2025
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'EWO_2025_FEATURE_VIS_OPTION', 'ewo_2025_feature_visibility' );

/**
 * Full feature registry: keys, labels, descriptions.
 *
 * @return array<string,array{label:string,desc:string}>
 */
function ewo_2025_fv_features() {
	return array(
		'youtube_slider'    => array(
			'label' => __( 'YouTube Slider', 'ewo-2025' ),
			'desc'  => __( 'Featured Analysis video carousel (homepage §2)', 'ewo-2025' ),
		),
		'latest_analysis'   => array(
			'label' => __( 'Latest Analysis', 'ewo-2025' ),
			'desc'  => __( 'Research & Analysis section — WordPress articles and Substack imports (homepage §7)', 'ewo-2025' ),
		),
		'community_wall'    => array(
			'label' => __( 'Community Wall', 'ewo-2025' ),
			'desc'  => __( 'Community posts grid (homepage §4)', 'ewo-2025' ),
		),
		'strategic_domains' => array(
			'label' => __( 'Strategic Domains / Smart Feed', 'ewo-2025' ),
			'desc'  => __( 'Smart Feed intelligence domain cards (homepage §5)', 'ewo-2025' ),
		),
		'smart_feed'        => array(
			'label' => __( 'Smart Feed Page', 'ewo-2025' ),
			'desc'  => __( 'Public /smart-feed/ page content (independent of the homepage section)', 'ewo-2025' ),
		),
		'predictions'       => array(
			'label' => __( 'Predictions', 'ewo-2025' ),
			'desc'  => __( 'Strategic Predictions section (homepage §6)', 'ewo-2025' ),
		),
		'featured_videos'   => array(
			'label' => __( 'Featured Videos / Playlists', 'ewo-2025' ),
			'desc'  => __( 'Strategic Playlists carousel (homepage §8)', 'ewo-2025' ),
		),
		'book_section'      => array(
			'label' => __( 'Book Section', 'ewo-2025' ),
			'desc'  => __( 'Book promotion card (homepage §10)', 'ewo-2025' ),
		),
		'platform_network'  => array(
			'label' => __( 'Platform Network Section', 'ewo-2025' ),
			'desc'  => __( 'Connect With EWO platform cards: YouTube, Spotify, X, Substack (homepage §9)', 'ewo-2025' ),
		),
		'header_chips'      => array(
			'label' => __( 'Social Platform Chips', 'ewo-2025' ),
			'desc'  => __( 'Circular platform shortcode chips in the site header', 'ewo-2025' ),
		),
		'sidebar_social'    => array(
			'label' => __( 'Sidebar Social Cards', 'ewo-2025' ),
			'desc'  => __( 'Follow EWO card in the homepage sidebar', 'ewo-2025' ),
		),
		'footer_social'     => array(
			'label' => __( 'Footer Social Links', 'ewo-2025' ),
			'desc'  => __( 'Footer platform cards and icon row', 'ewo-2025' ),
		),
		'newsletter'        => array(
			'label' => __( 'Newsletter Section', 'ewo-2025' ),
			'desc'  => __( 'Subscribe / newsletter block in the footer', 'ewo-2025' ),
		),
	);
}

/**
 * Default state: all features enabled.
 *
 * @return array<string,int>
 */
function ewo_2025_fv_defaults() {
	return array_fill_keys( array_keys( ewo_2025_fv_features() ), 1 );
}

/**
 * Check whether a named feature is enabled.
 *
 * Defaults to enabled when the option is missing or the key is absent,
 * so the current site layout is never accidentally broken.
 *
 * @param string $key Feature key (see ewo_2025_fv_features()).
 * @return bool
 */
function ewo_2025_feature_enabled( $key ) {
	static $cache = null;
	if ( null === $cache ) {
		$saved = get_option( EWO_2025_FEATURE_VIS_OPTION, array() );
		$cache = is_array( $saved )
			? array_merge( ewo_2025_fv_defaults(), $saved )
			: ewo_2025_fv_defaults();
	}
	return ! empty( $cache[ $key ] );
}

/* ---------------------------------------------------------------------------
 * Admin
 * ------------------------------------------------------------------------- */

/**
 * Register the Feature Visibility submenu page.
 */
function ewo_2025_fv_admin_menu() {
	add_submenu_page(
		'ewo-settings',
		__( 'Feature Visibility', 'ewo-2025' ),
		__( 'Feature Visibility', 'ewo-2025' ),
		'manage_options',
		'ewo-feature-visibility',
		'ewo_2025_fv_render_page'
	);
}
add_action( 'admin_menu', 'ewo_2025_fv_admin_menu' );

/**
 * Handle save — POSTed via admin-post.php action ewo_fv_save.
 */
function ewo_2025_fv_handle_save() {
	check_admin_referer( 'ewo_fv_save', 'ewo_fv_nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Permission denied.', 'ewo-2025' ) );
	}

	$features = ewo_2025_fv_features();
	$raw      = isset( $_POST['ewo_fv'] ) && is_array( $_POST['ewo_fv'] ) // phpcs:ignore WordPress.Security.NonceVerification
		? $_POST['ewo_fv'] // phpcs:ignore WordPress.Security.NonceVerification
		: array();

	$output = array();
	foreach ( $features as $key => $unused ) {
		$output[ sanitize_key( $key ) ] = isset( $raw[ $key ] ) ? 1 : 0;
	}

	update_option( EWO_2025_FEATURE_VIS_OPTION, $output );

	// Write dynamic section enable/disable back to EWO_2025_DYN_SECTIONS_OPTION.
	if ( defined( 'EWO_2025_DYN_SECTIONS_OPTION' ) ) {
		$ds_raw   = isset( $_POST['ewo_fv_ds'] ) && is_array( $_POST['ewo_fv_ds'] ) // phpcs:ignore WordPress.Security.NonceVerification
			? $_POST['ewo_fv_ds'] // phpcs:ignore WordPress.Security.NonceVerification
			: array();
		$all_secs = get_option( EWO_2025_DYN_SECTIONS_OPTION, array() );
		if ( is_array( $all_secs ) ) {
			$now = gmdate( 'Y-m-d H:i:s' );
			foreach ( $all_secs as &$sec ) {
				if ( ! empty( $sec['builtin'] ) ) {
					continue;
				}
				$sec['enabled'] = isset( $ds_raw[ $sec['id'] ?? '' ] ) ? 1 : 0;
				$sec['updated'] = $now;
			}
			unset( $sec );
			update_option( EWO_2025_DYN_SECTIONS_OPTION, $all_secs );
		}
	}

	wp_redirect( admin_url( 'admin.php?page=ewo-feature-visibility&saved=1' ) );
	exit;
}
add_action( 'admin_post_ewo_fv_save', 'ewo_2025_fv_handle_save' );

/**
 * Render the Feature Visibility admin page.
 */
function ewo_2025_fv_render_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$features      = ewo_2025_fv_features();
	$saved         = get_option( EWO_2025_FEATURE_VIS_OPTION, array() );
	$settings      = is_array( $saved )
		? array_merge( ewo_2025_fv_defaults(), $saved )
		: ewo_2025_fv_defaults();
	$saved_msg     = isset( $_GET['saved'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['saved'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
	$enabled_count = count( array_filter( $settings ) );

	// User-created dynamic sections (exclude built-ins, which are always rendered by front-page.php).
	$dyn_sections = defined( 'EWO_2025_DYN_SECTIONS_OPTION' ) && function_exists( 'ewo_2025_ds_get_all_sections' )
		? array_values( array_filter( ewo_2025_ds_get_all_sections(), function ( $s ) { return empty( $s['builtin'] ); } ) )
		: array();
	$dyn_enabled    = count( array_filter( $dyn_sections, function ( $s ) { return ! empty( $s['enabled'] ); } ) );
	$total_count    = count( $features ) + count( $dyn_sections );
	$disabled_count = $total_count - $enabled_count - $dyn_enabled;

	// Feature groups for display.
	$groups = array(
		array(
			'title' => __( 'Homepage Sections', 'ewo-2025' ),
			'keys'  => array( 'youtube_slider', 'latest_analysis', 'community_wall', 'strategic_domains', 'predictions', 'featured_videos', 'book_section', 'platform_network' ),
		),
		array(
			'title' => __( 'Pages', 'ewo-2025' ),
			'keys'  => array( 'smart_feed' ),
		),
		array(
			'title' => __( 'Social & Platform Elements', 'ewo-2025' ),
			'keys'  => array( 'header_chips', 'sidebar_social', 'footer_social', 'newsletter' ),
		),
	);
	?>
	<style>
	:root{--fv-bg:#060f1e;--fv-surface:#0b1829;--fv-border:rgba(50,100,160,.3);--fv-border2:rgba(50,100,160,.14);--fv-gold:#d7a84b;--fv-gold-dk:#b08020;--fv-text:#dde8f5;--fv-muted:#6b88b5;--fv-label:#8aaad4;--fv-white:#fff;--fv-green:#4ade80;--fv-red:#f87171;--fv-radius:8px}
	#ewo-fv-page{background:var(--fv-bg);min-height:100vh;padding:28px 24px 60px;color:var(--fv-text);font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}
	#ewo-fv-page *{box-sizing:border-box}
	.fv-head{margin-bottom:24px}
	.fv-head h1{color:var(--fv-white);font-size:1.5rem;font-weight:700;margin:0 0 6px}
	.fv-head p{color:var(--fv-muted);font-size:.85rem;margin:0}
	.fv-notice{background:rgba(215,168,75,.1);border:1px solid rgba(215,168,75,.4);border-radius:var(--fv-radius);color:var(--fv-gold);font-size:.82rem;font-weight:600;margin-bottom:20px;padding:10px 16px}
	.fv-stats{display:flex;gap:12px;margin-bottom:24px;flex-wrap:wrap}
	.fv-stat{background:var(--fv-surface);border:1px solid var(--fv-border);border-radius:var(--fv-radius);min-width:110px;padding:14px 18px;text-align:center}
	.fv-stat__num{color:var(--fv-white);font-size:1.6rem;font-weight:700;line-height:1}
	.fv-stat__num--g{color:var(--fv-green)}
	.fv-stat__num--r{color:var(--fv-red)}
	.fv-stat__lbl{color:var(--fv-muted);font-size:.72rem;font-weight:600;letter-spacing:.06em;margin-top:4px;text-transform:uppercase}
	.fv-card{background:var(--fv-surface);border:1px solid var(--fv-border);border-radius:var(--fv-radius);margin-bottom:14px}
	.fv-card__head{border-bottom:1px solid var(--fv-border2);padding:12px 18px}
	.fv-card__head h2{color:var(--fv-gold);font-size:.72rem;font-weight:700;letter-spacing:.12em;margin:0;text-transform:uppercase}
	.fv-row{align-items:center;border-bottom:1px solid var(--fv-border2);display:flex;gap:16px;padding:14px 18px}
	.fv-row:last-child{border-bottom:0}
	.fv-row__info{flex:1}
	.fv-row__label{color:var(--fv-white);font-size:.88rem;font-weight:600}
	.fv-row__desc{color:var(--fv-muted);font-size:.76rem;margin-top:2px}
	.fv-tw{align-items:center;display:flex;flex-shrink:0;gap:8px}
	.fv-tgl{position:relative;display:inline-block;width:44px;height:24px}
	.fv-tgl input{opacity:0;width:0;height:0;position:absolute}
	.fv-sld{position:absolute;cursor:pointer;inset:0;background:rgba(50,100,160,.3);border-radius:24px;transition:background .2s}
	.fv-sld::before{position:absolute;content:"";height:18px;width:18px;left:3px;bottom:3px;background:var(--fv-muted);border-radius:50%;transition:transform .2s,background .2s}
	.fv-tgl input:checked+.fv-sld{background:rgba(74,222,128,.22)}
	.fv-tgl input:checked+.fv-sld::before{transform:translateX(20px);background:var(--fv-green)}
	.fv-ts{color:var(--fv-muted);font-size:.72rem;font-weight:600;min-width:54px;text-align:right;text-transform:uppercase}
	.fv-ts--on{color:var(--fv-green)}
	.fv-actions{margin-top:20px}
	.fv-save{background:var(--fv-gold);border:0;border-radius:var(--fv-radius);color:#0a0600;cursor:pointer;font-size:.88rem;font-weight:700;padding:11px 28px;transition:background .15s}
	.fv-save:hover{background:var(--fv-gold-dk)}
	</style>
	<script>
	function ewoFvToggle(el){
		var tw=el.closest('.fv-tw');
		var ts=tw.querySelector('.fv-ts');
		if(el.checked){ts.textContent='Enabled';ts.classList.add('fv-ts--on');}
		else{ts.textContent='Disabled';ts.classList.remove('fv-ts--on');}
	}
	</script>

	<div id="ewo-fv-page">
		<div class="fv-head">
			<h1><?php esc_html_e( 'Feature Visibility', 'ewo-2025' ); ?></h1>
			<p><?php esc_html_e( 'Toggle frontend sections on or off. Disabled features are hidden completely — no data is ever deleted.', 'ewo-2025' ); ?></p>
		</div>

		<?php if ( $saved_msg ) : ?>
			<div class="fv-notice"><?php esc_html_e( 'Visibility settings saved.', 'ewo-2025' ); ?></div>
		<?php endif; ?>

		<div class="fv-stats">
			<div class="fv-stat">
				<div class="fv-stat__num"><?php echo esc_html( (string) $total_count ); ?></div>
				<div class="fv-stat__lbl"><?php esc_html_e( 'Total Features', 'ewo-2025' ); ?></div>
			</div>
			<div class="fv-stat">
				<div class="fv-stat__num fv-stat__num--g"><?php echo esc_html( (string) ( $enabled_count + $dyn_enabled ) ); ?></div>
				<div class="fv-stat__lbl"><?php esc_html_e( 'Enabled', 'ewo-2025' ); ?></div>
			</div>
			<div class="fv-stat">
				<div class="fv-stat__num<?php echo $disabled_count > 0 ? ' fv-stat__num--r' : ''; ?>"><?php echo esc_html( (string) $disabled_count ); ?></div>
				<div class="fv-stat__lbl"><?php esc_html_e( 'Disabled', 'ewo-2025' ); ?></div>
			</div>
		</div>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="ewo_fv_save">
			<?php wp_nonce_field( 'ewo_fv_save', 'ewo_fv_nonce' ); ?>

			<?php foreach ( $groups as $group ) : ?>
			<div class="fv-card">
				<div class="fv-card__head">
					<h2><?php echo esc_html( $group['title'] ); ?></h2>
				</div>
				<?php foreach ( $group['keys'] as $key ) :
					$f  = $features[ $key ];
					$on = ! empty( $settings[ $key ] );
				?>
				<div class="fv-row">
					<div class="fv-row__info">
						<div class="fv-row__label"><?php echo esc_html( $f['label'] ); ?></div>
						<div class="fv-row__desc"><?php echo esc_html( $f['desc'] ); ?></div>
					</div>
					<div class="fv-tw">
						<label class="fv-tgl" title="<?php echo esc_attr( $f['label'] ); ?>">
							<input type="checkbox"
								name="ewo_fv[<?php echo esc_attr( $key ); ?>]"
								value="1"
								<?php checked( $on ); ?>
								onchange="ewoFvToggle(this)">
							<span class="fv-sld"></span>
						</label>
						<span class="fv-ts<?php echo $on ? ' fv-ts--on' : ''; ?>">
							<?php echo $on ? esc_html__( 'Enabled', 'ewo-2025' ) : esc_html__( 'Disabled', 'ewo-2025' ); ?>
						</span>
					</div>
				</div>
				<?php endforeach; ?>
			</div>
			<?php endforeach; ?>

			<?php if ( ! empty( $dyn_sections ) ) : ?>
			<div class="fv-card">
				<div class="fv-card__head">
					<h2><?php esc_html_e( 'Custom Sections', 'ewo-2025' ); ?></h2>
				</div>
				<?php foreach ( $dyn_sections as $sec ) :
					$sid        = $sec['id'] ?? '';
					$on         = ! empty( $sec['enabled'] );
					$card_count = function_exists( 'ewo_2025_ds_get_cards_for_section' )
						? count( ewo_2025_ds_get_cards_for_section( $sid, false ) ) : 0;
					$desc_parts = array();
					if ( $card_count > 0 ) {
						$desc_parts[] = sprintf(
							/* translators: %d: number of cards */
							_n( '%d card', '%d cards', $card_count, 'ewo-2025' ),
							$card_count
						);
					}
					if ( ! empty( $sec['eyebrow'] ) ) {
						$desc_parts[] = $sec['eyebrow'];
					}
					$fv_desc = ! empty( $desc_parts ) ? implode( ' — ', $desc_parts ) : __( 'Dynamic homepage section', 'ewo-2025' );
				?>
				<div class="fv-row">
					<div class="fv-row__info">
						<div class="fv-row__label"><?php echo esc_html( $sec['title'] ?? '' ); ?></div>
						<div class="fv-row__desc"><?php echo esc_html( $fv_desc ); ?></div>
					</div>
					<div class="fv-tw">
						<label class="fv-tgl" title="<?php echo esc_attr( $sec['title'] ?? '' ); ?>">
							<input type="checkbox"
								name="ewo_fv_ds[<?php echo esc_attr( $sid ); ?>]"
								value="1"
								<?php checked( $on ); ?>
								onchange="ewoFvToggle(this)">
							<span class="fv-sld"></span>
						</label>
						<span class="fv-ts<?php echo $on ? ' fv-ts--on' : ''; ?>">
							<?php echo $on ? esc_html__( 'Enabled', 'ewo-2025' ) : esc_html__( 'Disabled', 'ewo-2025' ); ?>
						</span>
					</div>
				</div>
				<?php endforeach; ?>
			</div>
			<?php elseif ( defined( 'EWO_2025_DYN_SECTIONS_OPTION' ) ) : ?>
			<div class="fv-card">
				<div class="fv-card__head"><h2><?php esc_html_e( 'Custom Sections', 'ewo-2025' ); ?></h2></div>
				<div class="fv-row">
					<div class="fv-row__info">
						<div class="fv-row__label" style="color:var(--fv-muted)"><?php esc_html_e( 'No custom sections yet.', 'ewo-2025' ); ?></div>
						<div class="fv-row__desc">
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=ewo-dyn-sections&action=add' ) ); ?>" style="color:var(--fv-gold)">
								<?php esc_html_e( 'Create a section in EWO Settings → Homepage Sections', 'ewo-2025' ); ?>
							</a>
						</div>
					</div>
				</div>
			</div>
			<?php endif; ?>

			<div class="fv-actions">
				<button type="submit" class="fv-save"><?php esc_html_e( 'Save Visibility Settings', 'ewo-2025' ); ?></button>
			</div>
		</form>
	</div>
	<?php
}
