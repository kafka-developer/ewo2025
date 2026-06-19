<?php
/**
 * Template for the Videos page / EWO Media Hub.
 *
 * This page intentionally bypasses the normal page content renderer.
 *
 * @package EWO_2025
 */

get_header();

if ( ! function_exists( 'ewo_2025_media_is_hidden' ) ) {
	function ewo_2025_media_is_hidden( $post_id, $meta_key = 'ewo_youtube_hidden' ) {
		return (bool) get_post_meta( $post_id, $meta_key, true );
	}
}

if ( ! function_exists( 'ewo_2025_media_is_short' ) ) {
	function ewo_2025_media_is_short( $post_id ) {
		$type = strtolower( (string) get_post_meta( $post_id, 'ewo_youtube_video_type', true ) );

		if ( in_array( $type, array( 'short', 'shorts' ), true ) ) {
			return true;
		}

		foreach ( array( 'ewo_youtube_is_short', '_ewo_youtube_is_short' ) as $key ) {
			$value = strtolower( (string) get_post_meta( $post_id, $key, true ) );

			if ( in_array( $value, array( '1', 'yes', 'true' ), true ) ) {
				return true;
			}
		}

		return false;
	}
}

if ( ! function_exists( 'ewo_2025_media_video_url' ) ) {
	function ewo_2025_media_video_url( $post_id ) {
		foreach ( array( 'ewo_youtube_url', '_ewo_youtube_url', 'youtube_url', 'video_url' ) as $key ) {
			$url = get_post_meta( $post_id, $key, true );

			if ( $url ) {
				return esc_url_raw( $url );
			}
		}

		return '';
	}
}

if ( ! function_exists( 'ewo_2025_media_image_url_is_valid' ) ) {
	function ewo_2025_media_image_url_is_valid( $url ) {
		$url = trim( (string) $url );

		if ( '' === $url || 0 === stripos( $url, 'data:' ) ) {
			return false;
		}

		if ( ! preg_match( '#^https?://#i', $url ) ) {
			return false;
		}

		return (bool) preg_match( '#\.(jpe?g|png|gif|webp|avif)(\?.*)?$#i', $url )
			|| false !== stripos( $url, 'ytimg.com' )
			|| false !== stripos( $url, 'img.youtube.com' );
	}
}

if ( ! function_exists( 'ewo_2025_media_video_thumb' ) ) {
	function ewo_2025_media_video_thumb( $post ) {
		$featured = get_the_post_thumbnail_url( $post, 'large' );

		if ( $featured && ewo_2025_media_image_url_is_valid( $featured ) ) {
			return $featured;
		}

		$video_id = get_post_meta( $post->ID, 'ewo_youtube_video_id', true );

		if ( $video_id ) {
			return 'https://img.youtube.com/vi/' . rawurlencode( $video_id ) . '/hqdefault.jpg';
		}

		$stored = get_post_meta( $post->ID, 'ewo_youtube_thumbnail', true );

		return ewo_2025_media_image_url_is_valid( $stored ) ? esc_url_raw( $stored ) : '';
	}
}

if ( ! function_exists( 'ewo_2025_media_query_videos' ) ) {
	function ewo_2025_media_query_videos( $shorts = false, $limit = 5 ) {
		$query = new WP_Query(
			array(
				'post_type'           => 'ewo_video',
				'post_status'         => 'publish',
				'posts_per_page'      => 30,
				'orderby'             => 'date',
				'order'               => 'DESC',
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
			)
		);

		$items = array();

		foreach ( $query->posts as $post ) {
			if ( ewo_2025_media_is_hidden( $post->ID ) ) {
				continue;
			}

			if ( $shorts !== ewo_2025_media_is_short( $post->ID ) ) {
				continue;
			}

			$items[] = $post;

			if ( $limit === count( $items ) ) {
				break;
			}
		}

		return $items;
	}
}

