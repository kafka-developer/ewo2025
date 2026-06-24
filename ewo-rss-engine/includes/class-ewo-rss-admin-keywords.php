<?php
/**
 * Admin screen: Strategic Domains → Subdomains → Keywords.
 *
 * Manages the keyword hierarchy and triggers keyword-feed fetches. All writes
 * go through a single nonce-checked admin-post handler dispatched on an action
 * field; only manage_options users reach the page.
 *
 * @package EWO_RSS_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Keyword management admin controller.
 */
class EWO_RSS_Admin_Keywords {
	const MENU_SLUG  = 'ewo-rss-keywords';
	const PARENT     = 'ewo-rss-engine';
	const CAP        = 'manage_options';
	const ACTION     = 'ewo_rss_keywords';
	const NONCE      = 'ewo_rss_keywords_nonce';
	const NOTICE_KEY = 'ewo_rss_kw_notice_';

	/**
	 * Page hook suffix.
	 *
	 * @var string
	 */
	protected $hook = '';

	/**
	 * Register hooks.
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_post_' . self::ACTION, array( $this, 'handle' ) );
		add_action( 'admin_notices', array( $this, 'render_notice' ) );
	}

	/**
	 * Add the submenu.
	 */
	public function register_menu() {
		$this->hook = (string) add_submenu_page(
			self::PARENT,
			__( 'Keywords', 'ewo-rss-engine' ),
			__( 'Keywords', 'ewo-rss-engine' ),
			self::CAP,
			self::MENU_SLUG,
			array( $this, 'render' )
		);
	}

	/* ---------------------------------------------------------------------
	 * Stats helpers
	 * ------------------------------------------------------------------- */

	/**
	 * Subdomain/keyword/source counts for a domain.
	 *
	 * @param int $domain_id Domain ID.
	 * @return array{subdomains:int,keywords:int,sources:int}
	 */
	protected function get_domain_stats( $domain_id ) {
		global $wpdb;
		$domain_id  = (int) $domain_id;
		$sub_table  = EWO_RSS_Taxonomy::subdomains_table();
		$kw_table   = EWO_RSS_Taxonomy::keywords_table();
		$src_table  = EWO_RSS_Source_Store::table();

		$subdomains = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $sub_table WHERE domain_id = %d", $domain_id ) ); // phpcs:ignore WordPress.DB
		$keywords   = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $kw_table k INNER JOIN $sub_table s ON k.subdomain_id = s.id WHERE s.domain_id = %d", $domain_id ) ); // phpcs:ignore WordPress.DB
		$sources    = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $src_table WHERE domain_id = %d", $domain_id ) ); // phpcs:ignore WordPress.DB

		return array(
			'subdomains' => $subdomains,
			'keywords'   => $keywords,
			'sources'    => $sources,
		);
	}

	/**
	 * Keyword/source counts for a subdomain.
	 *
	 * @param int $subdomain_id Subdomain ID.
	 * @return array{keywords:int,sources:int}
	 */
	protected function get_subdomain_stats( $subdomain_id ) {
		global $wpdb;
		$subdomain_id = (int) $subdomain_id;
		$kw_table     = EWO_RSS_Taxonomy::keywords_table();
		$src_table    = EWO_RSS_Source_Store::table();

		$keywords = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $kw_table WHERE subdomain_id = %d", $subdomain_id ) ); // phpcs:ignore WordPress.DB
		$sources  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $src_table WHERE subdomain_id = %d", $subdomain_id ) ); // phpcs:ignore WordPress.DB

		return array(
			'keywords' => $keywords,
			'sources'  => $sources,
		);
	}

	/**
	 * Article count captured for a keyword.
	 *
	 * @param int $keyword_id Keyword ID.
	 * @return int
	 */
	protected function get_keyword_article_count( $keyword_id ) {
		return EWO_RSS_Source_Store::count( array( 'keyword_id' => (int) $keyword_id ) );
	}

