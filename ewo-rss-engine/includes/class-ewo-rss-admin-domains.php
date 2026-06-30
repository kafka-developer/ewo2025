<?php
/**
 * Strategic Domains admin page — 3-column master-detail UI.
 *
 * Column 1: Strategic Domains
 * Column 2: Subdomains (loads on domain select)
 * Column 3: Keywords   (loads on subdomain select)
 *
 * All write operations use wp_ajax_* handlers that return JSON.
 * Column 2 and 3 are populated via client-side AJAX after selection.
 *
 * @package EWO_RSS_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Strategic Domains 3-column admin UI.
 */
class EWO_RSS_Admin_Domains {

	const MENU_SLUG    = 'ewo-rss-domains';
	const PARENT       = 'ewo-rss-engine';
	const CAP          = 'manage_options';
	const NONCE_ACTION = 'ewo_domains';

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
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		$ajax_actions = array(
			'get_subdomains',
			'get_keywords',
			'add_domain',
			'update_domain',
			'delete_domain',
			'add_subdomain',
			'update_subdomain',
			'delete_subdomain',
			'add_keyword',
			'update_keyword',
			'delete_keyword',
			'generate_feeds',
		);

		foreach ( $ajax_actions as $action ) {
			add_action( 'wp_ajax_ewo_domains_' . $action, array( $this, 'ajax_' . $action ) );
		}
	}

	/**
	 * Register the submenu page.
	 */
	public function register_menu() {
		$this->hook = (string) add_submenu_page(
			self::PARENT,
			__( 'Strategic Domains', 'ewo-rss-engine' ),
			__( 'Strategic Domains', 'ewo-rss-engine' ),
			self::CAP,
			self::MENU_SLUG,
			array( $this, 'render' )
		);
	}

	/**
	 * Enqueue dedicated assets only on this page.
	 *
	 * @param string $hook Current page hook.
	 */
	public function enqueue_assets( $hook ) {
		if ( $hook !== $this->hook ) {
			return;
		}

		wp_enqueue_style(
			'ewo-domains',
			EWO_RSS_ENGINE_URL . 'assets/css/domains.css',
			array(),
			EWO_RSS_ENGINE_VERSION
		);

		wp_enqueue_script(
			'ewo-domains',
			EWO_RSS_ENGINE_URL . 'assets/js/domains.js',
			array(),
			EWO_RSS_ENGINE_VERSION,
			true
		);

		wp_localize_script(
			'ewo-domains',
			'ewoDomains',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( self::NONCE_ACTION ),
				'i18n'    => array(
					'selectDomain'    => __( 'Select a domain to see its subdomains.', 'ewo-rss-engine' ),
					'selectSubdomain' => __( 'Select a subdomain to see its keywords.', 'ewo-rss-engine' ),
					'noSubdomains'    => __( 'No subdomains yet. Add one above.', 'ewo-rss-engine' ),
					'noKeywords'      => __( 'No keywords yet. Add one above.', 'ewo-rss-engine' ),
					'confirmDelete'   => __( 'Are you sure you want to delete this item and everything under it?', 'ewo-rss-engine' ),
					'generating'      => __( 'Generating feeds…', 'ewo-rss-engine' ),
					'saved'           => __( 'Saved.', 'ewo-rss-engine' ),
					'error'           => __( 'Something went wrong. Please try again.', 'ewo-rss-engine' ),
					'subdomains'      => __( 'subdomains', 'ewo-rss-engine' ),
					'keywords'        => __( 'keywords', 'ewo-rss-engine' ),
					'active'          => __( 'Active', 'ewo-rss-engine' ),
					'inactive'        => __( 'Inactive', 'ewo-rss-engine' ),
					'edit'            => __( 'Edit', 'ewo-rss-engine' ),
					'delete'          => __( 'Delete', 'ewo-rss-engine' ),
					'save'            => __( 'Save', 'ewo-rss-engine' ),
					'cancel'          => __( 'Cancel', 'ewo-rss-engine' ),
				),
			)
		);
	}

	/* ---------------------------------------------------------------------
	 * Security helper
	 * ------------------------------------------------------------------- */

	/**
	 * Verify nonce + capability, send JSON error and exit on failure.
	 */
	protected function verify_nonce() {
		if ( ! check_ajax_referer( self::NONCE_ACTION, 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'ewo-rss-engine' ) ), 403 );
		}
		if ( ! current_user_can( self::CAP ) ) {
			wp_send_json_error( array( 'message' => __( 'Not allowed.', 'ewo-rss-engine' ) ), 403 );
		}
	}

	/* ---------------------------------------------------------------------
	 * Page render — server-side shell only; columns 2 & 3 filled via AJAX
	 * ------------------------------------------------------------------- */

	/**
	 * Render the page.
	 */
	public function render() {
		if ( ! current_user_can( self::CAP ) ) {
			return;
		}

		$domains = EWO_RSS_Taxonomy::get_domains();
		$total   = count( $domains );
		?>
		<div class="wrap ewo-domains-wrap">

			<div class="ewo-domains-page-header">
				<h1 class="ewo-domains-heading"><?php esc_html_e( 'Strategic Domains', 'ewo-rss-engine' ); ?></h1>
				<p class="ewo-domains-subtitle">
					<?php esc_html_e( 'Create and manage strategic domains. Each domain can have multiple subdomains.', 'ewo-rss-engine' ); ?>
				</p>
			</div>

			<div id="ewo-domains-notice" class="ewo-domains-flash" style="display:none;" role="status" aria-live="polite"></div>

			<div class="ewo-domains-columns">

				<!-- ===================== COLUMN 1: DOMAINS ===================== -->
				<div class="ewo-col-panel" id="ewo-col-domains">

					<div class="ewo-col-header">
						<div class="ewo-col-title-group">
							<span class="ewo-col-number">1</span>
							<span class="ewo-col-title"><?php esc_html_e( 'Strategic Domains', 'ewo-rss-engine' ); ?></span>
						</div>
						<span class="ewo-col-badge" id="ewo-domains-count"><?php echo esc_html( (string) $total ); ?></span>
					</div>

					<div class="ewo-col-controls">
						<input type="search" id="ewo-search-domains" class="ewo-col-search"
							placeholder="<?php esc_attr_e( 'Search domains…', 'ewo-rss-engine' ); ?>"
							autocomplete="off" />
						<button type="button" class="button button-primary ewo-btn-add" id="ewo-btn-add-domain">
							<span aria-hidden="true">+</span> <?php esc_html_e( 'Add Domain', 'ewo-rss-engine' ); ?>
						</button>
					</div>

					<div class="ewo-col-add-form" id="ewo-add-domain-form" style="display:none;" aria-hidden="true">
						<input type="text" class="ewo-add-input" id="ewo-new-domain-name"
							placeholder="<?php esc_attr_e( 'Domain name…', 'ewo-rss-engine' ); ?>" maxlength="191" />
						<textarea class="ewo-add-textarea" id="ewo-new-domain-desc"
							placeholder="<?php esc_attr_e( 'Description (optional)…', 'ewo-rss-engine' ); ?>"
							rows="2" maxlength="500"></textarea>
						<div class="ewo-add-form-actions">
							<button type="button" class="button button-primary button-small" id="ewo-save-domain">
								<?php esc_html_e( 'Save', 'ewo-rss-engine' ); ?>
							</button>
							<button type="button" class="button button-small" id="ewo-cancel-domain">
								<?php esc_html_e( 'Cancel', 'ewo-rss-engine' ); ?>
							</button>
						</div>
					</div>

					<ul class="ewo-col-list" id="ewo-domains-list" role="listbox" aria-label="<?php esc_attr_e( 'Strategic Domains', 'ewo-rss-engine' ); ?>">
						<?php if ( empty( $domains ) ) : ?>
							<li class="ewo-col-empty" id="ewo-domains-empty">
								<?php esc_html_e( 'No strategic domains yet. Add one above.', 'ewo-rss-engine' ); ?>
							</li>
						<?php else : ?>
							<?php foreach ( $domains as $d ) : ?>
								<?php
								$sub_count = count( EWO_RSS_Taxonomy::get_subdomains( (int) $d->id ) );
								?>
								<li class="ewo-col-row"
									data-id="<?php echo esc_attr( (string) $d->id ); ?>"
									data-name="<?php echo esc_attr( $d->name ); ?>"
									data-description="<?php echo esc_attr( isset( $d->description ) ? (string) $d->description : '' ); ?>"
									role="option" tabindex="0">
									<div class="ewo-row-main">
										<span class="ewo-row-name"><?php echo esc_html( $d->name ); ?></span>
										<span class="ewo-row-meta">
											<?php echo esc_html( (string) $sub_count ); ?>
											<?php esc_html_e( 'subdomains', 'ewo-rss-engine' ); ?>
										</span>
									</div>
									<div class="ewo-row-menu-wrap">
										<button type="button" class="ewo-row-menu-btn"
											aria-haspopup="true" aria-expanded="false"
											aria-label="<?php esc_attr_e( 'Row actions', 'ewo-rss-engine' ); ?>">
											<span aria-hidden="true">⋯</span>
										</button>
										<div class="ewo-row-menu" role="menu">
											<button type="button" class="ewo-menu-item ewo-menu-edit"
												data-id="<?php echo esc_attr( (string) $d->id ); ?>"
												data-name="<?php echo esc_attr( $d->name ); ?>"
												data-type="domain" role="menuitem">
												<?php esc_html_e( 'Edit', 'ewo-rss-engine' ); ?>
											</button>
											<button type="button" class="ewo-menu-item ewo-menu-delete ewo-menu-danger"
												data-id="<?php echo esc_attr( (string) $d->id ); ?>"
												data-type="domain" role="menuitem">
												<?php esc_html_e( 'Delete', 'ewo-rss-engine' ); ?>
											</button>
										</div>
									</div>
								</li>
							<?php endforeach; ?>
						<?php endif; ?>
					</ul>

				</div><!-- #ewo-col-domains -->

				<!-- ===================== COLUMN 2: SUBDOMAINS ===================== -->
				<div class="ewo-col-panel ewo-col-panel--inactive" id="ewo-col-subdomains">

					<div class="ewo-col-header">
						<div class="ewo-col-title-group">
							<span class="ewo-col-number">2</span>
							<span class="ewo-col-title"><?php esc_html_e( 'Subdomains', 'ewo-rss-engine' ); ?></span>
						</div>
						<span class="ewo-col-badge" id="ewo-subdomains-count">0</span>
					</div>

					<div class="ewo-col-breadcrumb" id="ewo-sub-breadcrumb" style="display:none;">
						<span class="ewo-bc-item" id="ewo-bc-domain-name"></span>
						<span class="ewo-bc-sep" aria-hidden="true">›</span>
						<span class="ewo-bc-item ewo-bc-current"><?php esc_html_e( 'Subdomains', 'ewo-rss-engine' ); ?></span>
					</div>

					<div class="ewo-col-controls" id="ewo-sub-controls" style="display:none;">
						<input type="search" id="ewo-search-subdomains" class="ewo-col-search"
							placeholder="<?php esc_attr_e( 'Search subdomains…', 'ewo-rss-engine' ); ?>"
							autocomplete="off" />
						<button type="button" class="button button-primary ewo-btn-add" id="ewo-btn-add-subdomain">
							<span aria-hidden="true">+</span> <?php esc_html_e( 'Add Subdomain', 'ewo-rss-engine' ); ?>
						</button>
					</div>

					<div class="ewo-col-add-form" id="ewo-add-subdomain-form" style="display:none;" aria-hidden="true">
						<input type="text" class="ewo-add-input" id="ewo-new-subdomain-name"
							placeholder="<?php esc_attr_e( 'Subdomain name…', 'ewo-rss-engine' ); ?>" maxlength="191" />
						<div class="ewo-add-form-actions">
							<button type="button" class="button button-primary button-small" id="ewo-save-subdomain">
								<?php esc_html_e( 'Save', 'ewo-rss-engine' ); ?>
							</button>
							<button type="button" class="button button-small" id="ewo-cancel-subdomain">
								<?php esc_html_e( 'Cancel', 'ewo-rss-engine' ); ?>
							</button>
						</div>
					</div>

					<ul class="ewo-col-list" id="ewo-subdomains-list" role="listbox"
						aria-label="<?php esc_attr_e( 'Subdomains', 'ewo-rss-engine' ); ?>">
						<li class="ewo-col-empty ewo-col-placeholder">
							<?php esc_html_e( 'Select a domain to see its subdomains.', 'ewo-rss-engine' ); ?>
						</li>
					</ul>

				</div><!-- #ewo-col-subdomains -->

				<!-- ===================== COLUMN 3: KEYWORDS ===================== -->
				<div class="ewo-col-panel ewo-col-panel--inactive" id="ewo-col-keywords">

					<div class="ewo-col-header">
						<div class="ewo-col-title-group">
							<span class="ewo-col-number">3</span>
							<span class="ewo-col-title"><?php esc_html_e( 'Keywords', 'ewo-rss-engine' ); ?></span>
						</div>
						<span class="ewo-col-badge" id="ewo-keywords-count">0</span>
					</div>

					<div class="ewo-col-breadcrumb" id="ewo-kw-breadcrumb" style="display:none;">
						<span class="ewo-bc-item" id="ewo-bc-kw-domain"></span>
						<span class="ewo-bc-sep" aria-hidden="true">›</span>
						<span class="ewo-bc-item" id="ewo-bc-kw-subdomain"></span>
						<span class="ewo-bc-sep" aria-hidden="true">›</span>
						<span class="ewo-bc-item ewo-bc-current"><?php esc_html_e( 'Keywords', 'ewo-rss-engine' ); ?></span>
					</div>

					<div class="ewo-col-controls ewo-kw-col-controls" id="ewo-kw-controls" style="display:none;">
						<input type="search" id="ewo-search-keywords" class="ewo-col-search"
							placeholder="<?php esc_attr_e( 'Search keywords…', 'ewo-rss-engine' ); ?>"
							autocomplete="off" />
						<div class="ewo-kw-btn-row">
							<button type="button" class="button button-primary ewo-btn-add" id="ewo-btn-add-keyword">
								<span aria-hidden="true">+</span> <?php esc_html_e( 'Add Keyword', 'ewo-rss-engine' ); ?>
							</button>
							<button type="button" class="button ewo-btn-generate" id="ewo-btn-generate">
								⚡ <?php esc_html_e( 'Generate Feeds', 'ewo-rss-engine' ); ?>
							</button>
						</div>
					</div>

					<div class="ewo-col-add-form" id="ewo-add-keyword-form" style="display:none;" aria-hidden="true">
						<input type="text" class="ewo-add-input" id="ewo-new-keyword-name"
							placeholder="<?php esc_attr_e( 'Keyword…', 'ewo-rss-engine' ); ?>" maxlength="191" />
						<label class="ewo-kw-active-toggle">
							<input type="checkbox" id="ewo-new-keyword-active" checked />
							<?php esc_html_e( 'Active', 'ewo-rss-engine' ); ?>
						</label>
						<div class="ewo-add-form-actions">
							<button type="button" class="button button-primary button-small" id="ewo-save-keyword">
								<?php esc_html_e( 'Save', 'ewo-rss-engine' ); ?>
							</button>
							<button type="button" class="button button-small" id="ewo-cancel-keyword">
								<?php esc_html_e( 'Cancel', 'ewo-rss-engine' ); ?>
							</button>
						</div>
					</div>

					<ul class="ewo-col-list" id="ewo-keywords-list" role="listbox"
						aria-label="<?php esc_attr_e( 'Keywords', 'ewo-rss-engine' ); ?>">
						<li class="ewo-col-empty ewo-col-placeholder">
							<?php esc_html_e( 'Select a subdomain to see its keywords.', 'ewo-rss-engine' ); ?>
						</li>
					</ul>

				</div><!-- #ewo-col-keywords -->

			</div><!-- .ewo-domains-columns -->

			<div id="ewo-generate-results" class="ewo-generate-results" style="display:none;" aria-live="polite"></div>

			<p class="ewo-domains-footer-note">
				<span class="dashicons dashicons-info" aria-hidden="true"></span>
				<?php esc_html_e( 'Feeds are generated from Keywords. Make sure keywords are relevant and active.', 'ewo-rss-engine' ); ?>
			</p>

		</div><!-- .ewo-domains-wrap -->
		<?php
	}

	/* ---------------------------------------------------------------------
	 * AJAX: load subdomains for a domain
	 * ------------------------------------------------------------------- */

	/**
	 * Return subdomains for a domain as JSON.
	 */
	public function ajax_get_subdomains() {
		$this->verify_nonce();
		$domain_id = isset( $_POST['domain_id'] ) ? absint( wp_unslash( $_POST['domain_id'] ) ) : 0;
		if ( $domain_id <= 0 ) {
			wp_send_json_error( array( 'message' => 'Invalid domain ID.' ), 400 );
		}

		$domain     = EWO_RSS_Taxonomy::get_domain( $domain_id );
		$subdomains = EWO_RSS_Taxonomy::get_subdomains( $domain_id );

		$rows = array();
		foreach ( $subdomains as $s ) {
			$rows[] = array(
				'id'            => (int) $s->id,
				'name'          => $s->name,
				'keyword_count' => count( EWO_RSS_Taxonomy::get_keywords( (int) $s->id ) ),
			);
		}

		wp_send_json_success(
			array(
				'domain_id'   => $domain_id,
				'domain_name' => $domain ? $domain->name : '',
				'subdomains'  => $rows,
			)
		);
	}

	/* ---------------------------------------------------------------------
	 * AJAX: load keywords for a subdomain
	 * ------------------------------------------------------------------- */

	/**
	 * Return keywords for a subdomain as JSON.
	 */
	public function ajax_get_keywords() {
		$this->verify_nonce();
		$subdomain_id = isset( $_POST['subdomain_id'] ) ? absint( wp_unslash( $_POST['subdomain_id'] ) ) : 0;
		if ( $subdomain_id <= 0 ) {
			wp_send_json_error( array( 'message' => 'Invalid subdomain ID.' ), 400 );
		}

		$subdomain = EWO_RSS_Taxonomy::get_subdomain( $subdomain_id );
		$domain    = $subdomain ? EWO_RSS_Taxonomy::get_domain( (int) $subdomain->domain_id ) : null;
		$keywords  = EWO_RSS_Taxonomy::get_keywords( $subdomain_id );

		$rows = array();
		foreach ( $keywords as $k ) {
			$rows[] = array(
				'id'      => (int) $k->id,
				'keyword' => $k->keyword,
				'active'  => (bool) $k->active,
				'feed_id' => (int) $k->feed_id,
			);
		}

		wp_send_json_success(
			array(
				'subdomain_id'   => $subdomain_id,
				'subdomain_name' => $subdomain ? $subdomain->name : '',
				'domain_name'    => $domain ? $domain->name : '',
				'keywords'       => $rows,
			)
		);
	}

	/* ---------------------------------------------------------------------
	 * AJAX: Domain CRUD
	 * ------------------------------------------------------------------- */

	/** Add a domain. */
	public function ajax_add_domain() {
		$this->verify_nonce();
		$name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$desc = isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '';
		$id   = EWO_RSS_Taxonomy::add_domain( $name, $desc );
		if ( ! $id ) {
			wp_send_json_error( array( 'message' => __( 'Could not add domain.', 'ewo-rss-engine' ) ) );
		}
		$domain = EWO_RSS_Taxonomy::get_domain( $id );
		wp_send_json_success(
			array(
				'id'              => $id,
				'name'            => $domain ? $domain->name : $name,
				'description'     => $domain && isset( $domain->description ) ? $domain->description : $desc,
				'subdomain_count' => 0,
				'total'           => count( EWO_RSS_Taxonomy::get_domains() ),
			)
		);
	}

	/** Update a domain name and/or description. */
	public function ajax_update_domain() {
		$this->verify_nonce();
		$id   = isset( $_POST['id'] ) ? absint( wp_unslash( $_POST['id'] ) ) : 0;
		$name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		if ( $id <= 0 || '' === $name ) {
			wp_send_json_error( array( 'message' => 'Invalid input.' ), 400 );
		}

		$data    = array(
			'name'       => $name,
			'updated_at' => current_time( 'mysql', true ),
		);
		$formats = array( '%s', '%s' );

		if ( isset( $_POST['description'] ) ) {
			$data['description'] = sanitize_textarea_field( wp_unslash( $_POST['description'] ) );
			$formats[]           = '%s';
		}

		global $wpdb;
		$result = $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			EWO_RSS_Taxonomy::domains_table(),
			$data,
			array( 'id' => $id ),
			$formats,
			array( '%d' )
		);

		if ( false === $result ) {
			wp_send_json_error( array( 'message' => __( 'Could not update domain.', 'ewo-rss-engine' ) ) );
		}
		wp_send_json_success(
			array(
				'id'          => $id,
				'name'        => $name,
				'description' => isset( $data['description'] ) ? $data['description'] : '',
			)
		);
	}

	/** Delete a domain (cascades to subdomains + keywords). */
	public function ajax_delete_domain() {
		$this->verify_nonce();
		$id = isset( $_POST['id'] ) ? absint( wp_unslash( $_POST['id'] ) ) : 0;
		EWO_RSS_Taxonomy::delete_domain( $id );
		wp_send_json_success(
			array(
				'id'    => $id,
				'total' => count( EWO_RSS_Taxonomy::get_domains() ),
			)
		);
	}

	/* ---------------------------------------------------------------------
	 * AJAX: Subdomain CRUD
	 * ------------------------------------------------------------------- */

	/** Add a subdomain under a domain. */
	public function ajax_add_subdomain() {
		$this->verify_nonce();
		$domain_id = isset( $_POST['domain_id'] ) ? absint( wp_unslash( $_POST['domain_id'] ) ) : 0;
		$name      = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$id        = EWO_RSS_Taxonomy::add_subdomain( $domain_id, $name );
		if ( ! $id ) {
			wp_send_json_error( array( 'message' => __( 'Could not add subdomain.', 'ewo-rss-engine' ) ) );
		}
		$sub = EWO_RSS_Taxonomy::get_subdomain( $id );
		wp_send_json_success(
			array(
				'id'            => $id,
				'name'          => $sub ? $sub->name : $name,
				'keyword_count' => 0,
				'total'         => count( EWO_RSS_Taxonomy::get_subdomains( $domain_id ) ),
			)
		);
	}

	/** Update a subdomain name. */
	public function ajax_update_subdomain() {
		$this->verify_nonce();
		$id   = isset( $_POST['id'] ) ? absint( wp_unslash( $_POST['id'] ) ) : 0;
		$name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		if ( $id <= 0 || '' === $name ) {
			wp_send_json_error( array( 'message' => 'Invalid input.' ), 400 );
		}

		global $wpdb;
		$result = $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			EWO_RSS_Taxonomy::subdomains_table(),
			array(
				'name'       => $name,
				'updated_at' => current_time( 'mysql', true ),
			),
			array( 'id' => $id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		if ( false === $result ) {
			wp_send_json_error( array( 'message' => __( 'Could not update subdomain.', 'ewo-rss-engine' ) ) );
		}
		wp_send_json_success( array( 'id' => $id, 'name' => $name ) );
	}

	/** Delete a subdomain (cascades to keywords). */
	public function ajax_delete_subdomain() {
		$this->verify_nonce();
		$id  = isset( $_POST['id'] ) ? absint( wp_unslash( $_POST['id'] ) ) : 0;
		$sub = EWO_RSS_Taxonomy::get_subdomain( $id );
		$dom = $sub ? (int) $sub->domain_id : 0;
		EWO_RSS_Taxonomy::delete_subdomain( $id );
		wp_send_json_success(
			array(
				'id'    => $id,
				'total' => $dom ? count( EWO_RSS_Taxonomy::get_subdomains( $dom ) ) : 0,
			)
		);
	}

	/* ---------------------------------------------------------------------
	 * AJAX: Keyword CRUD
	 * ------------------------------------------------------------------- */

	/** Add a keyword and sync its feed. */
	public function ajax_add_keyword() {
		$this->verify_nonce();
		$subdomain_id = isset( $_POST['subdomain_id'] ) ? absint( wp_unslash( $_POST['subdomain_id'] ) ) : 0;
		$keyword      = isset( $_POST['keyword'] ) ? sanitize_text_field( wp_unslash( $_POST['keyword'] ) ) : '';
		$active       = ! empty( $_POST['active'] );
		$id           = EWO_RSS_Taxonomy::add_keyword( $subdomain_id, $keyword, $active );
		if ( ! $id ) {
			wp_send_json_error( array( 'message' => __( 'Could not add keyword.', 'ewo-rss-engine' ) ) );
		}
		EWO_RSS_Keyword_Feeds::sync_keyword( $id );
		$kw = EWO_RSS_Taxonomy::get_keyword( $id );
		wp_send_json_success(
			array(
				'id'      => $id,
				'keyword' => $kw ? $kw->keyword : $keyword,
				'active'  => $active,
				'feed_id' => $kw ? (int) $kw->feed_id : 0,
				'total'   => count( EWO_RSS_Taxonomy::get_keywords( $subdomain_id ) ),
			)
		);
	}

	/** Update a keyword's text and/or active state. */
	public function ajax_update_keyword() {
		$this->verify_nonce();
		$id      = isset( $_POST['id'] ) ? absint( wp_unslash( $_POST['id'] ) ) : 0;
		$keyword = isset( $_POST['keyword'] ) ? sanitize_text_field( wp_unslash( $_POST['keyword'] ) ) : '';
		// active is sent as '1' or '0'.
		$active_raw = isset( $_POST['active'] ) ? (string) wp_unslash( $_POST['active'] ) : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( $id <= 0 ) {
			wp_send_json_error( array( 'message' => 'Invalid ID.' ), 400 );
		}

		$fields = array();
		if ( '' !== $keyword ) {
			$fields['keyword'] = $keyword;
		}
		if ( null !== $active_raw ) {
			$fields['active'] = ( '1' === $active_raw ) ? 1 : 0;
		}

		if ( ! empty( $fields ) ) {
			EWO_RSS_Taxonomy::update_keyword( $id, $fields );
			EWO_RSS_Keyword_Feeds::sync_keyword( $id );
		}

		$kw = EWO_RSS_Taxonomy::get_keyword( $id );
		wp_send_json_success(
			array(
				'id'      => $id,
				'keyword' => $kw ? $kw->keyword : $keyword,
				'active'  => $kw ? (bool) $kw->active : (bool) $active_raw,
			)
		);
	}

	/** Delete a keyword and its feed. */
	public function ajax_delete_keyword() {
		$this->verify_nonce();
		$id  = isset( $_POST['id'] ) ? absint( wp_unslash( $_POST['id'] ) ) : 0;
		$kw  = EWO_RSS_Taxonomy::get_keyword( $id );
		$sub = $kw ? (int) $kw->subdomain_id : 0;
		EWO_RSS_Taxonomy::delete_keyword( $id );
		wp_send_json_success(
			array(
				'id'    => $id,
				'total' => $sub ? count( EWO_RSS_Taxonomy::get_keywords( $sub ) ) : 0,
			)
		);
	}

	/* ---------------------------------------------------------------------
	 * AJAX: Feed generation
	 * ------------------------------------------------------------------- */

	/**
	 * Generate/fetch feeds for selected keyword IDs (or all in a subdomain).
	 *
	 * Returns per-keyword results for newly created feeds so the UI can display
	 * only the net-new URLs (existing feeds are excluded from the results list).
	 */
	public function ajax_generate_feeds() {
		$this->verify_nonce();

		// keyword_ids may arrive as array (from JS FormData array) or comma-string.
		$ids_raw = isset( $_POST['keyword_ids'] ) ? wp_unslash( $_POST['keyword_ids'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( ! is_array( $ids_raw ) ) {
			$ids_raw = explode( ',', (string) $ids_raw );
		}
		$kw_ids = array_values( array_filter( array_map( 'absint', (array) $ids_raw ) ) );

		$subdomain_id = isset( $_POST['subdomain_id'] ) ? absint( wp_unslash( $_POST['subdomain_id'] ) ) : 0;

		// If no explicit keyword IDs given, derive from subdomain.
		if ( empty( $kw_ids ) && $subdomain_id > 0 ) {
			foreach ( EWO_RSS_Taxonomy::get_keywords( $subdomain_id ) as $k ) {
				$kw_ids[] = (int) $k->id;
			}
		}

		if ( empty( $kw_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No keywords selected.', 'ewo-rss-engine' ) ) );
		}

		$new_feeds = array();

		foreach ( $kw_ids as $kw_id ) {
			$kw_before  = EWO_RSS_Taxonomy::get_keyword( $kw_id );
			$had_feed   = $kw_before && (int) $kw_before->feed_id > 0;

			EWO_RSS_Keyword_Feeds::sync_keyword( $kw_id );

			if ( ! $had_feed ) {
				$kw_after = EWO_RSS_Taxonomy::get_keyword( $kw_id );
				$feed_id  = $kw_after ? (int) $kw_after->feed_id : 0;
				if ( $feed_id > 0 ) {
					$new_feeds[] = array(
						'keyword'  => $kw_after ? $kw_after->keyword : '',
						'feed_url' => EWO_RSS_Feed::url( $feed_id ),
						'status'   => 'new',
					);
				}
			}
		}

		$count   = count( $new_feeds );
		$message = $count > 0
			? sprintf(
				/* translators: %d number of new feeds */
				_n( '%d new feed URL generated.', '%d new feed URLs generated.', $count, 'ewo-rss-engine' ),
				$count
			)
			: __( 'All feeds already existed. No new feed URLs were generated.', 'ewo-rss-engine' );

		wp_send_json_success(
			array(
				'message'   => $message,
				'new_feeds' => $new_feeds,
			)
		);
	}
}
