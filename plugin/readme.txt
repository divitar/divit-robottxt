=== Divit RobotTXT ===
Contributors: divitar
Tags: robots, robots.txt, seo, crawl, indexing
Requires at least: 5.0
Tested up to: 7.1
Requires PHP: 7.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Manage your robots.txt from the WordPress admin. Content is served via hook — no file is written to disk.

== Description ==

**Divit RobotTXT** gives you full control over your site's robots.txt directly from the WordPress admin panel, without ever touching your server filesystem.

= How it works =

WordPress natively intercepts requests to `/robots.txt` when no physical file exists in your site root. This plugin hooks into that mechanism via the `robots_txt` filter to serve your custom content dynamically.

= Features =

* **Visual editor** — Edit your robots.txt rules in a clean, monospace textarea.
* **Quick-add buttons** — Insert common directives with a single click.
* **Recommended template** — Load a sensible default configuration for WordPress sites.
* **Server-side validation** — Each directive is validated before saving. Invalid lines are rejected with a clear error message.
* **Client-side validation** — Instant feedback as you type, before even hitting Save.
* **Status panel** — See at a glance whether a physical `robots.txt` file would override your settings, and whether your site is visible to search engines.
* **No files written** — Content is stored in `wp_options` and served via WordPress hook.
* **Fully translatable** — All strings use the `divit-robottxt` text domain.

= Valid directives =

* `User-agent: *` — Apply rules to all crawlers (or a named bot)
* `Allow: /path` — Explicitly allow access to a path
* `Disallow: /path` — Block access to a path
* `Crawl-delay: 10` — Request a crawl delay in seconds
* `Sitemap: https://example.com/sitemap.xml` — Point crawlers to your sitemap
* Lines starting with `#` are treated as comments

= Important notes =

* If a physical `robots.txt` file exists in your WordPress root, it will be served by the web server before WordPress can intercept the request. The plugin detects this and shows a warning.
* If your site is set to "Discourage search engines" in **Settings → Reading**, WordPress uses its own robots.txt output. This plugin respects that setting and will not override it.

== Installation ==

1. Upload the `divit-robottxt` folder to `/wp-content/plugins/`.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Go to **Settings → Divit RobotTXT** to configure your robots.txt content.

== Frequently Asked Questions ==

= Does this plugin write a robots.txt file to my server? =

No. Content is stored in the WordPress database (`wp_options`) and served dynamically via the `robots_txt` WordPress filter hook.

= What happens if I already have a robots.txt file? =

If a physical `robots.txt` file exists in your site root, the web server will serve it directly and WordPress (and this plugin) will not be involved. The plugin will warn you about this on the settings page. You should delete or rename the physical file to use this plugin.

= What happens if I deactivate the plugin? =

WordPress will revert to its default robots.txt output (`User-agent: *` with no Disallow rules for public sites).

= What happens if I uninstall the plugin? =

All saved content is removed from the database.

= Does it work with multisite? =

This version targets single-site installations. Multisite support is planned for a future release.

== Screenshots ==

1. The settings page with the editor, quick-add toolbar, and status sidebar.
2. Validation error feedback when an invalid directive is entered.
3. Status panel showing physical file detection and search-engine visibility.

== Changelog ==

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
Initial release. No upgrade steps required.
