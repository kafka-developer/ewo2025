<?php
/**
 * Reusable homepage sidebar widget system.
 *
 * A card registry renders the homepage sidebar. Cards are ordered and toggled
 * from the Sidebar Settings admin page, and new cards can be registered by
 * other code via the `ewo_2025_sidebar_cards` filter — so the architecture is
 * reusable for future widgets.
 *
 * @package EWO_2025
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const EWO_2025_SIDEBAR_OPTION = 'ewo_2025_sidebar';

/**
 * Default sidebar settings.
 *
 * @return array<string,mixed>
 */
function ewo_2025_sidebar_defaults() {
	return array(
		'about_text'      => __( 'EWO is a geopolitical intelligence publication tracking the systems, dependencies, and power shifts shaping the global order.', 'ewo-2025' ),
		'tagline_text'    => __( 'Explore. Understand. Stay Ahead.', 'ewo-2025' ),
		'tagline_enabled' => 1,
		'stats'           => array(
			'videos'    => '',
			'playlists' => '',
			'community' => '',
		),
		'sections'        => array(
			'about'  => array(
				'enabled' => 1,
				'order'   => 1,
			),
			'stats'  => array(
				'enabled' => 1,
				'order'   => 2,
			),
			'follow' => array(
				'enabled' => 1,
				'order'   => 3,
			),
		),
	);
}

/**
 * Merged sidebar settings (saved over defaults).
 *
 * @return array<string,mixed>
 */
function ewo_2025_sidebar_settings() {
	$defaults = ewo_2025_sidebar_defaults();
	$saved    = get_option( EWO_2025_SIDEBAR_OPTION, array() );
	if ( ! is_array( $saved ) ) {
		$saved = array();
	}

	$settings                    = array_merge( $defaults, $saved );
	$settings['stats']           = array_merge( $defaults['stats'], isset( $saved['stats'] ) && is_array( $saved['stats'] ) ? $saved['stats'] : array() );
	$settings['sections']        = isset( $saved['sections'] ) && is_array( $saved['sections'] ) ? $saved['sections'] : array();
	foreach ( $defaults['sections'] as $id => $cfg ) {
		$settings['sections'][ $id ] = array_merge( $cfg, isset( $saved['sections'][ $id ] ) && is_array( $saved['sections'][ $id ] ) ? $saved['sections'][ $id ] : array() );
	}

	return $settings;
}

/* ---------------------------------------------------------------------------
 * Card registry
 * ------------------------------------------------------------------------- */

/**
 * Built-in + filtered sidebar cards, sorted by order.
 *
 * Each card: id, title, enabled, order, render (callable).
 *
 * @return array<string,array<string,mixed>>
 */
function ewo_2025_sidebar_cards() {
	$settings = ewo_2025_sidebar_settings();

	$cards = array(
		'about'  => array(
			'title'  => __( 'About EWO', 'ewo-2025' ),
			'render' => 'ewo_2025_sidebar_card_about',
		),
		'stats'  => array(
			'title'  => __( 'EWO Stats', 'ewo-2025' ),
			'render' => 'ewo_2025_sidebar_card_stats',
		),
		'follow' => array(
			'title'  => __( 'Follow EWO', 'ewo-2025' ),
			'render' => 'ewo_2025_sidebar_card_follow',
		),
	);

	foreach ( $cards as $id => &$card ) {
		$cfg             = isset( $settings['sections'][ $id ] ) ? $settings['sections'][ $id ] : array();
		$card['id']      = $id;
		$card['enabled'] = ! empty( $cfg['enabled'] );
		$card['order']   = isset( $cfg['order'] ) ? (int) $cfg['order'] : 99;
	}
	unset( $card );

	/**
	 * Register additional sidebar cards.
	 *
	 * @param array<string,array<string,mixed>> $cards    Cards.
	 * @param array<string,mixed>               $settings Sidebar settings.
	 */
	$cards = apply_filters( 'ewo_2025_sidebar_cards', $cards, $settings );

	uasort(
		$cards,
		static function ( $a, $b ) {
			$ao = isset( $a['order'] ) ? (int) $a['order'] : 99;
			$bo = isset( $b['order'] ) ? (int) $b['order'] : 99;
			return $ao === $bo ? 0 : ( $ao < $bo ? -1 : 1 );
		}
	);

	return $cards;
}

/**
 * Render the homepage sidebar.
 */
