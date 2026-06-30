# Changelog

All notable changes to the EWO 2025 theme are documented here.

## Versioning & build tracking

Every distributable build is identified by **two** values, both defined in `functions.php`:

- `EWO_THEME_VERSION` — semantic version (must match the `Version:` header in `style.css`).
- `EWO_THEME_BUILD` — build ID in the form `YYYYMMDD-NN` (`NN` = the build number for that day, starting at `01`).

**Rule:** every time the ZIP is rebuilt you MUST bump `EWO_THEME_BUILD` (or `EWO_THEME_VERSION`)
and add a matching entry below. `build-theme.sh` enforces this — it refuses to package a
version+build that has already been released.

The combined token (`version+build`, e.g. `0.3.8+20260624-01`) is shown in the admin bar for
admins, emitted as an HTML comment on the homepage, and used to cache-bust CSS/JS.

## v0.3.8 — Build 20260626-22

Added:
- **Homepage Section Order Manager** (`inc/ewo-section-order.php`): New EWO Settings → Section Order admin page. Provides a drag-and-drop (jQuery UI Sortable) + Up/Down arrow UI to reorder the three section groups (Top/Main/Bottom). Also surfaces feature-visibility toggles for all sections that have a feature gate, so order and visibility can be managed in one place. Save handler merges only homepage-section keys back into `ewo_2025_feature_visibility` (preserving unrelated keys). Reset handler deletes the `ewo_2025_section_order` option to restore defaults.
- **Section Renderer Functions** (`inc/ewo-section-renderers.php`): Extracts every homepage section from `front-page.php` into a standalone `ewo_2025_render_section_*()` function. Each function is self-contained (computes its own URLs internally). Feature-visibility checks remain the caller's responsibility; render functions only guard against missing data (no posts, no domain data, etc.).
- **Dynamic front-page.php**: Replaces the hardcoded section blocks with a loop over `ewo_2025_so_get_order()` / `ewo_2025_so_registry()`. The `ewo-home-layout` wrapper (main column + sidebar) is fully preserved. Section order and visibility are now admin-controlled without editing PHP.

## v0.3.8 — Build 20260626-21

