<?php
/**
 * Admin-side controller: menu page, asset enqueueing, form save handling.
 *
 * @package Divit_RobotTXT
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Divit_RobotTXT_Admin
 *
 * @since 1.0.0
 */
class Divit_RobotTXT_Admin {

	/**
	 * The hook suffix returned by add_options_page().
	 * Used to scope asset enqueueing to this page only.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $page_hook = '';

	/**
	 * Register the plugin settings page under Settings.
	 *
	 * @since 1.0.0
	 */
	public function add_plugin_page() {
		$this->page_hook = add_options_page(
			__( 'Divit RobotTXT', 'divit-robottxt' ),       // Page <title>
			__( 'Divit RobotTXT', 'divit-robottxt' ),       // Menu label
			'manage_options',                                 // Required capability
			'divit-robottxt',                                 // Menu slug
			array( $this, 'render_page' )                    // Callback
		);
	}

	/**
	 * Enqueue admin CSS and JS only on the plugin's own settings page.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 */
	public function enqueue_assets( $hook_suffix ) {
		if ( 'settings_page_divit-robottxt' !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'divit-robottxt-admin',
			DIVIT_ROBOTTXT_PLUGIN_URL . 'admin/css/divit-robottxt-admin.css',
			array(),
			DIVIT_ROBOTTXT_VERSION
		);

		wp_enqueue_script(
			'divit-robottxt-admin',
			DIVIT_ROBOTTXT_PLUGIN_URL . 'admin/js/divit-robottxt-admin.js',
			array(),                       // No jQuery dependency needed.
			DIVIT_ROBOTTXT_VERSION,
			true                           // Load in footer.
		);

		// Pass data to JavaScript via wp_localize_script.
		wp_localize_script(
			'divit-robottxt-admin',
			'divitRobotsConfig',
			array(
				'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
				'validateNonce'    => wp_create_nonce( 'divit_robottxt_validate' ),
				'defaultTemplate'  => Divit_RobotTXT_Options::get_default_template(),
				'i18n'             => array(
					'confirmTemplate' => __( 'This will replace the current content with the recommended template. Continue?', 'divit-robottxt' ),
					'validating'      => __( 'Validating…', 'divit-robottxt' ),
					'validOk'         => __( 'Content is valid.', 'divit-robottxt' ),
					'hasWarnings'     => __( 'Valid, but with warnings:', 'divit-robottxt' ),
					'hasErrors'       => __( 'Validation failed:', 'divit-robottxt' ),
					'networkError'    => __( 'Network error. Please try again.', 'divit-robottxt' ),
				),
			)
		);
	}

	/**
	 * Handle the settings form submission.
	 *
	 * Hooked to admin_init so it runs before any output. On success or
	 * failure it redirects back to the settings page with a query-string
	 * flag that render_page() reads to show a notice.
	 *
	 * @since 1.0.0
	 */
	public function handle_form_save() {
		// Bail early if the form was not submitted.
		if ( ! isset( $_POST['divit_robottxt_nonce'] ) ) {
			return;
		}

		// 1. Verify nonce — prevents CSRF.
		if ( ! check_admin_referer( 'divit_robottxt_save_rules', 'divit_robottxt_nonce' ) ) {
			wp_die(
				esc_html__( 'Security check failed. Please try again.', 'divit-robottxt' ),
				esc_html__( 'Error', 'divit-robottxt' ),
				array( 'response' => 403 )
			);
		}

		// 2. Check capability — only admins may save.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				esc_html__( 'You do not have permission to change these settings.', 'divit-robottxt' ),
				esc_html__( 'Forbidden', 'divit-robottxt' ),
				array( 'response' => 403 )
			);
		}

		// 3. Retrieve raw POST value (wp_unslash happens inside save_rules).
		$raw = isset( $_POST['divit_robottxt_content'] ) ? $_POST['divit_robottxt_content'] : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		// 4. Delegate sanitisation, validation and storage.
		$result = Divit_RobotTXT_Options::save_rules( $raw );

		if ( is_wp_error( $result ) ) {
			// Store error message in a transient so the redirect can display it.
			set_transient(
				'divit_robottxt_error_' . get_current_user_id(),
				$result->get_error_message(),
				60
			);
			// Store the submitted (stripped) content so the user doesn't lose their work.
			set_transient(
				'divit_robottxt_submitted_' . get_current_user_id(),
				Divit_RobotTXT_Validator::prepare( $raw ),
				60
			);

			wp_safe_redirect(
				add_query_arg( 'divit-status', 'error', $this->settings_page_url() )
			);
			exit;
		}

		wp_safe_redirect(
			add_query_arg( 'divit-status', 'saved', $this->settings_page_url() )
		);
		exit;
	}

	/**
	 * Render the settings page.
	 *
	 * @since 1.0.0
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		require_once DIVIT_ROBOTTXT_PLUGIN_DIR . 'admin/partials/admin-display.php';
	}

	/**
	 * Return the URL of the plugin settings page.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	private function settings_page_url() {
		return admin_url( 'options-general.php?page=divit-robottxt' );
	}
}
