<?php
/**
 * Template for the public Community Wall page.
 *
 * Three views dispatched by query vars:
 *   /community-wall/                          → list: one card per category (latest post)
 *   /community-wall/category/{cat-slug}/      → category archive: all posts in one category
 *   /community-wall/{post-slug}/              → single post detail
 *
 * @package EWO_2025
 */

get_header();

defined( 'EWO_CW_CPT' )    || define( 'EWO_CW_CPT',    'ewo_community_post' );
defined( 'EWO_CW_TAX' )    || define( 'EWO_CW_TAX',    'ewo_cw_cat' );
defined( 'EWO_CW_META_A' ) || define( 'EWO_CW_META_A', '_ewo_cw_author_name' );
defined( 'EWO_CW_META_V' ) || define( 'EWO_CW_META_V', '_ewo_cw_visibility' );

$ewo_cw_post_slug = (string) get_query_var( 'ewo_cw_slug',     '' );
$ewo_cw_cat_slug  = (string) get_query_var( 'ewo_cw_cat_slug', '' );
$ewo_cw_base      = home_url( '/community-wall/' );

/* =========================================================================
   Helper: post author display name
   ======================================================================= */
function ewo_cwp_author( $post_id ) {
	$name = (string) get_post_meta( $post_id, EWO_CW_META_A, true );
	return $name ?: (string) get_the_author_meta( 'display_name', get_post_field( 'post_author', $post_id ) );
}

/* =========================================================================
   Helper: is post publicly visible?
   ======================================================================= */
function ewo_cwp_is_public( $post_id ) {
	$vis = (string) get_post_meta( $post_id, EWO_CW_META_V, true );
	if ( 'private' === $vis && ! current_user_can( 'manage_options' ) ) {
		return false;
	}
	return true;
}

/* =========================================================================
   SINGLE POST DETAIL VIEW   /community-wall/{slug}/
   ======================================================================= */
if ( '' !== $ewo_cw_post_slug ) :

	$ewo_cw_q = new WP_Query( array(
		'post_type'      => EWO_CW_CPT,
		'post_status'    => 'publish',
		'name'           => $ewo_cw_post_slug,
		'posts_per_page' => 1,
	) );
	$ewo_post = $ewo_cw_q->have_posts() ? $ewo_cw_q->posts[0] : null;
	if ( $ewo_post && ! ewo_cwp_is_public( $ewo_post->ID ) ) {
		$ewo_post = null;
	}

	$ewo_post_terms = $ewo_post
		? wp_get_object_terms( $ewo_post->ID, EWO_CW_TAX )
		: array();
	$ewo_post_term  = ( ! is_wp_error( $ewo_post_terms ) && ! empty( $ewo_post_terms ) ) ? $ewo_post_terms[0] : null;
?>
<main id="primary" class="site-main ewo-cwp-main">
<div class="ewo-cwp-wrap">

	<a href="<?php echo esc_url( $ewo_cw_base ); ?>" class="ewo-cwp-back">&larr; Community Wall</a>

	<?php if ( ! $ewo_post ) : ?>
		<p class="ewo-cwp-empty">Post not found.</p>
	<?php else : ?>
	<div class="ewo-cwp-detail-header">
		<?php if ( $ewo_post_term ) : ?>
			<a href="<?php echo esc_url( $ewo_cw_base . 'category/' . $ewo_post_term->slug . '/' ); ?>" class="ewo-cwp-kicker"><?php echo esc_html( $ewo_post_term->name ); ?></a>
		<?php endif; ?>
		<h1 class="ewo-cwp-detail-title"><?php echo esc_html( $ewo_post->post_title ); ?></h1>
		<div class="ewo-cwp-detail-meta">
			<?php $ewo_pa = ewo_cwp_author( $ewo_post->ID ); if ( $ewo_pa ) : ?>
				<span class="ewo-cwp-meta-author"><?php echo esc_html( $ewo_pa ); ?></span>
				<span class="ewo-cwp-dot">·</span>
			<?php endif; ?>
			<span class="ewo-cwp-meta-date"><?php echo esc_html( wp_date( 'F j, Y', strtotime( $ewo_post->post_date ) ) ); ?></span>
		</div>
	</div>

	<?php $ewo_thumb = get_the_post_thumbnail_url( $ewo_post->ID, 'large' ); if ( $ewo_thumb ) : ?>
		<div class="ewo-cwp-detail-image"><img src="<?php echo esc_url( $ewo_thumb ); ?>" alt="<?php echo esc_attr( $ewo_post->post_title ); ?>" /></div>
	<?php endif; ?>

	<div class="ewo-cwp-detail-body">
		<div class="ewo-cwp-content"><?php echo wp_kses_post( wpautop( $ewo_post->post_content ) ); ?></div>
	</div>
	<?php endif; ?>

