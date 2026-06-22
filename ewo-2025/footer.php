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
			<div class="site-footer__identity">
				<a class="site-footer__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php esc_attr_e( 'Emerging World Order home', 'ewo-2025' ); ?>">
					<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/ewo-logo.png' ); ?>" alt="<?php esc_attr_e( 'Emerging World Order', 'ewo-2025' ); ?>">
				</a>
				<div>
					<p class="site-footer__name"><?php esc_html_e( 'Emerging World Order', 'ewo-2025' ); ?></p>
					<p class="site-footer__tagline"><?php esc_html_e( 'Strategic intelligence on power, markets, and geopolitics.', 'ewo-2025' ); ?></p>
					<p class="site-footer__copyright">
						<?php
						printf(
							/* translators: %s: Site name. */
							esc_html__( '&copy; %s. All rights reserved.', 'ewo-2025' ),
							esc_html( get_bloginfo( 'name' ) )
						);
						?>
					</p>
				</div>
			</div>

			<div class="site-footer__platforms" aria-label="<?php esc_attr_e( 'EWO platform links', 'ewo-2025' ); ?>">
				<p class="site-footer__platforms-kicker"><?php esc_html_e( 'Follow EWO Across Platforms', 'ewo-2025' ); ?></p>
				<div class="site-footer__platform-grid">
					<?php ewo_2025_social_links( array( 'youtube', 'substack', 'spotify', 'x', 'rumble', 'amazon_book' ), 'footer' ); ?>
				</div>
			</div>
		</div>
	</footer>
</div>

<?php wp_footer(); ?>
</body>
</html>