if ( ! function_exists( 'ewo_2025_media_query_simple' ) ) {
	function ewo_2025_media_query_simple( $post_type, $limit = 5 ) {
		if ( ! post_type_exists( $post_type ) ) {
			return array();
		}

		$query = new WP_Query(
			array(
				'post_type'           => $post_type,
				'post_status'         => 'publish',
				'posts_per_page'      => $limit,
				'orderby'             => 'date',
				'order'               => 'DESC',
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
			)
		);

		return $query->posts;
	}
}

if ( ! function_exists( 'ewo_2025_media_query_tiktok' ) ) {
	function ewo_2025_media_query_tiktok() {
		foreach ( array( 'ewo_tiktok', 'ewo_tiktok_video', 'tiktok_video', 'tiktok' ) as $post_type ) {
			if ( post_type_exists( $post_type ) ) {
				return ewo_2025_media_query_simple( $post_type, 5 );
			}
		}

		return array();
	}
}

if ( ! function_exists( 'ewo_2025_media_meta_first' ) ) {
	function ewo_2025_media_meta_first( $post_id, $keys ) {
		foreach ( $keys as $key ) {
			$value = trim( (string) get_post_meta( $post_id, $key, true ) );

			if ( '' !== $value ) {
				return $value;
			}
		}

		return '';
	}
}

if ( ! function_exists( 'ewo_2025_media_render_video_card' ) ) {
	function ewo_2025_media_render_video_card( $post, $label = '' ) {
		$url   = ewo_2025_media_video_url( $post->ID );
		$thumb = ewo_2025_media_video_thumb( $post );
		$title = get_the_title( $post );
		?>
		<article class="ewo-media-card">
			<?php if ( $url ) : ?><a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer"><?php endif; ?>
				<?php if ( $thumb ) : ?>
					<img src="<?php echo esc_url( $thumb ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy">
				<?php else : ?>
					<span class="ewo-media-card__placeholder" aria-hidden="true"></span>
				<?php endif; ?>
			<?php if ( $url ) : ?></a><?php endif; ?>
			<div class="ewo-media-card__body">
				<?php if ( $label ) : ?><p class="ewo-media-card__label"><?php echo esc_html( $label ); ?></p><?php endif; ?>
				<h3 class="ewo-media-card__title">
					<?php if ( $url ) : ?>
						<a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $title ); ?></a>
					<?php else : ?>
						<?php echo esc_html( $title ); ?>
					<?php endif; ?>
				</h3>
			</div>
		</article>
		<?php
	}
}

if ( ! function_exists( 'ewo_2025_media_render_playlist_card' ) ) {
	function ewo_2025_media_render_playlist_card( $post ) {
		$title = ewo_2025_media_meta_first( $post->ID, array( 'ewo_youtube_playlist_title' ) );
		$title = $title ? $title : get_the_title( $post );
		$url   = get_post_meta( $post->ID, 'ewo_youtube_playlist_url', true );
		$thumb = get_post_meta( $post->ID, 'ewo_youtube_playlist_thumbnail', true );
		$count = ewo_2025_media_meta_first( $post->ID, array( 'ewo_youtube_playlist_video_count', 'ewo_youtube_video_count', 'video_count', 'item_count' ) );
		?>
		<article class="ewo-media-card">
			<?php if ( $url ) : ?><a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer"><?php endif; ?>
				<?php if ( ewo_2025_media_image_url_is_valid( $thumb ) ) : ?>
					<img src="<?php echo esc_url( $thumb ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy">
				<?php else : ?>
					<span class="ewo-media-card__placeholder" aria-hidden="true"></span>
				<?php endif; ?>
			<?php if ( $url ) : ?></a><?php endif; ?>
			<div class="ewo-media-card__body">
				<h3 class="ewo-media-card__title"><?php echo esc_html( $title ); ?></h3>
				<?php if ( '' !== $count ) : ?>
					<p class="ewo-media-card__meta"><?php echo esc_html( number_format_i18n( (int) $count ) ); ?> <?php esc_html_e( 'videos', 'ewo-2025' ); ?></p>
				<?php endif; ?>
			</div>
		</article>
		<?php
	}
}

