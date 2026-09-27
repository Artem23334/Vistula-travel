<?php
/**
 * Data-access contract: global settings — real implementation (TD-17).
 *
 * Reads from the `vistula_settings` option registered and sanitized in
 * src/Settings/schema.php + src/Settings/admin-page.php. Templates must
 * never call get_option() directly — this is the only sanctioned path,
 * per docs/project-architecture.md §6.1.
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
	 * Missing keys fall back to the schema's own default (not just
	 * $default) so that a field added to the schema after a site's
	 * `vistula_settings` option was first saved still behaves correctly —
	 * $default is only used for a key that isn't in the schema at all.
	 *
	 * @param string $key     Setting key — see docs/data-model.md §13 for the full catalogue.
	 * @param mixed  $default Value to return if $key is not a known setting at all.
	 * @return mixed
	 */
	function vistula_setting( string $key, mixed $default = null ): mixed {
		static $settings = null;

		if ( null === $settings ) {
			$settings = wp_parse_args( get_option( 'vistula_settings', array() ), vistula_core_settings_defaults() );
		}

		return array_key_exists( $key, $settings ) ? $settings[ $key ] : $default;
	}
}

if ( ! function_exists( 'vistula_formatted_address' ) ) {
	/**
	 * Compose the four structured address fields (data-model.md §13 — kept
	 * structured, not a single field, so JSON-LD's PostalAddress can use
	 * them individually per architecture §6.2) into one display line.
	 *
	 * @return string Empty string if no address fields are set at all.
	 */
	function vistula_formatted_address(): string {
		$parts = array_filter(
			array(
				vistula_setting( 'address_street', '' ),
				vistula_setting( 'address_postcode', '' ) . ' ' . vistula_setting( 'address_city', '' ),
				vistula_setting( 'address_country', '' ),
			),
			static fn( string $part ): bool => '' !== trim( $part )
		);

		return implode( ', ', array_map( 'trim', $parts ) );
	}
}
