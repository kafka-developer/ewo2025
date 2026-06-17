<?php
/**
 * Manual playlist management screen.
 *
 * @package EWO_YouTube_Integration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles manual playlist records.
 */
class EWO_YouTube_Playlist_Management {
	const PAGE_SLUG           = 'ewo-youtube-playlists';
	const SAVE_ACTION         = 'ewo_youtube_save_playlist';
	const DELETE_ACTION       = 'ewo_youtube_delete_playlist';
	const SAVE_NONCE_ACTION   = 'ewo_youtube_save_playlist';
	const DELETE_NONCE_ACTION = 'ewo_youtube_delete_playlist';

	/**
	 * Initialize hooks.
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_post_' . self::SAVE_ACTION, array( $this, 'handle_save_request' ) );
		add_action( 'admin_post_' . self::DELETE_ACTION, array( $this, 'handle_delete_request' ) );
	}

	/**
	 * Register the playlist management submenu.
	 */
	public function register_menu() {
		add_submenu_page(
			'ewo-youtube',
			esc_html__( 'Playlists', 'ewo-youtube-integration' ),
			esc_html__( 'Playlists', 'ewo-youtube-integration' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Handle playlist save requests.
	 */
	public function handle_save_request() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage playlists.', 'ewo-youtube-integration' ) );
		}

		check_admin_referer( self::SAVE_NONCE_ACTION );

		$post_id     = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$playlist_id = isset( $_POST['ewo_youtube_playlist_id'] ) ? sanitize_text_field( wp_unslash( $_POST['ewo_youtube_playlist_id'] ) ) : '';
		$title       = isset( $_POST['ewo_youtube_playlist_title'] ) ? sanitize_text_field( wp_unslash( $_POST['ewo_youtube_playlist_title'] ) ) : '';
		$description = isset( $_POST['ewo_youtube_playlist_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['ewo_youtube_playlist_description'] ) ) : '';
		$thumbnail   = isset( $_POST['ewo_youtube_playlist_thumbnail'] ) ? esc_url_raw( wp_unslash( $_POST['ewo_youtube_playlist_thumbnail'] ) ) : '';
		$url         = isset( $_POST['ewo_youtube_playlist_url'] ) ? esc_url_raw( wp_unslash( $_POST['ewo_youtube_playlist_url'] ) ) : '';

		if ( '' === $playlist_id || '' === $title ) {
			wp_safe_redirect( $this->get_page_url( array( 'ewo_youtube_playlist_status' => 'missing' ) ) );
			exit;
		}

		if ( '' === $url ) {
			$url = 'https://www.youtube.com/playlist?list=' . rawurlencode( $playlist_id );
		}

		$post_data = array(
			'post_title'   => $title,
			'post_content' => $description,
			'post_type'    => 'ewo_youtube_playlist',
			'post_status'  => 'publish',
		);

		if ( $post_id && 'ewo_youtube_playlist' === get_post_type( $post_id ) ) {
			$post_data['ID'] = $post_id;
			$saved_post_id   = wp_update_post( wp_slash( $post_data ), true );
		} else {
			$existing = $this->get_playlist_by_id( $playlist_id );

			if ( $existing ) {
				$post_data['ID'] = $existing;
				$saved_post_id   = wp_update_post( wp_slash( $post_data ), true );
			} else {
				$saved_post_id = wp_insert_post( wp_slash( $post_data ), true );
			}
		}

		if ( is_wp_error( $saved_post_id ) ) {
			wp_safe_redirect( $this->get_page_url( array( 'ewo_youtube_playlist_status' => 'error' ) ) );
			exit;
		}

		update_post_meta( $saved_post_id, 'ewo_youtube_playlist_id', $playlist_id );
		update_post_meta( $saved_post_id, 'ewo_youtube_playlist_title', $title );
		update_post_meta( $saved_post_id, 'ewo_youtube_playlist_description', $description );
		update_post_meta( $saved_post_id, 'ewo_youtube_playlist_thumbnail', $thumbnail );
		update_post_meta( $saved_post_id, 'ewo_youtube_playlist_url', $url );

		wp_safe_redirect( $this->get_page_url( array( 'ewo_youtube_playlist_status' => 'saved' ) ) );
		exit;
	}

	/**
	 * Handle playlist delete requests.
	 */
	public function handle_delete_request() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to delete playlists.', 'ewo-youtube-integration' ) );
		}

		check_admin_referer( self::DELETE_NONCE_ACTION );

		$post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;

		if ( $post_id && 'ewo_youtube_playlist' === get_post_type( $post_id ) ) {
			wp_trash_post( $post_id );
		}

		wp_safe_redirect( $this->get_page_url( array( 'ewo_youtube_playlist_status' => 'deleted' ) ) );
		exit;
	}

	/**
	 * Render the playlist management page.
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$edit_post = $this->get_edit_post();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'EWO YouTube Playlists', 'ewo-youtube-integration' ); ?></h1>
			<?php $this->render_notice(); ?>
			<h2><?php echo $edit_post ? esc_html__( 'Edit Playlist', 'ewo-youtube-integration' ) : esc_html__( 'Add Playlist', 'ewo-youtube-integration' ); ?></h2>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::SAVE_ACTION ); ?>">
				<input type="hidden" name="post_id" value="<?php echo esc_attr( $edit_post ? $edit_post->ID : 0 ); ?>">
				<?php wp_nonce_field( self::SAVE_NONCE_ACTION ); ?>
				<table class="form-table" role="presentation">
					<?php $this->render_form_fields( $edit_post ); ?>
				</table>
				<?php submit_button( $edit_post ? esc_html__( 'Update Playlist', 'ewo-youtube-integration' ) : esc_html__( 'Add Playlist', 'ewo-youtube-integration' ) ); ?>
			</form>
			<hr>
			<?php $this->render_playlists_table(); ?>
		</div>
		<?php
	}

	/**
	 * Render playlist form fields.
	 *
	 * @param WP_Post|null $post Playlist post.
	 */
	private function render_form_fields( $post ) {
		$playlist_id = $post ? get_post_meta( $post->ID, 'ewo_youtube_playlist_id', true ) : '';
		$title       = $post ? get_post_meta( $post->ID, 'ewo_youtube_playlist_title', true ) : '';
		$description = $post ? get_post_meta( $post->ID, 'ewo_youtube_playlist_description', true ) : '';
		$thumbnail   = $post ? get_post_meta( $post->ID, 'ewo_youtube_playlist_thumbnail', true ) : '';
		$url         = $post ? get_post_meta( $post->ID, 'ewo_youtube_playlist_url', true ) : '';
		?>
		<tr>
			<th scope="row"><label for="ewo-youtube-playlist-id"><?php esc_html_e( 'Playlist ID', 'ewo-youtube-integration' ); ?></label></th>
			<td><input id="ewo-youtube-playlist-id" name="ewo_youtube_playlist_id" type="text" class="regular-text" value="<?php echo esc_attr( $playlist_id ); ?>" required></td>
		</tr>
		<tr>
			<th scope="row"><label for="ewo-youtube-playlist-title"><?php esc_html_e( 'Title', 'ewo-youtube-integration' ); ?></label></th>
			<td><input id="ewo-youtube-playlist-title" name="ewo_youtube_playlist_title" type="text" class="regular-text" value="<?php echo esc_attr( $title ); ?>" required></td>
		</tr>
		<tr>
			<th scope="row"><label for="ewo-youtube-playlist-description"><?php esc_html_e( 'Description', 'ewo-youtube-integration' ); ?></label></th>
			<td><textarea id="ewo-youtube-playlist-description" name="ewo_youtube_playlist_description" rows="5" class="large-text"><?php echo esc_textarea( $description ); ?></textarea></td>
		</tr>
		<tr>
			<th scope="row"><label for="ewo-youtube-playlist-thumbnail"><?php esc_html_e( 'Thumbnail', 'ewo-youtube-integration' ); ?></label></th>
			<td><input id="ewo-youtube-playlist-thumbnail" name="ewo_youtube_playlist_thumbnail" type="url" class="regular-text" value="<?php echo esc_attr( $thumbnail ); ?>" placeholder="https://"></td>
		</tr>
		<tr>
			<th scope="row"><label for="ewo-youtube-playlist-url"><?php esc_html_e( 'URL', 'ewo-youtube-integration' ); ?></label></th>
			<td><input id="ewo-youtube-playlist-url" name="ewo_youtube_playlist_url" type="url" class="regular-text" value="<?php echo esc_attr( $url ); ?>" placeholder="https://www.youtube.com/playlist?list="></td>
		</tr>
		<?php
	}

	/**
	 * Render existing playlist records.
	 */
	private function render_playlists_table() {
		$playlists = get_posts(
			array(
				'post_type'      => 'ewo_youtube_playlist',
				'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
				'posts_per_page' => 50,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);
		?>
		<h2><?php esc_html_e( 'Saved Playlists', 'ewo-youtube-integration' ); ?></h2>
		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Title', 'ewo-youtube-integration' ); ?></th>
					<th><?php esc_html_e( 'Playlist ID', 'ewo-youtube-integration' ); ?></th>
					<th><?php esc_html_e( 'URL', 'ewo-youtube-integration' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'ewo-youtube-integration' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $playlists ) ) : ?>
					<tr><td colspan="4"><?php esc_html_e( 'No playlists have been added yet.', 'ewo-youtube-integration' ); ?></td></tr>
				<?php endif; ?>
				<?php foreach ( $playlists as $playlist ) : ?>
					<?php
					$playlist_id = get_post_meta( $playlist->ID, 'ewo_youtube_playlist_id', true );
					$url         = get_post_meta( $playlist->ID, 'ewo_youtube_playlist_url', true );
					?>
					<tr>
						<td><strong><?php echo esc_html( get_the_title( $playlist ) ); ?></strong></td>
						<td><code><?php echo esc_html( $playlist_id ); ?></code></td>
						<td><?php echo $url ? '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Open', 'ewo-youtube-integration' ) . '</a>' : '&mdash;'; ?></td>
						<td>
							<a href="<?php echo esc_url( $this->get_page_url( array( 'edit_playlist' => $playlist->ID ) ) ); ?>"><?php esc_html_e( 'Edit', 'ewo-youtube-integration' ); ?></a> |
							<a href="<?php echo esc_url( $this->get_delete_url( $playlist->ID ) ); ?>"><?php esc_html_e( 'Delete', 'ewo-youtube-integration' ); ?></a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Get playlist post for editing.
	 *
	 * @return WP_Post|null
	 */
	private function get_edit_post() {
		$post_id = isset( $_GET['edit_playlist'] ) ? absint( $_GET['edit_playlist'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! $post_id || 'ewo_youtube_playlist' !== get_post_type( $post_id ) ) {
			return null;
		}

		return get_post( $post_id );
	}

	/**
	 * Find an existing playlist by playlist ID.
	 *
	 * @param string $playlist_id Playlist ID.
	 * @return int
	 */
	private function get_playlist_by_id( $playlist_id ) {
		$posts = get_posts(
			array(
				'post_type'      => 'ewo_youtube_playlist',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => 'ewo_youtube_playlist_id',
				'meta_value'     => $playlist_id,
			)
		);

		return empty( $posts ) ? 0 : (int) $posts[0];
	}

	/**
	 * Render status notices.
	 */
	private function render_notice() {
		$status = isset( $_GET['ewo_youtube_playlist_status'] ) ? sanitize_key( wp_unslash( $_GET['ewo_youtube_playlist_status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( '' === $status ) {
			return;
		}

		$messages = array(
			'saved'   => __( 'Playlist saved.', 'ewo-youtube-integration' ),
			'deleted' => __( 'Playlist deleted.', 'ewo-youtube-integration' ),
			'missing' => __( 'Playlist ID and title are required.', 'ewo-youtube-integration' ),
			'error'   => __( 'Playlist could not be saved.', 'ewo-youtube-integration' ),
		);

		$type = in_array( $status, array( 'missing', 'error' ), true ) ? 'notice-error' : 'notice-success';

		if ( isset( $messages[ $status ] ) ) {
			echo '<div class="notice ' . esc_attr( $type ) . ' is-dismissible"><p>' . esc_html( $messages[ $status ] ) . '</p></div>';
		}
	}

	/**
	 * Get delete URL.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	private function get_delete_url( $post_id ) {
		return wp_nonce_url(
			add_query_arg(
				array(
					'action'  => self::DELETE_ACTION,
					'post_id' => $post_id,
				),
				admin_url( 'admin-post.php' )
			),
			self::DELETE_NONCE_ACTION
		);
	}

	/**
	 * Get page URL.
	 *
	 * @param array<string,int|string> $args Query args.
	 * @return string
	 */
	private function get_page_url( $args = array() ) {
		return add_query_arg(
			array_merge(
				array(
					'page' => self::PAGE_SLUG,
				),
				$args
			),
			admin_url( 'admin.php' )
		);
	}
}
