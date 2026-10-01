<?php
/**
 * `wp vistula seed-all` — runs seed-destinations, seed-tours, then
 * seed-posts in the one order that lets every relationship actually
 * populate (destinations must exist before tours link to them; tours
 * and destinations must exist before posts link to them).
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
 * Runs all three seed commands in dependency order.
 */
function vistula_core_cli_seed_all(): void {
	WP_CLI::log( '--- Destinations ---' );
	vistula_core_cli_seed_destinations();

	WP_CLI::log( '--- Tours ---' );
	vistula_core_cli_seed_tours();

	WP_CLI::log( '--- Travel Guide posts ---' );
	vistula_core_cli_seed_posts();

	WP_CLI::success( 'All demo content seeded.' );
}

WP_CLI::add_command(
	'vistula seed-all',
	'vistula_core_cli_seed_all',
	array(
		'shortdesc' => 'Seed destinations, tours, and Travel Guide posts in the correct order.',
	)
);
