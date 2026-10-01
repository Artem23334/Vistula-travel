<?php
/**
 * Data-access contract: Travel Guide blog posts (data-model.md §6).
 *
 * New this block — mirrors vistula_tour()/vistula_destination()'s shape
 * philosophy so templates have one consistent pattern across all three
 * content types, per docs/project-architecture.md §3.3 rule 2.
 *
 * @package Vistula_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'vistula_post' ) ) {
	/**
	 * Fetch a single Travel Guide post's resolved data.
	 *
	 * @param int $post_id Post ID (core `post` type).
	 * @return array|null Null if $post_id is not a `post`.
	 */
	function vistula_post( int $post_id ): ?array {
		$post = get_post( $post_id );
		if ( ! $post || 'post' !== $post->post_type ) {
			return null;
		}

		$categories = wp_get_post_categories( $post_id, array( 'fields' => 'slugs' ) );

		return array(
			'id'       => $post_id,
			'title'    => get_the_title( $post_id ),
			'slug'     => $post->post_name,
			'url'      => get_permalink( $post_id ),
			'excerpt'  => get_the_excerpt( $post_id ),
			'date'     => get_the_date( DATE_W3C, $post_id ),
			'author'   => get_the_author_meta( 'display_name', (int) $post->post_author ),

			'categories' => $categories,

			'reading_time_minutes' => (int) get_post_meta( $post_id, '_reading_time', true ) ?: null,

			'images' => array(
				'featured_id' => get_post_thumbnail_id( $post_id ) ?: null,
			),

			// Stored on the post (data-model §6.3), not derived — order is
			// editorial here, unlike Tour/Destination's reverse lookups.
			'tour_ids'        => array_map( 'intval', (array) vistula_core_acf_field( 'post_tours', $post_id, array() ) ),
			'destination_ids' => array_map( 'intval', (array) vistula_core_acf_field( 'post_destinations', $post_id, array() ) ),
		);
	}
}
