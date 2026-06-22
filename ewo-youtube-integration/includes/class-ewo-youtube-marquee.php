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
	 * Shortcode tag for the full videos archive grid.
	 */
	const ARCHIVE_SHORTCODE = 'ewo_youtube_videos';

	/**
	 * Frontend style handle.
	 */
	const STYLE_HANDLE = 'ewo-youtube-marquee';

	/**
	 * Frontend carousel script handle.
	 */
	const SCRIPT_HANDLE = 'ewo-youtube-carousel';

	/**
	 * Initialize hooks.
	 */
	public function init() {
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
		add_shortcode( self::SHORTCODE, array( $this, 'render_shortcode' ) );
		add_shortcode( self::ARCHIVE_SHORTCODE, array( $this, 'render_archive_shortcode' ) );
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

		wp_register_script(
			self::SCRIPT_HANDLE,
			EWO_YOUTUBE_INTEGRATION_URL . 'assets/js/youtube-carousel.js',
			array(),
			EWO_YOUTUBE_INTEGRATION_VERSION,
			true
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
	 * Render the single-feature latest-videos slider.
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
		wp_enqueue_script( self::SCRIPT_HANDLE );

		ob_start();
		?>
		<section class="ewo-youtube-marquee ewo-youtube-feature" data-ewo-feature aria-label="<?php esc_attr_e( 'Latest EWO YouTube videos', 'ewo-youtube-integration' ); ?>">
			<div class="ewo-youtube-marquee__header">
				<p class="ewo-youtube-marquee__kicker"><?php esc_html_e( 'Video Intelligence', 'ewo-youtube-integration' ); ?></p>
				<h2><?php esc_html_e( 'Latest Strategic Briefings', 'ewo-youtube-integration' ); ?></h2>
			</div>
			<div class="ewo-youtube-feature__stage">
				<button class="ewo-youtube-feature__arrow ewo-youtube-feature__arrow--prev" type="button" aria-label="<?php esc_attr_e( 'Previous video', 'ewo-youtube-integration' ); ?>">
					<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>
				</button>
				<div class="ewo-youtube-feature__viewport">
					<div class="ewo-youtube-feature__track">
						<?php
						foreach ( $videos as $video ) {
							$this->render_feature_slide( $video );
						}
						?>
					</div>
				</div>
				<button class="ewo-youtube-feature__arrow ewo-youtube-feature__arrow--next" type="button" aria-label="<?php esc_attr_e( 'Next video', 'ewo-youtube-integration' ); ?>">
					<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 18l6-6-6-6"/></svg>
				</button>
			</div>
			<div class="ewo-youtube-feature__dots" aria-label="<?php esc_attr_e( 'Carousel pagination', 'ewo-youtube-integration' ); ?>"></div>
		</section>
		<?php

		return ob_get_clean();
	}

	/**
	 * Render a single featured slide (image left, details right).
	 *
	 * @param WP_Post $video Video post.
	 */
	private function render_feature_slide( $video ) {
		$watch_url     = $this->get_watch_url( $video->ID );
		$thumbnail_url = $this->get_card_thumbnail_url( $video );

		$publish_date = get_the_date( '', $video );
		$title        = get_the_title( $video );
		$excerpt      = $this->get_slide_excerpt( $video );
		?>
		<article class="ewo-youtube-feature__slide">
			<div class="ewo-youtube-feature__card">
				<div class="ewo-youtube-feature__media">
					<?php if ( $thumbnail_url ) : ?>
						<img src="<?php echo esc_url( $thumbnail_url ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy">
					<?php else : ?>
						<span class="ewo-youtube-feature__placeholder" aria-hidden="true"></span>
					<?php endif; ?>
					<span class="ewo-youtube-feature__play" aria-hidden="true"></span>
				</div>
				<div class="ewo-youtube-feature__details">
					<time class="ewo-youtube-feature__date" datetime="<?php echo esc_attr( get_the_date( 'c', $video ) ); ?>"><?php echo esc_html( $publish_date ); ?></time>
					<h3 class="ewo-youtube-feature__title">
						<?php if ( $watch_url ) : ?>
							<a class="ewo-youtube-feature__link" href="<?php echo esc_url( $watch_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $title ); ?></a>
						<?php else : ?>
							<?php echo esc_html( $title ); ?>
						<?php endif; ?>
					</h3>
					<?php if ( '' !== $excerpt ) : ?>
						<p class="ewo-youtube-feature__excerpt"><?php echo esc_html( $excerpt ); ?></p>
					<?php endif; ?>
					<?php if ( $watch_url ) : ?>
						<a class="ewo-youtube-feature__button" href="<?php echo esc_url( $watch_url ); ?>" target="_blank" rel="noopener noreferrer">
							<span aria-hidden="true">&#9654;</span> <?php esc_html_e( 'Watch Analysis', 'ewo-youtube-integration' ); ?> <span aria-hidden="true">&rarr;</span>
						</a>
					<?php endif; ?>
				</div>
			</div>
		</article>
		<?php
	}

	/**
	 * Get an optional short description for a feature slide.
	 *
	 * Uses the post excerpt or content if present; returns an empty string
	 * otherwise (the description is then omitted).
	 *
	 * @param WP_Post $video Video post.
	 * @return string
	 */
	private function get_slide_excerpt( $video ) {
		$raw = '' !== $video->post_excerpt ? $video->post_excerpt : $video->post_content;
		$raw = trim( wp_strip_all_tags( (string) $raw ) );

		if ( '' === $raw ) {
			return '';
		}

		return wp_trim_words( $raw, 30 );
	}

	/**
	 * Render the videos archive shortcode output.
	 *
	 * @return string
	 */
	public function render_archive_shortcode() {
		
				return $this->render_archive();
	}

	/**
	 * Render dedicated content sections for the Videos page.
	 *
	 * @return string
	 */
	public function render_archive() {
		if ( ! wp_style_is( self::STYLE_HANDLE, 'registered' ) ) {
			$this->register_assets();
		}

		wp_enqueue_style( self::STYLE_HANDLE );

		$videos          = $this->get_archive_videos();
		$shorts          = $this->get_archive_shorts();
		$tiktok_clips    = $this->get_archive_tiktok_clips();
		$playlists       = $this->get_archive_playlists();
		$community_posts = $this->get_archive_community_posts();

		ob_start();
		?>
		<section class="ewo-media-hub" aria-label="<?php esc_attr_e( 'EWO Media Hub', 'ewo-youtube-integration' ); ?>">
			<header class="ewo-media-hub__hero">
				<p class="ewo-media-hub__kicker"><?php esc_html_e( 'MEDIA INTELLIGENCE', 'ewo-youtube-integration' ); ?></p>
				<h2 class="ewo-media-hub__title"><?php esc_html_e( 'EWO Media Hub', 'ewo-youtube-integration' ); ?></h2>
				<p class="ewo-media-hub__subtitle"><?php esc_html_e( 'Strategic videos, insights, and updates across all platforms.', 'ewo-youtube-integration' ); ?></p>
			</header>

			<?php
			$this->render_media_section(
				array(
					'id'          => 'ewo-media-briefings',
					'title'       => __( 'Latest Strategic Briefings', 'ewo-youtube-integration' ),
					'description' => __( 'Long-form YouTube analysis on geopolitics, power shifts, trade, and strategy.', 'ewo-youtube-integration' ),
					'items'       => $videos,
					'grid_class'  => 'ewo-media-hub__grid--videos',
					'empty'       => __( 'No strategic briefings have been added yet.', 'ewo-youtube-integration' ),
					'link_label'  => __( 'View all videos', 'ewo-youtube-integration' ),
					'link_url'    => $this->get_media_hub_link( 'youtube', 'ewo-media-briefings' ),
					'renderer'    => 'render_video_card',
					'icon'        => 'YT',
				)
			);

			$this->render_media_section(
				array(
					'id'          => 'ewo-media-shorts',
					'title'       => __( 'Latest Shorts', 'ewo-youtube-integration' ),
					'description' => __( 'Fast strategic signals and short-form commentary from EWO.', 'ewo-youtube-integration' ),
					'items'       => $shorts,
					'grid_class'  => 'ewo-media-hub__grid--shorts',
					'empty'       => __( 'No Shorts have been added yet.', 'ewo-youtube-integration' ),
					'link_label'  => __( 'View all shorts', 'ewo-youtube-integration' ),
					'link_url'    => $this->get_media_hub_link( 'youtube', 'ewo-media-shorts' ),
					'renderer'    => 'render_short_card',
					'icon'        => 'S',
				)
			);

			$this->render_media_section(
				array(
					'id'          => 'ewo-media-tiktok',
					'title'       => __( 'TikTok Clips', 'ewo-youtube-integration' ),
					'description' => __( 'Short clips and platform-native updates published for TikTok audiences.', 'ewo-youtube-integration' ),
					'items'       => $tiktok_clips,
					'grid_class'  => 'ewo-media-hub__grid--tiktok',
					'empty'       => __( 'No TikTok clips have been added yet.', 'ewo-youtube-integration' ),
					'link_label'  => __( 'View all TikTok clips', 'ewo-youtube-integration' ),
					'link_url'    => $this->get_media_hub_link( 'tiktok', 'ewo-media-tiktok' ),
					'renderer'    => 'render_tiktok_card',
					'icon'        => 'TK',
				)
			);

			$this->render_media_section(
				array(
					'id'          => 'ewo-media-playlists',
					'title'       => __( 'Featured Playlists', 'ewo-youtube-integration' ),
					'description' => __( 'Curated YouTube series organized by strategic theme and region.', 'ewo-youtube-integration' ),
					'items'       => $playlists,
					'grid_class'  => 'ewo-media-hub__grid--playlists',
					'empty'       => __( 'No playlists have been added yet.', 'ewo-youtube-integration' ),
					'link_label'  => __( 'View all playlists', 'ewo-youtube-integration' ),
					'link_url'    => $this->get_media_hub_link( 'youtube', 'ewo-media-playlists' ),
					'renderer'    => 'render_playlist_card',
					'icon'        => 'PL',
				)
			);

			$this->render_media_section(
				array(
					'id'          => 'ewo-media-community',
					'title'       => __( 'Community Intelligence', 'ewo-youtube-integration' ),
					'description' => __( 'YouTube community posts, field notes, and audience-facing updates.', 'ewo-youtube-integration' ),
					'items'       => $community_posts,
					'grid_class'  => 'ewo-media-hub__grid--community',
					'empty'       => __( 'No community posts have been added yet.', 'ewo-youtube-integration' ),
					'link_label'  => __( 'View all posts', 'ewo-youtube-integration' ),
					'link_url'    => $this->get_media_hub_link( 'youtube', 'ewo-media-community' ),
					'renderer'    => 'render_community_card',
					'icon'        => 'CI',
				)
			);
			?>
		</section>
		<?php

		return ob_get_clean();
	}

	/**
	 * Render one Media Hub section.
	 *
	 * @param array $args Section arguments.
	 */
	private function render_media_section( $args ) {
		?>
		<section id="<?php echo esc_attr( $args['id'] ); ?>" class="ewo-media-section ewo-media-hub__section" aria-labelledby="<?php echo esc_attr( $args['id'] . '-title' ); ?>">
			<div class="ewo-media-section__header ewo-media-hub__section-header">
				<div class="ewo-media-section__heading">
					<span class="ewo-media-section__icon" aria-hidden="true"><?php echo esc_html( $args['icon'] ); ?></span>
					<div>
						<h3 id="<?php echo esc_attr( $args['id'] . '-title' ); ?>" class="ewo-media-section__title ewo-media-hub__section-title"><?php echo esc_html( $args['title'] ); ?></h3>
						<p class="ewo-media-section__description ewo-media-hub__section-description"><?php echo esc_html( $args['description'] ); ?></p>
					</div>
				</div>
				<a class="ewo-media-section__view-all ewo-media-hub__view-all" href="<?php echo esc_url( $args['link_url'] ); ?>">
					<?php echo esc_html( $args['link_label'] ); ?> <span aria-hidden="true">&rarr;</span>
				</a>
			</div>
			<?php if ( empty( $args['items'] ) ) : ?>
				<p class="ewo-media-section__empty ewo-media-hub__empty"><?php echo esc_html( $args['empty'] ); ?></p>
			<?php else : ?>
				<div class="ewo-media-grid ewo-media-hub__grid <?php echo esc_attr( $args['grid_class'] ); ?>">
					<?php
					foreach ( $args['items'] as $item ) {
						$this->{$args['renderer']}( $item );
					}
					?>
				</div>
			<?php endif; ?>
		</section>
		<?php
	}

	/**
	 * Get latest video posts.
	 *
	 * @return WP_Post[]
	 */
	private function get_videos() {
		$query = new WP_Query(
			array(
				'post_type'           => 'ewo_video',
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
	 * Get long-form videos for the Media Hub.
	 *
	 * @return WP_Post[]
	 */
	private function get_archive_videos() {
		$query = new WP_Query(
			array(
				'post_type'           => 'ewo_video',
				'post_status'         => 'publish',
				'posts_per_page'      => 24,
				'orderby'             => 'date',
				'order'               => 'DESC',
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
			)
		);

		$videos = array();

		foreach ( $query->posts as $video ) {
			if ( $this->is_hidden_video( $video->ID ) || $this->is_short_video( $video->ID ) ) {
				continue;
			}

			$videos[] = $video;
		}

		return array_slice( $this->sort_videos_for_archive( $videos ), 0, 6 );
	}

	/**
	 * Get Shorts for the Media Hub.
	 *
	 * @return WP_Post[]
	 */
	private function get_archive_shorts() {
		$query = new WP_Query(
			array(
				'post_type'           => 'ewo_video',
				'post_status'         => 'publish',
				'posts_per_page'      => 24,
				'orderby'             => 'date',
				'order'               => 'DESC',
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
			)
		);

		$shorts = array();

		foreach ( $query->posts as $video ) {
			if ( $this->is_hidden_video( $video->ID ) || ! $this->is_short_video( $video->ID ) ) {
				continue;
			}

			$shorts[] = $video;
		}

		return array_slice( $this->sort_videos_for_archive( $shorts ), 0, 6 );
	}

	/**
	 * Get TikTok clips for the Media Hub when a TikTok content type is registered.
	 *
	 * @return WP_Post[]
	 */
	private function get_archive_tiktok_clips() {
		$post_type = $this->get_tiktok_post_type();

		if ( '' === $post_type ) {
			return array();
		}

		$query = new WP_Query(
			array(
				'post_type'           => $post_type,
				'post_status'         => 'publish',
				'posts_per_page'      => 6,
				'orderby'             => 'date',
				'order'               => 'DESC',
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
			)
		);

		return $query->posts;
	}

	/**
	 * Get playlists for the Media Hub.
	 *
	 * @return WP_Post[]
	 */
	private function get_archive_playlists() {
		$query = new WP_Query(
			array(
				'post_type'           => 'ewo_playlist',
				'post_status'         => 'publish',
				'posts_per_page'      => 12,
				'orderby'             => 'date',
				'order'               => 'DESC',
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
			)
		);

		$playlists = array();

		foreach ( $query->posts as $playlist ) {
			if ( $this->is_hidden_playlist( $playlist->ID ) ) {
				continue;
			}

			$playlists[] = $playlist;
		}

		return array_slice( $this->sort_playlists_for_archive( $playlists ), 0, 5 );
	}

	/**
	 * Get community posts for the Media Hub.
	 *
	 * @return WP_Post[]
	 */
	private function get_archive_community_posts() {
		$query = new WP_Query(
			array(
				'post_type'           => 'ewo_community',
				'post_status'         => 'publish',
				'posts_per_page'      => 5,
				'orderby'             => 'date',
				'order'               => 'DESC',
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
			)
		);

		return $query->posts;
	}

	/**
	 * Sort video results by manual order, then newest first.
	 *
	 * @param WP_Post[] $videos Video posts.
	 * @return WP_Post[]
	 */
	private function sort_videos_for_archive( $videos ) {
		usort(
			$videos,
			static function ( $a, $b ) {
				$sort_a = get_post_meta( $a->ID, 'ewo_youtube_sort_order', true );
				$sort_b = get_post_meta( $b->ID, 'ewo_youtube_sort_order', true );
				$sort_a = ( '' === $sort_a ) ? PHP_INT_MAX : (int) $sort_a;
				$sort_b = ( '' === $sort_b ) ? PHP_INT_MAX : (int) $sort_b;

				if ( $sort_a !== $sort_b ) {
					return $sort_a <=> $sort_b;
				}

				return strcmp( $b->post_date, $a->post_date );
			}
		);

		return $videos;
	}

	/**
	 * Sort playlist results by manual order, then newest first.
	 *
	 * @param WP_Post[] $playlists Playlist posts.
	 * @return WP_Post[]
	 */
	private function sort_playlists_for_archive( $playlists ) {
		usort(
			$playlists,
			static function ( $a, $b ) {
				$sort_a = get_post_meta( $a->ID, 'ewo_youtube_playlist_sort_order', true );
				$sort_b = get_post_meta( $b->ID, 'ewo_youtube_playlist_sort_order', true );
				$sort_a = ( '' === $sort_a ) ? PHP_INT_MAX : (int) $sort_a;
				$sort_b = ( '' === $sort_b ) ? PHP_INT_MAX : (int) $sort_b;

				if ( $sort_a !== $sort_b ) {
					return $sort_a <=> $sort_b;
				}

				return strcmp( $b->post_date, $a->post_date );
			}
		);

		return $playlists;
	}

	/**
	 * Render a compact Media Hub video card.
	 *
	 * @param WP_Post $video Video post.
	 */
	private function render_video_card( $video ) {
		$watch_url     = $this->get_watch_url( $video->ID );
		$thumbnail_url = $this->get_card_thumbnail_url( $video );
		$title         = get_the_title( $video );
		?>
		<article class="ewo-media-card ewo-media-card--video">
			<div class="ewo-media-card__media">
				<?php if ( $thumbnail_url ) : ?>
					<img src="<?php echo esc_url( $thumbnail_url ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy">
				<?php else : ?>
					<span class="ewo-youtube-marquee__placeholder" aria-hidden="true"></span>
				<?php endif; ?>
			</div>
			<div class="ewo-media-card__body">
				<time class="ewo-media-card__meta" datetime="<?php echo esc_attr( get_the_date( 'c', $video ) ); ?>"><?php echo esc_html( get_the_date( '', $video ) ); ?></time>
				<h4 class="ewo-media-card__title">
					<?php if ( $watch_url ) : ?>
						<a href="<?php echo esc_url( $watch_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $title ); ?></a>
					<?php else : ?>
						<?php echo esc_html( $title ); ?>
					<?php endif; ?>
				</h4>
			</div>
		</article>
		<?php
	}
	/**
	 * Render a single video card.
	 *
	 * @param WP_Post $video    Video post.
	 * @param bool    $is_clone Whether this is a non-interactive marquee clone.
	 */
	private function render_card( $video, $is_clone = false ) {
		$watch_url     = $this->get_watch_url( $video->ID );
		$thumbnail_url = $this->get_card_thumbnail_url( $video );
		$publish_date = get_the_date( '', $video );
		$title        = get_the_title( $video );
		$topics       = $this->get_card_topics( $video->ID );
		$tabindex     = $is_clone ? ' tabindex="-1"' : '';
		?>
		<article class="ewo-youtube-marquee__card">
			<div class="ewo-youtube-marquee__thumb">
				<?php if ( $thumbnail_url ) : ?>
					<img src="<?php echo esc_url( $thumbnail_url ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy">
				<?php else : ?>
					<span class="ewo-youtube-marquee__placeholder" aria-hidden="true"></span>
				<?php endif; ?>
			</div>
			<div class="ewo-youtube-marquee__body">
				<?php if ( ! empty( $topics ) ) : ?>
					<ul class="ewo-youtube-marquee__badges">
						<?php foreach ( $topics as $topic ) : ?>
							<li class="ewo-youtube-marquee__badge"><?php echo esc_html( $topic ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
				<time class="ewo-youtube-marquee__date" datetime="<?php echo esc_attr( get_the_date( 'c', $video ) ); ?>"><?php echo esc_html( $publish_date ); ?></time>
				<h3 class="ewo-youtube-marquee__title">
					<?php if ( $watch_url ) : ?>
						<a class="ewo-youtube-marquee__card-link" href="<?php echo esc_url( $watch_url ); ?>" target="_blank" rel="noopener noreferrer"<?php echo $tabindex; ?>><?php echo esc_html( $title ); ?></a>
					<?php else : ?>
						<?php echo esc_html( $title ); ?>
					<?php endif; ?>
				</h3>
				<?php if ( $watch_url ) : ?>
					<a class="ewo-youtube-marquee__button" href="<?php echo esc_url( $watch_url ); ?>" target="_blank" rel="noopener noreferrer"<?php echo $tabindex; ?>>
						<?php esc_html_e( 'Watch Analysis', 'ewo-youtube-integration' ); ?> <span aria-hidden="true">&rarr;</span>
					</a>
				<?php endif; ?>
			</div>
		</article>
		<?php
	}
	/**
	 * Render a single Short card.
	 *
	 * @param WP_Post $short Short video post.
	 */
	private function render_short_card( $short ) {
		$watch_url     = $this->get_watch_url( $short->ID );
		$thumbnail_url = $this->get_card_thumbnail_url( $short );
		$title         = get_the_title( $short );
		?>
		<article class="ewo-media-card ewo-media-card--short">
			<div class="ewo-media-card__media ewo-media-card__media--short">
				<?php if ( $thumbnail_url ) : ?>
					<img src="<?php echo esc_url( $thumbnail_url ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy">
				<?php else : ?>
					<span class="ewo-youtube-marquee__placeholder" aria-hidden="true"></span>
				<?php endif; ?>
			</div>
			<div class="ewo-media-card__body">
				<p class="ewo-media-card__eyebrow"><?php esc_html_e( 'Short', 'ewo-youtube-integration' ); ?></p>
				<h4 class="ewo-media-card__title">
					<?php if ( $watch_url ) : ?>
						<a href="<?php echo esc_url( $watch_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $title ); ?></a>
					<?php else : ?>
						<?php echo esc_html( $title ); ?>
					<?php endif; ?>
				</h4>
			</div>
		</article>
		<?php
	}

	/**
	 * Render a single TikTok card.
	 *
	 * @param WP_Post $clip TikTok post.
	 */
	private function render_tiktok_card( $clip ) {
		$url           = $this->get_first_meta_value( $clip->ID, array( 'ewo_tiktok_url', 'tiktok_url', 'video_url', 'url' ) );
		$thumbnail_url = $this->get_tiktok_thumbnail_url( $clip );
		$title         = get_the_title( $clip );
		$excerpt       = wp_trim_words( wp_strip_all_tags( '' !== $clip->post_excerpt ? $clip->post_excerpt : $clip->post_content ), 18 );
		?>
		<article class="ewo-media-card ewo-media-card--tiktok">
			<div class="ewo-media-card__media ewo-media-card__media--short">
				<?php if ( $thumbnail_url ) : ?>
					<img src="<?php echo esc_url( $thumbnail_url ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy">
				<?php else : ?>
					<span class="ewo-youtube-marquee__placeholder" aria-hidden="true"></span>
				<?php endif; ?>
			</div>
			<div class="ewo-media-card__body">
				<p class="ewo-media-card__eyebrow"><?php esc_html_e( 'TikTok', 'ewo-youtube-integration' ); ?></p>
				<h4 class="ewo-media-card__title">
					<?php if ( $url ) : ?>
						<a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $title ); ?></a>
					<?php else : ?>
						<?php echo esc_html( $title ); ?>
					<?php endif; ?>
				</h4>
				<?php if ( $excerpt ) : ?>
					<p class="ewo-media-card__excerpt"><?php echo esc_html( $excerpt ); ?></p>
				<?php endif; ?>
			</div>
		</article>
		<?php
	}

	/**
	 * Render a single playlist card.
	 *
	 * @param WP_Post $playlist Playlist post.
	 */
	private function render_playlist_card( $playlist ) {
		$title         = $this->get_playlist_title( $playlist );
		$url           = get_post_meta( $playlist->ID, 'ewo_youtube_playlist_url', true );
		$thumbnail_url = get_post_meta( $playlist->ID, 'ewo_youtube_playlist_thumbnail', true );
		$video_count   = $this->get_playlist_video_count( $playlist->ID );
		?>
		<article class="ewo-media-card ewo-media-card--playlist">
			<div class="ewo-media-card__media">
				<?php if ( $this->is_valid_image_url( $thumbnail_url ) ) : ?>
					<img src="<?php echo esc_url( $thumbnail_url ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy">
				<?php else : ?>
					<span class="ewo-youtube-marquee__placeholder" aria-hidden="true"></span>
				<?php endif; ?>
			</div>
			<div class="ewo-media-card__body">
				<p class="ewo-media-card__eyebrow"><?php esc_html_e( 'Playlist', 'ewo-youtube-integration' ); ?></p>
				<h4 class="ewo-media-card__title">
					<?php if ( $url ) : ?>
						<a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $title ); ?></a>
					<?php else : ?>
						<?php echo esc_html( $title ); ?>
					<?php endif; ?>
				</h4>
				<?php if ( $video_count ) : ?>
					<p class="ewo-media-card__meta">
						<?php
						printf(
							esc_html( _n( '%s video', '%s videos', $video_count, 'ewo-youtube-integration' ) ),
							esc_html( number_format_i18n( $video_count ) )
						);
						?>
					</p>
				<?php endif; ?>
			</div>
		</article>
		<?php
	}

	/**
	 * Render a single community post card.
	 *
	 * @param WP_Post $community_post Community post.
	 */
	private function render_community_card( $community_post ) {
		$url        = get_post_meta( $community_post->ID, 'ewo_youtube_community_url', true );
		$image_url  = get_post_meta( $community_post->ID, 'ewo_youtube_community_image', true );
		$excerpt    = wp_trim_words( wp_strip_all_tags( $community_post->post_content ), 28 );
		$title      = get_the_title( $community_post );
		$engagement = $this->get_community_engagement( $community_post->ID );
		?>
		<article class="ewo-media-card ewo-media-card--community">
			<?php if ( $this->is_valid_image_url( $image_url ) ) : ?>
				<div class="ewo-media-card__media">
					<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy">
				</div>
			<?php endif; ?>
			<div class="ewo-media-card__body">
				<time class="ewo-media-card__meta" datetime="<?php echo esc_attr( get_the_date( 'c', $community_post ) ); ?>"><?php echo esc_html( get_the_date( '', $community_post ) ); ?></time>
				<?php if ( $excerpt ) : ?>
					<p class="ewo-media-card__excerpt"><?php echo esc_html( $excerpt ); ?></p>
				<?php endif; ?>
				<?php if ( $engagement ) : ?>
					<p class="ewo-media-card__engagement"><?php echo esc_html( $engagement ); ?></p>
				<?php endif; ?>
				<?php if ( $url ) : ?>
					<a class="ewo-media-card__button" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'View Post', 'ewo-youtube-integration' ); ?> <span aria-hidden="true">&rarr;</span>
					</a>
				<?php endif; ?>
			</div>
		</article>
		<?php
	}

	/**
	 * Get optional topic badges for a video, if topic data exists.
	 *
	 * Reads an optional comma-separated `ewo_youtube_topics` meta value and
	 * keeps only the supported topics. Returns an empty array when no topic
	 * data is present (badges are then hidden).
	 *
	 * @param int $post_id Post ID.
	 * @return string[]
	 */
	private function get_card_topics( $post_id ) {
		$raw = get_post_meta( $post_id, 'ewo_youtube_topics', true );

		if ( ! is_string( $raw ) || '' === trim( $raw ) ) {
			return array();
		}

		$allowed = array( 'Geopolitics', 'Strategy', 'Energy', 'Trade', 'Economy' );
		$topics  = array();

		foreach ( explode( ',', $raw ) as $candidate ) {
			$candidate = trim( $candidate );

			foreach ( $allowed as $label ) {
				if ( 0 === strcasecmp( $candidate, $label ) ) {
					$topics[] = $label;
					break;
				}
			}
		}

		return array_values( array_unique( $topics ) );
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
	 * Determine whether a playlist is hidden.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	private function is_hidden_playlist( $post_id ) {
		return (bool) get_post_meta( $post_id, 'ewo_youtube_playlist_hidden', true );
	}

	/**
	 * Get a configured platform link, falling back to the Media Hub anchor.
	 *
	 * @param string $platform Platform key.
	 * @param string $fallback_anchor Section anchor.
	 * @return string
	 */
	private function get_media_hub_link( $platform, $fallback_anchor ) {
		if ( function_exists( 'ewo_2025_get_platform_url' ) ) {
			$url = ewo_2025_get_platform_url( $platform );

			if ( '' !== $url ) {
				return $url;
			}
		}

		return home_url( '/?page_id=19#' . $fallback_anchor );
	}

	/**
	 * Find an active TikTok content post type without registering a new one.
	 *
	 * @return string
	 */
	private function get_tiktok_post_type() {
		foreach ( array( 'ewo_tiktok', 'ewo_tiktok_video', 'tiktok_video', 'tiktok' ) as $post_type ) {
			if ( post_type_exists( $post_type ) ) {
				return $post_type;
			}
		}

		return '';
	}

	/**
	 * Get the first non-empty meta value.
	 *
	 * @param int      $post_id Post ID.
	 * @param string[] $keys    Meta keys.
	 * @return string
	 */
	private function get_first_meta_value( $post_id, $keys ) {
		foreach ( $keys as $key ) {
			$value = get_post_meta( $post_id, $key, true );

			if ( is_string( $value ) && '' !== trim( $value ) ) {
				return trim( $value );
			}
		}

		return '';
	}

	/**
	 * Get a TikTok thumbnail from featured image or supported meta keys.
	 *
	 * @param WP_Post $clip TikTok post.
	 * @return string
	 */
	private function get_tiktok_thumbnail_url( $clip ) {
		$featured = get_the_post_thumbnail_url( $clip, 'large' );
		if ( $featured && $this->is_valid_image_url( $featured ) ) {
			return $featured;
		}

		$stored = $this->get_first_meta_value(
			$clip->ID,
			array(
				'ewo_tiktok_thumbnail',
				'tiktok_thumbnail',
				'thumbnail_url',
				'video_thumbnail',
			)
		);

		if ( $this->is_valid_image_url( $stored ) ) {
			return esc_url_raw( $stored );
		}

		return '';
	}

	/**
	 * Get community engagement text when available.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	private function get_community_engagement( $post_id ) {
		$engagement = $this->get_first_meta_value(
			$post_id,
			array(
				'ewo_youtube_community_engagement',
				'ewo_youtube_engagement',
				'engagement',
			)
		);

		if ( '' !== $engagement ) {
			return $engagement;
		}

		$likes    = $this->get_first_meta_value( $post_id, array( 'ewo_youtube_community_likes', 'likes', 'like_count' ) );
		$comments = $this->get_first_meta_value( $post_id, array( 'ewo_youtube_community_comments', 'comments', 'comment_count' ) );
		$parts    = array();

		if ( '' !== $likes ) {
			$parts[] = sprintf( __( '%s likes', 'ewo-youtube-integration' ), number_format_i18n( (int) $likes ) );
		}

		if ( '' !== $comments ) {
			$parts[] = sprintf( __( '%s comments', 'ewo-youtube-integration' ), number_format_i18n( (int) $comments ) );
		}

		return implode( ' · ', $parts );
	}

	/**
	 * Get the display title for a playlist card.
	 *
	 * @param WP_Post $playlist Playlist post.
	 * @return string
	 */
	private function get_playlist_title( $playlist ) {
		$title = get_post_meta( $playlist->ID, 'ewo_youtube_playlist_title', true );

		if ( is_string( $title ) && '' !== trim( $title ) ) {
			return trim( $title );
		}

		return get_the_title( $playlist );
	}

	/**
	 * Get a playlist video count from supported meta keys.
	 *
	 * @param int $post_id Post ID.
	 * @return int
	 */
	private function get_playlist_video_count( $post_id ) {
		$meta_keys = array(
			'ewo_youtube_playlist_video_count',
			'ewo_youtube_video_count',
			'video_count',
			'item_count',
		);

		foreach ( $meta_keys as $meta_key ) {
			$count = get_post_meta( $post_id, $meta_key, true );

			if ( '' !== $count && is_numeric( $count ) ) {
				return max( 0, (int) $count );
			}
		}

		return 0;
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

	/**
	 * Resolve a working thumbnail URL for a video card.
	 *
	 * Order: featured image, then a stable YouTube thumbnail derived from the
	 * video ID (stored thumbnail URLs are often expiring/signed CDN links),
	 * then a valid stored thumbnail, otherwise empty (caller shows a
	 * placeholder rather than an empty img tag).
	 *
	 * @param WP_Post $video Video post.
	 * @return string
	 */
	private function get_card_thumbnail_url( $video ) {
		$featured = get_the_post_thumbnail_url( $video, 'large' );
		if ( $featured && $this->is_valid_image_url( $featured ) ) {
			return $featured;
		}

		$video_id = get_post_meta( $video->ID, 'ewo_youtube_video_id', true );
		if ( $video_id ) {
			return 'https://img.youtube.com/vi/' . rawurlencode( $video_id ) . '/hqdefault.jpg';
		}

		$stored = get_post_meta( $video->ID, 'ewo_youtube_thumbnail', true );
		if ( $this->is_valid_image_url( $stored ) ) {
			return esc_url_raw( $stored );
		}

		return '';
	}

	/**
	 * Validate an image URL: http(s) and a real image extension or a known image CDN.
	 *
	 * @param string $url URL.
	 * @return bool
	 */
	private function is_valid_image_url( $url ) {
		$url = trim( (string) $url );

		if ( '' === $url || 0 === stripos( $url, 'data:' ) ) {
			return false;
		}

		if ( ! preg_match( '#^https?://#i', $url ) ) {
			return false;
		}

		if ( preg_match( '#\.(jpe?g|png|gif|webp|avif)(\?.*)?$#i', $url ) ) {
			return true;
		}

		foreach ( array( 'ytimg.com', 'img.youtube.com', 'substackcdn.com', 'substack-post-media', 'amazonaws.com' ) as $host ) {
			if ( false !== stripos( $url, $host ) ) {
				return true;
			}
		}

		return false;
	}
}
