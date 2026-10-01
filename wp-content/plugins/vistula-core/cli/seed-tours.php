<?php
/**
 * `wp vistula seed-tours` — creates/updates the six demo Tours (TD-43).
 *
 * Idempotent by slug: re-running updates the same six posts rather than
 * duplicating them. Each tour's `destination_slug` is resolved against
 * the `destination` CPT (seeded by `wp vistula seed-destinations`) — if
 * found, `tour_destinations` is set and the tour is checked against the
 * publish-time validation gate (tour-validation.php's own
 * vistula_core_tour_missing_required_fields()) and published if nothing
 * is missing. If the destination CPT or term isn't seeded yet, the tour
 * is left as a draft — this is the Stage 5 → Stage 8 sequencing from
 * docs/implementation-roadmap.md, now implemented end-to-end rather than
 * permanently stopping at "draft". Run `wp vistula seed-destinations`
 * first for all six to actually publish.
 *
 * Content here is plausible demo copy for a fictional company (per the
 * Spec's own premise) — no real commercial claims, reviews, or booking
 * availability, per the Stage 5 brief.
 *
 * @package Vistula_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

/**
 * The six demo tours. `category`/`type` are term *names* (matching the
 * seed terms in src/Taxonomies/tour-taxonomies.php); everything else
 * maps directly onto the ACF field groups in config/acf-json/.
 *
 * @return array<int, array<string, mixed>>
 */
