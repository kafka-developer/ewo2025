<?php
/**
 * Template for the public Predictions page.
 *
 * /predictions/        → list with server-side filters, stats, table
 * /predictions/{id}/  → full detail view
 *
 * @package EWO_2025
 */

get_header();

if ( ! class_exists( 'EWO_Predictions_DB' ) ) {
	echo '<main id="primary" class="site-main ewo-pf-main"><div class="ewo-pf-wrap"><p class="ewo-pf-empty">Predictions not available.</p></div></main>';
	get_footer();
	return;
}

$ewo_pf_id = (int) get_query_var( 'ewo_prediction_id', 0 );

/* =========================================================================
   HELPERS
   ======================================================================= */

$ewo_pf_status_color = array(
	'active'   => '#22c55e',
	'tracking' => '#3b82f6',
	'hit'      => '#4ade80',
	'missed'   => '#ef4444',
	'partial'  => '#f59e0b',
	'archived' => '#6b7280',
);
$ewo_pf_status_label = array(
	'active'   => 'Active',
	'tracking' => 'Tracking',
	'hit'      => 'Hit',
	'missed'   => 'Missed',
	'partial'  => 'Partial',
	'archived' => 'Archived',
);
$ewo_pf_conf_color = function( $c ) {
	if ( $c >= 80 ) return '#22c55e';
	if ( $c >= 60 ) return '#f59e0b';
	return '#ef4444';
};
$ewo_pf_conf_bg = function( $c ) {
	if ( $c >= 80 ) return 'rgba(34,197,94,.14)';
	if ( $c >= 60 ) return 'rgba(245,158,11,.14)';
	return 'rgba(239,68,68,.14)';
};

if ( $ewo_pf_id > 0 ) :
/* =========================================================================
   DETAIL VIEW
   ======================================================================= */
	$ewo_pred = EWO_Predictions_DB::get( $ewo_pf_id );
	$ewo_pf_back = home_url( '/predictions/' );
