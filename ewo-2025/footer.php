<?php
/**
 * The footer for EWO 2025.
 *
 * @package EWO_2025
 */
$ewo_2025_newsletter_url   = ewo_2025_get_platform_url( 'newsletter' );
?>
	<footer id="colophon" class="site-footer">
		<div class="site-footer__inner site-footer__newsletter" id="newsletter">
			<div class="site-footer__newsletter-copy">
				<p class="site-footer__newsletter-kicker"><?php esc_html_e( 'Dispatch Channel', 'ewo-2025' ); ?></p>
				<p class="site-footer__newsletter-title"><?php esc_html_e( 'Receive the EWO Intelligence Briefing', 'ewo-2025' ); ?></p>
				<p class="site-footer__newsletter-text"><?php esc_html_e( 'A concise digest on geopolitics, markets, conflict, and strategic technology.', 'ewo-2025' ); ?></p>
			</div>
			<form class="ewo-newsletter__form" action="<?php echo esc_url( $ewo_2025_newsletter_url ? $ewo_2025_newsletter_url : '#' ); ?>" method="post"<?php echo $ewo_2025_newsletter_url ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>>
				<label class="screen-reader-text" for="ewo-footer-newsletter-email"><?php esc_html_e( 'Email address', 'ewo-2025' ); ?></label>
				<input id="ewo-footer-newsletter-email" type="email" placeholder="<?php esc_attr_e( 'Email address', 'ewo-2025' ); ?>">
				<button class="ewo-button ewo-button--gold" type="submit"><?php esc_html_e( 'Subscribe', 'ewo-2025' ); ?></button>
			</form>
		</div>

		<div class="site-footer__inner site-footer__inner--platforms">
			<div class="site-footer__brand">
				<a class="site-footer__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php esc_attr_e( 'Emerging World Order home', 'ewo-2025' ); ?>">
					<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/ewo-logo.png' ); ?>" alt="<?php esc_attr_e( 'Emerging World Order', 'ewo-2025' ); ?>">
				</a>
				<p class="site-footer__name"><?php esc_html_e( 'Emerging World Order', 'ewo-2025' ); ?></p>
				<p class="site-footer__tagline"><?php esc_html_e( 'Strategic intelligence on power, markets, and geopolitics.', 'ewo-2025' ); ?></p>
				<?php ewo_2025_render_footer_icons(); ?>
			</div>

			<div class="site-footer__divider" aria-hidden="true"></div>

			<div class="site-footer__platforms" aria-label="<?php esc_attr_e( 'Follow EWO across platforms', 'ewo-2025' ); ?>">
				<p class="site-footer__platforms-kicker"><?php esc_html_e( 'Follow EWO Across Platforms', 'ewo-2025' ); ?></p>
				<p class="site-footer__platforms-subtitle"><?php esc_html_e( 'Stay connected across all our platforms and never miss an update.', 'ewo-2025' ); ?></p>
				<div class="site-footer__platform-grid">
					<?php ewo_2025_render_footer_platform_cards(); ?>
				</div>
			</div>
		</div>

		<div class="site-footer__inner site-footer__legal">
			<p class="site-footer__copyright">
				<?php
				printf(
					/* translators: %s: Site name. */
					esc_html__( '&copy; %s. All rights reserved.', 'ewo-2025' ),
					esc_html( get_bloginfo( 'name' ) )
				);
				?>
			</p>
			<nav class="site-footer__legal-links" aria-label="<?php esc_attr_e( 'Legal', 'ewo-2025' ); ?>">
				<?php
				$ewo_2025_legal_links = array();
				if ( get_privacy_policy_url() ) {
					$ewo_2025_legal_links[] = array(
						'url'   => get_privacy_policy_url(),
						'label' => __( 'Privacy Policy', 'ewo-2025' ),
					);
				}
				$ewo_2025_terms_page = get_page_by_path( 'terms' );
				if ( $ewo_2025_terms_page ) {
					$ewo_2025_legal_links[] = array(
						'url'   => get_permalink( $ewo_2025_terms_page ),
						'label' => __( 'Terms', 'ewo-2025' ),
					);
				}
				$ewo_2025_contact_email = ewo_2025_get_platform_url( 'email' );
				if ( $ewo_2025_contact_email ) {
					$ewo_2025_legal_links[] = array(
						'url'   => $ewo_2025_contact_email,
						'label' => __( 'Contact', 'ewo-2025' ),
					);
				}
				foreach ( $ewo_2025_legal_links as $ewo_2025_legal_link ) :
					?>
					<a class="site-footer__legal-link" href="<?php echo esc_url( $ewo_2025_legal_link['url'] ); ?>"><?php echo esc_html( $ewo_2025_legal_link['label'] ); ?></a>
					<?php
				endforeach;
				?>
			</nav>
		</div>
	</footer>
</div>

<?php wp_footer(); ?>
</body>
</html>
