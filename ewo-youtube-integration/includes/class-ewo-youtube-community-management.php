<?php
/**
 * Manual single community-post add/manage screen.
 *
 * @package EWO_YouTube_Integration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles manual YouTube community post records on a dedicated admin page.
 */
class EWO_YouTube_Community_Management {
	const PAGE_SLUG           = 'ewo-youtube-add-community';
	const SAVE_ACTION         = 'ewo_youtube_save_community';
	const DELETE_ACTION       = 'ewo_youtube_delete_community';
	const SAVE_NONCE_ACTION   = 'ewo_youtube_save_community';
	const DELETE_NONCE_ACTION = 'ewo_youtube_delete_community';

	/**
	 * Initialize hooks.
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_post_' . self::SAVE_ACTION, array( $this, 'handle_save_request' ) );
		add_action( 'admin_post_' . self::DELETE_ACTION, array( $this, 'handle_delete_request' ) );
		add_action( 'load-post-new.php', array( $this, 'redirect_new_community_screen' ) );
		add_action( 'load-post.php', array( $this, 'redirect_edit_community_screen' ) );
		add_filter( 'get_edit_post_link', array( $this, 'filter_edit_link' ), 10, 3 );
		add_filter( 'post_row_actions', array( $this, 'filter_row_actions' ), 10, 2 );
	}

	/**
	 * Register the community submenu.
	 */
	public function register_menu() {
		add_submenu_page(
			'ewo-youtube',
			esc_html__( 'Community Posts', 'ewo-youtube-integration' ),
			esc_html__( 'Community Posts', 'ewo-youtube-integration' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' ),
			30
		);
	}

	/**
	 * Send the "Add New YouTube Community Post" action to this screen instead
	 * of the WordPress block editor.
	 */
	public function redirect_new_community_screen() {
		$post_type = isset( $_GET['post_type'] ) ? sanitize_key( wp_unslash( $_GET['post_type'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( 'ewo_community' !== $post_type ) {
			return;
		}

		wp_safe_redirect( $this->get_page_url() );
		exit;
	}

	/**
	 * Send "Edit" (post.php?action=edit) for a community post to this screen
	 * instead of the WordPress block editor.
	 */
	public function redirect_edit_community_screen() {
		$action  = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( 'edit' !== $action || ! $post_id || 'ewo_community' !== get_post_type( $post_id ) ) {
			return;
		}

		wp_safe_redirect( $this->get_page_url( array( 'edit_community' => $post_id ) ) );
		exit;
	}

	/**
	 * Point community-post edit links (row title and Edit action) at this screen.
	 *
	 * @param string $link    Default edit link.
	 * @param int    $post_id Post ID.
	 * @param string $context Link context.
	 * @return string
	 */
	public function filter_edit_link( $link, $post_id, $context ) {
		if ( 'ewo_community' !== get_post_type( $post_id ) || ! current_user_can( 'manage_options' ) ) {
			return $link;
		}

		$url = $this->get_page_url( array( 'edit_community' => $post_id ) );

		return 'display' === $context ? esc_url( $url ) : esc_url_raw( $url );
	}

	/**
	 * Remove the Quick Edit (inline) action for community posts.
	 *
	 * @param array<string,string> $actions Row actions.
	 * @param WP_Post               $post    Current post.
	 * @return array<string,string>
	 */
	public function filter_row_actions( $actions, $post ) {
		if ( 'ewo_community' !== $post->post_type ) {
			return $actions;
		}

		unset( $actions['inline hide-if-no-js'] );

		return $actions;
	}

	/**
	 * Handle community post save requests.
	 */
	public function handle_save_request() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage community posts.', 'ewo-youtube-integration' ) );
		}

		check_admin_referer( self::SAVE_NONCE_ACTION );

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$title   = isset( $_POST['ewo_youtube_community_title'] ) ? sanitize_text_field( wp_unslash( $_POST['ewo_youtube_community_title'] ) ) : '';
		$content = isset( $_POST['ewo_youtube_community_content'] ) ? sanitize_textarea_field( wp_unslash( $_POST['ewo_youtube_community_content'] ) ) : '';
		$image   = isset( $_POST['ewo_youtube_community_image'] ) ? esc_url_raw( wp_unslash( $_POST['ewo_youtube_community_image'] ) ) : '';
		$url     = isset( $_POST['ewo_youtube_community_url'] ) ? esc_url_raw( wp_unslash( $_POST['ewo_youtube_community_url'] ) ) : '';

		if ( '' === $title ) {
			wp_safe_redirect( $this->get_page_url( array( 'ewo_youtube_community_status' => 'missing' ) ) );
			exit;
		}

		$post_data = array(
			'post_title'   => $title,
			'post_content' => $content,
			'post_type'    => 'ewo_community',
			'post_status'  => 'publish',
		);

		if ( $post_id && 'ewo_community' === get_post_type( $post_id ) ) {
			$post_data['ID'] = $post_id;
			$saved_post_id   = wp_update_post( wp_slash( $post_data ), true );
		} else {
			$saved_post_id = wp_insert_post( wp_slash( $post_data ), true );
		}

		if ( is_wp_error( $saved_post_id ) ) {
			wp_safe_redirect( $this->get_page_url( array( 'ewo_youtube_community_status' => 'error' ) ) );
			exit;
		}

		if ( '' === $image ) {
			delete_post_meta( $saved_post_id, 'ewo_youtube_community_image' );
		} else {
			update_post_meta( $saved_post_id, 'ewo_youtube_community_image', $image );
		}

		if ( '' === $url ) {
			delete_post_meta( $saved_post_id, 'ewo_youtube_community_url' );
		} else {
			update_post_meta( $saved_post_id, 'ewo_youtube_community_url', $url );
		}

		wp_safe_redirect( $this->get_page_url( array( 'ewo_youtube_community_status' => 'saved' ) ) );
		exit;
	}

	/**
	 * Handle community post delete requests.
	 */
	public function handle_delete_request() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to delete community posts.', 'ewo-youtube-integration' ) );
		}

		check_admin_referer( self::DELETE_NONCE_ACTION );

		$post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;

		if ( $post_id && 'ewo_community' === get_post_type( $post_id ) ) {
			wp_trash_post( $post_id );
		}

		wp_safe_redirect( $this->get_page_url( array( 'ewo_youtube_community_status' => 'deleted' ) ) );
		exit;
	}

