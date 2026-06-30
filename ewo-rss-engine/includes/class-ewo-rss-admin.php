<?php
/**
 * Admin menu, dashboard, logs, and manual-run handling.
 *
 * @package EWO_RSS_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin-facing controller.
 */
class EWO_RSS_Admin {
	const MENU_SLUG      = 'ewo-rss-engine';
	const LOGS_SLUG      = 'ewo-rss-engine-logs';
	const RUN_ACTION     = 'ewo_rss_run_import';
	const CLEAR_ACTION   = 'ewo_rss_clear_logs';
	const CAPABILITY     = 'manage_options';
	const NOTICE_TRANSIENT_PREFIX = 'ewo_rss_notice_';

	/**
	 * Sources component.
	 *
	 * @var EWO_RSS_Sources
	 */
	protected $sources;

	/**
	 * Importer component.
	 *
	 * @var EWO_RSS_Importer
	 */
	protected $importer;

	/**
	 * Page hook suffixes owned by this plugin.
	 *
	 * @var string[]
	 */
	protected $hooks = array();

	/**
	 * Constructor.
	 *
	 * @param EWO_RSS_Sources  $sources  Sources component.
	 * @param EWO_RSS_Importer $importer Importer component.
	 */
	public function __construct( EWO_RSS_Sources $sources, EWO_RSS_Importer $importer ) {
		$this->sources  = $sources;
		$this->importer = $importer;
	}

	/**
	 * Register admin hooks.
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_menu', array( $this, 'sort_menu' ), 999 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_post_' . self::RUN_ACTION, array( $this, 'handle_run' ) );
		add_action( 'admin_post_' . self::CLEAR_ACTION, array( $this, 'handle_clear_logs' ) );
		add_action( 'admin_notices', array( $this, 'render_notice' ) );
	}

	/**
	 * Register the menu and sub-pages.
	 */
	public function register_menu() {
		$this->hooks['dashboard'] = add_menu_page(
			__( 'EWO RSS Engine', 'ewo-rss-engine' ),
			__( 'EWO RSS Engine', 'ewo-rss-engine' ),
			self::CAPABILITY,
			self::MENU_SLUG,
			array( $this, 'render_dashboard' ),
			'dashicons-rss',
			58
		);

		$this->hooks['dashboard_sub'] = add_submenu_page(
			self::MENU_SLUG,
			__( 'Dashboard', 'ewo-rss-engine' ),
			__( 'Dashboard', 'ewo-rss-engine' ),
			self::CAPABILITY,
			self::MENU_SLUG,
			array( $this, 'render_dashboard' )
		);

		$this->hooks['logs'] = add_submenu_page(
			self::MENU_SLUG,
			__( 'Import Logs', 'ewo-rss-engine' ),
			__( 'Import Logs', 'ewo-rss-engine' ),
			self::CAPABILITY,
			self::LOGS_SLUG,
			array( $this, 'render_logs' )
		);
	}

	/**
	 * Reorder the submenu to the desired position:
	 * Dashboard → Feed Sources → Sources → Strategic Domains → Import Logs → Feed Health → Duplicates → Attribution
	 *
	 * Runs at admin_menu priority 999 so all pages are already registered.
	 */
	public function sort_menu() {
		global $submenu;

		$slug = self::MENU_SLUG;
		if ( empty( $submenu[ $slug ] ) ) {
			return;
		}

		$order = array(
			self::MENU_SLUG,
			'edit.php?post_type=ewo_rss_source',
			'post-new.php?post_type=ewo_rss_source',
			'ewo-rss-sources',
			'ewo-rss-domains',
			self::LOGS_SLUG,
			'ewo-rss-health',
			'ewo-rss-duplicates',
			'ewo-rss-attribution',
		);

		usort(
			$submenu[ $slug ],
			static function ( $a, $b ) use ( $order ) {
				$pa = array_search( $a[2], $order, true );
				$pb = array_search( $b[2], $order, true );
				if ( false === $pa ) {
					$pa = 99;
				}
				if ( false === $pb ) {
					$pb = 99;
				}
				return $pa - $pb;
			}
		);
	}

