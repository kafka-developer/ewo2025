<?php
/**
 * Frontend YouTube video marquee.
 *
 * @package EWO_YouTube_Integration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders latest long-form YouTube videos.
 */
class EWO_YouTube_Marquee {
	/**
	 * Shortcode tag.
	 */
	const SHORTCODE = 'ewo_youtube_marquee';

	/**
	 * Frontend style handle.
	 */
	const STYLE_HANDLE = 'ewo-youtube-marquee';

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
			EWO_YOUTUBE_INTEGRATION_URL . 'assets/css/youtube-marquee.css',
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
	 * Render the video marquee.
	 *
	 * @return string
	 */
	public function render() {
		$videos = $this->get_videos();

		if ( empty( $videos ) ) {
			return '';
		}

		if ( ! wp_style_is( self::STYLE_HANDLE, 'registered' ) ) {
			$this->register_assets();
		}

		wp_enqueue_style( self::STYLE_HANDLE );

		ob_start();
		?>
		<section class="ewo-youtube-marquee<?php echo count( $videos ) > 1 ? '' : ' ewo-youtube-marquee--static'; ?>" aria-label="<?php esc_attr_e( 'Latest EWO YouTube videos', 'ewo-youtube-integration' ); ?>">
			<div class="ewo-youtube-marquee__header">
				<p class="ewo-youtube-marquee__kicker"><?php esc_html_e( 'Video Intelligence', 'ewo-youtube-integration' ); ?></p>
				<h2><?php esc_html_e( 'Latest Strategic Briefings', 'ewo-youtube-integration' ); ?></h2>
			</div>
			<div class="ewo-youtube-marquee__viewport">
				<div class="ewo-youtube-marquee__track">
					<?php
					foreach ( $videos as $video ) {
						$this->render_card( $video );
					}

					if ( count( $videos ) > 1 ) {
						foreach ( $videos as $video ) {
							$this->render_card( $video, true );
						}
					}
					?>
				</div>
			</div>
		</section>
		<?php

		return ob_get_clean();
	}

	/**
	 * Get latest video posts.
	 *
	 * @return WP_Post[]
	 */
	private function get_videos() {
		$query = new WP_Query(
			array(
				'post_type'           => 'ewo_youtube_video',
				'post_status'         => 'publish',
				'posts_per_page'      => 20,
				'orderby'             => 'date',
				'order'               => 'DESC',
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
			)
		);

		$videos = array();

		foreach ( $query->posts as $video ) {
			if ( $this->is_hidden_video( $video->ID ) ) {
				continue;
			}

			if ( $this->is_short_video( $video->ID ) ) {
				continue;
			}

			$videos[] = $video;

			if ( 5 === count( $videos ) ) {
				break;
			}
		}

		return $videos;
	}

	/**
	 * Render a single video card.
	 *
	 * @param WP_Post $video    Video post.
	 * @param bool    $is_clone Whether this is a non-interactive marquee clone.
	 */
	private function render_card( $video, $is_clone = false ) {
		$watch_url     = $this->get_watch_url( $video->ID );
		$thumbnail_url = get_the_post_thumbnail_url( $video, 'large' );

		if ( ! $thumbnail_url ) {
			$thumbnail_url = get_post_meta( $video->ID, 'ewo_youtube_thumbnail', true );
		}
		$publish_date  = get_the_date( '', $video );
		$title         = get_the_title( $video );
		?>
		<article class="ewo-youtube-marquee__card"<?php echo $is_clone ? ' aria-hidden="true"' : ''; ?>>
			<div class="ewo-youtube-marquee__thumb">
				<?php if ( $thumbnail_url ) : ?>
					<img src="<?php echo esc_url( $thumbnail_url ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy">
				<?php else : ?>
					<span class="ewo-youtube-marquee__placeholder" aria-hidden="true"></span>
				<?php endif; ?>
			</div>
			<div class="ewo-youtube-marquee__body">
				<time datetime="<?php echo esc_attr( get_the_date( 'c', $video ) ); ?>"><?php echo esc_html( $publish_date ); ?></time>
				<h3><?php echo esc_html( $title ); ?></h3>
				<?php if ( $watch_url ) : ?>
					<a class="ewo-youtube-marquee__button" href="<?php echo esc_url( $watch_url ); ?>" target="_blank" rel="noopener noreferrer"<?php echo $is_clone ? ' tabindex="-1"' : ''; ?>>
						<?php esc_html_e( 'Watch', 'ewo-youtube-integration' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</article>
		<?php
	}

	/**
	 * Determine whether a video is hidden.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	private function is_hidden_video( $post_id ) {
		return (bool) get_post_meta( $post_id, 'ewo_youtube_hidden', true );
	}

	/**
	 * Determine whether a video is marked as a Short.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	private function is_short_video( $post_id ) {
		$video_type = strtolower( (string) get_post_meta( $post_id, 'ewo_youtube_video_type', true ) );

		if ( in_array( $video_type, array( 'short', 'shorts' ), true ) ) {
			return true;
		}

		$flags = array(
			get_post_meta( $post_id, 'ewo_youtube_is_short', true ),
			get_post_meta( $post_id, '_ewo_youtube_is_short', true ),
		);

		foreach ( $flags as $flag ) {
			if ( in_array( strtolower( (string) $flag ), array( '1', 'yes', 'true' ), true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get a video watch URL from supported post meta keys.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	private function get_watch_url( $post_id ) {
		$meta_keys = array(
			'ewo_youtube_url',
			'_ewo_youtube_url',
			'youtube_url',
			'video_url',
		);

		foreach ( $meta_keys as $meta_key ) {
			$url = get_post_meta( $post_id, $meta_key, true );

			if ( $url ) {
				return esc_url_raw( $url );
			}
		}

		return '';
	}
}
