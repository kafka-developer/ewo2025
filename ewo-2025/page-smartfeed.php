<?php
/**
 * Template for the Smart Feed public page.
 *
 * Route: /smartfeed/ (and /smart-feed/)
 * Shows RSS feed items as individual cards, filterable by Strategic Domain via
 * tabs. Load More reveals additional cards without a page reload.
 *
 * @package EWO_2025
 */

get_header();

$ewo_sf         = ewo_2025_smart_feed_data();
$ewo_sf_domains = $ewo_sf['domains'];
$ewo_sf_items   = $ewo_sf['items'];
?>

<main id="primary" class="site-main ewo-sf-main">

	<!-- ============================================================
	     Hero
	     ============================================================ -->
	<div class="ewo-sf-hero">
		<div class="ewo-sf-container">
			<p class="ewo-kicker"><?php esc_html_e( 'Smart Feed Intelligence', 'ewo-2025' ); ?></p>
			<h1 class="ewo-sf-hero-title"><?php esc_html_e( 'Smart Feed', 'ewo-2025' ); ?></h1>
			<p class="ewo-sf-hero-desc">
				<?php esc_html_e( 'Live intelligence signals from monitored keyword feeds, organised by strategic domain.', 'ewo-2025' ); ?>
			</p>
		</div>
	</div>

	<div class="ewo-sf-container ewo-sf-body">

		<?php if ( ! empty( $ewo_sf_items ) ) : ?>

			<!-- Domain filter tabs -->
			<nav class="ewo-sf-tabs-wrap" aria-label="<?php esc_attr_e( 'Filter by domain', 'ewo-2025' ); ?>">
				<div class="ewo-sf-tabs" role="tablist">
					<button type="button"
					        class="ewo-sf-tab ewo-sf-tab--active"
					        data-domain="all"
					        role="tab"
					        aria-selected="true">
						<?php esc_html_e( 'All', 'ewo-2025' ); ?>
						<span class="ewo-sf-tab-count"><?php echo esc_html( (string) count( $ewo_sf_items ) ); ?></span>
					</button>
					<?php foreach ( $ewo_sf_domains as $ewo_sf_d ) :
						$ewo_sf_domain_count = count( array_filter( $ewo_sf_items, static function ( $item ) use ( $ewo_sf_d ) {
							return (int) $item['domain_id'] === (int) $ewo_sf_d['id'];
						} ) );
						if ( $ewo_sf_domain_count === 0 ) continue;
					?>
						<button type="button"
						        class="ewo-sf-tab"
						        data-domain="<?php echo esc_attr( (string) $ewo_sf_d['id'] ); ?>"
						        role="tab"
						        aria-selected="false">
							<?php echo esc_html( $ewo_sf_d['name'] ); ?>
							<span class="ewo-sf-tab-count"><?php echo esc_html( (string) $ewo_sf_domain_count ); ?></span>
						</button>
					<?php endforeach; ?>
				</div>
			</nav>

			<!-- Feed grid — cards visible by default; JS adds ewo-sf-invisible class for filtering -->
			<div class="ewo-sf-grid" id="ewo-sf-grid" aria-live="polite">
				<?php foreach ( $ewo_sf_items as $ewo_sf_item ) : ?>
					<article class="ewo-sf-card"
					         data-domain-id="<?php echo esc_attr( (string) $ewo_sf_item['domain_id'] ); ?>">

						<div class="ewo-sf-card-header">
							<span class="ewo-sf-breadcrumb">
								<?php if ( ! empty( $ewo_sf_item['domain_name'] ) ) : ?>
									<a href="<?php echo esc_url( home_url( '/strategic-domains/' . $ewo_sf_item['domain_slug'] . '/' ) ); ?>"
									   class="ewo-sf-bc-domain">
										<?php echo esc_html( $ewo_sf_item['domain_name'] ); ?>
									</a>
								<?php endif; ?>
								<?php if ( ! empty( $ewo_sf_item['subdomain_name'] ) ) : ?>
									<span class="ewo-sf-bc-sep" aria-hidden="true">›</span>
									<span class="ewo-sf-bc-sub"><?php echo esc_html( $ewo_sf_item['subdomain_name'] ); ?></span>
								<?php endif; ?>
							</span>
							<span class="ewo-sf-rss-icon" aria-hidden="true" title="RSS">
								<svg width="13" height="13" viewBox="0 0 13 13" fill="none" xmlns="http://www.w3.org/2000/svg">
									<circle cx="2.5" cy="10.5" r="1.5" fill="currentColor"/>
									<path d="M1 6.5C4.314 6.5 7 9.186 7 12.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
									<path d="M1 1.5C7.351 1.5 12.5 6.649 12.5 13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
								</svg>
							</span>
						</div>

						<h3 class="ewo-sf-card-title">
							<a href="<?php echo esc_url( $ewo_sf_item['url'] ); ?>"
							   target="_blank" rel="noopener noreferrer">
								<?php echo esc_html( wp_trim_words( $ewo_sf_item['title'], 14 ) ); ?>
							</a>
						</h3>

						<p class="ewo-sf-card-meta">
							<?php if ( ! empty( $ewo_sf_item['source_domain'] ) ) : ?>
								<span class="ewo-sf-source"><?php echo esc_html( $ewo_sf_item['source_domain'] ); ?></span>
							<?php endif; ?>
							<?php if ( ! empty( $ewo_sf_item['time_ago'] ) ) : ?>
								<?php if ( ! empty( $ewo_sf_item['source_domain'] ) ) : ?>
									<span class="ewo-sf-dot" aria-hidden="true">·</span>
								<?php endif; ?>
								<time class="ewo-sf-time"
								      datetime="<?php echo esc_attr( $ewo_sf_item['time_raw'] ); ?>">
									<?php echo esc_html( $ewo_sf_item['time_ago'] ); ?>
								</time>
							<?php endif; ?>
						</p>

						<?php if ( ! empty( $ewo_sf_item['snippet'] ) ) : ?>
							<p class="ewo-sf-snippet"><?php echo esc_html( $ewo_sf_item['snippet'] ); ?></p>
						<?php endif; ?>

						<?php if ( ! empty( $ewo_sf_item['keyword'] ) ) : ?>
							<div class="ewo-sf-tags">
								<span class="ewo-sf-tag"><?php echo esc_html( $ewo_sf_item['keyword'] ); ?></span>
							</div>
						<?php endif; ?>

					</article>
				<?php endforeach; ?>
			</div><!-- .ewo-sf-grid -->

			<p class="ewo-sf-empty ewo-sf-invisible" id="ewo-sf-empty">
				<?php esc_html_e( 'No feed items for this domain yet.', 'ewo-2025' ); ?>
			</p>

			<div class="ewo-sf-load-more-wrap">
				<button type="button" class="ewo-sf-load-more-btn ewo-sf-invisible" id="ewo-sf-load-more">
					<?php esc_html_e( 'Load More', 'ewo-2025' ); ?>
				</button>
			</div>

		<?php else : ?>

			<div class="ewo-sf-empty-page">
				<p><?php esc_html_e( 'No feed items yet. Configure Strategic Domains and run an import to populate this feed.', 'ewo-2025' ); ?></p>
				<?php if ( current_user_can( 'manage_options' ) ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=ewo-rss-domains' ) ); ?>"
					   class="ewo-button ewo-button--gold">
						<?php esc_html_e( 'Configure Strategic Domains →', 'ewo-2025' ); ?>
					</a>
				<?php endif; ?>
			</div>

		<?php endif; ?>

	</div><!-- .ewo-sf-container -->

</main>

<?php get_footer(); ?>
