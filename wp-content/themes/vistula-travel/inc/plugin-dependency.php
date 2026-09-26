<?php
/**
 * Guards against the theme's documented dependency on vistula-core (TD-11:
 * "theme cannot function without the plugin; it must fail gracefully with
 * an admin notice").
 *
 * Without this file, an inactive vistula-core would make every template
 * call to vistula_setting()/vistula_page_url()/vistula_tour() a PHP fatal
 * error (undefined function) — exactly the failure mode TD-11 rules out.
 *
 * @package Vistula_Travel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * True once vistula-core's data-access layer has actually loaded.
 *
 * @return bool
 */
function vistula_travel_core_plugin_active(): bool {
	return function_exists( 'vistula_setting' );
}

/**
 * If the plugin isn't active, define minimal no-op fallbacks for the three
 * data-access functions so front-end templates degrade to "no data" instead
 * of fataling. This is a safety net only — it invents no business data,
 * every fallback simply returns the caller's own $default/null.
 */
function vistula_travel_maybe_define_fallback_data_access(): void {
	if ( vistula_travel_core_plugin_active() ) {
		return;
	}

	if ( ! function_exists( 'vistula_setting' ) ) {
		function vistula_setting( string $key, mixed $default = null ): mixed {
			unset( $key );
			return $default;
		}
	}
	if ( ! function_exists( 'vistula_page_url' ) ) {
		function vistula_page_url( string $role, string $default = '#' ): string {
			unset( $role );
			return $default;
		}
	}
	if ( ! function_exists( 'vistula_tour' ) ) {
		function vistula_tour( int $post_id ): ?array {
			unset( $post_id );
			return null;
		}
	}
}
// Runs after plugins load, before the theme renders anything that calls these.
add_action( 'after_setup_theme', 'vistula_travel_maybe_define_fallback_data_access', 0 );

/**
 * Warn the site owner in wp-admin if the required plugin isn't active,
 * rather than letting the front end silently render with no business data.
 */
function vistula_travel_core_plugin_admin_notice(): void {
	if ( vistula_travel_core_plugin_active() ) {
		return;
	}
	?>
	<div class="notice notice-error">
		<p>
			<?php
			esc_html_e(
				'The Vistula Travel theme requires the "Vistula Core" plugin to be active. Without it, the site will render with no business data (tours, settings, navigation targets).',
				'vistula-travel'
			);
			?>
		</p>
	</div>
	<?php
}
add_action( 'admin_notices', 'vistula_travel_core_plugin_admin_notice' );
