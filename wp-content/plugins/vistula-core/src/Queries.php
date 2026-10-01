<?php
/**
 * Tour query builder.
 *
 * docs/project-architecture.md and docs/technical-decisions.md (TD-14)
 * informally call this `Vistula\Queries::tours()`. Implemented here as
 * `Vistula\Core\Queries` to match the autoloader namespace Stage 2
 * actually established (`Vistula\Core\` → src/, in vistula-core.php) —
 * a small, deliberate reconciliation between an early doc mention and
 * the real convention, noted in docs/technical-decisions.md.
 *
 * Every method returns an array of tour IDs (not `WP_Post` objects, and
 * never ACF-resolved arrays) — callers pass each ID to vistula_tour()
 * themselves. Keeps this class about *which* tours, not their shape.
 *
 * @package Vistula_Core
 */

namespace Vistula\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Centralizes every WP_Query used to list tours, so no template builds
 * its own `post_type => 'tour'` query (docs/project-architecture.md §9.1
 * — "no `posts_per_page = -1`" and the general data-access-layer rule).
 */
class Queries {

	/**
	 * General-purpose tour listing, with optional filters. Mirrors the
	 * GET-form-driven filtering architecture (docs/project-architecture.md
	 * §9.1) — this stage only builds the query capability; the actual
	 * filter UI is explicitly out of scope (Stage 5 brief §12).
	 *
	 * @param array{
	 *     category?: string,
	 *     type?: string,
	 *     destination_id?: int,
	 *     popular_only?: bool,
	 *     posts_per_page?: int,
	 *     paged?: int,
	 *     orderby?: string,
	 *     order?: string,
	 * } $args Filter arguments. All optional.
	 * @return int[] Tour post IDs.
	 */
	public static function tours( array $args = array() ): array {
		$query_args = array(
			'post_type'      => 'tour',
			'post_status'    => 'publish',
			'posts_per_page' => $args['posts_per_page'] ?? 12, // Never -1 — see the architecture note above.
			'paged'          => $args['paged'] ?? 1,
			'orderby'        => $args['orderby'] ?? 'menu_order title',
			'order'          => $args['order'] ?? 'ASC',
			'fields'         => 'ids',
			'no_found_rows'  => empty( $args['paged'] ) && 1 === ( $args['posts_per_page'] ?? 12 ), // cheap when pagination isn't needed
		);

		$tax_query = array();
		if ( ! empty( $args['category'] ) ) {
			$tax_query[] = array(
				'taxonomy' => 'tour_category',
				'field'    => 'slug',
				'terms'    => $args['category'],
			);
		}
		if ( ! empty( $args['type'] ) ) {
			$tax_query[] = array(
				'taxonomy' => 'tour_type',
				'field'    => 'slug',
				'terms'    => $args['type'],
			);
		}
		if ( ! empty( $tax_query ) ) {
			$query_args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}

		if ( ! empty( $args['popular_only'] ) ) {
			$query_args['meta_query'][] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'key'   => 'tour_is_popular',
				'value' => '1',
			);
		}

		if ( ! empty( $args['destination_id'] ) ) {
			return self::tours_for_destination( (int) $args['destination_id'], $query_args );
		}

