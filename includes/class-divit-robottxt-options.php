<?php
/**
 * Data access layer for robots.txt stored content.
 *
 * All reads and writes to wp_options go through this class.
 *
 * @package Divit_RobotTXT
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Divit_RobotTXT_Options
 *
 * @since 1.0.0
 */
class Divit_RobotTXT_Options {

	/**
	 * The wp_options key used to store the robots.txt rules.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const OPTION_KEY = 'divit_robottxt_rules';

	/**
	 * Retrieve stored robots.txt content.
	 *
	 * Returns an empty string if nothing has been saved yet.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function get_rules() {
		$value = get_option( self::OPTION_KEY, '' );

		return is_string( $value ) ? $value : '';
	}

	/**
	 * Sanitise and persist robots.txt content.
	 *
	 * @since 1.0.0
	 *
	 * @param string $raw Raw POST value (before wp_unslash).
	 * @return true|WP_Error True on success, WP_Error on validation failure.
	 */
	public static function save_rules( $raw ) {
		$prepared = Divit_RobotTXT_Validator::prepare( $raw );
		$result   = Divit_RobotTXT_Validator::validate( $prepared );

		if ( ! $result['valid'] ) {
			return new WP_Error(
				'divit_robottxt_invalid',
				implode( ' | ', $result['errors'] )
			);
		}

		update_option( self::OPTION_KEY, $prepared );

		return true;
	}

	/**
	 * Return the recommended robots.txt template.
	 *
	 * The Sitemap line uses the current site URL resolved at runtime
	 * so it is always accurate regardless of site migrations.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function get_default_template() {
		$sitemap_url = trailingslashit( get_site_url() ) . 'sitemap_index.xml';

		return implode(
			"\n",
			array(
				'User-agent: *',
				'# Permitir que todo se indexe por defecto',
				'Allow: /',
				'',
				'# Bloquear el acceso a la administración y archivos del núcleo',
				'Disallow: /wp-admin/',
				'Disallow: /wp-includes/',
				'Disallow: /wp-content/plugins/',
				'Disallow: /wp-content/themes/',
				'',
				'# Permitir archivos específicos dentro de wp-admin/wp-includes si es necesario',
				'Allow: /wp-admin/admin-ajax.php',
				'Allow: /wp-includes/js/',
				'Allow: /wp-includes/css/',
				'',
				'# Bloquear URLs de búsqueda internas (evita contenido duplicado)',
				'Disallow: /?s=',
				'Disallow: /search/',
				'',
				'# Bloquear enlaces de comentarios (trackbacks)',
				'Disallow: /trackback/',
				'Disallow: /xmlrpc.php',
				'',
				'# Indicar la ruta del mapa del sitio (Sitemap)',
				'Sitemap: ' . $sitemap_url,
			)
		);
	}
}
