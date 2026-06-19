<?php
/**
 * Template for the "Analysis" page (slug: analysis).
 *
 * Renders the Analysis category (ID 4) posts as a compact 2-column editorial
 * grid with pagination, instead of the page's own (blank) content.
 *
 * @package EWO_2025
 */

get_header();

$ewo_paged = (int) ( get_query_var( 'paged' ) ? get_query_var( 'paged' ) : get_query_var( 'page' ) );
if ( $ewo_paged < 1 && isset( $_GET['paged'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$ewo_paged = absint( wp_unslash( $_GET['paged'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
}
$ewo_paged = max( 1, $ewo_paged );

$ewo_analysis = new WP_Query(
	array(
		'cat'                 => 4,
		'post_status'         => 'publish',
		'posts_per_page'      => 12,
		'paged'               => $ewo_paged,
		'ignore_sticky_posts' => true,
	)
);
?>

<main id="primary" class="site-main">
	<header class="page-header">
		<h1 class="page-title"><?php esc_html_e( 'Latest Analysis', 'ewo-2025' ); ?></h1>
	</header>

	<?php if ( $ewo_analysis->have_posts() ) : ?>
		<div class="ewo-article-grid">
			<?php
			while ( $ewo_analysis->have_posts() ) :
				$ewo_analysis->the_post();
				get_template_part( 'template-parts/content', 'article-card' );
			endwhile;
			?>
		</div>

		<?php
		$ewo_total_pages = (int) $ewo_analysis->max_num_pages;
		if ( $ewo_total_pages > 1 ) :
			$ewo_links = paginate_links(
				array(
					'base'      => add_query_arg( 'paged', '%#%' ),
					'format'    => '',
					'current'   => $ewo_paged,
					'total'     => $ewo_total_pages,
					'mid_size'  => 1,
					'prev_text' => esc_html__( '← Previous', 'ewo-2025' ),
					'next_text' => esc_html__( 'Next →', 'ewo-2025' ),
				)
			);

			if ( $ewo_links ) :
				?>
				<nav class="ewo-pagination" aria-label="<?php esc_attr_e( 'Analysis pagination', 'ewo-2025' ); ?>">
					<?php echo wp_kses_post( $ewo_links ); ?>
				</nav>
				<?php
			endif;
		endif;

		wp_reset_postdata();
	else :
		get_template_part( 'template-parts/content', 'none' );
	endif;
	?>
</main>

<?php
get_footer();
