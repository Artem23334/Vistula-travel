<?php
/**
 * Asset enqueue foundation.
 *
 * Stage 4 (Design System) adds the front-end CSS layer. Load order matters
 * — each file after tokens.css depends on the custom properties tokens.css
 * defines, so wp_enqueue_style()'s $deps chain enforces the same order
 * WordPress would otherwise apply arbitrarily.
 *
 * No JS is enqueued yet — the Hero's scene-switching logic is Stage 14
 * (JavaScript/UX); this stage only ships the CSS it will attach to.
 *
 * @package Vistula_Travel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue the theme's stylesheet (style.css — required by WordPress) and
 * the Stage 4 design-system CSS layer, in dependency order.
 */
function vistula_travel_enqueue_assets(): void {
	wp_enqueue_style(
		'vistula-travel-style',
		get_stylesheet_uri(),
		array(),
		VISTULA_TRAVEL_VERSION
	);

	wp_enqueue_style(
		'vistula-travel-tokens',
		VISTULA_TRAVEL_URI . '/assets/css/tokens.css',
		array(),
		VISTULA_TRAVEL_VERSION
	);

	wp_enqueue_style(
		'vistula-travel-base',
		VISTULA_TRAVEL_URI . '/assets/css/base.css',
		array( 'vistula-travel-tokens' ),
		VISTULA_TRAVEL_VERSION
	);

	wp_enqueue_style(
		'vistula-travel-layout',
		VISTULA_TRAVEL_URI . '/assets/css/layout.css',
		array( 'vistula-travel-base' ),
		VISTULA_TRAVEL_VERSION
	);

	wp_enqueue_style(
		'vistula-travel-components',
		VISTULA_TRAVEL_URI . '/assets/css/components.css',
		array( 'vistula-travel-layout' ),
		VISTULA_TRAVEL_VERSION
	);

	wp_enqueue_style(
		'vistula-travel-header-footer',
		VISTULA_TRAVEL_URI . '/assets/css/header-footer.css',
		array( 'vistula-travel-components' ),
		VISTULA_TRAVEL_VERSION
	);

	wp_enqueue_style(
		'vistula-travel-hero',
		VISTULA_TRAVEL_URI . '/assets/css/hero.css',
		array( 'vistula-travel-components' ),
		VISTULA_TRAVEL_VERSION
	);

	wp_enqueue_style(
		'vistula-travel-animations',
		VISTULA_TRAVEL_URI . '/assets/css/animations.css',
		array( 'vistula-travel-components' ),
		VISTULA_TRAVEL_VERSION
	);

	// No additional JS files exist yet. When Stage 14 adds assets/js/*.js
	// (Hero scene-switching, mobile-nav toggle behaviour, filter AJAX,
	// and the IntersectionObserver that adds `js-ready`/`is-visible` for
	// assets/css/animations.css's .u-reveal), enqueue it here with
	// wp_enqueue_script() — never inline <script>.
}
add_action( 'wp_enqueue_scripts', 'vistula_travel_enqueue_assets' );
