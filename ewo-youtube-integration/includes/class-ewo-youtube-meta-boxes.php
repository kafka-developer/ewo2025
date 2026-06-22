<?php
/**
 * Admin metaboxes for YouTube content post types.
 *
 * @package EWO_YouTube_Integration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds and saves manual entry metaboxes for YouTube CPTs.
 */
class EWO_YouTube_Meta_Boxes {
	const NONCE_ACTION = 'ewo_youtube_save_meta_box';
	const NONCE_NAME   = 'ewo_youtube_meta_box_nonce';

	/**
	 * Initialize hooks.
	 */
	public function init() {
		add_action( 'add_meta_boxes', array( $this, 'register_meta_boxes' ), 10, 2 );
		add_action( 'add_meta_boxes_ewo_video', array( $this, 'add_video_meta_box' ) );
		add_action( 'add_meta_boxes_ewo_playlist', array( $this, 'add_playlist_meta_box' ) );
		add_action( 'save_post_ewo_video', array( $this, 'save_video_meta' ) );
		add_action( 'save_post_ewo_playlist', array( $this, 'save_playlist_meta' ) );
	}

	/**
	 * Register metaboxes from the generic hook as a compatibility path.
	 *
	 * @param string $post_type Post type slug.
	 * @param WP_Post|null $post Current post object.
	 */
	public function register_meta_boxes( $post_type, $post = null ) {
		if ( 'ewo_video' === $post_type ) {
			$this->add_video_meta_box();
		}

		if ( 'ewo_playlist' === $post_type ) {
			$this->add_playlist_meta_box();
		}
	}

	/**
	 * Register the video metabox on the ewo_video edit screen.
	 */
	public function add_video_meta_box() {
		add_meta_box(
			'ewo-youtube-video-details',
			esc_html__( 'YouTube Video Details', 'ewo-youtube-integration' ),
			array( $this, 'render_video_meta_box' ),
			'ewo_video',
			'normal',
			'high',
			array(
				'__block_editor_compatible_meta_box' => true,
			)
		);
	}

	/**
	 * Register the playlist metabox on the ewo_playlist edit screen.
	 */
	public function add_playlist_meta_box() {
		add_meta_box(
			'ewo-youtube-playlist-details',
			esc_html__( 'YouTube Playlist Details', 'ewo-youtube-integration' ),
			array( $this, 'render_playlist_meta_box' ),
			'ewo_playlist',
			'normal',
			'high',
			array(
				'__block_editor_compatible_meta_box' => true,
			)
		);
	}

	/**
	 * Render video metabox fields.
	 *
	 * @param WP_Post $post Current post.
	 */
	public function render_video_meta_box( $post ) {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

		$fields = array(
			'video_id'    => get_post_meta( $post->ID, 'ewo_youtube_video_id', true ),
			'url'         => get_post_meta( $post->ID, 'ewo_youtube_url', true ),
			'thumbnail'   => get_post_meta( $post->ID, 'ewo_youtube_thumbnail', true ),
			'published'   => get_post_meta( $post->ID, 'ewo_youtube_published_at', true ),
			'video_type'  => get_post_meta( $post->ID, 'ewo_youtube_video_type', true ),
			'hidden'      => get_post_meta( $post->ID, 'ewo_youtube_hidden', true ),
			'featured'    => get_post_meta( $post->ID, 'ewo_youtube_featured', true ),
			'sort_order'  => get_post_meta( $post->ID, 'ewo_youtube_sort_order', true ),
		);

		if ( '' === $fields['video_type'] ) {
			$fields['video_type'] = 'long_form';
		}

		$this->render_text_field( 'ewo_youtube_video_id', __( 'YouTube Video ID', 'ewo-youtube-integration' ), $fields['video_id'] );
		$this->render_url_field( 'ewo_youtube_url', __( 'YouTube Video URL', 'ewo-youtube-integration' ), $fields['url'] );
		$this->render_url_field( 'ewo_youtube_thumbnail', __( 'Thumbnail URL', 'ewo-youtube-integration' ), $fields['thumbnail'] );
		$this->render_date_field( 'ewo_youtube_published_at', __( 'Publish Date', 'ewo-youtube-integration' ), $fields['published'] );
		$this->render_select_field(
			'ewo_youtube_video_type',
			__( 'Video Type', 'ewo-youtube-integration' ),
			$fields['video_type'],
			array(
				'long_form' => __( 'Long-form', 'ewo-youtube-integration' ),
				'short'     => __( 'Short', 'ewo-youtube-integration' ),
			)
		);
		$this->render_select_field(
			'ewo_youtube_visibility',
			__( 'Visibility', 'ewo-youtube-integration' ),
			$fields['hidden'] ? 'hidden' : 'visible',
			array(
				'visible' => __( 'Visible', 'ewo-youtube-integration' ),
				'hidden'  => __( 'Hidden', 'ewo-youtube-integration' ),
			)
		);
		$this->render_select_field(
			'ewo_youtube_featured',
			__( 'Featured', 'ewo-youtube-integration' ),
			$fields['featured'] ? '1' : '0',
			array(
				'0' => __( 'No', 'ewo-youtube-integration' ),
				'1' => __( 'Yes', 'ewo-youtube-integration' ),
			)
		);
		$this->render_number_field( 'ewo_youtube_sort_order', __( 'Sort Order', 'ewo-youtube-integration' ), $fields['sort_order'] );
	}

