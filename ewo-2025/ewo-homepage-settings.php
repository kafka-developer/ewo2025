<?php
/**
 * EWO Homepage Settings — visibility, source, and count controls for homepage sections.
 *
 * Show/hide toggles write directly to the same ewo_2025_feature_visibility option used
 * by the Feature Visibility Manager, so both pages stay in sync automatically.
 *
 * Source and count controls use a separate option: ewo_2025_homepage_settings.
 *
 * @package EWO_2025
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'EWO_2025_HOMEPAGE_SETTINGS_OPTION', 'ewo_2025_homepage_settings' );

/**
 * Default homepage settings.
 *
 * @return array<string,mixed>
 */
function ewo_2025_hps_defaults() {
	return array(
		'latest_analysis_source' => 'both',  // 'both' | 'wp_only' | 'substack_only'
		'latest_analysis_count'  => 5,       // 1–20
		'playlists_filter'       => 'all',   // 'all' | 'featured'
		'playlists_count'        => 6,       // 1–24
		'domains_count'          => 0,       // 0 = show all
		'predictions_count'      => 6,       // 1–20
		'latest_analysis_mode'   => 'auto',  // 'auto' | 'custom' | 'mixed'
		'playlists_mode'         => 'auto',  // 'auto' | 'custom' | 'mixed'
	);
}

/**
 * Get merged homepage settings (saved over defaults).
 *
 * @return array<string,mixed>
 */
function ewo_2025_hps_get() {
	static $cache = null;
	if ( null === $cache ) {
		$saved = get_option( EWO_2025_HOMEPAGE_SETTINGS_OPTION, array() );
		$cache = is_array( $saved )
			? array_merge( ewo_2025_hps_defaults(), $saved )
			: ewo_2025_hps_defaults();
	}
	return $cache;
}

/* ---------------------------------------------------------------------------
 * Admin
 * ------------------------------------------------------------------------- */

/**
 * Register the Homepage Settings submenu page.
 */
function ewo_2025_hps_admin_menu() {
	add_submenu_page(
		'ewo-settings',
		__( 'Homepage Settings', 'ewo-2025' ),
		__( 'Homepage Settings', 'ewo-2025' ),
		'manage_options',
		'ewo-homepage-settings',
		'ewo_2025_hps_render_page'
	);
}
add_action( 'admin_menu', 'ewo_2025_hps_admin_menu' );

/**
 * Handle save — posted via admin-post.php action ewo_hps_save.
 */
function ewo_2025_hps_handle_save() {
	check_admin_referer( 'ewo_hps_save', 'ewo_hps_nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Permission denied.', 'ewo-2025' ) );
	}

	$raw = isset( $_POST['ewo_hps'] ) && is_array( $_POST['ewo_hps'] ) // phpcs:ignore WordPress.Security.NonceVerification
		? $_POST['ewo_hps'] // phpcs:ignore WordPress.Security.NonceVerification
		: array();

	// Save source/count/mode settings.
	$valid_sources  = array( 'both', 'wp_only', 'substack_only' );
	$valid_filters  = array( 'all', 'featured' );
	$valid_modes    = array( 'auto', 'custom', 'mixed' );
	$raw_src        = isset( $raw['latest_analysis_source'] ) ? sanitize_text_field( wp_unslash( $raw['latest_analysis_source'] ) ) : 'both';
	$raw_flt        = isset( $raw['playlists_filter'] ) ? sanitize_text_field( wp_unslash( $raw['playlists_filter'] ) ) : 'all';
	$raw_la_mode    = isset( $raw['latest_analysis_mode'] ) ? sanitize_text_field( wp_unslash( $raw['latest_analysis_mode'] ) ) : 'auto';
	$raw_pl_mode    = isset( $raw['playlists_mode'] )       ? sanitize_text_field( wp_unslash( $raw['playlists_mode'] ) )       : 'auto';

	$settings = array(
		'latest_analysis_source' => in_array( $raw_src, $valid_sources, true ) ? $raw_src : 'both',
		'latest_analysis_count'  => max( 1, min( 20, (int) ( $raw['latest_analysis_count'] ?? 5 ) ) ),
		'playlists_filter'       => in_array( $raw_flt, $valid_filters, true ) ? $raw_flt : 'all',
		'playlists_count'        => max( 1, min( 24, (int) ( $raw['playlists_count'] ?? 6 ) ) ),
		'domains_count'          => max( 0, min( 20, (int) ( $raw['domains_count'] ?? 0 ) ) ),
		'predictions_count'      => max( 1, min( 20, (int) ( $raw['predictions_count'] ?? 6 ) ) ),
		'latest_analysis_mode'   => in_array( $raw_la_mode, $valid_modes, true ) ? $raw_la_mode : 'auto',
		'playlists_mode'         => in_array( $raw_pl_mode, $valid_modes, true ) ? $raw_pl_mode : 'auto',
	);
	update_option( EWO_2025_HOMEPAGE_SETTINGS_OPTION, $settings );

	wp_redirect( admin_url( 'admin.php?page=ewo-homepage-settings&saved=1' ) );
	exit;
}
add_action( 'admin_post_ewo_hps_save', 'ewo_2025_hps_handle_save' );

