<?php
/**
 * The footer for EWO 2025.
 *
 * Copyright  — WordPress site title (Settings > General).
 * Contact    — email platform from EWO Settings (shown only when enabled + footer=true).
 * Social     — footer_icon-surface platforms from EWO Settings (enabled + footer=true + icon_row),
 *              email excluded here since it is rendered as the Contact link.
 *
 * @package EWO_2025
 */

// Contact link — email platform, respects enabled + footer flags.
$ewo_2025_email_cfg = ewo_2025_get_platform_settings( 'email' );
$ewo_2025_contact   = ( $ewo_2025_email_cfg && $ewo_2025_email_cfg['enabled'] && $ewo_2025_email_cfg['footer'] )
	? $ewo_2025_email_cfg['url'] : '';

// Social icons — all footer-enabled platforms from EWO Settings, email excluded (shown as Contact above).
$ewo_2025_social_icons = array_values( array_filter(
	ewo_2025_get_platform_surface_links( 'footer' ),
	static function ( $r ) { return 'email' !== $r['key']; }
) );
$ewo_2025_svg_allowed = ewo_2025_social_icon_kses();
?>
	<footer id="colophon" class="site-footer">
		<div class="site-footer__container site-footer__legal">

			<p class="site-footer__copyright">
				<?php
				printf(
					/* translators: %s: Site name. */
					esc_html__( '&copy; %s. All rights reserved.', 'ewo-2025' ),
					esc_html( get_bloginfo( 'name' ) )
				);
				?>
			</p>

			<?php if ( $ewo_2025_contact ) : ?>
			<a class="site-footer__legal-link site-footer__legal-contact"
				href="<?php echo esc_url( $ewo_2025_contact ); ?>">
				<?php esc_html_e( 'Contact', 'ewo-2025' ); ?>
			</a>
			<?php endif; ?>

			<?php if ( ! empty( $ewo_2025_social_icons ) ) : ?>
			<div class="site-footer__legal-icons" aria-label="<?php esc_attr_e( 'EWO social links', 'ewo-2025' ); ?>">
				<?php foreach ( $ewo_2025_social_icons as $ewo_2025_row ) : ?>
				<a class="ewo-footer-icon ewo-footer-icon--<?php echo esc_attr( $ewo_2025_row['class'] ); ?>"
					href="<?php echo esc_url( $ewo_2025_row['url'] ); ?>"
					<?php echo ewo_2025_link_target_attr( $ewo_2025_row['opens_in_new_tab'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static literal. ?>
					aria-label="<?php echo esc_attr( $ewo_2025_row['platform_name'] ); ?>">
					<span class="ewo-footer-icon__glyph"><?php echo wp_kses( $ewo_2025_row['icon'], $ewo_2025_svg_allowed ); ?></span>
				</a>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>

		</div>
	</footer>
</div><!-- #page -->

<?php wp_footer(); ?>
</body>
</html>
