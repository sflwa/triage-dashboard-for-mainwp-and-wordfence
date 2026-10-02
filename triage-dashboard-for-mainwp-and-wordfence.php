<?php
/**
 * Plugin Name: Triage Dashboard for MainWP and WordFence
 * Plugin URI: https://southfloridawebadvisors.com/
 * Description: High-efficiency, smart-synced security triage dashboard for Wordfence and MainWP. Requires MainWP Dashboard, Wordfence, and MainWP Wordfence Extension.
 * Version: 1.6.0
 * Author: South Florida Web Advisors
 * Author URI: https://southfloridawebadvisors.com/
 * Contributors: sflwa
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: triage-dashboard-for-mainwp-and-wordfence
 * Requires at least: 5.8
 * Tested up to: 7.1
 * Requires PHP: 7.4
 *
 * @package MainWP_Wordfence_Triage
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MWP_WF_TRIAGE_PATH', plugin_dir_path( __FILE__ ) );

require_once MWP_WF_TRIAGE_PATH . 'includes/class-triage-db.php';
require_once MWP_WF_TRIAGE_PATH . 'includes/class-triage-cron.php';
require_once MWP_WF_TRIAGE_PATH . 'includes/class-triage-ajax.php';

// Include the shared support handler if present.
if ( file_exists( plugin_dir_path( __FILE__ ) . 'sflwa-support-handler.php' ) ) {
	require_once plugin_dir_path( __FILE__ ) . 'sflwa-support-handler.php';

	if ( function_exists( 'sflwa_register_support_page' ) ) {
		sflwa_register_support_page(
			array(
				'plugin_name'   => 'Triage Dashboard for MainWP and WordFence',
				'plugin_slug'   => 'triage-dashboard-for-mainwp-and-wordfence',
				'text_domain'   => 'triage-dashboard-for-mainwp-and-wordfence',
				'plugin_file'   => __FILE__,
				'submenu_title' => 'Security Triage',
				'description'   => 'High-efficiency, smart-synced security triage dashboard for Wordfence and MainWP.',
				'version'       => '1.6.0',
			)
		);
	}
}

/**
 * Main Plugin Class
 */
class MainWP_WF_Triage {

	/**
	 * Instance variable.
	 *
	 * @var MainWP_WF_Triage|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return MainWP_WF_Triage
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
		register_activation_hook( __FILE__, array( $this, 'activate' ) );

		add_action( 'admin_menu', array( $this, 'register_admin_menu' ), 99 );
		add_action( 'admin_notices', array( $this, 'check_dependencies' ) );

		MainWP_WF_Triage_Cron::instance();
		MainWP_WF_Triage_Ajax::instance();
	}

	/**
	 * Activation hook.
	 */
	public function activate() {
		MainWP_WF_Triage_DB::instance()->create_table();

		if ( ! wp_next_scheduled( 'mainwp_wf_triage_cron_hook' ) ) {
			wp_schedule_event( time(), 'hourly', 'mainwp_wf_triage_cron_hook' );
		}
	}

	/**
	 * Verifies required parent plugins (MainWP & MainWP Wordfence Extension) are active.
	 */
	public function check_dependencies() {
		if ( ! class_exists( 'MainWP\Dashboard\MainWP' ) && ! class_exists( 'MainWP_System' ) ) {
			echo '<div class="notice notice-error"><p>';
			echo '<strong>Triage Dashboard for MainWP and WordFence:</strong> Requires the <a href="https://mainwp.com/" target="_blank">MainWP Dashboard</a> plugin to be installed and active.';
			echo '</p></div>';
		}

		global $mainWPWordfenceExtensionActivator;
		if ( ! isset( $mainWPWordfenceExtensionActivator ) ) {
			echo '<div class="notice notice-warning"><p>';
			echo '<strong>Triage Dashboard for MainWP and WordFence:</strong> Requires the <strong>MainWP Wordfence Extension</strong> to be initialized in order to fetch scan findings.';
			echo '</p></div>';
		}
	}

	/**
	 * Register main menu and submenus.
	 */
	public function register_admin_menu() {
		add_menu_page(
			'Security Triage',
			'Security Triage',
			'manage_options',
			'mainwp-security-triage',
			array( $this, 'render_admin_dashboard' ),
			'dashicons-shield-alt',
			30
		);

		add_submenu_page(
			'mainwp-security-triage',
			'Triage Dashboard',
			'Triage Dashboard',
			'manage_options',
			'mainwp-security-triage',
			array( $this, 'render_admin_dashboard' )
		);

		add_submenu_page(
			'mainwp-security-triage',
			'Executive Summary',
			'Executive Summary',
			'manage_options',
			'mainwp-security-triage-summary',
			array( $this, 'render_admin_summary' )
		);
	}

	/**
	 * Render Triage Dashboard View.
	 */
	public function render_admin_dashboard() {
		$template = MWP_WF_TRIAGE_PATH . 'templates/admin-dashboard-view.php';
		if ( file_exists( $template ) ) {
			include $template;
		}
	}

	/**
	 * Render Executive Summary View.
	 */
	public function render_admin_summary() {
		$template = MWP_WF_TRIAGE_PATH . 'templates/admin-summary-view.php';
		if ( file_exists( $template ) ) {
			include $template;
		}
	}
}

MainWP_WF_Triage::instance();
