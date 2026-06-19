<?php
/**
 * Admin dashboards: feed health, duplicate management, importer attribution.
 *
 * @package EWO_RSS_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Engine admin dashboards.
 */
class EWO_RSS_Admin_Tools {
	const PARENT     = 'ewo-rss-engine';
	const CAP        = 'manage_options';
	const ACTION     = 'ewo_rss_tool';
	const NONCE      = 'ewo_rss_tool_nonce';

	/**
	 * Register hooks.
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'menu' ), 20 );
		add_action( 'admin_post_' . self::ACTION, array( $this, 'handle' ) );
	}

	/**
	 * Register submenu pages.
	 */
	public function menu() {
		add_submenu_page( self::PARENT, __( 'Feed Health', 'ewo-rss-engine' ), __( 'Feed Health', 'ewo-rss-engine' ), self::CAP, 'ewo-rss-health', array( $this, 'render_health' ) );
		add_submenu_page( self::PARENT, __( 'Duplicates', 'ewo-rss-engine' ), __( 'Duplicates', 'ewo-rss-engine' ), self::CAP, 'ewo-rss-duplicates', array( $this, 'render_duplicates' ) );
		add_submenu_page( self::PARENT, __( 'Importer Attribution', 'ewo-rss-engine' ), __( 'Attribution', 'ewo-rss-engine' ), self::CAP, 'ewo-rss-attribution', array( $this, 'render_attribution' ) );
	}

	/* ----- Feed Health Dashboard (items 5) ----- */

