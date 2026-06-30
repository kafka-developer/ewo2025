<?php
/** EWO Book page — admin settings, data helpers. @package EWO_2025 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

const EWO_2025_BOOK_OPTION = 'ewo_2025_book_settings';

/* ============================================================
   Data helpers
   ============================================================ */

function ewo_2025_get_book_defaults() {
	return array(
		'title'             => '',
		'subtitle'          => '',
		'author'            => '',
		'cover_image_id'    => 0,
		'cover_image_url'   => '',
		'description'       => '',
		'highlights'        => array(),
		'quote'             => '',
		'quote_attribution' => '',
		'amazon_url'        => '',
		'button_text'       => __( 'Buy on Amazon', 'ewo-2025' ),
		'show_cover'        => 1,
		'show_highlights'   => 1,
		'show_quote'        => 1,
	);
}

/** Returns the saved book settings merged with defaults. */
function ewo_2025_get_book_settings() {
	$defaults = ewo_2025_get_book_defaults();
	$saved    = get_option( EWO_2025_BOOK_OPTION, array() );
	if ( ! is_array( $saved ) ) { $saved = array(); }
	// array_merge is shallow; restore highlights array properly.
	$merged = array_merge( $defaults, $saved );
	if ( ! is_array( $merged['highlights'] ) ) { $merged['highlights'] = array(); }
	return $merged;
}

/** True if the book has enough data to render a useful page. */
function ewo_2025_book_has_content() {
	$s = ewo_2025_get_book_settings();
	return '' !== $s['title'] || '' !== $s['description'] || '' !== $s['amazon_url'];
}

/* ============================================================
   Admin menu
   ============================================================ */

function ewo_2025_book_admin_menu() {
	add_submenu_page(
		'ewo-settings',
		__( 'Book Settings', 'ewo-2025' ),
		__( 'Book', 'ewo-2025' ),
		'manage_options',
		'ewo-settings-book',
		'ewo_2025_render_book_settings_page'
	);
}
add_action( 'admin_menu', 'ewo_2025_book_admin_menu' );

/* ============================================================
   Save handler
   ============================================================ */

function ewo_2025_handle_book_save() {
	check_admin_referer( 'ewo_book_save' );
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Forbidden' ); }

	$raw = ( isset( $_POST['ewo_book'] ) && is_array( $_POST['ewo_book'] ) )
		? $_POST['ewo_book']  // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce checked above.
		: array();

	// Highlights: textarea, one item per line.
	$hl_raw     = isset( $raw['highlights_raw'] ) ? wp_unslash( (string) $raw['highlights_raw'] ) : '';
	$highlights = array_values(
		array_filter(
			array_map( 'sanitize_text_field', preg_split( '/\r?\n/', $hl_raw ) )
		)
	);

	$settings = array(
		'title'             => sanitize_text_field( wp_unslash( $raw['title'] ?? '' ) ),
		'subtitle'          => sanitize_text_field( wp_unslash( $raw['subtitle'] ?? '' ) ),
		'author'            => sanitize_text_field( wp_unslash( $raw['author'] ?? '' ) ),
		'cover_image_id'    => absint( $raw['cover_image_id'] ?? 0 ),
		'cover_image_url'   => esc_url_raw( wp_unslash( $raw['cover_image_url'] ?? '' ) ),
		'description'       => wp_kses_post( wp_unslash( $raw['description'] ?? '' ) ),
		'highlights'        => $highlights,
		'quote'             => sanitize_text_field( wp_unslash( $raw['quote'] ?? '' ) ),
		'quote_attribution' => sanitize_text_field( wp_unslash( $raw['quote_attribution'] ?? '' ) ),
		'amazon_url'        => esc_url_raw( wp_unslash( $raw['amazon_url'] ?? '' ) ),
		'button_text'       => sanitize_text_field( wp_unslash( $raw['button_text'] ?? __( 'Buy on Amazon', 'ewo-2025' ) ) ),
		'show_cover'        => ! empty( $raw['show_cover'] )      ? 1 : 0,
		'show_highlights'   => ! empty( $raw['show_highlights'] ) ? 1 : 0,
		'show_quote'        => ! empty( $raw['show_quote'] )      ? 1 : 0,
	);

	update_option( EWO_2025_BOOK_OPTION, $settings );
	wp_safe_redirect( admin_url( 'admin.php?page=ewo-settings-book&saved=1' ) );
	exit;
}
add_action( 'admin_post_ewo_book_save', 'ewo_2025_handle_book_save' );