?>
<main id="primary" class="site-main ewo-pf-main">
<div class="ewo-pf-wrap">

	<a href="<?php echo esc_url( $ewo_pf_back ); ?>" class="ewo-pf-back">&larr; All Predictions</a>

	<?php if ( ! $ewo_pred || 'private' === ( $ewo_pred->visibility ?? 'public' ) ) : ?>
		<p class="ewo-pf-empty">Prediction not found.</p>
	<?php else :
		$ewo_pf_sc = $ewo_pf_status_color[ $ewo_pred->status ] ?? '#6b7280';
		$ewo_pf_sl = $ewo_pf_status_label[ $ewo_pred->status ] ?? ucfirst( $ewo_pred->status );
		$ewo_pf_cc = $ewo_pf_conf_color( (int) $ewo_pred->confidence_score );
		$ewo_pf_cb = $ewo_pf_conf_bg( (int) $ewo_pred->confidence_score );
	?>
	<div class="ewo-pf-detail-header">
		<div class="ewo-pf-detail-crumb">
			<span class="ewo-pf-kicker">Strategic Predictions</span>
			<?php if ( $ewo_pred->domain_name ) : ?>
				<span class="ewo-pf-crumb-sep">·</span>
				<span class="ewo-pf-crumb-domain"><?php echo esc_html( $ewo_pred->domain_name ); ?></span>
			<?php endif; ?>
			<?php if ( $ewo_pred->subdomain_name ) : ?>
				<span class="ewo-pf-crumb-sep">›</span>
				<span class="ewo-pf-crumb-sub"><?php echo esc_html( $ewo_pred->subdomain_name ); ?></span>
			<?php endif; ?>
		</div>
		<h1 class="ewo-pf-detail-title"><?php echo esc_html( $ewo_pred->title ); ?></h1>
		<?php $ewo_pf_sc_bg = 'rgba(255,255,255,.07)'; ?>
		<div class="ewo-pf-detail-pills">
			<span class="ewo-pf-pill" style="color:<?php echo esc_attr( $ewo_pf_sc ); ?>;background:<?php echo esc_attr( $ewo_pf_sc_bg ); ?>"><?php echo esc_html( $ewo_pf_sl ); ?></span>
			<span class="ewo-pf-pill" style="color:<?php echo esc_attr( $ewo_pf_cc ); ?>;background:<?php echo esc_attr( $ewo_pf_cb ); ?>"><?php echo esc_html( $ewo_pred->confidence_score . '% confidence' ); ?></span>
			<?php if ( $ewo_pred->prediction_type ) : ?>
				<span class="ewo-pf-pill ewo-pf-pill--type"><?php echo esc_html( $ewo_pred->prediction_type ); ?></span>
			<?php endif; ?>
		</div>
	</div>

	<div class="ewo-pf-detail-body">
		<div class="ewo-pf-detail-main">
			<div class="ewo-pf-card">
				<p class="ewo-pf-card-label">Prediction</p>
				<p class="ewo-pf-detail-statement"><?php echo esc_html( $ewo_pred->prediction_statement ); ?></p>
			</div>
			<?php if ( ! empty( $ewo_pred->rationale ) ) : ?>
			<div class="ewo-pf-card" style="margin-top:12px;">
				<p class="ewo-pf-card-label">Full Analysis</p>
				<p class="ewo-pf-detail-text"><?php echo nl2br( esc_html( $ewo_pred->rationale ) ); ?></p>
			</div>
			<?php endif; ?>
			<?php if ( ! empty( $ewo_pred->outcome_notes ) ) : ?>
			<div class="ewo-pf-card" style="margin-top:12px;">
				<p class="ewo-pf-card-label">Outcome Notes</p>
				<p class="ewo-pf-detail-text"><?php echo esc_html( $ewo_pred->outcome_notes ); ?></p>
			</div>
			<?php endif; ?>
		</div>
		<aside class="ewo-pf-detail-side">
			<div class="ewo-pf-card">
				<p class="ewo-pf-card-label">Details</p>
				<dl class="ewo-pf-detail-dl">
					<dt>Status</dt>
					<dd style="color:<?php echo esc_attr( $ewo_pf_sc ); ?>;"><?php echo esc_html( $ewo_pf_sl ); ?></dd>
					<dt>Confidence</dt>
					<dd style="color:<?php echo esc_attr( $ewo_pf_cc ); ?>;"><?php echo esc_html( $ewo_pred->confidence_score . '%' ); ?></dd>
					<?php if ( $ewo_pred->domain_name ) : ?><dt>Domain</dt><dd><?php echo esc_html( $ewo_pred->domain_name ); ?></dd><?php endif; ?>
					<?php if ( $ewo_pred->subdomain_name ) : ?><dt>Subdomain</dt><dd><?php echo esc_html( $ewo_pred->subdomain_name ); ?></dd><?php endif; ?>
					<?php if ( $ewo_pred->prediction_type ) : ?><dt>Type</dt><dd><?php echo esc_html( $ewo_pred->prediction_type ); ?></dd><?php endif; ?>
					<?php if ( $ewo_pred->prediction_date ) : ?><dt>Made On</dt><dd><?php echo esc_html( wp_date( 'M j, Y', strtotime( $ewo_pred->prediction_date ) ) ); ?></dd><?php endif; ?>
					<?php if ( $ewo_pred->target_date ) : ?><dt>Target</dt><dd><?php echo esc_html( wp_date( 'M j, Y', strtotime( $ewo_pred->target_date ) ) ); ?></dd><?php endif; ?>
					<?php if ( ! empty( $ewo_pred->source_url ) ) : ?>
					<dt>Source</dt>
					<dd><a href="<?php echo esc_url( $ewo_pred->source_url ); ?>" target="_blank" rel="noopener noreferrer" class="ewo-pf-source-link">View Source ↗</a></dd>
					<?php endif; ?>
				</dl>
			</div>
		</aside>
	</div>

	<?php endif; ?>

