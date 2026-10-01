<?php
/**
 * Data-access contract: tours — real implementation (Stage 5).
 *
 * Returns the exact shape worked through in docs/data-model.md §3.
 * Templates call vistula_tour(), never get_field()/get_post_meta()
 * directly and never query post_type=tour directly.
 *
 * @package Vistula_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Thin wrapper around ACF/SCF's get_field(), so this file (and therefore
 * vistula_tour()) degrades to $default rather than a fatal error if the
 * field-framework plugin (TD-12) is not active — the same "fail
 * gracefully" principle TD-11 already applies to vistula-core itself.
 *
 * @param string $key     ACF field name.
 * @param int    $post_id Post ID.
 * @param mixed  $default Fallback if ACF isn't active or the field is empty.
 * @return mixed
 */
function vistula_core_acf_field( string $key, int $post_id, mixed $default = null ): mixed {
	if ( ! function_exists( 'get_field' ) ) {
		return $default;
	}
	$value = get_field( $key, $post_id );
	return ( null === $value || '' === $value ) ? $default : $value;
}

if ( ! function_exists( 'vistula_tour' ) ) {
	/**
	 * Fetch a single tour's fully resolved data — see the worked example
	 * in docs/data-model.md §3 for the exact shape this returns.
	 *
	 * @param int $post_id Tour post ID.
	 * @return array|null Null if $post_id is not a published `tour`.
	 */
	function vistula_tour( int $post_id ): ?array {
		$post = get_post( $post_id );
		if ( ! $post || 'tour' !== $post->post_type ) {
			return null;
		}

		// --- Destinations (relationship; first = primary; data-model §2.4) ---
		$destination_ids = vistula_core_acf_field( 'tour_destinations', $post_id, array() );
		$destinations    = array();
		foreach ( (array) $destination_ids as $destination_id ) {
			$destination_id = (int) $destination_id;
			if ( 'destination' === get_post_type( $destination_id ) ) {
				$destinations[] = array(
					'id'    => $destination_id,
					'title' => get_the_title( $destination_id ),
					'url'   => get_permalink( $destination_id ),
				);
			}
		}

		// --- FAQs (relationship, ordered; curated per tour) ---
		$faq_ids = array_map( 'intval', (array) vistula_core_acf_field( 'tour_faqs', $post_id, array() ) );

		// --- Itinerary (repeater; data-model §9) ---
		$itinerary_rows = (array) vistula_core_acf_field( 'tour_itinerary', $post_id, array() );
		$itinerary      = array();
		foreach ( $itinerary_rows as $row ) {
			$itinerary[] = array(
				'day'         => isset( $row['day'] ) ? (int) $row['day'] : 1,
				'time'        => $row['time'] ?? null,
				'title'       => $row['title'] ?? '',
				'description' => $row['description'] ?? '',
			);
		}

		// --- Simple repeatable text lists (Highlights/Included/...) ---
		$flatten_list = static function ( array $rows ): array {
			return array_values(
				array_filter(
					array_map( static fn( array $row ): string => (string) ( $row['text'] ?? '' ), $rows )
				)
			);
		};

		$difficulty_key = (string) vistula_core_acf_field( 'tour_difficulty', $post_id, '' );
		$difficulty_labels = function_exists( 'vistula_core_enum_difficulty' ) ? vistula_core_enum_difficulty() : array();

		$duration_unit    = (string) vistula_core_acf_field( 'tour_duration_unit', $post_id, '' );
		$duration_minutes = get_post_meta( $post_id, '_duration_minutes', true );
		$duration_bucket  = get_post_meta( $post_id, '_duration_bucket', true );

		return array(
			'id'      => $post_id,
			'title'   => get_the_title( $post_id ),
			'slug'    => $post->post_name,
			'url'     => get_permalink( $post_id ),
			'excerpt' => get_the_excerpt( $post_id ),

			'price' => array(
				'from'     => vistula_core_acf_field( 'tour_price_from', $post_id ),
				'currency' => 'PLN', // TD-15: single site-wide currency, not a per-tour field.
				'type'     => vistula_core_acf_field( 'tour_price_type', $post_id ),
				'child'    => vistula_core_acf_field( 'tour_price_child', $post_id ),
				'private'  => vistula_core_acf_field( 'tour_price_private', $post_id ),
			),

			'duration' => array(
				'value'   => vistula_core_acf_field( 'tour_duration_value', $post_id ),
				'unit'    => '' !== $duration_unit ? $duration_unit : null,
				'minutes' => '' !== $duration_minutes ? (int) $duration_minutes : null,
				'bucket'  => $duration_bucket ?: null,
			),

			'destinations' => $destinations,

			'categories' => wp_get_post_terms( $post_id, 'tour_category', array( 'fields' => 'slugs' ) ),
			'types'      => wp_get_post_terms( $post_id, 'tour_type', array( 'fields' => 'slugs' ) ),

			'meeting_point' => array(
				'text' => vistula_core_acf_field( 'tour_meeting_point', $post_id, '' ),
				'lat'  => vistula_core_acf_field( 'tour_meeting_lat', $post_id ),
				'lng'  => vistula_core_acf_field( 'tour_meeting_lng', $post_id ),
			),

			'times' => array(
				'departure' => vistula_core_acf_field( 'tour_departure_time', $post_id ),
				'return'    => vistula_core_acf_field( 'tour_return_time', $post_id ),
			),

			'group' => array(
				'min' => vistula_core_acf_field( 'tour_group_min', $post_id ),
				'max' => vistula_core_acf_field( 'tour_group_max', $post_id ),
			),

			'difficulty' => array(
				'key'   => '' !== $difficulty_key ? $difficulty_key : null,
				'label' => $difficulty_labels[ $difficulty_key ] ?? null,
			),

			'age' => array(
				'min' => vistula_core_acf_field( 'tour_min_age', $post_id ),
				'max' => vistula_core_acf_field( 'tour_max_age', $post_id ),
			),

			'guide_languages' => (array) vistula_core_acf_field( 'tour_guide_languages', $post_id, array() ),

			'highlights'    => $flatten_list( (array) vistula_core_acf_field( 'tour_highlights', $post_id, array() ) ),
			'included'      => $flatten_list( (array) vistula_core_acf_field( 'tour_included', $post_id, array() ) ),
			'not_included'  => $flatten_list( (array) vistula_core_acf_field( 'tour_not_included', $post_id, array() ) ),
			'what_to_bring' => $flatten_list( (array) vistula_core_acf_field( 'tour_what_to_bring', $post_id, array() ) ),

			'important_info_html' => vistula_core_acf_field( 'tour_important_info', $post_id, '' ),

			'itinerary' => $itinerary,

			'images' => array(
				'featured_id'  => get_post_thumbnail_id( $post_id ) ?: null,
				'hero_id'      => vistula_core_acf_field( 'tour_hero_image', $post_id ),
				'gallery_ids'  => array_map( 'intval', (array) vistula_core_acf_field( 'tour_gallery', $post_id, array() ) ),
			),

			'faq_ids' => $faq_ids,

			'booking' => array(
				'enabled'      => (bool) vistula_core_acf_field( 'tour_booking_enabled', $post_id, false ),
				'provider_ref' => vistula_core_acf_field( 'tour_booking_provider_ref', $post_id ),
				'note'         => vistula_core_acf_field( 'tour_availability_note', $post_id ),
			),

			'is_popular' => (bool) vistula_core_acf_field( 'tour_is_popular', $post_id, false ),
		);
	}
}
