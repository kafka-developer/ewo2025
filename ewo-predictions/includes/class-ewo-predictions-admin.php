<?php
/**
 * Admin UI controller for EWO Predictions.
 *
 * @package EWO_Predictions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EWO_Predictions_Admin {

	const MENU_SLUG    = 'ewo-predictions';
	const ADD_SLUG     = 'ewo-predictions-add';
	const CAP          = 'manage_options';
	const NONCE_SAVE   = 'ewo_pred_save';
	const NONCE_DELETE = 'ewo_pred_delete';
	const PER_PAGE     = 10;

	protected $hook = '';

	public function init() {
		add_action( 'admin_menu',             array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts',  array( $this, 'enqueue_assets' ) );
		add_action( 'admin_post_ewo_pred_save',   array( $this, 'handle_save' ) );
		add_action( 'admin_post_ewo_pred_delete', array( $this, 'handle_delete' ) );
		add_action( 'wp_ajax_ewo_pred_subdomains', array( $this, 'ajax_subdomains' ) );
		add_action( 'admin_notices',          array( $this, 'render_notice' ) );
	}

	public function register_menu() {
		$this->hook = (string) add_menu_page(
			__( 'EWO Predictions', 'ewo-predictions' ),
			__( 'Predictions', 'ewo-predictions' ),
			self::CAP,
			self::MENU_SLUG,
			array( $this, 'dispatch' ),
			'dashicons-chart-line',
			57
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'All Predictions', 'ewo-predictions' ),
			__( 'All Predictions', 'ewo-predictions' ),
			self::CAP,
			self::MENU_SLUG,
			array( $this, 'dispatch' )
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Add Prediction', 'ewo-predictions' ),
			__( '+ Add Prediction', 'ewo-predictions' ),
			self::CAP,
			self::ADD_SLUG,
			array( $this, 'render_add' )
		);
	}

	public function enqueue_assets( $hook ) {
		if ( false === strpos( $hook, 'ewo-predictions' ) ) {
			return;
		}
		wp_enqueue_style( 'ewo-predictions-admin', EWO_PRED_URL . 'assets/css/admin.css', array(), EWO_PRED_VERSION );
		wp_enqueue_script( 'ewo-predictions-admin', EWO_PRED_URL . 'assets/js/admin.js', array(), EWO_PRED_VERSION, true );
		wp_localize_script( 'ewo-predictions-admin', 'ewoPred', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'ewo_pred_ajax' ),
		) );
	}

	/* -------------------------------------------------------------------------
	   Dispatch
	   ---------------------------------------------------------------------- */

	public function dispatch() {
		if ( ! current_user_can( self::CAP ) ) {
			return;
		}
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
		$id     = isset( $_GET['id'] )     ? absint( wp_unslash( $_GET['id'] ) )           : 0;
		// phpcs:enable

		switch ( $action ) {
			case 'edit':
				$this->render_edit( $id );
				break;
			case 'view':
				$this->render_view( $id );
				break;
			default:
				$this->render_list();
		}
	}

	/* -------------------------------------------------------------------------
	   List page
	   ---------------------------------------------------------------------- */

	public function render_list() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$domain_id    = isset( $_GET['domain_id'] )    ? absint( wp_unslash( $_GET['domain_id'] ) )           : 0;
		$subdomain_id = isset( $_GET['subdomain_id'] ) ? absint( wp_unslash( $_GET['subdomain_id'] ) )         : 0;
		$pred_type    = isset( $_GET['pred_type'] )    ? sanitize_text_field( wp_unslash( $_GET['pred_type'] ) ) : '';
		$status       = isset( $_GET['status'] )       ? sanitize_key( wp_unslash( $_GET['status'] ) )         : '';
		$confidence   = isset( $_GET['confidence'] )   ? sanitize_key( wp_unslash( $_GET['confidence'] ) )     : '';
		$date_from    = isset( $_GET['date_from'] )    ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : '';
		$date_to      = isset( $_GET['date_to'] )      ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) )   : '';
		$paged        = isset( $_GET['paged'] )        ? max( 1, absint( wp_unslash( $_GET['paged'] ) ) )      : 1;
		$orderby      = isset( $_GET['orderby'] )      ? sanitize_key( wp_unslash( $_GET['orderby'] ) )        : 'id';
		$order        = isset( $_GET['order'] ) && 'asc' === strtolower( wp_unslash( $_GET['order'] ) ) ? 'ASC' : 'DESC'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		// phpcs:enable

		$conf_range = $this->confidence_range( $confidence );

		$args = array(
			'domain_id'      => $domain_id,
			'subdomain_id'   => $subdomain_id,
			'prediction_type'=> $pred_type,
			'status'         => $status,
			'confidence_min' => $conf_range[0],
			'confidence_max' => $conf_range[1],
			'date_from'      => $date_from,
			'date_to'        => $date_to,
			'orderby'        => $orderby,
			'order'          => $order,
			'limit'          => self::PER_PAGE,
			'offset'         => ( $paged - 1 ) * self::PER_PAGE,
		);

		$rows    = EWO_Predictions_DB::query( $args );
		$total   = EWO_Predictions_DB::count( $args );
		$pages   = (int) ceil( $total / self::PER_PAGE );
		$metrics = EWO_Predictions_DB::metrics();
		$types   = EWO_Predictions_DB::get_types();
		$domains    = $this->get_domains();
		$subdomains = $this->get_subdomains( $domain_id );
		$add_url    = admin_url( 'admin.php?page=' . self::ADD_SLUG );
		$base_url   = admin_url( 'admin.php?page=' . self::MENU_SLUG );

		$filter_args = array_filter( array(
			'domain_id'    => $domain_id ?: null,
			'subdomain_id' => $subdomain_id ?: null,
			'pred_type'    => $pred_type ?: null,
			'status'       => $status ?: null,
			'confidence'   => $confidence ?: null,
			'date_from'    => $date_from ?: null,
			'date_to'      => $date_to ?: null,
			'orderby'      => $orderby !== 'id' ? $orderby : null,
			'order'        => $order !== 'DESC' ? strtolower( $order ) : null,
		) );
		?>
		<div class="ewo-pred-wrap">

			<!-- ── PAGE HEADER ── -->
			<div class="ewo-pred-page-header">
				<div>
					<h1 class="ewo-pred-page-title"><?php esc_html_e( 'Predictions', 'ewo-predictions' ); ?></h1>
					<p class="ewo-pred-page-sub"><?php esc_html_e( 'Track and manage geopolitical, economic, energy, and strategic predictions.', 'ewo-predictions' ); ?></p>
				</div>
				<a href="<?php echo esc_url( $add_url ); ?>" class="ewo-pred-btn ewo-pred-btn--gold">
					+ <?php esc_html_e( 'Add Prediction', 'ewo-predictions' ); ?>
				</a>
			</div>

			<!-- ── FILTER BAR ── -->
			<form method="get" class="ewo-pred-filters" id="ewo-pred-filters">
				<input type="hidden" name="page" value="<?php echo esc_attr( self::MENU_SLUG ); ?>" />

				<div class="ewo-pred-filter-grid">
					<div class="ewo-pred-filter-field">
						<label><?php esc_html_e( 'Domain', 'ewo-predictions' ); ?></label>
						<select name="domain_id" id="ewo-pred-domain-filter">
							<option value="0"><?php esc_html_e( 'All Domains', 'ewo-predictions' ); ?></option>
							<?php foreach ( $domains as $d ) : ?>
								<option value="<?php echo esc_attr( (string) $d->id ); ?>" <?php selected( $domain_id, (int) $d->id ); ?>><?php echo esc_html( $d->name ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="ewo-pred-filter-field">
						<label><?php esc_html_e( 'Subdomain', 'ewo-predictions' ); ?></label>
						<select name="subdomain_id" id="ewo-pred-subdomain-filter">
							<option value="0"><?php esc_html_e( 'All Subdomains', 'ewo-predictions' ); ?></option>
							<?php foreach ( $subdomains as $s ) : ?>
								<option value="<?php echo esc_attr( (string) $s->id ); ?>"
								        data-domain="<?php echo esc_attr( (string) $s->domain_id ); ?>"
								        <?php selected( $subdomain_id, (int) $s->id ); ?>>
									<?php echo esc_html( $s->name ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="ewo-pred-filter-field">
						<label><?php esc_html_e( 'Type', 'ewo-predictions' ); ?></label>
						<select name="pred_type">
							<option value=""><?php esc_html_e( 'All Types', 'ewo-predictions' ); ?></option>
							<?php foreach ( $types as $t ) : ?>
								<option value="<?php echo esc_attr( $t ); ?>" <?php selected( $pred_type, $t ); ?>><?php echo esc_html( $t ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="ewo-pred-filter-field">
						<label><?php esc_html_e( 'Status', 'ewo-predictions' ); ?></label>
						<select name="status">
							<option value=""><?php esc_html_e( 'All Statuses', 'ewo-predictions' ); ?></option>
							<?php foreach ( EWO_Predictions_DB::statuses() as $s ) : ?>
								<option value="<?php echo esc_attr( $s ); ?>" <?php selected( $status, $s ); ?>><?php echo esc_html( ucfirst( $s ) ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="ewo-pred-filter-field">
						<label><?php esc_html_e( 'Confidence', 'ewo-predictions' ); ?></label>
						<select name="confidence">
							<option value=""><?php esc_html_e( 'All Confidence', 'ewo-predictions' ); ?></option>
							<option value="high"   <?php selected( $confidence, 'high' ); ?>><?php esc_html_e( 'High (80–100%)', 'ewo-predictions' ); ?></option>
							<option value="medium" <?php selected( $confidence, 'medium' ); ?>><?php esc_html_e( 'Medium (60–79%)', 'ewo-predictions' ); ?></option>
							<option value="low"    <?php selected( $confidence, 'low' ); ?>><?php esc_html_e( 'Low (below 60%)', 'ewo-predictions' ); ?></option>
						</select>
					</div>

					<div class="ewo-pred-filter-field">
						<label><?php esc_html_e( 'Date From', 'ewo-predictions' ); ?></label>
						<input type="date" name="date_from" value="<?php echo esc_attr( $date_from ); ?>" />
					</div>

					<div class="ewo-pred-filter-field">
						<label><?php esc_html_e( 'Date To', 'ewo-predictions' ); ?></label>
						<input type="date" name="date_to" value="<?php echo esc_attr( $date_to ); ?>" />
					</div>

					<div class="ewo-pred-filter-actions">
						<button type="submit" class="ewo-pred-btn ewo-pred-btn--primary"><?php esc_html_e( 'Apply', 'ewo-predictions' ); ?></button>
						<a href="<?php echo esc_url( $base_url ); ?>" class="ewo-pred-btn ewo-pred-btn--ghost"><?php esc_html_e( 'Reset', 'ewo-predictions' ); ?></a>
					</div>
				</div>
			</form>

			<!-- ── METRIC CARDS ── -->
			<div class="ewo-pred-metrics">
				<?php
				$cards = array(
					array( 'label' => __( 'Total Predictions', 'ewo-predictions' ), 'value' => $metrics['total'],    'icon' => 'dashicons-list-view',    'mod' => '' ),
					array( 'label' => __( 'Active',            'ewo-predictions' ), 'value' => $metrics['active'],   'icon' => 'dashicons-marker',       'mod' => 'green' ),
					array( 'label' => __( 'Hit',               'ewo-predictions' ), 'value' => $metrics['hit'],      'icon' => 'dashicons-yes-alt',      'mod' => 'gold' ),
					array( 'label' => __( 'Missed',            'ewo-predictions' ), 'value' => $metrics['missed'],   'icon' => 'dashicons-dismiss',      'mod' => 'red' ),
					array( 'label' => __( 'Avg Confidence',    'ewo-predictions' ), 'value' => $metrics['avg_conf'] . '%', 'icon' => 'dashicons-performance', 'mod' => 'blue' ),
				);
				foreach ( $cards as $card ) :
				?>
					<div class="ewo-pred-metric-card">
						<div class="ewo-pred-metric-body">
							<span class="ewo-pred-metric-label"><?php echo esc_html( $card['label'] ); ?></span>
							<span class="ewo-pred-metric-value"><?php echo esc_html( (string) $card['value'] ); ?></span>
						</div>
						<span class="ewo-pred-metric-icon dashicons <?php echo esc_attr( $card['icon'] ); ?> ewo-pred-metric-icon--<?php echo esc_attr( $card['mod'] ); ?>"></span>
					</div>
				<?php endforeach; ?>
			</div>

			<!-- ── TABLE CARD ── -->
			<div class="ewo-pred-table-card">

				<div class="ewo-pred-table-card-header">
					<h2 class="ewo-pred-table-title"><?php esc_html_e( 'All Predictions', 'ewo-predictions' ); ?></h2>
					<?php if ( $total > 0 ) : ?>
						<span class="ewo-pred-count-label">
							<?php
							$first = ( $paged - 1 ) * self::PER_PAGE + 1;
							$last  = min( $paged * self::PER_PAGE, $total );
							printf(
								/* translators: 1: first, 2: last, 3: total */
								esc_html__( 'Showing %1$d–%2$d of %3$d', 'ewo-predictions' ),
								(int) $first, (int) $last, (int) $total
							);
							?>
						</span>
					<?php endif; ?>
				</div>

				<?php if ( empty( $rows ) ) : ?>
					<p class="ewo-pred-empty"><?php esc_html_e( 'No predictions match the current filters.', 'ewo-predictions' ); ?></p>
				<?php else : ?>
					<div class="ewo-pred-table-scroll">
						<table class="ewo-pred-table">
							<thead>
								<tr>
									<th><?php $this->sort_link( 'id',              __( 'ID', 'ewo-predictions' ),              $orderby, $order, $filter_args ); ?></th>
									<th><?php $this->sort_link( 'title',           __( 'Title', 'ewo-predictions' ),           $orderby, $order, $filter_args ); ?></th>
									<th><?php esc_html_e( 'Domain', 'ewo-predictions' ); ?></th>
									<th><?php esc_html_e( 'Subdomain', 'ewo-predictions' ); ?></th>
									<th><?php esc_html_e( 'Type', 'ewo-predictions' ); ?></th>
									<th><?php $this->sort_link( 'confidence_score', __( 'Confidence', 'ewo-predictions' ),     $orderby, $order, $filter_args ); ?></th>
									<th><?php $this->sort_link( 'prediction_date', __( 'Pred. Date', 'ewo-predictions' ),      $orderby, $order, $filter_args ); ?></th>
									<th><?php $this->sort_link( 'target_date',     __( 'Target Date', 'ewo-predictions' ),     $orderby, $order, $filter_args ); ?></th>
									<th><?php $this->sort_link( 'status',          __( 'Status', 'ewo-predictions' ),          $orderby, $order, $filter_args ); ?></th>
									<th><?php esc_html_e( 'Actions', 'ewo-predictions' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $rows as $row ) : ?>
									<?php
									$view_url    = add_query_arg( array( 'page' => self::MENU_SLUG, 'action' => 'view', 'id' => $row->id ), admin_url( 'admin.php' ) );
									$edit_url    = add_query_arg( array( 'page' => self::MENU_SLUG, 'action' => 'edit', 'id' => $row->id ), admin_url( 'admin.php' ) );
									$delete_url  = wp_nonce_url(
										add_query_arg( array( 'action' => 'ewo_pred_delete', 'id' => $row->id ), admin_url( 'admin-post.php' ) ),
										self::NONCE_DELETE . '_' . $row->id
									);
									?>
									<tr>
										<td class="ewo-pred-td-id">#<?php echo esc_html( (string) $row->id ); ?></td>
										<td class="ewo-pred-td-title">
											<a href="<?php echo esc_url( $view_url ); ?>" class="ewo-pred-title-link">
												<?php echo esc_html( wp_trim_words( $row->title, 8 ) ); ?>
											</a>
										</td>
										<td class="ewo-pred-td-muted"><?php echo esc_html( $row->domain_name ?: '—' ); ?></td>
										<td class="ewo-pred-td-muted"><?php echo esc_html( $row->subdomain_name ?: '—' ); ?></td>
										<td class="ewo-pred-td-muted"><?php echo esc_html( $row->prediction_type ?: '—' ); ?></td>
										<td><?php echo wp_kses_post( $this->confidence_badge( (int) $row->confidence_score ) ); ?></td>
										<td class="ewo-pred-td-date"><?php echo esc_html( $row->prediction_date ? wp_date( 'M j, Y', strtotime( $row->prediction_date ) ) : '—' ); ?></td>
										<td class="ewo-pred-td-date"><?php echo esc_html( $row->target_date ? wp_date( 'M j, Y', strtotime( $row->target_date ) ) : '—' ); ?></td>
										<td><?php echo wp_kses_post( $this->status_badge( $row->status ) ); ?></td>
										<td class="ewo-pred-td-actions">
											<a href="<?php echo esc_url( $view_url ); ?>"   class="ewo-pred-action-btn" title="<?php esc_attr_e( 'View', 'ewo-predictions' ); ?>"><span class="dashicons dashicons-visibility"></span></a>
											<a href="<?php echo esc_url( $edit_url ); ?>"   class="ewo-pred-action-btn" title="<?php esc_attr_e( 'Edit', 'ewo-predictions' ); ?>"><span class="dashicons dashicons-edit"></span></a>
											<a href="<?php echo esc_url( $delete_url ); ?>" class="ewo-pred-action-btn ewo-pred-action-btn--danger"
											   title="<?php esc_attr_e( 'Delete', 'ewo-predictions' ); ?>"
											   onclick="return confirm('<?php echo esc_js( __( 'Delete this prediction?', 'ewo-predictions' ) ); ?>');">
												<span class="dashicons dashicons-trash"></span>
											</a>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endif; ?>

				<!-- ── PAGINATION ── -->
				<?php if ( $pages > 1 ) : ?>
					<?php $this->render_pagination( $paged, $pages, $total, $filter_args ); ?>
				<?php endif; ?>

			</div><!-- .ewo-pred-table-card -->

		</div><!-- .ewo-pred-wrap -->
		<?php
	}

	/* -------------------------------------------------------------------------
	   Add form
	   ---------------------------------------------------------------------- */

	public function render_add() {
		if ( ! current_user_can( self::CAP ) ) {
			return;
		}
		$domains = $this->get_domains();
		?>
		<div class="ewo-pred-wrap">
			<div class="ewo-pred-page-header">
				<div>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::MENU_SLUG ) ); ?>" class="ewo-pred-back">
						&larr; <?php esc_html_e( 'All Predictions', 'ewo-predictions' ); ?>
					</a>
					<h1 class="ewo-pred-page-title"><?php esc_html_e( 'Add Prediction', 'ewo-predictions' ); ?></h1>
				</div>
			</div>

			<?php $this->render_form( null, $domains ); ?>
		</div>
		<?php
	}

	/* -------------------------------------------------------------------------
	   Edit form
	   ---------------------------------------------------------------------- */

	public function render_edit( $id ) {
		if ( ! current_user_can( self::CAP ) ) {
			return;
		}
		$pred = EWO_Predictions_DB::get( $id );
		if ( ! $pred ) {
			echo '<div class="ewo-pred-wrap"><p class="ewo-pred-empty">' . esc_html__( 'Prediction not found.', 'ewo-predictions' ) . '</p></div>';
			return;
		}
		$domains = $this->get_domains();
		?>
		<div class="ewo-pred-wrap">
			<div class="ewo-pred-page-header">
				<div>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::MENU_SLUG ) ); ?>" class="ewo-pred-back">
						&larr; <?php esc_html_e( 'All Predictions', 'ewo-predictions' ); ?>
					</a>
					<h1 class="ewo-pred-page-title">
						<?php esc_html_e( 'Edit Prediction', 'ewo-predictions' ); ?> <span class="ewo-pred-id-label">#<?php echo esc_html( (string) $pred->id ); ?></span>
					</h1>
				</div>
			</div>
			<?php $this->render_form( $pred, $domains ); ?>
		</div>
		<?php
	}

	/* -------------------------------------------------------------------------
	   Detail view
	   ---------------------------------------------------------------------- */

	public function render_view( $id ) {
		if ( ! current_user_can( self::CAP ) ) {
			return;
		}
		$pred = EWO_Predictions_DB::get( $id );
		if ( ! $pred ) {
			echo '<div class="ewo-pred-wrap"><p class="ewo-pred-empty">' . esc_html__( 'Prediction not found.', 'ewo-predictions' ) . '</p></div>';
			return;
		}
		$edit_url   = add_query_arg( array( 'page' => self::MENU_SLUG, 'action' => 'edit', 'id' => $pred->id ), admin_url( 'admin.php' ) );
		$delete_url = wp_nonce_url(
			add_query_arg( array( 'action' => 'ewo_pred_delete', 'id' => $pred->id ), admin_url( 'admin-post.php' ) ),
			self::NONCE_DELETE . '_' . $pred->id
		);
		?>
		<div class="ewo-pred-wrap">
			<div class="ewo-pred-page-header">
				<div>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::MENU_SLUG ) ); ?>" class="ewo-pred-back">
						&larr; <?php esc_html_e( 'All Predictions', 'ewo-predictions' ); ?>
					</a>
					<h1 class="ewo-pred-page-title"><?php echo esc_html( $pred->title ); ?></h1>
				</div>
				<div class="ewo-pred-detail-actions">
					<a href="<?php echo esc_url( $edit_url ); ?>" class="ewo-pred-btn ewo-pred-btn--gold"><?php esc_html_e( 'Edit', 'ewo-predictions' ); ?></a>
					<a href="<?php echo esc_url( $delete_url ); ?>" class="ewo-pred-btn ewo-pred-btn--danger"
					   onclick="return confirm('<?php echo esc_js( __( 'Delete this prediction?', 'ewo-predictions' ) ); ?>');">
						<?php esc_html_e( 'Delete', 'ewo-predictions' ); ?>
					</a>
				</div>
			</div>

			<div class="ewo-pred-detail-grid">
				<div class="ewo-pred-detail-main">
					<div class="ewo-pred-detail-card">
						<h3 class="ewo-pred-detail-section-title"><?php esc_html_e( 'Prediction Statement', 'ewo-predictions' ); ?></h3>
						<p class="ewo-pred-detail-statement"><?php echo esc_html( $pred->prediction_statement ); ?></p>

						<?php if ( ! empty( $pred->rationale ) ) : ?>
							<h3 class="ewo-pred-detail-section-title" style="margin-top:24px;"><?php esc_html_e( 'Rationale', 'ewo-predictions' ); ?></h3>
							<p class="ewo-pred-detail-text"><?php echo esc_html( $pred->rationale ); ?></p>
						<?php endif; ?>

						<?php if ( ! empty( $pred->outcome_notes ) ) : ?>
							<h3 class="ewo-pred-detail-section-title" style="margin-top:24px;"><?php esc_html_e( 'Outcome Notes', 'ewo-predictions' ); ?></h3>
							<p class="ewo-pred-detail-text"><?php echo esc_html( $pred->outcome_notes ); ?></p>
						<?php endif; ?>
					</div>
				</div>

				<div class="ewo-pred-detail-side">
					<div class="ewo-pred-detail-card">
						<h3 class="ewo-pred-detail-section-title"><?php esc_html_e( 'Details', 'ewo-predictions' ); ?></h3>
						<dl class="ewo-pred-meta-list">
							<dt><?php esc_html_e( 'Status', 'ewo-predictions' ); ?></dt>
							<dd><?php echo wp_kses_post( $this->status_badge( $pred->status ) ); ?></dd>
							<dt><?php esc_html_e( 'Confidence', 'ewo-predictions' ); ?></dt>
							<dd><?php echo wp_kses_post( $this->confidence_badge( (int) $pred->confidence_score ) ); ?></dd>
							<dt><?php esc_html_e( 'Domain', 'ewo-predictions' ); ?></dt>
							<dd><?php echo esc_html( $pred->domain_name ?: '—' ); ?></dd>
							<dt><?php esc_html_e( 'Subdomain', 'ewo-predictions' ); ?></dt>
							<dd><?php echo esc_html( $pred->subdomain_name ?: '—' ); ?></dd>
							<dt><?php esc_html_e( 'Type', 'ewo-predictions' ); ?></dt>
							<dd><?php echo esc_html( $pred->prediction_type ?: '—' ); ?></dd>
							<dt><?php esc_html_e( 'Prediction Date', 'ewo-predictions' ); ?></dt>
							<dd><?php echo esc_html( $pred->prediction_date ? wp_date( 'M j, Y', strtotime( $pred->prediction_date ) ) : '—' ); ?></dd>
							<dt><?php esc_html_e( 'Target Date', 'ewo-predictions' ); ?></dt>
							<dd><?php echo esc_html( $pred->target_date ? wp_date( 'M j, Y', strtotime( $pred->target_date ) ) : '—' ); ?></dd>
							<dt><?php esc_html_e( 'Visibility', 'ewo-predictions' ); ?></dt>
							<dd><?php echo esc_html( ucfirst( $pred->visibility ?? 'public' ) ); ?></dd>
							<?php if ( ! empty( $pred->source_url ) ) : ?>
							<dt><?php esc_html_e( 'Source', 'ewo-predictions' ); ?></dt>
							<dd style="word-break:break-all;"><a href="<?php echo esc_url( $pred->source_url ); ?>" target="_blank" rel="noopener noreferrer" style="color:var(--pred-gold);"><?php echo esc_html( $pred->source_url ); ?></a></dd>
							<?php endif; ?>
							<dt><?php esc_html_e( 'Created', 'ewo-predictions' ); ?></dt>
							<dd><?php echo esc_html( $pred->created_at ? wp_date( 'M j, Y', strtotime( $pred->created_at ) ) : '—' ); ?></dd>
						</dl>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/* -------------------------------------------------------------------------
	   Shared form
	   ---------------------------------------------------------------------- */

	protected function render_form( $pred, $domains ) {
		$is_edit    = ! is_null( $pred );
		$action_key = $is_edit ? 'ewo_pred_save' : 'ewo_pred_save';
		$subdomains = $is_edit && $pred->domain_id ? $this->get_subdomains( (int) $pred->domain_id ) : array();
		$common_types = array( 'Geopolitical', 'Economic', 'Energy', 'Financial', 'Military', 'Technology', 'Trade', 'Diplomatic', 'Environmental', 'Strategic' );
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ewo-pred-form-wrap">
			<input type="hidden" name="action" value="ewo_pred_save" />
			<?php if ( $is_edit ) : ?>
				<input type="hidden" name="pred_id" value="<?php echo esc_attr( (string) $pred->id ); ?>" />
			<?php endif; ?>
			<?php wp_nonce_field( self::NONCE_SAVE ); ?>

			<div class="ewo-pred-form-grid">
				<div class="ewo-pred-form-main">
					<div class="ewo-pred-form-card">

						<div class="ewo-pred-field">
							<label for="pred-title"><?php esc_html_e( 'Title', 'ewo-predictions' ); ?> <span class="ewo-pred-required">*</span></label>
							<input type="text" id="pred-title" name="title" required maxlength="500"
							       value="<?php echo esc_attr( $pred->title ?? '' ); ?>"
							       placeholder="<?php esc_attr_e( 'e.g. BRICS Currency Settlement Expansion', 'ewo-predictions' ); ?>" />
						</div>

						<div class="ewo-pred-field">
							<label for="pred-statement"><?php esc_html_e( 'Prediction Summary', 'ewo-predictions' ); ?> <span class="ewo-pred-required">*</span></label>
							<textarea id="pred-statement" name="prediction_statement" rows="3" required
							          placeholder="<?php esc_attr_e( 'A concise, clear statement of the prediction…', 'ewo-predictions' ); ?>"><?php echo esc_textarea( $pred->prediction_statement ?? '' ); ?></textarea>
						</div>

						<div class="ewo-pred-field">
							<label for="pred-rationale"><?php esc_html_e( 'Full Analysis', 'ewo-predictions' ); ?></label>
							<textarea id="pred-rationale" name="rationale" rows="6"
							          placeholder="<?php esc_attr_e( 'Detailed analysis, reasoning, and strategic significance…', 'ewo-predictions' ); ?>"><?php echo esc_textarea( $pred->rationale ?? '' ); ?></textarea>
						</div>

						<div class="ewo-pred-field">
							<label for="pred-source"><?php esc_html_e( 'Source / Reference URL', 'ewo-predictions' ); ?></label>
							<input type="url" id="pred-source" name="source_url" maxlength="500"
							       value="<?php echo esc_attr( $pred->source_url ?? '' ); ?>"
							       placeholder="https://" />
						</div>

						<?php if ( $is_edit ) : ?>
						<div class="ewo-pred-field">
							<label for="pred-outcome"><?php esc_html_e( 'Outcome Notes', 'ewo-predictions' ); ?></label>
							<textarea id="pred-outcome" name="outcome_notes" rows="3"
							          placeholder="<?php esc_attr_e( 'Record what actually happened…', 'ewo-predictions' ); ?>"><?php echo esc_textarea( $pred->outcome_notes ?? '' ); ?></textarea>
						</div>
						<?php endif; ?>

					</div><!-- .ewo-pred-form-card -->
				</div><!-- .ewo-pred-form-main -->

				<div class="ewo-pred-form-side">
					<div class="ewo-pred-form-card">

						<div class="ewo-pred-field">
							<label for="pred-domain"><?php esc_html_e( 'Domain', 'ewo-predictions' ); ?></label>
							<select id="pred-domain" name="domain_id">
								<option value="0"><?php esc_html_e( 'Select Domain…', 'ewo-predictions' ); ?></option>
								<?php foreach ( $domains as $d ) : ?>
									<option value="<?php echo esc_attr( (string) $d->id ); ?>" <?php selected( (int) ( $pred->domain_id ?? 0 ), (int) $d->id ); ?>><?php echo esc_html( $d->name ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="ewo-pred-field">
							<label for="pred-subdomain"><?php esc_html_e( 'Subdomain', 'ewo-predictions' ); ?></label>
							<select id="pred-subdomain" name="subdomain_id">
								<option value="0"><?php esc_html_e( 'Select Subdomain…', 'ewo-predictions' ); ?></option>
								<?php foreach ( $subdomains as $s ) : ?>
									<option value="<?php echo esc_attr( (string) $s->id ); ?>" <?php selected( (int) ( $pred->subdomain_id ?? 0 ), (int) $s->id ); ?>><?php echo esc_html( $s->name ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="ewo-pred-field">
							<label for="pred-type"><?php esc_html_e( 'Prediction Type', 'ewo-predictions' ); ?></label>
							<input type="text" id="pred-type" name="prediction_type" list="ewo-pred-type-list"
							       value="<?php echo esc_attr( $pred->prediction_type ?? '' ); ?>"
							       placeholder="<?php esc_attr_e( 'e.g. Energy, Financial…', 'ewo-predictions' ); ?>" />
							<datalist id="ewo-pred-type-list">
								<?php foreach ( $common_types as $ct ) : ?>
									<option value="<?php echo esc_attr( $ct ); ?>"></option>
								<?php endforeach; ?>
							</datalist>
						</div>

						<div class="ewo-pred-field">
							<label for="pred-confidence">
								<?php esc_html_e( 'Confidence Score', 'ewo-predictions' ); ?>
								<span class="ewo-pred-conf-display" id="ewo-pred-conf-display"><?php echo esc_html( ( $pred->confidence_score ?? 50 ) . '%' ); ?></span>
							</label>
							<input type="range" id="pred-confidence" name="confidence_score"
							       min="0" max="100" step="1"
							       value="<?php echo esc_attr( (string) ( $pred->confidence_score ?? 50 ) ); ?>" />
						</div>

						<div class="ewo-pred-field">
							<label for="pred-status"><?php esc_html_e( 'Status', 'ewo-predictions' ); ?></label>
							<select id="pred-status" name="status">
								<?php foreach ( EWO_Predictions_DB::statuses() as $s ) : ?>
									<option value="<?php echo esc_attr( $s ); ?>" <?php selected( $pred->status ?? 'active', $s ); ?>><?php echo esc_html( ucfirst( $s ) ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="ewo-pred-field">
							<label for="pred-pred-date"><?php esc_html_e( 'Prediction Date', 'ewo-predictions' ); ?></label>
							<input type="date" id="pred-pred-date" name="prediction_date"
							       value="<?php echo esc_attr( $pred->prediction_date ?? '' ); ?>" />
						</div>

						<div class="ewo-pred-field">
							<label for="pred-target-date"><?php esc_html_e( 'Target Date', 'ewo-predictions' ); ?></label>
							<input type="date" id="pred-target-date" name="target_date"
							       value="<?php echo esc_attr( $pred->target_date ?? '' ); ?>" />
						</div>

						<div class="ewo-pred-field">
							<label for="pred-visibility"><?php esc_html_e( 'Visibility', 'ewo-predictions' ); ?></label>
							<select id="pred-visibility" name="visibility">
								<option value="public"  <?php selected( ( $pred->visibility ?? 'public' ), 'public' ); ?>><?php esc_html_e( 'Public', 'ewo-predictions' ); ?></option>
								<option value="private" <?php selected( ( $pred->visibility ?? 'public' ), 'private' ); ?>><?php esc_html_e( 'Private', 'ewo-predictions' ); ?></option>
							</select>
						</div>

					</div><!-- .ewo-pred-form-card -->

					<div class="ewo-pred-form-buttons">
						<button type="submit" class="ewo-pred-btn ewo-pred-btn--gold ewo-pred-btn--wide">
							<?php echo $is_edit ? esc_html__( 'Update Prediction', 'ewo-predictions' ) : esc_html__( 'Save Prediction', 'ewo-predictions' ); ?>
						</button>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::MENU_SLUG ) ); ?>"
						   class="ewo-pred-btn ewo-pred-btn--ghost ewo-pred-btn--wide">
							<?php esc_html_e( 'Cancel', 'ewo-predictions' ); ?>
						</a>
					</div>
				</div><!-- .ewo-pred-form-side -->
			</div><!-- .ewo-pred-form-grid -->
		</form>
		<?php
	}

	/* -------------------------------------------------------------------------
	   Form handlers
	   ---------------------------------------------------------------------- */

	public function handle_save() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'Not allowed.', 'ewo-predictions' ) );
		}
		check_admin_referer( self::NONCE_SAVE );

		$pred_id = isset( $_POST['pred_id'] ) ? absint( wp_unslash( $_POST['pred_id'] ) ) : 0;

		$data = array(
			'title'                => isset( $_POST['title'] )                ? sanitize_text_field( wp_unslash( $_POST['title'] ) )                : '',
			'domain_id'            => isset( $_POST['domain_id'] )            ? absint( wp_unslash( $_POST['domain_id'] ) )                         : 0,
			'subdomain_id'         => isset( $_POST['subdomain_id'] )         ? absint( wp_unslash( $_POST['subdomain_id'] ) )                      : 0,
			'prediction_type'      => isset( $_POST['prediction_type'] )      ? sanitize_text_field( wp_unslash( $_POST['prediction_type'] ) )      : '',
			'prediction_statement' => isset( $_POST['prediction_statement'] ) ? sanitize_textarea_field( wp_unslash( $_POST['prediction_statement'] ) ) : '',
			'rationale'            => isset( $_POST['rationale'] )            ? sanitize_textarea_field( wp_unslash( $_POST['rationale'] ) )        : '',
			'confidence_score'     => isset( $_POST['confidence_score'] )     ? absint( wp_unslash( $_POST['confidence_score'] ) )                  : 50,
			'prediction_date'      => isset( $_POST['prediction_date'] )      ? sanitize_text_field( wp_unslash( $_POST['prediction_date'] ) )      : '',
			'target_date'          => isset( $_POST['target_date'] )          ? sanitize_text_field( wp_unslash( $_POST['target_date'] ) )          : '',
			'status'               => isset( $_POST['status'] )               ? sanitize_key( wp_unslash( $_POST['status'] ) )                      : 'active',
			'outcome_notes'        => isset( $_POST['outcome_notes'] )        ? sanitize_textarea_field( wp_unslash( $_POST['outcome_notes'] ) )    : '',
		'source_url'           => isset( $_POST['source_url'] )           ? esc_url_raw( wp_unslash( $_POST['source_url'] ) )                    : '',
		'visibility'           => isset( $_POST['visibility'] )           ? sanitize_key( wp_unslash( $_POST['visibility'] ) )                   : 'public',
		);

		if ( $pred_id > 0 ) {
			EWO_Predictions_DB::update( $pred_id, $data );
			$msg = 'updated';
		} else {
			$pred_id = EWO_Predictions_DB::insert( $data );
			$msg     = 'saved';
		}

		wp_safe_redirect( add_query_arg( array( 'page' => self::MENU_SLUG, 'action' => 'view', 'id' => $pred_id, 'msg' => $msg ), admin_url( 'admin.php' ) ) );
		exit;
	}

	public function handle_delete() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'Not allowed.', 'ewo-predictions' ) );
		}
		$id = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;
		check_admin_referer( self::NONCE_DELETE . '_' . $id );

		EWO_Predictions_DB::delete( $id );
		wp_safe_redirect( add_query_arg( array( 'page' => self::MENU_SLUG, 'msg' => 'deleted' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	public function ajax_subdomains() {
		check_ajax_referer( 'ewo_pred_ajax', 'nonce' );
		if ( ! current_user_can( self::CAP ) ) {
			wp_send_json_error( null, 403 );
		}
		$domain_id  = absint( $_POST['domain_id'] ?? 0 );
		$subdomains = $this->get_subdomains( $domain_id );
		$out = array();
		foreach ( $subdomains as $s ) {
			$out[] = array( 'id' => (int) $s->id, 'name' => $s->name );
		}
		wp_send_json_success( $out );
	}

	public function render_notice() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$msg = isset( $_GET['msg'] ) ? sanitize_key( wp_unslash( $_GET['msg'] ) ) : '';
		// phpcs:enable
		if ( ! $msg || false === strpos( $_SERVER['REQUEST_URI'] ?? '', 'page=ewo-predictions' ) ) { // phpcs:ignore WordPress.Security
			return;
		}
		$map = array(
			'saved'   => __( 'Prediction saved.', 'ewo-predictions' ),
			'updated' => __( 'Prediction updated.', 'ewo-predictions' ),
			'deleted' => __( 'Prediction deleted.', 'ewo-predictions' ),
		);
		if ( isset( $map[ $msg ] ) ) {
			printf( '<div class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html( $map[ $msg ] ) );
		}
	}

	/* -------------------------------------------------------------------------
	   Helpers
	   ---------------------------------------------------------------------- */

	protected function get_domains() {
		if ( class_exists( 'EWO_RSS_Taxonomy' ) ) {
			return EWO_RSS_Taxonomy::get_domains();
		}
		global $wpdb;
		$t = $wpdb->prefix . 'ewo_rss_domains';
		return (array) $wpdb->get_results( "SELECT id, name FROM $t ORDER BY name ASC" ); // phpcs:ignore WordPress.DB
	}

	protected function get_subdomains( $domain_id = 0 ) {
		if ( class_exists( 'EWO_RSS_Taxonomy' ) ) {
			return EWO_RSS_Taxonomy::get_subdomains( $domain_id );
		}
		global $wpdb;
		$t = $wpdb->prefix . 'ewo_rss_subdomains';
		if ( $domain_id > 0 ) {
			return (array) $wpdb->get_results( $wpdb->prepare( "SELECT id, domain_id, name FROM $t WHERE domain_id = %d ORDER BY name ASC", $domain_id ) ); // phpcs:ignore WordPress.DB
		}
		return (array) $wpdb->get_results( "SELECT id, domain_id, name FROM $t ORDER BY name ASC" ); // phpcs:ignore WordPress.DB
	}

	protected function confidence_range( $key ) {
		switch ( $key ) {
			case 'high':   return array( 80, 100 );
			case 'medium': return array( 60, 79 );
			case 'low':    return array( 0, 59 );
			default:       return array( '', '' );
		}
	}

	protected function confidence_badge( $score ) {
		if ( $score >= 80 ) {
			$cls = 'ewo-pred-badge--conf-high';
		} elseif ( $score >= 60 ) {
			$cls = 'ewo-pred-badge--conf-mid';
		} else {
			$cls = 'ewo-pred-badge--conf-low';
		}
		return '<span class="ewo-pred-badge ewo-pred-badge--confidence ' . esc_attr( $cls ) . '">' . esc_html( $score . '%' ) . '</span>';
	}

	protected function status_badge( $status ) {
		$map = array(
			'active'   => 'ewo-pred-badge--active',
			'tracking' => 'ewo-pred-badge--tracking',
			'hit'      => 'ewo-pred-badge--hit',
			'missed'   => 'ewo-pred-badge--missed',
			'partial'  => 'ewo-pred-badge--partial',
			'archived' => 'ewo-pred-badge--archived',
		);
		$cls = isset( $map[ $status ] ) ? $map[ $status ] : 'ewo-pred-badge--archived';
		return '<span class="ewo-pred-badge ' . esc_attr( $cls ) . '">' . esc_html( ucfirst( $status ) ) . '</span>';
	}

	protected function sort_link( $col, $label, $cur_by, $cur_ord, $base_args ) {
		$is_active  = ( $col === $cur_by );
		$next_order = ( $is_active && 'ASC' === $cur_ord ) ? 'desc' : 'asc';
		$arrow      = $is_active ? ( 'ASC' === $cur_ord ? ' ▲' : ' ▼' ) : '';
		$url = add_query_arg(
			array_merge( $base_args, array( 'page' => self::MENU_SLUG, 'orderby' => $col, 'order' => $next_order ) ),
			admin_url( 'admin.php' )
		);
		printf(
			'<a href="%s" class="ewo-pred-sort%s">%s%s</a>',
			esc_url( $url ),
			$is_active ? ' ewo-pred-sort--active' : '',
			esc_html( $label ),
			esc_html( $arrow )
		);
	}

	protected function render_pagination( $paged, $pages, $total, $filter_args ) {
		$block_size  = 10;
		$block_start = (int) ( floor( ( $paged - 1 ) / $block_size ) * $block_size ) + 1;
		$block_end   = min( $block_start + $block_size - 1, $pages );
		$base        = admin_url( 'admin.php' );
		?>
		<div class="ewo-pred-pagination">
			<span class="ewo-pred-pag-info">
				<?php
				$first = ( $paged - 1 ) * self::PER_PAGE + 1;
				$last  = min( $paged * self::PER_PAGE, $total );
				printf(
					/* translators: 1: first, 2: last, 3: total */
					esc_html__( 'Showing %1$d to %2$d of %3$d predictions', 'ewo-predictions' ),
					(int) $first, (int) $last, (int) $total
				);
				?>
			</span>
			<nav class="ewo-pred-pag-nav">
				<?php if ( $paged > 1 ) : ?>
					<a href="<?php echo esc_url( add_query_arg( array_merge( $filter_args, array( 'page' => self::MENU_SLUG, 'paged' => $paged - 1 ) ), $base ) ); ?>"
					   class="ewo-pred-pag-btn"><?php esc_html_e( 'Previous', 'ewo-predictions' ); ?></a>
				<?php else : ?>
					<span class="ewo-pred-pag-btn ewo-pred-pag-btn--disabled"><?php esc_html_e( 'Previous', 'ewo-predictions' ); ?></span>
				<?php endif; ?>

				<?php if ( $block_start > 1 ) : ?>
					<span class="ewo-pred-pag-ellipsis">&hellip;</span>
				<?php endif; ?>

				<?php for ( $i = $block_start; $i <= $block_end; $i++ ) : ?>
					<?php if ( $i === $paged ) : ?>
						<span class="ewo-pred-pag-btn ewo-pred-pag-btn--current"><?php echo esc_html( (string) $i ); ?></span>
					<?php else : ?>
						<a href="<?php echo esc_url( add_query_arg( array_merge( $filter_args, array( 'page' => self::MENU_SLUG, 'paged' => $i ) ), $base ) ); ?>"
						   class="ewo-pred-pag-btn"><?php echo esc_html( (string) $i ); ?></a>
					<?php endif; ?>
				<?php endfor; ?>

				<?php if ( $block_end < $pages ) : ?>
					<span class="ewo-pred-pag-ellipsis">&hellip;</span>
				<?php endif; ?>

				<?php if ( $paged < $pages ) : ?>
					<a href="<?php echo esc_url( add_query_arg( array_merge( $filter_args, array( 'page' => self::MENU_SLUG, 'paged' => $paged + 1 ) ), $base ) ); ?>"
					   class="ewo-pred-pag-btn"><?php esc_html_e( 'Next', 'ewo-predictions' ); ?></a>
				<?php else : ?>
					<span class="ewo-pred-pag-btn ewo-pred-pag-btn--disabled"><?php esc_html_e( 'Next', 'ewo-predictions' ); ?></span>
				<?php endif; ?>
			</nav>
		</div>
		<?php
	}
}
