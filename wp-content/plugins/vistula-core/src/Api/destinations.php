<?php
/**
 * Data-access contract: destinations — real implementation (this CMS/Data block).
 *
 * Mirrors src/Api/tours.php's vistula_tour() exactly — same shape
 * philosophy, same vistula_core_acf_field() defensive wrapper.
 *
 * @package Vistula_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'vistula_destination' ) ) {
	/**
	 * Fetch a single destination's fully resolved data.
	 *
	 * @param int $post_id Destination post ID.
	 * @return array|null Null if $post_id is not a published `destination`.
	 */
	function vistula_destination( int $post_id ): ?array {
		$post = get_post( $post_id );
		if ( ! $post || 'destination' !== $post->post_type ) {
			return null;
		}

		$flatten_list = static function ( array $rows ): array {
			return array_values(
				array_filter(
					array_map( static fn( array $row ): string => (string) ( $row['text'] ?? '' ), $rows )
				)
			);
		};

		// --- Things to do (title/description/image repeater) ---
		$things_rows = (array) vistula_core_acf_field( 'destination_things_to_do', $post_id, array() );
		$things_to_do = array();
		foreach ( $things_rows as $row ) {
			$things_to_do[] = array(
				'title'       => $row['title'] ?? '',
				'description' => $row['description'] ?? '',
				'image_id'    => ! empty( $row['image'] ) ? (int) $row['image'] : null,
			);
		}

		$type_key    = (string) vistula_core_acf_field( 'destination_type', $post_id, '' );
		$type_labels = function_exists( 'vistula_core_enum_destination_type' ) ? vistula_core_enum_destination_type() : array();

		// --- Derived relationships (data-model §5.4: tours and posts are
		// NEVER stored here — always derived by query) ---
		$tour_ids = class_exists( '\Vistula\Core\Queries' )
			? \Vistula\Core\Queries::tours_for_destination( $post_id )
			: array();
		$post_ids = class_exists( '\Vistula\Core\Queries' )
			? \Vistula\Core\Queries::posts_for_destination( $post_id )
			: array();

		return array(
			'id'      => $post_id,
			'title'   => get_the_title( $post_id ),
			'slug'    => $post->post_name,
			'url'     => get_permalink( $post_id ),
			'excerpt' => get_the_excerpt( $post_id ),

			'type' => array(
				'key'   => '' !== $type_key ? $type_key : null,
				'label' => $type_labels[ $type_key ] ?? null,
			),

			'map' => array(
				'lat' => vistula_core_acf_field( 'destination_lat', $post_id ),
				'lng' => vistula_core_acf_field( 'destination_lng', $post_id ),
			),

			'highlights'    => $flatten_list( (array) vistula_core_acf_field( 'destination_highlights', $post_id, array() ) ),
			'travel_tips'   => $flatten_list( (array) vistula_core_acf_field( 'destination_travel_tips', $post_id, array() ) ),
			'things_to_do'  => $things_to_do,

			'images' => array(
				'featured_id' => get_post_thumbnail_id( $post_id ) ?: null,
				'hero_id'     => vistula_core_acf_field( 'destination_hero_image', $post_id ),
				'gallery_ids' => array_map( 'intval', (array) vistula_core_acf_field( 'destination_gallery', $post_id, array() ) ),
			),

			'faq_ids' => array_map( 'intval', (array) vistula_core_acf_field( 'destination_faqs', $post_id, array() ) ),

			// Editorial override, if set; otherwise the derived tour_ids
			// below is what a template should actually display — see
			// data-model.md §5.3's "Default is the derived query" note.
			'featured_tour_ids_override' => array_map( 'intval', (array) vistula_core_acf_field( 'destination_featured_tours', $post_id, array() ) ),

			'tour_ids' => $tour_ids, // Derived from every tour's tour_destinations — data-model §5.4.
			'post_ids' => $post_ids, // Derived from every post's post_destinations — data-model §5.4.
		);
	}
}
