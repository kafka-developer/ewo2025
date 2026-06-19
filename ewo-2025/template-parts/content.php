<?php
/**
 * Template part for displaying posts.
 *
 * Listing/archive context delegates to the card part (excerpt only); single
 * posts render the full content.
 *
 * @package EWO_2025
 */

if ( ! is_singular() ) {
	get_template_part( 'template-parts/content', 'card' );
	return;
}

$ewo_subscriber_only = ewo_2025_is_subscriber_only_post( get_post() );
$ewo_substack_url    = ewo_2025_substack_source_url( get_post() );
$ewo_preview_text    = ewo_2025_subscriber_preview_text( get_post() );
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'entry' ); ?>>
	<?php /* No hero/featured image on single posts: Feedzy embeds the image in post_content, so showing it here would duplicate it. */ ?>
	<header class="entry-header">
		<?php if ( $ewo_subscriber_only ) : ?>
			<span class="ewo-subscriber-badge"><?php esc_html_e( '🔒 Subscriber Only', 'ewo-2025' ); ?></span>
		<?php endif; ?>
		<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
		<div class="entry-meta">
			<?php echo esc_html( get_the_date() ); ?>
		</div>
	</header>

	<div class="entry-content">
		<?php
		if ( $ewo_subscriber_only && $ewo_substack_url ) {
			if ( $ewo_preview_text ) {
				echo wp_kses_post( wpautop( esc_html( $ewo_preview_text ) ) );
			}
			printf(
				'<p class="entry-actions"><a class="ewo-button ewo-button--gold ewo-substack-button" href="%1$s" target="_blank" rel="noopener noreferrer">%2$s &rarr;</a></p>',
				esc_url( $ewo_substack_url ),
				esc_html__( 'Read on Substack', 'ewo-2025' )
			);
		} else {
			the_content();
		}

		wp_link_pages(
			array(
				'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'ewo-2025' ),
				'after'  => '</div>',
			)
		);
		?>
	</div>
</article>
