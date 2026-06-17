<?php
/**
 * Frontend YouTube Shorts grid.
 *
 * @package EWO_YouTube_Integration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders recent YouTube Shorts.
 */
class EWO_YouTube_Shorts {
	/**
	 * Shortcode tag.
	 */
	const SHORTCODE = 'ewo_youtube_shorts';

	/**
	 * Frontend style handle.
	 */
	const STYLE_HANDLE = 'ewo-youtube-shorts';

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
			EWO_YOUTUBE_INTEGRATION_URL . 'assets/css/youtube-shorts.css',
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
	 * Render the Shorts grid.
	 *
	 * @return string
	 */
	public function render() {
		$shorts = $this->get_shorts();

		if ( empty( $shorts ) ) {
			return '';
		}

		if ( ! wp_style_is( self::STYLE_HANDLE, 'registered' ) ) {
			$this->register_assets();
		}

		wp_enqueue_style( self::STYLE_HANDLE );

		ob_start();
		?>
		<section class="ewo-youtube-shorts" aria-label="<?php esc_attr_e( 'Recent EWO YouTube Shorts', 'ewo-youtube-integration' ); ?>">
			<div class="ewo-youtube-shorts__header">
				<p class="ewo-youtube-shorts__kicker"><?php esc_html_e( 'Shorts Desk', 'ewo-youtube-integration' ); ?></p>
				<h2><?php esc_html_e( 'Recent YouTube Shorts', 'ewo-youtube-integration' ); ?></h2>
			</div>
			<div class="ewo-youtube-shorts__grid">
				<?php
				foreach ( $shorts as $short ) {
					$this->render_card( $short );
				}
				?>
			</div>
		</section>
		<?php

		return ob_get_clean();
	}

	/**
	 * Get recent Shorts posts.
	 *
	 * @return WP_Post[]
	 */
	private function get_shorts() {
		$query = new WP_Query(
			array(
				'post_type'           => 'ewo_youtube_video',
				'post_status'         => 'publish',
				'posts_per_page'      => 40,
				'orderby'             => 'date',
				'order'               => 'DESC',
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
			)
		);

		$shorts = array();

		foreach ( $query->posts as $short ) {
			if ( $this->is_hidden_video( $short->ID ) ) {
				continue;
			}

			if ( ! $this->is_short_video( $short->ID ) ) {
				continue;
			}

			$shorts[] = $short;

			if ( 8 === count( $shorts ) ) {
				break;
			}
		}

		return $shorts;
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
	 * Render a single Shorts card.
	 *
	 * @param WP_Post $short Shorts post.
	 */
	private function render_card( $short ) {
		$watch_url     = $this->get_watch_url( $short->ID );
		$thumbnail_url = get_the_post_thumbnail_url( $short, 'large' );

		if ( ! $thumbnail_url ) {
			$thumbnail_url = get_post_meta( $short->ID, 'ewo_youtube_thumbnail', true );
		}
		$publish_date  = get_the_date( '', $short );
		$title         = get_the_title( $short );
		?>
		<article class="ewo-youtube-shorts__card">
			<div class="ewo-youtube-shorts__thumb">
				<?php if ( $thumbnail_url ) : ?>
					<img src="<?php echo esc_url( $thumbnail_url ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy">
				<?php else : ?>
					<span class="ewo-youtube-shorts__placeholder" aria-hidden="true"></span>
				<?php endif; ?>
			</div>
			<div class="ewo-youtube-shorts__body">
				<time datetime="<?php echo esc_attr( get_the_date( 'c', $short ) ); ?>"><?php echo esc_html( $publish_date ); ?></time>
				<h3><?php echo esc_html( $title ); ?></h3>
				<?php if ( $watch_url ) : ?>
					<a class="ewo-youtube-shorts__button" href="<?php echo esc_url( $watch_url ); ?>" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'Watch', 'ewo-youtube-integration' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</article>
		<?php
	}

	/**
	 * Get a Shorts watch URL from supported post meta keys.
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