	/**
	 * Render playlist metabox fields.
	 *
	 * @param WP_Post $post Current post.
	 */
	public function render_playlist_meta_box( $post ) {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

		$fields = array(
			'playlist_id' => get_post_meta( $post->ID, 'ewo_youtube_playlist_id', true ),
			'url'         => get_post_meta( $post->ID, 'ewo_youtube_playlist_url', true ),
			'thumbnail'   => get_post_meta( $post->ID, 'ewo_youtube_playlist_thumbnail', true ),
			'published'   => get_post_meta( $post->ID, 'ewo_youtube_playlist_published_at', true ),
			'hidden'      => get_post_meta( $post->ID, 'ewo_youtube_playlist_hidden', true ),
			'featured'    => get_post_meta( $post->ID, 'ewo_youtube_playlist_featured', true ),
			'sort_order'  => get_post_meta( $post->ID, 'ewo_youtube_playlist_sort_order', true ),
		);

		$this->render_text_field( 'ewo_youtube_playlist_id', __( 'YouTube Playlist ID', 'ewo-youtube-integration' ), $fields['playlist_id'] );
		$this->render_url_field( 'ewo_youtube_playlist_url', __( 'Playlist URL', 'ewo-youtube-integration' ), $fields['url'] );
		$this->render_url_field( 'ewo_youtube_playlist_thumbnail', __( 'Thumbnail URL', 'ewo-youtube-integration' ), $fields['thumbnail'] );
		$this->render_date_field( 'ewo_youtube_playlist_published_at', __( 'Publish Date', 'ewo-youtube-integration' ), $fields['published'] );
		$this->render_select_field(
			'ewo_youtube_playlist_visibility',
			__( 'Visibility', 'ewo-youtube-integration' ),
			$fields['hidden'] ? 'hidden' : 'visible',
			array(
				'visible' => __( 'Visible', 'ewo-youtube-integration' ),
				'hidden'  => __( 'Hidden', 'ewo-youtube-integration' ),
			)
		);
		$this->render_select_field(
			'ewo_youtube_playlist_featured',
			__( 'Featured', 'ewo-youtube-integration' ),
			$fields['featured'] ? '1' : '0',
			array(
				'0' => __( 'No', 'ewo-youtube-integration' ),
				'1' => __( 'Yes', 'ewo-youtube-integration' ),
			)
		);
		$this->render_number_field( 'ewo_youtube_playlist_sort_order', __( 'Sort Order', 'ewo-youtube-integration' ), $fields['sort_order'] );
	}

	/**
	 * Save video metadata.
	 *
	 * @param int $post_id Post ID.
	 */
	public function save_video_meta( $post_id ) {
		if ( ! $this->can_save( $post_id ) ) {
			return;
		}

		$video_type = $this->get_posted_select_value( 'ewo_youtube_video_type', array( 'long_form', 'short' ), 'long_form' );

		$this->save_text_meta( $post_id, 'ewo_youtube_video_id' );
		$this->save_url_meta( $post_id, 'ewo_youtube_url' );
		$this->save_url_meta( $post_id, 'ewo_youtube_thumbnail' );
		$this->save_text_meta( $post_id, 'ewo_youtube_published_at' );
		$this->sync_post_date( $post_id, 'ewo_youtube_published_at' );
		update_post_meta( $post_id, 'ewo_youtube_video_type', $video_type );
		$this->save_flag_meta( $post_id, 'ewo_youtube_hidden', 'ewo_youtube_visibility', 'hidden' );
		$this->save_flag_meta( $post_id, 'ewo_youtube_featured', 'ewo_youtube_featured', '1' );
		$this->save_int_meta( $post_id, 'ewo_youtube_sort_order' );

		if ( 'short' === $video_type ) {
			update_post_meta( $post_id, 'ewo_youtube_is_short', '1' );
		} else {
			delete_post_meta( $post_id, 'ewo_youtube_is_short' );
		}
	}