</div>
</main>
<?php
	wp_reset_postdata();

/* =========================================================================
   CATEGORY ARCHIVE VIEW   /community-wall/category/{slug}/
   ======================================================================= */
elseif ( '' !== $ewo_cw_cat_slug ) :

	$ewo_cat_term = get_term_by( 'slug', $ewo_cw_cat_slug, EWO_CW_TAX );

	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	$ewo_cw_paged = isset( $_GET['paged'] ) ? max( 1, absint( wp_unslash( $_GET['paged'] ) ) ) : 1;
	// phpcs:enable
	$ewo_per      = 9;

	if ( $ewo_cat_term && ! is_wp_error( $ewo_cat_term ) ) :
		$ewo_q = new WP_Query( array(
			'post_type'      => EWO_CW_CPT,
			'post_status'    => 'publish',
			'posts_per_page' => $ewo_per,
			'paged'          => $ewo_cw_paged,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'tax_query'      => array( array(
				'taxonomy' => EWO_CW_TAX,
				'field'    => 'slug',
				'terms'    => $ewo_cat_slug,
			) ),
		) );
		$ewo_cat_posts  = $ewo_q->posts;
		$ewo_cat_total  = $ewo_q->found_posts;
		$ewo_cat_pages  = (int) ceil( $ewo_cat_total / $ewo_per );
		$ewo_cat_base   = $ewo_cw_base . 'category/' . $ewo_cat_term->slug . '/';
	endif;
