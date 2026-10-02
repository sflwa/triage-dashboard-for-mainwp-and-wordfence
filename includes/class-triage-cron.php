<?php
/**
 * Cron and Processing Engine for Triage Dashboard.
 *
 * @package MainWP_Wordfence_Triage
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MainWP_WF_Triage_Cron
 */
class MainWP_WF_Triage_Cron {

	/**
	 * Instance variable.
	 *
	 * @var MainWP_WF_Triage_Cron|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return MainWP_WF_Triage_Cron
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'mainwp_wf_triage_cron_hook', array( $this, 'run_delta_sync' ) );
	}

	/**
	 * Runs delta background sync for sites requiring attention.
	 */
	public function run_delta_sync() {
		$db              = MainWP_WF_Triage_DB::instance();
		$target_site_ids = $db->get_sites_requiring_sync();

		if ( empty( $target_site_ids ) ) {
			return;
		}

		foreach ( $target_site_ids as $site_id ) {
			$this->sync_single_site( $site_id );
		}
	}

	/**
	 * Syncs Wordfence scan findings for a single site.
	 *
	 * @param int    $site_id Child site ID.
	 * @param string $site_name Child site name.
	 * @return int Number of issues saved.
	 */
	public function sync_single_site( $site_id, $site_name = '' ) {
		global $mainWPWordfenceExtensionActivator;

		if ( ! isset( $mainWPWordfenceExtensionActivator ) || ! $site_id ) {
			return 0;
		}

		$db         = MainWP_WF_Triage_DB::instance();
		$child_file = $mainWPWordfenceExtensionActivator->get_child_file();
		$child_key  = $mainWPWordfenceExtensionActivator->get_child_key();

		if ( empty( $site_name ) ) {
			$sites = apply_filters( 'mainwp_getsites', $child_file, $child_key, $site_id );
			if ( ! empty( $sites ) && is_array( $sites ) ) {
				$site_info = current( $sites );
				$site_name = isset( $site_info['name'] ) ? $site_info['name'] : 'Site ' . $site_id;
			}
		}

		$post_data = array( 'mwp_action' => 'loadIssues' );

		$information = apply_filters(
			'mainwp_fetchurlauthed',
			$child_file,
			$child_key,
			$site_id,
			'wordfence',
			$post_data
		);

		$db->clear_site_issues( $site_id );

		$inserted_count = 0;

		if ( is_array( $information ) && isset( $information['issuesLists']['new'] ) && is_array( $information['issuesLists']['new'] ) ) {
			foreach ( $information['issuesLists']['new'] as $issue ) {
				$type = isset( $issue['type'] ) ? $issue['type'] : 'unknown';

				$summary = '';
				if ( ! empty( $issue['shortMsg'] ) ) {
					$summary = $issue['shortMsg'];
				} elseif ( ! empty( $issue['summary'] ) ) {
					$summary = $issue['summary'];
				} elseif ( ! empty( $issue['longMsg'] ) ) {
					$summary = wp_strip_all_tags( $issue['longMsg'] );
				}

				if ( empty( $summary ) ) {
					continue;
				}

				// SKIP SUBSCRIBER WEAK PASSWORDS ENTIRELY
				if ( 'easyPassword' === $type ) {
					if ( false !== strpos( $summary, "'subscriber'" ) || false !== strpos( $summary, "'suscriptor'" ) ) {
						continue;
					}
				}

				// Simple Category Assignment based on Wordfence Issue Type
				$cat      = 'low';
				$severity = 'low';

				if ( in_array( $type, array( 'file', 'knownfile', 'coreUnknown', 'configReadable', 'publiclyAccessible', 'malware' ), true ) || false !== strpos( $type, 'file' ) ) {
					$cat      = 'malware';
					$severity = 'critical';
				} elseif ( in_array( $type, array( 'wfPluginVulnerable', 'wfThemeVulnerable', 'wfPluginAbandoned' ), true ) || false !== strpos( $type, 'Vulnerable' ) || false !== strpos( $type, 'Abandoned' ) ) {
					$cat      = 'vulnerability';
					$severity = 'high';
				} elseif ( in_array( $type, array( 'wfPluginUpgrade', 'wfThemeUpgrade', 'wfUpgrade' ), true ) || false !== strpos( $type, 'Upgrade' ) ) {
					$cat      = 'outdated';
					$severity = 'medium';
				}

				// Format summary for easyPassword high-privilege users
				if ( 'easyPassword' === $type ) {
					$summary = 'Weak Password on Non-Subscriber Account';
				}

				$db->insert_issue(
					array(
						'site_id'    => $site_id,
						'site_name'  => $site_name,
						'issue_type' => $type,
						'severity'   => $severity,
						'category'   => $cat,
						'group_key'  => $type,
						'summary'    => $summary,
					)
				);

				$inserted_count++;
			}
		}

		return $inserted_count;
	}
}