function vistula_core_demo_tours(): array {
	return array(
		array(
			'slug'        => 'krakow-wieliczka',
			'destination_slug' => 'krakow',
			'title'       => 'Kraków & Wieliczka',
			'excerpt'     => 'A full day in Kraków\'s Old Town and the UNESCO-listed Wieliczka Salt Mine.',
			'category'    => 'History',
			'type'        => 'Group Tour',
			'price_from'  => 399,
			'duration_value' => 1,
			'duration_unit'  => 'days',
			'difficulty'  => 'easy',
			'group_min'   => 2,
			'group_max'   => 20,
			'meeting_point' => 'Main Square (Rynek Główny), by the Adam Mickiewicz statue, Kraków.',
			'highlights'  => array( 'Kraków Old Town and Main Square', 'Wawel Castle exterior and grounds', 'Wieliczka Salt Mine underground chambers' ),
			'included'    => array( 'Licensed guide', 'Salt Mine entry ticket', 'Transport between sites' ),
			'itinerary'   => array(
				array( 'day' => 1, 'time' => '09:00', 'title' => 'Old Town walking tour', 'description' => 'Main Square, St. Mary\'s Basilica, Wawel Hill.' ),
				array( 'day' => 1, 'time' => '13:00', 'title' => 'Wieliczka Salt Mine', 'description' => 'Guided descent through the historic mine chambers.' ),
			),
		),
		array(
			'slug'        => 'zakopane-tatra-mountains',
			'destination_slug' => 'zakopane',
			'title'       => 'Zakopane & Tatra Mountains',
			'excerpt'     => 'A day trip to the Tatra foothills and the mountain town of Zakopane.',
			'category'    => 'Mountains',
			'type'        => 'Group Tour',
			'price_from'  => 349,
			'duration_value' => 1,
			'duration_unit'  => 'days',
			'difficulty'  => 'moderate',
			'group_min'   => 2,
			'group_max'   => 16,
			'meeting_point' => 'Krupówki Street entrance, Zakopane.',
			'highlights'  => array( 'Gubałówka viewpoint', 'Krupówki Street and local crafts', 'Tatra National Park foothills' ),
			'included'    => array( 'Licensed guide', 'Transport' ),
			'itinerary'   => array(
				array( 'day' => 1, 'time' => '09:00', 'title' => 'Gubałówka funicular', 'description' => 'Panoramic views over the Tatra range.' ),
				array( 'day' => 1, 'time' => '12:00', 'title' => 'Krupówki Street', 'description' => 'Free time for local food and crafts.' ),
			),
		),
		array(
			'slug'        => 'gdansk-baltic-coast',
			'destination_slug' => 'gdansk',
			'title'       => 'Gdańsk & Baltic Coast',
			'excerpt'     => 'Explore the historic port city of Gdańsk and its Baltic waterfront.',
			'category'    => 'City Tours',
			'type'        => 'Group Tour',
			'price_from'  => 399,
			'duration_value' => 1,
			'duration_unit'  => 'days',
			'difficulty'  => 'easy',
			'group_min'   => 2,
			'group_max'   => 20,
			'meeting_point' => 'Long Market (Długi Targ), by the Neptune Fountain, Gdańsk.',
			'highlights'  => array( 'Długi Targ and Neptune\'s Fountain', 'Gdańsk waterfront and the Crane (Żuraw)', 'Baltic Sea coastline' ),
			'included'    => array( 'Licensed guide', 'Transport along the coast' ),
			'itinerary'   => array(
				array( 'day' => 1, 'time' => '09:00', 'title' => 'Old Town Gdańsk', 'description' => 'Długi Targ, St. Mary\'s Church, the historic waterfront.' ),
				array( 'day' => 1, 'time' => '14:00', 'title' => 'Baltic coastline', 'description' => 'Time by the sea.' ),
			),
		),
		array(
			'slug'        => 'warsaw-experience',
			'destination_slug' => 'warsaw',
			'title'       => 'Warsaw Experience',
			'excerpt'     => 'A day exploring Poland\'s capital, from the rebuilt Old Town to modern Warsaw.',
			'category'    => 'City Tours',
			'type'        => 'Group Tour',
			'price_from'  => 249,
			'duration_value' => 1,
			'duration_unit'  => 'days',
			'difficulty'  => 'easy',
			'group_min'   => 2,
			'group_max'   => 20,
			'meeting_point' => 'Old Town Market Place, Warsaw.',
			'highlights'  => array( 'Warsaw Old Town (rebuilt UNESCO site)', 'Royal Castle exterior', 'Modern city centre skyline' ),
			'included'    => array( 'Licensed guide' ),
			'itinerary'   => array(
				array( 'day' => 1, 'time' => '09:00', 'title' => 'Old Town walking tour', 'description' => 'Market Place, Royal Castle, Barbican.' ),
				array( 'day' => 1, 'time' => '13:00', 'title' => 'Modern Warsaw', 'description' => 'City centre and skyline viewpoint.' ),
			),
		),
		array(
			'slug'        => 'wroclaw',
			'destination_slug' => 'wroclaw',
			'title'       => 'Wrocław',
			'excerpt'     => 'A day discovering Wrocław\'s Market Square, bridges, and famous garden dwarves.',
			'category'    => 'City Tours',
			'type'        => 'Group Tour',
			'price_from'  => 299,
			'duration_value' => 1,
			'duration_unit'  => 'days',
			'difficulty'  => 'easy',
			'group_min'   => 2,
			'group_max'   => 20,
			'meeting_point' => 'Market Square (Rynek), by the Old Town Hall, Wrocław.',
			'highlights'  => array( 'Market Square and Old Town Hall', 'Ostrów Tumski (Cathedral Island)', 'Wrocław\'s bronze dwarf statues' ),
			'included'    => array( 'Licensed guide' ),
			'itinerary'   => array(
				array( 'day' => 1, 'time' => '09:00', 'title' => 'Market Square', 'description' => 'Old Town Hall and surrounding architecture.' ),
				array( 'day' => 1, 'time' => '11:30', 'title' => 'Ostrów Tumski', 'description' => 'Cathedral Island and the river islands.' ),
			),
		),
		array(
			'slug'        => 'masurian-lakes-nature-escape',
			'destination_slug' => 'masuria',
			'title'       => 'Masurian Lakes Nature Escape',
			'excerpt'     => 'A two-day escape into the lake district of Masuria.',
			'category'    => 'Nature',
			'type'        => 'Weekend Trip',
			'price_from'  => 899,
			'duration_value' => 2,
			'duration_unit'  => 'days',
			'difficulty'  => 'moderate',
			'group_min'   => 2,
			'group_max'   => 12,
			'meeting_point' => 'Giżycko town centre, by the harbour.',
			'highlights'  => array( 'Lake Śniardwy and Lake Mamry', 'Boat crossing on the Masurian lakes', 'Forest trails through the lake district' ),
			'included'    => array( 'Licensed guide', '1 night accommodation', 'Boat crossing' ),
			'itinerary'   => array(
				array( 'day' => 1, 'time' => '09:00', 'title' => 'Giżycko and the harbour', 'description' => 'Introduction to the lake district.' ),
				array( 'day' => 1, 'time' => '14:00', 'title' => 'Lake boat crossing', 'description' => 'Crossing Lake Niegocin.' ),
				array( 'day' => 2, 'time' => '10:00', 'title' => 'Forest trails', 'description' => 'Walking trails through the Masurian forests.' ),
			),
		),
	);
}

