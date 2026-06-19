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
	}

	/**
	 * Render the dashboard page.
	 */
	public function render_dashboard() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}

		$source_ids = $this->sources->get_all_sources();
		$logs       = array_slice( EWO_RSS_Logs::all(), 0, 10 );
		$next_run   = wp_next_scheduled( EWO_RSS_ENGINE_CRON_HOOK );
		?>
		<div class="wrap ewo-rss-wrap">
			<h1><?php esc_html_e( 'EWO RSS Engine', 'ewo-rss-engine' ); ?></h1>
			<p class="ewo-rss-tagline">
				<?php esc_html_e( 'Internal RSS/news ingestion engine for Emerging World Order 2025.', 'ewo-rss-engine' ); ?>
			</p>

			<p>
				<?php
				if ( $next_run ) {
					printf(
						/* translators: %s: human-readable time difference. */
						esc_html__( 'Next scheduled import: in %s.', 'ewo-rss-engine' ),
						esc_html( human_time_diff( time(), $next_run ) )
					);
				} else {
					esc_html_e( 'No import is currently scheduled.', 'ewo-rss-engine' );
				}
				?>
			</p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ewo-rss-runall">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::RUN_ACTION ); ?>" />
				<input type="hidden" name="source_id" value="0" />
				<?php wp_nonce_field( self::RUN_ACTION ); ?>
				<?php submit_button( __( 'Run All Imports Now', 'ewo-rss-engine' ), 'primary', 'submit', false ); ?>
			</form>

			<h2><?php esc_html_e( 'Feed Sources', 'ewo-rss-engine' ); ?></h2>
			<?php if ( empty( $source_ids ) ) : ?>
				<p>
					<?php
					printf(
						/* translators: %s: link to add a new feed source. */
						esc_html__( 'No feed sources yet. %s', 'ewo-rss-engine' ),
						'<a href="' . esc_url( admin_url( 'post-new.php?post_type=' . EWO_RSS_Sources::POST_TYPE ) ) . '">' . esc_html__( 'Add one', 'ewo-rss-engine' ) . '</a>'
					);
					?>
				</p>
			<?php else : ?>
				<table class="widefat striped">
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
								<td>
									<a href="<?php echo esc_url( get_edit_post_link( $source_id ) ); ?>">
										<?php echo esc_html( $settings['name'] ); ?>
									</a>
								</td>
								<td><code><?php echo esc_html( $settings['feed_url'] ); ?></code></td>
								<td>
									<?php
									echo $settings['enabled']
										? esc_html__( 'Enabled', 'ewo-rss-engine' )
										: esc_html__( 'Disabled', 'ewo-rss-engine' );
									?>
								</td>
								<td><?php echo esc_html( '' !== $settings['last_run'] ? $settings['last_run'] : '—' ); ?></td>
								<td>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
										<input type="hidden" name="action" value="<?php echo esc_attr( self::RUN_ACTION ); ?>" />
										<input type="hidden" name="source_id" value="<?php echo esc_attr( (string) $source_id ); ?>" />
										<?php wp_nonce_field( self::RUN_ACTION ); ?>
										<?php submit_button( __( 'Run', 'ewo-rss-engine' ), 'secondary small', 'submit', false ); ?>
									</form>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<h2><?php esc_html_e( 'Recent Activity', 'ewo-rss-engine' ); ?></h2>
			<?php $this->render_logs_table( $logs ); ?>
			<p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::LOGS_SLUG ) ); ?>">
					<?php esc_html_e( 'View all logs', 'ewo-rss-engine' ); ?>
				</a>
			</p>
		</div>
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