?>
<main id="primary" class="site-main ewo-cwp-main">

	<div class="ewo-cwp-page-header">
		<div class="ewo-cwp-wrap">
			<div class="ewo-cwp-header-row">
				<div>
					<a href="<?php echo esc_url( $ewo_cw_base ); ?>" class="ewo-cwp-header-back">&larr; Community Wall</a>
					<?php if ( $ewo_cat_term && ! is_wp_error( $ewo_cat_term ) ) : ?>
						<p class="ewo-cwp-kicker">Category</p>
						<h1 class="ewo-cwp-page-title"><?php echo esc_html( $ewo_cat_term->name ); ?></h1>
						<p class="ewo-cwp-page-sub"><?php printf( esc_html__( 'All posts in %s', 'ewo-2025' ), esc_html( $ewo_cat_term->name ) ); ?></p>
					<?php else : ?>
						<h1 class="ewo-cwp-page-title"><?php esc_html_e( 'Category Not Found', 'ewo-2025' ); ?></h1>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>

	<div class="ewo-cwp-wrap ewo-cwp-body">
		<?php if ( ! $ewo_cat_term || is_wp_error( $ewo_cat_term ) ) : ?>
			<p class="ewo-cwp-empty"><?php esc_html_e( 'Category not found.', 'ewo-2025' ); ?></p>
		<?php elseif ( empty( $ewo_cat_posts ) ) : ?>
			<p class="ewo-cwp-empty"><?php esc_html_e( 'No posts in this category yet.', 'ewo-2025' ); ?></p>
		<?php else : ?>
		<div class="ewo-cwp-grid">
			<?php foreach ( $ewo_cat_posts as $ewo_p ) :
				if ( ! ewo_cwp_is_public( $ewo_p->ID ) ) continue;
				$ewo_p_url   = $ewo_cw_base . $ewo_p->post_name . '/';
				$ewo_p_thumb = get_the_post_thumbnail_url( $ewo_p->ID, 'medium_large' );
				$ewo_p_auth  = ewo_cwp_author( $ewo_p->ID );
				$ewo_p_exc   = wp_trim_words( wp_strip_all_tags( $ewo_p->post_content ), 24 );
			?>
			<div class="ewo-cwp-card">
				<?php if ( $ewo_p_thumb ) : ?>
					<a href="<?php echo esc_url( $ewo_p_url ); ?>" class="ewo-cwp-card-img-link">
						<img class="ewo-cwp-card-img" src="<?php echo esc_url( $ewo_p_thumb ); ?>" alt="<?php echo esc_attr( $ewo_p->post_title ); ?>" loading="lazy" />
					</a>
				<?php endif; ?>
				<div class="ewo-cwp-card-body">
					<h2 class="ewo-cwp-card-title"><a href="<?php echo esc_url( $ewo_p_url ); ?>"><?php echo esc_html( $ewo_p->post_title ); ?></a></h2>
					<?php if ( $ewo_p_exc ) : ?><p class="ewo-cwp-card-excerpt"><?php echo esc_html( $ewo_p_exc ); ?></p><?php endif; ?>
					<div class="ewo-cwp-card-footer">
						<div class="ewo-cwp-card-meta">
							<?php if ( $ewo_p_auth ) : ?><span class="ewo-cwp-card-author"><?php echo esc_html( $ewo_p_auth ); ?></span><span class="ewo-cwp-dot">·</span><?php endif; ?>
							<span class="ewo-cwp-card-date"><?php echo esc_html( wp_date( 'M j, Y', strtotime( $ewo_p->post_date ) ) ); ?></span>
						</div>
						<a href="<?php echo esc_url( $ewo_p_url ); ?>" class="ewo-cwp-read-more">Read More →</a>
					</div>
				</div>
			</div>
			<?php endforeach; ?>
		</div>

		<?php if ( $ewo_cat_pages > 1 ) : ?>
		<div class="ewo-cwp-pagination"><nav class="ewo-cwp-pag-nav">
			<?php if ( $ewo_cw_paged > 1 ) : ?>
				<a href="<?php echo esc_url( add_query_arg( 'paged', $ewo_cw_paged - 1, $ewo_cat_base ) ); ?>" class="ewo-cwp-pag-btn">← Prev</a>
			<?php else : ?>
				<span class="ewo-cwp-pag-btn ewo-cwp-pag-btn--off">← Prev</span>
			<?php endif; ?>
			<?php for ( $i = max(1,$ewo_cw_paged-4); $i <= min($ewo_cat_pages,$ewo_cw_paged+4); $i++ ) : ?>
				<?php if ( $i === $ewo_cw_paged ) : ?><span class="ewo-cwp-pag-btn ewo-cwp-pag-btn--cur"><?php echo esc_html((string)$i); ?></span>
				<?php else : ?><a href="<?php echo esc_url(add_query_arg('paged',$i,$ewo_cat_base)); ?>" class="ewo-cwp-pag-btn"><?php echo esc_html((string)$i); ?></a><?php endif; ?>
			<?php endfor; ?>
			<?php if ( $ewo_cw_paged < $ewo_cat_pages ) : ?>
				<a href="<?php echo esc_url( add_query_arg( 'paged', $ewo_cw_paged + 1, $ewo_cat_base ) ); ?>" class="ewo-cwp-pag-btn">Next →</a>
			<?php else : ?>
				<span class="ewo-cwp-pag-btn ewo-cwp-pag-btn--off">Next →</span>
			<?php endif; ?>
		</nav></div>
		<?php endif; ?>
		<?php endif; ?>
	</div>
</main>
<?php
	wp_reset_postdata();

/* =========================================================================
   LIST VIEW   /community-wall/   (one card per category, latest post)
   ======================================================================= */
