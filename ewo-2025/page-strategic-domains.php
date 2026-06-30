<?php
/**
 * Template for the Strategic Domains public page.
 *
 * Handles two views depending on the ewo_domain_slug query var:
 *   – absent  → /strategic-domains/  (index grid of all domains)
 *   – present → /strategic-domains/{slug}/  (single domain detail)
 *
 * @package EWO_2025
 */

get_header();

$ewo_sfd_slug = sanitize_title( (string) get_query_var( 'ewo_domain_slug', '' ) );

if ( '' !== $ewo_sfd_slug ) :

	/* DOMAIN DETAIL VIEW  /strategic-domains/{slug}/ */
	$ewo_domain = ewo_2025_sfd_detail_data( $ewo_sfd_slug );
	?>
	<main id="primary" class="site-main ewo-sfd-main">
		<div class="ewo-sfd-container">

			<a href="<?php echo esc_url( home_url( '/strategic-domains/' ) ); ?>" class="ewo-sfd-back">
				&larr; <?php esc_html_e( 'All Strategic Domains', 'ewo-2025' ); ?>
			</a>

			<?php if ( ! $ewo_domain ) : ?>

				<p class="ewo-sfd-empty ewo-sfd-empty--page">
					<?php esc_html_e( 'Domain not found.', 'ewo-2025' ); ?>
				</p>

			<?php else : ?>

				<div class="ewo-sfd-detail-header">
					<p class="ewo-kicker"><?php esc_html_e( 'Smart Feed Intelligence', 'ewo-2025' ); ?></p>
					<h1 class="ewo-sfd-detail-title"><?php echo esc_html( $ewo_domain['name'] ); ?></h1>
					<?php if ( ! empty( $ewo_domain['description'] ) ) : ?>
						<p class="ewo-sfd-detail-desc"><?php echo esc_html( $ewo_domain['description'] ); ?></p>
					<?php endif; ?>
				</div>

				<?php if ( empty( $ewo_domain['subdomains'] ) ) : ?>
					<p class="ewo-sfd-empty">
						<?php esc_html_e( 'No subdomains configured for this domain yet.', 'ewo-2025' ); ?>
					</p>
				<?php else : ?>
					<div class="ewo-sfd-subdomains">
						<?php foreach ( $ewo_domain['subdomains'] as $ewo_sub ) : ?>
							<div class="ewo-sfd-subdomain-block">

								<h2 class="ewo-sfd-subdomain-title"><?php echo esc_html( $ewo_sub['name'] ); ?></h2>

								<?php if ( ! empty( $ewo_sub['keywords'] ) ) : ?>
									<div class="ewo-sfd-kw-section">
										<p class="ewo-sfd-label"><?php esc_html_e( 'Keywords & Feeds', 'ewo-2025' ); ?></p>
										<ul class="ewo-sfd-kw-list">
											<?php foreach ( $ewo_sub['keywords'] as $ewo_kw ) : ?>
												<li class="ewo-sfd-kw-item<?php echo ! $ewo_kw['active'] ? ' ewo-sfd-kw-item--inactive' : ''; ?>">
													<span class="ewo-sfd-kw-name"><?php echo esc_html( $ewo_kw['keyword'] ); ?></span>
													<?php if ( ! empty( $ewo_kw['feed_url'] ) ) : ?>
														<a href="<?php echo esc_url( $ewo_kw['feed_url'] ); ?>"
														   class="ewo-sfd-feed-link"
														   target="_blank" rel="noopener noreferrer"
														   title="<?php esc_attr_e( 'View RSS feed', 'ewo-2025' ); ?>">
															RSS
														</a>
													<?php endif; ?>
													<?php if ( ! $ewo_kw['active'] ) : ?>
														<span class="ewo-sfd-kw-inactive"><?php esc_html_e( 'inactive', 'ewo-2025' ); ?></span>
													<?php endif; ?>
												</li>
											<?php endforeach; ?>
										</ul>
									</div>
								<?php endif; ?>

								<div class="ewo-sfd-sources-section">
									<?php if ( ! empty( $ewo_sub['sources'] ) ) : ?>
										<p class="ewo-sfd-label"><?php esc_html_e( 'Latest Sources', 'ewo-2025' ); ?></p>
										<ul class="ewo-sfd-source-list">
											<?php foreach ( $ewo_sub['sources'] as $ewo_src ) :
												$ewo_ts  = ! empty( $ewo_src->published_at ) && '0000-00-00 00:00:00' !== $ewo_src->published_at
													? $ewo_src->published_at
													: $ewo_src->fetched_at;
												$ewo_ago = ewo_2025_time_ago( $ewo_ts );
											?>
												<li class="ewo-sfd-source-item">
													<a href="<?php echo esc_url( $ewo_src->url ); ?>"
													   class="ewo-sfd-source-link"
													   target="_blank" rel="noopener noreferrer">
														<?php echo esc_html( $ewo_src->title ); ?>
													</a>
													<span class="ewo-sfd-source-meta">
														<?php echo esc_html( $ewo_src->source_domain ); ?>
														<?php if ( $ewo_ago ) : ?>
															&middot; <?php echo esc_html( $ewo_ago ); ?>
														<?php endif; ?>
													</span>
												</li>
											<?php endforeach; ?>
										</ul>
									<?php else : ?>
										<p class="ewo-sfd-empty">
											<?php esc_html_e( 'No source articles captured yet for this subdomain.', 'ewo-2025' ); ?>
										</p>
									<?php endif; ?>
								</div>

							</div><!-- .ewo-sfd-subdomain-block -->
						<?php endforeach; ?>
					</div><!-- .ewo-sfd-subdomains -->
				<?php endif; ?>

			<?php endif; ?>

		</div><!-- .ewo-sfd-container -->
	</main>

