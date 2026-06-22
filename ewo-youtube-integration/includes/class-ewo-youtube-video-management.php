<?php
/**
 * Manual single-video add/manage screen.
 *
 * @package EWO_YouTube_Integration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles manual single YouTube video records on a dedicated admin page.
 */
class EWO_YouTube_Video_Management {
	const PAGE_SLUG           = 'ewo-youtube-add-video';
	const SAVE_ACTION         = 'ewo_youtube_save_video';
	const DELETE_ACTION       = 'ewo_youtube_delete_video';
	const SAVE_NONCE_ACTION   = 'ewo_youtube_save_video';
	const DELETE_NONCE_ACTION = 'ewo_youtube_delete_video';

	/**
	 * Initialize hooks.
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_post_' . self::SAVE_ACTION, array( $this, 'handle_save_request' ) );
		add_action( 'admin_post_' . self::DELETE_ACTION, array( $this, 'handle_delete_request' ) );
		add_action( 'load-post-new.php', array( $this, 'redirect_new_video_screen' ) );
		add_action( 'load-post.php', array( $this, 'redirect_edit_video_screen' ) );
		add_filter( 'get_edit_post_link', array( $this, 'filter_edit_link' ), 10, 3 );
		add_filter( 'post_row_actions', array( $this, 'filter_row_actions' ), 10, 2 );
	}

	/**
	 * Register the add video submenu.
	 */
	public function register_menu() {
		add_submenu_page(
			'ewo-youtube',
			esc_html__( 'Videos', 'ewo-youtube-integration' ),
			esc_html__( 'Videos', 'ewo-youtube-integration' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' ),
			10
		);
	}

	/**
	 * Send the "Add New YouTube Video" action to this screen instead of
	 * the WordPress block editor.
	 */
	public function redirect_new_video_screen() {
		$post_type = isset( $_GET['post_type'] ) ? sanitize_key( wp_unslash( $_GET['post_type'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( 'ewo_video' !== $post_type ) {
			return;
		}

		wp_safe_redirect( $this->get_page_url() );
		exit;
	}

	/**
	 * Send "Edit" (post.php?action=edit) for a video to this screen instead
	 * of the WordPress block editor.
	 */
	public function redirect_edit_video_screen() {
		$action  = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( 'edit' !== $action || ! $post_id || 'ewo_video' !== get_post_type( $post_id ) ) {
			return;
		}

		wp_safe_redirect( $this->get_page_url( array( 'edit_video' => $post_id ) ) );
		exit;
	}

	/**
	 * Point video edit links (row title and Edit action) at this screen.
	 *
	 * @param string $link    Default edit link.
	 * @param int    $post_id Post ID.
	 * @param string $context Link context.
	 * @return string
	 */
	public function filter_edit_link( $link, $post_id, $context ) {
		if ( 'ewo_video' !== get_post_type( $post_id ) || ! current_user_can( 'manage_options' ) ) {
			return $link;
		}

		$url = $this->get_page_url( array( 'edit_video' => $post_id ) );

		return 'display' === $context ? esc_url( $url ) : esc_url_raw( $url );
	}

	/**
	 * Remove the Quick Edit (inline) action for videos.
	 *
	 * @param array<string,string> $actions Row actions.
	 * @param WP_Post               $post    Current post.
	 * @return array<string,string>
	 */
	public function filter_row_actions( $actions, $post ) {
		if ( 'ewo_video' !== $post->post_type ) {
			return $actions;
		}

		unset( $actions['inline hide-if-no-js'] );

		return $actions;
	}

	/**
	 * Handle video save requests.
	 */
	public function handle_save_request() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage YouTube videos.', 'ewo-youtube-integration' ) );
		}

		check_admin_referer( self::SAVE_NONCE_ACTION );

		$post_id    = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$raw_input  = isset( $_POST['ewo_youtube_video_input'] ) ? sanitize_text_field( wp_unslash( $_POST['ewo_youtube_video_input'] ) ) : '';
		$title      = isset( $_POST['ewo_youtube_title'] ) ? sanitize_text_field( wp_unslash( $_POST['ewo_youtube_title'] ) ) : '';
		$video_type = isset( $_POST['ewo_youtube_video_type'] ) ? sanitize_key( wp_unslash( $_POST['ewo_youtube_video_type'] ) ) : 'long_form';
		$thumbnail  = isset( $_POST['ewo_youtube_thumbnail'] ) ? esc_url_raw( wp_unslash( $_POST['ewo_youtube_thumbnail'] ) ) : '';
		$featured   = isset( $_POST['ewo_youtube_featured'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['ewo_youtube_featured'] ) );
		$hidden     = isset( $_POST['ewo_youtube_visibility'] ) && 'hidden' === sanitize_key( wp_unslash( $_POST['ewo_youtube_visibility'] ) );
		$sort_order = isset( $_POST['ewo_youtube_sort_order'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['ewo_youtube_sort_order'] ) ) ) : '';

		$video_id   = $this->parse_video_id( $raw_input );
		$video_type = in_array( $video_type, array( 'long_form', 'short' ), true ) ? $video_type : 'long_form';

		if ( '' === $video_id || '' === $title ) {
			wp_safe_redirect( $this->get_page_url( array( 'ewo_youtube_video_status' => 'missing' ) ) );
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
			wp_safe_redirect( $this->get_page_url( array( 'ewo_youtube_video_status' => 'error' ) ) );
			exit;
		}

		update_post_meta( $saved_post_id, 'ewo_youtube_video_id', $video_id );
		update_post_meta( $saved_post_id, 'ewo_youtube_title', $title );
		update_post_meta( $saved_post_id, 'ewo_youtube_url', $url );
		update_post_meta( $saved_post_id, 'ewo_youtube_video_type', $video_type );

		if ( '' === $thumbnail ) {
			delete_post_meta( $saved_post_id, 'ewo_youtube_thumbnail' );
		} else {
			update_post_meta( $saved_post_id, 'ewo_youtube_thumbnail', $thumbnail );
		}

		if ( 'short' === $video_type ) {
			update_post_meta( $saved_post_id, 'ewo_youtube_is_short', '1' );
		} else {
			delete_post_meta( $saved_post_id, 'ewo_youtube_is_short' );
		}

		$this->set_flag( $saved_post_id, 'ewo_youtube_featured', $featured );
		$this->set_flag( $saved_post_id, 'ewo_youtube_hidden', $hidden );

		if ( '' === $sort_order ) {
			delete_post_meta( $saved_post_id, 'ewo_youtube_sort_order' );
		} else {
			update_post_meta( $saved_post_id, 'ewo_youtube_sort_order', (string) intval( $sort_order ) );
		}

		wp_safe_redirect( $this->get_page_url( array( 'ewo_youtube_video_status' => 'saved' ) ) );
		exit;
	}

	/**
	 * Handle video delete requests.
	 */
	public function handle_delete_request() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to delete YouTube videos.', 'ewo-youtube-integration' ) );
		}

