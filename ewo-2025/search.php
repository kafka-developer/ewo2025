<?php
/**
 * The template for displaying search results.
 *
 * @package EWO_2025
 */

get_header();
?>

<main id="primary" class="site-main">
	<div class="content-area">
		<header class="page-header">
			<h1 class="page-title">
				<?php
				printf(
					/* translators: %s: Search query. */
					esc_html__( 'Search results for: %s', 'ewo-2025' ),
					'<span>' . esc_html( get_search_query() ) . '</span>'
				);
				?>
			</h1>
		</header>

		<?php
		if ( have_posts() ) :
			while ( have_posts() ) :
				the_post();
				get_template_part( 'template-parts/content', get_post_type() );
			endwhile;

			the_posts_navigation();
		else :
			get_template_part( 'template-parts/content', 'none' );
		endif;
		?>
	</div>
</main>

<?php
get_footer();
