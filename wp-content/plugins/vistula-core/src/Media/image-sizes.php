<?php
/**
 * Image derivative sizes (docs/project-architecture.md §7.2, TD-23).
 *
 * Registered now (Stage 5) so any image uploaded while building/seeding
 * Tours already has correct derivatives by the time Stage 6/7 need them
 * — regenerating thumbnails retroactively is exactly the kind of avoidable
 * rework the roadmap's staged approach exists to prevent. This file adds
 * no front-end markup, CSS, or `<picture>` logic — those are Stage 6/7.
 *
 * WebP output and focal-point cropping (also TD-23) remain TO EVALUATE,
 * per the architecture doc's own note, and are not implemented here.
 *
 * @package Vistula_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds every derivative width for each ratio in architecture §7.2's
 * table. Multiple widths per ratio are required for a real `srcset` —
 * see that section's "why several widths per ratio" note.
 */
function vistula_core_register_image_sizes(): void {
	// Tour / destination card — 4:3.
	add_image_size( 'vistula-card-480', 480, 360, true );
	add_image_size( 'vistula-card-800', 800, 600, true );
	add_image_size( 'vistula-card-1200', 1200, 900, true );

	// Tour / destination hero — 16:9.
	add_image_size( 'vistula-hero-640', 640, 360, true );
	add_image_size( 'vistula-hero-1024', 1024, 576, true );
	add_image_size( 'vistula-hero-1440', 1440, 810, true );
	add_image_size( 'vistula-hero-1920', 1920, 1080, true );

	// Square thumbnail / mobile hero / avatar — 1:1.
	add_image_size( 'vistula-square-320', 320, 320, true );
	add_image_size( 'vistula-square-640', 640, 640, true );
	add_image_size( 'vistula-square-1200', 1200, 1200, true );

	// Open Graph / social share — 1.91:1, single fixed size (not srcset material).
	add_image_size( 'vistula-og', 1200, 630, true );
}
add_action( 'after_setup_theme', 'vistula_core_register_image_sizes' );
// Registered on `after_setup_theme` (not `init`) so it runs before any
// theme code that might query available image sizes — matches where
// WordPress itself recommends add_image_size() be called.