if ( ! function_exists( 'ewo_2025_media_render_text_card' ) ) {
	function ewo_2025_media_render_text_card( $post ) {
		$image = get_post_meta( $post->ID, 'ewo_youtube_community_image', true );
		$url   = get_post_meta( $post->ID, 'ewo_youtube_community_url', true );
		$text  = wp_trim_words( wp_strip_all_tags( $post->post_content ), 24 );
		?>
		<article class="ewo-media-card ewo-media-card--text">
			<?php if ( ewo_2025_media_image_url_is_valid( $image ) ) : ?>
				<img src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( get_the_title( $post ) ); ?>" loading="lazy">
			<?php endif; ?>
			<div class="ewo-media-card__body">
				<time class="ewo-media-card__meta" datetime="<?php echo esc_attr( get_the_date( 'c', $post ) ); ?>"><?php echo esc_html( get_the_date( '', $post ) ); ?></time>
				<?php if ( $text ) : ?><p class="ewo-media-card__excerpt"><?php echo esc_html( $text ); ?></p><?php endif; ?>
				<?php if ( $url ) : ?><a class="ewo-media-card__more" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View post', 'ewo-2025' ); ?> &rarr;</a><?php endif; ?>
			</div>
		</article>
		<?php
	}
}

$ewo_videos    = ewo_2025_media_query_videos( false, 5 );
$ewo_shorts    = ewo_2025_media_query_videos( true, 5 );
$ewo_tiktok    = ewo_2025_media_query_tiktok();
$ewo_playlists = array_filter(
	ewo_2025_media_query_simple( 'ewo_playlist', 5 ),
	static function ( $post ) {
		return ! ewo_2025_media_is_hidden( $post->ID, 'ewo_youtube_playlist_hidden' );
	}
);
$ewo_community = ewo_2025_media_query_simple( 'ewo_community', 5 );
?>

