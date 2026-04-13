<?php
/**
 * Plugin Name:       Divit RobotTXT
 * Plugin URI:        https://github.com/divitar/divit-robottxt
 * Description:       Manage your robots.txt content from the WordPress admin panel. Content is served via WordPress hook — no file is ever written to disk.
 * Version:           1.0.0
 * Requires at least: 5.0
 * Requires PHP:      7.2
 * Author:            divitar
 * Author URI:        https://profiles.wordpress.org/divitar/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       divit-robottxt
 * Domain Path:       /languages
 *
 * @package Divit_RobotTXT
 */

// Abort if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'DIVIT_ROBOTTXT_VERSION', '1.0.0' );
define( 'DIVIT_ROBOTTXT_PLUGIN_FILE', __FILE__ );
define( 'DIVIT_ROBOTTXT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'DIVIT_ROBOTTXT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Loads the plugin after all other plugins are loaded.
 * Using plugins_loaded ensures full WordPress environment is available.
 *
 * @since 1.0.0
 */
function divit_robottxt_run() {
	require_once DIVIT_ROBOTTXT_PLUGIN_DIR . 'includes/class-divit-robottxt-loader.php';

	$loader = new Divit_RobotTXT_Loader();
	$loader->run();
}
add_action( 'plugins_loaded', 'divit_robottxt_run' );