/**
 * Creates or updates the six demo tours, idempotently by slug.
 */
function vistula_core_cli_seed_tours(): void {
	foreach ( vistula_core_demo_tours() as $tour ) {
		$existing = get_page_by_path( $tour['slug'], OBJECT, 'tour' );

		$post_args = array(
			'post_type'    => 'tour',
			'post_title'   => $tour['title'],
			'post_name'    => $tour['slug'],
			'post_excerpt' => $tour['excerpt'],
			'post_content' => '',
			'post_status'  => 'draft', // Possibly published further down, once every required field is confirmed present.
		);

		if ( $existing ) {
			$post_args['ID'] = $existing->ID;
			$post_id         = wp_update_post( $post_args, true );
		} else {
			$post_id = wp_insert_post( $post_args, true );
		}

		if ( is_wp_error( $post_id ) ) {
			WP_CLI::warning( sprintf( 'Skipped "%s": %s', $tour['title'], $post_id->get_error_message() ) );
			continue;
		}

		// Taxonomies — resolved by term name, created in tour-taxonomies.php's seed step.
		if ( term_exists( $tour['category'], 'tour_category' ) ) {
			wp_set_object_terms( $post_id, $tour['category'], 'tour_category' );
		}
		if ( term_exists( $tour['type'], 'tour_type' ) ) {
			wp_set_object_terms( $post_id, $tour['type'], 'tour_type' );
		}

		// ACF fields.
		update_field( 'tour_price_from', $tour['price_from'], $post_id );
		update_field( 'tour_price_type', 'per_person', $post_id );
		update_field( 'tour_duration_value', $tour['duration_value'], $post_id );
		update_field( 'tour_duration_unit', $tour['duration_unit'], $post_id );
		update_field( 'tour_meeting_point', $tour['meeting_point'], $post_id );
		update_field( 'tour_difficulty', $tour['difficulty'], $post_id );
		update_field( 'tour_group_min', $tour['group_min'], $post_id );
		update_field( 'tour_group_max', $tour['group_max'], $post_id );
		update_field( 'tour_booking_enabled', 0, $post_id );

		update_field(
			'tour_highlights',
			array_map( static fn( string $t ): array => array( 'text' => $t ), $tour['highlights'] ),
			$post_id
		);
		update_field(
			'tour_included',
			array_map( static fn( string $t ): array => array( 'text' => $t ), $tour['included'] ),
			$post_id
		);
		update_field( 'tour_itinerary', $tour['itinerary'], $post_id );

		// Resolve the destination by slug (seeded by `wp vistula seed-destinations`).
		// If it doesn't exist yet, leave tour_destinations unset — the tour
		// simply stays a draft below, exactly as the publish-gate intends.
		$destination = get_page_by_path( $tour['destination_slug'], OBJECT, 'destination' );
		if ( $destination ) {
			update_field( 'tour_destinations', array( $destination->ID ), $post_id );
		}

		// Derived fields + publish-gate hooks only fire on `acf/save_post`
		// (triggered by the admin form), not on update_field() calls made
		// directly from CLI — so this script computes/enforces them itself.
		vistula_core_compute_tour_derived_fields( $post_id );

		$missing = vistula_core_tour_missing_required_fields( $post_id );
		if ( empty( $missing ) ) {
			wp_update_post( array( 'ID' => $post_id, 'post_status' => 'publish' ) );
			WP_CLI::log( sprintf( '%s "%s" (#%d, published)', $existing ? 'Updated' : 'Created', $tour['title'], $post_id ) );
		} else {
			WP_CLI::log(
				sprintf(
					'%s "%s" (#%d, draft — missing: %s)',
					$existing ? 'Updated' : 'Created',
					$tour['title'],
					$post_id,
					implode( ', ', $missing )
				)
			);
		}
	}

	WP_CLI::success( 'Demo tours seeded. Run `wp vistula seed-destinations` first if any remained drafts for a missing destination.' );
}

WP_CLI::add_command(
	'vistula seed-tours',
	'vistula_core_cli_seed_tours',
	array(
		'shortdesc' => 'Create/update the six demo Tours (TD-43). Idempotent by slug; leaves all as drafts.',
	)
);
