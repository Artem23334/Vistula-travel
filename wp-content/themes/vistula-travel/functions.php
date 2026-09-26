<?php
/**
 * Vistula Travel theme bootstrap.
 *
 * Deliberately thin — see docs/project-architecture.md §3.3, rule 1:
 * "Theme = presentation; plugin = data model." Nothing that survives a
 * theme change (CPTs, settings, business data) belongs in this theme.
 *
 * @package Vistula_Travel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

define( 'VISTULA_TRAVEL_VERSION', '0.1.0' );
define( 'VISTULA_TRAVEL_DIR', get_template_directory() );
define( 'VISTULA_TRAVEL_URI', get_template_directory_uri() );

/**
 * Everything the theme actually does lives in /inc.
 * Add new includes here as later stages add them (e.g. inc/template-tags.php).
 */
require VISTULA_TRAVEL_DIR . '/inc/plugin-dependency.php';
require VISTULA_TRAVEL_DIR . '/inc/setup.php';
require VISTULA_TRAVEL_DIR . '/inc/enqueue.php';
