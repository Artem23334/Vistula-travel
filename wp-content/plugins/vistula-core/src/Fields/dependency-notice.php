<?php
/**
 * Field-framework dependency notice (TD-12).
 *
 * json-sync.php (same directory) is deliberately defensive — a no-op if
 * Secure Custom Fields isn't active, so vistula-core itself never fatals.
 * But a silent no-op also means an editor opening a Tour just sees no
 * field tabs at all, with no indication why. This file is the other half
 * of "fail gracefully": say so, clearly, in wp-admin, with the exact fix.
 *
 * Mirrors the same pattern already used for vistula-core's own dependency
 * on the theme side (vistula-travel/inc/plugin-dependency.php) — a
 * missing *required* plugin gets a notice, not silence.
 *
 * @package Vistula_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * True once Secure Custom Fields (or ACF) has actually loaded.
 *
 * Same check src/Api/tours.php's vistula_core_acf_field() wrapper already
 * uses (function_exists('get_field')) — kept to that one check rather
 * than adding a second detection method for the same thing.
 *
 * @return bool
 */
function vistula_core_field_framework_active(): bool {
	return function_exists( 'get_field' );
}

/**
 * Warns the site owner in wp-admin if Secure Custom Fields isn't active,
 * with the exact remediation steps (Composer, or the plugin directory).
 */
function vistula_core_field_framework_admin_notice(): void {
	if ( vistula_core_field_framework_active() ) {
		return;
	}
	?>
	<div class="notice notice-error">
		<p>
			<strong><?php esc_html_e( 'Vistula Core:', 'vistula-core' ); ?></strong>
			<?php
			esc_html_e(
				'The "Secure Custom Fields" plugin is required (TD-12) and is not active. Tour fields will not appear on the Tour edit screen until it is installed and activated.',
				'vistula-core'
			);
			?>
		</p>
		<p>
			<?php
			esc_html_e( 'Either run, from the project root:', 'vistula-core' );
			?>
			<code>composer install</code>
			<?php
			esc_html_e(
				'(installs it automatically, per composer.json), or install "Secure Custom Fields" manually from Plugins → Add New and activate it.',
				'vistula-core'
			);
			?>
		</p>
	</div>
	<?php
}
add_action( 'admin_notices', 'vistula_core_field_framework_admin_notice' );