</div>
</main>

<?php else :
/* =========================================================================
   LIST VIEW
   ======================================================================= */

// phpcs:disable WordPress.Security.NonceVerification.Recommended
$ewo_pf_domain_id  = isset( $_GET['domain_id'] )   ? absint( wp_unslash( $_GET['domain_id'] ) )                  : 0;
$ewo_pf_sub_id     = isset( $_GET['subdomain_id'] ) ? absint( wp_unslash( $_GET['subdomain_id'] ) )               : 0;
$ewo_pf_type       = isset( $_GET['pred_type'] )    ? sanitize_text_field( wp_unslash( $_GET['pred_type'] ) )     : '';
$ewo_pf_status     = isset( $_GET['status'] )       ? sanitize_key( wp_unslash( $_GET['status'] ) )               : '';
$ewo_pf_confidence = isset( $_GET['confidence'] )   ? sanitize_key( wp_unslash( $_GET['confidence'] ) )           : '';
$ewo_pf_paged      = isset( $_GET['paged'] )        ? max( 1, absint( wp_unslash( $_GET['paged'] ) ) )            : 1;
// phpcs:enable

$ewo_pf_per_page = 12;
$ewo_pf_conf_range = array( '', '' );
if ( $ewo_pf_confidence === 'high' )   $ewo_pf_conf_range = array( 80, 100 );
if ( $ewo_pf_confidence === 'medium' ) $ewo_pf_conf_range = array( 60, 79 );
if ( $ewo_pf_confidence === 'low' )    $ewo_pf_conf_range = array( 0, 59 );

$ewo_pf_args = array(
	'domain_id'      => $ewo_pf_domain_id,
	'subdomain_id'   => $ewo_pf_sub_id,
	'prediction_type'=> $ewo_pf_type,
	'status'         => $ewo_pf_status,
	'confidence_min' => $ewo_pf_conf_range[0],
	'confidence_max' => $ewo_pf_conf_range[1],
	'visibility'     => 'public',
	'orderby'        => 'id',
	'order'          => 'DESC',
	'limit'          => $ewo_pf_per_page,
	'offset'         => ( $ewo_pf_paged - 1 ) * $ewo_pf_per_page,
);

$ewo_pf_rows   = EWO_Predictions_DB::query( $ewo_pf_args );
$ewo_pf_total  = EWO_Predictions_DB::count( $ewo_pf_args );
$ewo_pf_pages  = (int) ceil( $ewo_pf_total / $ewo_pf_per_page );
$ewo_pf_stats  = EWO_Predictions_DB::metrics();
$ewo_pf_types  = EWO_Predictions_DB::get_types();

// Taxonomy helpers
$ewo_pf_domains    = class_exists( 'EWO_RSS_Taxonomy' ) ? EWO_RSS_Taxonomy::get_domains() : array();
$ewo_pf_subdomains = class_exists( 'EWO_RSS_Taxonomy' ) ? EWO_RSS_Taxonomy::get_subdomains() : array();

