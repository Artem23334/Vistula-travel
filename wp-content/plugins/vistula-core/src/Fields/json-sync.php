<?php
/**
 * Field-framework integration point (TD-12: Secure Custom Fields, via Local JSON).
 *
 * Does NOT create any field groups — that is Stage 5 (Tours CMS) and Stage 8
 * (Destinations). This file only makes sure that whenever a future stage
 * *does* define field groups through the admin UI, their JSON definitions
 * save into this plugin (git-tracked) instead of into wp-content/uploads
 * (SCF/ACF's default, which is NOT tracked — see .gitignore).
 *
 * Secure Custom Fields IS a real, required runtime dependency of this
 * plugin — declared in /composer.json (`wpackagist-plugin/secure-custom-fields`)
 * and surfaced with an admin notice if missing (src/Fields/dependency-notice.php,
 * same directory). What's defensive is only this *file's own* hooks: each
 * one is a no-op if SCF isn't active yet, so requiring this file never
 * itself causes a fatal error — the dependency is still real, just failed
 * gracefully, per the same principle TD-11 already applies to vistula-core
 * itself.
 *
 * @package Vistula_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Redirect ACF/SCF's "Local JSON" save path into this plugin's config/
 * directory, so field-group definitions are code, not database-only state
 * (TD-12 requirement F1).
 *
 * @param array $paths Existing save paths (ACF only uses the first entry).
 * @return array
 */
function vistula_core_acf_json_save_paths( array $paths ): array {
	$paths[0] = VISTULA_CORE_DIR . 'config/acf-json';
	return $paths;
}
add_filter( 'acf/json/save_paths', 'vistula_core_acf_json_save_paths' );
// Older ACF/SCF releases use the singular filter name; harmless to add both.
add_filter( 'acf/json/save_path', static fn() => VISTULA_CORE_DIR . 'config/acf-json' );

/**
 * Load field-group definitions from the same directory, so a fresh
 * environment (e.g. after `git clone`) sees the same field groups without
 * anything being re-created by hand.
 *
 * @param array $paths Existing load paths.
 * @return array
 */
function vistula_core_acf_json_load_paths( array $paths ): array {
	$paths[] = VISTULA_CORE_DIR . 'config/acf-json';
	return $paths;
}
add_filter( 'acf/json/load_paths', 'vistula_core_acf_json_load_paths' );
