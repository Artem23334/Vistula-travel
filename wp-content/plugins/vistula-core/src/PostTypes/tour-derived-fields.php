<?php
/**
 * Derived (Class C, system-generated) Tour fields — data-model.md §2.3.
 *
 * `_price_sort`, `_duration_minutes` and `_duration_bucket` are never
 * admin-editable; they are recomputed here whenever ACF saves a tour's
 * fields, from the real editable fields (`tour_price_from`,
 * `tour_duration_value`, `tour_duration_unit`). Stored as plain,
 * underscore-prefixed post meta (hidden from the default Custom Fields
 * metabox, per WordPress convention) so `WP_Query`'s numeric meta_query
 * (data-model.md §17) has something efficient to sort/filter on.
 *
 * @package Vistula_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Recomputes `_price_sort`, `_duration_minutes` and `_duration_bucket`
 * for a tour after its ACF fields are saved.
 *
 * @param int $post_id Post ID ACF just saved fields for.
 */
function vistula_core_compute_tour_derived_fields( int $post_id ): void {
	if ( 'tour' !== get_post_type( $post_id ) ) {
		return;
	}

	// --- _price_sort ---
	$price_from = get_field( 'tour_price_from', $post_id );
	if ( '' !== $price_from && null !== $price_from ) {
		update_post_meta( $post_id, '_price_sort', (float) $price_from );
	} else {
		delete_post_meta( $post_id, '_price_sort' );
	}

	// --- _duration_minutes / _duration_bucket ---
	$duration_value = get_field( 'tour_duration_value', $post_id );
	$duration_unit  = get_field( 'tour_duration_unit', $post_id );

	if ( '' !== $duration_value && null !== $duration_value && in_array( $duration_unit, array( 'hours', 'days' ), true ) ) {
		$minutes = 'days' === $duration_unit
			? (int) round( (float) $duration_value * 1440 )
			: (int) round( (float) $duration_value * 60 );

		update_post_meta( $post_id, '_duration_minutes', $minutes );
		update_post_meta( $post_id, '_duration_bucket', vistula_core_compute_duration_bucket( $minutes ) );
	} else {
		delete_post_meta( $post_id, '_duration_minutes' );
		delete_post_meta( $post_id, '_duration_bucket' );
	}
}
add_action( 'acf/save_post', 'vistula_core_compute_tour_derived_fields', 20 );
