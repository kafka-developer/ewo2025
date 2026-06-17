<?php
/**
 * Plugin settings registration and rendering.
 *
 * @package EWO_YouTube_Integration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and renders YouTube integration settings.
 */
class EWO_YouTube_Settings {
	const OPTION_API_KEY     = 'ewo_youtube_api_key';
	const OPTION_CHANNEL_ID  = 'ewo_youtube_channel_id';
	const OPTION_ENABLE_SYNC = 'ewo_youtube_enable_api_sync';
	const SETTINGS_GROUP     = 'ewo_youtube_settings';
	const SETTINGS_SECTION   = 'ewo_youtube_api_settings';

	/**
	 * Initialize hooks.
	 */
	public function init() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Register settings, section, and fields.
	 */
	public function register_settings() {
		register_setting(
			self::SETTINGS_GROUP,
			self::OPTION_API_KEY,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);

		register_setting(
			self::SETTINGS_GROUP,
			self::OPTION_CHANNEL_ID,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);

		register_setting(
			self::SETTINGS_GROUP,
			self::OPTION_ENABLE_SYNC,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_yes_no' ),
				'default'           => 'no',
			)
		);

		add_settings_section(
			self::SETTINGS_SECTION,
			esc_html__( 'YouTube API Settings', 'ewo-youtube-integration' ),
			array( $this, 'render_section' ),
			self::SETTINGS_GROUP
		);

		add_settings_field(
			self::OPTION_API_KEY,
			esc_html__( 'YouTube API Key', 'ewo-youtube-integration' ),
			array( $this, 'render_api_key_field' ),
			self::SETTINGS_GROUP,
			self::SETTINGS_SECTION
		);

		add_settings_field(
			self::OPTION_CHANNEL_ID,
			esc_html__( 'YouTube Channel ID', 'ewo-youtube-integration' ),
			array( $this, 'render_channel_id_field' ),
			self::SETTINGS_GROUP,
			self::SETTINGS_SECTION
		);