/* ============================================================
   Admin render
   ============================================================ */

function ewo_2025_render_book_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) { return; }

	wp_enqueue_media();

	$s              = ewo_2025_get_book_settings();
	$hl_text        = implode( "\n", $s['highlights'] );
	$saved          = isset( $_GET['saved'] ) && '1' === sanitize_key( $_GET['saved'] );
	$cover_id       = (int) $s['cover_image_id'];
	$cover_thumb    = $cover_id ? wp_get_attachment_image_url( $cover_id, 'medium' ) : $s['cover_image_url'];
	$book_page      = get_page_by_path( 'book' );
	$book_page_url  = $book_page ? get_permalink( $book_page ) : home_url( '/book/' );
	?>
<div class="ewo-bk-wrap">
<style>
.ewo-bk-wrap *{box-sizing:border-box}
.ewo-bk-wrap{background:#060f1e;color:#dde8f5;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;min-height:100vh;padding:0 0 80px}
.ewo-bk-page-header{background:#0b1829;border-bottom:1px solid rgba(50,100,160,.28);padding:26px 32px 22px;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap}
.ewo-bk-page-header h1{color:#fff;font-size:1.4rem;font-weight:700;margin:0 0 5px}
.ewo-bk-page-header p{color:#6b88b5;font-size:.82rem;margin:0}
.ewo-bk-preview-link{align-items:center;background:rgba(215,168,75,.1);border:1px solid rgba(215,168,75,.3);border-radius:6px;color:#d7a84b;display:inline-flex;font-size:.75rem;font-weight:600;gap:5px;padding:7px 14px;text-decoration:none;white-space:nowrap;transition:background 140ms}
.ewo-bk-preview-link:hover{background:rgba(215,168,75,.2);color:#d7a84b}
.ewo-bk-inner{padding:26px 32px 0;max-width:900px}
.ewo-bk-notice{background:rgba(45,184,122,.12);border:1px solid rgba(45,184,122,.3);border-radius:6px;color:#2db87a;font-size:.82rem;font-weight:600;margin-bottom:22px;padding:10px 16px}
.ewo-bk-stats{display:grid;grid-template-columns:repeat(5,1fr);gap:10px;margin-bottom:26px}
.ewo-bk-stat{background:#0b1829;border:1px solid rgba(50,100,160,.28);border-radius:8px;padding:12px 14px}
.ewo-bk-stat__num{color:#fff;font-size:1.4rem;font-weight:800;line-height:1}
.ewo-bk-stat__label{color:#6b88b5;font-size:.67rem;font-weight:700;letter-spacing:.08em;margin-top:4px;text-transform:uppercase}
.ewo-bk-card{background:#0b1829;border:1px solid rgba(50,100,160,.28);border-radius:10px;margin-bottom:20px;overflow:hidden}
.ewo-bk-card-head{background:rgba(0,0,0,.2);border-bottom:1px solid rgba(50,100,160,.18);padding:12px 18px;display:flex;align-items:center;justify-content:space-between}
.ewo-bk-card-title{color:#8aaad4;font-size:.68rem;font-weight:800;letter-spacing:.12em;margin:0;text-transform:uppercase}
.ewo-bk-card-body{padding:18px}
.ewo-bk-field{margin-bottom:16px}
.ewo-bk-field:last-child{margin-bottom:0}
.ewo-bk-label{color:#6b88b5;display:block;font-size:.7rem;font-weight:700;letter-spacing:.07em;margin-bottom:5px;text-transform:uppercase}
.ewo-bk-input{background:#0f2035;border:1px solid rgba(50,100,160,.4);border-radius:5px;color:#dde8f5;font-size:.85rem;padding:7px 11px;width:100%}
.ewo-bk-input:focus{border-color:#d7a84b;outline:none}
.ewo-bk-textarea{background:#0f2035;border:1px solid rgba(50,100,160,.4);border-radius:5px;color:#dde8f5;font-family:inherit;font-size:.85rem;min-height:90px;padding:8px 11px;resize:vertical;width:100%}
.ewo-bk-textarea:focus{border-color:#d7a84b;outline:none}
.ewo-bk-row2{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.ewo-bk-cover-row{display:flex;align-items:flex-start;gap:16px}
.ewo-bk-cover-preview{background:#060f1e;border:1px solid rgba(50,100,160,.4);border-radius:6px;flex:0 0 90px;height:120px;object-fit:cover;width:90px}
.ewo-bk-cover-preview--empty{align-items:center;color:#6b88b5;display:flex;font-size:.7rem;font-weight:600;justify-content:center;letter-spacing:.06em;text-transform:uppercase}
.ewo-bk-cover-fields{flex:1;display:flex;flex-direction:column;gap:8px}
.ewo-bk-img-btn{background:rgba(50,100,160,.18);border:1px solid rgba(50,100,160,.4);border-radius:5px;color:#8aaad4;cursor:pointer;font-size:.75rem;font-weight:600;padding:6px 12px;transition:all 140ms;white-space:nowrap}
.ewo-bk-img-btn:hover{background:rgba(215,168,75,.12);border-color:rgba(215,168,75,.4);color:#d7a84b}
.ewo-bk-toggle-row{display:flex;gap:20px;flex-wrap:wrap}
.ewo-bk-toggle-label{align-items:center;color:#8aaad4;cursor:pointer;display:flex;font-size:.82rem;font-weight:600;gap:6px}
.ewo-bk-toggle-label input{accent-color:#d7a84b;height:14px;width:14px}
.ewo-bk-hint{color:#6b88b5;font-size:.72rem;margin-top:4px}
.ewo-bk-btn{background:#d7a84b;border:0;border-radius:6px;color:#0a0600;cursor:pointer;font-size:.85rem;font-weight:700;letter-spacing:.03em;padding:11px 26px;transition:background 140ms}
.ewo-bk-btn:hover{background:#b08020}
.ewo-bk-save-row{align-items:center;display:flex;gap:14px;padding-top:6px}
@media(max-width:720px){.ewo-bk-stats{grid-template-columns:repeat(3,1fr)}.ewo-bk-row2{grid-template-columns:1fr}.ewo-bk-inner{padding:18px 16px 0}.ewo-bk-page-header{padding:18px 16px 14px}}
</style>

<div class="ewo-bk-page-header">
	<div>
		<h1><?php esc_html_e( 'Book Settings', 'ewo-2025' ); ?></h1>
		<p><?php esc_html_e( 'Manage content for the /book/ frontend page.', 'ewo-2025' ); ?></p>
	</div>
	<a href="<?php echo esc_url( $book_page_url ); ?>" target="_blank" rel="noopener" class="ewo-bk-preview-link">
		&#8599; <?php esc_html_e( 'View Book Page', 'ewo-2025' ); ?>
	</a>
</div>

<div class="ewo-bk-inner">

<?php if ( $saved ) : ?>
	<div class="ewo-bk-notice"><?php esc_html_e( 'Book settings saved.', 'ewo-2025' ); ?></div>
<?php endif; ?>

<!-- Stats -->
<div class="ewo-bk-stats">
	<div class="ewo-bk-stat">
		<div class="ewo-bk-stat__num"><?php echo '' !== $s['title'] ? '✓' : '—'; ?></div>
		<div class="ewo-bk-stat__label"><?php esc_html_e( 'Title', 'ewo-2025' ); ?></div>
	</div>
	<div class="ewo-bk-stat">
		<div class="ewo-bk-stat__num"><?php echo '' !== $s['cover_image_url'] || $cover_id ? '✓' : '—'; ?></div>
		<div class="ewo-bk-stat__label"><?php esc_html_e( 'Cover', 'ewo-2025' ); ?></div>
	</div>
	<div class="ewo-bk-stat">
		<div class="ewo-bk-stat__num"><?php echo '' !== $s['description'] ? '✓' : '—'; ?></div>
		<div class="ewo-bk-stat__label"><?php esc_html_e( 'Description', 'ewo-2025' ); ?></div>
	</div>
	<div class="ewo-bk-stat">
		<div class="ewo-bk-stat__num"><?php echo count( $s['highlights'] ); ?></div>
		<div class="ewo-bk-stat__label"><?php esc_html_e( 'Highlights', 'ewo-2025' ); ?></div>
	</div>
	<div class="ewo-bk-stat">
		<div class="ewo-bk-stat__num"><?php echo '' !== $s['amazon_url'] ? '✓' : '—'; ?></div>
		<div class="ewo-bk-stat__label"><?php esc_html_e( 'Amazon URL', 'ewo-2025' ); ?></div>
	</div>
</div>

<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<?php wp_nonce_field( 'ewo_book_save' ); ?>
	<input type="hidden" name="action" value="ewo_book_save">

	<!-- Identity & Cover -->
	<div class="ewo-bk-card">
		<div class="ewo-bk-card-head"><h2 class="ewo-bk-card-title"><?php esc_html_e( 'Identity & Cover', 'ewo-2025' ); ?></h2></div>
		<div class="ewo-bk-card-body">

			<div class="ewo-bk-row2" style="margin-bottom:16px">
				<div class="ewo-bk-field">
					<label class="ewo-bk-label" for="bk-title"><?php esc_html_e( 'Book Title', 'ewo-2025' ); ?></label>
					<input id="bk-title" type="text" name="ewo_book[title]"
						value="<?php echo esc_attr( $s['title'] ); ?>"
						placeholder="<?php esc_attr_e( 'Emerging World Order 2025', 'ewo-2025' ); ?>"
						class="ewo-bk-input">
				</div>
				<div class="ewo-bk-field">
					<label class="ewo-bk-label" for="bk-subtitle"><?php esc_html_e( 'Subtitle / Tagline', 'ewo-2025' ); ?></label>
					<input id="bk-subtitle" type="text" name="ewo_book[subtitle]"
						value="<?php echo esc_attr( $s['subtitle'] ); ?>"
						placeholder="<?php esc_attr_e( 'A Framework for Reading Global Power', 'ewo-2025' ); ?>"
						class="ewo-bk-input">
				</div>
			</div>

			<div class="ewo-bk-field" style="margin-bottom:16px">
				<label class="ewo-bk-label" for="bk-author"><?php esc_html_e( 'Author Name', 'ewo-2025' ); ?></label>
				<input id="bk-author" type="text" name="ewo_book[author]"
					value="<?php echo esc_attr( $s['author'] ); ?>"
					placeholder="<?php esc_attr_e( 'EWO Research Team', 'ewo-2025' ); ?>"
					class="ewo-bk-input" style="max-width:320px">
			</div>

			<div class="ewo-bk-field">
				<label class="ewo-bk-label"><?php esc_html_e( 'Cover Image', 'ewo-2025' ); ?></label>
				<div class="ewo-bk-cover-row">
					<?php if ( $cover_thumb ) : ?>
						<img id="ewo-bk-cover-preview" src="<?php echo esc_url( $cover_thumb ); ?>"
							class="ewo-bk-cover-preview" alt="">
					<?php else : ?>
						<div id="ewo-bk-cover-preview" class="ewo-bk-cover-preview ewo-bk-cover-preview--empty">
							<?php esc_html_e( 'No image', 'ewo-2025' ); ?>
						</div>
					<?php endif; ?>
					<div class="ewo-bk-cover-fields">
						<input id="ewo-bk-cover-url" type="url" name="ewo_book[cover_image_url]"
							value="<?php echo esc_attr( $s['cover_image_url'] ); ?>"
							placeholder="https://"
							class="ewo-bk-input">
						<input id="ewo-bk-cover-id" type="hidden" name="ewo_book[cover_image_id]"
							value="<?php echo esc_attr( $cover_id ); ?>">
						<button type="button" id="ewo-bk-select-cover" class="ewo-bk-img-btn">
							<?php esc_html_e( '&#128247; Select from Media Library', 'ewo-2025' ); ?>
						</button>
						<span class="ewo-bk-hint"><?php esc_html_e( 'Or paste a direct URL above. Recommended: 600 × 900 px.', 'ewo-2025' ); ?></span>
					</div>
				</div>
			</div>

		</div>
	</div>

	<!-- Description -->
	<div class="ewo-bk-card">
		<div class="ewo-bk-card-head"><h2 class="ewo-bk-card-title"><?php esc_html_e( 'Description', 'ewo-2025' ); ?></h2></div>
		<div class="ewo-bk-card-body">
			<div class="ewo-bk-field">
				<label class="ewo-bk-label" for="bk-desc"><?php esc_html_e( 'Book Description', 'ewo-2025' ); ?></label>
				<textarea id="bk-desc" name="ewo_book[description]" class="ewo-bk-textarea" rows="5"
					placeholder="<?php esc_attr_e( 'A compelling description of the book…', 'ewo-2025' ); ?>"
				><?php echo esc_textarea( $s['description'] ); ?></textarea>
				<span class="ewo-bk-hint"><?php esc_html_e( 'Basic HTML allowed (p, strong, em, a, ul, ol, li).', 'ewo-2025' ); ?></span>
			</div>
		</div>
	</div>

	<!-- Highlights -->
	<div class="ewo-bk-card">
		<div class="ewo-bk-card-head">
			<h2 class="ewo-bk-card-title"><?php esc_html_e( 'Highlights / Bullets', 'ewo-2025' ); ?></h2>
			<label class="ewo-bk-toggle-label">
				<input type="checkbox" name="ewo_book[show_highlights]" value="1" <?php checked( $s['show_highlights'] ); ?>>
				<?php esc_html_e( 'Show on page', 'ewo-2025' ); ?>
			</label>
		</div>
		<div class="ewo-bk-card-body">
			<div class="ewo-bk-field">
				<label class="ewo-bk-label" for="bk-highlights"><?php esc_html_e( 'One highlight per line', 'ewo-2025' ); ?></label>
				<textarea id="bk-highlights" name="ewo_book[highlights_raw]" class="ewo-bk-textarea" rows="5"
					placeholder="<?php esc_attr_e( "Systems-based geopolitical framework\nWhy alliances are breaking down\nHow to read global power shifts", 'ewo-2025' ); ?>"
				><?php echo esc_textarea( $hl_text ); ?></textarea>
			</div>
		</div>
	</div>

	<!-- Quote / Testimonial -->
	<div class="ewo-bk-card">
		<div class="ewo-bk-card-head">
			<h2 class="ewo-bk-card-title"><?php esc_html_e( 'Quote / Testimonial', 'ewo-2025' ); ?></h2>
			<label class="ewo-bk-toggle-label">
				<input type="checkbox" name="ewo_book[show_quote]" value="1" <?php checked( $s['show_quote'] ); ?>>
				<?php esc_html_e( 'Show on page', 'ewo-2025' ); ?>
			</label>
		</div>
		<div class="ewo-bk-card-body">
			<div class="ewo-bk-field" style="margin-bottom:12px">
				<label class="ewo-bk-label" for="bk-quote"><?php esc_html_e( 'Quote text', 'ewo-2025' ); ?></label>
				<textarea id="bk-quote" name="ewo_book[quote]" class="ewo-bk-textarea" rows="3"
					placeholder="<?php esc_attr_e( 'An essential read for anyone trying to make sense of a fracturing world.', 'ewo-2025' ); ?>"
				><?php echo esc_textarea( $s['quote'] ); ?></textarea>
			</div>
			<div class="ewo-bk-field">
				<label class="ewo-bk-label" for="bk-quote-attr"><?php esc_html_e( 'Attribution', 'ewo-2025' ); ?></label>
				<input id="bk-quote-attr" type="text" name="ewo_book[quote_attribution]"
					value="<?php echo esc_attr( $s['quote_attribution'] ); ?>"
					placeholder="<?php esc_attr_e( 'Jane Smith, Strategic Analyst', 'ewo-2025' ); ?>"
					class="ewo-bk-input" style="max-width:380px">
			</div>
		</div>
	</div>

	<!-- Purchase / CTA -->
	<div class="ewo-bk-card">
		<div class="ewo-bk-card-head"><h2 class="ewo-bk-card-title"><?php esc_html_e( 'Purchase / CTA', 'ewo-2025' ); ?></h2></div>
		<div class="ewo-bk-card-body">
			<div class="ewo-bk-row2">
				<div class="ewo-bk-field">
					<label class="ewo-bk-label" for="bk-amazon"><?php esc_html_e( 'Amazon / KDP URL', 'ewo-2025' ); ?></label>
					<input id="bk-amazon" type="url" name="ewo_book[amazon_url]"
						value="<?php echo esc_attr( $s['amazon_url'] ); ?>"
						placeholder="https://www.amazon.com/dp/..."
						class="ewo-bk-input">
				</div>
				<div class="ewo-bk-field">
					<label class="ewo-bk-label" for="bk-btn-text"><?php esc_html_e( 'Button Text', 'ewo-2025' ); ?></label>
					<input id="bk-btn-text" type="text" name="ewo_book[button_text]"
						value="<?php echo esc_attr( $s['button_text'] ); ?>"
						placeholder="<?php esc_attr_e( 'Buy on Amazon', 'ewo-2025' ); ?>"
						class="ewo-bk-input">
				</div>
			</div>
		</div>
	</div>

	<!-- Section visibility -->
	<div class="ewo-bk-card">
		<div class="ewo-bk-card-head"><h2 class="ewo-bk-card-title"><?php esc_html_e( 'Page Section Visibility', 'ewo-2025' ); ?></h2></div>
		<div class="ewo-bk-card-body">
			<div class="ewo-bk-toggle-row">
				<label class="ewo-bk-toggle-label">
					<input type="checkbox" name="ewo_book[show_cover]" value="1" <?php checked( $s['show_cover'] ); ?>>
					<?php esc_html_e( 'Book cover image', 'ewo-2025' ); ?>
				</label>
				<label class="ewo-bk-toggle-label">
					<input type="checkbox" name="ewo_book[show_highlights]" value="1" <?php checked( $s['show_highlights'] ); ?>>
					<?php esc_html_e( 'Highlights / bullets', 'ewo-2025' ); ?>
				</label>
				<label class="ewo-bk-toggle-label">
					<input type="checkbox" name="ewo_book[show_quote]" value="1" <?php checked( $s['show_quote'] ); ?>>
					<?php esc_html_e( 'Quote / testimonial', 'ewo-2025' ); ?>
				</label>
			</div>
			<span class="ewo-bk-hint" style="margin-top:10px;display:block">
				<?php esc_html_e( 'Individual section toggles above (on highlight/quote cards) also control visibility.', 'ewo-2025' ); ?>
			</span>
		</div>
	</div>

	<div class="ewo-bk-save-row">
		<button type="submit" class="ewo-bk-btn"><?php esc_html_e( 'Save Book Settings', 'ewo-2025' ); ?></button>
		<span style="color:#6b88b5;font-size:.78rem">
			<?php printf(
				/* translators: %s: link to book page. */
				esc_html__( 'Changes appear immediately on the %s.', 'ewo-2025' ),
				'<a href="' . esc_url( $book_page_url ) . '" target="_blank" style="color:#d7a84b">' . esc_html__( '/book/ page', 'ewo-2025' ) . '</a>'
			); ?>
		</span>
	</div>
</form>

</div><!-- .ewo-bk-inner -->
</div><!-- .ewo-bk-wrap -->

<script>
(function() {
	var btn = document.getElementById('ewo-bk-select-cover');
	if (!btn || typeof wp === 'undefined' || !wp.media) { return; }
	var uploader;
	btn.addEventListener('click', function(e) {
		e.preventDefault();
		if (uploader) { uploader.open(); return; }
		uploader = wp.media({
			title: '<?php echo esc_js( __( 'Select Book Cover Image', 'ewo-2025' ) ); ?>',
			button: { text: '<?php echo esc_js( __( 'Use as cover', 'ewo-2025' ) ); ?>' },
			multiple: false
		});
		uploader.on('select', function() {
			var att = uploader.state().get('selection').first().toJSON();
			document.getElementById('ewo-bk-cover-url').value = att.url;
			document.getElementById('ewo-bk-cover-id').value  = att.id;
			var prev = document.getElementById('ewo-bk-cover-preview');
			if (prev.tagName === 'IMG') {
				prev.src = att.url;
			} else {
				var img = document.createElement('img');
				img.id        = 'ewo-bk-cover-preview';
				img.src       = att.url;
				img.className = 'ewo-bk-cover-preview';
				img.alt       = '';
				prev.parentNode.replaceChild(img, prev);
			}
		});
		uploader.open();
	});
})();
</script>
	<?php
}
