<?php
/**
 * Serves the stored robots.txt content via the WordPress robots_txt filter.
 *
 * No file is written to disk. WordPress intercepts /robots.txt requests
 * and applies this filter before outputting the response.
 *
 * @package Divit_RobotTXT
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Divit_RobotTXT_Robots
 *
 * @since 1.0.0
 */
class Divit_RobotTXT_Robots {

	/**
	 * Filter the robots.txt output.
	 *
	 * Replaces WordPress's default output with the content stored by this
	 * plugin, but only when:
	 *   1. The site is marked as public (blog_public = 1). If the admin
	 *      has set "Discourage search engines", WordPress's own restrictive
	 *      output takes priority and we leave it untouched.
	 *   2. Custom content has actually been saved. If the textarea was
	 *      never saved, we fall back to WordPress's default.
	 *
	 * @since 1.0.0
	 *
	 * @param string $output  WordPress-generated robots.txt content.
	 * @param bool   $public  Whether the site is publicly visible to crawlers.
	 * @return string         The robots.txt content to serve.
	 */
	public function filter_robots_txt( $output, $public ) {
		// Respect the "Discourage search engines" Reading setting.
		if ( ! $public ) {
			return $output;
		}

		$stored = Divit_RobotTXT_Options::get_rules();

		if ( '' === $stored ) {
			return $output;
		}

		// Ensure the response ends with a newline (RFC convention).
		return rtrim( $stored ) . "\n";
	}
}
