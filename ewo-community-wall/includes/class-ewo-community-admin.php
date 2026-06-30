<?php
/**
 * Admin UI controller for EWO Community Wall.
 *
 * Manages community posts and categories entirely through custom admin pages.
 * Never opens the default WordPress editor or taxonomy admin screens.
 *
 * @package EWO_Community_Wall
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EWO_Community_Admin {

	const MENU_SLUG     = 'ewo-community-wall';
	const ADD_SLUG      = 'ewo-community-wall-add';
	const CAT_SLUG      = 'ewo-community-wall-cats';
	const CPT           = 'ewo_community_post';
	const TAX           = 'ewo_cw_cat';
	const CAP           = 'manage_options';
	const NONCE_SAVE    = 'ewo_cw_save';
	const NONCE_DELETE  = 'ewo_cw_delete';
	const NONCE_CAT_SAVE   = 'ewo_cw_cat_save';
	const NONCE_CAT_DELETE = 'ewo_cw_cat_delete';
	const PER_PAGE      = 15;

	const META_AUTHOR     = '_ewo_cw_author_name';
	const META_VISIBILITY = '_ewo_cw_visibility';

	protected $hook = '';

	public function init() {
		add_action( 'admin_menu',            array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_post_ewo_cw_save',       array( $this, 'handle_save' ) );
		add_action( 'admin_post_ewo_cw_delete',     array( $this, 'handle_delete' ) );
		add_action( 'admin_post_ewo_cw_cat_save',   array( $this, 'handle_cat_save' ) );
		add_action( 'admin_post_ewo_cw_cat_delete', array( $this, 'handle_cat_delete' ) );
		add_action( 'admin_notices',                array( $this, 'render_notice' ) );
		add_action( 'init',                         array( $this, 'maybe_migrate_categories' ), 20 );
	}

	/* -------------------------------------------------------------------------
	   Menu
	   ---------------------------------------------------------------------- */

	public function register_menu() {
		$this->hook = (string) add_menu_page(
			__( 'Community Wall', 'ewo-community-wall' ),
			__( 'Community Wall', 'ewo-community-wall' ),
			self::CAP,
			self::MENU_SLUG,
			array( $this, 'dispatch' ),
			'dashicons-format-status',
			26
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'All Posts', 'ewo-community-wall' ),
			__( 'All Posts', 'ewo-community-wall' ),
			self::CAP,
			self::MENU_SLUG,
			array( $this, 'dispatch' )
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Add Community Post', 'ewo-community-wall' ),
			__( '+ Add Post', 'ewo-community-wall' ),
			self::CAP,
			self::ADD_SLUG,
			array( $this, 'render_add' )
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Categories', 'ewo-community-wall' ),
			__( 'Categories', 'ewo-community-wall' ),
			self::CAP,
			self::CAT_SLUG,
			array( $this, 'dispatch_cats' )
		);
	}

	public function enqueue_assets( $hook ) {
		if ( false === strpos( $hook, 'ewo-community-wall' ) ) {
			return;
		}
		wp_enqueue_style( 'ewo-community-wall-admin', EWO_CW_URL . 'assets/css/admin.css', array(), EWO_CW_VERSION );
		wp_enqueue_media();
	}

	/* -------------------------------------------------------------------------
	   Dispatch — Posts
	   ---------------------------------------------------------------------- */

	public function dispatch() {
		if ( ! current_user_can( self::CAP ) ) {
			return;
		}
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
		$id     = isset( $_GET['id'] )     ? absint( wp_unslash( $_GET['id'] ) )           : 0;
		// phpcs:enable
		if ( 'edit' === $action ) {
			$this->render_edit( $id );
		} else {
			$this->render_list();
		}
	}

	/* -------------------------------------------------------------------------
	   Posts — List
	   ---------------------------------------------------------------------- */

	public function render_list() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$filter_status  = isset( $_GET['filter_status'] )  ? sanitize_key( wp_unslash( $_GET['filter_status'] ) )         : '';
		$filter_cat_id  = isset( $_GET['filter_cat'] )     ? absint( wp_unslash( $_GET['filter_cat'] ) )                  : 0;
		$filter_search  = isset( $_GET['filter_search'] )  ? sanitize_text_field( wp_unslash( $_GET['filter_search'] ) )  : '';
		$paged          = isset( $_GET['paged'] )          ? max( 1, absint( wp_unslash( $_GET['paged'] ) ) )             : 1;
		// phpcs:enable

		$query_args = array(
			'post_type'      => self::CPT,
			'post_status'    => $this->status_filter_to_wp( $filter_status ),
			'posts_per_page' => self::PER_PAGE,
			'paged'          => $paged,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);
		if ( '' !== $filter_search ) {
			$query_args['s'] = $filter_search;
		}
		if ( $filter_cat_id > 0 ) {
			$query_args['tax_query'] = array(
				array(
					'taxonomy' => self::TAX,
					'field'    => 'term_id',
					'terms'    => $filter_cat_id,
				),
			);
		}

		$q     = new WP_Query( $query_args );
		$posts = $q->posts;
		$total = $q->found_posts;
		$pages = (int) ceil( $total / self::PER_PAGE );

		$count_all       = wp_count_posts( self::CPT );
		$count_published = (int) ( $count_all->publish ?? 0 );
		$count_draft     = (int) ( $count_all->draft ?? 0 );
		$count_hidden    = (int) ( $count_all->private ?? 0 );
		$count_total     = $count_published + $count_draft + $count_hidden;

		$all_cats = get_terms( array( 'taxonomy' => self::TAX, 'hide_empty' => false ) );
		if ( is_wp_error( $all_cats ) ) {
			$all_cats = array();
		}

		$add_url  = admin_url( 'admin.php?page=' . self::ADD_SLUG );
		$base_url = admin_url( 'admin.php?page=' . self::MENU_SLUG );
		?>
		<div class="ewo-cw-wrap">

			<div class="ewo-cw-page-header">
				<div>
					<h1 class="ewo-cw-page-title"><?php esc_html_e( 'Community Wall', 'ewo-community-wall' ); ?></h1>
					<p class="ewo-cw-page-sub"><?php esc_html_e( 'Manage public community posts shown on the website.', 'ewo-community-wall' ); ?></p>
				</div>
				<a href="<?php echo esc_url( $add_url ); ?>" class="ewo-cw-btn ewo-cw-btn--gold">
					+ <?php esc_html_e( 'Add Community Post', 'ewo-community-wall' ); ?>
				</a>
			</div>

			<div class="ewo-cw-metrics">
				<?php foreach ( array(
					array( 'label' => __( 'Total Posts', 'ewo-community-wall' ),  'value' => $count_total,     'icon' => 'dashicons-list-view', 'mod' => '' ),
					array( 'label' => __( 'Published', 'ewo-community-wall' ),    'value' => $count_published, 'icon' => 'dashicons-yes-alt',   'mod' => 'green' ),
					array( 'label' => __( 'Drafts', 'ewo-community-wall' ),       'value' => $count_draft,     'icon' => 'dashicons-edit',      'mod' => 'blue' ),
					array( 'label' => __( 'Hidden', 'ewo-community-wall' ),       'value' => $count_hidden,    'icon' => 'dashicons-hidden',    'mod' => 'muted' ),
				) as $mc ) : ?>
					<div class="ewo-cw-metric-card">
						<div class="ewo-cw-metric-body">
							<span class="ewo-cw-metric-label"><?php echo esc_html( $mc['label'] ); ?></span>
							<span class="ewo-cw-metric-value"><?php echo esc_html( (string) $mc['value'] ); ?></span>
						</div>
						<span class="ewo-cw-metric-icon dashicons <?php echo esc_attr( $mc['icon'] ); ?> ewo-cw-metric-icon--<?php echo esc_attr( $mc['mod'] ); ?>"></span>
					</div>
				<?php endforeach; ?>
			</div>

			<form method="get" class="ewo-cw-filters">
				<input type="hidden" name="page" value="<?php echo esc_attr( self::MENU_SLUG ); ?>" />
				<div class="ewo-cw-filter-grid">
					<div class="ewo-cw-filter-field">
						<label><?php esc_html_e( 'Search', 'ewo-community-wall' ); ?></label>
						<input type="text" name="filter_search" value="<?php echo esc_attr( $filter_search ); ?>" placeholder="<?php esc_attr_e( 'Search posts…', 'ewo-community-wall' ); ?>" />
					</div>
					<div class="ewo-cw-filter-field">
						<label><?php esc_html_e( 'Category', 'ewo-community-wall' ); ?></label>
						<select name="filter_cat">
							<option value="0"><?php esc_html_e( 'All Categories', 'ewo-community-wall' ); ?></option>
							<?php foreach ( $all_cats as $cat ) : ?>
								<option value="<?php echo esc_attr( (string) $cat->term_id ); ?>" <?php selected( $filter_cat_id, (int) $cat->term_id ); ?>><?php echo esc_html( $cat->name ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="ewo-cw-filter-field">
						<label><?php esc_html_e( 'Status', 'ewo-community-wall' ); ?></label>
						<select name="filter_status">
							<option value=""><?php esc_html_e( 'All Statuses', 'ewo-community-wall' ); ?></option>
							<option value="published" <?php selected( $filter_status, 'published' ); ?>><?php esc_html_e( 'Published', 'ewo-community-wall' ); ?></option>
							<option value="draft"     <?php selected( $filter_status, 'draft' ); ?>><?php esc_html_e( 'Draft', 'ewo-community-wall' ); ?></option>
							<option value="hidden"    <?php selected( $filter_status, 'hidden' ); ?>><?php esc_html_e( 'Hidden', 'ewo-community-wall' ); ?></option>
						</select>
					</div>
					<div class="ewo-cw-filter-actions">
						<button type="submit" class="ewo-cw-btn ewo-cw-btn--primary"><?php esc_html_e( 'Apply', 'ewo-community-wall' ); ?></button>
						<a href="<?php echo esc_url( $base_url ); ?>" class="ewo-cw-btn ewo-cw-btn--ghost"><?php esc_html_e( 'Reset', 'ewo-community-wall' ); ?></a>
					</div>
				</div>
			</form>

			<div class="ewo-cw-table-card">
				<div class="ewo-cw-table-card-header">
					<h2 class="ewo-cw-table-title"><?php esc_html_e( 'All Community Posts', 'ewo-community-wall' ); ?></h2>
					<?php if ( $total > 0 ) : ?>
						<span class="ewo-cw-count-label">
							<?php
							$first = ( $paged - 1 ) * self::PER_PAGE + 1;
							$last  = min( $paged * self::PER_PAGE, $total );
							printf( esc_html__( 'Showing %1$d–%2$d of %3$d', 'ewo-community-wall' ), (int) $first, (int) $last, (int) $total );
							?>
						</span>
					<?php endif; ?>
				</div>

				<?php if ( empty( $posts ) ) : ?>
					<p class="ewo-cw-empty"><?php esc_html_e( 'No community posts found.', 'ewo-community-wall' ); ?></p>
				<?php else : ?>
					<div class="ewo-cw-table-scroll">
						<table class="ewo-cw-table">
							<thead>
								<tr>
									<th><?php esc_html_e( 'ID', 'ewo-community-wall' ); ?></th>
									<th><?php esc_html_e( 'Title', 'ewo-community-wall' ); ?></th>
									<th><?php esc_html_e( 'Author', 'ewo-community-wall' ); ?></th>
									<th><?php esc_html_e( 'Category', 'ewo-community-wall' ); ?></th>
									<th><?php esc_html_e( 'Date', 'ewo-community-wall' ); ?></th>
									<th><?php esc_html_e( 'Status', 'ewo-community-wall' ); ?></th>
									<th><?php esc_html_e( 'Actions', 'ewo-community-wall' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $posts as $post ) :
									$edit_url   = add_query_arg( array( 'page' => self::MENU_SLUG, 'action' => 'edit', 'id' => $post->ID ), admin_url( 'admin.php' ) );
									$delete_url = wp_nonce_url(
										add_query_arg( array( 'action' => 'ewo_cw_delete', 'id' => $post->ID ), admin_url( 'admin-post.php' ) ),
										self::NONCE_DELETE . '_' . $post->ID
									);
									$author_name = (string) get_post_meta( $post->ID, self::META_AUTHOR, true );
									if ( empty( $author_name ) ) {
										$author_name = get_the_author_meta( 'display_name', $post->post_author );
									}
									$terms    = wp_get_object_terms( $post->ID, self::TAX, array( 'fields' => 'names' ) );
									$cat_name = ( ! is_wp_error( $terms ) && ! empty( $terms ) ) ? $terms[0] : '—';
								?>
									<tr>
										<td class="ewo-cw-td-id">#<?php echo esc_html( (string) $post->ID ); ?></td>
										<td class="ewo-cw-td-title">
											<a href="<?php echo esc_url( $edit_url ); ?>" class="ewo-cw-title-link">
												<?php echo esc_html( wp_trim_words( $post->post_title, 8 ) ); ?>
											</a>
										</td>
										<td class="ewo-cw-td-muted"><?php echo esc_html( $author_name ?: '—' ); ?></td>
										<td class="ewo-cw-td-muted"><?php echo esc_html( $cat_name ); ?></td>
										<td class="ewo-cw-td-date"><?php echo esc_html( wp_date( 'M j, Y', strtotime( $post->post_date ) ) ); ?></td>
										<td><?php echo wp_kses_post( $this->status_badge( $post->post_status ) ); ?></td>
										<td class="ewo-cw-td-actions">
											<a href="<?php echo esc_url( $edit_url ); ?>" class="ewo-cw-action-btn" title="<?php esc_attr_e( 'Edit', 'ewo-community-wall' ); ?>"><span class="dashicons dashicons-edit"></span></a>
											<a href="<?php echo esc_url( $delete_url ); ?>" class="ewo-cw-action-btn ewo-cw-action-btn--danger"
											   title="<?php esc_attr_e( 'Delete', 'ewo-community-wall' ); ?>"
											   onclick="return confirm('<?php echo esc_js( __( 'Delete this community post?', 'ewo-community-wall' ) ); ?>');">
												<span class="dashicons dashicons-trash"></span>
											</a>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
					<?php if ( $pages > 1 ) : ?>
						<?php $this->render_pagination( $paged, $pages, $total, array_filter( array( 'filter_status' => $filter_status ?: null, 'filter_cat' => $filter_cat_id ?: null, 'filter_search' => $filter_search ?: null ) ) ); ?>
					<?php endif; ?>
				<?php endif; ?>
			</div>

		</div>
		<?php
		wp_reset_postdata();
	}

	/* -------------------------------------------------------------------------
	   Posts — Add / Edit forms
	   ---------------------------------------------------------------------- */

	public function render_add() {
		if ( ! current_user_can( self::CAP ) ) {
			return;
		}
		?>
		<div class="ewo-cw-wrap">
			<div class="ewo-cw-page-header">
				<div>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::MENU_SLUG ) ); ?>" class="ewo-cw-back">&larr; <?php esc_html_e( 'All Posts', 'ewo-community-wall' ); ?></a>
					<h1 class="ewo-cw-page-title"><?php esc_html_e( 'Add Community Post', 'ewo-community-wall' ); ?></h1>
				</div>
			</div>
			<?php $this->render_post_form( null ); ?>
		</div>
		<?php
	}

	public function render_edit( $id ) {
		if ( ! current_user_can( self::CAP ) ) {
			return;
		}
		$post = get_post( $id );
		if ( ! $post || self::CPT !== $post->post_type ) {
			echo '<div class="ewo-cw-wrap"><p class="ewo-cw-empty">' . esc_html__( 'Post not found.', 'ewo-community-wall' ) . '</p></div>';
			return;
		}
		?>
		<div class="ewo-cw-wrap">
			<div class="ewo-cw-page-header">
				<div>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::MENU_SLUG ) ); ?>" class="ewo-cw-back">&larr; <?php esc_html_e( 'All Posts', 'ewo-community-wall' ); ?></a>
					<h1 class="ewo-cw-page-title"><?php esc_html_e( 'Edit Community Post', 'ewo-community-wall' ); ?> <span class="ewo-cw-id-label">#<?php echo esc_html( (string) $post->ID ); ?></span></h1>
				</div>
			</div>
			<?php $this->render_post_form( $post ); ?>
		</div>
		<?php
	}

	protected function render_post_form( $post ) {
		$is_edit     = ( null !== $post );
		$title       = $is_edit ? $post->post_title    : '';
		$content     = $is_edit ? $post->post_content  : '';
		$status      = $is_edit ? $this->wp_status_to_cw( $post->post_status ) : 'published';
		$author_name = $is_edit ? (string) get_post_meta( $post->ID, self::META_AUTHOR, true )     : '';
		$visibility  = $is_edit ? (string) get_post_meta( $post->ID, self::META_VISIBILITY, true ) : 'public';
		$thumbnail   = $is_edit ? (int) get_post_thumbnail_id( $post->ID ) : 0;
		$thumb_url   = $thumbnail ? wp_get_attachment_image_url( $thumbnail, 'medium' ) : '';

		// Current taxonomy term for this post.
		$current_terms = $is_edit ? wp_get_object_terms( $post->ID, self::TAX ) : array();
		$current_tid   = ( ! is_wp_error( $current_terms ) && ! empty( $current_terms ) ) ? (int) $current_terms[0]->term_id : 0;

		$all_cats = get_terms( array( 'taxonomy' => self::TAX, 'hide_empty' => false ) );
		if ( is_wp_error( $all_cats ) ) {
			$all_cats = array();
		}
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="ewo-cw-form-wrap">
			<input type="hidden" name="action" value="ewo_cw_save" />
			<?php if ( $is_edit ) : ?>
				<input type="hidden" name="post_id" value="<?php echo esc_attr( (string) $post->ID ); ?>" />
			<?php endif; ?>
			<?php wp_nonce_field( self::NONCE_SAVE ); ?>

			<div class="ewo-cw-form-grid">
				<div class="ewo-cw-form-main">
					<div class="ewo-cw-form-card">
						<div class="ewo-cw-field">
							<label for="cw-title"><?php esc_html_e( 'Post Title', 'ewo-community-wall' ); ?> <span class="ewo-cw-required">*</span></label>
							<input type="text" id="cw-title" name="title" required maxlength="500"
							       value="<?php echo esc_attr( $title ); ?>"
							       placeholder="<?php esc_attr_e( 'Enter post title…', 'ewo-community-wall' ); ?>" />
						</div>
						<div class="ewo-cw-field">
							<label for="cw-content"><?php esc_html_e( 'Post Content', 'ewo-community-wall' ); ?></label>
							<textarea id="cw-content" name="content" rows="10"
							          placeholder="<?php esc_attr_e( 'Write the post content…', 'ewo-community-wall' ); ?>"><?php echo esc_textarea( $content ); ?></textarea>
						</div>
					</div>
				</div>

				<div class="ewo-cw-form-side">
					<div class="ewo-cw-form-card">

						<div class="ewo-cw-field">
							<label for="cw-cat"><?php esc_html_e( 'Category', 'ewo-community-wall' ); ?></label>
							<select id="cw-cat" name="cat_term_id">
								<option value="0"><?php esc_html_e( '— No Category —', 'ewo-community-wall' ); ?></option>
								<?php foreach ( $all_cats as $cat ) : ?>
									<option value="<?php echo esc_attr( (string) $cat->term_id ); ?>" <?php selected( $current_tid, (int) $cat->term_id ); ?>><?php echo esc_html( $cat->name ); ?></option>
								<?php endforeach; ?>
							</select>
							<?php if ( empty( $all_cats ) ) : ?>
								<p class="ewo-cw-field-hint">
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::CAT_SLUG ) ); ?>"><?php esc_html_e( 'Create categories first →', 'ewo-community-wall' ); ?></a>
								</p>
							<?php endif; ?>
						</div>

						<div class="ewo-cw-field">
							<label for="cw-author-name"><?php esc_html_e( 'Author Display Name', 'ewo-community-wall' ); ?></label>
							<input type="text" id="cw-author-name" name="author_name"
							       value="<?php echo esc_attr( $author_name ); ?>"
							       placeholder="<?php esc_attr_e( 'Leave blank to use account name', 'ewo-community-wall' ); ?>" />
						</div>

						<div class="ewo-cw-field">
							<label for="cw-status"><?php esc_html_e( 'Status', 'ewo-community-wall' ); ?></label>
							<select id="cw-status" name="status">
								<option value="published" <?php selected( $status, 'published' ); ?>><?php esc_html_e( 'Published', 'ewo-community-wall' ); ?></option>
								<option value="draft"     <?php selected( $status, 'draft' ); ?>><?php esc_html_e( 'Draft', 'ewo-community-wall' ); ?></option>
								<option value="hidden"    <?php selected( $status, 'hidden' ); ?>><?php esc_html_e( 'Hidden', 'ewo-community-wall' ); ?></option>
							</select>
						</div>

						<div class="ewo-cw-field">
							<label for="cw-visibility"><?php esc_html_e( 'Visibility', 'ewo-community-wall' ); ?></label>
							<select id="cw-visibility" name="visibility">
								<option value="public"  <?php selected( $visibility, 'public' ); ?>><?php esc_html_e( 'Public', 'ewo-community-wall' ); ?></option>
								<option value="private" <?php selected( $visibility, 'private' ); ?>><?php esc_html_e( 'Private', 'ewo-community-wall' ); ?></option>
							</select>
						</div>

						<div class="ewo-cw-field">
							<label><?php esc_html_e( 'Image (optional)', 'ewo-community-wall' ); ?></label>
							<div class="ewo-cw-image-picker">
								<input type="hidden" name="thumbnail_id" id="cw-thumbnail-id" value="<?php echo esc_attr( (string) $thumbnail ); ?>" />
								<img id="cw-thumb-preview" src="<?php echo esc_url( $thumb_url ?: '' ); ?>" alt="" <?php echo $thumb_url ? '' : 'style="display:none;"'; ?> />
								<div class="ewo-cw-image-btns">
									<button type="button" class="ewo-cw-btn ewo-cw-btn--primary" id="cw-upload-btn">
										<?php echo $thumbnail ? esc_html__( 'Change Image', 'ewo-community-wall' ) : esc_html__( 'Upload / Select', 'ewo-community-wall' ); ?>
									</button>
									<?php if ( $thumbnail ) : ?>
										<button type="button" class="ewo-cw-btn ewo-cw-btn--ghost" id="cw-remove-btn"><?php esc_html_e( 'Remove', 'ewo-community-wall' ); ?></button>
									<?php endif; ?>
								</div>
							</div>
						</div>

					</div>

					<div class="ewo-cw-form-buttons">
						<button type="submit" class="ewo-cw-btn ewo-cw-btn--gold ewo-cw-btn--wide">
							<?php echo $is_edit ? esc_html__( 'Update Post', 'ewo-community-wall' ) : esc_html__( 'Save Post', 'ewo-community-wall' ); ?>
						</button>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::MENU_SLUG ) ); ?>"
						   class="ewo-cw-btn ewo-cw-btn--ghost ewo-cw-btn--wide">
							<?php esc_html_e( 'Cancel', 'ewo-community-wall' ); ?>
						</a>
					</div>
				</div>
			</div>
		</form>

		<script>
		(function() {
			var frame, uploadBtn = document.getElementById('cw-upload-btn'),
			    removeBtn = document.getElementById('cw-remove-btn'),
			    idInput   = document.getElementById('cw-thumbnail-id'),
			    preview   = document.getElementById('cw-thumb-preview');
			if ( uploadBtn ) {
				uploadBtn.addEventListener('click', function(e) {
					e.preventDefault();
					if ( frame ) { frame.open(); return; }
					frame = wp.media({ title: 'Select Image', button: { text: 'Use this image' }, multiple: false });
					frame.on('select', function() {
						var att = frame.state().get('selection').first().toJSON();
						idInput.value = att.id;
						preview.src = (att.sizes && att.sizes.medium) ? att.sizes.medium.url : att.url;
						preview.style.display = 'block';
						uploadBtn.textContent = 'Change Image';
						if ( !document.getElementById('cw-remove-btn') ) {
							var rb = document.createElement('button');
							rb.type = 'button'; rb.id = 'cw-remove-btn'; rb.className = 'ewo-cw-btn ewo-cw-btn--ghost';
							rb.textContent = 'Remove';
							rb.addEventListener('click', doRemove);
							uploadBtn.parentNode.appendChild(rb);
						}
					});
					frame.open();
				});
			}
			function doRemove() {
				idInput.value = ''; preview.src = ''; preview.style.display = 'none';
				uploadBtn.textContent = 'Upload / Select';
				var rb = document.getElementById('cw-remove-btn');
				if (rb) rb.remove();
			}
			if ( removeBtn ) removeBtn.addEventListener('click', doRemove);
		})();
		</script>
		<?php
	}

	/* -------------------------------------------------------------------------
	   Posts — Save / Delete handlers
	   ---------------------------------------------------------------------- */

	public function handle_save() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'Not allowed.', 'ewo-community-wall' ) );
		}
		check_admin_referer( self::NONCE_SAVE );

		$post_id     = isset( $_POST['post_id'] )      ? absint( wp_unslash( $_POST['post_id'] ) )                       : 0;
		$title       = isset( $_POST['title'] )        ? sanitize_text_field( wp_unslash( $_POST['title'] ) )            : '';
		$content     = isset( $_POST['content'] )      ? wp_kses_post( wp_unslash( $_POST['content'] ) )                 : '';
		$status_raw  = isset( $_POST['status'] )       ? sanitize_key( wp_unslash( $_POST['status'] ) )                  : 'published';
		$cat_term_id = isset( $_POST['cat_term_id'] )  ? absint( wp_unslash( $_POST['cat_term_id'] ) )                   : 0;
		$author_name = isset( $_POST['author_name'] )  ? sanitize_text_field( wp_unslash( $_POST['author_name'] ) )      : '';
		$visibility  = isset( $_POST['visibility'] )   ? sanitize_key( wp_unslash( $_POST['visibility'] ) )              : 'public';
		$thumb_id    = isset( $_POST['thumbnail_id'] ) ? absint( wp_unslash( $_POST['thumbnail_id'] ) )                  : 0;

		$post_data = array(
			'post_type'    => self::CPT,
			'post_title'   => $title,
			'post_content' => $content,
			'post_status'  => $this->cw_status_to_wp( $status_raw ),
		);

		if ( $post_id > 0 ) {
			$post_data['ID'] = $post_id;
			$result = wp_update_post( $post_data, true );
			$msg    = 'updated';
		} else {
			$result = wp_insert_post( $post_data, true );
			$msg    = 'saved';
		}
		if ( is_wp_error( $result ) ) {
			wp_die( esc_html( $result->get_error_message() ) );
		}

		$saved_id = $post_id > 0 ? $post_id : (int) $result;

		// Taxonomy term.
		if ( $cat_term_id > 0 ) {
			wp_set_object_terms( $saved_id, array( $cat_term_id ), self::TAX );
		} else {
			wp_set_object_terms( $saved_id, array(), self::TAX );
		}

		update_post_meta( $saved_id, self::META_AUTHOR,     $author_name );
		update_post_meta( $saved_id, self::META_VISIBILITY, $visibility );

		if ( $thumb_id > 0 ) {
			set_post_thumbnail( $saved_id, $thumb_id );
		} else {
			delete_post_thumbnail( $saved_id );
		}

		wp_safe_redirect( add_query_arg( array( 'page' => self::MENU_SLUG, 'action' => 'edit', 'id' => $saved_id, 'msg' => $msg ), admin_url( 'admin.php' ) ) );
		exit;
	}

	public function handle_delete() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'Not allowed.', 'ewo-community-wall' ) );
		}
		$id = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;
		check_admin_referer( self::NONCE_DELETE . '_' . $id );
		wp_trash_post( $id );
		wp_safe_redirect( add_query_arg( array( 'page' => self::MENU_SLUG, 'msg' => 'deleted' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/* -------------------------------------------------------------------------
	   Categories — Dispatch
	   ---------------------------------------------------------------------- */

	public function dispatch_cats() {
		if ( ! current_user_can( self::CAP ) ) {
			return;
		}
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
		$id     = isset( $_GET['id'] )     ? absint( wp_unslash( $_GET['id'] ) )           : 0;
		// phpcs:enable
		if ( 'edit' === $action && $id > 0 ) {
			$this->render_cat_edit( $id );
		} else {
			$this->render_cat_list();
		}
	}

	/* -------------------------------------------------------------------------
	   Categories — List + inline Add form
	   ---------------------------------------------------------------------- */

	protected function render_cat_list() {
		$terms = get_terms( array( 'taxonomy' => self::TAX, 'hide_empty' => false ) );
		if ( is_wp_error( $terms ) ) {
			$terms = array();
		}
		?>
		<div class="ewo-cw-wrap">
			<div class="ewo-cw-page-header">
				<div>
					<h1 class="ewo-cw-page-title"><?php esc_html_e( 'Community Wall Categories', 'ewo-community-wall' ); ?></h1>
					<p class="ewo-cw-page-sub"><?php esc_html_e( 'Create and manage the categories for community posts.', 'ewo-community-wall' ); ?></p>
				</div>
			</div>

			<div class="ewo-cw-cat-layout">

				<!-- ADD FORM -->
				<div class="ewo-cw-cat-add-panel">
					<div class="ewo-cw-form-card">
						<h2 class="ewo-cw-cat-panel-title"><?php esc_html_e( 'Add New Category', 'ewo-community-wall' ); ?></h2>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<input type="hidden" name="action" value="ewo_cw_cat_save" />
							<?php wp_nonce_field( self::NONCE_CAT_SAVE ); ?>
							<div class="ewo-cw-field">
								<label for="cat-name"><?php esc_html_e( 'Name', 'ewo-community-wall' ); ?> <span class="ewo-cw-required">*</span></label>
								<input type="text" id="cat-name" name="cat_name" required maxlength="200"
								       placeholder="<?php esc_attr_e( 'e.g. Updates, Analysis…', 'ewo-community-wall' ); ?>" />
							</div>
							<div class="ewo-cw-field">
								<label for="cat-slug"><?php esc_html_e( 'Slug', 'ewo-community-wall' ); ?></label>
								<input type="text" id="cat-slug" name="cat_slug" maxlength="200"
								       placeholder="<?php esc_attr_e( 'auto-generated if empty', 'ewo-community-wall' ); ?>" />
							</div>
							<button type="submit" class="ewo-cw-btn ewo-cw-btn--gold ewo-cw-btn--wide"><?php esc_html_e( 'Add Category', 'ewo-community-wall' ); ?></button>
						</form>
					</div>
				</div>

				<!-- CATEGORY TABLE -->
				<div class="ewo-cw-cat-table-panel">
					<div class="ewo-cw-table-card">
						<div class="ewo-cw-table-card-header">
							<h2 class="ewo-cw-table-title"><?php esc_html_e( 'All Categories', 'ewo-community-wall' ); ?></h2>
							<span class="ewo-cw-count-label"><?php printf( esc_html__( '%d total', 'ewo-community-wall' ), count( $terms ) ); ?></span>
						</div>
						<?php if ( empty( $terms ) ) : ?>
							<p class="ewo-cw-empty"><?php esc_html_e( 'No categories yet. Add one on the left.', 'ewo-community-wall' ); ?></p>
						<?php else : ?>
							<div class="ewo-cw-table-scroll">
								<table class="ewo-cw-table">
									<thead>
										<tr>
											<th><?php esc_html_e( 'Name', 'ewo-community-wall' ); ?></th>
											<th><?php esc_html_e( 'Slug', 'ewo-community-wall' ); ?></th>
											<th><?php esc_html_e( 'Posts', 'ewo-community-wall' ); ?></th>
											<th><?php esc_html_e( 'Actions', 'ewo-community-wall' ); ?></th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ( $terms as $term ) :
											$edit_url   = add_query_arg( array( 'page' => self::CAT_SLUG, 'action' => 'edit', 'id' => $term->term_id ), admin_url( 'admin.php' ) );
											$delete_url = wp_nonce_url(
												add_query_arg( array( 'action' => 'ewo_cw_cat_delete', 'id' => $term->term_id ), admin_url( 'admin-post.php' ) ),
												self::NONCE_CAT_DELETE . '_' . $term->term_id
											);
										?>
											<tr>
												<td class="ewo-cw-td-title">
													<a href="<?php echo esc_url( $edit_url ); ?>" class="ewo-cw-title-link"><?php echo esc_html( $term->name ); ?></a>
												</td>
												<td class="ewo-cw-td-muted"><code><?php echo esc_html( $term->slug ); ?></code></td>
												<td class="ewo-cw-td-muted"><?php echo esc_html( (string) $term->count ); ?></td>
												<td class="ewo-cw-td-actions">
													<a href="<?php echo esc_url( $edit_url ); ?>" class="ewo-cw-action-btn" title="<?php esc_attr_e( 'Edit', 'ewo-community-wall' ); ?>"><span class="dashicons dashicons-edit"></span></a>
													<a href="<?php echo esc_url( $delete_url ); ?>" class="ewo-cw-action-btn ewo-cw-action-btn--danger"
													   title="<?php esc_attr_e( 'Delete', 'ewo-community-wall' ); ?>"
													   onclick="return confirm('<?php echo esc_js( __( 'Delete this category? Posts in it will become uncategorized.', 'ewo-community-wall' ) ); ?>');">
														<span class="dashicons dashicons-trash"></span>
													</a>
												</td>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							</div>
						<?php endif; ?>
					</div>
				</div>

			</div><!-- .ewo-cw-cat-layout -->
		</div>
		<?php
	}

	/* -------------------------------------------------------------------------
	   Categories — Edit form
	   ---------------------------------------------------------------------- */

	protected function render_cat_edit( $id ) {
		$term = get_term( $id, self::TAX );
		if ( ! $term || is_wp_error( $term ) ) {
			echo '<div class="ewo-cw-wrap"><p class="ewo-cw-empty">' . esc_html__( 'Category not found.', 'ewo-community-wall' ) . '</p></div>';
			return;
		}
		?>
		<div class="ewo-cw-wrap">
			<div class="ewo-cw-page-header">
				<div>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::CAT_SLUG ) ); ?>" class="ewo-cw-back">&larr; <?php esc_html_e( 'All Categories', 'ewo-community-wall' ); ?></a>
					<h1 class="ewo-cw-page-title"><?php esc_html_e( 'Edit Category', 'ewo-community-wall' ); ?></h1>
				</div>
			</div>
			<div style="max-width:480px;">
				<div class="ewo-cw-form-card">
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action"  value="ewo_cw_cat_save" />
						<input type="hidden" name="cat_id"  value="<?php echo esc_attr( (string) $term->term_id ); ?>" />
						<?php wp_nonce_field( self::NONCE_CAT_SAVE ); ?>
						<div class="ewo-cw-field">
							<label for="cat-name"><?php esc_html_e( 'Name', 'ewo-community-wall' ); ?> <span class="ewo-cw-required">*</span></label>
							<input type="text" id="cat-name" name="cat_name" required maxlength="200" value="<?php echo esc_attr( $term->name ); ?>" />
						</div>
						<div class="ewo-cw-field">
							<label for="cat-slug"><?php esc_html_e( 'Slug', 'ewo-community-wall' ); ?></label>
							<input type="text" id="cat-slug" name="cat_slug" maxlength="200" value="<?php echo esc_attr( $term->slug ); ?>" />
						</div>
						<div class="ewo-cw-form-buttons" style="margin-top:16px;">
							<button type="submit" class="ewo-cw-btn ewo-cw-btn--gold"><?php esc_html_e( 'Update Category', 'ewo-community-wall' ); ?></button>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::CAT_SLUG ) ); ?>" class="ewo-cw-btn ewo-cw-btn--ghost"><?php esc_html_e( 'Cancel', 'ewo-community-wall' ); ?></a>
						</div>
					</form>
				</div>
			</div>
		</div>
		<?php
	}

	/* -------------------------------------------------------------------------
	   Categories — Save / Delete handlers
	   ---------------------------------------------------------------------- */

	public function handle_cat_save() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'Not allowed.', 'ewo-community-wall' ) );
		}
		check_admin_referer( self::NONCE_CAT_SAVE );

		$cat_id   = isset( $_POST['cat_id'] )   ? absint( wp_unslash( $_POST['cat_id'] ) )                    : 0;
		$cat_name = isset( $_POST['cat_name'] ) ? sanitize_text_field( wp_unslash( $_POST['cat_name'] ) )     : '';
		$cat_slug = isset( $_POST['cat_slug'] ) ? sanitize_title( wp_unslash( $_POST['cat_slug'] ) )          : '';

		if ( empty( $cat_name ) ) {
			wp_die( esc_html__( 'Category name is required.', 'ewo-community-wall' ) );
		}

		$args = array( 'name' => $cat_name );
		if ( '' !== $cat_slug ) {
			$args['slug'] = $cat_slug;
		}

		if ( $cat_id > 0 ) {
			$result = wp_update_term( $cat_id, self::TAX, $args );
			$msg    = 'cat_updated';
		} else {
			$result = wp_insert_term( $cat_name, self::TAX, $args );
			$msg    = 'cat_saved';
		}

		if ( is_wp_error( $result ) ) {
			wp_die( esc_html( $result->get_error_message() ) );
		}

		wp_safe_redirect( add_query_arg( array( 'page' => self::CAT_SLUG, 'msg' => $msg ), admin_url( 'admin.php' ) ) );
		exit;
	}

	public function handle_cat_delete() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'Not allowed.', 'ewo-community-wall' ) );
		}
		$id = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;
		check_admin_referer( self::NONCE_CAT_DELETE . '_' . $id );
		wp_delete_term( $id, self::TAX );
		wp_safe_redirect( add_query_arg( array( 'page' => self::CAT_SLUG, 'msg' => 'cat_deleted' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/* -------------------------------------------------------------------------
	   Notice
	   ---------------------------------------------------------------------- */

	public function render_notice() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$msg  = isset( $_GET['msg'] )  ? sanitize_key( wp_unslash( $_GET['msg'] ) )  : '';
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		// phpcs:enable
		if ( ! $msg || false === strpos( $page, 'ewo-community-wall' ) ) {
			return;
		}
		$map = array(
			'saved'       => __( 'Community post saved.', 'ewo-community-wall' ),
			'updated'     => __( 'Community post updated.', 'ewo-community-wall' ),
			'deleted'     => __( 'Community post moved to trash.', 'ewo-community-wall' ),
			'cat_saved'   => __( 'Category added.', 'ewo-community-wall' ),
			'cat_updated' => __( 'Category updated.', 'ewo-community-wall' ),
			'cat_deleted' => __( 'Category deleted.', 'ewo-community-wall' ),
		);
		if ( isset( $map[ $msg ] ) ) {
			printf( '<div class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html( $map[ $msg ] ) );
		}
	}

	/* -------------------------------------------------------------------------
	   One-time migration: old _ewo_cw_category meta → taxonomy terms
	   ---------------------------------------------------------------------- */

	public function maybe_migrate_categories() {
		if ( get_option( 'ewo_cw_cat_migrated_v1' ) ) {
			return;
		}
		// Only run when taxonomy is registered.
		if ( ! taxonomy_exists( self::TAX ) ) {
			return;
		}

		$posts = get_posts( array(
			'post_type'      => self::CPT,
			'post_status'    => array( 'publish', 'draft', 'private' ),
			'posts_per_page' => -1,
			'fields'         => 'ids',
		) );

		foreach ( $posts as $pid ) {
			$existing = wp_get_object_terms( $pid, self::TAX );
			if ( ! is_wp_error( $existing ) && ! empty( $existing ) ) {
				continue;
			}
			$cat_name = (string) get_post_meta( $pid, '_ewo_cw_category', true );
			if ( empty( $cat_name ) ) {
				continue;
			}
			$term = get_term_by( 'name', $cat_name, self::TAX );
			if ( ! $term ) {
				$result = wp_insert_term( $cat_name, self::TAX );
				$tid    = is_wp_error( $result ) ? 0 : (int) $result['term_id'];
			} else {
				$tid = (int) $term->term_id;
			}
			if ( $tid > 0 ) {
				wp_set_object_terms( $pid, array( $tid ), self::TAX );
			}
		}

		update_option( 'ewo_cw_cat_migrated_v1', true, false );
	}

	/* -------------------------------------------------------------------------
	   Helpers
	   ---------------------------------------------------------------------- */

	protected function cw_status_to_wp( $status ) {
		$map = array( 'published' => 'publish', 'draft' => 'draft', 'hidden' => 'private' );
		return isset( $map[ $status ] ) ? $map[ $status ] : 'draft';
	}

	protected function wp_status_to_cw( $wp_status ) {
		$map = array( 'publish' => 'published', 'draft' => 'draft', 'private' => 'hidden' );
		return isset( $map[ $wp_status ] ) ? $map[ $wp_status ] : 'draft';
	}

	protected function status_filter_to_wp( $filter ) {
		switch ( $filter ) {
			case 'published': return array( 'publish' );
			case 'draft':     return array( 'draft' );
			case 'hidden':    return array( 'private' );
			default:          return array( 'publish', 'draft', 'private' );
		}
	}

	protected function status_badge( $wp_status ) {
		$map = array(
			'publish' => array( 'label' => 'Published', 'cls' => 'ewo-cw-badge--published' ),
			'draft'   => array( 'label' => 'Draft',     'cls' => 'ewo-cw-badge--draft' ),
			'private' => array( 'label' => 'Hidden',    'cls' => 'ewo-cw-badge--hidden' ),
		);
		$info = isset( $map[ $wp_status ] ) ? $map[ $wp_status ] : array( 'label' => ucfirst( $wp_status ), 'cls' => 'ewo-cw-badge--draft' );
		return '<span class="ewo-cw-badge ' . esc_attr( $info['cls'] ) . '">' . esc_html( $info['label'] ) . '</span>';
	}

	protected function render_pagination( $paged, $pages, $total, $filter_args ) {
		$base     = admin_url( 'admin.php' );
		$nav_args = array_merge( $filter_args, array( 'page' => self::MENU_SLUG ) );
		?>
		<div class="ewo-cw-pagination">
			<span class="ewo-cw-pag-info">
				<?php
				$first = ( $paged - 1 ) * self::PER_PAGE + 1;
				$last  = min( $paged * self::PER_PAGE, $total );
				printf( esc_html__( 'Showing %1$d to %2$d of %3$d posts', 'ewo-community-wall' ), (int) $first, (int) $last, (int) $total );
				?>
			</span>
			<nav class="ewo-cw-pag-nav">
				<?php if ( $paged > 1 ) : ?>
					<a href="<?php echo esc_url( add_query_arg( array_merge( $nav_args, array( 'paged' => $paged - 1 ) ), $base ) ); ?>" class="ewo-cw-pag-btn"><?php esc_html_e( 'Previous', 'ewo-community-wall' ); ?></a>
				<?php else : ?>
					<span class="ewo-cw-pag-btn ewo-cw-pag-btn--disabled"><?php esc_html_e( 'Previous', 'ewo-community-wall' ); ?></span>
				<?php endif; ?>
				<?php for ( $i = max( 1, $paged - 4 ); $i <= min( $pages, $paged + 4 ); $i++ ) : ?>
					<?php if ( $i === $paged ) : ?>
						<span class="ewo-cw-pag-btn ewo-cw-pag-btn--current"><?php echo esc_html( (string) $i ); ?></span>
					<?php else : ?>
						<a href="<?php echo esc_url( add_query_arg( array_merge( $nav_args, array( 'paged' => $i ) ), $base ) ); ?>" class="ewo-cw-pag-btn"><?php echo esc_html( (string) $i ); ?></a>
					<?php endif; ?>
				<?php endfor; ?>
				<?php if ( $paged < $pages ) : ?>
					<a href="<?php echo esc_url( add_query_arg( array_merge( $nav_args, array( 'paged' => $paged + 1 ) ), $base ) ); ?>" class="ewo-cw-pag-btn"><?php esc_html_e( 'Next', 'ewo-community-wall' ); ?></a>
				<?php else : ?>
					<span class="ewo-cw-pag-btn ewo-cw-pag-btn--disabled"><?php esc_html_e( 'Next', 'ewo-community-wall' ); ?></span>
				<?php endif; ?>
			</nav>
		</div>
		<?php
	}
}
