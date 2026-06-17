<?php
/**
 * Frontend YouTube playlists grid.
 *
 * @package EWO_YouTube_Integration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders saved YouTube playlists.
 */
class EWO_YouTube_Playlists {
	const SHORTCODE    = 'ewo_youtube_playlists';
	const STYLE_HANDLE = 'ewo-youtube-playlists';

	/**
	 * Initialize hooks.
	 */
	public function init() {
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
		add_shortcode( self::SHORTCODE, array( $this, 'render_shortcode' ) );
	}

	/**
	 * Register frontend assets.
	 */
	public function register_assets() {
		wp_register_style(
			self::STYLE_HANDLE,
			EWO_YOUTUBE_INTEGRATION_URL . 'assets/css/youtube-playlists.css',
			array(),
			EWO_YOUTUBE_INTEGRATION_VERSION
		);
	}

	/**
	 * Render shortcode output.
	 *
	 * @return string
	 */
	public function render_shortcode() {
		return $this->render();
	}

	/**
	 * Render playlist cards.
	 *
	 * @return string
	 */
	public function render() {
		$playlists = $this->get_playlists();

		if ( empty( $playlists ) ) {
			return '';
		}

		if ( ! wp_style_is( self::STYLE_HANDLE, 'registered' ) ) {
			$this->register_assets();
		}

		wp_enqueue_style( self::STYLE_HANDLE );

		ob_start();
		?>
		<section class="ewo-youtube-playlists" aria-label="<?php esc_attr_e( 'EWO YouTube playlists', 'ewo-youtube-integration' ); ?>">
			<div class="ewo-youtube-playlists__header">
				<p class="ewo-youtube-playlists__kicker"><?php esc_html_e( 'Playlist Archive', 'ewo-youtube-integration' ); ?></p>
				<h2><?php esc_html_e( 'YouTube Playlists', 'ewo-youtube-integration' ); ?></h2>
			</div>
			<div class="ewo-youtube-playlists__grid">
				<?php foreach ( $playlists as $playlist ) : ?>
					<?php $this->render_card( $playlist ); ?>
				<?php endforeach; ?>
			</div>
		</section>
		<?php

		return ob_get_clean();
	}

	/**
	 * Get playlist posts.
	 *
	 * @return WP_Post[]
	 */
	private function get_playlists() {
		$query = new WP_Query(
			array(
				'post_type'           => 'ewo_youtube_playlist',
				'post_status'         => 'publish',
				'posts_per_page'      => 12,
				'orderby'             => 'date',
				'order'               => 'DESC',
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
			)
		);

		return $query->posts;
	}

	/**
	 * Render a playlist card.
	 *
	 * @param WP_Post $playlist Playlist post.
	 */
	private function render_card( $playlist ) {
		$title       = get_post_meta( $playlist->ID, 'ewo_youtube_playlist_title', true );
		$description = get_post_meta( $playlist->ID, 'ewo_youtube_playlist_description', true );
		$thumbnail   = get_post_meta( $playlist->ID, 'ewo_youtube_playlist_thumbnail', true );
		$url         = get_post_meta( $playlist->ID, 'ewo_youtube_playlist_url', true );

		if ( '' === $title ) {
			$title = get_the_title( $playlist );
		}

		if ( '' === $description ) {
			$description = get_the_excerpt( $playlist );
		}
		?>
		<article class="ewo-youtube-playlists__card">
			<div class="ewo-youtube-playlists__thumb">
				<?php if ( $thumbnail ) : ?>
					<img src="<?php echo esc_url( $thumbnail ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy">
				<?php else : ?>
					<span class="ewo-youtube-playlists__placeholder" aria-hidden="true"></span>
				<?php endif; ?>
			</div>
			<div class="ewo-youtube-playlists__body">
				<h3><?php echo esc_html( $title ); ?></h3>
				<?php if ( $description ) : ?>
					<p><?php echo esc_html( wp_trim_words( $description, 24 ) ); ?></p>
				<?php endif; ?>
				<?php if ( $url ) : ?>
					<a class="ewo-youtube-playlists__button" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'View Playlist', 'ewo-youtube-integration' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</article>
		<?php
	}
}
