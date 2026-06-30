<?php
/**
 * Template for the public Book page (/book/).
 *
 * Pulls all content from the Book Settings admin (EWO Settings → Book).
 * Shows an admin-only placeholder when settings are empty.
 *
 * @package EWO_2025
 */

get_header();

defined( 'EWO_2025_BOOK_OPTION' ) || define( 'EWO_2025_BOOK_OPTION', 'ewo_2025_book_settings' );

if ( ! function_exists( 'ewo_2025_get_book_settings' ) ) {
	function ewo_2025_get_book_settings() {
		$s = get_option( EWO_2025_BOOK_OPTION, array() );
		if ( ! is_array( $s ) ) { $s = array(); }
		$defaults = array(
			'title' => '', 'subtitle' => '', 'author' => '',
			'cover_image_id' => 0, 'cover_image_url' => '',
			'description' => '', 'highlights' => array(),
			'quote' => '', 'quote_attribution' => '',
			'amazon_url' => '', 'button_text' => 'Buy on Amazon',
			'show_cover' => 1, 'show_highlights' => 1, 'show_quote' => 1,
		);
		$merged = array_merge( $defaults, $s );
		if ( ! is_array( $merged['highlights'] ) ) { $merged['highlights'] = array(); }
		return $merged;
	}
}

$bk = ewo_2025_get_book_settings();

// Resolve cover image URL (attachment ID beats raw URL when available).
$bk_cover_url = '';
if ( ! empty( $bk['cover_image_id'] ) ) {
	$bk_cover_url = wp_get_attachment_image_url( (int) $bk['cover_image_id'], 'large' ) ?: '';
}
if ( '' === $bk_cover_url && '' !== $bk['cover_image_url'] ) {
	$bk_cover_url = $bk['cover_image_url'];
}

$bk_has_content  = '' !== $bk['title'] || '' !== $bk['description'] || '' !== $bk['amazon_url'];
$bk_admin        = current_user_can( 'manage_options' );
$bk_settings_url = admin_url( 'admin.php?page=ewo-settings-book' );
$bk_home         = home_url( '/' );
?>

<main id="primary" class="site-main ewo-book-pg-main">

<?php if ( ! $bk_has_content ) : ?>
	<div class="ewo-book-pg-wrap" style="padding-top:60px;padding-bottom:80px">
		<?php if ( $bk_admin ) : ?>
			<div class="ewo-book-pg-empty-admin">
				<div class="ewo-book-pg-empty-icon">&#128214;</div>
				<h1 class="ewo-book-pg-empty-title"><?php esc_html_e( 'Book page is not configured yet.', 'ewo-2025' ); ?></h1>
				<p class="ewo-book-pg-empty-sub"><?php esc_html_e( 'Add a title, description, cover image, and Amazon link in Book Settings to populate this page.', 'ewo-2025' ); ?></p>
				<a href="<?php echo esc_url( $bk_settings_url ); ?>" class="ewo-book-pg-cta">
					<?php esc_html_e( 'Configure Book Settings →', 'ewo-2025' ); ?>
				</a>
			</div>
		<?php else : ?>
			<p class="ewo-book-pg-coming"><?php esc_html_e( 'Coming soon.', 'ewo-2025' ); ?></p>
		<?php endif; ?>
	</div>