	/**
	 * Enqueue assets on this plugin's pages only.
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 */
	public function enqueue_assets( $hook_suffix ) {
		if ( ! in_array( $hook_suffix, $this->hooks, true ) ) {
			return;
		}

		wp_enqueue_style(
			'ewo-rss-engine-admin',
			EWO_RSS_ENGINE_URL . 'assets/css/admin.css',
			array(),
			EWO_RSS_ENGINE_VERSION
		);

		wp_enqueue_script(
			'ewo-rss-engine-admin',
			EWO_RSS_ENGINE_URL . 'assets/js/admin.js',
			array(),
			EWO_RSS_ENGINE_VERSION,
			true
		);

		// Dashboard gets its own dedicated stylesheet (separate URL = no cache collision).
		if ( isset( $this->hooks['dashboard'] ) && $hook_suffix === $this->hooks['dashboard'] ) {
			wp_enqueue_style(
				'ewo-rss-engine-dashboard',
				EWO_RSS_ENGINE_URL . 'assets/css/dashboard.css',
				array( 'ewo-rss-engine-admin' ),
				EWO_RSS_ENGINE_VERSION
			);
		}
	}

	/* ---------------------------------------------------------------------
	 * Dashboard metrics helpers
	 * ------------------------------------------------------------------- */

	/**
	 * Most recent fetched_at timestamp from the sources table.
	 *
	 * @return string Human-readable or '—'.
	 */
	protected function last_fetch_time() {
		global $wpdb;
		$table = EWO_RSS_Source_Store::table();
		$ts    = $wpdb->get_var( "SELECT fetched_at FROM $table ORDER BY fetched_at DESC LIMIT 1" ); // phpcs:ignore WordPress.DB
		if ( ! $ts || '0000-00-00 00:00:00' === $ts ) {
			return '—';
		}
		$ago = human_time_diff( strtotime( $ts ), time() );
		return sprintf(
			/* translators: %s time ago */
			__( '%s ago', 'ewo-rss-engine' ),
			$ago
		);
	}

	/**
	 * Top N strategic domains by source count.
	 *
	 * @param int $limit Number to return.
	 * @return array<int,object> Each has name, source_count.
	 */
	protected function top_domains( $limit = 5 ) {
		global $wpdb;
		$src   = EWO_RSS_Source_Store::table();
		$dom   = EWO_RSS_Taxonomy::domains_table();
		$limit = (int) $limit;
		return (array) $wpdb->get_results( // phpcs:ignore WordPress.DB
			"SELECT d.name, COUNT(s.id) AS source_count
			 FROM $dom d
			 LEFT JOIN $src s ON s.domain_id = d.id
			 GROUP BY d.id, d.name
			 ORDER BY source_count DESC, d.name ASC
			 LIMIT $limit"
		);
	}

	/**
	 * Most recently added keywords.
	 *
	 * @param int $limit Number to return.
	 * @return array<int,object>
	 */
	protected function recent_keywords( $limit = 5 ) {
		global $wpdb;
		$kw    = EWO_RSS_Taxonomy::keywords_table();
		$sub   = EWO_RSS_Taxonomy::subdomains_table();
		$dom   = EWO_RSS_Taxonomy::domains_table();
		$limit = (int) $limit;
		return (array) $wpdb->get_results( // phpcs:ignore WordPress.DB
			"SELECT k.keyword, k.active, k.created_at, s.name AS subdomain_name, d.name AS domain_name
			 FROM $kw k
			 LEFT JOIN $sub s ON k.subdomain_id = s.id
			 LEFT JOIN $dom d ON s.domain_id = d.id
			 ORDER BY k.created_at DESC
			 LIMIT $limit"
		);
	}

	/* ---------------------------------------------------------------------
	 * Dashboard render
	 * ------------------------------------------------------------------- */

