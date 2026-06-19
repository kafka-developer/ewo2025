# EWO RSS Engine

Internal RSS/news ingestion engine for **Emerging World Order 2025**.

Standalone WordPress plugin. It is **independent of Smart Feed Reader** — it does not modify, extend, or depend on that plugin in any way.

- **Version:** 0.1.0
- **Requires WordPress:** 6.0+
- **Requires PHP:** 7.4+

## What it does (Phase 1)

Imports items from configured RSS/Atom feeds (e.g. Substack) into **Analysis posts**, with:

- **Feed sources** managed as a custom post type (`ewo_rss_source`), each with its own feed URL, target category, post status, and per-run item cap.
- **Scheduled imports** via WP-Cron (hourly by default).
- **Deduplication** — each feed item's GUID is stored on the created post, so re-runs never create duplicates.
- **Thumbnails** — the importer extracts an image from each item (enclosure, media tag, or first inline `<img>`) and sets it as the featured image.
- **Logs** — every run records found/created/skipped/error counts, viewable in the admin.

## Structure

```
ewo-rss-engine/
├── ewo-rss-engine.php                      Bootstrap: constants, includes, activation/deactivation, cron
├── README.md
├── includes/
│   ├── class-ewo-rss-engine.php            Coordinator — wires components together
│   ├── class-ewo-rss-sources.php           Feed-source CPT + settings meta box
│   ├── class-ewo-rss-importer.php          Feed fetch → posts, with dedup
│   ├── class-ewo-rss-thumbnails.php        Featured-image extraction + sideload
│   ├── class-ewo-rss-scheduler.php         Binds the import routine to WP-Cron
│   ├── class-ewo-rss-admin.php             Admin menu, dashboard, logs, manual run
│   ├── class-ewo-rss-logs.php              Capped log store (option-backed)
│   └── index.php
└── assets/
    ├── css/admin.css
    ├── js/admin.js
    └── index.php
```

## Usage

1. Copy the `ewo-rss-engine` folder into `wp-content/plugins/` and activate **EWO RSS Engine**.
2. Open **EWO RSS Engine → Feed Sources → Add New**, give it a title, paste a feed URL, choose a target category and post status, and enable it.
3. Imports run automatically each hour. Use **EWO RSS Engine → Dashboard → Run All Imports Now** (or the per-source **Run** button) to import on demand.
4. Review run history under **EWO RSS Engine → Import Logs**.

## Architecture notes

- All classes are namespaced by the `EWO_RSS_` prefix; constants by `EWO_RSS_ENGINE_`.
- The cron hook is `EWO_RSS_ENGINE_CRON_HOOK` (`ewo_rss_engine_run`); it is scheduled on activation and cleared on deactivation.
- Components are instantiated and wired in `EWO_RSS_Engine::init()`.

## Changelog

### 0.1.0
- Initial release: feed-source CPT, scheduled + manual imports, GUID deduplication, thumbnail sideloading, and import logs.
