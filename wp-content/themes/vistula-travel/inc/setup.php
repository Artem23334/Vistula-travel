<?php
/**
 * Theme setup: supports, nav menus, text domain.
 *
 * Nav menu locations mirror docs/project-architecture.md §4.6 exactly —
 * do not add or rename a location here without updating that table.
 *
 * @package Vistula_Travel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Core theme supports and nav menu registration.
 */
function vistula_travel_setup(): void {
	load_theme_textdomain( 'vistula-travel', VISTULA_TRAVEL_DIR . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support(
		'html5',
		array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' )
	);
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'custom-logo' ); // Kept as a WordPress-native fallback only;
	// the primary logo source is the vistula-core plugin setting — see
	// docs/project-architecture.md §6.1 ("the logo is a plugin setting").

	register_nav_menus(
		array(
			'primary'      => __( 'Primary Menu', 'vistula-travel' ),
			'footer_nav'   => __( 'Footer Navigation', 'vistula-travel' ),
			'footer_legal' => __( 'Footer Legal Links', 'vistula-travel' ),
		)
	);
}
add_action( 'after_setup_theme', 'vistula_travel_setup' );
