<?php
/**
 * Validates robots.txt content line by line.
 *
 * Intentionally free of WordPress dependencies so it can be unit-tested
 * standalone. All methods are static.
 *
 * @package Divit_RobotTXT
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Divit_RobotTXT_Validator
 *
 * @since 1.0.0
 */
class Divit_RobotTXT_Validator {

	/**
	 * Directives explicitly recognised by this validator.
	 *
	 * @since 1.0.0
	 * @var string[]
	 */
	const KNOWN_DIRECTIVES = array(
		'user-agent',
		'allow',
		'disallow',
		'crawl-delay',
		'sitemap',
		'host',
	);

	/**
	 * Maximum allowed length for a single line, in characters.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	const MAX_LINE_LENGTH = 2048;

	/**
	 * Validate robots.txt content.
	 *
	 * Returns an associative array:
	 *   'valid'    => bool   — whether the content passed all checks
	 *   'errors'   => array  — blocking errors (each is a string)
	 *   'warnings' => array  — non-blocking notices (each is a string)
	 *
	 * @since 1.0.0
	 *
	 * @param string $content Raw robots.txt content (after HTML-stripping).
	 * @return array{valid: bool, errors: string[], warnings: string[]}
	 */
	public static function validate( $content ) {
		$errors   = array();
		$warnings = array();

		// Normalise line endings.
		$content = str_replace( array( "\r\n", "\r" ), "\n", $content );
		$lines   = explode( "\n", $content );

		$has_user_agent = false;

		foreach ( $lines as $index => $line ) {
			$line_number = $index + 1;
			$trimmed     = trim( $line );

			// Empty lines are valid (group separators).
			if ( '' === $trimmed ) {
				continue;
			}

			// Comment lines are valid.
			if ( '#' === $trimmed[0] ) {
				continue;
			}

			// Check maximum line length.
			if ( strlen( $trimmed ) > self::MAX_LINE_LENGTH ) {
				$errors[] = sprintf(
					/* translators: %d: line number */
					__( 'Line %d exceeds the maximum allowed length of 2048 characters.', 'divit-robottxt' ),
					$line_number
				);
				continue;
			}

			// Every valid directive must contain a colon.
			if ( false === strpos( $trimmed, ':' ) ) {
				$errors[] = sprintf(
					/* translators: 1: line number, 2: line content */
					__( 'Line %1$d is not a valid directive: "%2$s". Expected format is "Directive: value".', 'divit-robottxt' ),
					$line_number,
					$trimmed
				);
				continue;
			}

			list( $raw_directive, $raw_value ) = explode( ':', $trimmed, 2 );

			$directive = strtolower( trim( $raw_directive ) );
			$value     = trim( $raw_value );

			// Check directive is known.
			if ( ! in_array( $directive, self::KNOWN_DIRECTIVES, true ) ) {
				$errors[] = sprintf(
					/* translators: 1: line number, 2: directive name */
					__( 'Line %1$d contains an unknown directive: "%2$s".', 'divit-robottxt' ),
					$line_number,
					$raw_directive
				);
				continue;
			}

			if ( 'user-agent' === $directive ) {
				$has_user_agent = true;

				if ( '' === $value ) {
					$errors[] = sprintf(
						/* translators: %d: line number */
						__( 'Line %d: User-agent must have a value (e.g., "User-agent: *").', 'divit-robottxt' ),
						$line_number
					);
				}
			}

			// Validate Crawl-delay value is numeric.
			if ( 'crawl-delay' === $directive && '' !== $value && ! is_numeric( $value ) ) {
				$errors[] = sprintf(
					/* translators: 1: line number, 2: provided value */
					__( 'Line %1$d: Crawl-delay value must be a number. Got: "%2$s".', 'divit-robottxt' ),
					$line_number,
					$value
				);
			}

			// Validate Sitemap value looks like a URL.
			if ( 'sitemap' === $directive ) {
				if ( '' === $value ) {
					$errors[] = sprintf(
						/* translators: %d: line number */
						__( 'Line %d: Sitemap directive must include a URL.', 'divit-robottxt' ),
						$line_number
					);
				} elseif ( 0 !== strpos( $value, 'http://') && 0 !== strpos( $value, 'https://' ) ) {
					$warnings[] = sprintf(
						/* translators: 1: line number, 2: provided value */
						__( 'Line %1$d: Sitemap URL should start with http:// or https://. Got: "%2$s".', 'divit-robottxt' ),
						$line_number,
						$value
					);
				}
			}
		}

		if ( ! $has_user_agent && '' !== trim( $content ) ) {
			$warnings[] = __( 'No User-agent directive found. At least one User-agent rule is recommended.', 'divit-robottxt' );
		}

		return array(
			'valid'    => empty( $errors ),
			'errors'   => $errors,
			'warnings' => $warnings,
		);
	}

	/**
	 * Normalise line endings and strip HTML from raw user input.
	 *
	 * @since 1.0.0
	 *
	 * @param string $raw Raw input from $_POST.
	 * @return string
	 */
	public static function prepare( $raw ) {
		// Remove magic quotes added by older PHP / WordPress.
		if ( function_exists( 'wp_unslash' ) ) {
			$raw = wp_unslash( $raw );
		}

		// Strip any HTML tags — robots.txt is plain text.
		$raw = wp_strip_all_tags( $raw );

		// Normalise line endings.
		$raw = str_replace( array( "\r\n", "\r" ), "\n", $raw );

		// Trim trailing whitespace from each line while preserving structure.
		$lines = array_map( 'rtrim', explode( "\n", $raw ) );

		return implode( "\n", $lines );
	}
}
