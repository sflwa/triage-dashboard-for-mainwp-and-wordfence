<?php
/**
 * Executive Summary Dashboard View
 *
 * @package MainWP_Wordfence_Triage
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mainwp_wf_triage_db        = MainWP_WF_Triage_DB::instance();
$mainwp_wf_triage_analytics = $mainwp_wf_triage_db->get_analytics_summary();

$mainwp_wf_triage_abandoned_plugins = $mainwp_wf_triage_db->get_abandoned_plugins_summary();
$mainwp_wf_triage_update_plugins    = $mainwp_wf_triage_db->get_update_required_plugins_summary();

/**
 * Extracts plugin name from summary string for WP.org search link.
 *
 * @param string $summary Wordfence summary text.
 * @return string
 */
function mainwp_wf_triage_extract_plugin_slug( $summary ) {
	if ( preg_match( '/The (?:Plugin|Theme)\s+"([^"]+)"/i', $summary, $matches ) ) {
		return $matches[1];
	} elseif ( preg_match( '/Plugin\s+"([^"]+)"/i', $summary, $matches ) ) {
		return $matches[1];
	}
	return $summary;
}
?>

<div class="wrap">
	<h1 class="wp-heading-inline">
		<span class="dashicons dashicons-chart-bar" style="font-size: 30px; width: 30px; height: 30px; margin-right: 8px; vertical-align: middle;"></span>
		Security Executive Summary
	</h1>
	
	<hr class="wp-header-end">

	<!-- SEVERITY BREAKDOWN KPI CARDS -->
	<div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 15px; margin: 20px 0;">
		<div style="background: #fff; padding: 15px 20px; border: 1px solid #e2e8f0; border-radius: 6px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
			<div style="color: #64748b; font-size: 11px; font-weight: 600; text-transform: uppercase;">Affected Sites</div>
			<div style="font-size: 24px; font-weight: bold; color: #0f172a; margin-top: 5px;"><?php echo esc_html( $mainwp_wf_triage_analytics['flagged_sites'] ); ?></div>
		</div>
		<div style="background: #fff; padding: 15px 20px; border: 1px solid #e2e8f0; border-left: 4px solid #dc2626; border-radius: 6px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
			<div style="color: #64748b; font-size: 11px; font-weight: 600; text-transform: uppercase;">Critical (Tier 1)</div>
			<div style="font-size: 24px; font-weight: bold; color: #dc2626; margin-top: 5px;"><?php echo esc_html( $mainwp_wf_triage_analytics['critical_count'] ); ?></div>
		</div>
		<div style="background: #fff; padding: 15px 20px; border: 1px solid #e2e8f0; border-left: 4px solid #d97706; border-radius: 6px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
			<div style="color: #64748b; font-size: 11px; font-weight: 600; text-transform: uppercase;">High Severity (Tier 2)</div>
			<div style="font-size: 24px; font-weight: bold; color: #d97706; margin-top: 5px;"><?php echo esc_html( $mainwp_wf_triage_analytics['high_count'] ); ?></div>
		</div>
		<div style="background: #fff; padding: 15px 20px; border: 1px solid #e2e8f0; border-left: 4px solid #eab308; border-radius: 6px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
			<div style="color: #64748b; font-size: 11px; font-weight: 600; text-transform: uppercase;">Medium Severity (Tier 3)</div>
			<div style="font-size: 24px; font-weight: bold; color: #eab308; margin-top: 5px;"><?php echo esc_html( $mainwp_wf_triage_analytics['medium_count'] ); ?></div>
		</div>
		<div style="background: #fff; padding: 15px 20px; border: 1px solid #e2e8f0; border-left: 4px solid #64748b; border-radius: 6px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
			<div style="color: #64748b; font-size: 11px; font-weight: 600; text-transform: uppercase;">Low Severity (Tier 4)</div>
			<div style="font-size: 24px; font-weight: bold; color: #64748b; margin-top: 5px;"><?php echo esc_html( $mainwp_wf_triage_analytics['low_count'] ); ?></div>
		</div>
	</div>

	<style>
		.summary-card { background: #fff; border: 1px solid #c3c4c7; border-radius: 6px; padding: 20px; margin-top: 20px; box-shadow: 0 1px 1px rgba(0,0,0,.04); }
		.summary-card h2 { margin-top: 0; padding-bottom: 10px; border-bottom: 1px solid #f0f0f1; font-size: 16px; }
	</style>

	<!-- SECTION A: ABANDONED PLUGINS (ALPHABETICAL) -->
	<div class="summary-card">
		<h2><span class="dashicons dashicons-dismiss" style="color:#d97706;"></span> Section A: Abandoned Plugins (Deduplicated Alphabetical List)</h2>
		<?php if ( empty( $mainwp_wf_triage_abandoned_plugins ) ) : ?>
			<p style="color: #008a20; font-weight: 600;">✔ No abandoned plugins detected across the network.</p>
		<?php else : ?>
			<table class="wp-list-table widefat fixed striped table-view-list">
				<thead>
					<tr>
						<th style="width: 55%;">Plugin & Issue Details</th>
						<th style="width: 15%;">Affected Sites</th>
						<th style="width: 30%; text-align: right;">Replacement Hotlink (Option B)</th>
					</tr>
				</thead>
				<tbody>
					<?php 
					foreach ( $mainwp_wf_triage_abandoned_plugins as $ab_item ) : 
						$plugin_name = mainwp_wf_triage_extract_plugin_slug( $ab_item->summary );
						$search_url  = 'https://wordpress.org/plugins/search/' . urlencode( $plugin_name ) . '/';
						?>
						<tr>
							<td><strong><?php echo esc_html( $ab_item->summary ); ?></strong></td>
							<td>
								<span class="badge" style="background: #d97706; color: #fff; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: bold;">
									<?php echo esc_html( $ab_item->site_count ); ?> Site<?php echo $ab_item->site_count > 1 ? 's' : ''; ?>
								</span>
							</td>
							<td style="text-align: right;">
								<a href="<?php echo esc_url( $search_url ); ?>" target="_blank" class="button button-small button-secondary">
									🔍 Click for Recommendations ↗
								</a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>

	<!-- SECTION B: PLUGINS REQUIRING UPDATES (ALPHABETICAL) -->
	<div class="summary-card">
		<h2><span class="dashicons dashicons-update" style="color:#2563eb;"></span> Section B: Plugins Requiring Updates (Vulnerabilities & Outdated Versions)</h2>
		<?php if ( empty( $mainwp_wf_triage_update_plugins ) ) : ?>
			<p style="color: #008a20; font-weight: 600;">✔ All network plugins are up to date with zero active vulnerabilities.</p>
		<?php else : ?>
			<table class="wp-list-table widefat fixed striped table-view-list">
				<thead>
					<tr>
						<th style="width: 55%;">Plugin & Update Details</th>
						<th style="width: 20%;">Reason Flagged</th>
						<th style="width: 25%; text-align: right;">Affected Sites</th>
					</tr>
				</thead>
				<tbody>
					<?php 
					foreach ( $mainwp_wf_triage_update_plugins as $up_item ) : 
						$reason_label = ( 'wfPluginVulnerable' === $up_item->issue_type || 'wfThemeVulnerable' === $up_item->issue_type ) ? '⚠️ Active Security Vulnerability' : '🔄 Outdated Version';
						$reason_color = ( 'wfPluginVulnerable' === $up_item->issue_type || 'wfThemeVulnerable' === $up_item->issue_type ) ? '#dc2626' : '#2563eb';
						?>
						<tr>
							<td><strong><?php echo esc_html( $up_item->summary ); ?></strong></td>
							<td>
								<span style="color: <?php echo esc_attr( $reason_color ); ?>; font-weight: 600; font-size: 12px;">
									<?php echo esc_html( $reason_label ); ?>
								</span>
							</td>
							<td style="text-align: right;">
								<span class="badge" style="background: #2563eb; color: #fff; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: bold;">
									<?php echo esc_html( $up_item->site_count ); ?> Site<?php echo $up_item->site_count > 1 ? 's' : ''; ?>
								</span>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>
</div>
