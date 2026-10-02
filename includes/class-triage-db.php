<?php
/**
 * Database abstraction layer for Triage Dashboard.
 *
 * @package MainWP_Wordfence_Triage
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MainWP_WF_Triage_DB
 */
class MainWP_WF_Triage_DB {

	/**
	 * Instance variable.
	 *
	 * @var MainWP_WF_Triage_DB|null
	 */
	private static $instance = null;

	/**
	 * Database table name.
	 *
	 * @var string
	 */
	private $table_name;

	/**
	 * Get singleton instance.
	 *
	 * @return MainWP_WF_Triage_DB
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
		global $wpdb;
		$this->table_name =$wpdb->prefix . 'mainwp_wf_triage_log';
	}

	/**
	 * Get table name.
	 *
	 * @return string
	 */
	public function get_table_name() {
		return $this->table_name;
	}

	/**
	 * Create triage database table.
	 */
	public function create_table() {
		global $wpdb;
		$charset_collate =$wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$this->table_name} ( 			id bigint(20) NOT NULL AUTO_INCREMENT, 			site_id int(11) NOT NULL, 			site_name varchar(255) NOT NULL, 			issue_type varchar(100) NOT NULL, 			severity varchar(20) NOT NULL, 			category varchar(50) NOT NULL, 			group_key varchar(255) NOT NULL, 			summary text NOT NULL, 			last_synced datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, 			PRIMARY KEY  (id), 			KEY site_id (site_id), 			KEY category (category) 		)$charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Clear recorded issues for a single site.
	 *
	 * @param int $site_id Child site ID.
	 */
	public function clear_site_issues( $site_id ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete( $this->table_name, array( 'site_id' => absint($site_id ) ) );
	}

	/**
	 * Insert a security issue into the triage log.
	 *
	 * @param array $data Issue record data.
	 */
	public function insert_issue( $data ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->insert($this->table_name,
			array(
				'site_id'    => absint( $data['site_id'] ),
				'site_name'  => sanitize_text_field( $data['site_name'] ),
				'issue_type' => sanitize_text_field( $data['issue_type'] ),
				'severity'   => sanitize_text_field( $data['severity'] ),
				'category'   => sanitize_text_field( $data['category'] ),
				'group_key'  => sanitize_text_field( $data['group_key'] ),
				'summary'    => sanitize_textarea_field( $data['summary'] ),
			)
		);
	}

	/**
	 * Retrieves unique summaries for a category along with their site list & user counts.
	 *
	 * @param string $category Issue category tier.
	 * @return array
	 */
	public function get_grouped_by_category( $category ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$summaries = $wpdb->get_results($wpdb->prepare(
				"SELECT summary, COUNT(DISTINCT site_id) as site_count 
				 FROM `{$this->table_name}` 
				 WHERE category = %s 
				 GROUP BY summary 
				 ORDER BY site_count DESC, summary ASC",
				$category
			)
		);

		if ( empty( $summaries ) ) {
			return array();
		}

		foreach ( $summaries as &$item ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$item->sites = $wpdb->get_results($wpdb->prepare(
					"SELECT site_id, site_name, COUNT(*) as issue_count 
					 FROM `{$this->table_name}` 
					 WHERE category = %s AND summary = %s 
					 GROUP BY site_id, site_name 
					 ORDER BY site_name ASC",
					$category,$item->summary
				)
			);
		}

		return $summaries;
	}

	/**
	 * Returns an alphabetized list of flagged sites currently in the triage log with issue counts.
	 *
	 * @return array
	 */
	public function get_flagged_sites_list() {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results(
			"SELECT site_id, site_name, COUNT(*) as issue_count 
			 FROM `{$this->table_name}` 
			 GROUP BY site_id, site_name 
			 ORDER BY site_name ASC"
		);
	}

	/**
	 * Returns deduplicated, alphabetized abandoned plugins for the Executive Summary.
	 *
	 * @return array
	 */
	public function get_abandoned_plugins_summary() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results($wpdb->prepare(
				"SELECT summary, COUNT(DISTINCT site_id) as site_count 
				 FROM `{$this->table_name}` 
				 WHERE issue_type = %s 
				 GROUP BY summary 
				 ORDER BY summary ASC",
				'wfPluginAbandoned'
			)
		);
	}

	/**
	 * Returns deduplicated, alphabetized plugins requiring updates (vulnerable or outdated) for Executive Summary.
	 *
	 * @return array
	 */
	public function get_update_required_plugins_summary() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results($wpdb->prepare(
				"SELECT summary, issue_type, COUNT(DISTINCT site_id) as site_count 
				 FROM `{$this->table_name}` 
				 WHERE category IN (%s, %s) AND issue_type != %s 
				 GROUP BY summary, issue_type 
				 ORDER BY summary ASC",
				'vulnerability',
				'outdated',
				'wfPluginAbandoned'
			)
		);
	}

	/**
	 * STRICT FILTER: Returns ONLY site_ids where MainWP Wordfence status = 2 (Has Issues).
	 *
	 * @return array
	 */
	public function get_sites_requiring_sync() {
		global $wpdb;
		$wf_table =$wpdb->prefix . 'mainwp_wordfence';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( $wpdb->get_var($wpdb->prepare( 'SHOW TABLES LIKE %s', $wf_table ) ) !==$wf_table ) {
			return array();
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_col($wpdb->prepare(
				"SELECT DISTINCT site_id 
				 FROM `{$wf_table}` 
				 WHERE status = %d",
				2
			)
		);
	}

	/**
	 * Get executive analytics summary numbers.
	 *
	 * @return array
	 */
	public function get_analytics_summary() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$total_issues =$wpdb->get_var( "SELECT COUNT(*) FROM `{$this->table_name}`" );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$flagged_sites =$wpdb->get_var( "SELECT COUNT(DISTINCT site_id) FROM `{$this->table_name}`" );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$critical_count = $wpdb->get_var($wpdb->prepare(
				"SELECT COUNT(*) FROM `{$this->table_name}` WHERE category = %s",
				'malware'
			)
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$high_count = $wpdb->get_var($wpdb->prepare(
				"SELECT COUNT(*) FROM `{$this->table_name}` WHERE category = %s",
				'vulnerability'
			)
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$medium_count = $wpdb->get_var($wpdb->prepare(
				"SELECT COUNT(*) FROM `{$this->table_name}` WHERE category = %s",
				'outdated'
			)
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$low_count = $wpdb->get_var($wpdb->prepare(
				"SELECT COUNT(*) FROM `{$this->table_name}` WHERE category = %s",
				'low'
			)
		);

		return array(
			'total_issues'   => $total_issues ? intval( $total_issues ) : 0,
			'flagged_sites'  => $flagged_sites ? intval( $flagged_sites ) : 0,
			'critical_count' => $critical_count ? intval( $critical_count ) : 0,
			'high_count'     => $high_count ? intval( $high_count ) : 0,
			'medium_count'   => $medium_count ? intval( $medium_count ) : 0,
			'low_count'      => $low_count ? intval( $low_count ) : 0,
		);
	}
}
