<?php
/**
 * Template part for displaying a message when posts are not found.
 *
 * @package EWO_2025
 */
?>

<section class="entry no-results not-found">
	<header class="entry-header">
		<h1 class="entry-title"><?php esc_html_e( 'Nothing found', 'ewo-2025' ); ?></h1>
	</header>

	<div class="entry-content">
		<?php if ( is_search() ) : ?>
			<p><?php esc_html_e( 'No results matched your search. Try another query.', 'ewo-2025' ); ?></p>
			<?php get_search_form(); ?>
		<?php else : ?>
			<p><?php esc_html_e( 'Ready to publish your first post.', 'ewo-2025' ); ?></p>
		<?php endif; ?>
	</div>
</section>