function ewo_2025_sidebar() {
	$cards   = ewo_2025_sidebar_cards();
	$visible = array_filter(
		$cards,
		static function ( $card ) {
			return ! empty( $card['enabled'] ) && ! empty( $card['render'] ) && is_callable( $card['render'] );
		}
	);

	if ( empty( $visible ) ) {
		return;
	}
	?>
	<aside class="ewo-home-sidebar" aria-label="<?php esc_attr_e( 'About and follow EWO', 'ewo-2025' ); ?>">
		<div class="ewo-home-sidebar__inner">
			<?php
			foreach ( $visible as $id => $card ) :
				?>
				<section class="ewo-sidebar-card ewo-sidebar-card--<?php echo esc_attr( $id ); ?>">
					<?php if ( ! empty( $card['title'] ) ) : ?>
						<h2 class="ewo-sidebar-card__title"><?php echo esc_html( $card['title'] ); ?></h2>
					<?php endif; ?>
					<?php call_user_func( $card['render'], $card, $id ); ?>
				</section>
				<?php
			endforeach;
			?>
		</div>
	</aside>
	<?php
}

/* ---------------------------------------------------------------------------
 * Default card renderers
 * ------------------------------------------------------------------------- */

/**
 * About card.
 */
function ewo_2025_sidebar_card_about() {
	$s = ewo_2025_sidebar_settings();

	if ( '' !== trim( (string) $s['about_text'] ) ) {
		echo '<p class="ewo-sidebar-card__text">' . esc_html( $s['about_text'] ) . '</p>';
	}
	if ( ! empty( $s['tagline_enabled'] ) && '' !== trim( (string) $s['tagline_text'] ) ) {
		echo '<p class="ewo-sidebar-card__tagline">' . esc_html( $s['tagline_text'] ) . '</p>';
	}
}

/**
 * Stats card.
 */
function ewo_2025_sidebar_card_stats() {
	$rows = array(
		'videos'    => __( 'Videos', 'ewo-2025' ),
		'playlists' => __( 'Playlists', 'ewo-2025' ),
		'community' => __( 'Community', 'ewo-2025' ),
	);
	echo '<ul class="ewo-sidebar-stats">';
	foreach ( $rows as $key => $label ) {
		echo '<li class="ewo-sidebar-stats__item">';
		echo '<span class="ewo-sidebar-stats__value">' . esc_html( ewo_2025_sidebar_stat_value( $key ) ) . '</span>';
		echo '<span class="ewo-sidebar-stats__label">' . esc_html( $label ) . '</span>';
		echo '</li>';
	}
	echo '</ul>';
}

/**
 * Resolve a stat value: admin override, else live count.
 *
 * @param string $key videos|playlists|community.
 * @return string
 */
function ewo_2025_sidebar_stat_value( $key ) {
	$s   = ewo_2025_sidebar_settings();
	$val = isset( $s['stats'][ $key ] ) ? trim( (string) $s['stats'][ $key ] ) : '';
	if ( '' !== $val ) {
		return $val;
	}

	$cpt = array(
		'videos'    => 'ewo_video',
		'playlists' => 'ewo_playlist',
		'community' => 'ewo_community_post',
	);

	if ( isset( $cpt[ $key ] ) && post_type_exists( $cpt[ $key ] ) ) {
		$counts = wp_count_posts( $cpt[ $key ] );
		return (string) ( isset( $counts->publish ) ? (int) $counts->publish : 0 );
	}

	return '0';
}

/**
 * Follow card — platform links (reuse configured platform URLs).
 */
function ewo_2025_sidebar_card_follow() {
	if ( empty( ewo_2025_get_platform_surface_links( 'sidecard' ) ) ) {
		echo '<p class="ewo-sidebar-card__muted">' . esc_html__( 'Enable links for the Side Card in EWO Settings → Social Links.', 'ewo-2025' ) . '</p>';
		return;
	}
	echo '<div class="ewo-sidebar-follow">';
	ewo_2025_render_sidecard_links();
	echo '</div>';
}

/* ---------------------------------------------------------------------------
 * Admin: Sidebar Settings page
 * ------------------------------------------------------------------------- */

/**
 * Sidebar settings admin screen.
 */
class EWO_2025_Sidebar_Settings {
	const SLUG         = 'ewo-sidebar-settings';
	const SAVE_ACTION  = 'ewo_2025_save_sidebar';
	const NONCE        = 'ewo_2025_sidebar_nonce';

	/**
	 * Hook into admin.
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_post_' . self::SAVE_ACTION, array( $this, 'save' ) );
	}

	/**
	 * Register the page under Appearance.
	 */
	public function menu() {
		add_theme_page(
			__( 'EWO Sidebar', 'ewo-2025' ),
			__( 'EWO Sidebar', 'ewo-2025' ),
			'edit_theme_options',
			self::SLUG,
			array( $this, 'render' )
		);
	}



