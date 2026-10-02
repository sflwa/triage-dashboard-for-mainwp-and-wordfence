<?php
/**
 * AJAX Handler for Live Console Sync in Triage Dashboard.
 *
 * @package MainWP_Wordfence_Triage
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MainWP_WF_Triage_Ajax
 */
class MainWP_WF_Triage_Ajax {

	/**
	 * Instance variable.
	 *
	 * @var MainWP_WF_Triage_Ajax|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return MainWP_WF_Triage_Ajax
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
		add_action( 'wp_ajax_mainwp_wf_triage_manual_sync', array( $this, 'handle_manual_sync' ) );
		add_action( 'wp_ajax_mainwp_wf_triage_single_test', array( $this, 'handle_single_test' ) );
	}

	/**
	 * Handles manual batch network sync AJAX request.
	 */
	public function handle_manual_sync() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized access.' ) );
		}

		global $mainWPWordfenceExtensionActivator;

		if ( ! isset( $mainWPWordfenceExtensionActivator ) ) {
			wp_send_json_error( array( 'message' => 'MainWP Wordfence Extension Activator not initialized.' ) );
		}

		$mode       = isset( $_POST['mode'] ) ? sanitize_text_field( wp_unslash( $_POST['mode'] ) ) : 'full';
		$offset     = isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0;
		$batch_size = 10;

		$child_file = $mainWPWordfenceExtensionActivator->get_child_file();
		$child_key  = $mainWPWordfenceExtensionActivator->get_child_key();
		$db         = MainWP_WF_Triage_DB::instance();

		if ( 'smart' === $mode ) {
			$target_ids = $db->get_sites_requiring_sync();
			if ( empty( $target_ids ) ) {
				wp_send_json_success(
					array(
						'site_logs'   => array(),
						'offset'      => 0,
						'next_offset' => 0,
						'total'       => 0,
						'has_more'    => false,
						'message'     => 'All sites clean or up to date. Zero changes detected.',
					)
				);
			}

			$all_sites = apply_filters( 'mainwp_getsites', $child_file, $child_key, null );
			$sites     = array();
			foreach ( $all_sites as $s ) {
				if ( in_array( $s['id'], $target_ids, true ) ) {
					$sites[] = $s;
				}
			}
		} else {
			$sites = apply_filters( 'mainwp_getsites', $child_file, $child_key, null );
		}

		if ( empty( $sites ) || ! is_array( $sites ) ) {
			wp_send_json_error( array( 'message' => 'No sites returned from MainWP.' ) );
		}

		$total_sites = count( $sites );
		$batch       = array_slice( $sites, $offset, $batch_size );
		$site_logs   = array();
		$cron        = MainWP_WF_Triage_Cron::instance();

		foreach ( $batch as $site ) {
			$site_id   = isset( $site['id'] ) ? absint( $site['id'] ) : 0;
			$site_name = isset( $site['name'] ) ? $site['name'] : 'Site ' . $site_id;

			if ( ! $site_id ) {
				continue;
			}

			$inserted = $cron->sync_single_site( $site_id, $site_name );

			$site_logs[] = array(
				'site_id'   => $site_id,
				'site_name' => $site_name,
				'count'     => $inserted,
			);
		}

		$next_offset = $offset + count( $batch );
		$has_more    = $next_offset < $total_sites;

		wp_send_json_success(
			array(
				'site_logs'   => $site_logs,
				'offset'      => $offset,
				'next_offset' => $next_offset,
				'total'       => $total_sites,
				'has_more'    => $has_more,
			)
		);
	}

	/**
	 * Handles single site sync AJAX request.
	 */
	public function handle_single_test() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		$site_id = isset( $_POST['site_id'] ) ? absint( $_POST['site_id'] ) : 0;
		if ( ! $site_id ) {
			wp_send_json_error( 'Invalid Site ID' );
		}

		$cron     = MainWP_WF_Triage_Cron::instance();
		$inserted = $cron->sync_single_site( $site_id );

		wp_send_json_success(
			array(
				'site_id'       => $site_id,
				'inserted_rows' => $inserted,
				'status'        => 'Single site synced successfully.',
			)
		);
	}
}