	/**
	 * Last-fetch timestamp for a keyword's feed.
	 *
	 * @param int $feed_id Feed (source) post ID.
	 * @return string Human-readable or '—'.
	 */
	protected function get_keyword_last_fetch( $feed_id ) {
		if ( $feed_id <= 0 ) {
			return '—';
		}
		$metrics = EWO_RSS_Feed::metrics( $feed_id );
		$ts      = isset( $metrics['last_success'] ) ? $metrics['last_success'] : '';
		if ( '' === $ts || '—' === $ts ) {
			return '—';
		}
		return esc_html( $ts );
	}

	/**
	 * Feed RSS URL for a keyword's generated feed.
	 *
	 * @param int $feed_id Feed (source) post ID.
	 * @return string URL or ''.
	 */
	protected function get_keyword_feed_url( $feed_id ) {
		if ( $feed_id <= 0 ) {
			return '';
		}
		return EWO_RSS_Feed::url( $feed_id );
	}

	/* ---------------------------------------------------------------------
	 * Page
	 * ------------------------------------------------------------------- */

	/**
	 * Render the management page.
	 */
	public function render() {
		if ( ! current_user_can( self::CAP ) ) {
			return;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$kw_search    = isset( $_GET['kw_search'] ) ? sanitize_text_field( wp_unslash( $_GET['kw_search'] ) ) : '';
		$filter_domain = isset( $_GET['kw_domain'] ) ? absint( wp_unslash( $_GET['kw_domain'] ) ) : 0;
		$filter_sub    = isset( $_GET['kw_subdomain'] ) ? absint( wp_unslash( $_GET['kw_subdomain'] ) ) : 0;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$has_filter = ( '' !== $kw_search || $filter_domain > 0 || $filter_sub > 0 );
		$domains    = EWO_RSS_Taxonomy::get_domains();

		// When filtering by domain, restrict to that domain.
		if ( $filter_domain > 0 ) {
			$domains = array_values( array_filter( $domains, function ( $d ) use ( $filter_domain ) {
				return (int) $d->id === $filter_domain;
			} ) );
		}
		?>
		<div class="wrap ewo-rss-wrap ewo-kw-page">
			<h1><?php esc_html_e( 'Keywords', 'ewo-rss-engine' ); ?></h1>
			<p class="ewo-rss-tagline">
				<?php esc_html_e( 'Domain → Subdomain → Keyword. Each active keyword auto-generates a Google News RSS feed that is fetched into Sources.', 'ewo-rss-engine' ); ?>
			</p>

			<div class="ewo-kw-toolbar">
				<form method="get" class="ewo-kw-filter-form">
					<input type="hidden" name="page" value="<?php echo esc_attr( self::MENU_SLUG ); ?>" />
					<input type="search" name="kw_search" value="<?php echo esc_attr( $kw_search ); ?>"
						placeholder="<?php esc_attr_e( 'Search keywords…', 'ewo-rss-engine' ); ?>"
						class="ewo-kw-search-input" />
					<select name="kw_domain">
						<option value="0"><?php esc_html_e( 'All Domains', 'ewo-rss-engine' ); ?></option>
						<?php foreach ( EWO_RSS_Taxonomy::get_domains() as $d ) : ?>
							<option value="<?php echo esc_attr( (string) $d->id ); ?>" <?php selected( $filter_domain, (int) $d->id ); ?>>
								<?php echo esc_html( $d->name ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<select name="kw_subdomain">
						<option value="0"><?php esc_html_e( 'All Subdomains', 'ewo-rss-engine' ); ?></option>
						<?php foreach ( EWO_RSS_Taxonomy::get_subdomains() as $s ) : ?>
							<option value="<?php echo esc_attr( (string) $s->id ); ?>" <?php selected( $filter_sub, (int) $s->id ); ?>>
								<?php echo esc_html( $s->name ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<?php submit_button( __( 'Filter', 'ewo-rss-engine' ), 'secondary', 'submit', false ); ?>
					<?php if ( $has_filter ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::MENU_SLUG ) ); ?>" class="button-link ewo-kw-reset">
							<?php esc_html_e( '✕ Reset', 'ewo-rss-engine' ); ?>
						</a>
					<?php endif; ?>
				</form>

				<div class="ewo-kw-global-actions">
					<?php echo $this->fetch_button( 'fetch_all', 0, __( 'Fetch All Active Feeds Now', 'ewo-rss-engine' ), 'primary' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</div>

			<div class="ewo-kw-add-domain-bar">
				<strong><?php esc_html_e( 'Add Domain:', 'ewo-rss-engine' ); ?></strong>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ewo-kw-inline">
					<?php $this->form_head( 'add_domain' ); ?>
					<input type="text" name="name" required placeholder="<?php esc_attr_e( 'e.g. Energy', 'ewo-rss-engine' ); ?>" />
					<?php submit_button( __( 'Add Domain', 'ewo-rss-engine' ), 'secondary', 'submit', false ); ?>
				</form>
			</div>

			<?php if ( empty( $domains ) ) : ?>
				<p class="ewo-kw-empty"><?php esc_html_e( 'No domains yet. Add one above to get started.', 'ewo-rss-engine' ); ?></p>
			<?php else : ?>
				<div class="ewo-kw-domains">
					<?php foreach ( $domains as $domain ) : ?>
						<?php $this->render_domain( $domain, $kw_search, $filter_sub, $has_filter ); ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render one domain card.
	 *
	 * @param object $domain     Domain row.
	 * @param string $kw_search  Keyword search string.
	 * @param int    $filter_sub Subdomain filter ID.
	 * @param bool   $has_filter Whether any filter is active.
	 */
	protected function render_domain( $domain, $kw_search = '', $filter_sub = 0, $has_filter = false ) {
		$domain_id  = (int) $domain->id;
		$stats      = $this->get_domain_stats( $domain_id );
		$subdomains = EWO_RSS_Taxonomy::get_subdomains( $domain_id );

		// When filtering by subdomain, restrict to that subdomain.
		if ( $filter_sub > 0 ) {
			$subdomains = array_values( array_filter( $subdomains, function ( $s ) use ( $filter_sub ) {
				return (int) $s->id === $filter_sub;
			} ) );
		}

		// Determine if this domain should start expanded.
		$expanded = $has_filter;
		$body_id  = 'ewo-domain-body-' . $domain_id;
		?>
		<div class="ewo-kw-domain-card">
			<div class="ewo-kw-domain-header" data-toggle="<?php echo esc_attr( $body_id ); ?>">
				<div class="ewo-kw-domain-title-wrap">
					<span class="ewo-kw-toggle-icon"><?php echo $expanded ? '▼' : '▶'; ?></span>
					<h2 class="ewo-kw-domain-name"><?php echo esc_html( $domain->name ); ?></h2>
					<span class="ewo-kw-domain-meta">
						<?php
						printf(
							/* translators: 1: subdomain count, 2: keyword count, 3: source count */
							esc_html__( '%1$d subdomains · %2$d keywords · %3$d sources', 'ewo-rss-engine' ),
							(int) $stats['subdomains'],
							(int) $stats['keywords'],
							(int) $stats['sources']
						);
						?>
					</span>
				</div>
				<div class="ewo-kw-domain-actions" onclick="event.stopPropagation();">
					<?php echo $this->fetch_button( 'fetch_domain', $domain_id, __( 'Fetch Domain', 'ewo-rss-engine' ), 'secondary small' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php
					echo $this->confirm_button( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						'delete_domain',
						array( 'domain_id' => $domain_id ),
						__( 'Delete', 'ewo-rss-engine' ),
						__( 'Delete this domain and all its subdomains, keywords and generated feeds?', 'ewo-rss-engine' ),
						'small delete'
					);
					?>
				</div>
			</div>

			<div class="ewo-kw-domain-body" id="<?php echo esc_attr( $body_id ); ?>" <?php echo $expanded ? '' : 'style="display:none;"'; ?>>

				<div class="ewo-kw-add-sub-bar">
					<strong><?php esc_html_e( 'Add Subdomain:', 'ewo-rss-engine' ); ?></strong>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ewo-kw-inline">
						<?php $this->form_head( 'add_subdomain' ); ?>
						<input type="hidden" name="domain_id" value="<?php echo esc_attr( (string) $domain_id ); ?>" />
						<input type="text" name="name" required placeholder="<?php esc_attr_e( 'e.g. LNG', 'ewo-rss-engine' ); ?>" />
						<?php submit_button( __( 'Add Subdomain', 'ewo-rss-engine' ), 'secondary small', 'submit', false ); ?>
					</form>
				</div>

				<?php if ( empty( $subdomains ) ) : ?>
					<p class="description ewo-kw-empty"><?php esc_html_e( 'No subdomains yet.', 'ewo-rss-engine' ); ?></p>
				<?php else : ?>
					<div class="ewo-kw-subdomains">
						<?php foreach ( $subdomains as $subdomain ) : ?>
							<?php $this->render_subdomain( $subdomain, $kw_search, $has_filter ); ?>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render one subdomain section.
	 *
	 * @param object $subdomain  Subdomain row.
	 * @param string $kw_search  Keyword search string.
	 * @param bool   $has_filter Whether any filter is active.
	 */
	protected function render_subdomain( $subdomain, $kw_search = '', $has_filter = false ) {
		$subdomain_id = (int) $subdomain->id;
		$stats        = $this->get_subdomain_stats( $subdomain_id );
		$keywords     = EWO_RSS_Taxonomy::get_keywords( $subdomain_id );

		// Filter keywords by search string.
		if ( '' !== $kw_search ) {
			$search   = strtolower( $kw_search );
			$keywords = array_values( array_filter( $keywords, function ( $k ) use ( $search ) {
				return false !== strpos( strtolower( $k->keyword ), $search );
			} ) );
		}

		// Skip subdomain if search is active and no keywords match.
		if ( '' !== $kw_search && empty( $keywords ) ) {
			return;
		}

		$expanded = $has_filter;
		$body_id  = 'ewo-sub-body-' . $subdomain_id;
		?>
		<div class="ewo-kw-subdomain-card">
			<div class="ewo-kw-subdomain-header" data-toggle="<?php echo esc_attr( $body_id ); ?>">
				<div class="ewo-kw-subdomain-title-wrap">
					<span class="ewo-kw-toggle-icon"><?php echo $expanded ? '▼' : '▶'; ?></span>
					<h3 class="ewo-kw-subdomain-name"><?php echo esc_html( $subdomain->name ); ?></h3>
					<span class="ewo-kw-subdomain-meta">
						<?php
						printf(
							/* translators: 1: keyword count, 2: source count */
							esc_html__( '%1$d keywords · %2$d sources', 'ewo-rss-engine' ),
							(int) $stats['keywords'],
							(int) $stats['sources']
						);
						?>
					</span>
				</div>
				<div class="ewo-kw-subdomain-actions" onclick="event.stopPropagation();">
					<?php echo $this->fetch_button( 'fetch_subdomain', $subdomain_id, __( 'Fetch', 'ewo-rss-engine' ), 'secondary small' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php
					echo $this->confirm_button( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						'delete_subdomain',
						array( 'subdomain_id' => $subdomain_id ),
						__( 'Delete', 'ewo-rss-engine' ),
						__( 'Delete this subdomain and all its keywords and generated feeds?', 'ewo-rss-engine' ),
						'small delete'
					);
					?>
				</div>
			</div>

			<div class="ewo-kw-subdomain-body" id="<?php echo esc_attr( $body_id ); ?>" <?php echo $expanded ? '' : 'style="display:none;"'; ?>>

				<div class="ewo-kw-add-kw-bar">
					<strong><?php esc_html_e( 'Add Keyword:', 'ewo-rss-engine' ); ?></strong>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ewo-kw-inline">
						<?php $this->form_head( 'add_keyword' ); ?>
						<input type="hidden" name="subdomain_id" value="<?php echo esc_attr( (string) $subdomain_id ); ?>" />
						<input type="text" name="keyword" required placeholder="<?php esc_attr_e( 'New keyword', 'ewo-rss-engine' ); ?>" />
						<label><input type="checkbox" name="active" value="1" checked /> <?php esc_html_e( 'Active', 'ewo-rss-engine' ); ?></label>
						<?php submit_button( __( 'Add', 'ewo-rss-engine' ), 'secondary small', 'submit', false ); ?>
					</form>
				</div>

				<?php if ( empty( $keywords ) ) : ?>
					<p class="description ewo-kw-empty"><?php esc_html_e( 'No keywords yet.', 'ewo-rss-engine' ); ?></p>
				<?php else : ?>
					<table class="widefat striped ewo-kw-table">
						<thead>
							<tr>
								<th class="ewo-kw-col-keyword"><?php esc_html_e( 'Keyword', 'ewo-rss-engine' ); ?></th>
								<th class="ewo-kw-col-active"><?php esc_html_e( 'Active', 'ewo-rss-engine' ); ?></th>
								<th class="ewo-kw-col-feed"><?php esc_html_e( 'Feed URL', 'ewo-rss-engine' ); ?></th>
								<th class="ewo-kw-col-fetch"><?php esc_html_e( 'Last Fetch', 'ewo-rss-engine' ); ?></th>
								<th class="ewo-kw-col-articles"><?php esc_html_e( 'Articles', 'ewo-rss-engine' ); ?></th>
								<th class="ewo-kw-col-created"><?php esc_html_e( 'Created', 'ewo-rss-engine' ); ?></th>
								<th class="ewo-kw-col-actions"><?php esc_html_e( 'Actions', 'ewo-rss-engine' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $keywords as $kw ) : ?>
								<?php
								$feed_url    = $this->get_keyword_feed_url( (int) $kw->feed_id );
								$last_fetch  = $this->get_keyword_last_fetch( (int) $kw->feed_id );
								$article_cnt = $this->get_keyword_article_count( (int) $kw->id );
								?>
								<tr class="ewo-kw-row" id="ewo-kw-row-<?php echo esc_attr( (string) $kw->id ); ?>">
									<td class="ewo-kw-col-keyword">
										<span class="ewo-kw-label"><?php echo esc_html( $kw->keyword ); ?></span>
									</td>
									<td class="ewo-kw-col-active">
										<?php if ( $kw->active ) : ?>
											<span class="ewo-kw-badge ewo-kw-badge--active"><?php esc_html_e( 'Active', 'ewo-rss-engine' ); ?></span>
										<?php else : ?>
											<span class="ewo-kw-badge ewo-kw-badge--inactive"><?php esc_html_e( 'Inactive', 'ewo-rss-engine' ); ?></span>
										<?php endif; ?>
									</td>
									<td class="ewo-kw-col-feed">
										<?php if ( '' !== $feed_url ) : ?>
											<a href="<?php echo esc_url( $feed_url ); ?>" target="_blank" rel="noopener noreferrer" class="ewo-kw-feed-link" title="<?php echo esc_attr( $feed_url ); ?>">
												<?php esc_html_e( 'View Feed', 'ewo-rss-engine' ); ?>
											</a>
										<?php else : ?>
											<span class="ewo-kw-na">—</span>
										<?php endif; ?>
									</td>
									<td class="ewo-kw-col-fetch"><?php echo esc_html( $last_fetch ); ?></td>
									<td class="ewo-kw-col-articles"><?php echo esc_html( (string) $article_cnt ); ?></td>
									<td class="ewo-kw-col-created"><?php echo esc_html( isset( $kw->created_at ) ? substr( $kw->created_at, 0, 10 ) : '—' ); ?></td>
									<td class="ewo-kw-col-actions">
										<div class="ewo-kw-actions">
											<?php echo $this->fetch_button( 'fetch_keyword', (int) $kw->id, __( 'Fetch Now', 'ewo-rss-engine' ), 'secondary small' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
											<button type="button" class="button button-secondary small ewo-kw-edit-toggle"
												data-kw-id="<?php echo esc_attr( (string) $kw->id ); ?>">
												<?php esc_html_e( 'Edit', 'ewo-rss-engine' ); ?>
											</button>
											<?php
											echo $this->simple_button( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
												'toggle_keyword',
												array( 'keyword_id' => (int) $kw->id ),
												$kw->active ? __( 'Deactivate', 'ewo-rss-engine' ) : __( 'Activate', 'ewo-rss-engine' ),
												'secondary small'
											);
											?>
											<?php
											echo $this->confirm_button( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
												'delete_keyword',
												array( 'keyword_id' => (int) $kw->id ),
												__( 'Delete', 'ewo-rss-engine' ),
												__( 'Delete this keyword and its generated feed?', 'ewo-rss-engine' ),
												'small delete'
											);
											?>
										</div>
									</td>
								</tr>
								<tr class="ewo-kw-edit-row" id="ewo-kw-edit-<?php echo esc_attr( (string) $kw->id ); ?>" style="display:none;">
									<td colspan="7" class="ewo-kw-edit-cell">
										<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ewo-kw-edit-form">
											<?php $this->form_head( 'edit_keyword' ); ?>
											<input type="hidden" name="keyword_id" value="<?php echo esc_attr( (string) $kw->id ); ?>" />
											<label class="ewo-kw-edit-label"><?php esc_html_e( 'Edit keyword text:', 'ewo-rss-engine' ); ?></label>
											<input type="text" name="keyword" value="<?php echo esc_attr( $kw->keyword ); ?>" required class="ewo-kw-edit-input" />
											<?php submit_button( __( 'Save', 'ewo-rss-engine' ), 'primary small', 'submit', false ); ?>
											<button type="button" class="button button-secondary small ewo-kw-edit-cancel"
												data-kw-id="<?php echo esc_attr( (string) $kw->id ); ?>">
												<?php esc_html_e( 'Cancel', 'ewo-rss-engine' ); ?>
											</button>
										</form>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/* ---------------------------------------------------------------------
	 * Form helpers
	 * ------------------------------------------------------------------- */

	/**
	 * Emit the hidden action + nonce fields shared by every form.
	 *
	 * @param string $kw_action Sub-action.
	 */
	protected function form_head( $kw_action ) {
		echo '<input type="hidden" name="action" value="' . esc_attr( self::ACTION ) . '" />';
		echo '<input type="hidden" name="kw_action" value="' . esc_attr( $kw_action ) . '" />';
		wp_nonce_field( self::ACTION, self::NONCE );
	}

	/**
	 * Build a small single-button form.
	 *
	 * @param string              $kw_action Sub-action.
	 * @param array<string,int>   $fields    Hidden int fields.
	 * @param string              $label     Button label.
	 * @param string              $class     Button class.
	 * @return string HTML.
	 */
	protected function simple_button( $kw_action, array $fields, $label, $class = 'secondary' ) {
		ob_start();
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
			<?php
			$this->form_head( $kw_action );
			foreach ( $fields as $name => $value ) {
				echo '<input type="hidden" name="' . esc_attr( $name ) . '" value="' . esc_attr( (string) $value ) . '" />';
			}
			submit_button( $label, $class, 'submit', false );
			?>
		</form>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * A fetch button (alias of simple_button for clarity at call sites).
	 *
	 * @param string $kw_action  Sub-action.
	 * @param int    $id         Target ID (0 for fetch-all).
	 * @param string $label      Label.
	 * @param string $class      Button class.
	 * @return string HTML.
	 */
	protected function fetch_button( $kw_action, $id, $label, $class = 'secondary' ) {
		$fields = array();
		if ( 'fetch_keyword' === $kw_action ) {
			$fields['keyword_id'] = (int) $id;
		} elseif ( 'fetch_subdomain' === $kw_action ) {
			$fields['subdomain_id'] = (int) $id;
		} elseif ( 'fetch_domain' === $kw_action ) {
			$fields['domain_id'] = (int) $id;
		}
		return $this->simple_button( $kw_action, $fields, $label, $class );
	}

	/**
	 * A destructive button with a JS confirm.
	 *
	 * @param string            $kw_action Sub-action.
	 * @param array<string,int> $fields    Hidden fields.
	 * @param string            $label     Label.
	 * @param string            $confirm   Confirm message.
	 * @param string            $class     Button class.
	 * @return string HTML.
	 */
	protected function confirm_button( $kw_action, array $fields, $label, $confirm, $class = 'small delete' ) {
		ob_start();
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;"
			onsubmit="return confirm( <?php echo esc_attr( wp_json_encode( $confirm ) ); ?> );">
			<?php
			$this->form_head( $kw_action );
			foreach ( $fields as $name => $value ) {
				echo '<input type="hidden" name="' . esc_attr( $name ) . '" value="' . esc_attr( (string) $value ) . '" />';
			}
			submit_button( $label, $class, 'submit', false );
			?>
		</form>
		<?php
		return (string) ob_get_clean();
	}

	/* ---------------------------------------------------------------------
	 * Handler
	 * ------------------------------------------------------------------- */

	/**
	 * Dispatch every keyword admin action through one nonce-checked endpoint.
	 */
	public function handle() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'You are not allowed to do that.', 'ewo-rss-engine' ) );
		}
		check_admin_referer( self::ACTION, self::NONCE );

		$kw_action = isset( $_POST['kw_action'] ) ? sanitize_key( wp_unslash( $_POST['kw_action'] ) ) : '';
		$message   = '';

		switch ( $kw_action ) {
			case 'add_domain':
				$name    = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
				$id      = EWO_RSS_Taxonomy::add_domain( $name );
				$message = $id ? __( 'Domain added.', 'ewo-rss-engine' ) : __( 'Could not add domain.', 'ewo-rss-engine' );
				break;

			case 'delete_domain':
				EWO_RSS_Taxonomy::delete_domain( isset( $_POST['domain_id'] ) ? absint( wp_unslash( $_POST['domain_id'] ) ) : 0 );
				$message = __( 'Domain deleted.', 'ewo-rss-engine' );
				break;

			case 'add_subdomain':
				$domain_id = isset( $_POST['domain_id'] ) ? absint( wp_unslash( $_POST['domain_id'] ) ) : 0;
				$name      = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
				$id        = EWO_RSS_Taxonomy::add_subdomain( $domain_id, $name );
				$message   = $id ? __( 'Subdomain added.', 'ewo-rss-engine' ) : __( 'Could not add subdomain.', 'ewo-rss-engine' );
				break;

			case 'delete_subdomain':
				EWO_RSS_Taxonomy::delete_subdomain( isset( $_POST['subdomain_id'] ) ? absint( wp_unslash( $_POST['subdomain_id'] ) ) : 0 );
				$message = __( 'Subdomain deleted.', 'ewo-rss-engine' );
				break;

			case 'add_keyword':
				$subdomain_id = isset( $_POST['subdomain_id'] ) ? absint( wp_unslash( $_POST['subdomain_id'] ) ) : 0;
				$keyword      = isset( $_POST['keyword'] ) ? sanitize_text_field( wp_unslash( $_POST['keyword'] ) ) : '';
				$active       = ! empty( $_POST['active'] );
				$id           = EWO_RSS_Taxonomy::add_keyword( $subdomain_id, $keyword, $active );
				if ( $id ) {
					EWO_RSS_Keyword_Feeds::sync_keyword( $id );
					$message = __( 'Keyword added and feed generated.', 'ewo-rss-engine' );
				} else {
					$message = __( 'Could not add keyword.', 'ewo-rss-engine' );
				}
				break;

			case 'edit_keyword':
				$keyword_id = isset( $_POST['keyword_id'] ) ? absint( wp_unslash( $_POST['keyword_id'] ) ) : 0;
				$keyword    = isset( $_POST['keyword'] ) ? sanitize_text_field( wp_unslash( $_POST['keyword'] ) ) : '';
				if ( $keyword_id > 0 && '' !== $keyword ) {
					EWO_RSS_Taxonomy::update_keyword( $keyword_id, array( 'keyword' => $keyword ) );
					EWO_RSS_Keyword_Feeds::sync_keyword( $keyword_id );
					$message = __( 'Keyword updated.', 'ewo-rss-engine' );
				} else {
					$message = __( 'Could not update keyword.', 'ewo-rss-engine' );
				}
				break;

			case 'toggle_keyword':
				$keyword_id = isset( $_POST['keyword_id'] ) ? absint( wp_unslash( $_POST['keyword_id'] ) ) : 0;
				$kw         = EWO_RSS_Taxonomy::get_keyword( $keyword_id );
				if ( $kw ) {
					EWO_RSS_Taxonomy::update_keyword( $keyword_id, array( 'active' => empty( $kw->active ) ? 1 : 0 ) );
					EWO_RSS_Keyword_Feeds::sync_keyword( $keyword_id );
					$message = __( 'Keyword updated.', 'ewo-rss-engine' );
				}
				break;

			case 'delete_keyword':
				EWO_RSS_Taxonomy::delete_keyword( isset( $_POST['keyword_id'] ) ? absint( wp_unslash( $_POST['keyword_id'] ) ) : 0 );
				$message = __( 'Keyword deleted.', 'ewo-rss-engine' );
				break;

			case 'fetch_keyword':
				$totals  = EWO_RSS_Keyword_Feeds::fetch_keyword( isset( $_POST['keyword_id'] ) ? absint( wp_unslash( $_POST['keyword_id'] ) ) : 0 );
				$message = $this->fetch_message( $totals );
				break;

			case 'fetch_subdomain':
				$totals  = EWO_RSS_Keyword_Feeds::fetch_subdomain( isset( $_POST['subdomain_id'] ) ? absint( wp_unslash( $_POST['subdomain_id'] ) ) : 0 );
				$message = $this->fetch_message( $totals );
				break;

			case 'fetch_domain':
				$domain_id = isset( $_POST['domain_id'] ) ? absint( wp_unslash( $_POST['domain_id'] ) ) : 0;
				$totals    = $this->fetch_domain( $domain_id );
				$message   = $this->fetch_message( $totals );
				break;

			case 'fetch_all':
				$totals  = EWO_RSS_Keyword_Feeds::fetch_all_active();
				$message = $this->fetch_message( $totals );
				break;
		}

		if ( '' !== $message ) {
			set_transient( self::NOTICE_KEY . get_current_user_id(), $message, MINUTE_IN_SECONDS );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=' . self::MENU_SLUG ) );
		exit;
	}

	/**
	 * Fetch all active keyword feeds under a domain.
	 *
	 * @param int $domain_id Domain ID.
	 * @return array{found:int,created:int,skipped:int,errors:int}
	 */
	protected function fetch_domain( $domain_id ) {
		$totals = array(
			'found'   => 0,
			'created' => 0,
			'skipped' => 0,
			'errors'  => 0,
		);
		foreach ( EWO_RSS_Taxonomy::get_subdomains( (int) $domain_id ) as $sub ) {
			$sub_totals = EWO_RSS_Keyword_Feeds::fetch_subdomain( (int) $sub->id );
			foreach ( $totals as $key => $val ) {
				$totals[ $key ] = $val + (int) ( isset( $sub_totals[ $key ] ) ? $sub_totals[ $key ] : 0 );
			}
		}
		return $totals;
	}

	/**
	 * Summarize fetch totals into a notice string.
	 *
	 * @param array<string,int> $totals Totals.
	 * @return string
	 */
	protected function fetch_message( array $totals ) {
		return sprintf(
			/* translators: 1: found, 2: created, 3: skipped, 4: errors. */
			__( 'Fetch finished — %1$d found, %2$d new Sources, %3$d skipped, %4$d errors.', 'ewo-rss-engine' ),
			(int) $totals['found'],
			(int) $totals['created'],
			(int) $totals['skipped'],
			(int) $totals['errors']
		);
	}

	/**
	 * Render and consume a stored notice.
	 */
	public function render_notice() {
		$key     = self::NOTICE_KEY . get_current_user_id();
		$message = get_transient( $key );
		if ( false === $message ) {
			return;
		}
		delete_transient( $key );
		printf( '<div class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html( $message ) );
	}
}
