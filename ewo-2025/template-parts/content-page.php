<?php
/**
 * Template part for displaying page content.
 *
 * @package EWO_2025
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'entry' ); ?>>
	<?php if ( has_post_thumbnail() ) : ?>
		<?php the_post_thumbnail( 'large' ); ?>
	<?php endif; ?>

	<header class="entry-header">
		<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
	</header>

	<div class="entry-content">
		<?php
		the_content();

		wp_link_pages(
			array(
				'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'ewo-2025' ),
				'after'  => '</div>',
			)
		);
		?>
	</div>
</article>
