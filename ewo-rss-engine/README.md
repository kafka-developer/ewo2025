# EWO RSS Engine

Internal RSS/news ingestion engine for **Emerging World Order 2025**.

Standalone WordPress plugin. It is **independent of Smart Feed Reader** — it does not modify, extend, or depend on that plugin in any way.

- **Version:** 0.5.0
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
│   ├── class-ewo-rss-taxonomy.php          Domains/subdomains/keywords tables + CRUD
│   ├── class-ewo-rss-keyword-feeds.php     Keyword → Google News feed + Source capture
│   ├── class-ewo-rss-extractor.php         v0.1 full-article readable-text extraction
│   ├── class-ewo-rss-source-store.php      Captured-Sources table + CRUD
│   ├── class-ewo-rss-admin-keywords.php    Strategic Keywords admin + Fetch buttons
│   ├── class-ewo-rss-admin-sources.php     Sources admin list + [ewo_sources] shortcode
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

## Keyword-driven feeds & Sources (0.5.0)

A second ingestion path built **on top of** the existing feed model. It turns a
strategic keyword hierarchy into auto-generated Google News feeds and captures
the full text of each matching article as a reviewable **Source**.

Flow: **Strategic Domain → Subdomain → Keyword → auto-generated RSS feed → feed items → full-article Sources**.

- **Strategic Keywords** admin screen (under *EWO RSS Engine*): manage Strategic
  Domains, Subdomains, and Keywords. Each keyword has an active toggle plus
  created/updated timestamps and lives in three custom tables
  (`{prefix}ewo_rss_domains`, `…_subdomains`, `…_keywords`).
- **Auto-generated feeds** — every *active* keyword is mirrored to a native
  `ewo_rss_source` feed whose URL is
  `https://news.google.com/rss/search?q={keyword}&hl=en-US&gl=US&ceid=US:en`.
  These feeds are tagged `_ewo_rss_generated_by = keyword` and carry
  `_ewo_rss_keyword_id` / `_ewo_rss_subdomain_id` / `_ewo_rss_strategic_domain_id`
  attribution meta, so they reuse the engine's existing fetcher, feed-health, and
  status model. There is at most one feed per keyword (idempotent sync). Toggling
  a keyword inactive disables its feed without deleting captured Sources.
- **Fetch buttons** — *Fetch Now* per keyword, per subdomain, and *Fetch All
  Active Keyword Feeds Now*.
- **Full-article extraction (v0.1)** — for each RSS item the original page is
  fetched and reduced to readable body text: strips `script/style/nav/footer/
  header/aside/…`, prefers `<article>`, else the largest paragraph-dense block,
  preserving paragraph order. No summarizing/rewriting/ranking. Falls back to the
  RSS description if extraction fails (and never fatals if DOMDocument is absent).
- **Sources** are stored in `{prefix}ewo_rss_article_sources` (title, URL,
  normalized URL hash, source domain, domain/subdomain/keyword, feed ID, published
  + fetched dates, full content, status `new`/`reviewed`/`ignored`). Dedup uses the
  same normalized URL hash as the rest of the engine.
- **Sources** admin screen lists captures with filters (Strategic Domain,
  Subdomain, Keyword, Status) and inline status changes.
- **`[ewo_sources]`** shortcode renders the latest Sources grouped by Strategic
  Domain → Subdomain. Attributes: `limit` (default 60), `status` (default
  `new,reviewed`).
- **Cron** — active keyword feeds are fetched every **30 minutes**
  (`ewo_rss_engine_keywords_run` on the `ewo_rss_thirty_min` interval), separate
  from the hourly curated-source import, which now skips keyword feeds.

## Architecture notes

- All classes are namespaced by the `EWO_RSS_` prefix; constants by `EWO_RSS_ENGINE_`.
- The cron hook is `EWO_RSS_ENGINE_CRON_HOOK` (`ewo_rss_engine_run`); it is scheduled on activation and cleared on deactivation.
- Components are instantiated and wired in `EWO_RSS_Engine::init()`.

## Changelog

### 0.5.0
- Added keyword-driven feed generation: Strategic Domain → Subdomain → Keyword
  hierarchy (three custom tables) with active toggle and created/updated dates.
- Each active keyword auto-generates a Google News RSS feed stored as a native
  `ewo_rss_source` with domain/subdomain/keyword attribution meta (one feed per
  keyword, idempotent sync).
- v0.1 full-article extractor (DOMDocument) and a `ewo_rss_article_sources` table
  storing captured Sources with a new/reviewed/ignored status workflow.
- Strategic Keywords + Sources admin screens (with Fetch Now / per-subdomain /
  Fetch All buttons and Source filters), the `[ewo_sources]` shortcode, and a
  30-minute keyword-feed cron.

### 0.1.0
- Initial release: feed-source CPT, scheduled + manual imports, GUID deduplication, thumbnail sideloading, and import logs.