<?php else : ?>

	<div class="ewo-book-pg-header">
		<div class="ewo-book-pg-wrap">
			<a href="<?php echo esc_url( $bk_home ); ?>" class="ewo-book-pg-back">
				&#8592; <?php esc_html_e( 'Home', 'ewo-2025' ); ?>
			</a>
			<p class="ewo-book-pg-kicker"><?php esc_html_e( 'EWO — The Book', 'ewo-2025' ); ?></p>
		</div>
	</div>

	<div class="ewo-book-pg-body">
		<div class="ewo-book-pg-wrap">
			<div class="ewo-book-pg-hero">

				<?php if ( ! empty( $bk['show_cover'] ) ) : ?>
				<div class="ewo-book-pg-cover-col">
					<?php if ( '' !== $bk_cover_url ) : ?>
						<div class="ewo-book-pg-cover-frame">
							<img src="<?php echo esc_url( $bk_cover_url ); ?>"
								alt="<?php echo esc_attr( $bk['title'] ?: __( 'Book cover', 'ewo-2025' ) ); ?>"
								class="ewo-book-pg-cover-img">
						</div>
					<?php else : ?>
						<div class="ewo-book-pg-cover-frame ewo-book-pg-cover-placeholder" aria-hidden="true">
							<span class="ewo-book-pg-cover-pl-kicker"><?php esc_html_e( 'Emerging World Order', 'ewo-2025' ); ?></span>
							<span class="ewo-book-pg-cover-pl-title"><?php echo esc_html( $bk['title'] ?: 'EWO 2025' ); ?></span>
							<span class="ewo-book-pg-cover-pl-year">2025</span>
						</div>
					<?php endif; ?>

					<?php if ( '' !== $bk['amazon_url'] ) : ?>
						<a href="<?php echo esc_url( $bk['amazon_url'] ); ?>"
							class="ewo-book-pg-cta ewo-book-pg-cta--cover"
							target="_blank" rel="noopener noreferrer">
							<?php echo esc_html( $bk['button_text'] ?: __( 'Buy on Amazon', 'ewo-2025' ) ); ?>
						</a>
					<?php endif; ?>
				</div>
				<?php endif; ?>

				<div class="ewo-book-pg-content-col">

					<?php if ( '' !== $bk['title'] ) : ?>
						<h1 class="ewo-book-pg-title"><?php echo esc_html( $bk['title'] ); ?></h1>
					<?php endif; ?>

					<?php if ( '' !== $bk['subtitle'] ) : ?>
						<p class="ewo-book-pg-subtitle"><?php echo esc_html( $bk['subtitle'] ); ?></p>
					<?php endif; ?>

					<?php if ( '' !== $bk['author'] ) : ?>
						<p class="ewo-book-pg-author">
							<?php esc_html_e( 'By', 'ewo-2025' ); ?>
							<strong><?php echo esc_html( $bk['author'] ); ?></strong>
						</p>
					<?php endif; ?>

					<?php if ( '' !== $bk['description'] ) : ?>
						<div class="ewo-book-pg-description">
							<?php echo wp_kses_post( wpautop( $bk['description'] ) ); ?>
						</div>
					<?php endif; ?>

					<?php if ( ! empty( $bk['show_highlights'] ) && ! empty( $bk['highlights'] ) ) : ?>
						<ul class="ewo-book-pg-highlights">
							<?php foreach ( $bk['highlights'] as $hl ) : ?>
								<li><?php echo esc_html( $hl ); ?></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>

					<?php if ( '' !== $bk['amazon_url'] ) : ?>
						<div class="ewo-book-pg-cta-row">
							<a href="<?php echo esc_url( $bk['amazon_url'] ); ?>"
								class="ewo-book-pg-cta"
								target="_blank" rel="noopener noreferrer">
								<?php echo esc_html( $bk['button_text'] ?: __( 'Buy on Amazon', 'ewo-2025' ) ); ?>
							</a>
						</div>
					<?php endif; ?>

				</div>
			</div>

			<?php if ( ! empty( $bk['show_quote'] ) && '' !== $bk['quote'] ) : ?>
				<div class="ewo-book-pg-quote-section">
					<blockquote class="ewo-book-pg-quote">
						<p><?php echo esc_html( $bk['quote'] ); ?></p>
						<?php if ( '' !== $bk['quote_attribution'] ) : ?>
							<cite class="ewo-book-pg-quote-attr"><?php echo esc_html( $bk['quote_attribution'] ); ?></cite>
						<?php endif; ?>
					</blockquote>
				</div>
			<?php endif; ?>

		</div>
	</div>

<?php endif; ?>

</main>

<?php get_footer(); ?>