		check_admin_referer( self::DELETE_NONCE_ACTION );

		$post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;

		if ( $post_id && 'ewo_video' === get_post_type( $post_id ) ) {
			wp_trash_post( $post_id );
		}

		wp_safe_redirect( $this->get_page_url( array( 'ewo_youtube_video_status' => 'deleted' ) ) );
		exit;
	}

	/**
	 * Render the add/manage video page.
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$edit_post = $this->get_edit_post();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'EWO YouTube — Add Video', 'ewo-youtube-integration' ); ?></h1>
			<?php $this->render_notice(); ?>
			<h2><?php echo $edit_post ? esc_html__( 'Edit Video', 'ewo-youtube-integration' ) : esc_html__( 'Add Video', 'ewo-youtube-integration' ); ?></h2>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::SAVE_ACTION ); ?>">
				<input type="hidden" name="post_id" value="<?php echo esc_attr( $edit_post ? $edit_post->ID : 0 ); ?>">
				<?php wp_nonce_field( self::SAVE_NONCE_ACTION ); ?>
				<table class="form-table" role="presentation">
					<?php $this->render_form_fields( $edit_post ); ?>
				</table>
				<?php submit_button( $edit_post ? esc_html__( 'Update Video', 'ewo-youtube-integration' ) : esc_html__( 'Add Video', 'ewo-youtube-integration' ) ); ?>
			</form>
			<hr>
			<?php $this->render_videos_table(); ?>
		</div>
		<?php
	}

	/**
	 * Render the add/edit form fields.
	 *
	 * @param WP_Post|null $post Video post.
	 */
	private function render_form_fields( $post ) {
		$video_input = $post ? get_post_meta( $post->ID, 'ewo_youtube_video_id', true ) : '';
		$title       = $post ? get_the_title( $post ) : '';
		$video_type  = $post ? get_post_meta( $post->ID, 'ewo_youtube_video_type', true ) : 'long_form';
		$thumbnail   = $post ? get_post_meta( $post->ID, 'ewo_youtube_thumbnail', true ) : '';
		$featured    = $post ? (bool) get_post_meta( $post->ID, 'ewo_youtube_featured', true ) : false;
		$hidden      = $post ? (bool) get_post_meta( $post->ID, 'ewo_youtube_hidden', true ) : false;
		$sort_order  = $post ? get_post_meta( $post->ID, 'ewo_youtube_sort_order', true ) : '';
		$video_type  = in_array( $video_type, array( 'long_form', 'short' ), true ) ? $video_type : 'long_form';
		?>
		<tr>
			<th scope="row"><label for="ewo-youtube-video-input"><?php esc_html_e( 'YouTube URL or Video ID', 'ewo-youtube-integration' ); ?></label></th>
			<td>
				<input id="ewo-youtube-video-input" name="ewo_youtube_video_input" type="text" class="regular-text" value="<?php echo esc_attr( $video_input ); ?>" required>
				<p class="description"><?php esc_html_e( 'Paste a watch, youtu.be, or shorts URL — or just the 11-character video ID.', 'ewo-youtube-integration' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="ewo-youtube-video-title"><?php esc_html_e( 'Title', 'ewo-youtube-integration' ); ?></label></th>
			<td><input id="ewo-youtube-video-title" name="ewo_youtube_title" type="text" class="regular-text" value="<?php echo esc_attr( $title ); ?>" required></td>
		</tr>
		<tr>
			<th scope="row"><label for="ewo-youtube-video-type"><?php esc_html_e( 'Video Type', 'ewo-youtube-integration' ); ?></label></th>
			<td>
				<select id="ewo-youtube-video-type" name="ewo_youtube_video_type">
					<option value="long_form" <?php selected( $video_type, 'long_form' ); ?>><?php esc_html_e( 'Long-form', 'ewo-youtube-integration' ); ?></option>
					<option value="short" <?php selected( $video_type, 'short' ); ?>><?php esc_html_e( 'Short', 'ewo-youtube-integration' ); ?></option>
				</select>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="ewo-youtube-video-thumbnail"><?php esc_html_e( 'Thumbnail URL', 'ewo-youtube-integration' ); ?></label></th>
			<td><input id="ewo-youtube-video-thumbnail" name="ewo_youtube_thumbnail" type="url" class="regular-text" value="<?php echo esc_attr( $thumbnail ); ?>" placeholder="https://"></td>
		</tr>
		<tr>
			<th scope="row"><label for="ewo-youtube-video-featured"><?php esc_html_e( 'Featured', 'ewo-youtube-integration' ); ?></label></th>
			<td>
				<select id="ewo-youtube-video-featured" name="ewo_youtube_featured">
					<option value="0" <?php selected( $featured, false ); ?>><?php esc_html_e( 'No', 'ewo-youtube-integration' ); ?></option>
					<option value="1" <?php selected( $featured, true ); ?>><?php esc_html_e( 'Yes', 'ewo-youtube-integration' ); ?></option>
				</select>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="ewo-youtube-video-visibility"><?php esc_html_e( 'Visibility', 'ewo-youtube-integration' ); ?></label></th>
			<td>
				<select id="ewo-youtube-video-visibility" name="ewo_youtube_visibility">
					<option value="visible" <?php selected( $hidden, false ); ?>><?php esc_html_e( 'Visible', 'ewo-youtube-integration' ); ?></option>
					<option value="hidden" <?php selected( $hidden, true ); ?>><?php esc_html_e( 'Hidden', 'ewo-youtube-integration' ); ?></option>
				</select>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="ewo-youtube-video-sort-order"><?php esc_html_e( 'Sort Order', 'ewo-youtube-integration' ); ?></label></th>
			<td>
				<input id="ewo-youtube-video-sort-order" name="ewo_youtube_sort_order" type="number" step="1" class="small-text" value="<?php echo esc_attr( $sort_order ); ?>">
				<p class="description"><?php esc_html_e( 'Lower numbers appear first on the Videos page. Leave blank for newest-first.', 'ewo-youtube-integration' ); ?></p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Render existing video records.
	 */
	private function render_videos_table() {
		$videos = get_posts(
			array(
				'post_type'      => 'ewo_video',
				'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
				'posts_per_page' => 50,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		// Shorts are managed on the dedicated Shorts screen; list long-form only here.
		$videos = array_values( array_filter( $videos, array( $this, 'is_long_form_video' ) ) );
		?>
		<h2><?php esc_html_e( 'Saved Videos', 'ewo-youtube-integration' ); ?></h2>
		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Title', 'ewo-youtube-integration' ); ?></th>
					<th><?php esc_html_e( 'Video ID', 'ewo-youtube-integration' ); ?></th>
					<th><?php esc_html_e( 'Type', 'ewo-youtube-integration' ); ?></th>
					<th><?php esc_html_e( 'Flags', 'ewo-youtube-integration' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'ewo-youtube-integration' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $videos ) ) : ?>
					<tr><td colspan="5"><?php esc_html_e( 'No videos have been added yet.', 'ewo-youtube-integration' ); ?></td></tr>
				<?php endif; ?>
				<?php foreach ( $videos as $video ) : ?>
					<?php
					$video_id = get_post_meta( $video->ID, 'ewo_youtube_video_id', true );
					$type     = get_post_meta( $video->ID, 'ewo_youtube_video_type', true );
					$featured = get_post_meta( $video->ID, 'ewo_youtube_featured', true );
					$hidden   = get_post_meta( $video->ID, 'ewo_youtube_hidden', true );
					?>
					<tr>
						<td><strong><?php echo esc_html( get_the_title( $video ) ); ?></strong></td>
						<td><code><?php echo esc_html( $video_id ); ?></code></td>
						<td><?php echo esc_html( 'short' === $type ? __( 'Short', 'ewo-youtube-integration' ) : __( 'Long-form', 'ewo-youtube-integration' ) ); ?></td>
						<td>
							<?php echo $featured ? esc_html__( 'Featured', 'ewo-youtube-integration' ) : esc_html__( 'Not featured', 'ewo-youtube-integration' ); ?>
							<br>
							<?php echo $hidden ? esc_html__( 'Hidden', 'ewo-youtube-integration' ) : esc_html__( 'Visible', 'ewo-youtube-integration' ); ?>
						</td>
						<td>
							<a href="<?php echo esc_url( $this->get_page_url( array( 'edit_video' => $video->ID ) ) ); ?>"><?php esc_html_e( 'Edit', 'ewo-youtube-integration' ); ?></a> |
							<a href="<?php echo esc_url( $this->get_delete_url( $video->ID ) ); ?>"><?php esc_html_e( 'Delete', 'ewo-youtube-integration' ); ?></a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Get the video post being edited.
	 *
	 * @return WP_Post|null
	 */
	private function get_edit_post() {
		$post_id = isset( $_GET['edit_video'] ) ? absint( $_GET['edit_video'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

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
	 * Determine whether a video is long-form (i.e. not a Short).
	 *
	 * @param WP_Post $video Video post.
	 * @return bool
	 */
	public function is_long_form_video( $video ) {
		if ( 'short' === strtolower( (string) get_post_meta( $video->ID, 'ewo_youtube_video_type', true ) ) ) {
			return false;
		}

		return ! in_array( strtolower( (string) get_post_meta( $video->ID, 'ewo_youtube_is_short', true ) ), array( '1', 'yes', 'true' ), true );
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
		$status = isset( $_GET['ewo_youtube_video_status'] ) ? sanitize_key( wp_unslash( $_GET['ewo_youtube_video_status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( '' === $status ) {
			return;
		}

		$messages = array(
			'saved'   => __( 'Video saved.', 'ewo-youtube-integration' ),
			'deleted' => __( 'Video deleted.', 'ewo-youtube-integration' ),
			'missing' => __( 'A valid YouTube URL/ID and title are required.', 'ewo-youtube-integration' ),
			'error'   => __( 'Video could not be saved.', 'ewo-youtube-integration' ),
		);

		$type = in_array( $status, array( 'missing', 'error' ), true ) ? 'notice-error' : 'notice-success';

		if ( isset( $messages[ $status ] ) ) {
			echo '<div class="notice ' . esc_attr( $type ) . ' is-dismissible"><p>' . esc_html( $messages[ $status ] ) . '</p></div>';
		}
	}

	/**
	 * Get the delete URL for a video.
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
