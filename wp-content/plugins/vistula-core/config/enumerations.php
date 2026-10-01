<?php
/**
 * Enumerations catalogue (data-model.md §20).
 *
 * Machine keys are the single source of truth here; ACF/SCF field-group
 * JSON (config/acf-json/) also bakes the same keys+labels into each
 * select/checkbox field's "choices" setting, since Local JSON doesn't
 * cleanly support loading choices from PHP without extra filter plumbing.
 * If a value here ever changes, update the matching JSON file's
 * `choices` too — noted as a known, accepted minor duplication (TD-10).
 *
 * @package Vistula_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * `tour_price_type` — data-model.md §2.3 Pricing.
 *
 * @return array<string, string>
 */
function vistula_core_enum_price_type(): array {
	return array(
		'per_person' => __( 'Per person', 'vistula-core' ),
		'per_group'  => __( 'Per group', 'vistula-core' ),
	);
}

/**
 * `tour_duration_unit` — data-model.md §2.3 Details.
 *
 * @return array<string, string>
 */
function vistula_core_enum_duration_unit(): array {
	return array(
		'hours' => __( 'Hours', 'vistula-core' ),
		'days'  => __( 'Days', 'vistula-core' ),
	);
}

/**
 * `_duration_bucket` (Class C, computed) — data-model.md §2.3 Details.
 * Thresholds are PROVISIONAL per data-model.md's own note.
 *
 * @return array<string, string>
 */
function vistula_core_enum_duration_bucket(): array {
	return array(
		'half_day'  => __( 'Half day', 'vistula-core' ),
		'full_day'  => __( 'Full day', 'vistula-core' ),
		'multi_day' => __( 'Multi-day', 'vistula-core' ),
	);
}

/**
 * `tour_difficulty` — data-model.md §2.3 Details.
 *
 * @return array<string, string>
 */
function vistula_core_enum_difficulty(): array {
	return array(
		'easy'        => __( 'Easy', 'vistula-core' ),
		'moderate'    => __( 'Moderate', 'vistula-core' ),
		'challenging' => __( 'Challenging', 'vistula-core' ),
	);
}

/**
 * `tour_guide_languages` — data-model.md §2.3 Details (OQ-11).
 * Site UI languages (TD-19) plus room for a guide-only language later.
 *
 * @return array<string, string>
 */
function vistula_core_enum_guide_languages(): array {
	return array(
		'pl' => __( 'Polish', 'vistula-core' ),
		'en' => __( 'English', 'vistula-core' ),
		'ru' => __( 'Russian', 'vistula-core' ),
		'uk' => __( 'Ukrainian', 'vistula-core' ),
	);
}

/**
 * `destination_type` — data-model.md §5.3.
 *
 * @return array<string, string>
 */
function vistula_core_enum_destination_type(): array {
	return array(
		'city'   => __( 'City', 'vistula-core' ),
		'region' => __( 'Region', 'vistula-core' ),
	);
}

/**
 * Duration-bucket thresholds (data-model.md §2.3, provisional):
 * ≤5h = half day; 1 day or >5h (but <2 days) = full day; ≥2 days = multi day.
 *
 * @param int $minutes Total duration in minutes.
 * @return string One of vistula_core_enum_duration_bucket()'s keys.
 */
function vistula_core_compute_duration_bucket( int $minutes ): string {
	if ( $minutes <= 5 * 60 ) {
		return 'half_day';
	}
	if ( $minutes < 2 * 1440 ) {
		return 'full_day';
	}
	return 'multi_day';
}
