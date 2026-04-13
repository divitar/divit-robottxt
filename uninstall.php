<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * Removes all data stored by Divit RobotTXT from the database.
 *
 * @package Divit_RobotTXT
 * @since   1.0.0
 */

// Abort if not called from WordPress uninstall context.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Remove all plugin options.
delete_option( 'divit_robottxt_rules' );
