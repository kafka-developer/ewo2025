<?php
/**
 * Manual single-Short add/manage screen.
 *
 * Shorts are stored as ewo_video posts with a "short" video type; this screen
 * is a focused add/list/edit/delete view for that subset.
 *
 * @package EWO_YouTube_Integration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles manual YouTube Short records on a dedicated admin page.
 */
class EWO_YouTube_Shorts_Management {
	const PAGE_SLUG           = 'ewo-youtube-shorts';
	const SAVE_ACTION         = 'ewo_youtube_save_short';
	const DELETE_ACTION       = 'ewo_youtube_delete_short';
	const SAVE_NONCE_ACTION   = 'ewo_youtube_save_short';
	const DELETE_NONCE_ACTION = 'ewo_youtube_delete_short';

	/**
	 * Initialize hooks.
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_post_' . self::SAVE_ACTION, array( $this, 'handle_save_request' ) );
		add_action( 'admin_post_' . self::DELETE_ACTION, array( $this, 'handle_delete_request' ) );
	}

	/**
	 * Register the Shorts submenu.
	 */
	public function register_menu() {
		add_submenu_page(
			'ewo-youtube',
			esc_html__( 'Shorts', 'ewo-youtube-integration' ),
			esc_html__( 'Shorts', 'ewo-youtube-integration' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Handle Short save requests.
	 */
	public function handle_save_request() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage Shorts.', 'ewo-youtube-integration' ) );
		}

		check_admin_referer( self::SAVE_NONCE_ACTION );

		$post_id    = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$raw_input  = isset( $_POST['ewo_youtube_video_input'] ) ? sanitize_text_field( wp_unslash( $_POST['ewo_youtube_video_input'] ) ) : '';
		$title      = isset( $_POST['ewo_youtube_title'] ) ? sanitize_text_field( wp_unslash( $_POST['ewo_youtube_title'] ) ) : '';
		$thumbnail  = isset( $_POST['ewo_youtube_thumbnail'] ) ? esc_url_raw( wp_unslash( $_POST['ewo_youtube_thumbnail'] ) ) : '';
		$featured   = isset( $_POST['ewo_youtube_featured'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['ewo_youtube_featured'] ) );
		$hidden     = isset( $_POST['ewo_youtube_visibility'] ) && 'hidden' === sanitize_key( wp_unslash( $_POST['ewo_youtube_visibility'] ) );
		$sort_order = isset( $_POST['ewo_youtube_sort_order'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['ewo_youtube_sort_order'] ) ) ) : '';

		$video_id = $this->parse_video_id( $raw_input );

		if ( '' === $video_id || '' === $title ) {
			wp_safe_redirect( $this->get_page_url( array( 'ewo_youtube_short_status' => 'missing' ) ) );
			exit;
		}

		$url       = 'https://www.youtube.com/watch?v=' . rawurlencode( $video_id );
		$post_data = array(
			'post_title'  => $title,
			'post_type'   => 'ewo_video',
			'post_status' => 'publish',
		);

		if ( $post_id && 'ewo_video' === get_post_type( $post_id ) ) {
			$post_data['ID'] = $post_id;
			$saved_post_id   = wp_update_post( wp_slash( $post_data ), true );
		} else {
			$existing = $this->get_video_by_id( $video_id );

			if ( $existing ) {
				$post_data['ID'] = $existing;
				$saved_post_id   = wp_update_post( wp_slash( $post_data ), true );
			} else {
				$saved_post_id = wp_insert_post( wp_slash( $post_data ), true );
			}
		}

		if ( is_wp_error( $saved_post_id ) ) {
			wp_safe_redirect( $this->get_page_url( array( 'ewo_youtube_short_status' => 'error' ) ) );
			exit;
		}

		update_post_meta( $saved_post_id, 'ewo_youtube_video_id', $video_id );
		update_post_meta( $saved_post_id, 'ewo_youtube_title', $title );
		update_post_meta( $saved_post_id, 'ewo_youtube_url', $url );
		update_post_meta( $saved_post_id, 'ewo_youtube_video_type', 'short' );
		update_post_meta( $saved_post_id, 'ewo_youtube_is_short', '1' );

		if ( '' === $thumbnail ) {
			delete_post_meta( $saved_post_id, 'ewo_youtube_thumbnail' );
		} else {
			update_post_meta( $saved_post_id, 'ewo_youtube_thumbnail', $thumbnail );
		}

		$this->set_flag( $saved_post_id, 'ewo_youtube_featured', $featured );
		$this->set_flag( $saved_post_id, 'ewo_youtube_hidden', $hidden );

		if ( '' === $sort_order ) {
			delete_post_meta( $saved_post_id, 'ewo_youtube_sort_order' );
		} else {
			update_post_meta( $saved_post_id, 'ewo_youtube_sort_order', (string) intval( $sort_order ) );
		}

		wp_safe_redirect( $this->get_page_url( array( 'ewo_youtube_short_status' => 'saved' ) ) );
		exit;
	}

