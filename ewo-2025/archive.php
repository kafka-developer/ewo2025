<?php
/**
 * The template for displaying archives.
 *
 * @package EWO_2025
 */

get_header();
?>

<main id="primary" class="site-main">
	<div class="content-area">
		<header class="page-header">
			<?php
			the_archive_title( '<h1 class="page-title">', '</h1>' );
			the_archive_description( '<div class="archive-description">', '</div>' );
			?>
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