	/**
	 * Render the dashboard page.
	 */
	public function render_dashboard() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}

		// Metrics.
		$total_sources   = EWO_RSS_Source_Store::count( array() );
		$active_keywords = count( EWO_RSS_Taxonomy::get_active_keywords() );
		$all_feeds       = EWO_RSS_Feed::all();
		$active_feeds    = count( array_filter( $all_feeds, function ( $fid ) {
			return EWO_RSS_Feed::STATUS_ENABLED === EWO_RSS_Feed::status( $fid );
		} ) );
		$domains    = EWO_RSS_Taxonomy::get_domains();
		$subdomains = EWO_RSS_Taxonomy::get_subdomains();
		$last_fetch = $this->last_fetch_time();

		// Panel data.
		$recent_sources = EWO_RSS_Source_Store::query( array( 'limit' => 8 ) );
		$top_domains    = $this->top_domains( 8 );
		$recent_kws     = $this->recent_keywords( 8 );

		// Feed sources + logs.
		$source_ids = $this->sources->get_all_sources();
		$logs       = array_slice( EWO_RSS_Logs::all(), 0, 10 );
		$next_run   = wp_next_scheduled( EWO_RSS_ENGINE_CRON_HOOK );
		?>
		<div class="wrap">
		<div class="ewo-db-page">

			<!-- ============================================================
			     Page header
			     ============================================================ -->
			<div class="ewo-db-header">
				<div class="ewo-db-header-left">
					<h1 class="ewo-db-title"><?php esc_html_e( 'EWO RSS Engine', 'ewo-rss-engine' ); ?></h1>
					<p class="ewo-db-subtitle">
						<?php esc_html_e( 'Internal RSS/news ingestion engine for Emerging World Order 2025.', 'ewo-rss-engine' ); ?>
						<?php if ( $next_run ) : ?>
							<span class="ewo-db-subtitle-cron">
								&middot;
								<?php
								printf(
									/* translators: %s: time diff */
									esc_html__( 'Next import in %s', 'ewo-rss-engine' ),
									esc_html( human_time_diff( time(), $next_run ) )
								);
								?>
							</span>
						<?php endif; ?>
					</p>
				</div>
				<div class="ewo-db-header-right">
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="<?php echo esc_attr( self::RUN_ACTION ); ?>" />
						<input type="hidden" name="source_id" value="0" />
						<?php wp_nonce_field( self::RUN_ACTION ); ?>
						<button type="submit" class="button button-primary ewo-db-run-btn">
							<span class="dashicons dashicons-update" aria-hidden="true"></span>
							<?php esc_html_e( 'Run All Imports Now', 'ewo-rss-engine' ); ?>
						</button>
					</form>
				</div>
			</div>

			<!-- ============================================================
			     Stat cards
			     ============================================================ -->
			<div class="ewo-db-stats">

				<div class="ewo-db-stat">
					<span class="ewo-db-stat-label"><?php esc_html_e( 'Total Sources', 'ewo-rss-engine' ); ?></span>
					<span class="ewo-db-stat-value"><?php echo esc_html( number_format_i18n( $total_sources ) ); ?></span>
					<span class="ewo-db-stat-helper"><?php esc_html_e( 'Stored source items', 'ewo-rss-engine' ); ?></span>
				</div>

				<div class="ewo-db-stat">
					<span class="ewo-db-stat-label"><?php esc_html_e( 'Active Keywords', 'ewo-rss-engine' ); ?></span>
					<span class="ewo-db-stat-value"><?php echo esc_html( number_format_i18n( $active_keywords ) ); ?></span>
					<span class="ewo-db-stat-helper"><?php esc_html_e( 'Keywords generating feeds', 'ewo-rss-engine' ); ?></span>
				</div>

				<div class="ewo-db-stat">
					<span class="ewo-db-stat-label"><?php esc_html_e( 'Active Feeds', 'ewo-rss-engine' ); ?></span>
					<span class="ewo-db-stat-value"><?php echo esc_html( number_format_i18n( $active_feeds ) ); ?></span>
					<span class="ewo-db-stat-helper"><?php esc_html_e( 'RSS feeds enabled', 'ewo-rss-engine' ); ?></span>
				</div>

				<div class="ewo-db-stat">
					<span class="ewo-db-stat-label"><?php esc_html_e( 'Strategic Domains', 'ewo-rss-engine' ); ?></span>
					<span class="ewo-db-stat-value"><?php echo esc_html( number_format_i18n( count( $domains ) ) ); ?></span>
					<span class="ewo-db-stat-helper"><?php esc_html_e( 'Configured domains', 'ewo-rss-engine' ); ?></span>
				</div>

				<div class="ewo-db-stat">
					<span class="ewo-db-stat-label"><?php esc_html_e( 'Subdomains', 'ewo-rss-engine' ); ?></span>
					<span class="ewo-db-stat-value"><?php echo esc_html( number_format_i18n( count( $subdomains ) ) ); ?></span>
					<span class="ewo-db-stat-helper"><?php esc_html_e( 'Configured subdomains', 'ewo-rss-engine' ); ?></span>
				</div>

				<div class="ewo-db-stat">
					<span class="ewo-db-stat-label"><?php esc_html_e( 'Last Fetch', 'ewo-rss-engine' ); ?></span>
					<span class="ewo-db-stat-value ewo-db-stat-value--text"><?php echo esc_html( $last_fetch ); ?></span>
					<span class="ewo-db-stat-helper"><?php esc_html_e( 'Most recent import', 'ewo-rss-engine' ); ?></span>
				</div>

			</div><!-- .ewo-db-stats -->

			<!-- ============================================================
			     Two-column body
			     ============================================================ -->
			<div class="ewo-db-body">

				<!-- ========== LEFT COLUMN ========== -->
				<div class="ewo-db-col-main">

					<!-- Recent Source Views -->
					<div class="ewo-db-card">
						<div class="ewo-db-card-header">
							<h2 class="ewo-db-card-title"><?php esc_html_e( 'Recent Source Views', 'ewo-rss-engine' ); ?></h2>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=ewo-rss-sources' ) ); ?>" class="ewo-db-card-action">
								<?php esc_html_e( 'View all →', 'ewo-rss-engine' ); ?>
							</a>
						</div>
						<?php if ( empty( $recent_sources ) ) : ?>
							<p class="ewo-db-empty"><?php esc_html_e( 'No sources captured yet.', 'ewo-rss-engine' ); ?></p>
						<?php else : ?>
							<div class="ewo-db-card-body--flush">
								<table class="ewo-db-table">
									<thead>
										<tr>
											<th><?php esc_html_e( 'Title', 'ewo-rss-engine' ); ?></th>
											<th><?php esc_html_e( 'Publication', 'ewo-rss-engine' ); ?></th>
											<th><?php esc_html_e( 'Subdomain', 'ewo-rss-engine' ); ?></th>
											<th><?php esc_html_e( 'Date', 'ewo-rss-engine' ); ?></th>
											<th><?php esc_html_e( 'View', 'ewo-rss-engine' ); ?></th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ( $recent_sources as $src ) : ?>
											<?php
											$detail_url = admin_url( 'admin.php?page=ewo-rss-sources&view_source=' . (int) $src->id );
											$subdomain  = EWO_RSS_Taxonomy::get_subdomain( (int) $src->subdomain_id );
											?>
											<tr>
												<td class="ewo-db-td-title">
													<a href="<?php echo esc_url( $detail_url ); ?>" class="ewo-db-title-link">
														<?php echo esc_html( $src->title ); ?>
													</a>
												</td>
												<td class="ewo-db-td-muted ewo-db-td-nowrap"><?php echo esc_html( $src->source_domain ?: '—' ); ?></td>
												<td class="ewo-db-td-muted"><?php echo esc_html( $subdomain ? $subdomain->name : '—' ); ?></td>
												<td class="ewo-db-td-muted ewo-db-td-nowrap">
													<?php echo esc_html( $src->published_at ? substr( $src->published_at, 0, 10 ) : '—' ); ?>
												</td>
												<td>
													<a href="<?php echo esc_url( $detail_url ); ?>" class="ewo-db-view-link">
														<?php esc_html_e( 'View', 'ewo-rss-engine' ); ?>
													</a>
												</td>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							</div>
						<?php endif; ?>
					</div><!-- /Recent Source Views -->

					<!-- Feed Sources -->
					<div class="ewo-db-card">
						<div class="ewo-db-card-header">
							<h2 class="ewo-db-card-title"><?php esc_html_e( 'Feed Sources', 'ewo-rss-engine' ); ?></h2>
							<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . EWO_RSS_Sources::POST_TYPE ) ); ?>" class="ewo-db-card-action">
								<?php esc_html_e( '+ Add new', 'ewo-rss-engine' ); ?>
							</a>
						</div>
						<?php if ( empty( $source_ids ) ) : ?>
							<p class="ewo-db-empty">
								<?php esc_html_e( 'No feed sources configured.', 'ewo-rss-engine' ); ?>
								<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . EWO_RSS_Sources::POST_TYPE ) ); ?>">
									<?php esc_html_e( 'Add one →', 'ewo-rss-engine' ); ?>
								</a>
							</p>
						<?php else : ?>
							<div class="ewo-db-card-body--flush">
								<table class="ewo-db-table">
									<thead>
										<tr>
											<th><?php esc_html_e( 'Source', 'ewo-rss-engine' ); ?></th>
											<th><?php esc_html_e( 'Feed URL', 'ewo-rss-engine' ); ?></th>
											<th><?php esc_html_e( 'Status', 'ewo-rss-engine' ); ?></th>
											<th><?php esc_html_e( 'Last Run', 'ewo-rss-engine' ); ?></th>
											<th><?php esc_html_e( 'Actions', 'ewo-rss-engine' ); ?></th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ( $source_ids as $source_id ) : ?>
											<?php $settings = $this->sources->get_settings( $source_id ); ?>
											<tr>
												<td class="ewo-db-td-title">
													<a href="<?php echo esc_url( get_edit_post_link( $source_id ) ); ?>" class="ewo-db-title-link">
														<?php echo esc_html( $settings['name'] ); ?>
													</a>
												</td>
												<td class="ewo-db-td-code">
													<span class="ewo-db-code-cell"><?php echo esc_html( $settings['feed_url'] ); ?></span>
												</td>
												<td>
													<?php if ( $settings['enabled'] ) : ?>
														<span class="ewo-db-badge ewo-db-badge--green"><?php esc_html_e( 'Enabled', 'ewo-rss-engine' ); ?></span>
													<?php else : ?>
														<span class="ewo-db-badge ewo-db-badge--grey"><?php esc_html_e( 'Disabled', 'ewo-rss-engine' ); ?></span>
													<?php endif; ?>
												</td>
												<td class="ewo-db-td-muted ewo-db-td-nowrap">
													<?php echo esc_html( '' !== $settings['last_run'] ? $settings['last_run'] : '—' ); ?>
												</td>
												<td>
													<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin:0;padding:0;">
														<input type="hidden" name="action" value="<?php echo esc_attr( self::RUN_ACTION ); ?>" />
														<input type="hidden" name="source_id" value="<?php echo esc_attr( (string) $source_id ); ?>" />
														<?php wp_nonce_field( self::RUN_ACTION ); ?>
														<button type="submit" class="button button-secondary button-small">
															<?php esc_html_e( 'Run', 'ewo-rss-engine' ); ?>
														</button>
													</form>
												</td>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							</div>
						<?php endif; ?>
					</div><!-- /Feed Sources -->

					<!-- Recent Activity -->
					<div class="ewo-db-card">
						<div class="ewo-db-card-header">
							<h2 class="ewo-db-card-title"><?php esc_html_e( 'Recent Activity', 'ewo-rss-engine' ); ?></h2>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::LOGS_SLUG ) ); ?>" class="ewo-db-card-action">
								<?php esc_html_e( 'View all logs →', 'ewo-rss-engine' ); ?>
							</a>
						</div>
						<?php if ( empty( $logs ) ) : ?>
							<p class="ewo-db-empty"><?php esc_html_e( 'No activity logged yet.', 'ewo-rss-engine' ); ?></p>
						<?php else : ?>
							<div class="ewo-db-card-body--flush">
								<table class="ewo-db-table">
									<thead>
										<tr>
											<th><?php esc_html_e( 'Time', 'ewo-rss-engine' ); ?></th>
											<th><?php esc_html_e( 'Source', 'ewo-rss-engine' ); ?></th>
											<th class="ewo-db-th-center"><?php esc_html_e( 'Found', 'ewo-rss-engine' ); ?></th>
											<th class="ewo-db-th-center"><?php esc_html_e( 'Created', 'ewo-rss-engine' ); ?></th>
											<th class="ewo-db-th-center"><?php esc_html_e( 'Skipped', 'ewo-rss-engine' ); ?></th>
											<th class="ewo-db-th-center"><?php esc_html_e( 'Errors', 'ewo-rss-engine' ); ?></th>
											<th><?php esc_html_e( 'Message', 'ewo-rss-engine' ); ?></th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ( $logs as $entry ) : ?>
											<?php $has_error = ( isset( $entry['errors'] ) && (int) $entry['errors'] > 0 ); ?>
											<tr <?php echo $has_error ? 'class="ewo-db-tr-error"' : ''; ?>>
												<td class="ewo-db-td-muted ewo-db-td-nowrap"><?php echo esc_html( isset( $entry['time'] ) ? $entry['time'] : '' ); ?></td>
												<td><?php echo esc_html( isset( $entry['source_name'] ) ? $entry['source_name'] : '' ); ?></td>
												<td class="ewo-db-td-num"><?php echo esc_html( (string) ( isset( $entry['found'] ) ? $entry['found'] : 0 ) ); ?></td>
												<td class="ewo-db-td-num ewo-db-td-created"><?php echo esc_html( (string) ( isset( $entry['created'] ) ? $entry['created'] : 0 ) ); ?></td>
												<td class="ewo-db-td-num"><?php echo esc_html( (string) ( isset( $entry['skipped'] ) ? $entry['skipped'] : 0 ) ); ?></td>
												<td class="ewo-db-td-num <?php echo $has_error ? 'ewo-db-td-error' : ''; ?>">
													<?php echo esc_html( (string) ( isset( $entry['errors'] ) ? $entry['errors'] : 0 ) ); ?>
												</td>
												<td class="ewo-db-td-muted"><?php echo esc_html( isset( $entry['message'] ) ? $entry['message'] : '' ); ?></td>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							</div>
						<?php endif; ?>
					</div><!-- /Recent Activity -->

				</div><!-- .ewo-db-col-main -->

				<!-- ========== RIGHT COLUMN ========== -->
				<div class="ewo-db-col-side">

					<!-- Top Domains -->
					<div class="ewo-db-card">
						<div class="ewo-db-card-header">
							<h2 class="ewo-db-card-title"><?php esc_html_e( 'Top Domains', 'ewo-rss-engine' ); ?></h2>
						</div>
						<?php if ( empty( $top_domains ) ) : ?>
							<p class="ewo-db-empty"><?php esc_html_e( 'No domains yet.', 'ewo-rss-engine' ); ?></p>
						<?php else : ?>
							<ul class="ewo-db-side-list">
								<?php foreach ( $top_domains as $td ) : ?>
									<li class="ewo-db-side-item ewo-db-side-item--row">
										<span class="ewo-db-side-name"><?php echo esc_html( $td->name ); ?></span>
										<span class="ewo-db-side-count">
											<?php echo esc_html( number_format_i18n( (int) $td->source_count ) ); ?>
											<?php esc_html_e( 'sources', 'ewo-rss-engine' ); ?>
										</span>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</div><!-- /Top Domains -->

					<!-- Recent Keywords -->
					<div class="ewo-db-card">
						<div class="ewo-db-card-header">
							<h2 class="ewo-db-card-title"><?php esc_html_e( 'Recent Keywords', 'ewo-rss-engine' ); ?></h2>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=ewo-rss-domains' ) ); ?>" class="ewo-db-card-action">
								<?php esc_html_e( 'Manage →', 'ewo-rss-engine' ); ?>
							</a>
						</div>
						<?php if ( empty( $recent_kws ) ) : ?>
							<p class="ewo-db-empty"><?php esc_html_e( 'No keywords yet.', 'ewo-rss-engine' ); ?></p>
						<?php else : ?>
							<ul class="ewo-db-side-list">
								<?php foreach ( $recent_kws as $kw ) : ?>
									<li class="ewo-db-side-item">
										<div class="ewo-db-kw-name">
											<?php echo esc_html( $kw->keyword ); ?>
											<?php if ( ! $kw->active ) : ?>
												<span class="ewo-db-badge ewo-db-badge--grey ewo-db-badge--xs">
													<?php esc_html_e( 'inactive', 'ewo-rss-engine' ); ?>
												</span>
											<?php endif; ?>
										</div>
										<div class="ewo-db-kw-meta">
											<?php if ( ! empty( $kw->subdomain_name ) ) : ?>
												<?php echo esc_html( $kw->subdomain_name ); ?>
												<?php if ( ! empty( $kw->domain_name ) ) : ?>
													<span class="ewo-db-dot">&middot;</span><?php echo esc_html( $kw->domain_name ); ?>
												<?php endif; ?>
											<?php else : ?>
												<?php echo esc_html( $kw->domain_name ?? '—' ); ?>
											<?php endif; ?>
										</div>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</div><!-- /Recent Keywords -->

					<!-- Quick Actions -->
					<div class="ewo-db-card">
						<div class="ewo-db-card-header">
							<h2 class="ewo-db-card-title"><?php esc_html_e( 'Quick Actions', 'ewo-rss-engine' ); ?></h2>
						</div>
						<div class="ewo-db-card-body">
							<ul class="ewo-db-quick-list">

								<li>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=ewo-rss-domains' ) ); ?>" class="ewo-db-quick-btn">
										<span class="dashicons dashicons-networking" aria-hidden="true"></span>
										<?php esc_html_e( 'Manage Strategic Domains', 'ewo-rss-engine' ); ?>
									</a>
								</li>

								<li>
									<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . EWO_RSS_Sources::POST_TYPE ) ); ?>" class="ewo-db-quick-btn">
										<span class="dashicons dashicons-rss" aria-hidden="true"></span>
										<?php esc_html_e( 'Manage Feed Sources', 'ewo-rss-engine' ); ?>
									</a>
								</li>

								<li>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::LOGS_SLUG ) ); ?>" class="ewo-db-quick-btn">
										<span class="dashicons dashicons-list-view" aria-hidden="true"></span>
										<?php esc_html_e( 'View Import Logs', 'ewo-rss-engine' ); ?>
									</a>
								</li>

								<li>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ewo-db-quick-form">
										<input type="hidden" name="action" value="<?php echo esc_attr( self::RUN_ACTION ); ?>" />
										<input type="hidden" name="source_id" value="0" />
										<?php wp_nonce_field( self::RUN_ACTION ); ?>
										<button type="submit" class="ewo-db-quick-btn ewo-db-quick-btn--primary">
											<span class="dashicons dashicons-update" aria-hidden="true"></span>
											<?php esc_html_e( 'Run All Imports', 'ewo-rss-engine' ); ?>
										</button>
									</form>
								</li>

							</ul>
						</div>
					</div><!-- /Quick Actions -->

				</div><!-- .ewo-db-col-side -->

			</div><!-- .ewo-db-body -->

		</div><!-- .ewo-db-page -->
		</div><!-- .wrap -->
		<?php
	}

	/**
	 * Render the full logs page.
	 */
	public function render_logs() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}
		?>
		<div class="wrap ewo-rss-wrap">
			<h1><?php esc_html_e( 'Import Logs', 'ewo-rss-engine' ); ?></h1>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::CLEAR_ACTION ); ?>" />
				<?php wp_nonce_field( self::CLEAR_ACTION ); ?>
				<?php submit_button( __( 'Clear Logs', 'ewo-rss-engine' ), 'delete', 'submit', false ); ?>
			</form>

			<?php $this->render_logs_table( EWO_RSS_Logs::all() ); ?>
		</div>
		<?php
	}

	/**
	 * Render a logs table.
	 *
	 * @param array<int,array<string,mixed>> $logs Log entries.
	 */
	protected function render_logs_table( $logs ) {
		if ( empty( $logs ) ) {
			echo '<p>' . esc_html__( 'No log entries yet.', 'ewo-rss-engine' ) . '</p>';
			return;
		}
		?>
		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Time', 'ewo-rss-engine' ); ?></th>
					<th><?php esc_html_e( 'Source', 'ewo-rss-engine' ); ?></th>
					<th><?php esc_html_e( 'Found', 'ewo-rss-engine' ); ?></th>
					<th><?php esc_html_e( 'Created', 'ewo-rss-engine' ); ?></th>
					<th><?php esc_html_e( 'Skipped', 'ewo-rss-engine' ); ?></th>
					<th><?php esc_html_e( 'Errors', 'ewo-rss-engine' ); ?></th>
					<th><?php esc_html_e( 'Message', 'ewo-rss-engine' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $logs as $entry ) : ?>
					<tr>
						<td><?php echo esc_html( isset( $entry['time'] ) ? $entry['time'] : '' ); ?></td>
						<td><?php echo esc_html( isset( $entry['source_name'] ) ? $entry['source_name'] : '' ); ?></td>
						<td><?php echo esc_html( (string) ( isset( $entry['found'] ) ? $entry['found'] : 0 ) ); ?></td>
						<td><?php echo esc_html( (string) ( isset( $entry['created'] ) ? $entry['created'] : 0 ) ); ?></td>
						<td><?php echo esc_html( (string) ( isset( $entry['skipped'] ) ? $entry['skipped'] : 0 ) ); ?></td>
						<td><?php echo esc_html( (string) ( isset( $entry['errors'] ) ? $entry['errors'] : 0 ) ); ?></td>
						<td><?php echo esc_html( isset( $entry['message'] ) ? $entry['message'] : '' ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Handle a manual import request (all sources or one).
	 */
	public function handle_run() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You are not allowed to do that.', 'ewo-rss-engine' ) );
		}

		check_admin_referer( self::RUN_ACTION );

		$source_id = isset( $_POST['source_id'] ) ? absint( wp_unslash( $_POST['source_id'] ) ) : 0;

		if ( $source_id > 0 ) {
			$result = $this->importer->import_source( $source_id );
		} else {
			$result = $this->importer->import_all();
		}

		$message = sprintf(
			/* translators: 1: created count, 2: skipped count, 3: error count. */
			__( 'Import finished — %1$d created, %2$d skipped, %3$d errors.', 'ewo-rss-engine' ),
			(int) $result['created'],
			(int) $result['skipped'],
			(int) $result['errors']
		);

		$this->set_notice( $message );
		$this->redirect_back();
	}

	/**
	 * Handle a clear-logs request.
	 */
	public function handle_clear_logs() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You are not allowed to do that.', 'ewo-rss-engine' ) );
		}

		check_admin_referer( self::CLEAR_ACTION );

		EWO_RSS_Logs::clear();
		$this->set_notice( __( 'Logs cleared.', 'ewo-rss-engine' ) );

		wp_safe_redirect( admin_url( 'admin.php?page=' . self::LOGS_SLUG ) );
		exit;
	}

	/**
	 * Store a one-time admin notice for the current user.
	 *
	 * @param string $message Notice text.
	 */
	protected function set_notice( $message ) {
		set_transient( self::NOTICE_TRANSIENT_PREFIX . get_current_user_id(), $message, MINUTE_IN_SECONDS );
	}

	/**
	 * Render and consume a stored admin notice.
	 */
	public function render_notice() {
		$key     = self::NOTICE_TRANSIENT_PREFIX . get_current_user_id();
		$message = get_transient( $key );

		if ( false === $message ) {
			return;
		}

		delete_transient( $key );

		printf(
			'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
			esc_html( $message )
		);
	}

	/**
	 * Redirect back to the dashboard.
	 */
	protected function redirect_back() {
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::MENU_SLUG ) );
		exit;
	}
}