$ewo_pf_base = home_url( '/predictions/' );
$ewo_pf_filter_state = array_filter( array(
	'domain_id'   => $ewo_pf_domain_id   ?: null,
	'subdomain_id'=> $ewo_pf_sub_id      ?: null,
	'pred_type'   => $ewo_pf_type        ?: null,
	'status'      => $ewo_pf_status      ?: null,
	'confidence'  => $ewo_pf_confidence  ?: null,
) );
?>
<main id="primary" class="site-main ewo-pf-main">

	<div class="ewo-pf-page-header">
		<div class="ewo-pf-wrap">
			<div class="ewo-pf-header-row">
				<div>
					<p class="ewo-pf-kicker">Forecasts</p>
					<h1 class="ewo-pf-page-title">Predictions</h1>
					<p class="ewo-pf-page-sub">Track EWO geopolitical, economic, energy, and strategic predictions.</p>
				</div>
				<?php if ( current_user_can( 'manage_options' ) ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ewo-predictions-add' ) ); ?>"
				   class="ewo-pf-add-btn">+ Add Prediction</a>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<div class="ewo-pf-wrap ewo-pf-body">

		<!-- Filter bar -->
		<form method="get" action="<?php echo esc_url( $ewo_pf_base ); ?>" class="ewo-pf-filters">
			<div class="ewo-pf-filter-grid">

				<div class="ewo-pf-filter-field">
					<label>Domain</label>
					<select name="domain_id" id="ewo-pf-domain">
						<option value="0">All Domains</option>
						<?php foreach ( $ewo_pf_domains as $ewo_d ) : ?>
							<option value="<?php echo esc_attr( (string) $ewo_d->id ); ?>" <?php selected( $ewo_pf_domain_id, (int) $ewo_d->id ); ?>><?php echo esc_html( $ewo_d->name ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="ewo-pf-filter-field">
					<label>Subdomain</label>
					<select name="subdomain_id" id="ewo-pf-subdomain">
						<option value="0">All Subdomains</option>
						<?php foreach ( $ewo_pf_subdomains as $ewo_s ) : ?>
							<option value="<?php echo esc_attr( (string) $ewo_s->id ); ?>"
							        data-domain="<?php echo esc_attr( (string) $ewo_s->domain_id ); ?>"
							        <?php selected( $ewo_pf_sub_id, (int) $ewo_s->id ); ?>>
								<?php echo esc_html( $ewo_s->name ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="ewo-pf-filter-field">
					<label>Type</label>
					<select name="pred_type">
						<option value="">All Types</option>
						<?php foreach ( $ewo_pf_types as $ewo_t ) : ?>
							<option value="<?php echo esc_attr( $ewo_t ); ?>" <?php selected( $ewo_pf_type, $ewo_t ); ?>><?php echo esc_html( $ewo_t ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="ewo-pf-filter-field">
					<label>Status</label>
					<select name="status">
						<option value="">All Statuses</option>
						<?php foreach ( EWO_Predictions_DB::statuses() as $ewo_st ) : ?>
							<option value="<?php echo esc_attr( $ewo_st ); ?>" <?php selected( $ewo_pf_status, $ewo_st ); ?>><?php echo esc_html( ucfirst( $ewo_st ) ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="ewo-pf-filter-field">
					<label>Confidence</label>
					<select name="confidence">
						<option value="">All</option>
						<option value="high"   <?php selected( $ewo_pf_confidence, 'high' ); ?>>High (80–100%)</option>
						<option value="medium" <?php selected( $ewo_pf_confidence, 'medium' ); ?>>Medium (60–79%)</option>
						<option value="low"    <?php selected( $ewo_pf_confidence, 'low' ); ?>>Low (&lt;60%)</option>
					</select>
				</div>

				<div class="ewo-pf-filter-actions">
					<button type="submit" class="ewo-pf-btn ewo-pf-btn--primary">Apply</button>
					<a href="<?php echo esc_url( $ewo_pf_base ); ?>" class="ewo-pf-btn ewo-pf-btn--ghost">Reset</a>
				</div>

			</div>
		</form>

		<!-- Stats cards -->
		<div class="ewo-pf-stats">
			<?php
			$ewo_pf_stat_cards = array(
				array( 'label' => 'Total Predictions', 'value' => $ewo_pf_stats['total'],    'icon' => '◈', 'color' => '#dde8f5' ),
				array( 'label' => 'Active',            'value' => $ewo_pf_stats['active'],   'icon' => '●', 'color' => '#22c55e' ),
				array( 'label' => 'Hit',               'value' => $ewo_pf_stats['hit'],      'icon' => '✓', 'color' => '#4ade80' ),
				array( 'label' => 'Missed',            'value' => $ewo_pf_stats['missed'],   'icon' => '✗', 'color' => '#ef4444' ),
				array( 'label' => 'Avg Confidence',    'value' => $ewo_pf_stats['avg_conf'] . '%', 'icon' => '◎', 'color' => '#d7a84b' ),
			);
			foreach ( $ewo_pf_stat_cards as $ewo_pf_sc ) :
			?>
			<div class="ewo-pf-stat-card">
				<div class="ewo-pf-stat-body">
					<span class="ewo-pf-stat-label"><?php echo esc_html( $ewo_pf_sc['label'] ); ?></span>
					<span class="ewo-pf-stat-value"><?php echo esc_html( (string) $ewo_pf_sc['value'] ); ?></span>
				</div>
				<span class="ewo-pf-stat-icon" style="color:<?php echo esc_attr( $ewo_pf_sc['color'] ); ?>;"><?php echo esc_html( $ewo_pf_sc['icon'] ); ?></span>
			</div>
			<?php endforeach; ?>
		</div>

		<!-- Predictions table/cards -->
		<div class="ewo-pf-table-wrap">
			<div class="ewo-pf-table-header">
				<h2 class="ewo-pf-table-title">All Predictions</h2>
				<?php if ( $ewo_pf_total > 0 ) : ?>
				<span class="ewo-pf-table-count">
					<?php
					$ewo_pf_first = ( $ewo_pf_paged - 1 ) * $ewo_pf_per_page + 1;
					$ewo_pf_last  = min( $ewo_pf_paged * $ewo_pf_per_page, $ewo_pf_total );
					echo esc_html( "Showing {$ewo_pf_first}–{$ewo_pf_last} of {$ewo_pf_total}" );
					?>
				</span>
				<?php endif; ?>
			</div>

			<?php if ( empty( $ewo_pf_rows ) ) : ?>
				<p class="ewo-pf-empty">No predictions match your filters.</p>
			<?php else : ?>

			<!-- Desktop table -->
			<div class="ewo-pf-table-scroll">
				<table class="ewo-pf-table">
					<thead>
						<tr>
							<th>Title</th>
							<th>Domain</th>
							<th>Type</th>
							<th>Confidence</th>
							<th>Pred. Date</th>
							<th>Target Date</th>
							<th>Status</th>
							<th>Action</th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ( $ewo_pf_rows as $ewo_pf_row ) :
						$ewo_pf_detail = home_url( '/predictions/' . (int) $ewo_pf_row->id . '/' );
						$ewo_pf_rc     = (int) $ewo_pf_row->confidence_score;
						$ewo_pf_rsc    = $ewo_pf_status_color[ $ewo_pf_row->status ] ?? '#6b7280';
						$ewo_pf_rsl    = $ewo_pf_status_label[ $ewo_pf_row->status ] ?? ucfirst( $ewo_pf_row->status );
					?>
					<tr>
						<td class="ewo-pf-td-title">
							<a href="<?php echo esc_url( $ewo_pf_detail ); ?>" class="ewo-pf-row-link">
								<?php echo esc_html( wp_trim_words( $ewo_pf_row->title, 9 ) ); ?>
							</a>
							<?php if ( ! empty( $ewo_pf_row->prediction_statement ) ) : ?>
								<p class="ewo-pf-row-summary"><?php echo esc_html( wp_trim_words( $ewo_pf_row->prediction_statement, 14 ) ); ?></p>
							<?php endif; ?>
						</td>
						<td class="ewo-pf-td-meta">
							<?php echo esc_html( $ewo_pf_row->domain_name ?: '—' ); ?>
							<?php if ( $ewo_pf_row->subdomain_name ) : ?><br><span class="ewo-pf-sub-label"><?php echo esc_html( $ewo_pf_row->subdomain_name ); ?></span><?php endif; ?>
						</td>
						<td class="ewo-pf-td-meta"><?php echo esc_html( $ewo_pf_row->prediction_type ?: '—' ); ?></td>
						<td>
							<span class="ewo-pf-conf-badge"
							      style="color:<?php echo esc_attr( $ewo_pf_conf_color( $ewo_pf_rc ) ); ?>;background:<?php echo esc_attr( $ewo_pf_conf_bg( $ewo_pf_rc ) ); ?>;">
								<?php echo esc_html( $ewo_pf_rc . '%' ); ?>
							</span>
						</td>
						<td class="ewo-pf-td-date"><?php echo esc_html( $ewo_pf_row->prediction_date ? wp_date( 'M j, Y', strtotime( $ewo_pf_row->prediction_date ) ) : '—' ); ?></td>
						<td class="ewo-pf-td-date"><?php echo esc_html( $ewo_pf_row->target_date ? wp_date( 'M j, Y', strtotime( $ewo_pf_row->target_date ) ) : '—' ); ?></td>
						<td>
							<span class="ewo-pf-status-badge"
							      style="color:<?php echo esc_attr( $ewo_pf_rsc ); ?>;background:<?php echo esc_attr( str_replace( '#', '', $ewo_pf_rsc ) === $ewo_pf_rsc ? $ewo_pf_rsc : 'rgba(0,0,0,0)' ); ?>">
								<?php echo esc_html( $ewo_pf_rsl ); ?>
							</span>
						</td>
						<td>
							<a href="<?php echo esc_url( $ewo_pf_detail ); ?>" class="ewo-pf-view-btn">View →</a>
						</td>
					</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			</div>

			<!-- Mobile cards -->
			<div class="ewo-pf-cards">
				<?php foreach ( $ewo_pf_rows as $ewo_pf_row ) :
					$ewo_pf_detail = home_url( '/predictions/' . (int) $ewo_pf_row->id . '/' );
					$ewo_pf_rc     = (int) $ewo_pf_row->confidence_score;
					$ewo_pf_rsc    = $ewo_pf_status_color[ $ewo_pf_row->status ] ?? '#6b7280';
					$ewo_pf_rsl    = $ewo_pf_status_label[ $ewo_pf_row->status ] ?? ucfirst( $ewo_pf_row->status );
				?>
				<div class="ewo-pf-card ewo-pf-pred-card">
					<div class="ewo-pf-pred-card-top">
						<span class="ewo-pf-conf-badge"
						      style="color:<?php echo esc_attr( $ewo_pf_conf_color( $ewo_pf_rc ) ); ?>;background:<?php echo esc_attr( $ewo_pf_conf_bg( $ewo_pf_rc ) ); ?>;">
							<?php echo esc_html( $ewo_pf_rc . '%' ); ?>
						</span>
						<span class="ewo-pf-status-badge" style="color:<?php echo esc_attr( $ewo_pf_rsc ); ?>;"><?php echo esc_html( $ewo_pf_rsl ); ?></span>
					</div>
					<h3 class="ewo-pf-pred-card-title">
						<a href="<?php echo esc_url( $ewo_pf_detail ); ?>"><?php echo esc_html( $ewo_pf_row->title ); ?></a>
					</h3>
					<?php if ( $ewo_pf_row->prediction_statement ) : ?>
						<p class="ewo-pf-pred-card-summary"><?php echo esc_html( wp_trim_words( $ewo_pf_row->prediction_statement, 20 ) ); ?></p>
					<?php endif; ?>
					<div class="ewo-pf-pred-card-meta">
						<?php if ( $ewo_pf_row->domain_name ) : ?><span><?php echo esc_html( $ewo_pf_row->domain_name ); ?></span><?php endif; ?>
						<?php if ( $ewo_pf_row->prediction_type ) : ?><span class="ewo-pf-dot">·</span><span><?php echo esc_html( $ewo_pf_row->prediction_type ); ?></span><?php endif; ?>
						<?php if ( $ewo_pf_row->target_date ) : ?><span class="ewo-pf-dot">·</span><span><?php echo esc_html( wp_date( 'M Y', strtotime( $ewo_pf_row->target_date ) ) ); ?></span><?php endif; ?>
					</div>
					<a href="<?php echo esc_url( $ewo_pf_detail ); ?>" class="ewo-pf-view-btn">View Details →</a>
				</div>
				<?php endforeach; ?>
			</div>

			<?php endif; ?>

			<!-- Pagination -->
			<?php if ( $ewo_pf_pages > 1 ) :
				$ewo_pf_bs  = 10;
				$ewo_pf_bst = (int)( floor( ( $ewo_pf_paged - 1 ) / $ewo_pf_bs ) * $ewo_pf_bs ) + 1;
				$ewo_pf_ben = min( $ewo_pf_bst + $ewo_pf_bs - 1, $ewo_pf_pages );
			?>
			<div class="ewo-pf-pagination">
				<nav class="ewo-pf-pag-nav">
					<?php if ( $ewo_pf_paged > 1 ) : ?>
						<a href="<?php echo esc_url( add_query_arg( array_merge( $ewo_pf_filter_state, array( 'paged' => $ewo_pf_paged - 1 ) ), $ewo_pf_base ) ); ?>" class="ewo-pf-pag-btn">← Prev</a>
					<?php else : ?>
						<span class="ewo-pf-pag-btn ewo-pf-pag-btn--off">← Prev</span>
					<?php endif; ?>

					<?php if ( $ewo_pf_bst > 1 ) : ?><span class="ewo-pf-pag-ellipsis">…</span><?php endif; ?>

					<?php for ( $i = $ewo_pf_bst; $i <= $ewo_pf_ben; $i++ ) : ?>
						<?php if ( $i === $ewo_pf_paged ) : ?>
							<span class="ewo-pf-pag-btn ewo-pf-pag-btn--cur"><?php echo esc_html( (string) $i ); ?></span>
						<?php else : ?>
							<a href="<?php echo esc_url( add_query_arg( array_merge( $ewo_pf_filter_state, array( 'paged' => $i ) ), $ewo_pf_base ) ); ?>" class="ewo-pf-pag-btn"><?php echo esc_html( (string) $i ); ?></a>
						<?php endif; ?>
					<?php endfor; ?>

					<?php if ( $ewo_pf_ben < $ewo_pf_pages ) : ?><span class="ewo-pf-pag-ellipsis">…</span><?php endif; ?>

					<?php if ( $ewo_pf_paged < $ewo_pf_pages ) : ?>
						<a href="<?php echo esc_url( add_query_arg( array_merge( $ewo_pf_filter_state, array( 'paged' => $ewo_pf_paged + 1 ) ), $ewo_pf_base ) ); ?>" class="ewo-pf-pag-btn">Next →</a>
					<?php else : ?>
						<span class="ewo-pf-pag-btn ewo-pf-pag-btn--off">Next →</span>
					<?php endif; ?>
				</nav>
			</div>
			<?php endif; ?>

		</div><!-- .ewo-pf-table-wrap -->

		<!-- Subdomain cascade script -->
		<script>
		(function(){
			var d = document.getElementById('ewo-pf-domain');
			var s = document.getElementById('ewo-pf-subdomain');
			if (!d || !s) return;
			function cascade() {
				var dv = d.value;
				Array.prototype.forEach.call(s.options, function(o, i) {
					if (i === 0) return;
					var show = !dv || dv === '0' || o.getAttribute('data-domain') === dv;
					o.hidden = !show; o.disabled = !show;
				});
				if (s.selectedIndex > 0 && s.options[s.selectedIndex] && s.options[s.selectedIndex].hidden) s.value = '0';
			}
			d.addEventListener('change', cascade);
			cascade();
		})();
		</script>

	</div><!-- .ewo-pf-body -->
</main>
<?php endif;

get_footer();
