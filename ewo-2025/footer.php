<?php
/**
 * The footer for EWO 2025.
 *
 * @package EWO_2025
 */
$ewo_2025_newsletter_url   = ewo_2025_get_platform_url( 'newsletter' );
$ewo_2025_footer_platforms = array(
	'youtube'     => array(
		'name'  => __( 'YouTube', 'ewo-2025' ),
		'label' => __( 'Watch & Subscribe', 'ewo-2025' ),
		'icon'  => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.96-1.96C18.85 4 12 4 12 4s-6.85 0-8.58.46a2.78 2.78 0 0 0-1.96 1.96A29.1 29.1 0 0 0 1 12a29.1 29.1 0 0 0 .46 5.58 2.78 2.78 0 0 0 1.96 1.96C5.15 20 12 20 12 20s6.85 0 8.58-.46a2.78 2.78 0 0 0 1.96-1.96A29.1 29.1 0 0 0 23 12a29.1 29.1 0 0 0-.46-5.58Z"/><path class="ewo-footer-platform__play" d="m10 15 5.2-3L10 9v6Z"/></svg>',
		'class' => 'youtube',
	),
	'substack'    => array(
		'name'  => __( 'Substack', 'ewo-2025' ),
		'label' => __( 'Read Analysis', 'ewo-2025' ),
		'icon'  => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 3h16v2.6H4V3Zm0 4.4h16V10H4V7.4Zm0 4.4h16V21l-8-4.4L4 21v-9.2Z"/></svg>',
		'class' => 'substack',
	),
	'spotify'     => array(
		'name'  => __( 'Spotify', 'ewo-2025' ),
		'label' => __( 'Listen to Podcasts', 'ewo-2025' ),
		'icon'  => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm4.58 14.43a.76.76 0 0 1-1.04.25c-2.85-1.74-6.44-2.13-10.66-1.17a.76.76 0 0 1-.34-1.49c4.62-1.05 8.6-.6 11.79 1.35.36.22.47.7.25 1.06Zm1.22-2.72a.96.96 0 0 1-1.32.32c-3.26-2-8.24-2.58-12.1-1.41a.96.96 0 1 1-.56-1.84c4.42-1.34 9.91-.69 13.66 1.61.45.28.6.87.32 1.32Zm.1-2.84C14 8.56 7.58 8.35 3.86 9.48a1.15 1.15 0 1 1-.67-2.2c4.27-1.3 11.38-1.05 15.88 1.62a1.15 1.15 0 0 1-1.17 1.97Z"/></svg>',
		'class' => 'spotify',
	),
	'tiktok'      => array(
		'name'  => __( 'TikTok', 'ewo-2025' ),
		'label' => __( 'Watch Clips', 'ewo-2025' ),
		'icon'  => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15.5 3c.42 3 2.07 4.8 5 5.02v3.2a8.38 8.38 0 0 1-4.92-1.57v6.2c0 3.15-2.04 5.15-5.14 5.15A5.18 5.18 0 0 1 5 15.77c0-3.4 2.93-5.88 6.27-5.2v3.36c-1.55-.49-2.85.36-2.85 1.8 0 1.2.82 2.04 1.98 2.04 1.2 0 1.9-.72 1.9-2.2V3h3.2Z"/></svg>',
		'class' => 'tiktok',
	),
	'x'           => array(
		'name'  => __( 'X / Twitter', 'ewo-2025' ),
		'label' => __( 'Latest Updates', 'ewo-2025' ),
		'icon'  => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M17.58 3h3.05l-6.66 7.62L21.8 21h-6.13l-4.8-6.28L5.38 21H2.31l7.13-8.15L1.93 3h6.28l4.34 5.74L17.58 3Zm-1.07 16.17h1.69L7.29 4.73H5.48l11.03 14.44Z"/></svg>',
		'class' => 'x',
	),
	'amazon_book' => array(
		'name'  => __( 'Amazon Book', 'ewo-2025' ),
		'label' => __( 'Our Book', 'ewo-2025' ),
		'icon'  => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4.2C5 3.54 5.54 3 6.2 3H20v15.8H6.45A1.45 1.45 0 0 0 5 20.25V4.2Zm2.3 1.3v10.9H18V5.5H7.3ZM4 20.25A2.75 2.75 0 0 1 6.75 17.5H20V21H6.75A2.75 2.75 0 0 1 4 20.25Z"/></svg>',
		'class' => 'amazon',
	),
);
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
					<?php foreach ( $ewo_2025_footer_platforms as $ewo_2025_key => $ewo_2025_platform ) : ?>
						<?php
						$ewo_2025_url = ewo_2025_get_platform_url( $ewo_2025_key );
						$tag          = '' !== $ewo_2025_url ? 'a' : 'span';
						?>
						<<?php echo esc_html( $tag ); ?>
							class="ewo-footer-platform ewo-footer-platform--<?php echo esc_attr( $ewo_2025_platform['class'] ); ?><?php echo '' === $ewo_2025_url ? ' ewo-footer-platform--disabled' : ''; ?>"
							<?php if ( '' !== $ewo_2025_url ) : ?>
								href="<?php echo esc_url( $ewo_2025_url ); ?>" target="_blank" rel="noopener noreferrer"
							<?php else : ?>
								aria-disabled="true"
							<?php endif; ?>
						>
							<span class="ewo-footer-platform__icon"><?php echo $ewo_2025_platform['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<span class="ewo-footer-platform__text">
								<span class="ewo-footer-platform__name"><?php echo esc_html( $ewo_2025_platform['name'] ); ?></span>
								<span class="ewo-footer-platform__label"><?php echo esc_html( $ewo_2025_platform['label'] ); ?></span>
							</span>
						</<?php echo esc_html( $tag ); ?>>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	</footer>
</div>

<?php wp_footer(); ?>
</body>
</html>