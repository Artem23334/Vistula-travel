<?php
/**
 * Data-access contract: destinations.
 *
 * The `destination` CPT does not exist yet — it is registered in Stage 8
 * (Destinations), per docs/implementation-roadmap.md. This stub exists so
 * the theme's contract with the plugin is fixed from the start: templates
 * call vistula_destination(), never query post_type=destination directly.
 * Mirrors src/Api/tours.php — see that file for the same rationale.
 *
 * @package Vistula_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'vistula_destination' ) ) {
	/**
	 * Fetch a single destination's resolved data (see the field spec in
	 * docs/data-model.md §5 once implemented).
	 *
	 * @param int $post_id Destination post ID.
	 * @return array|null Null until Stage 8 registers the `destination` CPT and its fields.
	 */
	function vistula_destination( int $post_id ): ?array {
		// TODO(Stage 8): resolve the full field set defined in
		// docs/data-model.md §5 for a `destination` post and return it as a
		// stable array shape templates can rely on.
		return null;
	}
}
