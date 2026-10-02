<?php
/**
 * Security Triage Dashboard Template
 *
 * @package MainWP_Wordfence_Triage
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mainwp_wf_triage_db = MainWP_WF_Triage_DB::instance();

$mainwp_wf_triage_critical_groups      =$mainwp_wf_triage_db->get_grouped_by_category( 'malware' );
$mainwp_wf_triage_vulnerability_groups =$mainwp_wf_triage_db->get_grouped_by_category( 'vulnerability' );
$mainwp_wf_triage_outdated_groups      =$mainwp_wf_triage_db->get_grouped_by_category( 'outdated' );
$mainwp_wf_triage_low_groups           =$mainwp_wf_triage_db->get_grouped_by_category( 'low' );

$mainwp_wf_triage_flagged_sites_list = $mainwp_wf_triage_db->get_flagged_sites_list();$mainwp_wf_triage_analytics          = $mainwp_wf_triage_db->get_analytics_summary();$mainwp_wf_triage_open_nonce         = wp_create_nonce( 'mainwp-admin-nonce' );

/**
 * Render grouped summary accordion for security issues.
 *
 * @param array  $groups Grouped issue objects.
 * @param string $open_nonce Security nonce for direct site login.
 * @param string $badge_color Hex background color for site count badge.
 * @param bool   $show_search_button Whether to display the MainWP search copy button.
 * @param bool   $start_open Whether the nested item accordions start expanded.
 */
function mainwp_wf_triage_render_summary_accordion( $groups, $open_nonce,$badge_color = '#dba617', $show_search_button = false,$start_open = false ) {
	if ( empty( $groups ) ) {
		echo '<p style="color: #008a20; font-weight: 600; padding: 10px 0;">✔ No issues flagged in this tier.</p>';
		return;
	}

	foreach ( $groups as$group_item ) {
		$open_attr =$start_open ? 'open' : '';
		?>
		<details class="triage-group-accordion" <?php echo esc_attr( $open_attr ); ?> style="margin-bottom: 10px; border: 1px solid #e2e8f0; border-radius: 6px; background: #fafafa; overflow: hidden;">
			<summary style="padding: 12px 15px; font-weight: 600; cursor: pointer; background: #f8fafc; display: flex; align-items: center; justify-content: space-between;">
				<span style="font-size: 13px; color: #1e293b; max-width: 75%; line-height: 1.4;"><?php echo esc_html( $group_item->summary ); ?></span>
				<div style="display: flex; align-items: center; gap: 10px;">
					<?php if ( $show_search_button ) : ?>
						<button type="button" class="button button-small button-secondary" onclick="copyAndOpenMainWp(event, '<?php echo esc_js( $group_item->summary ); ?>')" style="font-size: 11px;">
							📋 Copy Text & Search in MainWP ↗
						</button>
					<?php endif; ?>
					<span style="background: <?php echo esc_attr( $badge_color ); ?>; color: #fff; padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: bold;">
						<?php echo esc_html( $group_item->site_count ); ?> Site<?php echo$group_item->site_count > 1 ? 's' : ''; ?>
					</span>
				</div>
			</summary>
			<div style="padding: 12px 15px; background: #fff;">
				<table class="wp-list-table widefat fixed striped table-view-list" style="border: none;">
					<thead>
						<tr>
							<th style="width: 55%;">Site Name</th>
							<th style="width: 20%;">Issue Count</th>
							<th style="width: 25%; text-align: right;">Action</th>
						</tr>
					</thead>
					<tbody>
						<?php 
						if ( ! empty( $group_item->sites ) && is_array($group_item->sites ) ) :
							foreach ( $group_item->sites as $site_obj ) :$open_url = admin_url( 'admin.php?page=SiteOpen&newWindow=yes&websiteid=' . absint( $site_obj->site_id ) . '&_opennonce=' . esc_attr( $open_nonce ) );
								?>
								<tr class="triage-site-row" data-site-id="<?php echo esc_attr( $site_obj->site_id ); ?>">
									<td><strong><?php echo esc_html( $site_obj->site_name ); ?></strong></td>
									<td>
										<span style="color: #64748b; font-size: 12px; font-weight: 600;">
											<?php echo esc_html( $site_obj->issue_count ); ?> Issue<?php echo$site_obj->issue_count > 1 ? 's' : ''; ?>
										</span>
									</td>
									<td style="text-align: right;">
										<a href="<?php echo esc_url( $open_url ); ?>" target="_blank" class="button button-small">Open Site</a>
									</td>
								</tr>
								<?php 
							endforeach; 
						endif;
						?>
					</tbody>
				</table>
			</div>
		</details>
		<?php
	}
}
?>

