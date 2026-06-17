=== EWO YouTube Integration ===
Contributors: ewo
Tags: youtube, ewo
Requires at least: 6.0
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds YouTube-related admin content types and settings for EWO.

== Description ==

EWO YouTube Integration creates admin-only custom post types for YouTube videos, playlists, and community posts. It also adds an EWO YouTube settings page for API credentials and sync configuration.

Use the [ewo_youtube_marquee] shortcode or ewo_youtube_marquee() PHP function to display the latest long-form YouTube videos. Use [ewo_youtube_playlists] or ewo_youtube_playlists() to display playlist cards. Use the [ewo_youtube_shorts] shortcode or ewo_youtube_shorts() PHP function to display recent YouTube Shorts. Use the admin Sync Videos button to pull latest channel uploads from the YouTube Data API.

== Changelog ==

= 0.1.0 =
* Initial plugin structure.
* Added frontend video marquee shortcode and render function.
* Added frontend Shorts grid shortcode and render function.
* Added manual YouTube Data API video sync.
* Added bulk URL import screen with metadata lookup and video management actions.
* Added manual playlist management and frontend playlist cards.