<main id="primary" class="site-main">
	<div class="content-area">
		<section class="ewo-media-hub">
			<header class="ewo-media-hub__hero">
				<p class="ewo-media-hub__kicker"><?php esc_html_e( 'Media Intelligence', 'ewo-2025' ); ?></p>
				<h1><?php esc_html_e( 'EWO Media Hub', 'ewo-2025' ); ?></h1>
				<p><?php esc_html_e( 'Strategic videos, insights, and updates across all platforms.', 'ewo-2025' ); ?></p>
			</header>

			<section class="ewo-media-section">
				<div class="ewo-section-header">
					<div>
						<h2><?php esc_html_e( 'Latest Strategic Briefings', 'ewo-2025' ); ?></h2>
						<p class="ewo-section-description"><?php esc_html_e( 'Long-form YouTube analysis on geopolitics, power shifts, trade, and strategy.', 'ewo-2025' ); ?></p>
					</div>
					<a href="#"><?php esc_html_e( 'View all videos', 'ewo-2025' ); ?> &rarr;</a>
				</div>
				<div class="ewo-media-grid">
					<?php if ( $ewo_videos ) : ?>
						<?php foreach ( $ewo_videos as $ewo_item ) : ?>
							<?php ewo_2025_media_render_video_card( $ewo_item ); ?>
						<?php endforeach; ?>
					<?php else : ?>
						<p class="ewo-media-empty"><?php esc_html_e( 'No strategic briefings have been added yet.', 'ewo-2025' ); ?></p>
					<?php endif; ?>
				</div>
			</section>

			<section class="ewo-media-section">
				<div class="ewo-section-header">
					<div>
						<h2><?php esc_html_e( 'Latest Shorts', 'ewo-2025' ); ?></h2>
						<p class="ewo-section-description"><?php esc_html_e( 'Fast strategic signals and short-form commentary from EWO.', 'ewo-2025' ); ?></p>
					</div>
					<a href="#"><?php esc_html_e( 'View all shorts', 'ewo-2025' ); ?> &rarr;</a>
				</div>
				<div class="ewo-media-grid">
					<?php if ( $ewo_shorts ) : ?>
						<?php foreach ( $ewo_shorts as $ewo_item ) : ?>
							<?php ewo_2025_media_render_video_card( $ewo_item, __( 'Short', 'ewo-2025' ) ); ?>
						<?php endforeach; ?>
					<?php else : ?>
						<p class="ewo-media-empty"><?php esc_html_e( 'No Shorts have been added yet.', 'ewo-2025' ); ?></p>
					<?php endif; ?>
				</div>
			</section>

			<section class="ewo-media-section">
				<div class="ewo-section-header">
					<div>
						<h2><?php esc_html_e( 'TikTok Clips', 'ewo-2025' ); ?></h2>
						<p class="ewo-section-description"><?php esc_html_e( 'Short clips and platform-native updates published for TikTok audiences.', 'ewo-2025' ); ?></p>
					</div>
					<a href="#"><?php esc_html_e( 'View all TikTok clips', 'ewo-2025' ); ?> &rarr;</a>
				</div>
				<div class="ewo-media-grid">
					<?php if ( $ewo_tiktok ) : ?>
						<?php foreach ( $ewo_tiktok as $ewo_item ) : ?>
							<?php ewo_2025_media_render_text_card( $ewo_item ); ?>
						<?php endforeach; ?>
					<?php else : ?>
						<p class="ewo-media-empty"><?php esc_html_e( 'No TikTok clips have been added yet.', 'ewo-2025' ); ?></p>
					<?php endif; ?>
				</div>
			</section>

			<section class="ewo-media-section">
				<div class="ewo-section-header">
					<div>
						<h2><?php esc_html_e( 'Featured Playlists', 'ewo-2025' ); ?></h2>
						<p class="ewo-section-description"><?php esc_html_e( 'Curated YouTube series organized by strategic theme and region.', 'ewo-2025' ); ?></p>
					</div>
					<a href="#"><?php esc_html_e( 'View all playlists', 'ewo-2025' ); ?> &rarr;</a>
				</div>
				<div class="ewo-media-grid">
					<?php if ( $ewo_playlists ) : ?>
						<?php foreach ( $ewo_playlists as $ewo_item ) : ?>
							<?php ewo_2025_media_render_playlist_card( $ewo_item ); ?>
						<?php endforeach; ?>
					<?php else : ?>
						<p class="ewo-media-empty"><?php esc_html_e( 'No playlists have been added yet.', 'ewo-2025' ); ?></p>
					<?php endif; ?>
				</div>
			</section>

			<section class="ewo-media-section">
				<div class="ewo-section-header">
					<div>
						<h2><?php esc_html_e( 'Community Intelligence', 'ewo-2025' ); ?></h2>
						<p class="ewo-section-description"><?php esc_html_e( 'YouTube community posts, field notes, and audience-facing updates.', 'ewo-2025' ); ?></p>
					</div>
					<a href="#"><?php esc_html_e( 'View all posts', 'ewo-2025' ); ?> &rarr;</a>
				</div>
				<div class="ewo-media-grid">
					<?php if ( $ewo_community ) : ?>
						<?php foreach ( $ewo_community as $ewo_item ) : ?>
							<?php ewo_2025_media_render_text_card( $ewo_item ); ?>
						<?php endforeach; ?>
					<?php else : ?>
						<p class="ewo-media-empty"><?php esc_html_e( 'No community posts have been added yet.', 'ewo-2025' ); ?></p>
					<?php endif; ?>
				</div>
			</section>
		</section>
	</div>
</main>

<?php
get_footer();