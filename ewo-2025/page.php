<?php
/**
 * The template for displaying pages.
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
			get_template_part( 'template-parts/content', 'page' );
		endwhile;
		?>
	</div>
</main>

<?php
get_footer();
