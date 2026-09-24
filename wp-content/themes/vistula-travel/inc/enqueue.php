<?php
/**
 * Asset enqueue foundation.
 *
 * Minimal on purpose — see docs/implementation-roadmap.md Stage 4 (Design System)
 * and Stage 14 (JavaScript/UX). Only the required theme stylesheet is enqueued
 * here. Do not add component CSS or interaction JS to this file; add a new
 * inc/ file (or extend this one) when those stages actually begin.
 *
 * @package Vistula_Travel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue the theme's own stylesheet (style.css — required by WordPress).
 */
function vistula_travel_enqueue_assets(): void {
	wp_enqueue_style(
		'vistula-travel-style',
		get_stylesheet_uri(),
		array(),
		VISTULA_TRAVEL_VERSION
	);

	// No additional CSS/JS files exist yet. When Stage 4 adds
	// assets/css/*.css or Stage 14 adds assets/js/*.js, enqueue them here
	// with wp_enqueue_style() / wp_enqueue_script() — never inline <style>/<script>.
}
add_action( 'wp_enqueue_scripts', 'vistula_travel_enqueue_assets' );