Changed:
- **Footer icons colored by platform brand** (`style.css`): Added per-modifier color rules for all platform icon classes (`ewo-footer-icon--youtube` → #ff0000, substack → #ff6719, spotify → #1ed760, tiktok → #ee1d52, x → #e7e7e7, amazon → #ff9900, rumble → #85c742, telegram → #229ed9, linkedin → #0a66c2, email → gold). Icons show brand color at rest; hover still brightens to white with gold border.

## v0.3.8 — Build 20260626-20

Changed:
- **Footer shows all footer-enabled socials from EWO Settings** (`inc/ewo-social-links.php`, `footer.php`): Added a plain `'footer'` surface to `ewo_2025_get_platform_surface_links()` that filters solely by the admin `footer` toggle (enabled + footer=true + url not empty) with no hardcoded platform property gate. Footer now switches from `'footer_icon'` surface (which required `icon_row=true` in the registry, silently hiding Substack/Spotify/TikTok) to the new `'footer'` surface. Any platform enabled with footer=true in EWO Settings now appears as an icon in the footer.

## v0.3.8 — Build 20260626-19

Changed:
- **Footer driven by EWO Settings** (`footer.php`, `style.css`): Footer now reads all dynamic data from the centralized `ewo_2025_social_links` option. Copyright comes from `get_bloginfo('name')`. Contact link comes from `ewo_2025_get_platform_settings('email')` — only rendered when email platform is enabled and footer=true. Social icons come from `ewo_2025_get_platform_surface_links('footer_icon')` (enabled + footer + icon_row platforms, email excluded since it is the Contact link). Layout is a three-zone flex row: copyright (flex:1, left), Contact (natural width, center), social icons (flex:1, justify-content:flex-end, right). Restored `ewo-footer-icon` CSS rules (36×36 px rounded button, dark background, hover gold border) removed in build 18. Added mobile stacking at ≤680px (column, centered). No hardcoded URLs, no hardcoded visibility — all controlled by EWO Settings admin toggles.

## v0.3.8 — Build 20260626-18

Changed:
- **Footer simplified to legal bar only** (`footer.php`, `style.css`, `main.css`): Removed newsletter/dispatch band, brand column, platform card grid, social icon buttons, and all multi-column footer structure. Footer now contains only a single `site-footer__container` div with copyright text (left) and Contact link (right). Removed all associated CSS: `site-footer__band--newsletter/--platforms`, `site-footer__brand`, `site-footer__logo`, `site-footer__name`, `site-footer__tagline`, `site-footer__platforms`, `site-footer__platform-grid`, `ewo-footer-platform` (all variants), `site-footer__social-row`, `ewo-footer-icon` (all variants), `ewo-newsletter__form`, and newsletter content element rules. Removed dead `site-footer__inner` flex/padding rules and footer responsive band breakpoints. Kept only `.site-footer`, `.site-footer__container` centering, `.site-footer__legal` flex row, and `.site-footer__legal-link` rules.

## v0.3.8 — Build 20260626-17

Changed:
- **Footer full rewrite** (`footer.php`, `style.css`, `main.css`): Replaced the three `site-footer__inner` wrapper divs with a clean two-level structure — a full-width `site-footer__band` outer div (provides background/border) and a shared `site-footer__container` inner div (provides centering). All three rows (newsletter, brand+platforms, legal) use the identical `site-footer__container` class, so their left and right edges are guaranteed to be pixel-identical. Removed the `is_front_page()` conditional class additions and the hidden `site-footer__divider` element. Removed the `.site-footer__newsletter` display/grid rule from `main.css` (now handled by `.site-footer__band--newsletter .site-footer__container` in `style.css`). Replaced `.site-footer__inner--platforms` grid rule with `.site-footer__band--platforms .site-footer__container`. Updated responsive breakpoints to target new class names. Computed left edge for all rows at 1440px: 123px.

## v0.3.8 — Build 20260626-16

Fixed:
- **Debug CSS removed** (`main.css`): Removed temporary outline debug CSS added in build 20260626-15.

## v0.3.8 — Build 20260626-15

Changed:
- **Debug CSS** (`main.css`): Added temporary outline debug CSS for footer alignment investigation (red=outer container, blue=inner content, green=card). Build 20260626-16 removed these.

## v0.3.8 — Build 20260626-14

Fixed:
- **Frontpage container alignment** (`footer.php`): On the frontpage only (`is_front_page()`), the Dispatch Channel / Newsletter section and the Footer Platforms section now receive `ewo-section` as an additional outer class, matching the container class used by the Book section and Connect section in `front-page.php`. On all other pages, the footer renders exactly as before with `site-footer__inner` only — global footer styling is unchanged. The shared container class on the frontpage is now `ewo-section` for all four bottom sections.

## v0.3.8 — Build 20260626-13

Fixed:
- **Container alignment — Platform Network** (`front-page.php`): Moved `#connect` (Platform Network / Connect With EWO section) outside `ewo-home-layout > ewo-home-main`. It is now a direct child of `site-main--home`, the same level as the Book section. All four sections (Platform Network, Book, Dispatch Channel, Footer Platforms) now use an identical container formula: `width: min(100% − 32px, 1180px); margin: 0 auto` on a full-width parent. Platform Network and Book use the `.ewo-section` class on `site-main--home` (which is `width: 100%`); Dispatch Channel and Footer Platforms use `site-footer__inner` on `site-footer` (also full-width). Computed left edge at all viewports above 1212px: `(viewport − 1180px) / 2` — identical for all four.

## v0.3.8 — Build 20260626-12

Fixed:
- **Book section centering** (`front-page.php`): Moved `#book` section outside `ewo-home-layout > ewo-home-main` so it uses the standard `.ewo-section` container (`max-width: 1180px; margin: 0 auto; width: min(100%-32px, 1180px)`) — identical to `.site-footer__inner`. Previously the book was inside the sidebar layout's main column, which positioned it with a different left-edge offset than the footer at all viewport widths above 1180px.
- **Book section spacing** (`assets/css/main.css`): Added `.site-main--home > .ewo-section.ewo-book-section { margin-top/bottom: clamp(40px, 5vw, 72px); }` to add outer vertical margin now that the book card sits between the layout and the footer.
- **Footer platform grid** (`style.css`): Changed `.site-footer__inner--platforms` from 3-column (`minmax(230px, 0.82fr) 1px minmax(0, 1.3fr)`) to clean 2-column (`minmax(260px, 360px) 1fr`). Changed `align-items: stretch` → `align-items: start`. `.site-footer__divider` is now unconditionally `display: none` — the 1px divider column no longer exists in the grid.

## v0.3.8 — Build 20260626-11

Fixed:
- **Latest Analysis stretched card** (`assets/css/main.css`): Changed `align-items: stretch` → `align-items: start` on `.ewo-analysis-grid` so the featured card no longer stretches to match the full height of the right-side 2-column grid. Split the shared `height: 100%` rule — featured card now uses `height: auto` (natural height); secondary cards keep `height: 100%` to fill their own grid cells equally.
- **Book section top-alignment** (`assets/css/main.css`): Changed `align-items: center` → `align-items: start` on `.ewo-book-v1` so the cover and content column both start at the top of the card rather than being vertically centered relative to each other.
- **Footer newsletter alignment** (`assets/css/main.css`): Changed `align-items: center` → `align-items: start` on `.site-footer__newsletter` so the text copy and email form both align at the top of the grid row.

## v0.3.8 — Build 20260626-10

Removed:
- "Edit Book Settings" admin edit bar from the bottom of the public `/book/` page (`page-book.php`). Book settings are managed exclusively via EWO Settings → Book in the WordPress admin. Removed the associated `.ewo-book-pg-admin-bar` CSS block from `assets/css/book.css`. The admin-only empty-state prompt ("Book page is not configured yet") is retained since it only appears when the page has no content.

## v0.3.8 — Build 20260626-09

Changed:
- **Data audit — DB tables:** All 6 custom tables (`wp_ewo_rss_domains`, `wp_ewo_rss_subdomains`, `wp_ewo_rss_keywords`, `wp_ewo_rss_article_sources`, `wp_ewo_rss_import_log`, `wp_ewo_predictions`) serve distinct purposes and are correctly normalized. The 3-table domain hierarchy is a genuine parent→child→grandchild FK chain; predictions and article_sources both classify records against the same shared taxonomy (not duplication). No DB table merges warranted.
- **Data consolidation — options:** Migrated `ewo_2025_custom_cards` into `ewo_2025_dyn_cards` on first load. The old option stored cards in a different schema (`section`, `link_type`, `link_value`); these are converted to the dyn_cards schema (`section_id` with `builtin_` prefix, `target_type`, `target_value`). After migration the old option is deleted. Guard flag: `ewo_cc_migrated_v1`. This eliminates the last orphaned option left after the Custom Cards admin page was removed.

## v0.3.8 — Build 20260626-08

Removed:
- **`EWO Settings → Custom Cards` admin page** — duplicate of `EWO Settings → Homepage Cards`. Both UIs created cards that rendered in the same homepage sections (Latest Analysis, Strategic Playlists, Featured Cards, Custom Section). The admin page and its menu hook have been removed. All data helpers (`ewo_2025_cc_get_section`, `ewo_2025_cc_render_briefing_card`, `ewo_2025_cc_render_playlist_card`) and the `ewo_2025_custom_cards` option remain intact — existing cards continue to render on the homepage with no data migration required. New cards should be created in `EWO Settings → Homepage Cards`.

## v0.3.8 — Build 20260626-07

Fixed:
- **Removed duplicate Section Visibility block from Homepage Settings.** Latest Analysis, Strategic Playlists, Strategic Domains, and Predictions show/hide toggles were present in both Homepage Settings and Feature Visibility. The Homepage Settings copy has been removed; Feature Visibility is now the single source of truth for all section visibility. The save handler no longer writes to `ewo_2025_feature_visibility` for those keys.
- **Dynamic sections now appear in Feature Visibility.** Any section created via EWO Settings → Homepage Sections now has a toggle row in the "Custom Sections" group. Saving Feature Visibility writes the `enabled` field back to `EWO_2025_DYN_SECTIONS_OPTION` directly. The Total/Enabled/Disabled stat cards include dynamic sections in their counts. When no custom sections exist, a placeholder row links to the Homepage Sections page.

## v0.3.8 — Build 20260626-06

Fixed:
- Homepage Cards section dropdown now includes the four existing (built-in) homepage sections — Latest Analysis, Strategic Playlists, Featured Cards, and Custom Section — as an "Existing Sections (Built-in)" optgroup, in addition to any user-created Custom Sections.
- Homepage Sections admin list shows built-in sections at the top as read-only rows (with "Built-in" badge and "Managed by theme" label) so their card counts are visible and cards can be filtered from there.
- Dynamic cards assigned to built-in section IDs are now picked up and rendered by front-page.php: Latest Analysis (§7), Featured Cards (§8a), and Custom Section (§8b) append dynamic cards into their briefing-card grid; Strategic Playlists (§8) appends dynamic briefing cards in a separate analysis grid below the playlist grid.
- `ewo_2025_ds_render_all()` skips built-in sections to prevent duplicate rendering.
- Delete handler in Homepage Sections silently redirects (no-op) if a built-in ID is submitted.

## v0.3.8 — Build 20260626-05

Added:
- `EWO Settings → Homepage Sections` admin page (`inc/ewo-dynamic-sections.php`): full CRUD for dynamic homepage sections. Each section has: title, eyebrow label, description, CTA button text/URL, display order, and enabled toggle. Sections appear on the homepage in display order; disabled sections are hidden.
- `EWO Settings → Homepage Cards` admin page (same file): full CRUD for dynamic cards. Each card has: title, eyebrow label, description, featured image URL, section assignment, target type (WordPress Post / WordPress Page / Category Archive / YouTube Video / YouTube Playlist / Substack URL / RSS Article / External URL), target value (dynamic dropdown for WP content or URL field for others), display order, and enabled toggle.
- `ewo_2025_ds_render_all()` — renders all enabled dynamic sections on the homepage (injected between §8b and §9 in front-page.php).
- `ewo_2025_ds_render_section($section, $cards)` — renders a section using standard `ewo-section` + `ewo-article-grid ewo-analysis-grid` markup; first card is the large featured card, remaining cards go in `.ewo-analysis-grid__secondary`.
- `ewo_2025_ds_render_card($card, $featured)` — renders a card using `ewo-briefing-card ewo-briefing-card--dyn` classes, identical markup to the Latest Analysis briefing card.
- `ewo_2025_ds_get_all_sections()`, `ewo_2025_ds_get_enabled_sections()`, `ewo_2025_ds_get_section_by_id()`, `ewo_2025_ds_get_all_cards()`, `ewo_2025_ds_get_cards_for_section()`, `ewo_2025_ds_resolve_url()`, `ewo_2025_ds_target_type_label()` data helpers.
- Section delete also removes all orphaned cards that belong to the deleted section.
- Card form shows dynamic target fields: URL input for URL types, WP dropdown for post/page/category.
- `inc/ewo-dynamic-sections.php` loaded in `functions.php` before `ewo-feature-visibility.php` so FV can reference dynamic section data.

## v0.3.8 — Build 20260626-04

Added:
- `EWO Settings → Custom Cards` admin page (`inc/ewo-custom-cards.php`): full CRUD for custom homepage cards. Each card has: title, eyebrow/label, description, featured image URL, button/link text, link type (External URL / YouTube URL / Substack URL / WordPress Page / WordPress Post / Category Archive), link target (dynamic dropdown or URL field), section placement, display order, and enable/disable toggle.
- Four section placements for custom cards: Latest Analysis (§7), Strategic Playlists (§8), Featured Cards (new standalone §8a), Custom Section (new standalone §8b).
- `ewo_2025_cc_get_section($section)` — returns enabled custom cards for a section, sorted by display order.
- `ewo_2025_cc_render_briefing_card($card, $featured)` — renders custom card using the same `ewo-briefing-card` markup as auto-generated Latest Analysis cards.
- `ewo_2025_cc_render_playlist_card($card)` — renders custom card using the same `ewo-youtube-playlists__card` markup.
- Per-section content mode setting in `EWO Settings → Homepage Settings`: Latest Analysis and Strategic Playlists each get a mode radio (Auto only / Custom only / Mixed auto + custom). Defaults to Auto (no behavior change for existing sites).
- Featured Cards section (§8a) and Custom Section (§8b) render automatically on the homepage when enabled custom cards are assigned to them. No mode setting needed — always custom-only.

## v0.3.8 — Build 20260626-03

Added:
- `EWO Settings → Homepage Settings` admin page (`inc/ewo-homepage-settings.php`): one-stop controls for the four data-driven homepage sections. Section show/hide toggles write to the shared `ewo_2025_feature_visibility` option (stays in sync with Feature Visibility Manager). Source/count settings use a new `ewo_2025_homepage_settings` option.
- Latest Analysis: source selector (Both / WordPress only / Substack only) + card count (1–20). Source filter uses `ewo_2025_substack_source_url()` post-filter; fetches extra posts to account for exclusions.
- Strategic Playlists: filter (All / Featured only via `ewo_youtube_playlist_featured` meta) + card count (1–24). Homepage now runs its own `WP_Query` for `ewo_playlist` instead of calling `ewo_youtube_playlists()`, preserving the identical CSS markup and enqueuing `ewo-youtube-playlists` stylesheet.
- Strategic Domains: card count limit (0 = all, max 20) via `array_slice()` on `ewo_2025_sfd_index_data()` output.
- Strategic Predictions: card count (1–20) via configurable `limit` on `EWO_Predictions_DB::query()` (fetches `count × 2` to absorb archived filtering).
- `latest_analysis` added to Feature Visibility registry (defaults to Enabled) so `ewo_2025_feature_enabled('latest_analysis')` works; also appears in Feature Visibility admin page Homepage Sections group.

## v0.3.8 — Build 20260626-02

Fixed:
- Feature Visibility Manager: added missing `Platform Network Section` toggle for the Connect With EWO homepage section (§9 — YouTube, Spotify, X, Substack platform cards). Toggle appears in the Homepage Sections group; defaults to Enabled.

## v0.3.8 — Build 20260626-01

Added:
- Feature Visibility Manager (`EWO Settings → Feature Visibility`): one-stop admin page to enable/disable all major frontend sections and elements without deleting data. Toggles: YouTube Slider, Community Wall, Strategic Domains / Smart Feed, Smart Feed Page, Predictions, Featured Videos / Playlists, Book Section, Social Platform Chips (header), Sidebar Social Cards, Footer Social Links, Newsletter Section. All default to enabled — no site layout changes on first load.
- `inc/ewo-feature-visibility.php` — option `ewo_2025_feature_visibility`, `ewo_2025_feature_enabled($key)` helper, admin submenu under `ewo-settings`, nonce-protected POST handler `admin_post_ewo_fv_save`, dark-navy admin UI with toggle rows grouped by Homepage Sections / Pages / Social & Platform Elements.
- Visibility checks wired into `front-page.php` (§2 YouTube Slider, §4 Community Wall, §5 Strategic Domains, §6 Predictions, §8 Featured Videos, §10 Book), `header.php` (Platform Chips), `footer.php` (Newsletter and Footer Social blocks), `inc/ewo-sidebar.php` (Follow card), and `page-smart-feed.php` (Smart Feed page content).

## v0.3.8 — Build 20260625-02

Added:
- `/book/` public page (`page-book.php`) — dark navy two-column layout: cover image/placeholder left, title/subtitle/author/description/highlights/CTA right; quote section below; admin edit bar for admins; admin-only placeholder with settings link when no content is configured.
- `assets/css/book.css` — page-scoped dark-navy styles for the book page (cover frame, highlights checklist, gold CTA, quote blockquote).
- `EWO Settings → Book` admin submenu (`inc/ewo-book.php`) — full settings page: title, subtitle, author, cover image (URL + WP media picker), description, highlights textarea (one per line), quote/attribution, Amazon URL, button text, section visibility toggles; nonce-protected POST handler `admin_post_ewo_book_save`; stats cards showing what's configured.
- Auto-creates `/book/` WP page on first load (guarded by `ewo_2025_book_page_v1` option).
- `ewo_2025_get_book_settings()` / `ewo_2025_book_has_content()` data helpers in `inc/ewo-book.php`.

## v0.3.8 — Build 20260625-01

Added:
- Social Links admin (`EWO Settings → Social Links`): full dark-navy management UI for all platform chips. Per-platform: short code badge, name, URL, Header/Footer/Side Card/Enabled toggles, display sort order, delete (custom platforms). Stats cards show total / enabled / header count / footer count / custom count.
- "Add New Platform" form lets admins add custom platforms (short code, name, tagline, URL, toggles, order) — they appear in the header chips immediately.
- `ewo_2025_render_header_chips()` — replaces the hardcoded key list in `header.php`; header chips now respect the stored `header` toggle and `sort_order` for both registry and custom platforms.
- `header` and `sort_order` fields added to the `ewo_2025_social_links` option schema (backward-compatible; defaults mirror existing behavior).
- `_custom` bucket in option stores user-added platforms; `ewo_2025_get_custom_platforms()` reads them.
- Delete custom platform via nonce-protected GET; add via nonce-protected POST to `admin_post_ewo_social_add`.

## v0.3.8 — Build 20260624-02

Added:
- Strategic Domains public page: `/strategic-domains/` (index grid) and `/strategic-domains/{slug}/` (domain detail) driven entirely by admin-created taxonomy data from the EWO RSS Engine plugin.
- `inc/ewo-sfd-data.php` — data layer for the public page (domains → subdomains → keywords → sources).
- `page-strategic-domains.php` — handles both index and detail views.
- `assets/css/strategic-domains.css` — dark-card visual language matching theme.
- EWO RSS Engine: `description` column added to `wp_ewo_rss_domains` table (schema v2).
- Admin Strategic Domains UI: description textarea in add-domain form and inline edit.

## [Unreleased] — toward v0.4.0

Added:
- EWO Media Hub layout
- Separate Videos, Shorts, TikTok, Playlists, Community sections
- Footer platform cards

Fixed:
- Analysis page routing
- Analysis grid layout
- Duplicate Substack images
- Subscriber-only Substack handling
- Homepage Analysis links
- Video page rendering template

Changed:
- Videos page redesigned as Media Hub
- Footer redesigned as platform ecosystem

## v0.3.8 — Build 20260624-01

Added:
- Strict build tracking: `EWO_THEME_BUILD` build ID alongside `EWO_THEME_VERSION`.
- Admin-bar node now shows `EWO Theme v0.3.8 / Build 20260624-01` for admins.
- Homepage source now carries an `<!-- EWO Theme v0.3.8 | Build 20260624-01 -->` comment.
- `build-theme.sh` packaging script that enforces a version/build bump per rebuild.

Changed:
- CSS/JS cache-busting token now combines the semantic version and build ID.