else :

	$ewo_terms = get_terms( array(
		'taxonomy'   => EWO_CW_TAX,
		'hide_empty' => true,
		'orderby'    => 'name',
		'order'      => 'ASC',
	) );
	if ( is_wp_error( $ewo_terms ) ) {
		$ewo_terms = array();
	}

	// For each term: get latest public published post.
	$ewo_cat_cards = array();
	foreach ( $ewo_terms as $ewo_term ) {
		$ewo_cat_q = get_posts( array(
			'post_type'      => EWO_CW_CPT,
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'tax_query'      => array( array(
				'taxonomy' => EWO_CW_TAX,
				'field'    => 'term_id',
				'terms'    => $ewo_term->term_id,
			) ),
			'meta_query'     => array(
				'relation' => 'OR',
				array( 'key' => EWO_CW_META_V, 'value' => 'private', 'compare' => '!=' ),
				array( 'key' => EWO_CW_META_V, 'compare' => 'NOT EXISTS' ),
			),
		) );
		if ( empty( $ewo_cat_q ) ) {
			continue;
		}
		$ewo_cat_cards[] = array(
			'term'  => $ewo_term,
			'post'  => $ewo_cat_q[0],
			'count' => (int) $ewo_term->count,
		);
	}

	// Posts with no category (fallback bucket).
	$ewo_orphan_q = get_posts( array(
		'post_type'      => EWO_CW_CPT,
		'post_status'    => 'publish',
		'posts_per_page' => 3,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'tax_query'      => array( array(
			'taxonomy' => EWO_CW_TAX,
			'operator' => 'NOT EXISTS',
		) ),
		'meta_query'     => array(
			'relation' => 'OR',
			array( 'key' => EWO_CW_META_V, 'value' => 'private', 'compare' => '!=' ),
			array( 'key' => EWO_CW_META_V, 'compare' => 'NOT EXISTS' ),
		),
	) );
