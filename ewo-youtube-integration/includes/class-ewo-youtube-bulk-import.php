<?php
/**
 * Bulk YouTube URL import screen.
 *
 * @package EWO_YouTube_Integration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles pasted YouTube URL imports and admin row actions.
 */
class EWO_YouTube_Bulk_Import {
	const PAGE_SLUG             = 'ewo-youtube-bulk-import';
	const IMPORT_ACTION         = 'ewo_youtube_bulk_import';
	const ROW_ACTION            = 'ewo_youtube_video_row_action';
	const IMPORT_NONCE_ACTION   = 'ewo_youtube_bulk_import';
	const ROW_NONCE_ACTION      = 'ewo_youtube_video_row_action';
	const TRANSIENT_LAST_RESULT = 'ewo_youtube_bulk_import_last_result';
	const TRANSIENT_LAST_ERROR  = 'ewo_youtube_bulk_import_last_error';

	/**
	 * Initialize hooks.
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_post_' . self::IMPORT_ACTION, array( $this, 'handle_import_request' ) );
		add_action( 'admin_post_' . self::ROW_ACTION, array( $this, 'handle_row_action' ) );
	}

	/**
	 * Register the bulk import submenu.
	 */
	public function register_menu() {
		add_submenu_page(
			'ewo-youtube',
			esc_html__( 'Bulk Import', 'ewo-youtube-integration' ),
			esc_html__( 'Bulk Import', 'ewo-youtube-integration' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' ),
			60
		);
	}

	/**
	 * Handle pasted URL imports.
	 */
	public function handle_import_request() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to import YouTube videos.', 'ewo-youtube-integration' ) );
		}

		check_admin_referer( self::IMPORT_NONCE_ACTION );

		$raw_urls = isset( $_POST['ewo_youtube_urls'] ) ? sanitize_textarea_field( wp_unslash( $_POST['ewo_youtube_urls'] ) ) : '';
		$result   = $this->import_urls( $raw_urls );
		$status   = is_wp_error( $result ) ? 'error' : 'success';