<?php else : ?>

	<?php
	/* INDEX VIEW  /strategic-domains/ */
	$ewo_domains = ewo_2025_sfd_index_data();
	?>

	<main id="primary" class="site-main ewo-sfd-main">

		<div class="ewo-sfd-hero">
			<div class="ewo-sfd-container">
				<p class="ewo-kicker"><?php esc_html_e( 'Smart Feed Intelligence', 'ewo-2025' ); ?></p>
				<h1 class="ewo-sfd-hero-title"><?php esc_html_e( 'Strategic Domains', 'ewo-2025' ); ?></h1>
				<p class="ewo-sfd-hero-desc">
					<?php esc_html_e( 'Structured intelligence tracking across global strategic domains. Each domain monitors live keyword feeds, captures source articles, and surfaces the signals that matter.', 'ewo-2025' ); ?>
				</p>
			</div>
		</div>

		<div class="ewo-sfd-container ewo-sfd-index-body">

			<?php if ( empty( $ewo_domains ) ) : ?>

				<div class="ewo-sfd-no-domains">
					<p><?php esc_html_e( 'No strategic domains have been configured yet.', 'ewo-2025' ); ?></p>
					<?php if ( current_user_can( 'manage_options' ) ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=ewo-rss-domains' ) ); ?>"
						   class="ewo-button ewo-button--gold">
							<?php esc_html_e( 'Configure Strategic Domains →', 'ewo-2025' ); ?>
						</a>
					<?php endif; ?>
				</div>

			<?php else : ?>

				<div class="ewo-sfd-grid">
					<?php foreach ( $ewo_domains as $ewo_d ) :
						$ewo_detail_url = home_url( '/strategic-domains/' . $ewo_d['slug'] . '/' );
					?>
						<article class="ewo-sfd-card">

							<a href="<?php echo esc_url( $ewo_detail_url ); ?>"
							   class="ewo-sfd-card-overlay"
							   aria-hidden="true" tabindex="-1"></a>

							<div class="ewo-sfd-card-body">
								<h2 class="ewo-sfd-card-title">
									<a href="<?php echo esc_url( $ewo_detail_url ); ?>"
									   class="ewo-domain-title-link">
										<?php echo esc_html( $ewo_d['name'] ); ?>
									</a>
								</h2>

								<p class="ewo-sfd-card-desc<?php echo empty( $ewo_d['description'] ) ? ' ewo-sfd-card-desc--empty' : ''; ?>">
									<?php
									echo ! empty( $ewo_d['description'] )
										? esc_html( $ewo_d['description'] )
										: esc_html__( 'Strategic intelligence domain.', 'ewo-2025' );
									?>
								</p>

								<?php if ( ! empty( $ewo_d['sources'] ) ) : ?>
									<ul class="ewo-sfd-card-sources">
										<?php foreach ( $ewo_d['sources'] as $ewo_src ) :
											$ewo_ts  = ! empty( $ewo_src->published_at ) && '0000-00-00 00:00:00' !== $ewo_src->published_at
												? $ewo_src->published_at
												: $ewo_src->fetched_at;
											$ewo_ago = ewo_2025_time_ago( $ewo_ts );
										?>
											<li class="ewo-sfd-card-source">
												<a href="<?php echo esc_url( $ewo_src->url ); ?>"
												   class="ewo-sfd-card-source-link"
												   target="_blank" rel="noopener noreferrer">
													<?php echo esc_html( wp_trim_words( $ewo_src->title, 11 ) ); ?>
												</a>
												<span class="ewo-sfd-card-source-meta">
													<?php echo esc_html( $ewo_src->source_domain ); ?>
													<?php if ( $ewo_ago ) : ?>
														&middot; <?php echo esc_html( $ewo_ago ); ?>
													<?php endif; ?>
												</span>
											</li>
										<?php endforeach; ?>
									</ul>
								<?php else : ?>
									<p class="ewo-sfd-card-empty">
										<?php esc_html_e( 'Awaiting source signals.', 'ewo-2025' ); ?>
									</p>
								<?php endif; ?>
							</div><!-- .ewo-sfd-card-body -->

							<footer class="ewo-sfd-card-footer">
								<span class="ewo-sfd-card-stat">
									<strong><?php echo esc_html( (string) $ewo_d['subdomain_count'] ); ?></strong>
									<?php esc_html_e( 'Subdomains', 'ewo-2025' ); ?>
								</span>
								<span class="ewo-sfd-card-stat">
									<strong><?php echo esc_html( (string) $ewo_d['keyword_count'] ); ?></strong>
									<?php esc_html_e( 'Keywords', 'ewo-2025' ); ?>
								</span>
								<span class="ewo-sfd-card-stat">
									<strong><?php echo esc_html( (string) $ewo_d['feed_count'] ); ?></strong>
									<?php esc_html_e( 'Feeds', 'ewo-2025' ); ?>
								</span>
								<a href="<?php echo esc_url( $ewo_detail_url ); ?>" class="ewo-sfd-card-cta">
									<?php esc_html_e( 'Explore →', 'ewo-2025' ); ?>
								</a>
							</footer>

						</article><!-- .ewo-sfd-card -->
					<?php endforeach; ?>
				</div><!-- .ewo-sfd-grid -->

			<?php endif; ?>

		</div><!-- .ewo-sfd-container -->
	</main>

<?php endif;

get_footer();
