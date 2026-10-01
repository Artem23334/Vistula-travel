<?php
/**
 * `wp vistula seed-destinations` — creates/updates the six initial
 * Destinations (Warsaw, Kraków, Gdańsk, Wrocław, Zakopane, Masuria).
 *
 * Idempotent by slug, same pattern as cli/seed-tours.php. Published
 * directly (unlike seed-tours.php) — a destination has no cross-CPT
 * dependency the way a tour depends on destinations existing, so there's
 * no reason to hold these back as drafts.
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
 * The six initial destinations. Slugs here are the join key `wp vistula
 * seed-tours` uses to link each demo tour to its destination(s).
 *
 * @return array<int, array<string, mixed>>
 */
function vistula_core_demo_destinations(): array {
	return array(
		array(
			'slug'       => 'warsaw',
			'title'      => 'Warsaw',
			'excerpt'    => "Poland's capital — a rebuilt Old Town alongside a modern skyline.",
			'content'    => 'Warsaw blends a meticulously rebuilt historic centre with a fast-moving modern capital. Explore the Old Town Market Place, the Royal Castle, and the contemporary skyline around the city centre.',
			'type'       => 'city',
			'highlights' => array( 'Old Town Market Place', 'Royal Castle', 'Warsaw Uprising Museum' ),
		),
		array(
			'slug'       => 'krakow',
			'title'      => 'Kraków',
			'excerpt'    => "Poland's former royal capital, with a UNESCO-listed Old Town and the Wieliczka Salt Mine nearby.",
			'content'    => "Kraków is Poland's cultural heart — a largely intact medieval Old Town, Wawel Castle, and the historic Jewish quarter of Kazimierz, with the Wieliczka Salt Mine just outside the city.",
			'type'       => 'city',
			'highlights' => array( 'Main Square (Rynek Główny)', 'Wawel Castle', 'Kazimierz district' ),
		),
		array(
			'slug'       => 'gdansk',
			'title'      => 'Gdańsk',
			'excerpt'    => 'A historic Baltic port city with a colourful waterfront and maritime heritage.',
			'content'    => "Gdańsk's Long Market and waterfront crane are among Poland's most photographed scenes, backed by centuries of Hanseatic trading history and a Baltic Sea coastline.",
			'type'       => 'city',
			'highlights' => array( 'Długi Targ (Long Market)', 'Neptune\'s Fountain', 'Gdańsk waterfront' ),
		),
		array(
			'slug'       => 'wroclaw',
			'title'      => 'Wrocław',
			'excerpt'    => 'A city of bridges and islands, known for its Market Square and bronze dwarf statues.',
			'content'    => "Wrocław sits on the Oder River across a dozen islands and more than a hundred bridges, centred on a grand Market Square and the cathedral quarter of Ostrów Tumski.",
			'type'       => 'city',
			'highlights' => array( 'Market Square', 'Ostrów Tumski', "Wrocław's dwarf statues" ),
		),
		array(
			'slug'       => 'zakopane',
			'title'      => 'Zakopane',
			'excerpt'    => "Poland's winter capital, gateway to the Tatra Mountains.",
			'content'    => 'Zakopane is the base for exploring the Tatra Mountains, known for its distinctive wooden architecture, the Krupówki promenade, and year-round access to the mountains.',
			'type'       => 'city',
			'highlights' => array( 'Krupówki Street', 'Gubałówka viewpoint', 'Tatra National Park gateway' ),
		),
		array(
			'slug'       => 'masuria',
			'title'      => 'Masuria',
			'excerpt'    => "Poland's lake district — over 2,000 lakes connected by rivers and canals.",
			'content'    => 'Masuria (the Masurian Lake District) is a region of forests and interconnected lakes in northeastern Poland, popular for sailing, boating, and nature escapes.',
			'type'       => 'region',
			'highlights' => array( 'Lake Śniardwy', 'Lake Mamry', 'Masurian forest trails' ),
		),
	);
}

/**
 * Creates or updates the six demo destinations, idempotently by slug.
 */
function vistula_core_cli_seed_destinations(): void {
	foreach ( vistula_core_demo_destinations() as $destination ) {
		$existing = get_page_by_path( $destination['slug'], OBJECT, 'destination' );

		$post_args = array(
			'post_type'    => 'destination',
			'post_title'   => $destination['title'],
			'post_name'    => $destination['slug'],
			'post_excerpt' => $destination['excerpt'],
			'post_content' => $destination['content'],
			'post_status'  => 'publish',
		);

		if ( $existing ) {
			$post_args['ID'] = $existing->ID;
			$post_id         = wp_update_post( $post_args, true );
		} else {
			$post_id = wp_insert_post( $post_args, true );
		}

		if ( is_wp_error( $post_id ) ) {
			WP_CLI::warning( sprintf( 'Skipped "%s": %s', $destination['title'], $post_id->get_error_message() ) );
			continue;
		}

		update_field( 'destination_type', $destination['type'], $post_id );
		update_field(
			'destination_highlights',
			array_map( static fn( string $t ): array => array( 'text' => $t ), $destination['highlights'] ),
			$post_id
		);

		WP_CLI::log( sprintf( '%s "%s" (#%d, published)', $existing ? 'Updated' : 'Created', $destination['title'], $post_id ) );
	}

	WP_CLI::success( 'Demo destinations seeded.' );
}

WP_CLI::add_command(
	'vistula seed-destinations',
	'vistula_core_cli_seed_destinations',
	array(
		'shortdesc' => 'Create/update the six initial Destinations. Idempotent by slug.',
	)
);