	/**
	 * Render the feed-health dashboard.
	 */
	public function render_health() {
		if ( ! current_user_can( self::CAP ) ) {
			return;
		}
		$feeds = EWO_RSS_Feed::all( true );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Feed Health', 'ewo-rss-engine' ); ?></h1>
			<table class="widefat striped">
				<thead><tr>
					<th><?php esc_html_e( 'Feed', 'ewo-rss-engine' ); ?></th>
					<th><?php esc_html_e( 'Importer', 'ewo-rss-engine' ); ?></th>
					<th><?php esc_html_e( 'Status', 'ewo-rss-engine' ); ?></th>
					<th><?php esc_html_e( 'Health', 'ewo-rss-engine' ); ?></th>
					<th><?php esc_html_e( 'Last success', 'ewo-rss-engine' ); ?></th>
					<th><?php esc_html_e( 'Last failure', 'ewo-rss-engine' ); ?></th>
					<th><?php esc_html_e( 'Consec. fails', 'ewo-rss-engine' ); ?></th>
					<th><?php esc_html_e( 'Errors', 'ewo-rss-engine' ); ?></th>
					<th><?php esc_html_e( 'Resp (ms)', 'ewo-rss-engine' ); ?></th>
					<th><?php esc_html_e( 'Imported', 'ewo-rss-engine' ); ?></th>
				</tr></thead>
				<tbody>
				<?php if ( empty( $feeds ) ) : ?>
					<tr><td colspan="10"><?php esc_html_e( 'No feeds configured.', 'ewo-rss-engine' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $feeds as $feed_id ) : $m = EWO_RSS_Feed::metrics( $feed_id ); ?>
						<tr>
							<td><a href="<?php echo esc_url( get_edit_post_link( $feed_id ) ); ?>"><?php echo esc_html( $m['name'] ); ?></a></td>
							<td><?php echo esc_html( 'native' === $m['type'] ? 'EWO Native' : 'Feedzy' ); ?></td>
							<td><?php echo esc_html( ucfirst( $m['status'] ) ); ?></td>
							<td><?php echo wp_kses_post( EWO_RSS_Admin_Feeds::health_badge( $m['health'] ) ); ?></td>
							<td><?php echo esc_html( $m['last_success'] ? $m['last_success'] : '—' ); ?></td>
							<td><?php echo esc_html( $m['last_failure'] ? $m['last_failure'] : '—' ); ?></td>
							<td><?php echo esc_html( (string) $m['consecutive_failures'] ); ?></td>
							<td><?php echo esc_html( (string) $m['error_count'] ); ?></td>
							<td><?php echo esc_html( $m['avg_response_ms'] ? (string) $m['avg_response_ms'] : '—' ); ?></td>
							<td><?php echo esc_html( (string) $m['total_imported'] ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/* ----- Duplicate Management Panel (item 8) ----- */

	/**
	 * Render the duplicate management panel.
	 */
	public function render_duplicates() {
		if ( ! current_user_can( self::CAP ) ) {
			return;
		}
		$groups = EWO_RSS_Dedup::groups();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Duplicate Management', 'ewo-rss-engine' ); ?></h1>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin:12px 0;">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>" />
				<input type="hidden" name="do" value="resolve_all" />
				<?php wp_nonce_field( self::NONCE ); ?>
				<button class="button button-primary"<?php echo empty( $groups ) ? ' disabled' : ''; ?>>
					<?php esc_html_e( 'Resolve all duplicates (keep canonical)', 'ewo-rss-engine' ); ?>
				</button>
				<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=ewo-rss-duplicates' ) ); ?>"><?php esc_html_e( 'Rescan', 'ewo-rss-engine' ); ?></a>
				<span style="margin-left:8px;"><?php echo esc_html( sprintf( /* translators: %d groups */ __( '%d duplicate group(s)', 'ewo-rss-engine' ), count( $groups ) ) ); ?></span>
			</form>

			<?php if ( empty( $groups ) ) : ?>
				<p><?php esc_html_e( 'No duplicates found. One article exists once across the platform.', 'ewo-rss-engine' ); ?></p>
			<?php else : ?>
				<?php foreach ( $groups as $group ) : ?>
					<div class="postbox" style="padding:12px;margin-bottom:12px;">
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>" />
							<input type="hidden" name="do" value="merge" />
							<?php wp_nonce_field( self::NONCE ); ?>
							<table class="widefat striped">
								<thead><tr>
									<th><?php esc_html_e( 'Keep', 'ewo-rss-engine' ); ?></th>
									<th><?php esc_html_e( 'Post', 'ewo-rss-engine' ); ?></th>
									<th><?php esc_html_e( 'Importer', 'ewo-rss-engine' ); ?></th>
									<th><?php esc_html_e( 'Source feed', 'ewo-rss-engine' ); ?></th>
									<th><?php esc_html_e( 'Status', 'ewo-rss-engine' ); ?></th>
								</tr></thead>
								<tbody>
								<?php foreach ( $group['members'] as $member ) : ?>
									<tr>
										<td><input type="radio" name="keep_id" value="<?php echo esc_attr( $member['id'] ); ?>" <?php checked( $member['canonical'] ); ?> required /></td>
										<td><a href="<?php echo esc_url( (string) get_edit_post_link( $member['id'] ) ); ?>">#<?php echo esc_html( $member['id'] ); ?> — <?php echo esc_html( $member['title'] ); ?></a></td>
										<td><?php echo esc_html( EWO_RSS_Meta::IMPORTER_NATIVE === $member['importer'] ? 'EWO Native' : ( EWO_RSS_Meta::IMPORTER_FEEDZY === $member['importer'] ? 'Feedzy' : '—' ) ); ?></td>
										<td><?php echo esc_html( $member['feed_name'] ? $member['feed_name'] : (string) $member['feed_id'] ); ?></td>
										<td><?php echo esc_html( $member['status'] ); ?></td>
										<input type="hidden" name="member_ids[]" value="<?php echo esc_attr( $member['id'] ); ?>" />
									</tr>
								<?php endforeach; ?>
								</tbody>
							</table>
							<p style="margin-top:8px;">
								<button class="button"><?php esc_html_e( 'Merge — keep selected, trash the rest', 'ewo-rss-engine' ); ?></button>
							</p>
						</form>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/* ----- Importer Attribution (item 11) ----- */

	/**
	 * Render the importer attribution view.
	 */
	public function render_attribution() {
		if ( ! current_user_can( self::CAP ) ) {
			return;
		}
		$stats  = EWO_RSS_Audit_Log::importer_stats();
		$active = $this->active_counts();
		$runs   = EWO_RSS_Audit_Log::recent( 30 );

		$importers = array(
			EWO_RSS_Meta::IMPORTER_NATIVE => __( 'EWO Native', 'ewo-rss-engine' ),
			EWO_RSS_Meta::IMPORTER_FEEDZY => __( 'Feedzy', 'ewo-rss-engine' ),
		);
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Importer Attribution', 'ewo-rss-engine' ); ?></h1>
			<p class="description"><?php esc_html_e( 'Measure migration progress away from Feedzy toward EWO Native ingestion.', 'ewo-rss-engine' ); ?></p>
			<table class="widefat striped" style="max-width:760px;">
				<thead><tr>
					<th><?php esc_html_e( 'Importer', 'ewo-rss-engine' ); ?></th>
					<th><?php esc_html_e( 'Active content', 'ewo-rss-engine' ); ?></th>
					<th><?php esc_html_e( 'Total imports (runs)', 'ewo-rss-engine' ); ?></th>
					<th><?php esc_html_e( 'Imported', 'ewo-rss-engine' ); ?></th>
					<th><?php esc_html_e( 'Duplicates', 'ewo-rss-engine' ); ?></th>
				</tr></thead>
				<tbody>
				<?php foreach ( $importers as $key => $label ) : $s = isset( $stats[ $key ] ) ? $stats[ $key ] : array(); ?>
					<tr>
						<td><strong><?php echo esc_html( $label ); ?></strong></td>
						<td><?php echo esc_html( (string) ( isset( $active[ $key ] ) ? $active[ $key ] : 0 ) ); ?></td>
						<td><?php echo esc_html( (string) ( isset( $s['runs'] ) ? $s['runs'] : 0 ) ); ?></td>
						<td><?php echo esc_html( (string) ( isset( $s['imported'] ) ? $s['imported'] : 0 ) ); ?></td>
						<td><?php echo esc_html( (string) ( isset( $s['duplicates'] ) ? $s['duplicates'] : 0 ) ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>

			<h2><?php esc_html_e( 'Recent import runs (audit log)', 'ewo-rss-engine' ); ?></h2>
			<table class="widefat striped">
				<thead><tr>
					<th><?php esc_html_e( 'When (UTC)', 'ewo-rss-engine' ); ?></th>
					<th><?php esc_html_e( 'Feed', 'ewo-rss-engine' ); ?></th>
					<th><?php esc_html_e( 'Importer', 'ewo-rss-engine' ); ?></th>
					<th><?php esc_html_e( 'Result', 'ewo-rss-engine' ); ?></th>
					<th><?php esc_html_e( 'Imported', 'ewo-rss-engine' ); ?></th>
					<th><?php esc_html_e( 'Dupes', 'ewo-rss-engine' ); ?></th>
					<th><?php esc_html_e( 'Errors', 'ewo-rss-engine' ); ?></th>
					<th><?php esc_html_e( 'Message', 'ewo-rss-engine' ); ?></th>
				</tr></thead>
				<tbody>
				<?php if ( empty( $runs ) ) : ?>
					<tr><td colspan="8"><?php esc_html_e( 'No runs recorded yet.', 'ewo-rss-engine' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $runs as $r ) : ?>
						<tr>
							<td><?php echo esc_html( $r->created_at ); ?></td>
							<td><?php echo esc_html( $r->feed_name ? $r->feed_name : (string) $r->feed_id ); ?></td>
							<td><?php echo esc_html( $r->importer ); ?></td>
							<td><?php echo esc_html( $r->result ); ?></td>
							<td><?php echo esc_html( (string) $r->imported_count ); ?></td>
							<td><?php echo esc_html( (string) $r->duplicate_count ); ?></td>
							<td><?php echo esc_html( (string) $r->error_count ); ?></td>
							<td><?php echo esc_html( $r->message ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Active (non-trashed) post counts per importer.
	 *
	 * @return array<string,int>
	 */
	protected function active_counts() {
		$counts = array();
		foreach ( array( EWO_RSS_Meta::IMPORTER_NATIVE, EWO_RSS_Meta::IMPORTER_FEEDZY ) as $importer ) {
			$counts[ $importer ] = count(
				get_posts(
					array(
						'post_type'      => 'any',
						'post_status'    => array( 'publish', 'pending', 'draft', 'future', 'private' ),
						'posts_per_page' => -1,
						'fields'         => 'ids',
						'meta_key'       => EWO_RSS_Meta::IMPORTER,
						'meta_value'     => $importer,
					)
				)
			);
		}

		return $counts;
	}

	/* ----- Action handler ----- */

	/**
	 * Handle duplicate-panel actions.
	 */
	public function handle() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'Not allowed.', 'ewo-rss-engine' ) );
		}
		check_admin_referer( self::NONCE );

		$do  = isset( $_POST['do'] ) ? sanitize_key( wp_unslash( $_POST['do'] ) ) : '';
		$msg = '';

		if ( 'resolve_all' === $do ) {
			$msg = sprintf( /* translators: %d count */ __( 'Resolved %d duplicate(s).', 'ewo-rss-engine' ), count( EWO_RSS_Dedup::resolve_all( false ) ) );
		} elseif ( 'merge' === $do ) {
			$keep    = isset( $_POST['keep_id'] ) ? absint( wp_unslash( $_POST['keep_id'] ) ) : 0;
			$members = isset( $_POST['member_ids'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['member_ids'] ) ) : array();
			$remove  = array_diff( $members, array( $keep ) );
			$n       = EWO_RSS_Dedup::merge( $keep, $remove );
			$msg     = sprintf( /* translators: %d count */ __( 'Merged: trashed %d duplicate(s).', 'ewo-rss-engine' ), $n );
		}

		if ( function_exists( 'ewo_rss_engine_flush_feed_cache' ) ) {
			ewo_rss_engine_flush_feed_cache();
		}

		wp_safe_redirect( add_query_arg( 'ewo_done', rawurlencode( $msg ), admin_url( 'admin.php?page=ewo-rss-duplicates' ) ) );
		exit;
	}
}
