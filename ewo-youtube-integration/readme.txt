=== EWO YouTube Integration ===
Contributors: ewo
Tags: youtube, ewo
Requires at least: 6.0
Requires PHP: 7.4
Stable tag: 0.2.7
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds YouTube-related admin content types and settings for EWO.

== Description ==

EWO YouTube Integration creates admin-only custom post types for YouTube videos, playlists, and community posts. It also adds an EWO YouTube settings page for API credentials and sync configuration.

Use the [ewo_youtube_marquee] shortcode or ewo_youtube_marquee() PHP function to display the latest long-form YouTube videos. Use [ewo_youtube_playlists] or ewo_youtube_playlists() to display playlist cards. Use the [ewo_youtube_shorts] shortcode or ewo_youtube_shorts() PHP function to display recent YouTube Shorts. Use the admin Sync Videos button to pull latest channel uploads from the YouTube Data API.

== Changelog ==

= 0.2.7 =
* Fixed missing/broken video card thumbnails: derive a stable YouTube thumbnail from the video ID instead of relying on expiring signed CDN URLs; validate image URLs and never output an empty img src.

= 0.2.6 =
* The Videos admin list now shows long-form videos only; Shorts are managed exclusively on the Shorts screen.

= 0.2.5 =
* Added a Shorts admin page (Add / List / Edit / Delete) for managing short-form videos, placed in the submenu between Videos and Community Posts.

= 0.2.4 =
* Cleaned up the EWO YouTube admin submenu: hid the duplicate custom-post-type list links and relabeled the management pages (Videos, Community Posts) into a single, ordered submenu. No functionality removed; all pages/handlers remain registered.

= 0.2.3 =
* Homepage video section is now a single-feature slider (one video at a time): image left / details right, autoplay with hover & focus pause, arrows, dots, swipe, and a fully clickable slide.
* Keeps the 5 latest videos; respects reduced-motion; no scrollbar.

= 0.2.2 =
* Polished the Videos page with a branded header (VIDEO INTELLIGENCE / Latest Strategic Briefings + subtitle) and hid the generic theme page title.
* Premium media-card redesign: tighter height, smaller date, 2-line title with ellipsis, fully clickable card, "Watch Analysis →" CTA, optional topic badges (hidden unless topic data exists).
* Frontend/design only — no admin, data model, or API changes.

= 0.2.1 =
* Replaced the Latest Strategic Briefings horizontal scroll strip with a Netflix/Prime-style carousel (prev/next arrows + pagination dots, no scrollbar).
* Responsive cards-per-view: 4-5 desktop, 2-3 tablet, 1 mobile; arrows move one page at a time, dots jump to a page; touch swipe supported.
* Added lightweight vanilla JavaScript (assets/js/youtube-carousel.js); no external libraries.

= 0.2.0 =
* Vertical slice completion for the YouTube video workflow.
* Added a Sort Order field to the Add/Edit Video screen; it now controls the order on the Videos page.
* Video cards (thumbnail and title) are now clickable links to the YouTube video, in addition to the Watch button.

= 0.1.6 =
* Added a dedicated "Add Video" admin page for adding and managing individual videos one at a time, with edit and delete actions.
* "Add New YouTube Video" now opens the Add Video page instead of the block editor.
* "Add New YouTube Playlist" now opens the Playlists management page instead of the block editor.

= 0.1.5 =
* Aligned frontend marquee, shorts, and playlist styles with the EWO 2025 theme (theme CSS variables, rounded cards, serif headings, gold-gradient buttons).

= 0.1.4 =
* Initial plugin structure.
* Added frontend video marquee shortcode and render function.
* Added frontend Shorts grid shortcode and render function.
* Added manual YouTube Data API video sync.
* Added bulk URL import screen with metadata lookup and video management actions.
* Added manual playlist management and frontend playlist cards.
