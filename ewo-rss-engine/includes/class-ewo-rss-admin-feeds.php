<?php
/**
 * Feed admin controls: native status management + bulk cleanup tools.
 *
 * Adds a status meta box (Enabled / Disabled / Hidden / Deleted) and per-feed
 * actions to both native sources and Feedzy jobs, plus list-table bulk actions.
 *
 * @package EWO_RSS_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Feed status + cleanup admin.
 */
class EWO_RSS_Admin_Feeds {
	const ACTION      = 'ewo_feed_action';
	const NONCE_SAVE  = 'ewo_feed_status_save';
	const NONCE_FIELD = 'ewo_feed_status_nonce';

	/**
	 * Register hooks.
	 */
	public function init() {
		foreach ( EWO_RSS_Feed::types() as $cpt ) {
			add_action( 'add_meta_boxes_' . $cpt, array( $this, 'add_meta_box' ) );
			add_action( 'save_post_' . $cpt, array( $this, 'save_status' ), 10, 2 );
			add_filter( 'bulk_actions-edit-' . $cpt, array( $this, 'bulk_actions' ) );
			add_filter( 'handle_bulk_actions-edit-' . $cpt, array( $this, 'handle_bulk' ), 10, 3 );
		}
		add_action( 'admin_post_' . self::ACTION, array( $this, 'handle_action' ) );
		add_action( 'admin_notices', array( $this, 'notices' ) );
	}

	/* ----- Meta box ----- */

	/**
	 * Register the status meta box.
	 */
	public function add_meta_box() {
		add_meta_box(
			'ewo_feed_status',
			__( 'EWO Feed Control', 'ewo-rss-engine' ),
			array( $this, 'render_meta_box' ),
			null,
			'side',
			'high'
		);
	}

