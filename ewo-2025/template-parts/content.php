<?php
/**
 * Template part for displaying posts.
 *
 * @package EWO_2025
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'entry' ); ?>>
	<?php if ( has_post_thumbnail() ) : ?>
		<?php the_post_thumbnail( 'large' ); ?>
	<?php endif; ?>

	<header class="entry-header">
		<?php
		if ( is_singular() ) :
			the_title( '<h1 class="entry-title">', '</h1>' );
		else :
			the_title( '<h2 class="entry-title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a></h2>' );
		endif;
		?>
		<div class="entry-meta">
			<?php echo esc_html( get_the_date() ); ?>
		</div>
	</header>

	<div class="entry-content">
		<?php
		if ( is_singular() ) {
			the_content();
		} else {
			the_excerpt();
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
