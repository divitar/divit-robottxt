<?php
/**
 * Settings page HTML template.
 *
 * Variables available from the caller (Divit_RobotTXT_Admin::render_page):
 *   All WordPress globals are accessible here.
 *
 * @package Divit_RobotTXT
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ── Gather data needed by the view ───────────────────────────────────────────

$stored_content  = Divit_RobotTXT_Options::get_rules();
$user_id         = get_current_user_id();
$blog_public     = (bool) get_option( 'blog_public', true );
$physical_exists = file_exists( ABSPATH . 'robots.txt' );

// After a failed save, restore the last submitted content so the user
// doesn't lose their work.
$submitted_content = get_transient( 'divit_robottxt_submitted_' . $user_id );
$error_message     = get_transient( 'divit_robottxt_error_' . $user_id );

if ( false !== $submitted_content ) {
	$display_content = $submitted_content;
	delete_transient( 'divit_robottxt_submitted_' . $user_id );
} else {
	$display_content = $stored_content;
}

if ( false !== $error_message ) {
	delete_transient( 'divit_robottxt_error_' . $user_id );
}

// ── Status flags ─────────────────────────────────────────────────────────────

$has_content  = '' !== $stored_content;
$robots_url   = get_site_url() . '/robots.txt';

// ── HTTP-level status notice (redirect query arg) ─────────────────────────────

$divit_status = isset( $_GET['divit-status'] ) ? sanitize_key( $_GET['divit-status'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
?>

<div class="wrap divit-robottxt-wrap">

	<h1 class="divit-robottxt-page-title">
		<span class="dashicons dashicons-shield-alt" aria-hidden="true"></span>
		<?php echo esc_html( get_admin_page_title() ); ?>
	</h1>

	<?php // ── Admin notices ───────────────────────────────────────────────── ?>

	<?php if ( 'saved' === $divit_status ) : ?>
		<div class="notice notice-success is-dismissible" role="alert">
			<p><?php esc_html_e( 'robots.txt content saved successfully.', 'divit-robottxt' ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( 'error' === $divit_status && '' !== $error_message ) : ?>
		<div class="notice notice-error is-dismissible" role="alert">
			<p>
				<strong><?php esc_html_e( 'Save failed:', 'divit-robottxt' ); ?></strong>
				<?php echo esc_html( $error_message ); ?>
			</p>
		</div>
	<?php endif; ?>

	<?php if ( $physical_exists ) : ?>
		<div class="notice notice-error" role="alert">
			<p>
				<strong><?php esc_html_e( 'Physical file detected:', 'divit-robottxt' ); ?></strong>
				<?php esc_html_e( 'A robots.txt file exists in your WordPress root directory. Your web server will serve that file directly, bypassing this plugin entirely. Remove the file to use this plugin.', 'divit-robottxt' ); ?>
			</p>
		</div>
	<?php endif; ?>

	<?php if ( ! $blog_public ) : ?>
		<div class="notice notice-warning" role="alert">
			<p>
				<strong><?php esc_html_e( 'Search engines are discouraged:', 'divit-robottxt' ); ?></strong>
				<?php
				printf(
					wp_kses(
						/* translators: %s: link to Reading Settings page */
						__( 'Your site is configured to discourage search engines in %s. This plugin respects that setting and will not override the restrictive robots.txt WordPress generates. Change that setting first to use your custom content.', 'divit-robottxt' ),
						array(
							'a' => array( 'href' => array() ),
						)
					),
					'<a href="' . esc_url( admin_url( 'options-reading.php' ) ) . '">' . esc_html__( 'Reading Settings', 'divit-robottxt' ) . '</a>'
				);
				?>
			</p>
		</div>
	<?php endif; ?>

	<?php // ── Two-column layout: editor + sidebar ────────────────────────── ?>

	<div class="divit-robottxt-layout">

		<?php // ── Left column: editor ─────────────────────────────────────── ?>

		<div class="divit-robottxt-main">

			<form method="post" action="" id="divit-robottxt-form" novalidate>

				<?php wp_nonce_field( 'divit_robottxt_save_rules', 'divit_robottxt_nonce' ); ?>

				<?php // ── Toolbar ─────────────────────────────────────────── ?>

				<div class="divit-robottxt-toolbar" role="toolbar" aria-label="<?php esc_attr_e( 'Editor toolbar', 'divit-robottxt' ); ?>">

					<button
						type="button"
						id="divit-btn-template"
						class="button button-secondary"
					>
						<span class="dashicons dashicons-download" aria-hidden="true"></span>
						<?php esc_html_e( 'Load Recommended Template', 'divit-robottxt' ); ?>
					</button>

					<button
						type="button"
						id="divit-btn-validate"
						class="button button-secondary"
					>
						<span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
						<?php esc_html_e( 'Validate', 'divit-robottxt' ); ?>
					</button>

					<div
						id="divit-validation-result"
						class="divit-validation-result"
						role="status"
						aria-live="polite"
						aria-atomic="true"
					></div>

				</div>

				<?php // ── Quick-insert buttons ────────────────────────────── ?>

				<div class="divit-quick-add" role="group" aria-label="<?php esc_attr_e( 'Quick-add directives', 'divit-robottxt' ); ?>">
					<span class="divit-quick-add__label" aria-hidden="true">
						<?php esc_html_e( 'Quick add:', 'divit-robottxt' ); ?>
					</span>

					<?php
					$quick_rules = array(
						'User-agent: *'                       => 'User-agent: *',
						'Disallow: /wp-admin/'                => 'Disallow: /wp-admin/',
						'Allow: /wp-admin/admin-ajax.php'     => 'Allow: /wp-admin/admin-ajax.php',
						'Allow: /'                            => 'Allow: /',
						'Disallow: /'                         => 'Disallow: /',
						'Crawl-delay: 10'                     => 'Crawl-delay: 10',
						'Sitemap: ' . get_site_url() . '/sitemap.xml' => 'Sitemap',
					);

					foreach ( $quick_rules as $rule => $label ) :
						?>
						<button
							type="button"
							class="button button-small divit-quick-insert"
							data-value="<?php echo esc_attr( $rule ); ?>"
						><?php echo esc_html( $label ); ?></button>
					<?php endforeach; ?>
				</div>

				<?php // ── Textarea ────────────────────────────────────────── ?>

				<div class="divit-editor-wrap">
					<label for="divit_robottxt_content" class="screen-reader-text">
						<?php esc_html_e( 'robots.txt content', 'divit-robottxt' ); ?>
					</label>

					<textarea
						id="divit_robottxt_content"
						name="divit_robottxt_content"
						class="divit-editor"
						rows="22"
						spellcheck="false"
						autocomplete="off"
						autocorrect="off"
						autocapitalize="off"
						wrap="off"
						aria-describedby="divit-editor-help"
					><?php echo esc_textarea( $display_content ); ?></textarea>

					<p id="divit-editor-help" class="description">
						<?php
						esc_html_e(
							'Each line must be a valid robots.txt directive. Lines beginning with # are treated as comments. Leave blank to use WordPress\'s default output.',
							'divit-robottxt'
						);
						?>
					</p>
				</div>

				<?php // ── Form actions ────────────────────────────────────── ?>

				<div class="divit-form-actions">
					<button type="submit" name="divit_robottxt_submit" class="button button-primary button-large">
						<span class="dashicons dashicons-saved" aria-hidden="true"></span>
						<?php esc_html_e( 'Save Changes', 'divit-robottxt' ); ?>
					</button>

					<a
						href="<?php echo esc_url( $robots_url ); ?>"
						target="_blank"
						rel="noopener noreferrer"
						class="button button-secondary button-large"
					>
						<span class="dashicons dashicons-external" aria-hidden="true"></span>
						<?php esc_html_e( 'View robots.txt', 'divit-robottxt' ); ?>
					</a>
				</div>

			</form>
		</div>

		<?php // ── Right column: sidebar cards ─────────────────────────────── ?>

		<aside class="divit-robottxt-sidebar" aria-label="<?php esc_attr_e( 'Plugin information', 'divit-robottxt' ); ?>">

			<?php // ── Status card ─────────────────────────────────────────── ?>

			<div class="divit-card">
				<h2 class="divit-card__title">
					<span class="dashicons dashicons-info-outline" aria-hidden="true"></span>
					<?php esc_html_e( 'Status', 'divit-robottxt' ); ?>
				</h2>

				<ul class="divit-status-list">

					<li class="divit-status-list__item <?php echo $physical_exists ? 'is-warning' : 'is-ok'; ?>">
						<span
							class="dashicons <?php echo $physical_exists ? 'dashicons-warning' : 'dashicons-yes-alt'; ?>"
							aria-hidden="true"
						></span>
						<span>
							<?php
							if ( $physical_exists ) {
								esc_html_e( 'Physical robots.txt file found — plugin bypassed', 'divit-robottxt' );
							} else {
								esc_html_e( 'No physical robots.txt file', 'divit-robottxt' );
							}
							?>
						</span>
					</li>

					<li class="divit-status-list__item <?php echo $blog_public ? 'is-ok' : 'is-warning'; ?>">
						<span
							class="dashicons <?php echo $blog_public ? 'dashicons-yes-alt' : 'dashicons-warning'; ?>"
							aria-hidden="true"
						></span>
						<span>
							<?php
							if ( $blog_public ) {
								esc_html_e( 'Site is visible to search engines', 'divit-robottxt' );
							} else {
								esc_html_e( 'Discourage search engines is ON', 'divit-robottxt' );
							}
							?>
						</span>
					</li>

					<li class="divit-status-list__item <?php echo $has_content ? 'is-ok' : 'is-neutral'; ?>">
						<span
							class="dashicons <?php echo $has_content ? 'dashicons-yes-alt' : 'dashicons-minus'; ?>"
							aria-hidden="true"
						></span>
						<span>
							<?php
							if ( $has_content ) {
								esc_html_e( 'Custom content active', 'divit-robottxt' );
							} else {
								esc_html_e( 'Using WordPress default', 'divit-robottxt' );
							}
							?>
						</span>
					</li>

				</ul>
			</div>

			<?php // ── Directives reference card ───────────────────────────── ?>

			<div class="divit-card">
				<h2 class="divit-card__title">
					<span class="dashicons dashicons-editor-code" aria-hidden="true"></span>
					<?php esc_html_e( 'Valid Directives', 'divit-robottxt' ); ?>
				</h2>

				<dl class="divit-directives">
					<dt><code>User-agent: *</code></dt>
					<dd><?php esc_html_e( 'Target all bots, or a specific bot name.', 'divit-robottxt' ); ?></dd>

					<dt><code>Disallow: /path</code></dt>
					<dd><?php esc_html_e( 'Block crawlers from a path.', 'divit-robottxt' ); ?></dd>

					<dt><code>Allow: /path</code></dt>
					<dd><?php esc_html_e( 'Explicitly allow a path (overrides Disallow).', 'divit-robottxt' ); ?></dd>

					<dt><code>Crawl-delay: 10</code></dt>
					<dd><?php esc_html_e( 'Seconds between requests (not all bots honour this).', 'divit-robottxt' ); ?></dd>

					<dt><code>Sitemap: URL</code></dt>
					<dd><?php esc_html_e( 'Full URL to your XML sitemap.', 'divit-robottxt' ); ?></dd>

					<dt><code># comment</code></dt>
					<dd><?php esc_html_e( 'Lines starting with # are ignored by crawlers.', 'divit-robottxt' ); ?></dd>
				</dl>
			</div>

			<?php // ── How it works card ───────────────────────────────────── ?>

			<div class="divit-card">
				<h2 class="divit-card__title">
					<span class="dashicons dashicons-admin-network" aria-hidden="true"></span>
					<?php esc_html_e( 'How It Works', 'divit-robottxt' ); ?>
				</h2>

				<p>
					<?php esc_html_e( 'Content is stored in the WordPress database and served dynamically via the robots_txt filter. No file is written to disk.', 'divit-robottxt' ); ?>
				</p>
				<p>
					<?php esc_html_e( 'WordPress intercepts requests to /robots.txt when no physical file exists in your site root. If a physical file is found, your server serves it directly and this plugin is bypassed.', 'divit-robottxt' ); ?>
				</p>
			</div>

		</aside>
	</div><!-- .divit-robottxt-layout -->

</div><!-- .wrap -->