	/**
	 * Save playlist metadata.
	 *
	 * @param int $post_id Post ID.
	 */
	public function save_playlist_meta( $post_id ) {
		if ( ! $this->can_save( $post_id ) ) {
			return;
		}

		$this->save_text_meta( $post_id, 'ewo_youtube_playlist_id' );
		$this->save_url_meta( $post_id, 'ewo_youtube_playlist_url' );
		$this->save_url_meta( $post_id, 'ewo_youtube_playlist_thumbnail' );
		$this->save_text_meta( $post_id, 'ewo_youtube_playlist_published_at' );
		$this->sync_post_date( $post_id, 'ewo_youtube_playlist_published_at' );
		$this->save_flag_meta( $post_id, 'ewo_youtube_playlist_hidden', 'ewo_youtube_playlist_visibility', 'hidden' );
		$this->save_flag_meta( $post_id, 'ewo_youtube_playlist_featured', 'ewo_youtube_playlist_featured', '1' );
		$this->save_int_meta( $post_id, 'ewo_youtube_playlist_sort_order' );

		$playlist_id = get_post_meta( $post_id, 'ewo_youtube_playlist_id', true );
		$thumbnail   = get_post_meta( $post_id, 'ewo_youtube_playlist_thumbnail', true );
		$url         = get_post_meta( $post_id, 'ewo_youtube_playlist_url', true );

		update_post_meta( $post_id, 'ewo_youtube_playlist_title', get_the_title( $post_id ) );
		update_post_meta( $post_id, 'ewo_youtube_playlist_description', get_post_field( 'post_content', $post_id ) );
		update_post_meta( $post_id, 'ewo_youtube_playlist_id', $playlist_id );
		update_post_meta( $post_id, 'ewo_youtube_playlist_thumbnail', $thumbnail );
		update_post_meta( $post_id, 'ewo_youtube_playlist_url', $url );
	}

	/**
	 * Determine whether metadata can be saved.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	private function can_save( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return false;
		}

		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return false;
		}

		if ( ! isset( $_POST[ self::NONCE_NAME ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION ) ) {
			return false;
		}

		return current_user_can( 'edit_post', $post_id );
	}

	/**
	 * Render a text input.
	 *
	 * @param string $name  Field name.
	 * @param string $label Field label.
	 * @param string $value Field value.
	 */
	private function render_text_field( $name, $label, $value ) {
		$this->render_input_field( $name, $label, $value, 'text' );
	}

	/**
	 * Render a URL input.
	 *
	 * @param string $name  Field name.
	 * @param string $label Field label.
	 * @param string $value Field value.
	 */
	private function render_url_field( $name, $label, $value ) {
		$this->render_input_field( $name, $label, $value, 'url' );
	}

	/**
	 * Render a date input.
	 *
	 * @param string $name  Field name.
	 * @param string $label Field label.
	 * @param string $value Field value.
	 */
	private function render_date_field( $name, $label, $value ) {
		$this->render_input_field( $name, $label, $this->format_date_value( $value ), 'date' );
	}

	/**
	 * Render a number input.
	 *
	 * @param string $name  Field name.
	 * @param string $label Field label.
	 * @param string $value Field value.
	 */
	private function render_number_field( $name, $label, $value ) {
		$this->render_input_field( $name, $label, $value, 'number' );
	}

	/**
	 * Render a basic input field.
	 *
	 * @param string $name  Field name.
	 * @param string $label Field label.
	 * @param string $value Field value.
	 * @param string $type  Input type.
	 */
	private function render_input_field( $name, $label, $value, $type ) {
		?>
		<p>
			<label for="<?php echo esc_attr( $name ); ?>"><strong><?php echo esc_html( $label ); ?></strong></label><br>
			<input id="<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" type="<?php echo esc_attr( $type ); ?>" class="widefat" value="<?php echo esc_attr( $value ); ?>">
		</p>
		<?php
	}

