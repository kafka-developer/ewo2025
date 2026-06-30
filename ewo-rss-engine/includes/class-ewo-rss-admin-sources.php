<?php
/**
 * Admin screen + front-end shortcode for captured Sources.
 *
 * Lists Sources from {@see EWO_RSS_Source_Store} with domain/subdomain/keyword/
 * status filters and inline status changes, and registers the `[ewo_sources]`
 * shortcode that groups the latest Sources by Strategic Domain → Subdomain.
 *
 * @package EWO_RSS_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sources admin + shortcode.
 */
class EWO_RSS_Admin_Sources {
	const MENU_SLUG     = 'ewo-rss-sources';
	const PARENT        = 'ewo-rss-engine';
	const CAP           = 'manage_options';
	const STATUS_ACTION = 'ewo_rss_source_status';
	const NONCE         = 'ewo_rss_source_status_nonce';
	const NOTICE_KEY    = 'ewo_rss_src_notice_';
	const PER_PAGE      = 10;
	const SHORTCODE     = 'ewo_sources';

	/** Columns allowed as orderby targets. */
	const SORTABLE = array( 'published_at', 'fetched_at', 'source_domain' );

	/**
	 * Register hooks.
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_post_' . self::STATUS_ACTION, array( $this, 'handle_status' ) );
		add_action( 'admin_notices', array( $this, 'render_notice' ) );
		add_shortcode( self::SHORTCODE, array( $this, 'shortcode' ) );
	}

	/**
	 * Add the submenu.
	 */
	public function register_menu() {
		add_submenu_page(
			self::PARENT,
			__( 'Sources', 'ewo-rss-engine' ),
			__( 'Sources', 'ewo-rss-engine' ),
			self::CAP,
			self::MENU_SLUG,
			array( $this, 'render' )
		);
	}

	/* ---------------------------------------------------------------------
	 * Admin list
	 * ------------------------------------------------------------------- */

