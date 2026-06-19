<?php
/**
 * The template for displaying single posts.
 *
 * @package EWO_2025
 */

get_header();
?>

<main id="primary" class="site-main">
	<div class="content-area">
		<?php
		while ( have_posts() ) :
			the_post();
			get_template_part( 'template-parts/content', get_post_type() );

			$ewo_current_id = get_the_ID();
			$ewo_prev       = get_previous_post( true );
			$ewo_next       = get_next_post( true );

			if ( $ewo_prev || $ewo_next ) :
				?>
				<nav class="ewo-post-nav" aria-label="<?php esc_attr_e( 'Analysis post navigation', 'ewo-2025' ); ?>">
					<?php if ( $ewo_prev ) : ?>
						<div class="ewo-post-nav__item ewo-post-nav__item--prev">
							<span class="ewo-post-nav__label"><?php esc_html_e( 'Previous Analysis', 'ewo-2025' ); ?></span>
							<?php
							$GLOBALS['post'] = $ewo_prev; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
							setup_postdata( $ewo_prev );
							get_template_part( 'template-parts/content', 'card' );
							wp_reset_postdata();
							?>
						</div>
					<?php endif; ?>
					<?php if ( $ewo_next ) : ?>
						<div class="ewo-post-nav__item ewo-post-nav__item--next">
							<span class="ewo-post-nav__label"><?php esc_html_e( 'Next Analysis', 'ewo-2025' ); ?></span>
							<?php
							$GLOBALS['post'] = $ewo_next; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
							setup_postdata( $ewo_next );
							get_template_part( 'template-parts/content', 'card' );
							wp_reset_postdata();
							?>
						</div>
					<?php endif; ?>
				</nav>
				<?php
			endif;

			$ewo_related = new WP_Query(
				array(
					'cat'                 => 4,
					'post_status'         => 'publish',
					'posts_per_page'      => 4,
					'post__not_in'        => array( $ewo_current_id ),
					'orderby'             => 'date',
					'order'               => 'DESC',
					'ignore_sticky_posts' => true,
				)
			);

			if ( $ewo_related->have_posts() ) :
				?>
				<section class="ewo-related" aria-label="<?php esc_attr_e( 'Related Analysis', 'ewo-2025' ); ?>">
					<h2 class="ewo-related__title"><?php esc_html_e( 'Related Analysis', 'ewo-2025' ); ?></h2>
					<div class="ewo-related__grid">
						<?php
						while ( $ewo_related->have_posts() ) :
							$ewo_related->the_post();
							get_template_part( 'template-parts/content', 'card' );
						endwhile;
						?>
					</div>
				</section>
				<?php
				wp_reset_postdata();
			endif;
		endwhile;
		?>
	</div>
</main>

<?php
get_footer();