	/**
	 * Render a select field.
	 *
	 * @param string $name    Field name.
	 * @param string $label   Field label.
	 * @param string $value   Current value.
	 * @param array  $options Select options.
	 */
	private function render_select_field( $name, $label, $value, $options ) {
		?>
		<p>
			<label for="<?php echo esc_attr( $name ); ?>"><strong><?php echo esc_html( $label ); ?></strong></label><br>
			<select id="<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" class="widefat">
				<?php foreach ( $options as $option_value => $option_label ) : ?>
					<option value="<?php echo esc_attr( $option_value ); ?>" <?php selected( $value, (string) $option_value ); ?>>
						<?php echo esc_html( $option_label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</p>
		<?php
	}

	/**
	 * Save text metadata.
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $meta_key Meta key.
	 */
	private function save_text_meta( $post_id, $meta_key ) {
		$value = isset( $_POST[ $meta_key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $meta_key ] ) ) : '';
		$this->update_or_delete_meta( $post_id, $meta_key, $value );
	}

	/**
	 * Save URL metadata.
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $meta_key Meta key.
	 */
	private function save_url_meta( $post_id, $meta_key ) {
		$value = isset( $_POST[ $meta_key ] ) ? esc_url_raw( wp_unslash( $_POST[ $meta_key ] ) ) : '';
		$this->update_or_delete_meta( $post_id, $meta_key, $value );
	}

	/**
	 * Save integer metadata.
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $meta_key Meta key.
	 */
	private function save_int_meta( $post_id, $meta_key ) {
		$raw_value = isset( $_POST[ $meta_key ] ) ? trim( sanitize_text_field( wp_unslash( $_POST[ $meta_key ] ) ) ) : '';
		$value     = '' === $raw_value ? '' : (string) intval( $raw_value );

		$this->update_or_delete_meta( $post_id, $meta_key, $value );
	}

	/**
	 * Save a boolean flag from a select field.
	 *
	 * @param int    $post_id        Post ID.
	 * @param string $meta_key       Meta key.
	 * @param string $posted_key     POST key.
	 * @param string $enabled_value  Value that enables the flag.
	 */
	private function save_flag_meta( $post_id, $meta_key, $posted_key, $enabled_value ) {
		$value = isset( $_POST[ $posted_key ] ) ? sanitize_key( wp_unslash( $_POST[ $posted_key ] ) ) : '';

		if ( $enabled_value === $value ) {
			update_post_meta( $post_id, $meta_key, '1' );
			return;
		}

		delete_post_meta( $post_id, $meta_key );
	}

	/**
	 * Get a sanitized select value.
	 *
	 * @param string $key     POST key.
	 * @param array  $allowed Allowed values.
	 * @param string $default Default value.
	 * @return string
	 */
	private function get_posted_select_value( $key, $allowed, $default ) {
		$value = isset( $_POST[ $key ] ) ? sanitize_key( wp_unslash( $_POST[ $key ] ) ) : $default;

		return in_array( $value, $allowed, true ) ? $value : $default;
	}


	/**
	 * Sync the WordPress post date from a saved publish date field.
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $meta_key Publish date meta key.
	 */
	private function sync_post_date( $post_id, $meta_key ) {
		$date_value = get_post_meta( $post_id, $meta_key, true );
		$timestamp  = strtotime( $date_value );

		if ( ! $timestamp ) {
			return;
		}

		remove_action( 'save_post_ewo_video', array( $this, 'save_video_meta' ) );
		remove_action( 'save_post_ewo_playlist', array( $this, 'save_playlist_meta' ) );

		$post_date = get_date_from_gmt( gmdate( 'Y-m-d H:i:s', $timestamp ) );
		wp_update_post(
			wp_slash(
				array(
					'ID'            => $post_id,
					'post_date'     => $post_date,
					'post_date_gmt' => get_gmt_from_date( $post_date ),
				)
			)
		);

		add_action( 'save_post_ewo_video', array( $this, 'save_video_meta' ) );
		add_action( 'save_post_ewo_playlist', array( $this, 'save_playlist_meta' ) );
	}

	/**
	 * Update or remove a meta value.
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $meta_key Meta key.
	 * @param string $value    Meta value.
	 */
	private function update_or_delete_meta( $post_id, $meta_key, $value ) {
		if ( '' === $value ) {
			delete_post_meta( $post_id, $meta_key );
			return;
		}

		update_post_meta( $post_id, $meta_key, $value );
	}

	/**
	 * Convert stored dates into an HTML date value.
	 *
	 * @param string $value Stored date value.
	 * @return string
	 */
	private function format_date_value( $value ) {
		if ( '' === $value ) {
			return '';
		}

		$timestamp = strtotime( $value );

		if ( ! $timestamp ) {
			return '';
		}

		return gmdate( 'Y-m-d', $timestamp );
	}
}