	/**
	 * Handle Short delete requests.
	 */
	public function handle_delete_request() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to delete Shorts.', 'ewo-youtube-integration' ) );
		}

		check_admin_referer( self::DELETE_NONCE_ACTION );

		$post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;

		if ( $post_id && 'ewo_video' === get_post_type( $post_id ) ) {
			wp_trash_post( $post_id );
		}

		wp_safe_redirect( $this->get_page_url( array( 'ewo_youtube_short_status' => 'deleted' ) ) );
		exit;
	}

	/**
	 * Render the add/manage Shorts page.
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$edit_post = $this->get_edit_post();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'EWO YouTube — Shorts', 'ewo-youtube-integration' ); ?></h1>
			<?php $this->render_notice(); ?>
			<h2><?php echo $edit_post ? esc_html__( 'Edit Short', 'ewo-youtube-integration' ) : esc_html__( 'Add Short', 'ewo-youtube-integration' ); ?></h2>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::SAVE_ACTION ); ?>">
				<input type="hidden" name="post_id" value="<?php echo esc_attr( $edit_post ? $edit_post->ID : 0 ); ?>">
				<?php wp_nonce_field( self::SAVE_NONCE_ACTION ); ?>
				<table class="form-table" role="presentation">
					<?php $this->render_form_fields( $edit_post ); ?>
				</table>
				<?php submit_button( $edit_post ? esc_html__( 'Update Short', 'ewo-youtube-integration' ) : esc_html__( 'Add Short', 'ewo-youtube-integration' ) ); ?>
			</form>
			<hr>
			<?php $this->render_shorts_table(); ?>
		</div>
		<?php
	}

	/**
	 * Render the add/edit form fields.
	 *
	 * @param WP_Post|null $post Short post.
	 */
	private function render_form_fields( $post ) {
		$video_input = $post ? get_post_meta( $post->ID, 'ewo_youtube_video_id', true ) : '';
		$title       = $post ? get_the_title( $post ) : '';
		$thumbnail   = $post ? get_post_meta( $post->ID, 'ewo_youtube_thumbnail', true ) : '';
		$featured    = $post ? (bool) get_post_meta( $post->ID, 'ewo_youtube_featured', true ) : false;
		$hidden      = $post ? (bool) get_post_meta( $post->ID, 'ewo_youtube_hidden', true ) : false;
		$sort_order  = $post ? get_post_meta( $post->ID, 'ewo_youtube_sort_order', true ) : '';
		?>
		<tr>
			<th scope="row"><label for="ewo-youtube-short-input"><?php esc_html_e( 'YouTube URL or Video ID', 'ewo-youtube-integration' ); ?></label></th>
			<td>
				<input id="ewo-youtube-short-input" name="ewo_youtube_video_input" type="text" class="regular-text" value="<?php echo esc_attr( $video_input ); ?>" required>
				<p class="description"><?php esc_html_e( 'Paste a Shorts, watch, or youtu.be URL — or just the 11-character video ID. Saved as a Short.', 'ewo-youtube-integration' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="ewo-youtube-short-title"><?php esc_html_e( 'Title', 'ewo-youtube-integration' ); ?></label></th>
			<td><input id="ewo-youtube-short-title" name="ewo_youtube_title" type="text" class="regular-text" value="<?php echo esc_attr( $title ); ?>" required></td>
		</tr>
		<tr>
			<th scope="row"><label for="ewo-youtube-short-thumbnail"><?php esc_html_e( 'Thumbnail URL', 'ewo-youtube-integration' ); ?></label></th>
			<td><input id="ewo-youtube-short-thumbnail" name="ewo_youtube_thumbnail" type="url" class="regular-text" value="<?php echo esc_attr( $thumbnail ); ?>" placeholder="https://"></td>
		</tr>
		<tr>
			<th scope="row"><label for="ewo-youtube-short-featured"><?php esc_html_e( 'Featured', 'ewo-youtube-integration' ); ?></label></th>
			<td>
				<select id="ewo-youtube-short-featured" name="ewo_youtube_featured">
					<option value="0" <?php selected( $featured, false ); ?>><?php esc_html_e( 'No', 'ewo-youtube-integration' ); ?></option>
					<option value="1" <?php selected( $featured, true ); ?>><?php esc_html_e( 'Yes', 'ewo-youtube-integration' ); ?></option>
				</select>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="ewo-youtube-short-visibility"><?php esc_html_e( 'Visibility', 'ewo-youtube-integration' ); ?></label></th>
			<td>
				<select id="ewo-youtube-short-visibility" name="ewo_youtube_visibility">
					<option value="visible" <?php selected( $hidden, false ); ?>><?php esc_html_e( 'Visible', 'ewo-youtube-integration' ); ?></option>
					<option value="hidden" <?php selected( $hidden, true ); ?>><?php esc_html_e( 'Hidden', 'ewo-youtube-integration' ); ?></option>
				</select>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="ewo-youtube-short-sort-order"><?php esc_html_e( 'Sort Order', 'ewo-youtube-integration' ); ?></label></th>
			<td>
				<input id="ewo-youtube-short-sort-order" name="ewo_youtube_sort_order" type="number" step="1" class="small-text" value="<?php echo esc_attr( $sort_order ); ?>">
			</td>
		</tr>
		<?php
	}

	/**
	 * Render existing Short records.
	 */
	private function render_shorts_table() {
		$shorts = $this->get_shorts();
		?>
		<h2><?php esc_html_e( 'Saved Shorts', 'ewo-youtube-integration' ); ?></h2>
		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Title', 'ewo-youtube-integration' ); ?></th>
					<th><?php esc_html_e( 'Video ID', 'ewo-youtube-integration' ); ?></th>
					<th><?php esc_html_e( 'Flags', 'ewo-youtube-integration' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'ewo-youtube-integration' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $shorts ) ) : ?>
					<tr><td colspan="4"><?php esc_html_e( 'No Shorts have been added yet.', 'ewo-youtube-integration' ); ?></td></tr>
				<?php endif; ?>
				<?php foreach ( $shorts as $short ) : ?>
					<?php
					$video_id = get_post_meta( $short->ID, 'ewo_youtube_video_id', true );
					$featured = get_post_meta( $short->ID, 'ewo_youtube_featured', true );
					$hidden   = get_post_meta( $short->ID, 'ewo_youtube_hidden', true );
					?>
					<tr>
						<td><strong><?php echo esc_html( get_the_title( $short ) ); ?></strong></td>
						<td><code><?php echo esc_html( $video_id ); ?></code></td>
						<td>
							<?php echo $featured ? esc_html__( 'Featured', 'ewo-youtube-integration' ) : esc_html__( 'Not featured', 'ewo-youtube-integration' ); ?>
							<br>
							<?php echo $hidden ? esc_html__( 'Hidden', 'ewo-youtube-integration' ) : esc_html__( 'Visible', 'ewo-youtube-integration' ); ?>
						</td>
						<td>
							<a href="<?php echo esc_url( $this->get_page_url( array( 'edit_short' => $short->ID ) ) ); ?>"><?php esc_html_e( 'Edit', 'ewo-youtube-integration' ); ?></a> |
							<a href="<?php echo esc_url( $this->get_delete_url( $short->ID ) ); ?>"><?php esc_html_e( 'Delete', 'ewo-youtube-integration' ); ?></a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Get all Short records (videos flagged as a Short), newest first.
	 *
	 * @return WP_Post[]
	 */
	private function get_shorts() {
		$videos = get_posts(
			array(
				'post_type'      => 'ewo_video',
				'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
				'posts_per_page' => 100,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		return array_values(
			array_filter(
				$videos,
				array( $this, 'is_short_video' )
			)
		);
	}

	/**
	 * Determine whether a video is a Short.
	 *
	 * @param WP_Post $video Video post.
	 * @return bool
	 */
	public function is_short_video( $video ) {
		if ( 'short' === strtolower( (string) get_post_meta( $video->ID, 'ewo_youtube_video_type', true ) ) ) {
			return true;
		}

		return in_array( strtolower( (string) get_post_meta( $video->ID, 'ewo_youtube_is_short', true ) ), array( '1', 'yes', 'true' ), true );
	}

	/**
	 * Get the Short post being edited.
	 *
	 * @return WP_Post|null
	 */
	private function get_edit_post() {
		$post_id = isset( $_GET['edit_short'] ) ? absint( $_GET['edit_short'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! $post_id || 'ewo_video' !== get_post_type( $post_id ) ) {
			return null;
		}

		return get_post( $post_id );
	}

	/**
	 * Find an existing video by video ID.
	 *
	 * @param string $video_id Video ID.
	 * @return int
	 */
	private function get_video_by_id( $video_id ) {
		$posts = get_posts(
			array(
				'post_type'      => 'ewo_video',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => 'ewo_youtube_video_id',
				'meta_value'     => $video_id,
			)
		);

		return empty( $posts ) ? 0 : (int) $posts[0];
	}

	/**
	 * Parse a YouTube video ID from a URL or raw ID.
	 *
	 * @param string $value URL or raw video ID.
	 * @return string
	 */
	private function parse_video_id( $value ) {
		if ( preg_match( '/^[A-Za-z0-9_-]{11}$/', $value ) ) {
			return sanitize_text_field( $value );
		}

		$parts = wp_parse_url( $value );

		if ( empty( $parts['host'] ) ) {
			return '';
		}

		$host = strtolower( $parts['host'] );
		$path = $parts['path'] ?? '';

		if ( false !== strpos( $host, 'youtu.be' ) ) {
			$id = trim( $path, '/' );
			$id = explode( '/', $id )[0] ?? '';

			return preg_match( '/^[A-Za-z0-9_-]{11}$/', $id ) ? sanitize_text_field( $id ) : '';
		}

		if ( false === strpos( $host, 'youtube.com' ) ) {
			return '';
		}

		if ( ! empty( $parts['query'] ) ) {
			parse_str( $parts['query'], $query );

			if ( ! empty( $query['v'] ) && preg_match( '/^[A-Za-z0-9_-]{11}$/', $query['v'] ) ) {
				return sanitize_text_field( $query['v'] );
			}
		}

		if ( preg_match( '#/(shorts|embed|live)/([A-Za-z0-9_-]{11})#', $path, $matches ) ) {
			return sanitize_text_field( $matches[2] );
		}

		return '';
	}

	/**
	 * Update or remove a boolean flag meta value.
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $meta_key Meta key.
	 * @param bool   $enabled  Whether the flag is on.
	 */
	private function set_flag( $post_id, $meta_key, $enabled ) {
		if ( $enabled ) {
			update_post_meta( $post_id, $meta_key, '1' );
			return;
		}

		delete_post_meta( $post_id, $meta_key );
	}

	/**
	 * Render status notices.
	 */
	private function render_notice() {
		$status = isset( $_GET['ewo_youtube_short_status'] ) ? sanitize_key( wp_unslash( $_GET['ewo_youtube_short_status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( '' === $status ) {
			return;
		}

		$messages = array(
			'saved'   => __( 'Short saved.', 'ewo-youtube-integration' ),
			'deleted' => __( 'Short deleted.', 'ewo-youtube-integration' ),
			'missing' => __( 'A valid YouTube URL/ID and title are required.', 'ewo-youtube-integration' ),
			'error'   => __( 'Short could not be saved.', 'ewo-youtube-integration' ),
		);

		$type = in_array( $status, array( 'missing', 'error' ), true ) ? 'notice-error' : 'notice-success';

		if ( isset( $messages[ $status ] ) ) {
			echo '<div class="notice ' . esc_attr( $type ) . ' is-dismissible"><p>' . esc_html( $messages[ $status ] ) . '</p></div>';
		}
	}

	/**
	 * Get the delete URL for a Short.
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
	 * Get the management page URL.
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