		add_settings_field(
			self::OPTION_ENABLE_SYNC,
			esc_html__( 'Enable API Sync', 'ewo-youtube-integration' ),
			array( $this, 'render_enable_sync_field' ),
			self::SETTINGS_GROUP,
			self::SETTINGS_SECTION
		);
	}

	/**
	 * Sanitize a yes/no option.
	 *
	 * @param string $value Submitted option value.
	 * @return string
	 */
	public function sanitize_yes_no( $value ) {
		return 'yes' === $value ? 'yes' : 'no';
	}

	/**
	 * Render the settings section intro.
	 */
	public function render_section() {
		echo '<p>' . esc_html__( 'Configure YouTube API credentials and sync behavior.', 'ewo-youtube-integration' ) . '</p>';
	}

	/**
	 * Render the API key field.
	 */
	public function render_api_key_field() {
		$value = get_option( self::OPTION_API_KEY, '' );
		?>
		<input
			type="password"
			id="<?php echo esc_attr( self::OPTION_API_KEY ); ?>"
			name="<?php echo esc_attr( self::OPTION_API_KEY ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			class="regular-text"
			autocomplete="off"
		/>
		<?php
	}

	/**
	 * Render the channel ID field.
	 */
	public function render_channel_id_field() {
		$value = get_option( self::OPTION_CHANNEL_ID, '' );
		?>
		<input
			type="text"
			id="<?php echo esc_attr( self::OPTION_CHANNEL_ID ); ?>"
			name="<?php echo esc_attr( self::OPTION_CHANNEL_ID ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			class="regular-text"
		/>
		<?php
	}

	/**
	 * Render the sync toggle field.
	 */
	public function render_enable_sync_field() {
		$value = get_option( self::OPTION_ENABLE_SYNC, 'no' );
		?>
		<select
			id="<?php echo esc_attr( self::OPTION_ENABLE_SYNC ); ?>"
			name="<?php echo esc_attr( self::OPTION_ENABLE_SYNC ); ?>"
		>
			<option value="no" <?php selected( $value, 'no' ); ?>><?php esc_html_e( 'No', 'ewo-youtube-integration' ); ?></option>
			<option value="yes" <?php selected( $value, 'yes' ); ?>><?php esc_html_e( 'Yes', 'ewo-youtube-integration' ); ?></option>
		</select>
		<?php
	}

	/**
	 * Render the full settings page.
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'EWO YouTube', 'ewo-youtube-integration' ); ?></h1>
			<?php $this->render_admin_notice(); ?>
			<form action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>" method="post">
				<?php
				settings_fields( self::SETTINGS_GROUP );
				do_settings_sections( self::SETTINGS_GROUP );
				submit_button();
				?>
			</form>
			<hr>
			<?php $this->render_sync_panel(); ?>
		</div>
		<?php
	}

	/**
	 * Render sync success/error notices.
	 */
	private function render_admin_notice() {
		if ( empty( $_GET['ewo_youtube_sync'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$status = sanitize_key( wp_unslash( $_GET['ewo_youtube_sync'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( 'success' === $status ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'YouTube videos synced successfully.', 'ewo-youtube-integration' ) . '</p></div>';
			return;
		}

		if ( 'error' === $status ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'YouTube sync failed. Review the sync error below.', 'ewo-youtube-integration' ) . '</p></div>';
		}
	}

	/**
	 * Render the manual sync panel.
	 */
	private function render_sync_panel() {
		$last_sync   = get_transient( EWO_YouTube_Sync::TRANSIENT_LAST_SYNC );
		$last_result = get_transient( EWO_YouTube_Sync::TRANSIENT_LAST_RESULT );
		$last_error  = get_transient( EWO_YouTube_Sync::TRANSIENT_LAST_ERROR );
		?>
		<h2><?php esc_html_e( 'Manual Sync', 'ewo-youtube-integration' ); ?></h2>
		<p><?php esc_html_e( 'Sync videos only when you click the button. The plugin does not call the YouTube API on page load.', 'ewo-youtube-integration' ); ?></p>
		<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
			<input type="hidden" name="action" value="<?php echo esc_attr( EWO_YouTube_Sync::ACTION ); ?>">
			<?php wp_nonce_field( EWO_YouTube_Sync::NONCE_ACTION ); ?>
			<?php submit_button( esc_html__( 'Sync Videos', 'ewo-youtube-integration' ), 'secondary', 'submit', false ); ?>
		</form>

		<table class="widefat striped" style="max-width: 760px; margin-top: 16px;">
			<tbody>
				<tr>
					<th scope="row"><?php esc_html_e( 'Last Sync', 'ewo-youtube-integration' ); ?></th>
					<td><?php echo esc_html( $last_sync ? wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $last_sync ) : __( 'Never', 'ewo-youtube-integration' ) ); ?></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Last Result', 'ewo-youtube-integration' ); ?></th>
					<td><?php echo esc_html( $this->format_last_result( $last_result ) ); ?></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Last Error', 'ewo-youtube-integration' ); ?></th>
					<td><?php echo esc_html( $last_error ? $last_error : __( 'None', 'ewo-youtube-integration' ) ); ?></td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Format the latest sync result.
	 *
	 * @param mixed $last_result Last sync result transient value.
	 * @return string
	 */
	private function format_last_result( $last_result ) {
		if ( ! is_array( $last_result ) ) {
			return __( 'No sync has completed yet.', 'ewo-youtube-integration' );
		}

		return sprintf(
			/* translators: 1: created count, 2: updated count, 3: total count. */
			__( '%1$d created, %2$d updated, %3$d total videos returned.', 'ewo-youtube-integration' ),
			(int) ( $last_result['created'] ?? 0 ),
			(int) ( $last_result['updated'] ?? 0 ),
			(int) ( $last_result['total'] ?? 0 )
		);
	}
}