?>
<main id="primary" class="site-main ewo-cwp-main">

	<div class="ewo-cwp-page-header">
		<div class="ewo-cwp-wrap">
			<div class="ewo-cwp-header-row">
				<div>
					<p class="ewo-cwp-kicker">Updates</p>
					<h1 class="ewo-cwp-page-title">Community Wall</h1>
					<p class="ewo-cwp-page-sub">Community updates, notes, and public posts from Emerging World Order.</p>
				</div>
				<?php if ( current_user_can( 'manage_options' ) ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=ewo-community-wall-add' ) ); ?>" class="ewo-cwp-add-btn">+ Add Post</a>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<div class="ewo-cwp-wrap ewo-cwp-body">

		<?php if ( empty( $ewo_cat_cards ) && empty( $ewo_orphan_q ) ) : ?>
			<p class="ewo-cwp-empty">No community posts yet.</p>
		<?php else : ?>

		<!-- Category cards grid -->
		<?php if ( ! empty( $ewo_cat_cards ) ) : ?>
		<div class="ewo-cwp-grid ewo-cwp-grid--cats">
			<?php foreach ( $ewo_cat_cards as $ewo_cc ) :
				$ewo_ct     = $ewo_cc['term'];
				$ewo_cp     = $ewo_cc['post'];
				$ewo_count  = $ewo_cc['count'];
				$ewo_cat_url  = $ewo_cw_base . 'category/' . $ewo_ct->slug . '/';
				$ewo_post_url = $ewo_cw_base . $ewo_cp->post_name . '/';
				$ewo_thumb    = get_the_post_thumbnail_url( $ewo_cp->ID, 'medium_large' );
				$ewo_excerpt  = wp_trim_words( wp_strip_all_tags( $ewo_cp->post_content ), 22 );
				$ewo_author   = ewo_cwp_author( $ewo_cp->ID );
			?>
			<div class="ewo-cwp-cat-card">

				<div class="ewo-cwp-cat-card-header">
					<a href="<?php echo esc_url( $ewo_cat_url ); ?>" class="ewo-cwp-cat-label"><?php echo esc_html( $ewo_ct->name ); ?></a>
					<?php if ( $ewo_count > 1 ) : ?>
						<a href="<?php echo esc_url( $ewo_cat_url ); ?>" class="ewo-cwp-view-all">View All (<?php echo esc_html( (string) $ewo_count ); ?>) →</a>
					<?php endif; ?>
				</div>

				<?php if ( $ewo_thumb ) : ?>
					<a href="<?php echo esc_url( $ewo_post_url ); ?>" class="ewo-cwp-card-img-link">
						<img class="ewo-cwp-card-img" src="<?php echo esc_url( $ewo_thumb ); ?>" alt="<?php echo esc_attr( $ewo_cp->post_title ); ?>" loading="lazy" />
					</a>
				<?php endif; ?>

				<div class="ewo-cwp-card-body">
					<h2 class="ewo-cwp-card-title">
						<a href="<?php echo esc_url( $ewo_post_url ); ?>"><?php echo esc_html( $ewo_cp->post_title ); ?></a>
					</h2>
					<?php if ( $ewo_excerpt ) : ?>
						<p class="ewo-cwp-card-excerpt"><?php echo esc_html( $ewo_excerpt ); ?></p>
					<?php endif; ?>
					<div class="ewo-cwp-card-footer">
						<div class="ewo-cwp-card-meta">
							<?php if ( $ewo_author ) : ?><span class="ewo-cwp-card-author"><?php echo esc_html( $ewo_author ); ?></span><span class="ewo-cwp-dot">·</span><?php endif; ?>
							<span class="ewo-cwp-card-date"><?php echo esc_html( wp_date( 'M j, Y', strtotime( $ewo_cp->post_date ) ) ); ?></span>
						</div>
						<a href="<?php echo esc_url( $ewo_post_url ); ?>" class="ewo-cwp-read-more">Read More →</a>
					</div>
				</div>

			</div><!-- .ewo-cwp-cat-card -->
			<?php endforeach; ?>
		</div><!-- .ewo-cwp-grid--cats -->
		<?php endif; ?>

		<!-- Uncategorized fallback -->
		<?php if ( ! empty( $ewo_orphan_q ) ) : ?>
		<div class="ewo-cwp-uncategorized">
			<h2 class="ewo-cwp-section-title">Other Posts</h2>
			<div class="ewo-cwp-grid">
				<?php foreach ( $ewo_orphan_q as $ewo_op ) :
					$ewo_op_url  = $ewo_cw_base . $ewo_op->post_name . '/';
					$ewo_op_t    = get_the_post_thumbnail_url( $ewo_op->ID, 'medium_large' );
					$ewo_op_auth = ewo_cwp_author( $ewo_op->ID );
					$ewo_op_exc  = wp_trim_words( wp_strip_all_tags( $ewo_op->post_content ), 20 );
				?>
				<div class="ewo-cwp-card">
					<?php if ( $ewo_op_t ) : ?>
						<a href="<?php echo esc_url( $ewo_op_url ); ?>" class="ewo-cwp-card-img-link">
							<img class="ewo-cwp-card-img" src="<?php echo esc_url( $ewo_op_t ); ?>" alt="<?php echo esc_attr( $ewo_op->post_title ); ?>" loading="lazy" />
						</a>
					<?php endif; ?>
					<div class="ewo-cwp-card-body">
						<h2 class="ewo-cwp-card-title"><a href="<?php echo esc_url( $ewo_op_url ); ?>"><?php echo esc_html( $ewo_op->post_title ); ?></a></h2>
						<?php if ( $ewo_op_exc ) : ?><p class="ewo-cwp-card-excerpt"><?php echo esc_html( $ewo_op_exc ); ?></p><?php endif; ?>
						<div class="ewo-cwp-card-footer">
							<div class="ewo-cwp-card-meta">
								<?php if ( $ewo_op_auth ) : ?><span class="ewo-cwp-card-author"><?php echo esc_html( $ewo_op_auth ); ?></span><span class="ewo-cwp-dot">·</span><?php endif; ?>
								<span class="ewo-cwp-card-date"><?php echo esc_html( wp_date( 'M j, Y', strtotime( $ewo_op->post_date ) ) ); ?></span>
							</div>
							<a href="<?php echo esc_url( $ewo_op_url ); ?>" class="ewo-cwp-read-more">Read More →</a>
						</div>
					</div>
				</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php endif; ?>

		<?php endif; // not empty ?>

	</div><!-- .ewo-cwp-body -->
</main>
<?php
endif;

wp_reset_postdata();
get_footer();