<div class="wrap">
	<h1 class="wp-heading-inline">
		<span class="dashicons dashicons-shield-alt" style="font-size: 30px; width: 30px; height: 30px; margin-right: 8px; vertical-align: middle;"></span>
		Wordfence Security Triage
	</h1>

	<div style="float: right; margin-top: 10px; display: flex; align-items: center; gap: 8px;">
		<!-- DYNAMIC SITE FILTER DROPDOWN -->
		<select id="filter-site-id" onchange="applySiteFilter()" style="height: 32px; max-width: 240px; vertical-align: middle;">
			<option value="">-- All Flagged Sites (<?php echo esc_html( count( $mainwp_wf_triage_flagged_sites_list ) ); ?>) --</option>
			<?php foreach ( $mainwp_wf_triage_flagged_sites_list as$flagged_site_item ) : ?>
				<option value="<?php echo esc_attr( $flagged_site_item->site_id ); ?>">
					<?php echo esc_html( $flagged_site_item->site_name ); ?> (<?php echo esc_html($flagged_site_item->issue_count ); ?>)
				</option>
			<?php endforeach; ?>
		</select>

		<!-- SYNC SELECTED SITE BUTTON -->
		<button class="button button-secondary" id="btn-sync-single" onclick="syncSingleFilteredSite()" disabled style="vertical-align: middle;">
			⚡ Sync Selected Site
		</button>

		<button class="button button-secondary" id="btn-smart-sync" onclick="startLiveSync(0, 'smart')" style="vertical-align: middle;">
			⚡ Smart Sync (Changes Only)
		</button>
		<button class="button button-primary" id="btn-sync" onclick="startLiveSync(0, 'full')" style="vertical-align: middle;">
			🔄 Full Network Sync
		</button>
	</div>
	
	<hr class="wp-header-end">

	<!-- ANALYTICS HEADER PANEL -->
	<div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin: 20px 0;">
		<div style="background: #fff; padding: 15px 20px; border: 1px solid #e2e8f0; border-radius: 6px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
			<div style="color: #64748b; font-size: 11px; font-weight: 600; text-transform: uppercase;">Total Flagged Issues</div>
			<div style="font-size: 24px; font-weight: bold; color: #0f172a; margin-top: 5px;"><?php echo esc_html( $mainwp_wf_triage_analytics['total_issues'] ); ?></div>
		</div>
		<div style="background: #fff; padding: 15px 20px; border: 1px solid #e2e8f0; border-radius: 6px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
			<div style="color: #64748b; font-size: 11px; font-weight: 600; text-transform: uppercase;">Sites Requiring Attention</div>
			<div style="font-size: 24px; font-weight: bold; color: #2563eb; margin-top: 5px;"><?php echo esc_html( $mainwp_wf_triage_analytics['flagged_sites'] ); ?></div>
		</div>
		<div style="background: #fff; padding: 15px 20px; border: 1px solid #e2e8f0; border-radius: 6px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
			<div style="color: #64748b; font-size: 11px; font-weight: 600; text-transform: uppercase;">Critical Threats (Tier 1)</div>
			<div style="font-size: 24px; font-weight: bold; color: #dc2626; margin-top: 5px;"><?php echo esc_html( $mainwp_wf_triage_analytics['critical_count'] ); ?></div>
		</div>
		<div style="background: #fff; padding: 15px 20px; border: 1px solid #e2e8f0; border-radius: 6px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
			<div style="color: #64748b; font-size: 11px; font-weight: 600; text-transform: uppercase;">Vulnerable Components</div>
			<div style="font-size: 24px; font-weight: bold; color: #d97706; margin-top: 5px;"><?php echo esc_html( $mainwp_wf_triage_analytics['high_count'] ); ?></div>
		</div>
	</div>

	<!-- LIVE TERMINAL SYNC CONSOLE -->
	<div id="sync-console-wrapper" style="display:none; margin: 15px 0; padding: 15px; background: #111827; color: #10b981; border-radius: 6px; font-family: monospace; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
		<div style="display: flex; justify-content: space-between; border-bottom: 1px solid #374151; padding-bottom: 8px; margin-bottom: 10px;">
			<strong style="color: #f3f4f6;" id="sync-mode-title">Network Sync Progress</strong>
			<span id="sync-progress-counter" style="color: #6ee7b7; font-weight: bold;">0 / 0 Sites</span>
		</div>
		<div id="sync-console-log" style="max-height: 220px; overflow-y: auto; font-size: 13px; line-height: 1.6;"></div>
	</div>

	<style>
		.triage-card { background: #fff; border: 1px solid #c3c4c7; border-left-width: 4px; padding: 15px 20px; margin-top: 20px; box-shadow: 0 1px 1px rgba(0,0,0,.04); }
		.triage-card.red { border-left-color: #d63638; }
		.triage-card.orange { border-left-color: #dba617; }
		.triage-card.yellow { border-left-color: #f0c33c; }
		.triage-card.grey { border-left-color: #8c8f94; }
		.triage-card h2 { cursor: pointer; user-select: none; margin-top: 0; padding-bottom: 8px; border-bottom: 1px solid #f0f0f1; display: flex; justify-content: space-between; align-items: center; }
		.triage-card h2:hover { color: #2563eb; }
	</style>

	<!-- TIER 1 (MAIN CARD OPEN BY DEFAULT, ITEMS COLLAPSED) -->
	<div class="triage-card red">
		<h2 onclick="toggleTierCard(this)">
			<span><span class="dashicons dashicons-warning" style="color:#d63638;"></span> Tier 1: Critical Malware & Unsafe Files (Grouped by Summary)</span>
			<span class="dashicons dashicons-arrow-up-alt2 tier-toggle-icon"></span>
		</h2>
		<div class="tier-card-body">
			<?php mainwp_wf_triage_render_summary_accordion( $mainwp_wf_triage_critical_groups,$mainwp_wf_triage_open_nonce, '#d63638', false, false ); ?>
		</div>
	</div>

	<!-- TIER 2 (COLLAPSIBLE TIER - CLOSED BY DEFAULT) -->
	<div class="triage-card orange">
		<h2 onclick="toggleTierCard(this)">
			<span><span class="dashicons dashicons-plugins-checked" style="color:#dba617;"></span> Tier 2: Vulnerable & Abandoned Plugins (Grouped by Summary)</span>
			<span class="dashicons dashicons-arrow-down-alt2 tier-toggle-icon"></span>
		</h2>
		<div class="tier-card-body" style="display: none;">
			<?php mainwp_wf_triage_render_summary_accordion( $mainwp_wf_triage_vulnerability_groups,$mainwp_wf_triage_open_nonce, '#dba617', true, false ); ?>
		</div>
	</div>

	<!-- TIER 3 (COLLAPSIBLE TIER - CLOSED BY DEFAULT) -->
	<div class="triage-card yellow">
		<h2 onclick="toggleTierCard(this)">
			<span><span class="dashicons dashicons-update" style="color:#f0c33c;"></span> Tier 3: Outdated Versions & Upgrade Warnings (Grouped by Summary)</span>
			<span class="dashicons dashicons-arrow-down-alt2 tier-toggle-icon"></span>
		</h2>
		<div class="tier-card-body" style="display: none;">
			<?php mainwp_wf_triage_render_summary_accordion( $mainwp_wf_triage_outdated_groups,$mainwp_wf_triage_open_nonce, '#f0c33c', true, false ); ?>
		</div>
	</div>

	<!-- TIER 4 (COLLAPSIBLE TIER - CLOSED BY DEFAULT) -->
	<div class="triage-card grey">
		<h2 onclick="toggleTierCard(this)">
			<span><span class="dashicons dashicons-admin-users" style="color:#8c8f94;"></span> Tier 4: Low Severity / User Security Audits (Grouped by Summary)</span>
			<span class="dashicons dashicons-arrow-down-alt2 tier-toggle-icon"></span>
		</h2>
		<div class="tier-card-body" style="display: none;">
			<?php mainwp_wf_triage_render_summary_accordion( $mainwp_wf_triage_low_groups,$mainwp_wf_triage_open_nonce, '#8c8f94', false, false ); ?>
		</div>
	</div>
</div>

<script>
function toggleTierCard(headerElem) {
	const body = headerElem.nextElementSibling;
	const icon = headerElem.querySelector('.tier-toggle-icon');
	
	if (body.style.display === 'none') {
		body.style.display = 'block';
		icon.classList.remove('dashicons-arrow-down-alt2');
		icon.classList.add('dashicons-arrow-up-alt2');
	} else {
		body.style.display = 'none';
		icon.classList.remove('dashicons-arrow-up-alt2');
		icon.classList.add('dashicons-arrow-down-alt2');
	}
}

function applySiteFilter() {
	const selectedSiteId = document.getElementById('filter-site-id').value;
	const syncSingleBtn = document.getElementById('btn-sync-single');
	const allAccordions = document.querySelectorAll('.triage-group-accordion');

	syncSingleBtn.disabled = !selectedSiteId;

	allAccordions.forEach(function(accordion) {
		const rows = accordion.querySelectorAll('.triage-site-row');
		let hasMatch = false;

		rows.forEach(function(row) {
			const siteId = row.getAttribute('data-site-id');
			if (!selectedSiteId || siteId === selectedSiteId) {
				row.style.display = '';
				hasMatch = true;
			} else {
				row.style.display = 'none';
			}
		});

		if (hasMatch) {
			accordion.style.display = '';
			if (selectedSiteId) accordion.open = true;
		} else {
			accordion.style.display = 'none';
		}
	});

	if (selectedSiteId) {
		document.querySelectorAll('.tier-card-body').forEach(function(b) {
			b.style.display = 'block';
		});
	}
}

function copyAndOpenMainWp(event, textToCopy) {
	event.preventDefault();
	event.stopPropagation();

	navigator.clipboard.writeText(textToCopy).then(function() {
		window.open('admin.php?page=PluginsManage', '_blank');
	}).catch(function() {
		window.open('admin.php?page=PluginsManage', '_blank');
	});
}

function syncSingleFilteredSite() {
	const siteId = document.getElementById('filter-site-id').value;
	if (!siteId) return;

	const wrapper = document.getElementById('sync-console-wrapper');
	const logBox = document.getElementById('sync-console-log');
	const title = document.getElementById('sync-mode-title');

	wrapper.style.display = 'block';
	title.innerText = '⚡ Single Site Sync Progress (ID: ' + siteId + ')';
	logBox.innerHTML = '<div>Fetching live Wordfence scan payload...</div>';

	jQuery.post(ajaxurl, { 
		action: 'mainwp_wf_triage_single_test',
		site_id: siteId 
	}, function(response) {
		if (response.success && response.data) {
			logBox.innerHTML += `<div style="color:#6ee7b7; font-weight:bold;">✔ ${response.data.status} Reloading view...</div>`;
			setTimeout(function() { location.reload(); }, 1200);
		} else {
			logBox.innerHTML += '<div style="color:#ef4444;">❌ Sync failed for Site ID ' + siteId + '</div>';
		}
	});
}

function startLiveSync(offset = 0, mode = 'full') {
	const btnSync = document.getElementById('btn-sync');
	const btnSmart = document.getElementById('btn-smart-sync');
	const wrapper = document.getElementById('sync-console-wrapper');
	const logBox = document.getElementById('sync-console-log');
	const counter = document.getElementById('sync-progress-counter');
	const title = document.getElementById('sync-mode-title');

	if (offset === 0) {
		btnSync.disabled = true;
		btnSmart.disabled = true;
		wrapper.style.display = 'block';
		title.innerText = mode === 'smart' ? '⚡ Smart Sync Progress (Changes Only)' : '🔄 Full Network Sync Progress';
		logBox.innerHTML = '<div>Initializing triage scan...</div>';
	}

	jQuery.post(ajaxurl, { 
		action: 'mainwp_wf_triage_manual_sync',
		offset: offset,
		mode: mode
	}, function(response) {
		if (response.success && response.data) {
			const data = response.data;

			if (data.message) {
				logBox.innerHTML += `<div style="color:#6ee7b7;">${data.message}</div>`;
				btnSync.disabled = false;
				btnSmart.disabled = false;
				return;
			}

			if (data.site_logs && data.site_logs.length > 0) {
				data.site_logs.forEach(function(item) {
					const statusColor = item.count > 0 ? '#ef4444' : '#10b981';
					logBox.innerHTML += `<div>[ID ${item.site_id}] <strong>${item.site_name}</strong>: <span style="color:${statusColor}">${item.count} issue(s) synced</span></div>`;
				});
				logBox.scrollTop = logBox.scrollHeight;
			}

			counter.innerText = `${data.next_offset > data.total ? data.total : data.next_offset} / ${data.total} Sites`;

			if (data.has_more) {
				startLiveSync(data.next_offset, mode);
			} else {
				logBox.innerHTML += '<div style="color:#6ee7b7; margin-top:8px; font-weight:bold;">✔ Sync complete! Reloading...</div>';
				setTimeout(function() { location.reload(); }, 1200);
			}
		} else {
			logBox.innerHTML += '<div style="color:#ef4444; margin-top:8px;">❌ Sync error. Check logs.</div>';
			btnSync.disabled = false;
			btnSmart.disabled = false;
		}
	});
}
</script>