		wp_safe_redirect( $this->get_page_url( array( 'ewo_youtube_import' => $status ) ) );
		exit;
	}

	/**
	 * Handle row actions for imported videos.
	 */
	public function handle_row_action() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to update YouTube videos.', 'ewo-youtube-integration' ) );
		}

		check_admin_referer( self::ROW_NONCE_ACTION );

		$post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;
		$task    = isset( $_GET['task'] ) ? sanitize_key( wp_unslash( $_GET['task'] ) ) : '';

		if ( ! $post_id || 'ewo_video' !== get_post_type( $post_id ) ) {
			$this->store_error( __( 'Invalid YouTube video record.', 'ewo-youtube-integration' ) );
			wp_safe_redirect( $this->get_page_url( array( 'ewo_youtube_import' => 'error' ) ) );
			exit;
		}

		switch ( $task ) {
			case 'delete':
				wp_trash_post( $post_id );
				break;
			case 'long_form':
				update_post_meta( $post_id, 'ewo_youtube_video_type', 'long_form' );
				delete_post_meta( $post_id, 'ewo_youtube_is_short' );
				break;
			case 'short':
				update_post_meta( $post_id, 'ewo_youtube_video_type', 'short' );
				update_post_meta( $post_id, 'ewo_youtube_is_short', '1' );
				break;
			case 'toggle_featured':
				$this->toggle_meta_flag( $post_id, 'ewo_youtube_featured' );
				break;
			case 'toggle_hidden':
				$this->toggle_meta_flag( $post_id, 'ewo_youtube_hidden' );
				break;
			default:
				$this->store_error( __( 'Unknown video action.', 'ewo-youtube-integration' ) );
				wp_safe_redirect( $this->get_page_url( array( 'ewo_youtube_import' => 'error' ) ) );
				exit;
		}

		wp_safe_redirect( $this->get_page_url( array( 'ewo_youtube_import' => 'updated' ) ) );
		exit;
	}

	/**
	 * Render the bulk import screen.
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'EWO YouTube Bulk Import', 'ewo-youtube-integration' ); ?></h1>
			<?php $this->render_notice(); ?>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::IMPORT_ACTION ); ?>">
				<?php wp_nonce_field( self::IMPORT_NONCE_ACTION ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="ewo-youtube-urls"><?php esc_html_e( 'YouTube URLs', 'ewo-youtube-integration' ); ?></label></th>
						<td>
							<textarea id="ewo-youtube-urls" name="ewo_youtube_urls" rows="10" class="large-text code" placeholder="https://www.youtube.com/watch?v=VIDEO_ID&#10;https://youtu.be/VIDEO_ID&#10;https://www.youtube.com/shorts/VIDEO_ID"></textarea>
							<p class="description"><?php esc_html_e( 'Paste one or more YouTube URLs. Video IDs are parsed automatically. If an API key is configured, metadata is fetched; otherwise placeholder records are created.', 'ewo-youtube-integration' ); ?></p>
						</td>
					</tr>
				</table>
				<?php submit_button( esc_html__( 'Import Videos', 'ewo-youtube-integration' ) ); ?>
			</form>
			<hr>
			<?php $this->render_videos_table(); ?>
		</div>
		<?php
	}

	/**
	 * Import pasted YouTube URLs.
	 *
	 * @param string $raw_urls Raw pasted URLs.
	 * @return array<string,int>|WP_Error
	 */
	private function import_urls( $raw_urls ) {
		$video_ids = $this->parse_video_ids( $raw_urls );

		if ( empty( $video_ids ) ) {
			return $this->store_error( __( 'No valid YouTube video URLs were found.', 'ewo-youtube-integration' ) );
		}

		$metadata = $this->get_video_metadata( $video_ids );
		$created  = 0;
		$updated  = 0;

		foreach ( $video_ids as $video_id ) {
			$video   = $metadata[ $video_id ] ?? $this->get_placeholder_video( $video_id );
			$post_id = $this->upsert_video_post( $video );

			if ( is_wp_error( $post_id ) ) {
				return $this->store_error( $post_id->get_error_message() );
			}

			if ( 'created' === $post_id['status'] ) {
				++$created;
			} else {
				++$updated;
			}
		}

		$result = array(
			'created' => $created,
			'updated' => $updated,
			'total'   => count( $video_ids ),
		);

		set_transient( self::TRANSIENT_LAST_RESULT, $result, 30 * DAY_IN_SECONDS );
		delete_transient( self::TRANSIENT_LAST_ERROR );

		return $result;
	}

	/**
	 * Parse YouTube video IDs from pasted URLs or IDs.
	 *
	 * @param string $raw_urls Raw pasted URLs.
	 * @return string[]
	 */
	private function parse_video_ids( $raw_urls ) {
		$parts     = preg_split( '/[\r\n,\s]+/', $raw_urls );
		$video_ids = array();

		foreach ( $parts as $part ) {
			$part = trim( (string) $part );

			if ( '' === $part ) {
				continue;
			}

			$video_id = $this->parse_video_id( $part );

			if ( $video_id ) {
				$video_ids[] = $video_id;
			}
		}

		return array_values( array_unique( $video_ids ) );
	}

	/**
	 * Parse a single YouTube video ID.
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
			return $this->sanitize_video_id_from_path( $path );
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
	 * Sanitize an ID from a URL path.
	 *
	 * @param string $path URL path.
	 * @return string
	 */
	private function sanitize_video_id_from_path( $path ) {
		$path  = trim( $path, '/' );
		$parts = explode( '/', $path );
		$id    = $parts[0] ?? '';

		return preg_match( '/^[A-Za-z0-9_-]{11}$/', $id ) ? sanitize_text_field( $id ) : '';
	}

	/**
	 * Fetch metadata for video IDs when an API key exists.
	 *
	 * @param string[] $video_ids Video IDs.
	 * @return array<string,array<string,string>>
	 */
	private function get_video_metadata( $video_ids ) {
		$api_key = trim( (string) get_option( EWO_YouTube_Settings::OPTION_API_KEY, '' ) );

		if ( '' === $api_key ) {
			return array();
		}

		$response = wp_remote_get(
			add_query_arg(
				array(
					'part' => 'snippet',
					'id'   => implode( ',', array_slice( $video_ids, 0, 50 ) ),
					'key'  => $api_key,
				),
				'https://www.googleapis.com/youtube/v3/videos'
			),
			array(
				'timeout' => 20,
			)
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			$this->store_error( __( 'Could not fetch YouTube metadata. Placeholder records were created where needed.', 'ewo-youtube-integration' ) );
			return array();
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $body ) ) {
			return array();
		}

		$metadata = array();

		foreach ( $body['items'] ?? array() as $item ) {
			$video_id = $item['id'] ?? '';
			$snippet  = $item['snippet'] ?? array();

			if ( '' === $video_id ) {
				continue;
			}

			$metadata[ $video_id ] = array(
				'video_id'     => sanitize_text_field( $video_id ),
				'title'        => sanitize_text_field( $snippet['title'] ?? sprintf( 'YouTube Video %s', $video_id ) ),
				'published_at' => sanitize_text_field( $snippet['publishedAt'] ?? '' ),
				'thumbnail'    => esc_url_raw( $this->get_best_thumbnail_url( $snippet['thumbnails'] ?? array() ) ),
				'url'          => esc_url_raw( 'https://www.youtube.com/watch?v=' . rawurlencode( $video_id ) ),
			);
		}

		return $metadata;
	}

	/**
	 * Get placeholder metadata for an unfetched video.
	 *
	 * @param string $video_id Video ID.
	 * @return array<string,string>
	 */
	private function get_placeholder_video( $video_id ) {
		return array(
			'video_id'     => $video_id,
			'title'        => sprintf( 'YouTube Video %s', $video_id ),
			'published_at' => '',
			'thumbnail'    => '',
			'url'          => esc_url_raw( 'https://www.youtube.com/watch?v=' . rawurlencode( $video_id ) ),
		);
	}

	/**
	 * Create or update a video post.
	 *
	 * @param array<string,string> $video Video metadata.
	 * @return array<string,int|string>|WP_Error
	 */
	private function upsert_video_post( $video ) {
		$existing = get_posts(
			array(
				'post_type'      => 'ewo_video',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => 'ewo_youtube_video_id',
				'meta_value'     => $video['video_id'],
			)
		);

		$post_date = $this->get_post_date( $video['published_at'] );
		$post_data = array(
			'post_title'    => $video['title'],
			'post_type'     => 'ewo_video',
			'post_status'   => 'publish',
			'post_date'     => $post_date,
			'post_date_gmt' => get_gmt_from_date( $post_date ),
		);

		if ( ! empty( $existing ) ) {
			$post_data['ID'] = (int) $existing[0];
			$post_id         = wp_update_post( wp_slash( $post_data ), true );
			$status          = 'updated';
		} else {
			$post_id = wp_insert_post( wp_slash( $post_data ), true );
			$status  = 'created';
		}

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		update_post_meta( $post_id, 'ewo_youtube_video_id', $video['video_id'] );
		update_post_meta( $post_id, 'ewo_youtube_title', $video['title'] );
		update_post_meta( $post_id, 'ewo_youtube_thumbnail', $video['thumbnail'] );
		update_post_meta( $post_id, 'ewo_youtube_published_at', $video['published_at'] );
		update_post_meta( $post_id, 'ewo_youtube_url', $video['url'] );

		if ( '' === get_post_meta( $post_id, 'ewo_youtube_video_type', true ) ) {
			update_post_meta( $post_id, 'ewo_youtube_video_type', 'long_form' );
		}

		update_post_meta( $post_id, 'ewo_youtube_imported_at', current_time( 'mysql', true ) );

		return array(
			'post_id' => (int) $post_id,
			'status'  => $status,
		);
	}

	/**
	 * Render the video records table.
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
		?>
		<h2><?php esc_html_e( 'Imported Videos', 'ewo-youtube-integration' ); ?></h2>
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
					<tr><td colspan="5"><?php esc_html_e( 'No YouTube video records yet.', 'ewo-youtube-integration' ); ?></td></tr>
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
						<td><?php $this->render_row_actions( $video->ID ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Render actions for a row.
	 *
	 * @param int $post_id Post ID.
	 */
	private function render_row_actions( $post_id ) {
		$actions = array(
			'edit'            => array( __( 'Edit', 'ewo-youtube-integration' ), get_edit_post_link( $post_id, '' ) ),
			'delete'          => array( __( 'Delete', 'ewo-youtube-integration' ), $this->get_row_action_url( $post_id, 'delete' ) ),
			'long_form'       => array( __( 'Mark as Long-form', 'ewo-youtube-integration' ), $this->get_row_action_url( $post_id, 'long_form' ) ),
			'short'           => array( __( 'Mark as Short', 'ewo-youtube-integration' ), $this->get_row_action_url( $post_id, 'short' ) ),
			'toggle_featured' => array( __( 'Featured', 'ewo-youtube-integration' ), $this->get_row_action_url( $post_id, 'toggle_featured' ) ),
			'toggle_hidden'   => array( __( 'Hidden', 'ewo-youtube-integration' ), $this->get_row_action_url( $post_id, 'toggle_hidden' ) ),
		);

		$links = array();

		foreach ( $actions as $action ) {
			$links[] = sprintf( '<a href="%1$s">%2$s</a>', esc_url( $action[1] ), esc_html( $action[0] ) );
		}

		echo wp_kses_post( implode( ' | ', $links ) );
	}

	/**
	 * Get a row action URL.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $task    Task name.
	 * @return string
	 */
	private function get_row_action_url( $post_id, $task ) {
		return wp_nonce_url(
			add_query_arg(
				array(
					'action'  => self::ROW_ACTION,
					'post_id' => $post_id,
					'task'    => $task,
				),
				admin_url( 'admin-post.php' )
			),
			self::ROW_NONCE_ACTION
		);
	}

	/**
	 * Toggle a yes/no meta flag.
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $meta_key Meta key.
	 */
	private function toggle_meta_flag( $post_id, $meta_key ) {
		if ( get_post_meta( $post_id, $meta_key, true ) ) {
			delete_post_meta( $post_id, $meta_key );
			return;
		}

		update_post_meta( $post_id, $meta_key, '1' );
	}

	/**
	 * Render import notices.
	 */
	private function render_notice() {
		$status = isset( $_GET['ewo_youtube_import'] ) ? sanitize_key( wp_unslash( $_GET['ewo_youtube_import'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( 'success' === $status ) {
			$result = get_transient( self::TRANSIENT_LAST_RESULT );
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $this->format_result( $result ) ) . '</p></div>';
			return;
		}

		if ( 'updated' === $status ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Video record updated.', 'ewo-youtube-integration' ) . '</p></div>';
			return;
		}

		if ( 'error' === $status ) {
			$error = get_transient( self::TRANSIENT_LAST_ERROR );
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $error ? $error : __( 'Bulk import failed.', 'ewo-youtube-integration' ) ) . '</p></div>';
		}
	}

	/**
	 * Format import result.
	 *
	 * @param mixed $result Result transient.
	 * @return string
	 */
	private function format_result( $result ) {
		if ( ! is_array( $result ) ) {
			return __( 'Import completed.', 'ewo-youtube-integration' );
		}

		return sprintf(
			/* translators: 1: created count, 2: updated count, 3: total count. */
			__( 'Import complete: %1$d created, %2$d updated, %3$d total URLs parsed.', 'ewo-youtube-integration' ),
			(int) ( $result['created'] ?? 0 ),
			(int) ( $result['updated'] ?? 0 ),
			(int) ( $result['total'] ?? 0 )
		);
	}

	/**
	 * Store import error.
	 *
	 * @param string $message Error message.
	 * @return WP_Error
	 */
	private function store_error( $message ) {
		$message = sanitize_text_field( $message );
		set_transient( self::TRANSIENT_LAST_ERROR, $message, 30 * DAY_IN_SECONDS );

		return new WP_Error( 'ewo_youtube_bulk_import_error', $message );
	}

	/**
	 * Get page URL.
	 *
	 * @param array<string,string> $args Query args.
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

	/**
	 * Get the best available thumbnail URL.
	 *
	 * @param array<string,array<string,string>> $thumbnails Thumbnail data.
	 * @return string
	 */
	private function get_best_thumbnail_url( $thumbnails ) {
		foreach ( array( 'maxres', 'standard', 'high', 'medium', 'default' ) as $size ) {
			if ( ! empty( $thumbnails[ $size ]['url'] ) ) {
				return $thumbnails[ $size ]['url'];
			}
		}

		return '';
	}

	/**
	 * Convert YouTube publish date to a WordPress local post date.
	 *
	 * @param string $published_at ISO 8601 publish date.
	 * @return string
	 */
	private function get_post_date( $published_at ) {
		$timestamp = strtotime( $published_at );

		if ( ! $timestamp ) {
			return current_time( 'mysql' );
		}

		return get_date_from_gmt( gmdate( 'Y-m-d H:i:s', $timestamp ) );
	}
}
