<?php
/**
 * Feed sources: a custom post type plus its meta box.
 *
 * @package EWO_RSS_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages the feed-source post type and per-source settings.
 */
class EWO_RSS_Sources {
	const POST_TYPE = 'ewo_rss_source';

	const META_FEED_URL    = '_ewo_rss_feed_url';
	const META_ENABLED     = '_ewo_rss_enabled';
	const META_CATEGORY    = '_ewo_rss_category';
	const META_POST_STATUS = '_ewo_rss_post_status';
	const META_MAX_ITEMS   = '_ewo_rss_max_items';
	const META_LAST_RUN    = '_ewo_rss_last_run';

	const NONCE_ACTION = 'ewo_rss_save_source';
	const NONCE_FIELD  = 'ewo_rss_source_nonce';

	/**
	 * Register runtime hooks.
	 */
	public function init() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'add_meta_boxes_' . self::POST_TYPE, array( $this, 'add_meta_box' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( $this, 'save_meta' ), 10, 2 );
	}

	/**
	 * Register the feed-source post type.
	 */
	public function register_post_type() {
		$labels = array(
			'name'               => __( 'Feed Sources', 'ewo-rss-engine' ),
			'singular_name'      => __( 'Feed Source', 'ewo-rss-engine' ),
			'add_new'            => __( 'Add New', 'ewo-rss-engine' ),
			'add_new_item'       => __( 'Add New Feed Source', 'ewo-rss-engine' ),
			'edit_item'          => __( 'Edit Feed Source', 'ewo-rss-engine' ),
			'new_item'           => __( 'New Feed Source', 'ewo-rss-engine' ),
			'view_item'          => __( 'View Feed Source', 'ewo-rss-engine' ),
			'search_items'       => __( 'Search Feed Sources', 'ewo-rss-engine' ),
			'not_found'          => __( 'No feed sources found.', 'ewo-rss-engine' ),
			'not_found_in_trash' => __( 'No feed sources found in Trash.', 'ewo-rss-engine' ),
			'menu_name'          => __( 'Feed Sources', 'ewo-rss-engine' ),
		);

		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => $labels,
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => 'ewo-rss-engine',
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
				'hierarchical'        => false,
				'supports'            => array( 'title' ),
				'exclude_from_search' => true,
				'publicly_queryable'  => false,
				'has_archive'         => false,
				'rewrite'             => false,
			)
		);
	}

	/**
	 * Register the source settings meta box.
	 */
	public function add_meta_box() {
		add_meta_box(
			'ewo_rss_source_settings',
			__( 'Feed Settings', 'ewo-rss-engine' ),
			array( $this, 'render_meta_box' ),
			self::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Render the source settings meta box.
	 *
	 * @param WP_Post $post Current source post.
	 */
	public function render_meta_box( $post ) {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );

		$feed_url    = (string) get_post_meta( $post->ID, self::META_FEED_URL, true );
		$enabled     = (string) get_post_meta( $post->ID, self::META_ENABLED, true );
		$category    = (int) get_post_meta( $post->ID, self::META_CATEGORY, true );
		$post_status = (string) get_post_meta( $post->ID, self::META_POST_STATUS, true );
		$max_items   = (int) get_post_meta( $post->ID, self::META_MAX_ITEMS, true );

		if ( '' === $post_status ) {
			$post_status = 'draft';
		}
		if ( $max_items < 1 ) {
			$max_items = 10;
		}
		?>
		<p>
			<label for="ewo_rss_feed_url"><strong><?php esc_html_e( 'Feed URL', 'ewo-rss-engine' ); ?></strong></label><br />
			<input type="url" class="widefat" id="ewo_rss_feed_url" name="ewo_rss_feed_url"
				value="<?php echo esc_attr( $feed_url ); ?>" placeholder="https://example.substack.com/feed" />
			<span class="description"><?php esc_html_e( 'The RSS/Atom feed to import from.', 'ewo-rss-engine' ); ?></span>
		</p>

		<p>
			<label>
				<input type="checkbox" id="ewo_rss_enabled" name="ewo_rss_enabled" value="1" <?php checked( '1', $enabled ); ?> />
				<?php esc_html_e( 'Enabled (include in scheduled imports)', 'ewo-rss-engine' ); ?>
			</label>
		</p>

		<p>
			<label for="ewo_rss_category"><strong><?php esc_html_e( 'Target Category', 'ewo-rss-engine' ); ?></strong></label><br />
			<?php
			wp_dropdown_categories(
				array(
					'show_option_none'  => __( '— Default category —', 'ewo-rss-engine' ),
					'option_none_value' => 0,
					'hide_empty'        => false,
					'name'              => 'ewo_rss_category',
					'id'                => 'ewo_rss_category',
					'selected'          => $category,
				)
			);
			?>
			<br /><span class="description"><?php esc_html_e( 'Imported items are created as Analysis posts in this category.', 'ewo-rss-engine' ); ?></span>
		</p>

		<p>
			<label for="ewo_rss_post_status"><strong><?php esc_html_e( 'Imported Post Status', 'ewo-rss-engine' ); ?></strong></label><br />
			<select id="ewo_rss_post_status" name="ewo_rss_post_status">
				<option value="draft" <?php selected( 'draft', $post_status ); ?>><?php esc_html_e( 'Draft', 'ewo-rss-engine' ); ?></option>
				<option value="publish" <?php selected( 'publish', $post_status ); ?>><?php esc_html_e( 'Published', 'ewo-rss-engine' ); ?></option>
				<option value="pending" <?php selected( 'pending', $post_status ); ?>><?php esc_html_e( 'Pending Review', 'ewo-rss-engine' ); ?></option>
			</select>
		</p>

		<p>
			<label for="ewo_rss_max_items"><strong><?php esc_html_e( 'Max Items Per Run', 'ewo-rss-engine' ); ?></strong></label><br />
			<input type="number" min="1" max="100" step="1" id="ewo_rss_max_items" name="ewo_rss_max_items" value="<?php echo esc_attr( (string) $max_items ); ?>" />
		</p>
		<?php
	}

	/**
	 * Persist source settings on save.
	 *
	 * @param int     $post_id Source post ID.
	 * @param WP_Post $post    Source post object.
	 */
	public function save_meta( $post_id, $post ) {
		if ( ! isset( $_POST[ self::NONCE_FIELD ] ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) );
		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$feed_url = isset( $_POST['ewo_rss_feed_url'] ) ? esc_url_raw( wp_unslash( $_POST['ewo_rss_feed_url'] ) ) : '';
		update_post_meta( $post_id, self::META_FEED_URL, $feed_url );

		$enabled = isset( $_POST['ewo_rss_enabled'] ) ? '1' : '';
		update_post_meta( $post_id, self::META_ENABLED, $enabled );

		$category = isset( $_POST['ewo_rss_category'] ) ? absint( wp_unslash( $_POST['ewo_rss_category'] ) ) : 0;
		update_post_meta( $post_id, self::META_CATEGORY, $category );

		$allowed_status = array( 'draft', 'publish', 'pending' );
		$post_status    = isset( $_POST['ewo_rss_post_status'] ) ? sanitize_key( wp_unslash( $_POST['ewo_rss_post_status'] ) ) : 'draft';
		if ( ! in_array( $post_status, $allowed_status, true ) ) {
			$post_status = 'draft';
		}
		update_post_meta( $post_id, self::META_POST_STATUS, $post_status );

		$max_items = isset( $_POST['ewo_rss_max_items'] ) ? absint( wp_unslash( $_POST['ewo_rss_max_items'] ) ) : 10;
		$max_items = max( 1, min( 100, $max_items ) );
		update_post_meta( $post_id, self::META_MAX_ITEMS, $max_items );
	}

	/**
	 * Get all published source IDs.
	 *
	 * @return int[]
	 */
	public function get_all_sources() {
		return get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);
	}

	/**
	 * Get the source IDs that are enabled and have a feed URL.
	 *
	 * @return int[]
	 */
	public function get_active_sources() {
		$active = array();

		foreach ( $this->get_all_sources() as $source_id ) {
			$enabled  = (string) get_post_meta( $source_id, self::META_ENABLED, true );
			$feed_url = (string) get_post_meta( $source_id, self::META_FEED_URL, true );

			if ( '1' === $enabled && '' !== $feed_url ) {
				$active[] = (int) $source_id;
			}
		}

		return $active;
	}

	/**
	 * Get a normalized settings array for a source.
	 *
	 * @param int $source_id Source post ID.
	 * @return array<string,mixed>
	 */
	public function get_settings( $source_id ) {
		$source_id = (int) $source_id;

		$post_status = (string) get_post_meta( $source_id, self::META_POST_STATUS, true );
		$max_items   = (int) get_post_meta( $source_id, self::META_MAX_ITEMS, true );

		return array(
			'id'          => $source_id,
			'name'        => get_the_title( $source_id ),
			'feed_url'    => (string) get_post_meta( $source_id, self::META_FEED_URL, true ),
			'enabled'     => '1' === (string) get_post_meta( $source_id, self::META_ENABLED, true ),
			'category'    => (int) get_post_meta( $source_id, self::META_CATEGORY, true ),
			'post_status' => '' !== $post_status ? $post_status : 'draft',
			'max_items'   => $max_items > 0 ? $max_items : 10,
			'last_run'    => (string) get_post_meta( $source_id, self::META_LAST_RUN, true ),
		);
	}

	/**
	 * Record the last-run timestamp for a source.
	 *
	 * @param int $source_id Source post ID.
	 */
	public function mark_run( $source_id ) {
		update_post_meta( (int) $source_id, self::META_LAST_RUN, current_time( 'mysql' ) );
	}
}
