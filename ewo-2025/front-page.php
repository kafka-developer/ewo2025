<?php
/**
 * Front page template.
 *
 * @package EWO_2025
 */

get_header();

$ewo_2025_newsletter_url  = ewo_2025_get_platform_url( 'newsletter' );
$ewo_2025_spotify_url     = ewo_2025_get_platform_url( 'spotify' );
$ewo_2025_amazon_book_url = ewo_2025_get_platform_url( 'amazon_book' );
?>

<!-- EWO Theme Version: <?php echo esc_html( EWO_THEME_VERSION ); ?> -->
<main id="primary" class="site-main site-main--home">
	<section class="ewo-hero" style="--ewo-hero-image: url('<?php echo esc_url( get_template_directory_uri() . '/assets/images/ewo-banner.png' ); ?>');">
		<div class="ewo-hero__overlay"></div>
		<div class="ewo-hero__inner">
			<div class="ewo-hero__content">
				<p class="ewo-kicker"><?php esc_html_e( 'Geopolitical Intelligence Publication', 'ewo-2025' ); ?></p>
				<h1><?php esc_html_e( 'Understanding The Systems Shaping Global Power', 'ewo-2025' ); ?></h1>
				<p class="ewo-hero__lede"><?php esc_html_e( 'EWO tracks the dependencies, vulnerabilities, responses, and consequences behind global power shifts.', 'ewo-2025' ); ?></p>
				<div class="ewo-hero__actions">
					<a class="ewo-button ewo-button--gold" href="<?php echo esc_url( $ewo_2025_newsletter_url ? $ewo_2025_newsletter_url : '#newsletter' ); ?>"<?php echo $ewo_2025_newsletter_url ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>><?php esc_html_e( 'Join the Briefing', 'ewo-2025' ); ?></a>
					<a class="ewo-button ewo-button--ghost" href="#latest-articles"><?php esc_html_e( 'Read Latest', 'ewo-2025' ); ?></a>
				</div>
			</div>
		</div>
	</section>

	<section id="ewo-method" class="ewo-section ewo-method-section">
		<div class="ewo-section__header ewo-method-section__header">
			<div>
				<p class="ewo-kicker"><?php esc_html_e( 'THE EWO METHOD', 'ewo-2025' ); ?></p>
				<h2><?php esc_html_e( 'Most analysis follows events. EWO follows systems.', 'ewo-2025' ); ?></h2>
			</div>
		</div>
		<div class="ewo-method-flow" aria-label="<?php esc_attr_e( 'Dependency to vulnerability to solution to consequence', 'ewo-2025' ); ?>">
			<?php
			$ewo_2025_method = array(
				array(
					'title'       => __( 'Dependency', 'ewo-2025' ),
					'description' => __( 'The System', 'ewo-2025' ),
				),
				array(
					'title'       => __( 'Vulnerability', 'ewo-2025' ),
					'description' => __( 'The Weakness', 'ewo-2025' ),
				),
				array(
					'title'       => __( 'Solution', 'ewo-2025' ),
					'description' => __( 'The Response', 'ewo-2025' ),
				),
				array(
					'title'       => __( 'Consequence', 'ewo-2025' ),
					'description' => __( 'The Outcome', 'ewo-2025' ),
				),
			);

			foreach ( $ewo_2025_method as $ewo_2025_index => $ewo_2025_stage ) :
				?>
				<article class="ewo-method-card">
					<span class="ewo-method-card__number"><?php echo esc_html( str_pad( (string) ( $ewo_2025_index + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
					<h3><?php echo esc_html( $ewo_2025_stage['title'] ); ?></h3>
					<p><?php echo esc_html( $ewo_2025_stage['description'] ); ?></p>
				</article>
				<?php
			endforeach;
			?>
		</div>
	</section>

	<section id="latest-videos" class="ewo-section ewo-section--media">
		<div class="ewo-section__header">
			<p class="ewo-kicker"><?php esc_html_e( 'INTELLIGENCE BRIEFINGS', 'ewo-2025' ); ?></p>
			<h2><?php esc_html_e( 'Latest Strategic Analysis', 'ewo-2025' ); ?></h2>
		</div>
		<?php ewo_2025_platform_links( array( 'youtube', 'rumble', 'tiktok' ), 'ewo-platform-links ewo-platform-links--section' ); ?>
		<div class="ewo-feature-grid ewo-feature-grid--videos">
			<article class="ewo-video-card ewo-video-card--large">
				<span class="ewo-play-mark" aria-hidden="true"></span>
				<p class="ewo-card-meta"><?php esc_html_e( 'Global Briefing', 'ewo-2025' ); ?></p>
				<h3><?php esc_html_e( 'The week in strategic risk', 'ewo-2025' ); ?></h3>
			</article>
			<article class="ewo-video-card">
				<span class="ewo-play-mark" aria-hidden="true"></span>
				<p class="ewo-card-meta"><?php esc_html_e( 'Dispatch', 'ewo-2025' ); ?></p>
				<h3><?php esc_html_e( 'Energy pressure points', 'ewo-2025' ); ?></h3>
			</article>
			<article class="ewo-video-card">
				<span class="ewo-play-mark" aria-hidden="true"></span>
				<p class="ewo-card-meta"><?php esc_html_e( 'Analysis', 'ewo-2025' ); ?></p>
				<h3><?php esc_html_e( 'Alliance politics after the summit', 'ewo-2025' ); ?></h3>
			</article>
		</div>
	</section>

	<section id="core-topics" class="ewo-section">
		<div class="ewo-section__header">
			<p class="ewo-kicker"><?php esc_html_e( 'STRATEGIC DOMAINS', 'ewo-2025' ); ?></p>
			<h2><?php esc_html_e( 'The Systems Shaping Global Power', 'ewo-2025' ); ?></h2>
		</div>
		<div class="ewo-topic-grid">
			<?php
			$ewo_2025_domains = array(
				array(
					'title'       => __( 'Energy Systems', 'ewo-2025' ),
					'description' => __( 'Supply chains, chokepoints, grids, fuels, and the leverage created by energy dependence.', 'ewo-2025' ),
					'icon'        => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M13 2 4 14h7l-1 8 9-12h-7l1-8Z"/></svg>',
					'visual'      => 'energy',
				),
				array(
					'title'       => __( 'Trade Networks', 'ewo-2025' ),
					'description' => __( 'Ports, corridors, sanctions, logistics, and the routes that define commercial influence.', 'ewo-2025' ),
					'icon'        => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 7h18M6 7v10m12-10v10M5 17h14l-2 4H7l-2-4ZM8 3h8l2 4H6l2-4Z"/></svg>',
					'visual'      => 'trade',
				),
				array(
					'title'       => __( 'Financial Power', 'ewo-2025' ),
					'description' => __( 'Capital flows, reserve assets, debt pressure, payment rails, and monetary coercion.', 'ewo-2025' ),
					'icon'        => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v18M17 7.5c-.9-1-2.4-1.5-4.2-1.5-2.4 0-4.3 1-4.3 2.8 0 4.2 8.9 1.6 8.9 5.9 0 1.9-1.9 3.3-4.8 3.3-2.2 0-3.9-.7-5-1.9"/></svg>',
					'visual'      => 'finance',
				),
				array(
					'title'       => __( 'Technology Competition', 'ewo-2025' ),
					'description' => __( 'Semiconductors, AI, cyber capability, standards, platforms, and industrial advantage.', 'ewo-2025' ),
					'icon'        => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="6" y="6" width="12" height="12" rx="2"/><path d="M9 1v4m6-4v4M9 19v4m6-4v4M1 9h4m-4 6h4m14-6h4m-4 6h4M10 10h4v4h-4z"/></svg>',
					'visual'      => 'technology',
				),
				array(
					'title'       => __( 'Military Balance', 'ewo-2025' ),
					'description' => __( 'Force posture, deterrence, defense production, alliances, and escalation dynamics.', 'ewo-2025' ),
					'icon'        => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 4 6v6c0 5 3.4 8 8 9 4.6-1 8-4 8-9V6l-8-3Z"/><path d="M12 8v8M8 12h8"/></svg>',
					'visual'      => 'military',
				),
				array(
					'title'       => __( 'Institutional Change', 'ewo-2025' ),
					'description' => __( 'Treaties, blocs, governance models, legitimacy contests, and the redesign of global rules.', 'ewo-2025' ),
					'icon'        => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 10h16M6 10v8m4-8v8m4-8v8m4-8v8M3 21h18M12 3l8 5H4l8-5Z"/></svg>',
					'visual'      => 'institutions',
				),
			);

			foreach ( $ewo_2025_domains as $ewo_2025_domain ) :
				?>
				<article class="ewo-topic-card ewo-domain-card ewo-domain-card--<?php echo esc_attr( $ewo_2025_domain['visual'] ); ?>">
					<div class="ewo-domain-card__icon">
						<?php echo wp_kses( $ewo_2025_domain['icon'], array( 'svg' => array( 'viewBox' => true, 'aria-hidden' => true ), 'path' => array( 'd' => true ), 'rect' => array( 'x' => true, 'y' => true, 'width' => true, 'height' => true, 'rx' => true ) ) ); ?>
					</div>
					<h3><?php echo esc_html( $ewo_2025_domain['title'] ); ?></h3>
					<p><?php echo esc_html( $ewo_2025_domain['description'] ); ?></p>
				</article>
				<?php
			endforeach;
			?>
		</div>
	</section>

	<section id="latest-articles" class="ewo-section">
		<div class="ewo-section__header">
			<p class="ewo-kicker"><?php esc_html_e( 'RESEARCH & ANALYSIS', 'ewo-2025' ); ?></p>
			<h2><?php esc_html_e( 'Long-form Strategic Assessments', 'ewo-2025' ); ?></h2>
		</div>
		<div class="ewo-article-grid">
			<?php
			$ewo_2025_latest = new WP_Query(
				array(
					'post_type'           => 'post',
					'posts_per_page'      => 3,
					'post_status'         => 'publish',
					'ignore_sticky_posts' => true,
				)
			);

			if ( $ewo_2025_latest->have_posts() ) :
				while ( $ewo_2025_latest->have_posts() ) :
					$ewo_2025_latest->the_post();
					?>
					<article <?php post_class( 'ewo-article-card ewo-briefing-card' ); ?>>
						<a class="ewo-briefing-card__media" href="<?php the_permalink(); ?>" aria-label="<?php the_title_attribute(); ?>">
							<?php if ( has_post_thumbnail() ) : ?>
								<?php the_post_thumbnail( 'medium_large' ); ?>
							<?php else : ?>
								<span class="ewo-briefing-card__placeholder" aria-hidden="true"></span>
							<?php endif; ?>
						</a>
						<div class="ewo-briefing-card__body">
							<p class="ewo-card-meta"><span><?php esc_html_e( 'Briefing', 'ewo-2025' ); ?></span><?php echo esc_html( get_the_date() ); ?></p>
							<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
							<p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 20 ) ); ?></p>
						</div>
					</article>
					<?php
				endwhile;
				wp_reset_postdata();
			else :
				?>
				<article class="ewo-article-card ewo-briefing-card">
					<div class="ewo-briefing-card__media">
						<span class="ewo-briefing-card__placeholder" aria-hidden="true"></span>
					</div>
					<div class="ewo-briefing-card__body">
						<p class="ewo-card-meta"><span><?php esc_html_e( 'Briefing', 'ewo-2025' ); ?></span><?php esc_html_e( 'Analysis', 'ewo-2025' ); ?></p>
						<h3><?php esc_html_e( 'New intelligence reports are being prepared.', 'ewo-2025' ); ?></h3>
						<p><?php esc_html_e( 'Publish posts in WordPress to populate this section automatically.', 'ewo-2025' ); ?></p>
					</div>
				</article>
			<?php endif; ?>
		</div>
	</section>

	<section id="podcast" class="ewo-section ewo-split-section">
		<div>
			<p class="ewo-kicker"><?php esc_html_e( 'AUDIO BRIEFINGS', 'ewo-2025' ); ?></p>
			<h2><?php esc_html_e( 'Strategic Conversations & Analysis', 'ewo-2025' ); ?></h2>
			<p><?php esc_html_e( 'Long-form conversations on the forces shaping the international system.', 'ewo-2025' ); ?></p>
			<?php ewo_2025_platform_links( array( 'spotify', 'substack' ), 'ewo-platform-links ewo-platform-links--inline' ); ?>
		</div>
		<a class="ewo-button ewo-button--ghost" href="<?php echo esc_url( $ewo_2025_spotify_url ? $ewo_2025_spotify_url : ( $ewo_2025_newsletter_url ? $ewo_2025_newsletter_url : '#newsletter' ) ); ?>"<?php echo ( $ewo_2025_spotify_url || $ewo_2025_newsletter_url ) ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>><?php esc_html_e( 'Get Episode Alerts', 'ewo-2025' ); ?></a>
	</section>

	<section id="book" class="ewo-section ewo-book-section">
		<div class="ewo-book-section__content">
			<p class="ewo-kicker"><?php esc_html_e( 'SPECIAL REPORT', 'ewo-2025' ); ?></p>
			<h2><?php esc_html_e( 'A Framework for Reading Global Power', 'ewo-2025' ); ?></h2>
			<p><?php esc_html_e( 'Promote the flagship EWO book here with a focused editorial pitch and a direct call to action.', 'ewo-2025' ); ?></p>
			<?php if ( $ewo_2025_amazon_book_url ) : ?>
				<a class="ewo-button ewo-button--gold" href="<?php echo esc_url( $ewo_2025_amazon_book_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Explore the Book', 'ewo-2025' ); ?></a>
			<?php endif; ?>
		</div>
	</section>

	<section id="newsletter" class="ewo-section ewo-newsletter">
		<div>
			<p class="ewo-kicker"><?php esc_html_e( 'DISPATCH CHANNEL', 'ewo-2025' ); ?></p>
			<h2><?php esc_html_e( 'Receive the EWO Intelligence Briefing', 'ewo-2025' ); ?></h2>
			<p><?php esc_html_e( 'A concise intelligence digest for geopolitics, markets, conflict, and strategic technology.', 'ewo-2025' ); ?></p>
		</div>
		<form class="ewo-newsletter__form" action="<?php echo esc_url( $ewo_2025_newsletter_url ? $ewo_2025_newsletter_url : '#' ); ?>" method="post"<?php echo $ewo_2025_newsletter_url ? ' target="_blank"' : ''; ?>>
			<label class="screen-reader-text" for="ewo-newsletter-email"><?php esc_html_e( 'Email address', 'ewo-2025' ); ?></label>
			<input id="ewo-newsletter-email" type="email" placeholder="<?php esc_attr_e( 'Email address', 'ewo-2025' ); ?>">
			<button class="ewo-button ewo-button--gold" type="submit"><?php esc_html_e( 'Subscribe', 'ewo-2025' ); ?></button>
		</form>
		<?php ewo_2025_platform_links( array( 'newsletter', 'substack' ), 'ewo-platform-links ewo-platform-links--inline' ); ?>
	</section>

	<?php if ( ewo_2025_has_platform_links( array( 'youtube', 'substack', 'spotify', 'x', 'rumble', 'tiktok', 'amazon_book', 'newsletter' ) ) ) : ?>
		<section id="follow-platforms" class="ewo-section ewo-follow-section">
			<div class="ewo-follow-section__content">
				<p class="ewo-kicker"><?php esc_html_e( 'PLATFORM NETWORK', 'ewo-2025' ); ?></p>
				<h2><?php esc_html_e( 'Follow EWO Across Platforms', 'ewo-2025' ); ?></h2>
				<p><?php esc_html_e( 'Connect with EWO briefings, audio analysis, video dispatches, research notes, and book updates across the platform ecosystem.', 'ewo-2025' ); ?></p>
			</div>
			<?php ewo_2025_platform_links( array( 'youtube', 'substack', 'spotify', 'x', 'rumble', 'tiktok', 'amazon_book', 'newsletter' ), 'ewo-platform-links ewo-platform-links--follow' ); ?>
		</section>
	<?php endif; ?>
</main>

<?php
get_footer();