	/**
	 * Render the Sources list or detail view.
	 */
	public function render() {
		if ( ! current_user_can( self::CAP ) ) {
			return;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$view_source = isset( $_GET['view_source'] ) ? absint( wp_unslash( $_GET['view_source'] ) ) : 0;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( $view_source > 0 ) {
			$this->render_detail( $view_source );
			return;
		}

		$this->render_list();
	}

	/**
	 * Render the paginated, sortable Sources list.
	 */
	protected function render_list() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$domain_id    = isset( $_GET['domain_id'] ) ? absint( wp_unslash( $_GET['domain_id'] ) ) : 0;
		$subdomain_id = isset( $_GET['subdomain_id'] ) ? absint( wp_unslash( $_GET['subdomain_id'] ) ) : 0;
		$keyword_id   = isset( $_GET['keyword_id'] ) ? absint( wp_unslash( $_GET['keyword_id'] ) ) : 0;
		$status       = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
		$paged        = isset( $_GET['paged'] ) ? max( 1, absint( wp_unslash( $_GET['paged'] ) ) ) : 1;
		$orderby      = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : '';
		$order        = isset( $_GET['order'] ) && 'asc' === strtolower( wp_unslash( $_GET['order'] ) ) ? 'ASC' : 'DESC'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( ! in_array( $status, EWO_RSS_Source_Store::statuses(), true ) ) {
			$status = '';
		}
		if ( ! in_array( $orderby, self::SORTABLE, true ) ) {
			$orderby = 'fetched_at';
		}

		$args = array(
			'domain_id'    => $domain_id,
			'subdomain_id' => $subdomain_id,
			'keyword_id'   => $keyword_id,
			'status'       => $status,
			'orderby'      => $orderby,
			'order'        => $order,
			'limit'        => self::PER_PAGE,
			'offset'       => ( $paged - 1 ) * self::PER_PAGE,
		);

		$rows      = EWO_RSS_Source_Store::query( $args );
		$total     = EWO_RSS_Source_Store::count( $args );
		$pages     = (int) ceil( $total / self::PER_PAGE );
		$first_num = ( ( $paged - 1 ) * self::PER_PAGE ) + 1;
		$last_num  = min( $paged * self::PER_PAGE, $total );

		// Base URL preserving all current filters + sort.
		$base_filter_args = array_filter( array(
			'domain_id'    => $domain_id ?: null,
			'subdomain_id' => $subdomain_id ?: null,
			'keyword_id'   => $keyword_id ?: null,
			'status'       => $status ?: null,
			'orderby'      => $orderby,
			'order'        => strtolower( $order ),
		) );
		?>
		<div class="wrap ewo-rss-wrap ewo-src-wrap">
			<h1><?php esc_html_e( 'Sources', 'ewo-rss-engine' ); ?></h1>
			<p class="ewo-rss-tagline">
				<?php esc_html_e( 'Captured full-article Sources from keyword feeds.', 'ewo-rss-engine' ); ?>
			</p>

			<?php $this->render_filters( $domain_id, $subdomain_id, $keyword_id, $status ); ?>

			<?php if ( $total > 0 ) : ?>
				<div class="ewo-src-count-bar">
					<?php
					if ( $pages > 1 ) {
						printf(
							/* translators: 1: first, 2: last, 3: total */
							esc_html__( 'Showing %1$d–%2$d of %3$d Sources', 'ewo-rss-engine' ),
							(int) $first_num,
							(int) $last_num,
							(int) $total
						);
					} else {
						printf(
							/* translators: %d total */
							esc_html__( '%d Sources', 'ewo-rss-engine' ),
							(int) $total
						);
					}
					?>
				</div>
			<?php endif; ?>

			<?php if ( empty( $rows ) ) : ?>
				<p><?php esc_html_e( 'No Sources match the current filters.', 'ewo-rss-engine' ); ?></p>
			<?php else : ?>
				<table class="widefat striped ewo-src-table">
					<thead>
						<tr>
							<th class="ewo-src-col-title"><?php esc_html_e( 'Title', 'ewo-rss-engine' ); ?></th>
							<th><?php $this->sort_link( 'source_domain', __( 'Source Domain', 'ewo-rss-engine' ), $orderby, $order, $base_filter_args ); ?></th>
							<th><?php esc_html_e( 'Strategic Domain', 'ewo-rss-engine' ); ?></th>
							<th><?php esc_html_e( 'Subdomain', 'ewo-rss-engine' ); ?></th>
							<th><?php esc_html_e( 'Keyword', 'ewo-rss-engine' ); ?></th>
							<th><?php $this->sort_link( 'published_at', __( 'Published', 'ewo-rss-engine' ), $orderby, $order, $base_filter_args ); ?></th>
							<th><?php $this->sort_link( 'fetched_at', __( 'Fetched', 'ewo-rss-engine' ), $orderby, $order, $base_filter_args ); ?></th>
							<th><?php esc_html_e( 'Status', 'ewo-rss-engine' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $rows as $row ) : ?>
							<?php
							$domain    = EWO_RSS_Taxonomy::get_domain( (int) $row->domain_id );
							$subdomain = EWO_RSS_Taxonomy::get_subdomain( (int) $row->subdomain_id );
							$keyword   = EWO_RSS_Taxonomy::get_keyword( (int) $row->keyword_id );
							$detail_url = add_query_arg(
								array_merge(
									$base_filter_args,
									array(
										'page'        => self::MENU_SLUG,
										'view_source' => $row->id,
									)
								),
								admin_url( 'admin.php' )
							);
							?>
							<tr>
								<td class="ewo-src-col-title">
									<a href="<?php echo esc_url( $detail_url ); ?>" class="ewo-src-title-link">
										<?php echo esc_html( $row->title ); ?>
									</a>
									<div class="row-actions">
										<span><a href="<?php echo esc_url( $detail_url ); ?>"><?php esc_html_e( 'View', 'ewo-rss-engine' ); ?></a></span>
										&nbsp;|&nbsp;
										<span><a href="<?php echo esc_url( $row->url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Original', 'ewo-rss-engine' ); ?></a></span>
									</div>
								</td>
								<td><?php echo esc_html( $row->source_domain ); ?></td>
								<td><?php echo esc_html( $domain ? $domain->name : '—' ); ?></td>
								<td><?php echo esc_html( $subdomain ? $subdomain->name : '—' ); ?></td>
								<td><?php echo esc_html( $keyword ? $keyword->keyword : '—' ); ?></td>
								<td><?php echo esc_html( $row->published_at ? substr( $row->published_at, 0, 10 ) : '—' ); ?></td>
								<td><?php echo esc_html( $row->fetched_at ? substr( $row->fetched_at, 0, 16 ) : '—' ); ?></td>
								<td><?php $this->status_form( (int) $row->id, (string) $row->status ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<?php $this->render_pagination( $paged, $pages, $total, $base_filter_args ); ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/* ---------------------------------------------------------------------
	 * Pagination
	 * ------------------------------------------------------------------- */

	/**
	 * Build a page URL preserving current filters.
	 *
	 * @param int                  $page      Target page number.
	 * @param array<string,mixed>  $base_args Current filter args (no 'paged').
	 * @return string
	 */
	protected function paged_url( $page, array $base_args ) {
		return add_query_arg(
			array_merge( $base_args, array( 'page' => self::MENU_SLUG, 'paged' => $page ) ),
			admin_url( 'admin.php' )
		);
	}

	/**
	 * Render a custom paginator: sliding window of 10 page numbers + Prev/Next.
	 *
	 * Shows the block of 10 pages that contains $paged, with ellipsis on either
	 * side when there are pages outside the current block.
	 *
	 * @param int                  $paged      Current page number.
	 * @param int                  $pages      Total number of pages.
	 * @param int                  $total      Total number of rows (for the count label).
	 * @param array<string,mixed>  $base_args  Filter args to preserve across pages.
	 */
	protected function render_pagination( $paged, $pages, $total, array $base_args ) {
		if ( $pages <= 1 && $total === 0 ) {
			return;
		}

		$block_size  = 10;
		$block_start = (int) ( floor( ( $paged - 1 ) / $block_size ) * $block_size ) + 1;
		$block_end   = min( $block_start + $block_size - 1, $pages );

		$prev_url = $paged > 1 ? $this->paged_url( $paged - 1, $base_args ) : '';
		$next_url = $paged < $pages ? $this->paged_url( $paged + 1, $base_args ) : '';
		?>
		<div class="ewo-src-pgbar">
			<span class="ewo-src-pg-count">
				<?php
				echo esc_html(
					sprintf(
						/* translators: %d total rows */
						_n( '%d item', '%d items', $total, 'ewo-rss-engine' ),
						$total
					)
				);
				?>
			</span>

			<?php if ( $pages > 1 ) : ?>
			<nav class="ewo-src-pagination" aria-label="<?php esc_attr_e( 'Sources pagination', 'ewo-rss-engine' ); ?>">

				<?php if ( $prev_url ) : ?>
					<a href="<?php echo esc_url( $prev_url ); ?>" class="ewo-pg-btn ewo-pg-nav">&laquo; <?php esc_html_e( 'Previous', 'ewo-rss-engine' ); ?></a>
				<?php else : ?>
					<span class="ewo-pg-btn ewo-pg-nav ewo-pg-disabled" aria-disabled="true">&laquo; <?php esc_html_e( 'Previous', 'ewo-rss-engine' ); ?></span>
				<?php endif; ?>

				<?php if ( $block_start > 1 ) : ?>
					<span class="ewo-pg-btn ewo-pg-dots" aria-hidden="true">&hellip;</span>
				<?php endif; ?>

				<?php for ( $i = $block_start; $i <= $block_end; $i++ ) : ?>
					<?php if ( $i === $paged ) : ?>
						<span class="ewo-pg-btn ewo-pg-current" aria-current="page"><?php echo esc_html( (string) $i ); ?></span>
					<?php else : ?>
						<a href="<?php echo esc_url( $this->paged_url( $i, $base_args ) ); ?>" class="ewo-pg-btn"><?php echo esc_html( (string) $i ); ?></a>
					<?php endif; ?>
				<?php endfor; ?>

				<?php if ( $block_end < $pages ) : ?>
					<span class="ewo-pg-btn ewo-pg-dots" aria-hidden="true">&hellip;</span>
				<?php endif; ?>

				<?php if ( $next_url ) : ?>
					<a href="<?php echo esc_url( $next_url ); ?>" class="ewo-pg-btn ewo-pg-nav"><?php esc_html_e( 'Next', 'ewo-rss-engine' ); ?> &raquo;</a>
				<?php else : ?>
					<span class="ewo-pg-btn ewo-pg-nav ewo-pg-disabled" aria-disabled="true"><?php esc_html_e( 'Next', 'ewo-rss-engine' ); ?> &raquo;</span>
				<?php endif; ?>

			</nav>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render a sortable column header link.
	 *
	 * @param string               $col        Column key.
	 * @param string               $label      Display label.
	 * @param string               $cur_orderby Current orderby.
	 * @param string               $cur_order  Current order ('ASC'|'DESC').
	 * @param array<string,string> $base_args  Base query args (filters).
	 */
	protected function sort_link( $col, $label, $cur_orderby, $cur_order, $base_args ) {
		$is_active = ( $col === $cur_orderby );
		$next_order = ( $is_active && 'ASC' === $cur_order ) ? 'desc' : 'asc';
		$arrow      = '';
		if ( $is_active ) {
			$arrow = 'ASC' === $cur_order ? ' ▲' : ' ▼';
		}

		$url = add_query_arg(
			array_merge(
				$base_args,
				array(
					'page'    => self::MENU_SLUG,
					'orderby' => $col,
					'order'   => $next_order,
				)
			),
			admin_url( 'admin.php' )
		);

		printf(
			'<a href="%s" class="ewo-src-sort%s">%s%s</a>',
			esc_url( $url ),
			$is_active ? ' ewo-src-sort--active' : '',
			esc_html( $label ),
			esc_html( $arrow )
		);
	}

	/**
	 * Render the filter bar with cascading Domain → Subdomain → Keyword dropdowns.
	 *
	 * @param int    $domain_id    Selected domain.
	 * @param int    $subdomain_id Selected subdomain.
	 * @param int    $keyword_id   Selected keyword.
	 * @param string $status       Selected status.
	 */
	protected function render_filters( $domain_id, $subdomain_id, $keyword_id, $status ) {
		$domains    = EWO_RSS_Taxonomy::get_domains();
		$subdomains = EWO_RSS_Taxonomy::get_subdomains();
		$keywords   = EWO_RSS_Taxonomy::get_keywords();
		?>
		<form method="get" class="ewo-src-filter-form" id="ewo-src-filter-form">
			<input type="hidden" name="page" value="<?php echo esc_attr( self::MENU_SLUG ); ?>" />

			<select name="domain_id" id="ewo-src-domain">
				<option value="0"><?php esc_html_e( 'All Domains', 'ewo-rss-engine' ); ?></option>
				<?php foreach ( $domains as $d ) : ?>
					<option value="<?php echo esc_attr( (string) $d->id ); ?>" <?php selected( $domain_id, (int) $d->id ); ?>>
						<?php echo esc_html( $d->name ); ?>
					</option>
				<?php endforeach; ?>
			</select>

			<select name="subdomain_id" id="ewo-src-subdomain">
				<option value="0"><?php esc_html_e( 'All Subdomains', 'ewo-rss-engine' ); ?></option>
				<?php foreach ( $subdomains as $s ) : ?>
					<option value="<?php echo esc_attr( (string) $s->id ); ?>"
						data-domain="<?php echo esc_attr( (string) $s->domain_id ); ?>"
						<?php selected( $subdomain_id, (int) $s->id ); ?>>
						<?php echo esc_html( $s->name ); ?>
					</option>
				<?php endforeach; ?>
			</select>

			<select name="keyword_id" id="ewo-src-keyword">
				<option value="0"><?php esc_html_e( 'All Keywords', 'ewo-rss-engine' ); ?></option>
				<?php foreach ( $keywords as $k ) : ?>
					<option value="<?php echo esc_attr( (string) $k->id ); ?>"
						data-subdomain="<?php echo esc_attr( (string) $k->subdomain_id ); ?>"
						<?php selected( $keyword_id, (int) $k->id ); ?>>
						<?php echo esc_html( $k->keyword ); ?>
					</option>
				<?php endforeach; ?>
			</select>

			<span class="ewo-src-filter-label"><?php esc_html_e( 'Review Status:', 'ewo-rss-engine' ); ?></span>
			<select name="status" id="ewo-src-status">
				<option value=""><?php esc_html_e( 'All', 'ewo-rss-engine' ); ?></option>
				<?php foreach ( EWO_RSS_Source_Store::statuses() as $s ) : ?>
					<option value="<?php echo esc_attr( $s ); ?>" <?php selected( $status, $s ); ?>><?php echo esc_html( ucfirst( $s ) ); ?></option>
				<?php endforeach; ?>
			</select>

			<?php submit_button( __( 'Filter', 'ewo-rss-engine' ), 'secondary', 'submit', false ); ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::MENU_SLUG ) ); ?>" class="button-link"><?php esc_html_e( 'Reset', 'ewo-rss-engine' ); ?></a>
		</form>

		<script>
		(function () {
			var domainSel   = document.getElementById( 'ewo-src-domain' );
			var subSel      = document.getElementById( 'ewo-src-subdomain' );
			var kwSel       = document.getElementById( 'ewo-src-keyword' );
			if ( ! domainSel || ! subSel || ! kwSel ) { return; }

			function applySubFilter() {
				var domainId = parseInt( domainSel.value, 10 );
				var opts = subSel.options;
				for ( var i = 1; i < opts.length; i++ ) {
					var match = ( domainId === 0 || parseInt( opts[ i ].dataset.domain, 10 ) === domainId );
					opts[ i ].hidden   = ! match;
					opts[ i ].disabled = ! match;
				}
				if ( subSel.selectedIndex > 0 && subSel.options[ subSel.selectedIndex ].hidden ) {
					subSel.value = '0';
				}
			}

			function applyKwFilter() {
				var subId = parseInt( subSel.value, 10 );
				var opts  = kwSel.options;
				for ( var i = 1; i < opts.length; i++ ) {
					var match = ( subId === 0 || parseInt( opts[ i ].dataset.subdomain, 10 ) === subId );
					opts[ i ].hidden   = ! match;
					opts[ i ].disabled = ! match;
				}
				if ( kwSel.selectedIndex > 0 && kwSel.options[ kwSel.selectedIndex ].hidden ) {
					kwSel.value = '0';
				}
			}

			domainSel.addEventListener( 'change', function () {
				applySubFilter();
				applyKwFilter();
			} );
			subSel.addEventListener( 'change', applyKwFilter );

			// Apply on page load to respect pre-selected values.
			applySubFilter();
			applyKwFilter();
		} )();
		</script>
		<?php
	}

	/**
	 * Inline status-change form for a Source row.
	 *
	 * @param int    $id      Source ID.
	 * @param string $current Current status.
	 */
	protected function status_form( $id, $current ) {
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:flex;gap:.3em;">
			<input type="hidden" name="action" value="<?php echo esc_attr( self::STATUS_ACTION ); ?>" />
			<input type="hidden" name="source_id" value="<?php echo esc_attr( (string) $id ); ?>" />
			<input type="hidden" name="redirect" value="<?php echo esc_attr( (string) wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) ); // phpcs:ignore WordPress.Security ?>" />
			<?php wp_nonce_field( self::STATUS_ACTION, self::NONCE ); ?>
			<select name="status">
				<?php foreach ( EWO_RSS_Source_Store::statuses() as $s ) : ?>
					<option value="<?php echo esc_attr( $s ); ?>" <?php selected( $current, $s ); ?>><?php echo esc_html( ucfirst( $s ) ); ?></option>
				<?php endforeach; ?>
			</select>
			<?php submit_button( __( 'Set', 'ewo-rss-engine' ), 'small', 'submit', false ); ?>
		</form>
		<?php
	}

	/* ---------------------------------------------------------------------
	 * Detail view
	 * ------------------------------------------------------------------- */

	/**
	 * Render the detail page for one Source.
	 *
	 * @param int $id Source ID.
	 */
	protected function render_detail( $id ) {
		global $wpdb;
		$table = EWO_RSS_Source_Store::table();
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", (int) $id ) ); // phpcs:ignore WordPress.DB

		if ( ! $row ) {
			echo '<div class="wrap"><p>' . esc_html__( 'Source not found.', 'ewo-rss-engine' ) . '</p></div>';
			return;
		}

		$domain    = EWO_RSS_Taxonomy::get_domain( (int) $row->domain_id );
		$subdomain = EWO_RSS_Taxonomy::get_subdomain( (int) $row->subdomain_id );
		$keyword   = EWO_RSS_Taxonomy::get_keyword( (int) $row->keyword_id );

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$back_args = array_filter( array(
			'page'         => self::MENU_SLUG,
			'domain_id'    => isset( $_GET['domain_id'] ) ? absint( wp_unslash( $_GET['domain_id'] ) ) : null,
			'subdomain_id' => isset( $_GET['subdomain_id'] ) ? absint( wp_unslash( $_GET['subdomain_id'] ) ) : null,
			'keyword_id'   => isset( $_GET['keyword_id'] ) ? absint( wp_unslash( $_GET['keyword_id'] ) ) : null,
			'status'       => isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : null,
			'paged'        => isset( $_GET['paged'] ) ? absint( wp_unslash( $_GET['paged'] ) ) : null,
			'orderby'      => isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : null,
			'order'        => isset( $_GET['order'] ) ? sanitize_key( wp_unslash( $_GET['order'] ) ) : null,
		) );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$back_url = add_query_arg( $back_args, admin_url( 'admin.php' ) );

		$status_labels = array(
			'new'      => __( 'New', 'ewo-rss-engine' ),
			'reviewed' => __( 'Reviewed', 'ewo-rss-engine' ),
			'ignored'  => __( 'Ignored', 'ewo-rss-engine' ),
		);
		?>
		<div class="wrap ewo-rss-wrap ewo-src-detail-wrap">
			<a href="<?php echo esc_url( $back_url ); ?>" class="ewo-src-back-link">
				← <?php esc_html_e( 'Back to Sources', 'ewo-rss-engine' ); ?>
			</a>

			<h1 class="ewo-src-detail-title"><?php echo esc_html( $row->title ); ?></h1>

			<div class="ewo-src-detail-actions">
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
					<input type="hidden" name="action" value="<?php echo esc_attr( self::STATUS_ACTION ); ?>" />
					<input type="hidden" name="source_id" value="<?php echo esc_attr( (string) $row->id ); ?>" />
					<input type="hidden" name="status" value="reviewed" />
					<input type="hidden" name="redirect" value="<?php echo esc_attr( remove_query_arg( 'view_source', add_query_arg( array(), '' ) ) ); ?>" />
					<?php wp_nonce_field( self::STATUS_ACTION, self::NONCE ); ?>
					<?php submit_button( __( 'Mark Reviewed', 'ewo-rss-engine' ), 'primary', 'submit', false ); ?>
				</form>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;margin-left:.5em;">
					<input type="hidden" name="action" value="<?php echo esc_attr( self::STATUS_ACTION ); ?>" />
					<input type="hidden" name="source_id" value="<?php echo esc_attr( (string) $row->id ); ?>" />
					<input type="hidden" name="status" value="ignored" />
					<input type="hidden" name="redirect" value="<?php echo esc_attr( remove_query_arg( 'view_source', add_query_arg( array(), '' ) ) ); ?>" />
					<?php wp_nonce_field( self::STATUS_ACTION, self::NONCE ); ?>
					<?php submit_button( __( 'Mark Ignored', 'ewo-rss-engine' ), 'secondary', 'submit', false ); ?>
				</form>
			</div>

			<table class="widefat ewo-src-meta-table">
				<tbody>
					<tr>
						<th><?php esc_html_e( 'Status', 'ewo-rss-engine' ); ?></th>
						<td>
							<span class="ewo-src-status-badge ewo-src-status-<?php echo esc_attr( (string) $row->status ); ?>">
								<?php echo esc_html( isset( $status_labels[ $row->status ] ) ? $status_labels[ $row->status ] : $row->status ); ?>
							</span>
						</td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Original URL', 'ewo-rss-engine' ); ?></th>
						<td><a href="<?php echo esc_url( $row->url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $row->url ); ?></a></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Source Domain', 'ewo-rss-engine' ); ?></th>
						<td><?php echo esc_html( $row->source_domain ); ?></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Strategic Domain', 'ewo-rss-engine' ); ?></th>
						<td><?php echo esc_html( $domain ? $domain->name : '—' ); ?></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Subdomain', 'ewo-rss-engine' ); ?></th>
						<td><?php echo esc_html( $subdomain ? $subdomain->name : '—' ); ?></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Keyword', 'ewo-rss-engine' ); ?></th>
						<td><?php echo esc_html( $keyword ? $keyword->keyword : '—' ); ?></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Published', 'ewo-rss-engine' ); ?></th>
						<td><?php echo esc_html( $row->published_at ?: '—' ); ?></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Fetched', 'ewo-rss-engine' ); ?></th>
						<td><?php echo esc_html( $row->fetched_at ); ?></td>
					</tr>
				</tbody>
			</table>

			<?php if ( ! empty( $row->content ) ) : ?>
				<h2><?php esc_html_e( 'Article Content', 'ewo-rss-engine' ); ?></h2>
				<div class="ewo-src-content-box">
					<?php echo wp_kses_post( $row->content ); ?>
				</div>
			<?php else : ?>
				<p class="ewo-src-no-content"><?php esc_html_e( 'No article content captured.', 'ewo-rss-engine' ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	/* ---------------------------------------------------------------------
	 * Handlers
	 * ------------------------------------------------------------------- */

	/**
	 * Persist a Source status change.
	 */
	public function handle_status() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'You are not allowed to do that.', 'ewo-rss-engine' ) );
		}
		check_admin_referer( self::STATUS_ACTION, self::NONCE );

