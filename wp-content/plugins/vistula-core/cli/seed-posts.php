<?php
/**
 * `wp vistula seed-posts` — creates/updates 5 demo Travel Guide posts,
 * one per agreed category, demonstrating the post_tours/post_destinations
 * relationships (data-model.md §6.3).
 *
 * Idempotent by slug, same pattern as the other seed commands. Resolves
 * its `tour_slug`/`destination_slug` references the same way
 * seed-tours.php resolves destination_slug — gracefully does nothing if
 * the target doesn't exist yet (run seed-tours/seed-destinations first
 * for the relationships to actually populate).
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
 * Five demo posts, one per category from
 * src/Content/blog-categories-seed.php.
 *
 * @return array<int, array<string, mixed>>
 */
function vistula_core_demo_posts(): array {
	return array(
		array(
			'slug'              => 'five-must-see-spots-krakow-old-town',
			'title'             => "Five Must-See Spots in Kraków's Old Town",
			'excerpt'           => "A first-timer's shortlist for Kraków's historic centre.",
			'content'           => "Kraków's Old Town rewards slow exploration. Start at the Main Square, step into St. Mary's Basilica, and make time for Wawel Castle before the crowds arrive. The Wieliczka Salt Mine, just outside the city, is well worth a half-day on its own.",
			'category'          => 'Things To Do',
			'tour_slug'         => 'krakow-wieliczka',
			'destination_slug'  => 'krakow',
		),
		array(
			'slug'              => 'taste-of-poland-pierogi-and-beyond',
			'title'             => 'A Taste of Poland: Pierogi and Beyond',
			'excerpt'           => "An introduction to Poland's most-loved dishes.",
			'content'           => 'Pierogi are only the beginning. Across the country you will find bigos (hunter\'s stew), żurek (sour rye soup), and oscypek (smoked sheep cheese) from the mountain regions around Zakopane.',
			'category'          => 'Polish Food',
			'tour_slug'         => '',
			'destination_slug'  => '',
		),
		array(
			'slug'              => 'planning-your-first-trip-to-poland',
			'title'             => 'Planning Your First Trip to Poland',
			'excerpt'           => 'Practical notes before you go.',
			'content'           => 'Poland is compact enough to combine several cities in one trip. Trains connect the main centres well, and most historic centres are easily walkable once you arrive.',
			'category'          => 'Travel Tips',
			'tour_slug'         => '',
			'destination_slug'  => '',
		),
		array(
			'slug'              => 'polands-cultural-treasures',
			'title'             => "Poland's Cultural Treasures: Museums and Traditions",
			'excerpt'           => "A look at Warsaw's museums and living traditions.",
			'content'           => "Warsaw's museums trace a complex twentieth-century history alongside centuries-older traditions still visible in the rebuilt Old Town.",
			'category'          => 'Culture',
			'tour_slug'         => 'warsaw-experience',
			'destination_slug'  => 'warsaw',
		),
		array(
			'slug'              => 'why-poland-should-be-your-next-destination',
			'title'             => 'Why Poland Should Be Your Next Destination',
			'excerpt'           => 'An overview of what draws travellers to Poland.',
			'content'           => 'From Baltic coastline to mountain trails, medieval city centres to modern capitals, Poland offers a wide range of travel experiences within a single, well-connected country.',
			'category'          => 'Poland Travel',
			'tour_slug'         => '',
			'destination_slug'  => '',
		),
	);
}

/**
 * Creates or updates the five demo posts, idempotently by slug.
 */
function vistula_core_cli_seed_posts(): void {
	foreach ( vistula_core_demo_posts() as $post_data ) {
		$existing = get_page_by_path( $post_data['slug'], OBJECT, 'post' );

		$post_args = array(
			'post_type'    => 'post',
			'post_title'   => $post_data['title'],
			'post_name'    => $post_data['slug'],
			'post_excerpt' => $post_data['excerpt'],
			'post_content' => $post_data['content'],
			'post_status'  => 'publish',
		);

		if ( $existing ) {
			$post_args['ID'] = $existing->ID;
			$post_id         = wp_update_post( $post_args, true );
		} else {
			$post_id = wp_insert_post( $post_args, true );
		}

		if ( is_wp_error( $post_id ) ) {
			WP_CLI::warning( sprintf( 'Skipped "%s": %s', $post_data['title'], $post_id->get_error_message() ) );
			continue;
		}

		if ( term_exists( $post_data['category'], 'category' ) ) {
			wp_set_object_terms( $post_id, $post_data['category'], 'category' );
		}

		if ( ! empty( $post_data['tour_slug'] ) ) {
			$tour = get_page_by_path( $post_data['tour_slug'], OBJECT, 'tour' );
			if ( $tour ) {
				update_field( 'post_tours', array( $tour->ID ), $post_id );
			}
		}

		if ( ! empty( $post_data['destination_slug'] ) ) {
			$destination = get_page_by_path( $post_data['destination_slug'], OBJECT, 'destination' );
			if ( $destination ) {
				update_field( 'post_destinations', array( $destination->ID ), $post_id );
			}
		}

		// _reading_time is hooked on core `save_post`, which wp_insert_post()/
		// wp_update_post() above already triggered — nothing extra to compute here.

		WP_CLI::log( sprintf( '%s "%s" (#%d, published)', $existing ? 'Updated' : 'Created', $post_data['title'], $post_id ) );
	}

	WP_CLI::success( 'Demo Travel Guide posts seeded.' );
}

WP_CLI::add_command(
	'vistula seed-posts',
	'vistula_core_cli_seed_posts',
	array(
		'shortdesc' => 'Create/update 5 demo Travel Guide posts. Idempotent by slug.',
	)
);
