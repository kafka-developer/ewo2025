<?php
/**
 * Front page template — EWO FINAL V1 homepage architecture.
 *
 * Section order:
 *  1. Hero
 *  2. Featured Analysis (YouTube carousel)
 *  3. The EWO Method
 *  4. Community Posts
 *  5. Strategic Domains (Smart Feed output)
 *  6. Strategic Predictions
 *  7. Latest Analysis (Substack)
 *  8. Strategic Playlists
 *  9. Connect With EWO
 * 10. Book
 *
 * @package EWO_2025
 */

get_header();

$ewo_2025_newsletter_url  = ewo_2025_get_platform_url( 'newsletter' );
$ewo_2025_amazon_book_url = ewo_2025_get_platform_url( 'amazon_book' );
$ewo_2025_analysis_page   = get_page_by_path( 'analysis' );
$ewo_2025_analysis_url    = $ewo_2025_analysis_page ? get_permalink( $ewo_2025_analysis_page ) : add_query_arg( 'page_id', 17, home_url( '/' ) );
$ewo_2025_videos_page     = get_page_by_path( 'videos' );
$ewo_2025_videos_url      = $ewo_2025_videos_page ? get_permalink( $ewo_2025_videos_page ) : home_url( '/videos/' );
?>

<!-- EWO Theme Version: <?php echo esc_html( EWO_THEME_VERSION ); ?> -->
<main id="primary" class="site-main site-main--home">

	<?php // 1. HERO. ?>
	<section class="ewo-hero" style="--ewo-hero-image: url('<?php echo esc_url( get_template_directory_uri() . '/assets/images/ewo-banner.png' ); ?>');">
		<div class="ewo-hero__overlay"></div>
		<div class="ewo-hero__inner">
			<div class="ewo-hero__content">
				<p class="ewo-kicker"><?php esc_html_e( 'Geopolitical Intelligence Publication', 'ewo-2025' ); ?></p>
				<h1><?php esc_html_e( 'Understanding The Systems Shaping Global Power', 'ewo-2025' ); ?></h1>
				<p class="ewo-hero__lede"><?php esc_html_e( 'EWO tracks the dependencies, vulnerabilities, responses, and consequences behind global power shifts.', 'ewo-2025' ); ?></p>
				<div class="ewo-hero__actions">
					<a class="ewo-button ewo-button--gold" href="<?php echo esc_url( $ewo_2025_analysis_url ); ?>"><?php esc_html_e( 'Read Latest Analysis', 'ewo-2025' ); ?></a>
					<a class="ewo-button ewo-button--ghost" href="#strategic-domains"><?php esc_html_e( 'Explore Strategic Domains', 'ewo-2025' ); ?></a>
				</div>
			</div>
		</div>
	</section>

	<div class="ewo-home-layout">
		<div class="ewo-home-main">

	<?php // 2. FEATURED ANALYSIS — latest YouTube videos (carousel). ?>
	<?php if ( function_exists( 'ewo_youtube_marquee' ) ) : ?>
		<section id="featured-analysis" class="ewo-section ewo-home-featured">
			<div class="ewo-section__header">
				<div class="ewo-section__header-copy">
					<p class="ewo-kicker"><?php esc_html_e( 'Video Intelligence', 'ewo-2025' ); ?></p>
					<h2><?php esc_html_e( 'Featured Analysis', 'ewo-2025' ); ?></h2>
				</div>
				<a class="ewo-button ewo-button--ghost ewo-section__cta" href="<?php echo esc_url( $ewo_2025_videos_url ); ?>"><?php esc_html_e( 'All Videos', 'ewo-2025' ); ?></a>
			</div>
			<?php echo ewo_youtube_marquee(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Plugin returns pre-escaped markup. ?>
		</section>
	<?php endif; ?>

	<?php // 3. THE EWO METHOD — two-column. ?>
	<section id="ewo-method" class="ewo-section ewo-method-v1">
		<div class="ewo-section__header">
			<div class="ewo-section__header-copy">
				<p class="ewo-kicker"><?php esc_html_e( 'Methodology', 'ewo-2025' ); ?></p>
				<h2><?php esc_html_e( 'The EWO Method', 'ewo-2025' ); ?></h2>
			</div>
		</div>
		<div class="ewo-method-v1__grid">
			<div class="ewo-method-v1__narrative">
				<p class="ewo-method-v1__lede"><?php esc_html_e( 'Most analysis follows events. EWO follows systems.', 'ewo-2025' ); ?></p>
				<p class="ewo-method-v1__lede"><?php esc_html_e( 'News is temporary. Dependencies are persistent.', 'ewo-2025' ); ?></p>
				<p class="ewo-method-v1__note"><?php esc_html_e( 'Every briefing follows four questions:', 'ewo-2025' ); ?></p>
			</div>
			<ol class="ewo-method-v1__flow">
				<?php
				$ewo_2025_method = array(
					array( 'title' => __( 'Dependency', 'ewo-2025' ), 'desc' => __( 'What the system relies on.', 'ewo-2025' ) ),
					array( 'title' => __( 'Vulnerability', 'ewo-2025' ), 'desc' => __( 'Where that reliance can break.', 'ewo-2025' ) ),
					array( 'title' => __( 'Response', 'ewo-2025' ), 'desc' => __( 'How actors move to protect or exploit it.', 'ewo-2025' ) ),
					array( 'title' => __( 'Consequence', 'ewo-2025' ), 'desc' => __( 'The shift in power that follows.', 'ewo-2025' ) ),
				);
				foreach ( $ewo_2025_method as $ewo_2025_i => $ewo_2025_stage ) :
					?>
					<li class="ewo-method-v1__step">
						<span class="ewo-method-v1__num"><?php echo esc_html( str_pad( (string) ( $ewo_2025_i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
						<span class="ewo-method-v1__step-body">
							<span class="ewo-method-v1__step-title"><?php echo esc_html( $ewo_2025_stage['title'] ); ?></span>
							<span class="ewo-method-v1__step-desc"><?php echo esc_html( $ewo_2025_stage['desc'] ); ?></span>
						</span>
					</li>
					<?php
				endforeach;
				?>
			</ol>
		</div>
	</section>

	<?php
	// 4. COMMUNITY POSTS — native short-form posts authored on the site.
	$ewo_2025_community = new WP_Query(
		array(
			'post_type'           => 'ewo_community_post',
			'posts_per_page'      => 6,
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true,
		)
	);
	if ( $ewo_2025_community->have_posts() ) :
		?>
		<section id="community" class="ewo-section">
			<div class="ewo-section__header">
				<div class="ewo-section__header-copy">
					<p class="ewo-kicker"><?php esc_html_e( 'Community', 'ewo-2025' ); ?></p>
					<h2><?php esc_html_e( 'From the EWO Community', 'ewo-2025' ); ?></h2>
				</div>
			</div>
			<div class="ewo-community-grid">
				<?php
				while ( $ewo_2025_community->have_posts() ) :
					$ewo_2025_community->the_post();
					$ewo_2025_c_text = get_the_excerpt();
					if ( '' === trim( (string) $ewo_2025_c_text ) ) {
						$ewo_2025_c_text = wp_strip_all_tags( strip_shortcodes( get_the_content() ) );
					}
					?>
					<article class="ewo-community-card">
						<p class="ewo-community-card__meta">
							<span class="ewo-community-card__handle"><?php esc_html_e( '@EmergingWO', 'ewo-2025' ); ?></span>
							<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
						</p>
						<?php if ( get_the_title() ) : ?>
							<h3 class="ewo-community-card__title"><?php the_title(); ?></h3>
						<?php endif; ?>
						<p class="ewo-community-card__text"><?php echo esc_html( wp_trim_words( $ewo_2025_c_text, 30 ) ); ?></p>
						<a class="ewo-community-card__link" href="<?php the_permalink(); ?>"><?php esc_html_e( 'View post', 'ewo-2025' ); ?> &rarr;</a>
					</article>
					<?php
				endwhile;
				wp_reset_postdata();
				?>
			</div>
		</section>
		<?php
	endif;
	?>

	<?php // 5. STRATEGIC DOMAINS — Smart Feed output (derived from imported analysis). ?>
	<section id="strategic-domains" class="ewo-section">
		<div class="ewo-section__header">
			<div class="ewo-section__header-copy">
				<p class="ewo-kicker"><?php esc_html_e( 'Smart Feed Intelligence', 'ewo-2025' ); ?></p>
				<h2><?php esc_html_e( 'Strategic Domains', 'ewo-2025' ); ?></h2>
			</div>
		</div>
		<div class="ewo-topic-grid ewo-domains-grid">
			<?php foreach ( ewo_2025_strategic_domains() as $ewo_2025_domain ) : ?>
				<article class="ewo-topic-card ewo-domain-card ewo-domain-card--<?php echo esc_attr( $ewo_2025_domain['visual'] ); ?>">
					<h3><?php echo esc_html( $ewo_2025_domain['name'] ); ?></h3>
					<p class="ewo-domain-card__desc"><?php echo esc_html( $ewo_2025_domain['description'] ); ?></p>
					<?php if ( ! empty( $ewo_2025_domain['headlines'] ) ) : ?>
						<ul class="ewo-domain-feed">
							<?php foreach ( $ewo_2025_domain['headlines'] as $ewo_2025_headline ) : ?>
								<li class="ewo-domain-feed__item">
									<span class="ewo-domain-feed__score">[<?php echo esc_html( $ewo_2025_headline['score'] ); ?>]</span>
									<a class="ewo-domain-feed__title" href="<?php echo esc_url( $ewo_2025_headline['url'] ); ?>"><?php echo esc_html( $ewo_2025_headline['title'] ); ?></a>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php else : ?>
						<p class="ewo-domain-feed__empty"><?php esc_html_e( 'Awaiting Smart Feed signals.', 'ewo-2025' ); ?></p>
					<?php endif; ?>
				</article>
			<?php endforeach; ?>
		</div>
	</section>

	<?php
	// 6. STRATEGIC PREDICTIONS — forecast cards (CPT-backed, with seed fallback).
	$ewo_2025_predictions = new WP_Query(
		array(
			'post_type'      => 'ewo_prediction',
			'posts_per_page' => 6,
			'post_status'    => 'publish',
		)
	);
	?>
	<section id="strategic-predictions" class="ewo-section">
		<div class="ewo-section__header">
			<div class="ewo-section__header-copy">
				<p class="ewo-kicker"><?php esc_html_e( 'Forecasts', 'ewo-2025' ); ?></p>
				<h2><?php esc_html_e( 'Strategic Predictions', 'ewo-2025' ); ?></h2>
			</div>
		</div>
		<div class="ewo-predictions-grid">
			<?php if ( $ewo_2025_predictions->have_posts() ) : ?>
				<?php
				while ( $ewo_2025_predictions->have_posts() ) :
					$ewo_2025_predictions->the_post();
					?>
					<article class="ewo-prediction-card">
						<p class="ewo-prediction-card__tag"><?php esc_html_e( 'Forecast', 'ewo-2025' ); ?></p>
						<h3 class="ewo-prediction-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
						<p class="ewo-prediction-card__text"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 24 ) ); ?></p>
					</article>
					<?php
				endwhile;
				wp_reset_postdata();
				?>
			<?php else : ?>
				<?php
				$ewo_2025_seed_predictions = array(
					array(
						'title' => __( 'The Next Energy Bottleneck', 'ewo-2025' ),
						'text'  => __( 'Where the next chokepoint forms as supply routes and refining capacity realign.', 'ewo-2025' ),
					),
					array(
						'title' => __( 'The Future of Maritime Trade', 'ewo-2025' ),
						'text'  => __( 'How corridors, insurance, and naval posture reshape who controls global shipping.', 'ewo-2025' ),
					),
					array(
						'title' => __( 'The Coming Payment System Shift', 'ewo-2025' ),
						'text'  => __( 'The slow migration away from a single settlement layer and what replaces it.', 'ewo-2025' ),
					),
				);
				foreach ( $ewo_2025_seed_predictions as $ewo_2025_seed ) :
					?>
					<article class="ewo-prediction-card ewo-prediction-card--seed">
						<p class="ewo-prediction-card__tag"><?php esc_html_e( 'Forecast', 'ewo-2025' ); ?></p>
						<h3 class="ewo-prediction-card__title"><?php echo esc_html( $ewo_2025_seed['title'] ); ?></h3>
						<p class="ewo-prediction-card__text"><?php echo esc_html( $ewo_2025_seed['text'] ); ?></p>
					</article>
					<?php
				endforeach;
				?>
			<?php endif; ?>
		</div>
	</section>

	<?php
	// 7. LATEST ANALYSIS — Substack / imported posts. Featured large + smaller grid.
	$ewo_2025_latest = new WP_Query(
		array(
			'post_type'           => 'post',
			'posts_per_page'      => 5,
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true,
		)
	);
	if ( $ewo_2025_latest->have_posts() ) :
		?>
		<section id="latest-analysis" class="ewo-section">
			<div class="ewo-section__header ewo-section__header--analysis">
				<div class="ewo-section__header-copy">
					<p class="ewo-kicker"><?php esc_html_e( 'Research & Analysis', 'ewo-2025' ); ?></p>
					<h2><?php esc_html_e( 'Latest Analysis', 'ewo-2025' ); ?></h2>
				</div>
				<a class="ewo-button ewo-button--ghost ewo-section__cta" href="<?php echo esc_url( $ewo_2025_analysis_url ); ?>"><?php esc_html_e( 'View All Analysis', 'ewo-2025' ); ?></a>
			</div>
			<div class="ewo-article-grid ewo-analysis-grid">
				<?php
				$ewo_2025_index = 0;
				while ( $ewo_2025_latest->have_posts() ) :
					$ewo_2025_latest->the_post();
					$ewo_2025_featured        = ( 0 === $ewo_2025_index );
					$ewo_2025_subscriber_only = ewo_2025_is_subscriber_only_post( get_post() );
					$ewo_2025_substack_url    = ewo_2025_substack_source_url( get_post() );
					$ewo_2025_excerpt         = $ewo_2025_subscriber_only ? ewo_2025_subscriber_preview_text( get_post() ) : get_the_excerpt();
					$ewo_2025_card_classes    = 'ewo-article-card ewo-briefing-card';
					if ( $ewo_2025_featured ) {
						$ewo_2025_card_classes .= ' ewo-briefing-card--featured';
					}
					if ( $ewo_2025_subscriber_only ) {
						$ewo_2025_card_classes .= ' ewo-briefing-card--subscriber';
					}
					?>
					<?php if ( 1 === $ewo_2025_index ) : ?>
						<div class="ewo-analysis-grid__secondary">
					<?php endif; ?>
					<article <?php post_class( $ewo_2025_card_classes ); ?>>
						<a class="ewo-briefing-card__media" href="<?php the_permalink(); ?>" aria-label="<?php the_title_attribute(); ?>">
							<?php if ( has_post_thumbnail() ) : ?>
								<?php the_post_thumbnail( $ewo_2025_featured ? 'large' : 'medium_large' ); ?>
							<?php else : ?>
								<span class="ewo-briefing-card__placeholder" aria-hidden="true"></span>
							<?php endif; ?>
						</a>
						<div class="ewo-briefing-card__body">
							<?php if ( $ewo_2025_subscriber_only ) : ?>
								<span class="ewo-subscriber-badge"><?php esc_html_e( '🔒 Subscriber Only', 'ewo-2025' ); ?></span>
							<?php endif; ?>
							<p class="ewo-card-meta"><span><?php esc_html_e( 'Briefing', 'ewo-2025' ); ?></span><?php echo esc_html( get_the_date() ); ?></p>
							<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
							<p><?php echo esc_html( wp_trim_words( $ewo_2025_excerpt, $ewo_2025_featured ? 34 : 18 ) ); ?></p>
							<?php if ( ! $ewo_2025_featured ) : ?>
								<a class="ewo-briefing-card__more" href="<?php the_permalink(); ?>"><?php esc_html_e( 'Read More', 'ewo-2025' ); ?> &rarr;</a>
							<?php endif; ?>
							<?php if ( $ewo_2025_featured && $ewo_2025_subscriber_only && $ewo_2025_substack_url ) : ?>
								<a class="ewo-button ewo-button--gold ewo-substack-button" href="<?php echo esc_url( $ewo_2025_substack_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Read on Substack', 'ewo-2025' ); ?> &rarr;</a>
							<?php endif; ?>
						</div>
					</article>
					<?php
					++$ewo_2025_index;
				endwhile;
				if ( $ewo_2025_index > 1 ) {
					echo '</div>'; // Close .ewo-analysis-grid__secondary.
				}
				wp_reset_postdata();
				?>
			</div>
		</section>
		<?php
	endif;
	?>

	<?php // 8. STRATEGIC PLAYLISTS — YouTube playlists. ?>
	<?php if ( function_exists( 'ewo_youtube_playlists' ) ) : ?>
		<section id="strategic-playlists" class="ewo-section ewo-home-playlists">
			<div class="ewo-section__header">
				<div class="ewo-section__header-copy">
					<p class="ewo-kicker"><?php esc_html_e( 'Curated Series', 'ewo-2025' ); ?></p>
					<h2><?php esc_html_e( 'Strategic Playlists', 'ewo-2025' ); ?></h2>
				</div>
			</div>
			<?php echo ewo_youtube_playlists(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Plugin returns pre-escaped markup. ?>
		</section>
	<?php endif; ?>

	<?php // 9. CONNECT WITH EWO — platform cards with optional follower counts. ?>
	<section id="connect" class="ewo-section">
		<div class="ewo-section__header">
			<div class="ewo-section__header-copy">
				<p class="ewo-kicker"><?php esc_html_e( 'Platform Network', 'ewo-2025' ); ?></p>
				<h2><?php esc_html_e( 'Connect With EWO', 'ewo-2025' ); ?></h2>
			</div>
		</div>
		<div class="ewo-connect-grid">
			<?php
			$ewo_2025_connect = array(
				'youtube'  => array(
					'name'  => __( 'YouTube', 'ewo-2025' ),
					'label' => __( 'Watch & Subscribe', 'ewo-2025' ),
					'icon'  => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.96-1.96C18.85 4 12 4 12 4s-6.85 0-8.58.46a2.78 2.78 0 0 0-1.96 1.96A29.1 29.1 0 0 0 1 12a29.1 29.1 0 0 0 .46 5.58 2.78 2.78 0 0 0 1.96 1.96C5.15 20 12 20 12 20s6.85 0 8.58-.46a2.78 2.78 0 0 0 1.96-1.96A29.1 29.1 0 0 0 23 12a29.1 29.1 0 0 0-.46-5.58Z"/><path d="m10 15 5.2-3L10 9v6Z" fill="#071426"/></svg>',
				),
				'spotify'  => array(
					'name'  => __( 'Spotify', 'ewo-2025' ),
					'label' => __( 'Listen to Podcasts', 'ewo-2025' ),
					'icon'  => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm4.58 14.43a.76.76 0 0 1-1.04.25c-2.85-1.74-6.44-2.13-10.66-1.17a.76.76 0 0 1-.34-1.49c4.62-1.05 8.6-.6 11.79 1.35.36.22.47.7.25 1.06Zm1.22-2.72a.96.96 0 0 1-1.32.32c-3.26-2-8.24-2.58-12.1-1.41a.96.96 0 1 1-.56-1.84c4.42-1.34 9.91-.69 13.66 1.61.45.28.6.87.32 1.32Zm.1-2.84C14 8.56 7.58 8.35 3.86 9.48a1.15 1.15 0 1 1-.67-2.2c4.27-1.3 11.38-1.05 15.88 1.62a1.15 1.15 0 0 1-1.17 1.97Z"/></svg>',
				),
				'x'        => array(
					'name'  => __( 'X', 'ewo-2025' ),
					'label' => __( 'Latest Updates', 'ewo-2025' ),
					'icon'  => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M17.58 3h3.05l-6.66 7.62L21.8 21h-6.13l-4.8-6.28L5.38 21H2.31l7.13-8.15L1.93 3h6.28l4.34 5.74L17.58 3Zm-1.07 16.17h1.69L7.29 4.73H5.48l11.03 14.44Z"/></svg>',
				),
				'substack' => array(
					'name'  => __( 'Substack', 'ewo-2025' ),
					'label' => __( 'Read Analysis', 'ewo-2025' ),
					'icon'  => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 3h16v2.6H4V3Zm0 4.4h16V10H4V7.4Zm0 4.4h16V21l-8-4.4L4 21v-9.2Z"/></svg>',
				),
			);

			$ewo_2025_connect_kses = array(
				'svg'  => array( 'viewbox' => true, 'aria-hidden' => true ),
				'path' => array( 'd' => true, 'fill' => true ),
			);

			foreach ( $ewo_2025_connect as $ewo_2025_key => $ewo_2025_platform ) :
				$ewo_2025_url   = ewo_2025_get_platform_url( $ewo_2025_key );
				$ewo_2025_count = ewo_2025_platform_follower_count( $ewo_2025_key );
				$ewo_2025_tag   = '' !== $ewo_2025_url ? 'a' : 'span';
				?>
				<<?php echo esc_html( $ewo_2025_tag ); ?> class="ewo-connect-card ewo-connect-card--<?php echo esc_attr( $ewo_2025_key ); ?><?php echo '' === $ewo_2025_url ? ' ewo-connect-card--disabled' : ''; ?>"<?php echo '' !== $ewo_2025_url ? ' href="' . esc_url( $ewo_2025_url ) . '" target="_blank" rel="noopener noreferrer"' : ' aria-disabled="true"'; ?>>
					<span class="ewo-connect-card__icon"><?php echo wp_kses( $ewo_2025_platform['icon'], $ewo_2025_connect_kses ); ?></span>
					<span class="ewo-connect-card__name"><?php echo esc_html( $ewo_2025_platform['name'] ); ?></span>
					<?php if ( '' !== $ewo_2025_count ) : ?>
						<span class="ewo-connect-card__count"><?php echo esc_html( $ewo_2025_count ); ?></span>
					<?php endif; ?>
					<span class="ewo-connect-card__label"><?php echo esc_html( $ewo_2025_platform['label'] ); ?></span>
				</<?php echo esc_html( $ewo_2025_tag ); ?>>
			<?php endforeach; ?>
		</div>
	</section>

	<?php // 10. BOOK. ?>
	<section id="book" class="ewo-section ewo-book-section ewo-book-v1">
		<div class="ewo-book-v1__cover" aria-hidden="true">
			<span class="ewo-book-v1__cover-kicker"><?php esc_html_e( 'Emerging World Order', 'ewo-2025' ); ?></span>
			<span class="ewo-book-v1__cover-year"><?php esc_html_e( '2025', 'ewo-2025' ); ?></span>
		</div>
		<div class="ewo-book-section__content">
			<p class="ewo-kicker"><?php esc_html_e( 'The Book', 'ewo-2025' ); ?></p>
			<h2><?php esc_html_e( 'A Framework for Reading Global Power', 'ewo-2025' ); ?></h2>
			<p class="ewo-book-v1__name"><?php esc_html_e( 'Emerging World Order 2025', 'ewo-2025' ); ?></p>
			<p><?php esc_html_e( 'A framework for reading global power through systems, dependencies, and the fault lines that decide outcomes — the same lens behind every EWO briefing.', 'ewo-2025' ); ?></p>
			<?php if ( $ewo_2025_amazon_book_url ) : ?>
				<a class="ewo-button ewo-button--gold" href="<?php echo esc_url( $ewo_2025_amazon_book_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Get the Book on Amazon', 'ewo-2025' ); ?></a>
			<?php endif; ?>
		</div>
	</section>

		</div><!-- .ewo-home-main -->
		<?php ewo_2025_sidebar(); ?>
	</div><!-- .ewo-home-layout -->

</main>

<?php
get_footer();