	/**
	 * Render the settings form.
	 */
	public function render() {
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			return;
		}
		$s        = ewo_2025_sidebar_settings();
		$sections = array(
			'about'  => __( 'About EWO', 'ewo-2025' ),
			'stats'  => __( 'EWO Stats', 'ewo-2025' ),
			'follow' => __( 'Follow EWO', 'ewo-2025' ),
		);
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'EWO Sidebar Settings', 'ewo-2025' ); ?></h1>
			<?php if ( isset( $_GET['updated'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Sidebar settings saved.', 'ewo-2025' ); ?></p></div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::SAVE_ACTION ); ?>" />
				<?php wp_nonce_field( self::NONCE ); ?>

				<h2><?php esc_html_e( 'Sections', 'ewo-2025' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php foreach ( $sections as $id => $label ) : $cfg = $s['sections'][ $id ]; ?>
						<tr>
							<th scope="row"><?php echo esc_html( $label ); ?></th>
							<td>
								<label><input type="checkbox" name="sections[<?php echo esc_attr( $id ); ?>][enabled]" value="1" <?php checked( ! empty( $cfg['enabled'] ) ); ?> /> <?php esc_html_e( 'Enabled', 'ewo-2025' ); ?></label>
								&nbsp;&nbsp;
								<label><?php esc_html_e( 'Order', 'ewo-2025' ); ?>
									<input type="number" min="1" max="99" style="width:64px;" name="sections[<?php echo esc_attr( $id ); ?>][order]" value="<?php echo esc_attr( (string) $cfg['order'] ); ?>" />
								</label>
							</td>
						</tr>
					<?php endforeach; ?>
				</table>

				<h2><?php esc_html_e( 'About EWO', 'ewo-2025' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="ewo_about_text"><?php esc_html_e( 'About text', 'ewo-2025' ); ?></label></th>
						<td><textarea id="ewo_about_text" name="about_text" rows="4" class="large-text"><?php echo esc_textarea( $s['about_text'] ); ?></textarea></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Tagline', 'ewo-2025' ); ?></th>
						<td>
							<label><input type="checkbox" name="tagline_enabled" value="1" <?php checked( ! empty( $s['tagline_enabled'] ) ); ?> /> <?php esc_html_e( 'Show tagline', 'ewo-2025' ); ?></label><br />
							<input type="text" name="tagline_text" class="regular-text" value="<?php echo esc_attr( $s['tagline_text'] ); ?>" />
						</td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'EWO Stats', 'ewo-2025' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Leave blank to show the live published count automatically.', 'ewo-2025' ); ?></p>
				<table class="form-table" role="presentation">
					<?php foreach ( array( 'videos' => __( 'Videos count', 'ewo-2025' ), 'playlists' => __( 'Playlists count', 'ewo-2025' ), 'community' => __( 'Community count', 'ewo-2025' ) ) as $key => $label ) : ?>
						<tr>
							<th scope="row"><label for="ewo_stat_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
							<td><input type="text" id="ewo_stat_<?php echo esc_attr( $key ); ?>" name="stats[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $s['stats'][ $key ] ); ?>" placeholder="<?php echo esc_attr( ewo_2025_sidebar_stat_value( $key ) ); ?>" /></td>
						</tr>
					<?php endforeach; ?>
				</table>


				<?php submit_button( __( 'Save Sidebar Settings', 'ewo-2025' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Persist the settings.
	 */
	public function save() {
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'ewo-2025' ) );
		}
		check_admin_referer( self::NONCE );

		$defaults = ewo_2025_sidebar_defaults();

		$out                    = array();
		$out['about_text']      = isset( $_POST['about_text'] ) ? sanitize_textarea_field( wp_unslash( $_POST['about_text'] ) ) : '';
		$out['tagline_text']    = isset( $_POST['tagline_text'] ) ? sanitize_text_field( wp_unslash( $_POST['tagline_text'] ) ) : '';
		$out['tagline_enabled'] = isset( $_POST['tagline_enabled'] ) ? 1 : 0;

		$out['stats'] = array();
		foreach ( array_keys( $defaults['stats'] ) as $key ) {
			$out['stats'][ $key ] = isset( $_POST['stats'][ $key ] ) ? sanitize_text_field( wp_unslash( $_POST['stats'][ $key ] ) ) : '';
		}

		$out['sections'] = array();
		foreach ( array_keys( $defaults['sections'] ) as $id ) {
			$out['sections'][ $id ] = array(
				'enabled' => isset( $_POST['sections'][ $id ]['enabled'] ) ? 1 : 0,
				'order'   => isset( $_POST['sections'][ $id ]['order'] ) ? max( 1, min( 99, absint( wp_unslash( $_POST['sections'][ $id ]['order'] ) ) ) ) : 99,
			);
		}

		update_option( EWO_2025_SIDEBAR_OPTION, $out );


		wp_safe_redirect( add_query_arg( 'updated', '1', admin_url( 'themes.php?page=' . self::SLUG ) ) );
		exit;
	}
}

add_action(
	'after_setup_theme',
	static function () {
		( new EWO_2025_Sidebar_Settings() )->init();
	}
);
