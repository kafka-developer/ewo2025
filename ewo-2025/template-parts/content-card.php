<?php
/**
 * Card layout for a post in listings (excerpt only — never full content).
 *
 * @package EWO_2025
 */

$ewo_card_thumb      = ewo_2025_card_thumbnail_url( get_post() );
$ewo_subscriber_only = ewo_2025_is_subscriber_only_post( get_post() );
$ewo_substack_url    = ewo_2025_substack_source_url( get_post() );
$ewo_excerpt         = $ewo_subscriber_only ? ewo_2025_subscriber_preview_text( get_post() ) : get_the_excerpt();
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'entry' ); ?>>
	<?php if ( has_post_thumbnail() ) : ?>
		<?php the_post_thumbnail( 'large' ); ?>
	<?php elseif ( $ewo_card_thumb ) : ?>
		<img class="wp-post-image" src="<?php echo esc_url( $ewo_card_thumb ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" loading="lazy">
	<?php endif; ?>

	<header class="entry-header">
		<?php if ( $ewo_subscriber_only ) : ?>
			<span class="ewo-subscriber-badge"><?php esc_html_e( '🔒 Subscriber Only', 'ewo-2025' ); ?></span>
		<?php endif; ?>
		<h2 class="entry-title"><a href="<?php echo esc_url( get_permalink() ); ?>" rel="bookmark"><?php the_title(); ?></a></h2>
		<div class="entry-meta">
			<?php echo esc_html( get_the_date() ); ?>
		</div>
	</header>

	<div class="entry-content">
		<p class="entry-summary"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( $ewo_excerpt ), 30, '…' ) ); ?></p>
		<p class="entry-actions">
			<?php if ( $ewo_subscriber_only && $ewo_substack_url ) : ?>
				<a class="ewo-button ewo-button--gold ewo-substack-button" href="<?php echo esc_url( $ewo_substack_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Read on Substack', 'ewo-2025' ); ?> &rarr;</a>
			<?php else : ?>
				<a class="ewo-button ewo-button--gold" href="<?php the_permalink(); ?>"><?php esc_html_e( 'Read Analysis', 'ewo-2025' ); ?> &rarr;</a>
			<?php endif; ?>
		</p>
	</div>
</article>
