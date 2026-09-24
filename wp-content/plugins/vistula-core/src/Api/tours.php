<?php
/**
 * Data-access contract: tours.
 *
 * The `tour` CPT does not exist yet — it is registered in Stage 5 (Tours
 * CMS), per docs/implementation-roadmap.md. This stub exists only so the
 * theme's contract with the plugin is fixed from the start: templates call
 * vistula_tour(), never query post_type=tour directly. Returns null until
 * Stage 5, deliberately — there is no real or fake tour to return.
 *
 * @package Vistula_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'vistula_tour' ) ) {
	/**
	 * Fetch a single tour's resolved data (see the worked example in
	 * docs/data-model.md §2 once implemented).
	 *
	 * @param int $post_id Tour post ID.
	 * @return array|null Null until Stage 5 registers the `tour` CPT and its fields.
	 */
	function vistula_tour( int $post_id ): ?array {
		// TODO(Stage 5): resolve the full field set defined in
		// docs/data-model.md §2 for a `tour` post and return it as a
		// stable array shape templates can rely on.
		return null;
	}
}
