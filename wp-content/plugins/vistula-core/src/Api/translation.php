<?php
/**
 * Data-access contract: translation resolution (TD-13, architecture §8.3 M-4).
 *
 * Relationship fields (e.g. a tour's `tour_destinations`) store post IDs.
 * Once a multilingual plugin is chosen (Stage 11), an ID captured on one
 * language's post may need mapping to the equivalent post in the current
 * language. Rather than let that mapping logic leak into every query,
 * every ID read through the data-access layer should pass through this
 * single seam.
 *
 * Today it is a deliberate no-op: no multilingual plugin is installed, so
 * it returns $post_id unchanged. A Stage 11 integration hooks into the
 * `vistula_resolve_translation` filter — nothing else in the codebase
 * needs to change when that happens.
 *
 * @package Vistula_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'vistula_resolve_translation' ) ) {
	/**
	 * Resolve a post ID to its equivalent in the given (or current) language.
	 *
	 * @param int         $post_id Source post ID.
	 * @param string|null $lang    Target language code, or null for the current language.
	 * @return int The resolved post ID — identical to $post_id until Stage 11.
	 */
	function vistula_resolve_translation( int $post_id, ?string $lang = null ): int {
		/**
		 * Filters the translated post ID for a given source post + language.
		 *
		 * @param int         $post_id Source post ID (also the default/no-op return value).
		 * @param string|null $lang    Requested language code, or null for "current".
		 */
		return (int) apply_filters( 'vistula_resolve_translation', $post_id, $lang );
	}
}
