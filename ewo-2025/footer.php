<?php
/**
 * The footer for EWO 2025.
 *
 * @package EWO_2025
 */
?>
	<footer id="colophon" class="site-footer">
		<div class="site-footer__inner">
			<div>
				<p>
					<?php
					printf(
						/* translators: %s: Site name. */
						esc_html__( '&copy; %s. All rights reserved.', 'ewo-2025' ),
						esc_html( get_bloginfo( 'name' ) )
					);
					?>
				</p>
			</div>
			<?php ewo_2025_platform_links( array( 'youtube', 'spotify', 'substack', 'x', 'rumble', 'tiktok', 'amazon_book', 'newsletter' ), 'ewo-platform-links ewo-platform-links--footer' ); ?>
		</div>
	</footer>
</div>

<?php wp_footer(); ?>
</body>
</html>
