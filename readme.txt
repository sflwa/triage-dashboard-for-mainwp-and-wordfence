=== Triage Dashboard for MainWP and WordFence ===
Contributors: sflwa
Donate link: https://southfloridawebadvisors.com/
Tags: mainwp, wordfence, security, triage, dashboard
Requires at least: 5.8
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.6.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

High-efficiency security triage dashboard for MainWP and Wordfence. Identifies, groups, and remediates security issues across site networks.

== Description ==

Triage Dashboard for MainWP and WordFence is designed for agencies and site managers handling large WordPress networks (100+ to 500+ sites) who need a clean, actionable overview of Wordfence scan results across all child sites without the clutter and noise.

Instead of hunting through individual site scan logs or managing hundreds of separate notification emails, this add-on groups security findings network-wide into 4 actionable priority tiers:

* **Tier 1 (Critical Malware & Unsafe Files):** Groups active core file modifications, malicious uploads, and unauthorized script injections directly by exact file summary patterns.
* **Tier 2 (Vulnerable & Abandoned Plugins):** Grouped network-wide by issue summary with a 1-click button that copies the plugin name and opens MainWP Plugin Management for instant batch remediation.
* **Tier 3 (Outdated Versions & Upgrade Warnings):** Groups plugin mismatch and update warnings with direct link workflows to MainWP.
* **Tier 4 (Low Severity & Security Audits):** Filters out low-priority subscriber password noise and aggregates non-subscriber weak passwords and external admin user audits.

### Features
* **Smart Delta Sync:** Queries MainWP's native Wordfence status database to sync only child sites with active issue changes (status = 2), completing hourly background syncs in seconds.
* **Full Network Batch Sync:** Live AJAX terminal console streams progress step-by-step across all child sites without memory or PHP execution timeouts.
* **Executive Summary Dashboard:** Includes a secondary administrative dashboard sub-view featuring deduplicated abandoned plugin lists and update requirements with direct WordPress.org search links.
* **Interactive Filtering:** Dynamically filter the triage dashboard by any specific flagged site to hide unrelated network noise.
* **Single Site Re-Scan:** Isolated 1-click AJAX re-sync button for individual sites right from the filter toolbar.
* **Zero SaaS Dependencies:** Runs 100% inside your native MainWP Dashboard database without external API fees or third-party connections.

== Requirements ==

This plugin is an extension add-on for MainWP and requires the following software installed and active on your MainWP Dashboard site:

1. [MainWP Dashboard](https://wordpress.org/plugins/mainwp/) (version 4.0 or higher)
2. MainWP Wordfence Extension
3. Wordfence Security installed on child sites

== Installation ==

1. Ensure MainWP Dashboard and MainWP Wordfence Extension are active on your main dashboard site.
2. Upload the `mainwp-wordfence-triage` folder to your `/wp-content/plugins/` directory.
3. Activate the plugin through the 'Plugins' menu in WordPress.
4. Navigate to **Security Triage** in your WordPress admin menu.
5. Click **Full Network Sync** to perform the initial network scan triage.

== Frequently Asked Questions ==

= Does this plugin connect to Wordfence Central? =
No. This plugin queries scan data locally from your MainWP Dashboard site via MainWP's native child site communication protocol.

= Will Smart Sync slow down my server? =
No. Smart Sync strictly filters for child sites where Wordfence reports active issues (`status = 2`), ignoring clean sites entirely to keep resource usage minimal.

= What happens during a Full Network Sync? =
The plugin uses a controlled AJAX loop processing 10 sites per request to ensure large networks (400+ sites) finish smoothly without hitting server memory or execution limits.

== Screenshots ==

1. Executive Analytics Bar and Tiered Security Triage Overview.
2. Executive Summary Submenu View detailing abandoned and update-required plugins.
3. Live Terminal Sync Console streaming single-site and batch AJAX responses.

== Changelog ==

= 1.6.0 =
* Updated official title to 'Triage Dashboard for MainWP and WordFence' and text domain to 'triage-dashboard-for-mainwp-and-wordfence'.
* Added Executive Summary submenu dashboard view with Section A (Abandoned Plugins) and Section B (Update Required Plugins).
* Adjusted Tier 1 layout so main card is open on load while nested item accordions start collapsed.

= 1.5.0 =
* Added dynamic site filter dropdown displaying only flagged sites and issue counts.
* Added 1-click 'Sync Selected Site' AJAX button.
* Made Tiers 2, 3, and 4 collapsible by default while keeping Tier 1 expanded on page load.
* Optimized easyPassword filtering to ignore subscriber-level weak password noise.

= 1.4.0 =
* Fixed MySQL `GROUP_CONCAT` truncation on large site lists.
* Simplified grouping logic to group directly on exact summary strings.
* Added clipboard-copy and search redirect for MainWP plugin management.

= 1.0.0 =
* Initial release.