		$query = new \WP_Query( $query_args );
		return array_map( 'intval', $query->posts );
	}

	/**
	 * "All tours for destination X" — the reverse lookup for a
	 * relationship stored on the tour side only (TD-14). A `meta_query`
	 * against the serialized relationship value; fine at this project's
	 * scale (data-model.md §16's own assessment — hundreds of tours, not
	 * tens of thousands). If this ever needs to change (performance, or a
	 * multilingual plugin's ID-mapping making it fragile), this is the
	 * one place to change it, per TD-14.
	 *
	 * @param int   $destination_id Destination post ID.
	 * @param array $extra_args     Additional WP_Query args to merge in (used internally by tours()).
	 * @return int[] Tour post IDs.
	 */
	public static function tours_for_destination( int $destination_id, array $extra_args = array() ): array {
		$defaults = array(
			'post_type'      => 'tour',
			'post_status'    => 'publish',
			'posts_per_page' => 12,
			'orderby'        => 'menu_order title',
			'order'          => 'ASC',
			'fields'         => 'ids',
		);

		$query_args = array_merge( $defaults, $extra_args );
		unset( $query_args['destination_id'] ); // Not a real WP_Query arg — already consumed by the caller.

		$query_args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			array(
				'key'     => 'tour_destinations',
				'value'   => '"' . $destination_id . '"',
				'compare' => 'LIKE', // ACF relationship fields serialize their ID array; this matches the quoted ID within it.
			),
		);

		$query = new \WP_Query( $query_args );
		return array_map( 'intval', $query->posts );
	}

	/**
	 * General-purpose destination listing.
	 *
	 * @param array{posts_per_page?: int, paged?: int} $args Filter arguments.
	 * @return int[] Destination post IDs.
	 */
	public static function destinations( array $args = array() ): array {
		$query_args = array(
			'post_type'      => 'destination',
			'post_status'    => 'publish',
			'posts_per_page' => $args['posts_per_page'] ?? 12,
			'paged'          => $args['paged'] ?? 1,
			'orderby'        => $args['orderby'] ?? 'menu_order title',
			'order'          => $args['order'] ?? 'ASC',
			'fields'         => 'ids',
		);

		$query = new \WP_Query( $query_args );
		return array_map( 'intval', $query->posts );
	}

	/**
	 * General-purpose Travel Guide listing, with an optional category filter.
	 *
	 * @param array{category?: string, posts_per_page?: int, paged?: int} $args Filter arguments.
	 * @return int[] Post IDs.
	 */
	public static function posts( array $args = array() ): array {
		$query_args = array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => $args['posts_per_page'] ?? 12, // Never -1.
			'paged'          => $args['paged'] ?? 1,
			'orderby'        => $args['orderby'] ?? 'date',
			'order'          => $args['order'] ?? 'DESC',
			'fields'         => 'ids',
		);

		if ( ! empty( $args['category'] ) ) {
			$query_args['category_name'] = $args['category']; // Core category, slug — TD-05.
		}

		$query = new \WP_Query( $query_args );
		return array_map( 'intval', $query->posts );
	}

	/**
	 * "All Travel Guide posts about tour X" — reverse lookup for
	 * `post_tours` (stored on the post, TD-14).
	 *
	 * @param int   $tour_id    Tour post ID.
	 * @param array $extra_args Additional WP_Query args to merge in.
	 * @return int[] Post IDs.
	 */
	public static function posts_for_tour( int $tour_id, array $extra_args = array() ): array {
		$defaults = array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 6,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'fields'         => 'ids',
		);

		$query_args               = array_merge( $defaults, $extra_args );
		$query_args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			array(
				'key'     => 'post_tours',
				'value'   => '"' . $tour_id . '"',
				'compare' => 'LIKE',
			),
		);

		$query = new \WP_Query( $query_args );
		return array_map( 'intval', $query->posts );
	}

	/**
	 * "All Travel Guide posts about destination X" — reverse lookup for
	 * `post_destinations` (stored on the post, TD-14). This is also what
	 * a destination's own `post_ids` (vistula_destination()) resolves via.
	 *
	 * @param int   $destination_id Destination post ID.
	 * @param array $extra_args     Additional WP_Query args to merge in.
	 * @return int[] Post IDs.
	 */
	public static function posts_for_destination( int $destination_id, array $extra_args = array() ): array {
		$defaults = array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 6,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'fields'         => 'ids',
		);

		$query_args               = array_merge( $defaults, $extra_args );
		$query_args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			array(
				'key'     => 'post_destinations',
				'value'   => '"' . $destination_id . '"',
				'compare' => 'LIKE',
			),
		);

		$query = new \WP_Query( $query_args );
		return array_map( 'intval', $query->posts );
	}

	/**
	 * "Tours related to tour X" — other published tours sharing at least
	 * one destination, falling back to sharing a category if none share
	 * a destination. Excludes $tour_id itself.
	 *
	 * @param int $tour_id Tour post ID to find related tours for.
	 * @param int $limit   Maximum number of related tours to return.
	 * @return int[] Tour post IDs.
	 */
	public static function related_tours( int $tour_id, int $limit = 3 ): array {
		$destination_ids = array_map( 'intval', (array) vistula_core_acf_field( 'tour_destinations', $tour_id, array() ) );

		$candidates = array();
		foreach ( $destination_ids as $destination_id ) {
			foreach ( self::tours_for_destination( $destination_id, array( 'posts_per_page' => $limit + 1 ) ) as $id ) {
				if ( $id !== $tour_id ) {
					$candidates[ $id ] = true;
				}
			}
			if ( count( $candidates ) >= $limit ) {
				break;
			}
		}

		if ( count( $candidates ) < $limit ) {
			$categories = wp_get_post_terms( $tour_id, 'tour_category', array( 'fields' => 'slugs' ) );
			if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
				foreach ( self::tours( array( 'category' => $categories[0], 'posts_per_page' => $limit + 1 ) ) as $id ) {
					if ( $id !== $tour_id ) {
						$candidates[ $id ] = true;
					}
				}
			}
		}

		return array_slice( array_keys( $candidates ), 0, $limit );
	}
}
