<?php
/**
 * Derived (Class C) Blog Post field: `_reading_time` (data-model.md §6.3).
 *
 * Unlike the Tour derived fields (which read ACF values and hook
 * `acf/save_post`), this reads `post_content` directly and hooks core's
 * own `save_post` — it has nothing to do with ACF, so it isn't coupled
 * to the field framework at all.
 *
 * @package Vistula_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Words-per-minute assumption for the estimate. Not configurable via UI
 * — this is a rough editorial estimate, not a precision metric.
 */
const VISTULA_READING_SPEED_WPM = 200;

/**
 * Recomputes `_reading_time` (whole minutes, minimum 1) from a Post's
 * content whenever it's saved.
 *
 * @param int     $post_id Post ID.
 * @param \WP_Post $post    Post object.
 */
function vistula_core_compute_post_reading_time( int $post_id, \WP_Post $post ): void {
	if ( 'post' !== $post->post_type ) {
		return;
	}
	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}

	// preg_match with \p{L} (not str_word_count, which is ASCII-only and
	// undercounts Polish/Russian/Ukrainian text) — M-1..M-15 compatibility
	// applies to this kind of incidental string handling too, not just
	// user-facing UI strings.
	$plain_text = wp_strip_all_tags( $post->post_content );
	$word_count = preg_match_all( '/\p{L}[\p{L}\p{Mn}\p{Pd}\'’]*/u', $plain_text );
	$minutes    = max( 1, (int) ceil( $word_count / VISTULA_READING_SPEED_WPM ) );

	update_post_meta( $post_id, '_reading_time', $minutes );
}
add_action( 'save_post', 'vistula_core_compute_post_reading_time', 20, 2 );
