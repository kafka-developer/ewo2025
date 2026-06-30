<?php
/**
 * Homepage Section Renderers — standalone render functions for every homepage section.
 *
 * Feature-visibility checks (ewo_2025_feature_enabled) are the CALLER's responsibility
 * (front-page.php loop). Render functions only perform internal data checks (e.g.,
 * "do we have posts/data to show?") and return early when there is nothing to render.
 *
 * @package EWO_2025
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render Section 1: Hero.
 */
function ewo_2025_render_section_hero() {
	$analysis_page = get_page_by_path( 'analysis' );
	$analysis_url  = $analysis_page ? get_permalink( $analysis_page ) : add_query_arg( 'page_id', 17, home_url( '/' ) );
	?>
	<section class="ewo-hero" style="--ewo-hero-image: url('<?php echo esc_url( get_template_directory_uri() . '/assets/images/ewo-banner.png' ); ?>');">
		<div class="ewo-hero__overlay"></div>
		<div class="ewo-hero__inner">
			<div class="ewo-hero__content">
				<p class="ewo-kicker"><?php esc_html_e( 'Geopolitical Intelligence Publication', 'ewo-2025' ); ?></p>
				<h1><?php esc_html_e( 'Understanding The Systems Shaping Global Power', 'ewo-2025' ); ?></h1>
				<p class="ewo-hero__lede"><?php esc_html_e( 'EWO tracks the dependencies, vulnerabilities, responses, and consequences behind global power shifts.', 'ewo-2025' ); ?></p>
				<div class="ewo-hero__actions">
					<a class="ewo-button ewo-button--gold" href="<?php echo esc_url( $analysis_url ); ?>"><?php esc_html_e( 'Read Latest Analysis', 'ewo-2025' ); ?></a>
					<a class="ewo-button ewo-button--ghost" href="#strategic-domains"><?php esc_html_e( 'Explore Strategic Domains', 'ewo-2025' ); ?></a>
				</div>
			</div>
		</div>
	</section>
	<?php
}

/**
 * Render Section 2: Featured Analysis (YouTube carousel).
 *
 * Returns early if the ewo_youtube_marquee function is not available.
 */
