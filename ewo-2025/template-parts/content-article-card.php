<?php
/**
 * Compact editorial article card (used on the Analysis grid).
 *
 * Thumbnail (16:9) on top, title, 1–2 line excerpt, date + author, and a
 * subtle "Read Analysis" cue. The whole card is clickable via a stretched link.
 *
 * @package EWO_2025
 */

$ewo_card_thumb      = ewo_2025_card_thumbnail_url( get_post() );
$ewo_author          = get_the_author();
$ewo_subscriber_only = ewo_2025_is_subscriber_only_post( get_post() );
$ewo_substack_url    = ewo_2025_substack_source_url( get_post() );
$ewo_excerpt         = $ewo_subscriber_only ? ewo_2025_subscriber_preview_text( get_post() ) : get_the_excerpt();
?>
<article class="ewo-article-card">
	<div class="ewo-article-card__media">
		<img src="<?php echo esc_url( $ewo_card_thumb ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" loading="lazy">
	</div>
	<div class="ewo-article-card__body">
		<?php if ( $ewo_subscriber_only ) : ?>
			<span class="ewo-subscriber-badge"><?php esc_html_e( '🔒 Subscriber Only', 'ewo-2025' ); ?></span>
		<?php endif; ?>
		<h2 class="ewo-article-card__title">
			<a class="ewo-article-card__link" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
		</h2>
		<p class="ewo-article-card__excerpt"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( $ewo_excerpt ), 22, '…' ) ); ?></p>
		<div class="ewo-article-card__meta">
			<?php echo esc_html( get_the_date() ); ?>
			<?php if ( $ewo_author ) : ?>
				<span class="ewo-article-card__sep" aria-hidden="true">·</span> <?php echo esc_html( $ewo_author ); ?>
			<?php endif; ?>
		</div>
		<?php if ( $ewo_subscriber_only && $ewo_substack_url ) : ?>
			<a class="ewo-article-card__more ewo-substack-button" href="<?php echo esc_url( $ewo_substack_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Read on Substack', 'ewo-2025' ); ?> &rarr;</a>
		<?php else : ?>
			<span class="ewo-article-card__more" aria-hidden="true"><?php esc_html_e( 'Read Analysis', 'ewo-2025' ); ?> &rarr;</span>
		<?php endif; ?>
	</div>
</article>