	/**
	 * Render the add/manage community post page.
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$edit_post = $this->get_edit_post();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'EWO YouTube — Add Community Post', 'ewo-youtube-integration' ); ?></h1>
			<?php $this->render_notice(); ?>
			<h2><?php echo $edit_post ? esc_html__( 'Edit Community Post', 'ewo-youtube-integration' ) : esc_html__( 'Add Community Post', 'ewo-youtube-integration' ); ?></h2>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::SAVE_ACTION ); ?>">
				<input type="hidden" name="post_id" value="<?php echo esc_attr( $edit_post ? $edit_post->ID : 0 ); ?>">
				<?php wp_nonce_field( self::SAVE_NONCE_ACTION ); ?>
				<table class="form-table" role="presentation">
					<?php $this->render_form_fields( $edit_post ); ?>
				</table>
				<?php submit_button( $edit_post ? esc_html__( 'Update Community Post', 'ewo-youtube-integration' ) : esc_html__( 'Add Community Post', 'ewo-youtube-integration' ) ); ?>
			</form>
			<hr>
			<?php $this->render_community_table(); ?>
		</div>
		<?php
	}

	/**
	 * Render the add/edit form fields.
	 *
	 * @param WP_Post|null $post Community post.
	 */
	private function render_form_fields( $post ) {
		$title   = $post ? get_the_title( $post ) : '';
		$content = $post ? get_post_field( 'post_content', $post ) : '';
		$image   = $post ? get_post_meta( $post->ID, 'ewo_youtube_community_image', true ) : '';
		$url     = $post ? get_post_meta( $post->ID, 'ewo_youtube_community_url', true ) : '';
		?>
		<tr>
			<th scope="row"><label for="ewo-youtube-community-title"><?php esc_html_e( 'Title', 'ewo-youtube-integration' ); ?></label></th>
			<td><input id="ewo-youtube-community-title" name="ewo_youtube_community_title" type="text" class="regular-text" value="<?php echo esc_attr( $title ); ?>" required></td>
		</tr>
		<tr>
			<th scope="row"><label for="ewo-youtube-community-content"><?php esc_html_e( 'Content', 'ewo-youtube-integration' ); ?></label></th>
			<td><textarea id="ewo-youtube-community-content" name="ewo_youtube_community_content" rows="5" class="large-text"><?php echo esc_textarea( $content ); ?></textarea></td>
		</tr>
		<tr>
			<th scope="row"><label for="ewo-youtube-community-image"><?php esc_html_e( 'Image URL', 'ewo-youtube-integration' ); ?></label></th>
			<td><input id="ewo-youtube-community-image" name="ewo_youtube_community_image" type="url" class="regular-text" value="<?php echo esc_attr( $image ); ?>" placeholder="https://"></td>
		</tr>
		<tr>
			<th scope="row"><label for="ewo-youtube-community-url"><?php esc_html_e( 'Link URL', 'ewo-youtube-integration' ); ?></label></th>
			<td><input id="ewo-youtube-community-url" name="ewo_youtube_community_url" type="url" class="regular-text" value="<?php echo esc_attr( $url ); ?>" placeholder="https://"></td>
		</tr>
		<?php
	}

