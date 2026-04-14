<?php
/**
 * Orchestrates all plugin hook registrations.
 *
 * Acts as the central bootstrap: loads every class, instantiates them,
 * and registers every action/filter through a single auditable point.
 *
 * @package Divit_RobotTXT
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Divit_RobotTXT_Loader
 *
 * Loads dependencies and registers all WordPress hooks.
 *
 * @since 1.0.0
 */
class Divit_RobotTXT_Loader {

	/**
	 * Run the loader: load files and wire up hooks.
	 *
	 * @since 1.0.0
	 */
	public function run() {
		$this->load_dependencies();
		$this->register_robots_hooks();

		if ( is_admin() ) {
			$this->register_admin_hooks();
		}
	}

	/**
	 * Require all plugin class files.
	 *
	 * @since 1.0.0
	 */
	private function load_dependencies() {
		require_once DIVIT_ROBOTTXT_PLUGIN_DIR . 'includes/class-divit-robottxt-validator.php';
		require_once DIVIT_ROBOTTXT_PLUGIN_DIR . 'includes/class-divit-robottxt-options.php';
		require_once DIVIT_ROBOTTXT_PLUGIN_DIR . 'includes/class-divit-robottxt-robots.php';

		if ( is_admin() ) {
			require_once DIVIT_ROBOTTXT_PLUGIN_DIR . 'admin/class-divit-robottxt-admin.php';
			require_once DIVIT_ROBOTTXT_PLUGIN_DIR . 'admin/class-divit-robottxt-ajax.php';
		}
	}

	/**
	 * Register the robots.txt filter hook.
	 * This runs on every request, not just in admin.
	 *
	 * @since 1.0.0
	 */
	private function register_robots_hooks() {
		$robots = new Divit_RobotTXT_Robots();

		add_filter( 'robots_txt', array( $robots, 'filter_robots_txt' ), 10, 2 );
	}

	/**
	 * Register all admin-side hooks.
	 *
	 * @since 1.0.0
	 */
	private function register_admin_hooks() {
		$admin = new Divit_RobotTXT_Admin();

		add_action( 'admin_menu', array( $admin, 'add_plugin_page' ) );
		add_action( 'admin_init', array( $admin, 'handle_form_save' ) );
		add_action( 'admin_enqueue_scripts', array( $admin, 'enqueue_assets' ) );

		$ajax = new Divit_RobotTXT_Ajax();

		add_action( 'wp_ajax_divit_robottxt_validate', array( $ajax, 'handle_validate' ) );
	}
}
