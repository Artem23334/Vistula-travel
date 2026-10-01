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
 * Implements the Tours + Destinations + Travel Guide CMS/Data block: the
 * `tour` and `destination` post types, `tour_category`/`tour_type`
 * taxonomies, core Post categories for the Travel Guide, their ACF/SCF
 * field groups (config/acf-json/, per TD-12), derived-field computation,
 * publish-time validation, admin columns, and the real
 * vistula_tour()/vistula_destination()/vistula_post()/Vistula\Core\Queries
 * data-access layer. Does NOT yet register FAQ/Testimonial CPTs — later
 * stages in docs/implementation-roadmap.md. This file establishes safe
 * loading, the data-access contract functions (now backed by real
 * storage for settings/page-roles — Stage 3, TD-17; Tours, Destinations
 * and the Travel Guide — this block), the Local JSON integration point
 * for the field framework decided in TD-12 (Secure Custom Fields), and
 * the Stage 2 content-policy baseline (TD-41: comments disabled,
 * author/tag archives noindex).
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

	// Stage 3: Global Settings (TD-17) — schema first (defines the
	// defaults/sanitizer both the admin page and vistula_setting() need),
	// then the admin page itself, then the page-roles bootstrap.
	require_once VISTULA_CORE_DIR . 'src/Settings/schema.php';
	require_once VISTULA_CORE_DIR . 'src/Settings/admin-page.php';
	require_once VISTULA_CORE_DIR . 'src/Settings/page-roles-bootstrap.php';

	// Field-framework integration (TD-12) — defines no fields, only wires
	// where future field-group definitions will save to/load from.
	require_once VISTULA_CORE_DIR . 'src/Fields/json-sync.php';
	require_once VISTULA_CORE_DIR . 'src/Fields/dependency-notice.php';

	// Stage 2 content-policy baseline (TD-41) — comments off, noindex on
	// author/tag archives. A business rule, so it lives here, not in the theme.
	require_once VISTULA_CORE_DIR . 'src/Content/comments-and-archives.php';

	// Stage 5: Tours CMS. Enumerations first (referenced by the fields
	// below); CPT + taxonomies next; then the hooks that depend on them
	// (derived fields, publish validation, admin columns) — order here
	// only matters for readability, since every one of these just
	// registers callbacks on 'init'/'admin_init'/'acf/save_post', none
	// execute immediately at require-time.
	require_once VISTULA_CORE_DIR . 'config/enumerations.php';
	require_once VISTULA_CORE_DIR . 'src/Media/image-sizes.php';
	require_once VISTULA_CORE_DIR . 'src/PostTypes/tour.php';
	require_once VISTULA_CORE_DIR . 'src/Taxonomies/tour-taxonomies.php';
	require_once VISTULA_CORE_DIR . 'src/PostTypes/tour-derived-fields.php';
	require_once VISTULA_CORE_DIR . 'src/PostTypes/tour-validation.php';
	require_once VISTULA_CORE_DIR . 'src/PostTypes/tour-admin-columns.php';

	// CMS/Data block: Destinations (data-model §5) and the Travel Guide
	// (core Posts, data-model §6) — both consume vistula_core_acf_field()
	// and Vistula\Core\Queries, already available from the Tours CMS above.
	require_once VISTULA_CORE_DIR . 'src/PostTypes/destination.php';
	require_once VISTULA_CORE_DIR . 'src/PostTypes/destination-admin-columns.php';
	require_once VISTULA_CORE_DIR . 'src/Api/destinations.php'; // Overrides the Stage 2 stub — real implementation now.
	require_once VISTULA_CORE_DIR . 'src/Api/posts.php';
	require_once VISTULA_CORE_DIR . 'src/Content/blog-categories-seed.php';
	require_once VISTULA_CORE_DIR . 'src/Content/post-derived-fields.php';
	// Vistula\Core\Queries (src/Queries.php) is class-based and loaded
	// lazily by the autoloader above — no require_once needed here.

	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		require_once VISTULA_CORE_DIR . 'cli/seed-destinations.php';
		require_once VISTULA_CORE_DIR . 'cli/seed-tours.php';
		require_once VISTULA_CORE_DIR . 'cli/seed-posts.php';
		require_once VISTULA_CORE_DIR . 'cli/seed-all.php'; // Calls the three functions above directly — must load last.
	}

	// Nothing else is registered yet: no FAQ/Testimonial CPTs, no settings
	// page fields for them. Those hook in here in later stages without
	// requiring changes to this bootstrap file.
}
add_action( 'plugins_loaded', 'vistula_core_init' );

/**
 * Activation: PHP-version guard, then register the CPT/taxonomies that
 * exist so far and flush rewrite rules exactly once. Rewrite rules are
 * NEVER flushed on a normal request (docs/implementation-roadmap.md
 * Stage 5 §8 — "do not blindly flush on every request") — only here, on
 * activation, which is the one moment WordPress itself recommends it.
 * The registration functions are required directly (not assumed already
 * loaded via vistula_core_init()) because activation can run before this
 * request's normal plugins_loaded → init cascade completes.
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

	require_once VISTULA_CORE_DIR . 'src/PostTypes/tour.php';
	require_once VISTULA_CORE_DIR . 'src/Taxonomies/tour-taxonomies.php';
	require_once VISTULA_CORE_DIR . 'src/PostTypes/destination.php';
	vistula_core_register_tour_post_type();
	vistula_core_register_tour_taxonomies();
	vistula_core_register_destination_post_type();
	flush_rewrite_rules();
}
register_activation_hook( VISTULA_CORE_FILE, 'vistula_core_activate' );

/**
 * Deactivation: flush rewrite rules once more so the CPT's routes don't
 * linger as dead rewrite entries after the plugin (and with it, the
 * `tour` post type) is switched off.
 */
function vistula_core_deactivate(): void {
	flush_rewrite_rules();
}
register_deactivation_hook( VISTULA_CORE_FILE, 'vistula_core_deactivate' );
