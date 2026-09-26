<?php
/**
 * Plugin Name:        Vistula Core
 * Plugin URI:          https://github.com/Artem23334/vistula-travel
 * Description:        Domain/data layer for Vistula Travel — CPTs, taxonomies, settings, and the data-access layer the theme calls. See docs/project-architecture.md §3.2–3.3. Presentation stays out of this plugin.
 * Version:              0.1.0
 * Requires at least:   6.5
 * Requires PHP:         8.2
 * Author:                Artem23334
 * License:               GPL-2.0-or-later
 * License URI:           https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:           vistula-core
 *
 * Foundation only. Does NOT yet register the Tour/Destination CPTs, taxonomies,
 * settings page, or any content model — those are later stages in
 * docs/implementation-roadmap.md (Stage 3 Global Settings, Stage 5 Tours CMS,
 * Stage 8 Destinations, ...). This file establishes safe loading, the
 * data-access contract functions so the theme has something real to call
 * without being coupled to a field framework or raw get_option() calls
 * (docs/project-architecture.md §3.3, rule 2), the Local JSON integration
 * point for the field framework decided in TD-12 (Secure Custom Fields —
 * no field groups exist yet), and the Stage 2 content-policy baseline
 * (TD-41: comments disabled, author/tag archives noindex).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

define( 'VISTULA_CORE_VERSION', '0.1.0' );
define( 'VISTULA_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'VISTULA_CORE_URI', plugin_dir_url( __FILE__ ) );
define( 'VISTULA_CORE_FILE', __FILE__ );

/**
 * Minimal PSR-4-ish autoloader for the Vistula\Core namespace.
 *
 * No Composer dependency is introduced at this stage (architecture §17.3 —
 * avoid unnecessary frameworks/build tooling until a concrete need appears).
 * Maps Vistula\Core\Foo\Bar to src/Foo/Bar.php.
 */
spl_autoload_register(
	static function ( string $class ): void {
		$prefix = 'Vistula\\Core\\';

		if ( ! str_starts_with( $class, $prefix ) ) {
			return;
		}

		$relative = substr( $class, strlen( $prefix ) );
		$path     = VISTULA_CORE_DIR . 'src/' . str_replace( '\\', '/', $relative ) . '.php';

		if ( is_readable( $path ) ) {
			require $path;
		}
	}
);

/**
 * Bootstraps the plugin. Deliberately does very little right now — this is
 * the hook point later stages attach their own bootstrapping to, rather than
 * scattering add_action() calls across this file.
 */
function vistula_core_init(): void {
	load_plugin_textdomain( 'vistula-core', false, dirname( plugin_basename( VISTULA_CORE_FILE ) ) . '/languages' );

	// Data-access layer contracts are loaded unconditionally: templates must
	// always be able to call them, even before their real implementations
	// (settings, CPTs) exist. See src/Api/*.php.
	require_once VISTULA_CORE_DIR . 'src/Api/settings.php';
	require_once VISTULA_CORE_DIR . 'src/Api/page-roles.php';
	require_once VISTULA_CORE_DIR . 'src/Api/tours.php';
	require_once VISTULA_CORE_DIR . 'src/Api/destinations.php';
	require_once VISTULA_CORE_DIR . 'src/Api/translation.php';

	// Field-framework integration (TD-12) — defines no fields, only wires
	// where future field-group definitions will save to/load from.
	require_once VISTULA_CORE_DIR . 'src/Fields/json-sync.php';

	// Stage 2 content-policy baseline (TD-41) — comments off, noindex on
	// author/tag archives. A business rule, so it lives here, not in the theme.
	require_once VISTULA_CORE_DIR . 'src/Content/comments-and-archives.php';

	// Nothing else is registered yet: no CPTs, no taxonomies, no settings
	// page, no query builders. Those hook in here in later stages without
	// requiring changes to this bootstrap file.
}
add_action( 'plugins_loaded', 'vistula_core_init' );

/**
 * Activation is intentionally a no-op beyond the safety check below: there
 * is nothing yet that needs a rewrite-rule flush or a default option. Later
 * stages (e.g. Stage 5, once CPTs are registered) should extend this, not
 * replace it.
 */
function vistula_core_activate(): void {
	if ( version_compare( PHP_VERSION, '8.2', '<' ) ) {
		deactivate_plugins( plugin_basename( VISTULA_CORE_FILE ) );
		wp_die(
			esc_html__( 'Vistula Core requires PHP 8.2 or higher.', 'vistula-core' ),
			esc_html__( 'Plugin activation error', 'vistula-core' ),
			array( 'back_link' => true )
		);
	}
}
register_activation_hook( VISTULA_CORE_FILE, 'vistula_core_activate' );
