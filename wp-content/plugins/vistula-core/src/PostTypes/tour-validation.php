<?php
/**
 * Tour publish-time validation (data-model.md §2.6, §19).
 *
 * "A tour cannot be published without title, short description, featured
 * image, price, price type, duration, ≥1 destination, meeting point,
 * difficulty; the editor sees a clear list of what's missing. Drafts save
 * freely." ACF's own built-in `required` field setting can't express this
 * — it blocks drafts too — so this is a dedicated hook instead, checked
 * after ACF has saved (so get_field() sees the just-submitted values).
 *
 * This is also, concretely, the mechanism the roadmap's Stage 5 → Stage 8
 * sequencing depends on: a demo tour has no destination until Stage 8
 * registers the `destination` CPT, so it is correctly and automatically
 * kept in draft until then — not a special case, just this rule applied.
 *
 * @package Vistula_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Required-field gate name => human label, for the admin notice.
 *
 * @return array<string, string>
 */
function vistula_core_tour_required_fields(): array {
	return array(
		'post_title'         => __( 'Title', 'vistula-core' ),
		'post_excerpt'       => __( 'Short description', 'vistula-core' ),
		'_thumbnail_id'      => __( 'Featured image', 'vistula-core' ),
		'tour_price_from'    => __( 'Price (from)', 'vistula-core' ),
		'tour_price_type'    => __( 'Price type', 'vistula-core' ),
		'tour_duration_value' => __( 'Duration', 'vistula-core' ),
		'tour_duration_unit'  => __( 'Duration unit', 'vistula-core' ),
		'tour_destinations'   => __( 'Destinations (at least one)', 'vistula-core' ),
		'tour_meeting_point'  => __( 'Meeting point', 'vistula-core' ),
		'tour_difficulty'     => __( 'Difficulty', 'vistula-core' ),
	);
}

/**
 * Returns the human labels of every required field currently missing on
 * $post_id, or an empty array if the tour is publish-ready.
 *
 * @param int $post_id Tour post ID.
 * @return string[]
 */
function vistula_core_tour_missing_required_fields( int $post_id ): array {
	$missing = array();
	$post    = get_post( $post_id );

	foreach ( vistula_core_tour_required_fields() as $key => $label ) {
		$value = match ( $key ) {
			'post_title'    => $post->post_title ?? '',
			'post_excerpt'  => $post->post_excerpt ?? '',
			'_thumbnail_id' => get_post_thumbnail_id( $post_id ),
			'tour_destinations' => get_field( 'tour_destinations', $post_id ),
			default         => get_field( $key, $post_id ),
		};

		$is_empty = is_array( $value ) ? empty( $value ) : ( '' === $value || null === $value || 0 === $value );

		if ( $is_empty ) {
			$missing[] = $label;
		}
	}

	return $missing;
}

/**
 * After ACF saves a tour's fields, demote it back to draft if it's
 * missing required fields — regardless of whether this save is a first
 * publish attempt or an edit to an already-published tour.
 *
 * @param int $post_id Post ID ACF just saved fields for.
 */
function vistula_core_enforce_tour_publish_requirements( int $post_id ): void {
	if ( 'tour' !== get_post_type( $post_id ) ) {
		return;
	}

	$post = get_post( $post_id );
	if ( ! $post || 'publish' !== $post->post_status ) {
		return; // Drafts save freely — nothing to enforce.
	}

	$missing = vistula_core_tour_missing_required_fields( $post_id );
	if ( empty( $missing ) ) {
		return; // Publish-ready.
	}

	// Demote back to draft. Use wp_update_post with a static guard against
	// re-triggering this same hook via the resulting save_post/acf/save_post cascade.
	static $demoting = false;
	if ( $demoting ) {
		return;
	}
	$demoting = true;
	wp_update_post(
		array(
			'ID'          => $post_id,
			'post_status' => 'draft',
		)
	);
	$demoting = false;

	set_transient( 'vistula_core_tour_missing_fields_' . $post_id, $missing, MINUTE_IN_SECONDS );
	add_action( 'admin_notices', 'vistula_core_render_tour_missing_fields_notice' );
}
add_action( 'acf/save_post', 'vistula_core_enforce_tour_publish_requirements', 30 );

/**
 * Renders the "here's what's missing" admin notice after a demotion.
 */
function vistula_core_render_tour_missing_fields_notice(): void {
	global $post;
	if ( ! $post ) {
		return;
	}

	$missing = get_transient( 'vistula_core_tour_missing_fields_' . $post->ID );
	if ( empty( $missing ) ) {
		return;
	}
	delete_transient( 'vistula_core_tour_missing_fields_' . $post->ID );

	printf(
		'<div class="notice notice-warning"><p>%s</p><ul style="list-style:disc;margin-left:1.5em;">%s</ul></div>',
		esc_html__( 'This tour was kept as a draft because the following required fields are missing:', 'vistula-core' ),
		implode( '', array_map( static fn( string $label ) => '<li>' . esc_html( $label ) . '</li>', $missing ) )
	);
}