	/**
	 * Render existing community post records.
	 */
	private function render_community_table() {
		$posts = get_posts(
			array(
				'post_type'      => 'ewo_community',
				'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
				'posts_per_page' => 50,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);
		?>
		<h2><?php esc_html_e( 'Saved Community Posts', 'ewo-youtube-integration' ); ?></h2>
		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Title', 'ewo-youtube-integration' ); ?></th>
					<th><?php esc_html_e( 'Link', 'ewo-youtube-integration' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'ewo-youtube-integration' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $posts ) ) : ?>
					<tr><td colspan="3"><?php esc_html_e( 'No community posts have been added yet.', 'ewo-youtube-integration' ); ?></td></tr>
				<?php endif; ?>
				<?php foreach ( $posts as $community_post ) : ?>
					<?php $url = get_post_meta( $community_post->ID, 'ewo_youtube_community_url', true ); ?>
					<tr>
						<td><strong><?php echo esc_html( get_the_title( $community_post ) ); ?></strong></td>
						<td><?php echo $url ? '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Open', 'ewo-youtube-integration' ) . '</a>' : '&mdash;'; ?></td>
						<td>
							<a href="<?php echo esc_url( $this->get_page_url( array( 'edit_community' => $community_post->ID ) ) ); ?>"><?php esc_html_e( 'Edit', 'ewo-youtube-integration' ); ?></a> |
							<a href="<?php echo esc_url( $this->get_delete_url( $community_post->ID ) ); ?>"><?php esc_html_e( 'Delete', 'ewo-youtube-integration' ); ?></a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Get the community post being edited.
	 *
	 * @return WP_Post|null
	 */
	private function get_edit_post() {
		$post_id = isset( $_GET['edit_community'] ) ? absint( $_GET['edit_community'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! $post_id || 'ewo_community' !== get_post_type( $post_id ) ) {
			return null;
		}

		return get_post( $post_id );
	}

	/**
	 * Render status notices.
	 */
	private function render_notice() {
		$status = isset( $_GET['ewo_youtube_community_status'] ) ? sanitize_key( wp_unslash( $_GET['ewo_youtube_community_status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( '' === $status ) {
			return;
		}

		$messages = array(
			'saved'   => __( 'Community post saved.', 'ewo-youtube-integration' ),
			'deleted' => __( 'Community post deleted.', 'ewo-youtube-integration' ),
			'missing' => __( 'A title is required.', 'ewo-youtube-integration' ),
			'error'   => __( 'Community post could not be saved.', 'ewo-youtube-integration' ),
		);

		$type = in_array( $status, array( 'missing', 'error' ), true ) ? 'notice-error' : 'notice-success';

		if ( isset( $messages[ $status ] ) ) {
			echo '<div class="notice ' . esc_attr( $type ) . ' is-dismissible"><p>' . esc_html( $messages[ $status ] ) . '</p></div>';
		}
	}

	/**
	 * Get the delete URL for a community post.
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
