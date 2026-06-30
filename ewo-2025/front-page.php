<?php
/**
 * Front page template — dynamic section rendering via Section Order Manager.
 *
 * Sections are rendered in the order configured in EWO Settings → Section Order.
 * Feature-visibility checks are performed here before calling each render function.
 *
 * @package EWO_2025
 */

get_header();
$ewo_so_order = ewo_2025_so_get_order();
$ewo_so_reg   = ewo_2025_so_registry();
?>
<!-- EWO Theme v<?php echo esc_html( EWO_THEME_VERSION ); ?> | Build <?php echo esc_html( EWO_THEME_BUILD ); ?> -->
<main id="primary" class="site-main site-main--home">

<?php
// TOP sections (hero, etc.) — rendered above the sidebar layout.
foreach ( $ewo_so_order['top'] as $key ) :
	if ( ! isset( $ewo_so_reg[ $key ] ) ) {
		continue;
	}
	$ewo_so_s = $ewo_so_reg[ $key ];
	if ( ! empty( $ewo_so_s['feature_key'] ) && ! ewo_2025_feature_enabled( $ewo_so_s['feature_key'] ) ) {
		continue;
	}
	call_user_func( $ewo_so_s['render_cb'] );
endforeach;
?>

<div class="ewo-home-layout">
	<div class="ewo-home-main">
<?php
// MAIN COLUMN sections — rendered inside the sidebar layout.
foreach ( $ewo_so_order['main'] as $key ) :
	if ( ! isset( $ewo_so_reg[ $key ] ) ) {
		continue;
	}
	$ewo_so_s = $ewo_so_reg[ $key ];
	if ( ! empty( $ewo_so_s['feature_key'] ) && ! ewo_2025_feature_enabled( $ewo_so_s['feature_key'] ) ) {
		continue;
	}
	call_user_func( $ewo_so_s['render_cb'] );
endforeach;
?>
	</div><!-- .ewo-home-main -->
	<?php ewo_2025_sidebar(); ?>
</div><!-- .ewo-home-layout -->

<?php
// BOTTOM sections — rendered full-width below the sidebar layout.
foreach ( $ewo_so_order['bottom'] as $key ) :
	if ( ! isset( $ewo_so_reg[ $key ] ) ) {
		continue;
	}
	$ewo_so_s = $ewo_so_reg[ $key ];
	if ( ! empty( $ewo_so_s['feature_key'] ) && ! ewo_2025_feature_enabled( $ewo_so_s['feature_key'] ) ) {
		continue;
	}
	call_user_func( $ewo_so_s['render_cb'] );
endforeach;
?>

</main>

<?php get_footer();
