<?php
/**
 * AJAX endpoint: validate robots.txt content without saving.
 *
 * Only available to logged-in users with manage_options capability.
 * No public (nopriv) equivalent is registered.
 *
 * @package Divit_RobotTXT
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Divit_RobotTXT_Ajax
 *
 * @since 1.0.0
 */
class Divit_RobotTXT_Ajax {

	/**
	 * Handle the divit_robottxt_validate AJAX action.
	 *
	 * Expects POST fields:
	 *   nonce   — wp_create_nonce( 'divit_robottxt_validate' )
	 *   content — raw textarea content
	 *
	 * Responds with wp_send_json_success / wp_send_json_error.
	 *
	 * @since 1.0.0
	 */
	public function handle_validate() {
		// 1. Verify nonce — must be first, before any capability check.
		check_ajax_referer( 'divit_robottxt_validate', 'nonce' );

		// 2. Verify capability.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Insufficient permissions.', 'divit-robottxt' ) ),
				403
			);
		}

		// 3. Retrieve and prepare the submitted content.
		$raw      = isset( $_POST['content'] ) ? $_POST['content'] : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$prepared = Divit_RobotTXT_Validator::prepare( $raw );

		// 4. Run validation.
		$result = Divit_RobotTXT_Validator::validate( $prepared );

		// 5. Return structured response.
		wp_send_json_success(
			array(
				'valid'    => $result['valid'],
				'errors'   => array_values( $result['errors'] ),
				'warnings' => array_values( $result['warnings'] ),
			)
		);
	}
}