function ewo_2025_render_section_featured_analysis() {
	if ( ! function_exists( 'ewo_youtube_marquee' ) ) {
		return;
	}
	$videos_page = get_page_by_path( 'videos' );
	$videos_url  = $videos_page ? get_permalink( $videos_page ) : home_url( '/videos/' );
	?>
	<section id="featured-analysis" class="ewo-section ewo-home-featured">
		<div class="ewo-section__header">
			<div class="ewo-section__header-copy">
				<p class="ewo-kicker"><?php esc_html_e( 'Video Intelligence', 'ewo-2025' ); ?></p>
				<h2><?php esc_html_e( 'Featured Analysis', 'ewo-2025' ); ?></h2>
			</div>
			<a class="ewo-button ewo-button--ghost ewo-section__cta" href="<?php echo esc_url( $videos_url ); ?>"><?php esc_html_e( 'All Videos', 'ewo-2025' ); ?></a>
		</div>
		<?php echo ewo_youtube_marquee(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Plugin returns pre-escaped markup. ?>
	</section>
	<?php
}

/**
 * Render Section 3: The EWO Method — two-column.
 */
function ewo_2025_render_section_ewo_method() {
	$method = array(
		array( 'title' => __( 'Dependency', 'ewo-2025' ), 'desc' => __( 'What the system relies on.', 'ewo-2025' ) ),
		array( 'title' => __( 'Vulnerability', 'ewo-2025' ), 'desc' => __( 'Where that reliance can break.', 'ewo-2025' ) ),
		array( 'title' => __( 'Response', 'ewo-2025' ), 'desc' => __( 'How actors move to protect or exploit it.', 'ewo-2025' ) ),
		array( 'title' => __( 'Consequence', 'ewo-2025' ), 'desc' => __( 'The shift in power that follows.', 'ewo-2025' ) ),
	);
	?>
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
				<?php foreach ( $method as $i => $stage ) : ?>
					<li class="ewo-method-v1__step">
						<span class="ewo-method-v1__num"><?php echo esc_html( str_pad( (string) ( $i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
						<span class="ewo-method-v1__step-body">
							<span class="ewo-method-v1__step-title"><?php echo esc_html( $stage['title'] ); ?></span>
							<span class="ewo-method-v1__step-desc"><?php echo esc_html( $stage['desc'] ); ?></span>
						</span>
					</li>
				<?php endforeach; ?>
			</ol>
		</div>
	</section>
	<?php
}

/**
 * Render Section 4: Community Posts wall.
 *
 * Renders nothing if no ewo_community_post posts exist.
 * Uses local WP_Query variable — does not touch globals.
 */
function ewo_2025_render_section_community_wall() {
	$community_q = new WP_Query(
		array(
			'post_type'           => 'ewo_community_post',
			'posts_per_page'      => 6,
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true,
		)
	);
	if ( ! $community_q->have_posts() ) {
		return;
	}
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
			while ( $community_q->have_posts() ) :
				$community_q->the_post();
				$c_text = get_the_excerpt();
				if ( '' === trim( (string) $c_text ) ) {
					$c_text = wp_strip_all_tags( strip_shortcodes( get_the_content() ) );
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
					<p class="ewo-community-card__text"><?php echo esc_html( wp_trim_words( $c_text, 30 ) ); ?></p>
					<a class="ewo-community-card__link" href="<?php the_permalink(); ?>"><?php esc_html_e( 'View post', 'ewo-2025' ); ?> &rarr;</a>
				</article>
				<?php
			endwhile;
			wp_reset_postdata();
			?>
		</div>
	</section>
	<?php
}

/**
 * Render Section 5: Strategic Domains — live data from EWO RSS Engine plugin.
 *
 * Renders nothing if no domain data is available.
 */
function ewo_2025_render_section_strategic_domains() {
	$hp_domains = function_exists( 'ewo_2025_sfd_index_data' ) ? ewo_2025_sfd_index_data() : array();
	$dom_count  = max( 0, (int) ewo_2025_hps_get()['domains_count'] );
	if ( $dom_count > 0 ) {
		$hp_domains = array_slice( $hp_domains, 0, $dom_count );
	}
	if ( empty( $hp_domains ) ) {
		return;
	}
	?>
	<section id="strategic-domains" class="ewo-section">
		<div class="ewo-section__header">
			<div class="ewo-section__header-copy">
				<p class="ewo-kicker"><?php esc_html_e( 'Smart Feed Intelligence', 'ewo-2025' ); ?></p>
				<h2><?php esc_html_e( 'Strategic Domains', 'ewo-2025' ); ?></h2>
			</div>
			<a class="ewo-button ewo-button--ghost ewo-section__cta"
			   href="<?php echo esc_url( home_url( '/strategic-domains/' ) ); ?>">
				<?php esc_html_e( 'All Domains →', 'ewo-2025' ); ?>
			</a>
		</div>
		<div class="ewo-topic-grid ewo-domains-grid">
			<?php foreach ( $hp_domains as $hp_d ) :
				$hp_url = home_url( '/strategic-domains/' . $hp_d['slug'] . '/' );
			?>
				<article class="ewo-topic-card ewo-domain-card">
					<a href="<?php echo esc_url( $hp_url ); ?>"
					   class="ewo-domain-card__full-link" aria-hidden="true" tabindex="-1"></a>

					<h3>
						<a href="<?php echo esc_url( $hp_url ); ?>">
							<?php echo esc_html( $hp_d['name'] ); ?>
						</a>
					</h3>

					<?php if ( ! empty( $hp_d['description'] ) ) : ?>
						<p class="ewo-domain-card__desc"><?php echo esc_html( $hp_d['description'] ); ?></p>
					<?php endif; ?>

					<?php if ( ! empty( $hp_d['sources'] ) ) : ?>
						<ul class="ewo-domain-feed">
							<?php foreach ( $hp_d['sources'] as $hp_src ) :
								$hp_ts  = ! empty( $hp_src->published_at ) && '0000-00-00 00:00:00' !== $hp_src->published_at
									? $hp_src->published_at
									: $hp_src->fetched_at;
								$hp_ago = function_exists( 'ewo_2025_time_ago' ) ? ewo_2025_time_ago( $hp_ts ) : '';
							?>
								<li class="ewo-domain-feed__item">
									<a class="ewo-domain-feed__title"
									   href="<?php echo esc_url( $hp_src->url ); ?>"
									   target="_blank" rel="noopener noreferrer">
										<?php echo esc_html( wp_trim_words( $hp_src->title, 11 ) ); ?>
									</a>
									<span class="ewo-domain-feed__meta">
										<?php echo esc_html( $hp_src->source_domain ); ?>
										<?php if ( $hp_ago ) : ?>
											&middot; <?php echo esc_html( $hp_ago ); ?>
										<?php endif; ?>
									</span>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php else : ?>
						<p class="ewo-domain-feed__empty"><?php esc_html_e( 'Awaiting Smart Feed signals.', 'ewo-2025' ); ?></p>
					<?php endif; ?>

					<div class="ewo-domain-card__footer">
						<span class="ewo-domain-card__stat">
							<strong><?php echo esc_html( (string) $hp_d['subdomain_count'] ); ?></strong>
							<?php esc_html_e( 'Subdomains', 'ewo-2025' ); ?>
						</span>
						<span class="ewo-domain-card__stat">
							<strong><?php echo esc_html( (string) $hp_d['keyword_count'] ); ?></strong>
							<?php esc_html_e( 'Keywords', 'ewo-2025' ); ?>
						</span>
						<span class="ewo-domain-card__stat">
							<strong><?php echo esc_html( (string) $hp_d['feed_count'] ); ?></strong>
							<?php esc_html_e( 'Feeds', 'ewo-2025' ); ?>
						</span>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	</section>
	<?php
}

/**
 * Render Section 6: Strategic Predictions.
 *
 * Renders nothing if no predictions are available.
 */
function ewo_2025_render_section_predictions() {
	$pred_count = max( 1, min( 20, (int) ewo_2025_hps_get()['predictions_count'] ) );
	$preds      = array();
	if ( class_exists( 'EWO_Predictions_DB' ) ) {
		$preds = EWO_Predictions_DB::query(
			array(
				'status'  => '',
				'limit'   => $pred_count * 2,
				'orderby' => 'id',
				'order'   => 'DESC',
			)
		);
		$preds = array_values( array_filter( $preds, function ( $p ) {
			return 'archived' !== $p->status;
		} ) );
		$preds = array_slice( $preds, 0, $pred_count );
	}
	if ( empty( $preds ) ) {
		return;
	}
	$pred_status_colors = array(
		'active'   => '#4ade80',
		'tracking' => '#60a5fa',
		'hit'      => '#86efac',
		'missed'   => '#f87171',
		'partial'  => '#fde047',
	);
	?>
	<section id="strategic-predictions" class="ewo-section">
		<div class="ewo-section__header">
			<div class="ewo-section__header-copy">
				<p class="ewo-kicker"><?php esc_html_e( 'Forecasts', 'ewo-2025' ); ?></p>
				<h2><?php esc_html_e( 'Strategic Predictions', 'ewo-2025' ); ?></h2>
			</div>
			<a class="ewo-button ewo-button--ghost ewo-section__cta"
			   href="<?php echo esc_url( home_url( '/predictions/' ) ); ?>">
				<?php esc_html_e( 'All Predictions →', 'ewo-2025' ); ?>
			</a>
		</div>
		<div class="ewo-predictions-grid">
			<?php foreach ( $preds as $p ) :
				$detail_url = home_url( '/predictions/' . (int) $p->id . '/' );
				$conf       = (int) $p->confidence_score;
				$conf_color = $conf >= 80 ? '#4ade80' : ( $conf >= 60 ? '#d7a84b' : '#f87171' );
				$stat_color = isset( $pred_status_colors[ $p->status ] ) ? $pred_status_colors[ $p->status ] : '#aebbcc';
			?>
				<article class="ewo-prediction-card">
					<a href="<?php echo esc_url( $detail_url ); ?>" class="ewo-prediction-card__overlay" aria-hidden="true" tabindex="-1"></a>

					<div class="ewo-prediction-card__meta-row">
						<p class="ewo-prediction-card__tag">
							<?php echo esc_html( $p->prediction_type ?: __( 'Forecast', 'ewo-2025' ) ); ?>
						</p>
						<span class="ewo-prediction-card__status-dot" style="background:<?php echo esc_attr( $stat_color ); ?>;"
						      title="<?php echo esc_attr( ucfirst( $p->status ) ); ?>"></span>
					</div>

					<h3 class="ewo-prediction-card__title">
						<a href="<?php echo esc_url( $detail_url ); ?>"
						   class="ewo-prediction-card__title-link">
							<?php echo esc_html( $p->title ); ?>
						</a>
					</h3>

					<p class="ewo-prediction-card__text">
						<?php echo esc_html( wp_trim_words( $p->prediction_statement, 22 ) ); ?>
					</p>

					<div class="ewo-prediction-card__footer">
						<?php if ( ! empty( $p->domain_name ) ) : ?>
							<span class="ewo-prediction-card__domain"><?php echo esc_html( $p->domain_name ); ?></span>
						<?php endif; ?>
						<span class="ewo-prediction-card__confidence" style="color:<?php echo esc_attr( $conf_color ); ?>;">
							<?php echo esc_html( $conf . '%' ); ?>
						</span>
						<?php if ( ! empty( $p->target_date ) ) : ?>
							<span class="ewo-prediction-card__date">
								<?php echo esc_html( wp_date( 'M Y', strtotime( $p->target_date ) ) ); ?>
							</span>
						<?php endif; ?>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	</section>
	<?php
}

/**
 * Render Section 7: Latest Analysis — source-filtered, count-limited, custom-card aware.
 *
 * Renders nothing if no posts, custom cards, or dynamic cards are available.
 * Requires global $post for setup_postdata().
 */
function ewo_2025_render_section_latest_analysis() {
	$analysis_page = get_page_by_path( 'analysis' );
	$analysis_url  = $analysis_page ? get_permalink( $analysis_page ) : add_query_arg( 'page_id', 17, home_url( '/' ) );

	$hps       = ewo_2025_hps_get();
	$la_count  = max( 1, min( 20, (int) $hps['latest_analysis_count'] ) );
	$la_source = $hps['latest_analysis_source'];
	$la_mode   = $hps['latest_analysis_mode'];

	// Fetch auto posts unless mode is custom-only.
	$la_posts = array();
	if ( 'custom' !== $la_mode ) {
		$la_fetch = ( 'both' === $la_source ) ? $la_count : min( $la_count * 6, 60 );
		$latest_q = new WP_Query(
			array(
				'post_type'           => 'post',
				'posts_per_page'      => $la_fetch,
				'post_status'         => 'publish',
				'ignore_sticky_posts' => true,
			)
		);
		foreach ( $latest_q->posts as $la_p ) {
			if ( count( $la_posts ) >= $la_count ) {
				break;
			}
			$is_sub = '' !== ewo_2025_substack_source_url( $la_p );
			if ( 'substack_only' === $la_source && ! $is_sub ) {
				continue;
			}
			if ( 'wp_only' === $la_source && $is_sub ) {
				continue;
			}
			$la_posts[] = $la_p;
		}
		wp_reset_postdata();
	}

	// Custom cards for this section (empty in auto-only mode).
	$la_custom = ( 'auto' !== $la_mode && function_exists( 'ewo_2025_cc_get_section' ) )
		? ewo_2025_cc_get_section( 'latest_analysis' )
		: array();

	// Dynamic cards assigned to the built-in Latest Analysis section.
	$la_dyn = function_exists( 'ewo_2025_ds_get_cards_for_section' )
		? ewo_2025_ds_get_cards_for_section( 'builtin_latest_analysis' ) : array();

	if ( empty( $la_posts ) && empty( $la_custom ) && empty( $la_dyn ) ) {
		return;
	}
	?>
	<section id="latest-analysis" class="ewo-section">
		<div class="ewo-section__header ewo-section__header--analysis">
			<div class="ewo-section__header-copy">
				<p class="ewo-kicker"><?php esc_html_e( 'Research & Analysis', 'ewo-2025' ); ?></p>
				<h2><?php esc_html_e( 'Latest Analysis', 'ewo-2025' ); ?></h2>
			</div>
			<a class="ewo-button ewo-button--ghost ewo-section__cta" href="<?php echo esc_url( $analysis_url ); ?>"><?php esc_html_e( 'View All Analysis', 'ewo-2025' ); ?></a>
		</div>
		<div class="ewo-article-grid ewo-analysis-grid">
			<?php
			$index    = 0;
			$sec_open = false;

			// Auto posts.
			foreach ( $la_posts as $la_post ) :
				global $post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
				$post = $la_post;
				setup_postdata( $post );
				$featured        = ( 0 === $index );
				$subscriber_only = ewo_2025_is_subscriber_only_post( $post );
				$substack_url    = ewo_2025_substack_source_url( $post );
				$excerpt         = $subscriber_only ? ewo_2025_subscriber_preview_text( $post ) : get_the_excerpt();
				$card_classes    = 'ewo-article-card ewo-briefing-card';
				if ( $featured ) {
					$card_classes .= ' ewo-briefing-card--featured';
				}
				if ( $subscriber_only ) {
					$card_classes .= ' ewo-briefing-card--subscriber';
				}
				if ( 1 === $index ) {
					echo '<div class="ewo-analysis-grid__secondary">';
					$sec_open = true;
				}
				?>
				<article <?php post_class( $card_classes ); ?>>
					<a class="ewo-briefing-card__media" href="<?php the_permalink(); ?>" aria-label="<?php the_title_attribute(); ?>">
						<?php if ( has_post_thumbnail() ) : ?>
							<?php the_post_thumbnail( $featured ? 'large' : 'medium_large' ); ?>
						<?php else : ?>
							<span class="ewo-briefing-card__placeholder" aria-hidden="true"></span>
						<?php endif; ?>
					</a>
					<div class="ewo-briefing-card__body">
						<?php if ( $subscriber_only ) : ?>
							<span class="ewo-subscriber-badge"><?php esc_html_e( '🔒 Subscriber Only', 'ewo-2025' ); ?></span>
						<?php endif; ?>
						<p class="ewo-card-meta"><span><?php esc_html_e( 'Briefing', 'ewo-2025' ); ?></span><?php echo esc_html( get_the_date() ); ?></p>
						<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
						<p><?php echo esc_html( wp_trim_words( $excerpt, $featured ? 34 : 18 ) ); ?></p>
						<?php if ( ! $featured ) : ?>
							<a class="ewo-briefing-card__more" href="<?php the_permalink(); ?>"><?php esc_html_e( 'Read More', 'ewo-2025' ); ?> &rarr;</a>
						<?php endif; ?>
						<?php if ( $featured && $subscriber_only && $substack_url ) : ?>
							<a class="ewo-button ewo-button--gold ewo-substack-button" href="<?php echo esc_url( $substack_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Read on Substack', 'ewo-2025' ); ?> &rarr;</a>
						<?php endif; ?>
					</div>
				</article>
				<?php
				++$index;
			endforeach;
			wp_reset_postdata();

			// Custom cards (appended after auto posts in mixed mode; only content in custom mode).
			foreach ( $la_custom as $la_cc ) :
				$featured = ( 0 === $index );
				if ( 1 === $index && ! $sec_open ) {
					echo '<div class="ewo-analysis-grid__secondary">';
					$sec_open = true;
				}
				ewo_2025_cc_render_briefing_card( $la_cc, $featured );
				++$index;
			endforeach;

			// Dynamic cards assigned via Homepage Cards admin (builtin_latest_analysis).
			foreach ( $la_dyn as $la_dc ) :
				$featured = ( 0 === $index );
				if ( 1 === $index && ! $sec_open ) {
					echo '<div class="ewo-analysis-grid__secondary">';
					$sec_open = true;
				}
				ewo_2025_ds_render_card( $la_dc, $featured );
				++$index;
			endforeach;

			if ( $sec_open ) {
				echo '</div>'; // Close .ewo-analysis-grid__secondary.
			}
			?>
		</div>
	</section>
	<?php
}

/**
 * Render Section 8: Strategic Playlists — custom query with count, featured filter, and custom card support.
 *
 * Enqueues ewo-youtube-playlists style and renders nothing if no data available.
 */
function ewo_2025_render_section_strategic_playlists() {
	$pl_hps    = ewo_2025_hps_get();
	$pl_count  = max( 1, min( 24, (int) $pl_hps['playlists_count'] ) );
	$pl_filter = $pl_hps['playlists_filter'];
	$pl_mode   = $pl_hps['playlists_mode'];

	// Auto playlists (skipped in custom-only mode or when CPT is absent).
	$pl_auto = array();
	if ( 'custom' !== $pl_mode && post_type_exists( 'ewo_playlist' ) ) {
		$pl_args = array(
			'post_type'           => 'ewo_playlist',
			'post_status'         => 'publish',
			'posts_per_page'      => $pl_count,
			'orderby'             => 'date',
			'order'               => 'DESC',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		);
		if ( 'featured' === $pl_filter ) {
			$pl_args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => 'ewo_youtube_playlist_featured',
					'value'   => '1',
					'compare' => '=',
				),
			);
		}
		$pl_query = new WP_Query( $pl_args );
		$pl_auto  = $pl_query->posts;
	}

	// Custom playlist cards (empty in auto-only mode).
	$pl_custom = ( 'auto' !== $pl_mode && function_exists( 'ewo_2025_cc_get_section' ) )
		? ewo_2025_cc_get_section( 'strategic_playlists' )
		: array();

	// Dynamic briefing cards assigned via Homepage Cards admin (builtin_strategic_playlists).
	$pl_dyn = function_exists( 'ewo_2025_ds_get_cards_for_section' )
		? ewo_2025_ds_get_cards_for_section( 'builtin_strategic_playlists' ) : array();

	if ( empty( $pl_auto ) && empty( $pl_custom ) && empty( $pl_dyn ) ) {
		return;
	}
	wp_enqueue_style( 'ewo-youtube-playlists' );
	?>
	<section id="strategic-playlists" class="ewo-section ewo-home-playlists">
		<div class="ewo-section__header">
			<div class="ewo-section__header-copy">
				<p class="ewo-kicker"><?php esc_html_e( 'Curated Series', 'ewo-2025' ); ?></p>
				<h2><?php esc_html_e( 'Strategic Playlists', 'ewo-2025' ); ?></h2>
			</div>
		</div>
		<section class="ewo-youtube-playlists" aria-label="<?php esc_attr_e( 'EWO YouTube playlists', 'ewo-youtube-integration' ); ?>">
			<div class="ewo-youtube-playlists__grid">
				<?php foreach ( $pl_auto as $pl ) :
					$pl_title = get_post_meta( $pl->ID, 'ewo_youtube_playlist_title', true );
					$pl_desc  = get_post_meta( $pl->ID, 'ewo_youtube_playlist_description', true );
					$pl_thumb = get_post_meta( $pl->ID, 'ewo_youtube_playlist_thumbnail', true );
					$pl_url   = get_post_meta( $pl->ID, 'ewo_youtube_playlist_url', true );
					if ( '' === $pl_title ) { $pl_title = get_the_title( $pl ); }
					if ( '' === $pl_desc )  { $pl_desc  = get_the_excerpt( $pl ); }
				?>
				<article class="ewo-youtube-playlists__card">
					<div class="ewo-youtube-playlists__thumb">
						<?php if ( $pl_thumb ) : ?>
							<img src="<?php echo esc_url( $pl_thumb ); ?>" alt="<?php echo esc_attr( $pl_title ); ?>" loading="lazy">
						<?php else : ?>
							<span class="ewo-youtube-playlists__placeholder" aria-hidden="true"></span>
						<?php endif; ?>
					</div>
					<div class="ewo-youtube-playlists__body">
						<h3><?php echo esc_html( $pl_title ); ?></h3>
						<?php if ( $pl_desc ) : ?>
							<p><?php echo esc_html( wp_trim_words( $pl_desc, 24 ) ); ?></p>
						<?php endif; ?>
						<?php if ( $pl_url ) : ?>
							<a class="ewo-youtube-playlists__button" href="<?php echo esc_url( $pl_url ); ?>" target="_blank" rel="noopener noreferrer">
								<?php esc_html_e( 'View Playlist', 'ewo-youtube-integration' ); ?>
							</a>
						<?php endif; ?>
					</div>
				</article>
				<?php endforeach; ?>
				<?php foreach ( $pl_custom as $pl_cc ) :
					ewo_2025_cc_render_playlist_card( $pl_cc );
				endforeach; ?>
			</div>
		</section>
		<?php if ( ! empty( $pl_dyn ) ) : ?>
		<div class="ewo-article-grid ewo-analysis-grid ewo-analysis-grid--playlists-dyn">
			<?php
			$pl_dyn_idx = 0;
			$pl_dyn_sec = false;
			foreach ( $pl_dyn as $pl_dc ) :
				$pl_dyn_feat = ( 0 === $pl_dyn_idx );
				if ( 1 === $pl_dyn_idx ) {
					echo '<div class="ewo-analysis-grid__secondary">';
					$pl_dyn_sec = true;
				}
				ewo_2025_ds_render_card( $pl_dc, $pl_dyn_feat );
				++$pl_dyn_idx;
			endforeach;
			if ( $pl_dyn_sec ) { echo '</div>'; }
			?>
		</div>
		<?php endif; ?>
	</section>
	<?php
}

/**
 * Render Section 8a: Featured Cards — standalone section for custom cards assigned to 'featured_cards'.
 *
 * Renders only if there are custom or dynamic cards.
 */
function ewo_2025_render_section_featured_cards() {
	$fc_cards = function_exists( 'ewo_2025_cc_get_section' ) ? ewo_2025_cc_get_section( 'featured_cards' ) : array();
	$fc_dyn   = function_exists( 'ewo_2025_ds_get_cards_for_section' )
		? ewo_2025_ds_get_cards_for_section( 'builtin_featured_cards' ) : array();
	if ( empty( $fc_cards ) && empty( $fc_dyn ) ) {
		return;
	}
	?>
	<section id="featured-cards" class="ewo-section">
		<div class="ewo-section__header">
			<div class="ewo-section__header-copy">
				<p class="ewo-kicker"><?php esc_html_e( 'Featured', 'ewo-2025' ); ?></p>
				<h2><?php esc_html_e( 'Featured Cards', 'ewo-2025' ); ?></h2>
			</div>
		</div>
		<div class="ewo-article-grid ewo-analysis-grid">
			<?php
			$fc_index    = 0;
			$fc_sec_open = false;
			foreach ( $fc_cards as $fc ) :
				$fc_feat = ( 0 === $fc_index );
				if ( 1 === $fc_index ) {
					echo '<div class="ewo-analysis-grid__secondary">';
					$fc_sec_open = true;
				}
				ewo_2025_cc_render_briefing_card( $fc, $fc_feat );
				++$fc_index;
			endforeach;
			foreach ( $fc_dyn as $fc_dc ) :
				$fc_feat = ( 0 === $fc_index );
				if ( 1 === $fc_index && ! $fc_sec_open ) {
					echo '<div class="ewo-analysis-grid__secondary">';
					$fc_sec_open = true;
				}
				ewo_2025_ds_render_card( $fc_dc, $fc_feat );
				++$fc_index;
			endforeach;
			if ( $fc_sec_open ) { echo '</div>'; }
			?>
		</div>
	</section>
	<?php
}

/**
 * Render Section 8b: Custom Section — standalone section for custom cards assigned to 'custom_section'.
 *
 * Renders only if there are custom or dynamic cards.
 */
function ewo_2025_render_section_custom_section() {
	$cs_cards = function_exists( 'ewo_2025_cc_get_section' ) ? ewo_2025_cc_get_section( 'custom_section' ) : array();
	$cs_dyn   = function_exists( 'ewo_2025_ds_get_cards_for_section' )
		? ewo_2025_ds_get_cards_for_section( 'builtin_custom_section' ) : array();
	if ( empty( $cs_cards ) && empty( $cs_dyn ) ) {
		return;
	}
	?>
	<section id="custom-section" class="ewo-section">
		<div class="ewo-section__header">
			<div class="ewo-section__header-copy">
				<p class="ewo-kicker"><?php esc_html_e( 'More', 'ewo-2025' ); ?></p>
				<h2><?php esc_html_e( 'More from EWO', 'ewo-2025' ); ?></h2>
			</div>
		</div>
		<div class="ewo-article-grid ewo-analysis-grid">
			<?php
			$cs_index    = 0;
			$cs_sec_open = false;
			foreach ( $cs_cards as $cs ) :
				$cs_feat = ( 0 === $cs_index );
				if ( 1 === $cs_index ) {
					echo '<div class="ewo-analysis-grid__secondary">';
					$cs_sec_open = true;
				}
				ewo_2025_cc_render_briefing_card( $cs, $cs_feat );
				++$cs_index;
			endforeach;
			foreach ( $cs_dyn as $cs_dc ) :
				$cs_feat = ( 0 === $cs_index );
				if ( 1 === $cs_index && ! $cs_sec_open ) {
					echo '<div class="ewo-analysis-grid__secondary">';
					$cs_sec_open = true;
				}
				ewo_2025_ds_render_card( $cs_dc, $cs_feat );
				++$cs_index;
			endforeach;
			if ( $cs_sec_open ) { echo '</div>'; }
			?>
		</div>
	</section>
	<?php
}

/**
 * Render Dynamic Homepage Sections — Section → Card → Target sections, admin-managed.
 *
 * Calls ewo_2025_ds_render_all() when the function exists.
 */
function ewo_2025_render_section_dynamic_sections() {
	if ( function_exists( 'ewo_2025_ds_render_all' ) ) {
		ewo_2025_ds_render_all();
	}
}

/**
 * Render Section 9: Platform Network — Connect With EWO platform cards.
 */
function ewo_2025_render_section_platform_network() {
	$connect = array(
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

	$connect_kses = array(
		'svg'  => array( 'viewbox' => true, 'aria-hidden' => true ),
		'path' => array( 'd' => true, 'fill' => true ),
	);
	?>
	<section id="connect" class="ewo-section">
		<div class="ewo-section__header">
			<div class="ewo-section__header-copy">
				<p class="ewo-kicker"><?php esc_html_e( 'Platform Network', 'ewo-2025' ); ?></p>
				<h2><?php esc_html_e( 'Connect With EWO', 'ewo-2025' ); ?></h2>
			</div>
		</div>
		<div class="ewo-connect-grid">
			<?php
			foreach ( $connect as $key => $platform ) :
				$url   = ewo_2025_get_platform_url( $key );
				$count = ewo_2025_platform_follower_count( $key );
				$tag   = '' !== $url ? 'a' : 'span';
				?>
				<<?php echo esc_html( $tag ); ?> class="ewo-connect-card ewo-connect-card--<?php echo esc_attr( $key ); ?><?php echo '' === $url ? ' ewo-connect-card--disabled' : ''; ?>"<?php echo '' !== $url ? ' href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer"' : ' aria-disabled="true"'; ?>>
					<span class="ewo-connect-card__icon"><?php echo wp_kses( $platform['icon'], $connect_kses ); ?></span>
					<span class="ewo-connect-card__name"><?php echo esc_html( $platform['name'] ); ?></span>
					<?php if ( '' !== $count ) : ?>
						<span class="ewo-connect-card__count"><?php echo esc_html( $count ); ?></span>
					<?php endif; ?>
					<span class="ewo-connect-card__label"><?php echo esc_html( $platform['label'] ); ?></span>
				</<?php echo esc_html( $tag ); ?>>
			<?php endforeach; ?>
		</div>
	</section>
	<?php
}

/**
 * Render Section 10: Book — book promotion card.
 */
function ewo_2025_render_section_book_section() {
	$amazon_book_url = ewo_2025_get_platform_url( 'amazon_book' );
	?>
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
			<?php if ( $amazon_book_url ) : ?>
				<a class="ewo-button ewo-button--gold" href="<?php echo esc_url( $amazon_book_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Get the Book on Amazon', 'ewo-2025' ); ?></a>
			<?php endif; ?>
		</div>
	</section>
	<?php
}