		$source_id = isset( $_POST['source_id'] ) ? absint( wp_unslash( $_POST['source_id'] ) ) : 0;
		$status    = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : '';

		if ( EWO_RSS_Source_Store::set_status( $source_id, $status ) ) {
			set_transient( self::NOTICE_KEY . get_current_user_id(), __( 'Source status updated.', 'ewo-rss-engine' ), MINUTE_IN_SECONDS );
		}

		$fallback = admin_url( 'admin.php?page=' . self::MENU_SLUG );
		$redirect = isset( $_POST['redirect'] ) ? esc_url_raw( wp_unslash( $_POST['redirect'] ) ) : '';
		wp_safe_redirect( $redirect ? wp_validate_redirect( $redirect, $fallback ) : $fallback );
		exit;
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

	/* ---------------------------------------------------------------------
	 * Front-end shortcode
	 * ------------------------------------------------------------------- */

	/**
	 * `[ewo_sources]` — latest Sources grouped by Strategic Domain → Subdomain.
	 *
	 * Attributes:
	 *  - limit  (int)    Max Sources to display overall. Default 60.
	 *  - status (string) Status filter. Default 'new,reviewed' (hides ignored).
	 *
	 * @param array<string,string>|string $atts Shortcode attributes.
	 * @return string
	 */
	public function shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'limit'  => 60,
				'status' => 'new,reviewed',
			),
			$atts,
			self::SHORTCODE
		);

		$limit         = max( 1, min( 200, (int) $atts['limit'] ) );
		$wanted_status = array_filter( array_map( 'sanitize_key', explode( ',', (string) $atts['status'] ) ) );

		// Pull a window of recent Sources, then group in PHP.
		$rows = EWO_RSS_Source_Store::query( array( 'limit' => $limit ) );

		if ( ! empty( $wanted_status ) ) {
			$rows = array_values(
				array_filter(
					$rows,
					static function ( $row ) use ( $wanted_status ) {
						return in_array( $row->status, $wanted_status, true );
					}
				)
			);
		}

		if ( empty( $rows ) ) {
			return '<div class="ewo-sources ewo-sources--empty"><p>' . esc_html__( 'No sources yet.', 'ewo-rss-engine' ) . '</p></div>';
		}

		// Group: domain → subdomain → rows[].
		$grouped = array();
		foreach ( $rows as $row ) {
			$grouped[ (int) $row->domain_id ][ (int) $row->subdomain_id ][] = $row;
		}

		ob_start();
		echo '<div class="ewo-sources">';

		foreach ( $grouped as $domain_id => $subgroups ) {
			$domain = EWO_RSS_Taxonomy::get_domain( $domain_id );
			$name   = $domain ? $domain->name : __( 'Uncategorized', 'ewo-rss-engine' );
			echo '<section class="ewo-sources__domain">';
			echo '<h2 class="ewo-sources__domain-title">' . esc_html( $name ) . '</h2>';

			foreach ( $subgroups as $subdomain_id => $items ) {
				$subdomain = EWO_RSS_Taxonomy::get_subdomain( $subdomain_id );
				$sub_name  = $subdomain ? $subdomain->name : __( 'General', 'ewo-rss-engine' );
				echo '<div class="ewo-sources__subdomain">';
				echo '<h3 class="ewo-sources__subdomain-title">' . esc_html( $sub_name ) . '</h3>';
				echo '<ul class="ewo-sources__list">';

				foreach ( $items as $item ) {
					$meta = trim( $item->source_domain . ( $item->published_at ? ' · ' . mysql2date( get_option( 'date_format' ), $item->published_at ) : '' ) );
					echo '<li class="ewo-sources__item">';
					echo '<a class="ewo-sources__link" href="' . esc_url( $item->url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $item->title ) . '</a>';
					if ( '' !== $meta ) {
						echo ' <span class="ewo-sources__meta">' . esc_html( $meta ) . '</span>';
					}
					echo '</li>';
				}

				echo '</ul></div>';
			}

			echo '</section>';
		}

		echo '</div>';

		return (string) ob_get_clean();
	}
}