/**
 * Render the Homepage Settings admin page.
 */
function ewo_2025_hps_render_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$hps       = ewo_2025_hps_get();
	$saved_msg = isset( $_GET['saved'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['saved'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
	?>
	<style>
	:root{--hs-bg:#060f1e;--hs-surface:#0b1829;--hs-surface2:#0f2035;--hs-border:rgba(50,100,160,.3);--hs-border2:rgba(50,100,160,.14);--hs-gold:#d7a84b;--hs-gold-dk:#b08020;--hs-text:#dde8f5;--hs-muted:#6b88b5;--hs-label:#8aaad4;--hs-white:#fff;--hs-green:#4ade80;--hs-radius:8px}
	#ewo-hs-page{background:var(--hs-bg);min-height:100vh;padding:28px 24px 60px;color:var(--hs-text);font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}
	#ewo-hs-page *{box-sizing:border-box}
	.hs-head{margin-bottom:24px}
	.hs-head h1{color:var(--hs-white);font-size:1.5rem;font-weight:700;margin:0 0 6px}
	.hs-head p{color:var(--hs-muted);font-size:.85rem;margin:0}
	.hs-notice{background:rgba(215,168,75,.1);border:1px solid rgba(215,168,75,.4);border-radius:var(--hs-radius);color:var(--hs-gold);font-size:.82rem;font-weight:600;margin-bottom:20px;padding:10px 16px}
	.hs-row{display:flex;gap:20px;flex-wrap:wrap;margin-bottom:14px;align-items:flex-start}
	.hs-card{background:var(--hs-surface);border:1px solid var(--hs-border);border-radius:var(--hs-radius);flex:1;min-width:280px}
	.hs-card--full{flex-basis:100%;flex-shrink:0}
	.hs-card__head{border-bottom:1px solid var(--hs-border2);padding:12px 18px}
	.hs-card__head h2{color:var(--hs-gold);font-size:.72rem;font-weight:700;letter-spacing:.12em;margin:0;text-transform:uppercase}
	.hs-field{border-bottom:1px solid var(--hs-border2);padding:14px 18px}
	.hs-field:last-child{border-bottom:0}
	.hs-field label{color:var(--hs-white);display:block;font-size:.85rem;font-weight:600;margin-bottom:6px}
	.hs-field .hs-desc{color:var(--hs-muted);font-size:.75rem;margin-bottom:8px}
	.hs-select,.hs-input{background:var(--hs-surface2);border:1px solid var(--hs-border);border-radius:6px;color:var(--hs-text);font-size:.85rem;padding:8px 12px;width:100%}
	.hs-select:focus,.hs-input:focus{border-color:var(--hs-gold);outline:none}
	.hs-actions{margin-top:20px}
	.hs-save{background:var(--hs-gold);border:0;border-radius:var(--hs-radius);color:#0a0600;cursor:pointer;font-size:.88rem;font-weight:700;padding:11px 28px;transition:background .15s}
	.hs-save:hover{background:var(--hs-gold-dk)}
	.hs-radio-group{display:flex;flex-direction:column;gap:8px;margin-top:4px}
	.hs-radio-group label{color:var(--hs-text);display:flex;align-items:center;gap:8px;font-size:.83rem;font-weight:normal;cursor:pointer}
	.hs-radio-group input[type=radio]{accent-color:var(--hs-gold)}
	</style>
	<div id="ewo-hs-page">
		<div class="hs-head">
			<h1><?php esc_html_e( 'Homepage Settings', 'ewo-2025' ); ?></h1>
			<p><?php esc_html_e( 'Control which sections appear, where their content comes from, and how many items they show.', 'ewo-2025' ); ?></p>
		</div>

		<?php if ( $saved_msg ) : ?>
			<div class="hs-notice"><?php esc_html_e( 'Homepage settings saved.', 'ewo-2025' ); ?></div>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="ewo_hps_save">
			<?php wp_nonce_field( 'ewo_hps_save', 'ewo_hps_nonce' ); ?>

			<!-- Latest Analysis -->
			<div class="hs-row">
				<div class="hs-card">
					<div class="hs-card__head"><h2><?php esc_html_e( 'Latest Analysis — Source', 'ewo-2025' ); ?></h2></div>
					<div class="hs-field">
						<label><?php esc_html_e( 'Content source', 'ewo-2025' ); ?></label>
						<p class="hs-desc"><?php esc_html_e( 'WordPress Articles are posts written directly in WP admin. Substack imports are posts pulled in by the RSS Engine with a substack.com source URL.', 'ewo-2025' ); ?></p>
						<div class="hs-radio-group">
							<?php
							$src_opts = array(
								'both'           => __( 'Both — WordPress articles and Substack imports (default)', 'ewo-2025' ),
								'wp_only'        => __( 'WordPress articles only', 'ewo-2025' ),
								'substack_only'  => __( 'Substack imports only', 'ewo-2025' ),
							);
							foreach ( $src_opts as $val => $lbl ) : ?>
								<label>
									<input type="radio" name="ewo_hps[latest_analysis_source]" value="<?php echo esc_attr( $val ); ?>"<?php checked( $hps['latest_analysis_source'], $val ); ?>>
									<?php echo esc_html( $lbl ); ?>
								</label>
							<?php endforeach; ?>
						</div>
					</div>
				</div>
				<div class="hs-card">
					<div class="hs-card__head"><h2><?php esc_html_e( 'Latest Analysis — Count', 'ewo-2025' ); ?></h2></div>
					<div class="hs-field">
						<label for="ewo-la-count"><?php esc_html_e( 'Number of cards to show', 'ewo-2025' ); ?></label>
						<p class="hs-desc"><?php esc_html_e( 'First card is featured (large). Remaining cards are in a secondary grid. Range: 1–20.', 'ewo-2025' ); ?></p>
						<input class="hs-input" id="ewo-la-count" type="number" name="ewo_hps[latest_analysis_count]" value="<?php echo esc_attr( (string) $hps['latest_analysis_count'] ); ?>" min="1" max="20">
					</div>
				</div>
			</div>

			<!-- Strategic Playlists -->
			<div class="hs-row">
				<div class="hs-card">
					<div class="hs-card__head"><h2><?php esc_html_e( 'Strategic Playlists — Filter', 'ewo-2025' ); ?></h2></div>
					<div class="hs-field">
						<label><?php esc_html_e( 'Which playlists to show', 'ewo-2025' ); ?></label>
						<p class="hs-desc"><?php esc_html_e( 'Mark individual playlists as "Featured" in the YouTube plugin meta box to use the Featured filter.', 'ewo-2025' ); ?></p>
						<div class="hs-radio-group">
							<?php
							$pl_opts = array(
								'all'      => __( 'All playlists (default)', 'ewo-2025' ),
								'featured' => __( 'Featured playlists only', 'ewo-2025' ),
							);
							foreach ( $pl_opts as $val => $lbl ) : ?>
								<label>
									<input type="radio" name="ewo_hps[playlists_filter]" value="<?php echo esc_attr( $val ); ?>"<?php checked( $hps['playlists_filter'], $val ); ?>>
									<?php echo esc_html( $lbl ); ?>
								</label>
							<?php endforeach; ?>
						</div>
					</div>
				</div>
				<div class="hs-card">
					<div class="hs-card__head"><h2><?php esc_html_e( 'Strategic Playlists — Count', 'ewo-2025' ); ?></h2></div>
					<div class="hs-field">
						<label for="ewo-pl-count"><?php esc_html_e( 'Number of playlist cards to show', 'ewo-2025' ); ?></label>
						<p class="hs-desc"><?php esc_html_e( 'Range: 1–24.', 'ewo-2025' ); ?></p>
						<input class="hs-input" id="ewo-pl-count" type="number" name="ewo_hps[playlists_count]" value="<?php echo esc_attr( (string) $hps['playlists_count'] ); ?>" min="1" max="24">
					</div>
				</div>
			</div>

			<!-- Domains + Predictions counts -->
			<div class="hs-row">
				<div class="hs-card">
					<div class="hs-card__head"><h2><?php esc_html_e( 'Strategic Domains — Count', 'ewo-2025' ); ?></h2></div>
					<div class="hs-field">
						<label for="ewo-dom-count"><?php esc_html_e( 'Maximum domain cards to show', 'ewo-2025' ); ?></label>
						<p class="hs-desc"><?php esc_html_e( 'Set to 0 to show all domains. Range: 0–20.', 'ewo-2025' ); ?></p>
						<input class="hs-input" id="ewo-dom-count" type="number" name="ewo_hps[domains_count]" value="<?php echo esc_attr( (string) $hps['domains_count'] ); ?>" min="0" max="20">
					</div>
				</div>
				<div class="hs-card">
					<div class="hs-card__head"><h2><?php esc_html_e( 'Strategic Predictions — Count', 'ewo-2025' ); ?></h2></div>
					<div class="hs-field">
						<label for="ewo-pred-count"><?php esc_html_e( 'Number of prediction cards to show', 'ewo-2025' ); ?></label>
						<p class="hs-desc"><?php esc_html_e( 'Archived predictions are never shown. Range: 1–20.', 'ewo-2025' ); ?></p>
						<input class="hs-input" id="ewo-pred-count" type="number" name="ewo_hps[predictions_count]" value="<?php echo esc_attr( (string) $hps['predictions_count'] ); ?>" min="1" max="20">
					</div>
				</div>
			</div>

			<!-- Custom Cards Mode -->
			<div class="hs-row">
				<div class="hs-card">
					<div class="hs-card__head"><h2><?php esc_html_e( 'Latest Analysis — Custom Cards Mode', 'ewo-2025' ); ?></h2></div>
					<div class="hs-field">
						<label><?php esc_html_e( 'Content source for Latest Analysis section', 'ewo-2025' ); ?></label>
						<p class="hs-desc"><?php esc_html_e( 'Requires custom cards assigned to "Latest Analysis" in EWO Settings → Custom Cards.', 'ewo-2025' ); ?></p>
						<div class="hs-radio-group">
							<?php
							$la_mode_opts = array(
								'auto'   => __( 'Auto only — WordPress/Substack posts (default)', 'ewo-2025' ),
								'custom' => __( 'Custom only — only cards from Custom Cards admin', 'ewo-2025' ),
								'mixed'  => __( 'Mixed — auto posts first, then custom cards appended', 'ewo-2025' ),
							);
							foreach ( $la_mode_opts as $val => $lbl ) : ?>
								<label>
									<input type="radio" name="ewo_hps[latest_analysis_mode]" value="<?php echo esc_attr( $val ); ?>"<?php checked( $hps['latest_analysis_mode'], $val ); ?>>
									<?php echo esc_html( $lbl ); ?>
								</label>
							<?php endforeach; ?>
						</div>
					</div>
				</div>
				<div class="hs-card">
					<div class="hs-card__head"><h2><?php esc_html_e( 'Strategic Playlists — Custom Cards Mode', 'ewo-2025' ); ?></h2></div>
					<div class="hs-field">
						<label><?php esc_html_e( 'Content source for Strategic Playlists section', 'ewo-2025' ); ?></label>
						<p class="hs-desc"><?php esc_html_e( 'Requires custom cards assigned to "Strategic Playlists" in EWO Settings → Custom Cards.', 'ewo-2025' ); ?></p>
						<div class="hs-radio-group">
							<?php
							$pl_mode_opts = array(
								'auto'   => __( 'Auto only — YouTube playlists from plugin (default)', 'ewo-2025' ),
								'custom' => __( 'Custom only — only cards from Custom Cards admin', 'ewo-2025' ),
								'mixed'  => __( 'Mixed — auto playlists first, then custom cards appended', 'ewo-2025' ),
							);
							foreach ( $pl_mode_opts as $val => $lbl ) : ?>
								<label>
									<input type="radio" name="ewo_hps[playlists_mode]" value="<?php echo esc_attr( $val ); ?>"<?php checked( $hps['playlists_mode'], $val ); ?>>
									<?php echo esc_html( $lbl ); ?>
								</label>
							<?php endforeach; ?>
						</div>
					</div>
				</div>
			</div>

			<div class="hs-actions">
				<button type="submit" class="hs-save"><?php esc_html_e( 'Save Homepage Settings', 'ewo-2025' ); ?></button>
			</div>
		</form>
	</div>
	<?php
}