	/**
	 * Render the status meta box.
	 *
	 * @param WP_Post $post Feed post.
	 */
	public function render_meta_box( $post ) {
		$metrics = EWO_RSS_Feed::metrics( $post->ID );
		$count   = count( $this->feed_post_ids( $post->ID ) );

		wp_nonce_field( self::NONCE_SAVE, self::NONCE_FIELD );

		$labels = array(
			EWO_RSS_Feed::STATUS_ENABLED  => __( 'Enabled — import & display', 'ewo-rss-engine' ),
			EWO_RSS_Feed::STATUS_DISABLED => __( 'Disabled — stop imports, hide items', 'ewo-rss-engine' ),
			EWO_RSS_Feed::STATUS_HIDDEN   => __( 'Hidden — keep importing, hide items', 'ewo-rss-engine' ),
			EWO_RSS_Feed::STATUS_DELETED  => __( 'Deleted — remove config (keep posts)', 'ewo-rss-engine' ),
		);
		?>
		<p>
			<label for="ewo_feed_status"><strong><?php esc_html_e( 'Status', 'ewo-rss-engine' ); ?></strong></label>
			<select id="ewo_feed_status" name="ewo_feed_status" style="width:100%;">
				<?php foreach ( $labels as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $metrics['status'], $value ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<p style="margin:0 0 6px;">
			<strong><?php esc_html_e( 'Health:', 'ewo-rss-engine' ); ?></strong>
			<?php echo wp_kses_post( self::health_badge( $metrics['health'] ) ); ?>
			&middot; <?php echo esc_html( sprintf( /* translators: %d count */ __( '%d posts', 'ewo-rss-engine' ), $count ) ); ?>
		</p>
		<p class="description" style="margin-top:0;">
			<?php
			printf(
				/* translators: 1: last success, 2: consecutive failures */
				esc_html__( 'Last success: %1$s · Consecutive failures: %2$d', 'ewo-rss-engine' ),
				esc_html( $metrics['last_success'] ? $metrics['last_success'] : '—' ),
				(int) $metrics['consecutive_failures']
			);
			?>
		</p>
		<?php if ( $count > 0 ) : ?>
			<hr />
			<p><a class="button" href="<?php echo esc_url( $this->action_url( $post->ID, 'hide' ) ); ?>"><?php esc_html_e( 'Hide all posts from feed', 'ewo-rss-engine' ); ?></a></p>
			<p><a class="button" href="<?php echo esc_url( $this->action_url( $post->ID, 'unhide' ) ); ?>"><?php esc_html_e( 'Unhide all posts from feed', 'ewo-rss-engine' ); ?></a></p>
			<p><a class="button" href="<?php echo esc_url( $this->action_url( $post->ID, 'recalc' ) ); ?>"><?php esc_html_e( 'Recalculate feed ownership', 'ewo-rss-engine' ); ?></a></p>
			<p>
				<a class="button button-link-delete" href="<?php echo esc_url( $this->action_url( $post->ID, 'delete_posts' ) ); ?>"
					onclick="return confirm('<?php echo esc_js( __( 'Move all imported posts from this feed to Trash?', 'ewo-rss-engine' ) ); ?>');">
					<?php esc_html_e( 'Delete imported posts (Trash)', 'ewo-rss-engine' ); ?>
				</a>
			</p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Persist the status from the meta box.
	 *
	 * @param int     $post_id Feed ID.
	 * @param WP_Post $post    Feed post.
	 */
	public function save_status( $post_id, $post ) {
		if ( ! isset( $_POST[ self::NONCE_FIELD ] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ), self::NONCE_SAVE ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$status = isset( $_POST['ewo_feed_status'] ) ? sanitize_key( wp_unslash( $_POST['ewo_feed_status'] ) ) : '';
		EWO_RSS_Feed::set_status( $post_id, $status );
	}

	/* ----- Per-feed actions ----- */

	/**
	 * Build a nonced action URL.
	 *
	 * @param int    $feed_id Feed ID.
	 * @param string $do      Action key.
	 * @return string
	 */
	protected function action_url( $feed_id, $do ) {
		return wp_nonce_url(
			admin_url( 'admin-post.php?action=' . self::ACTION . '&feed=' . (int) $feed_id . '&do=' . rawurlencode( $do ) ),
			self::ACTION . '_' . (int) $feed_id
		);
	}

	/**
	 * Handle a per-feed action.
	 */
	public function handle_action() {
		$feed_id = isset( $_GET['feed'] ) ? absint( wp_unslash( $_GET['feed'] ) ) : 0;
		$do      = isset( $_GET['do'] ) ? sanitize_key( wp_unslash( $_GET['do'] ) ) : '';

		check_admin_referer( self::ACTION . '_' . $feed_id );
		if ( ! current_user_can( 'edit_post', $feed_id ) ) {
			wp_die( esc_html__( 'Not allowed.', 'ewo-rss-engine' ) );
		}

		$result = $this->run_action( $feed_id, $do );

		wp_safe_redirect(
			add_query_arg(
				array(
					'post'         => $feed_id,
					'action'       => 'edit',
					'ewo_feed_msg' => rawurlencode( $result ),
				),
				admin_url( 'post.php' )
			)
		);
		exit;
	}

	/**
	 * Run a named action against a feed.
	 *
	 * @param int    $feed_id Feed ID.
	 * @param string $do      Action.
	 * @return string Result message.
	 */
	public function run_action( $feed_id, $do ) {
		switch ( $do ) {
			case 'hide':
				EWO_RSS_Feed::set_status( $feed_id, EWO_RSS_Feed::STATUS_HIDDEN );
				return __( 'Feed hidden.', 'ewo-rss-engine' );

			case 'unhide':
				EWO_RSS_Feed::set_status( $feed_id, EWO_RSS_Feed::STATUS_ENABLED );
				return __( 'Feed enabled.', 'ewo-rss-engine' );

			case 'delete_posts':
				$n = $this->trash_feed_posts( $feed_id );
				return sprintf( /* translators: %d count */ __( 'Trashed %d posts.', 'ewo-rss-engine' ), $n );

			case 'recalc':
				$n = $this->recalc_feed( $feed_id );
				return sprintf( /* translators: %d count */ __( 'Recalculated %d posts.', 'ewo-rss-engine' ), $n );

			case 'dedupe':
				$n = count( EWO_RSS_Dedup::resolve_all( false ) );
				return sprintf( /* translators: %d count */ __( 'Removed %d duplicates.', 'ewo-rss-engine' ), $n );
		}

		return __( 'No action.', 'ewo-rss-engine' );
	}

	/* ----- Bulk actions ----- */

	/**
	 * Add bulk actions to feed lists.
	 *
	 * @param array<string,string> $actions Actions.
	 * @return array<string,string>
	 */
	public function bulk_actions( $actions ) {
		$actions['ewo_hide']         = __( 'EWO: Hide feed', 'ewo-rss-engine' );
		$actions['ewo_unhide']       = __( 'EWO: Enable feed', 'ewo-rss-engine' );
		$actions['ewo_delete_posts'] = __( 'EWO: Delete imported posts', 'ewo-rss-engine' );
		$actions['ewo_recalc']       = __( 'EWO: Recalculate ownership', 'ewo-rss-engine' );

		return $actions;
	}

	/**
	 * Handle feed-list bulk actions.
	 *
	 * @param string $redirect Redirect URL.
	 * @param string $action   Action.
	 * @param int[]  $feed_ids Feed IDs.
	 * @return string
	 */
	public function handle_bulk( $redirect, $action, $feed_ids ) {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return $redirect;
		}

		$map = array(
			'ewo_hide'         => 'hide',
			'ewo_unhide'       => 'unhide',
			'ewo_delete_posts' => 'delete_posts',
			'ewo_recalc'       => 'recalc',
		);
		if ( ! isset( $map[ $action ] ) ) {
			return $redirect;
		}

		$n = 0;
		foreach ( (array) $feed_ids as $feed_id ) {
			$this->run_action( (int) $feed_id, $map[ $action ] );
			++$n;
		}

		return add_query_arg( 'ewo_feed_bulk', $n, $redirect );
	}

	/* ----- Helpers ----- */

	/**
	 * Imported post IDs for a feed (canonical + legacy linkage).
	 *
	 * @param int $feed_id Feed ID.
	 * @return int[]
	 */
	public function feed_post_ids( $feed_id ) {
		return get_posts(
			array(
				'post_type'      => 'any',
				'post_status'    => array( 'publish', 'pending', 'draft', 'future', 'private' ),
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'relation' => 'OR',
					array(
						'key'   => EWO_RSS_Meta::FEED_ID,
						'value' => (int) $feed_id,
					),
					array(
						'key'   => 'feedzy_job',
						'value' => (int) $feed_id,
					),
				),
			)
		);
	}

	/**
	 * Trash a feed's imported posts.
	 *
	 * @param int $feed_id Feed ID.
	 * @return int
	 */
	protected function trash_feed_posts( $feed_id ) {
		$n = 0;
		foreach ( $this->feed_post_ids( $feed_id ) as $post_id ) {
			if ( wp_trash_post( $post_id ) ) {
				++$n;
			}
		}
		if ( function_exists( 'ewo_rss_engine_flush_feed_cache' ) ) {
			ewo_rss_engine_flush_feed_cache();
		}

		return $n;
	}

	/**
	 * Re-stamp canonical attribution + flags on a feed's posts.
	 *
	 * @param int $feed_id Feed ID.
	 * @return int
	 */
	protected function recalc_feed( $feed_id ) {
		$n = 0;
		foreach ( $this->feed_post_ids( $feed_id ) as $post_id ) {
			$article_url = EWO_RSS_Meta::article_url( $post_id );
			EWO_RSS_Meta::stamp(
				$post_id,
				array(
					'feed_id'     => (int) $feed_id,
					'feed_name'   => EWO_RSS_Feed::name( $feed_id ),
					'feed_url'    => EWO_RSS_Feed::url( $feed_id ),
					'article_url' => $article_url,
					'importer'    => (string) get_post_meta( $post_id, EWO_RSS_Meta::IMPORTER, true ),
					'guid'        => (string) get_post_meta( $post_id, EWO_RSS_Meta::GUID, true ),
					'imported_at' => (string) get_post_meta( $post_id, EWO_RSS_Meta::IMPORTED_AT, true ),
				)
			);
			EWO_RSS_Meta::stamp_flags( $post_id, (string) get_post_field( 'post_content', $post_id ), $article_url );
			++$n;
		}

		return $n;
	}

	/**
	 * Health badge HTML.
	 *
	 * @param string $health Health value.
	 * @return string
	 */
	public static function health_badge( $health ) {
		$colors = array(
			EWO_RSS_Feed::HEALTH_HEALTHY => '#1a7f37',
			EWO_RSS_Feed::HEALTH_WARNING => '#bd8600',
			EWO_RSS_Feed::HEALTH_FAILING => '#b32d2e',
		);
		$color = isset( $colors[ $health ] ) ? $colors[ $health ] : '#666';

		return '<span style="color:' . esc_attr( $color ) . ';font-weight:600;">' . esc_html( ucfirst( $health ) ) . '</span>';
	}

	/**
	 * Admin notices for action results.
	 */
	public function notices() {
		if ( isset( $_GET['ewo_feed_msg'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				esc_html( sanitize_text_field( wp_unslash( $_GET['ewo_feed_msg'] ) ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			);
		}
		if ( isset( $_GET['ewo_feed_bulk'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				esc_html( sprintf( /* translators: %d feeds */ __( 'EWO: processed %d feed(s).', 'ewo-rss-engine' ), absint( wp_unslash( $_GET['ewo_feed_bulk'] ) ) ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			);
		}
	}
}
