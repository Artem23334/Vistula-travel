<?php
/**
 * Data-access contract: global settings.
 *
 * Real implementation (the Settings-API-backed `vistula_settings` option,
 * per docs/project-architecture.md §6.1) is Stage 3 — Global Settings, in
 * docs/implementation-roadmap.md. Until then this returns $default so
 * templates that call it behave sensibly (render nothing extra) rather than
 * erroring, WITHOUT inventing any company data.
 *
 * Templates must never call get_option() directly — this function (or a
 * later Settings class it delegates to) is the only sanctioned path.
 *
 * @package Vistula_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'vistula_setting' ) ) {
	/**
	 * Read a single global setting (company info, CTA, social links, etc.).
	 *
	 * @param string $key     Setting key — see docs/data-model.md §13 for the
	 *                        full catalogue once it's implemented.
	 * @param mixed  $default Value to return until Stage 3 implements real storage.
	 * @return mixed
	 */
	function vistula_setting( string $key, mixed $default = null ): mixed {
		// TODO(Stage 3): read from the `vistula_settings` option via the
		// Settings API, per docs/project-architecture.md §6.1. Left
		// unimplemented on purpose — no invented company data belongs here.
		return $default;
	}
}
