<?php
/**
 * Data-access contract: page-roles registry.
 *
 * The *set of roles* below is not invented — it is the registry defined in
 * docs/project-architecture.md §4.4. What's deferred to Stage 3 (Global
 * Settings) is mapping each role to a real Page ID; until then this returns
 * $default so no template hard-codes a slug like "/contact/" even at this
 * early stage (the exact anti-pattern §4.4 exists to prevent).
 *
 * @package Vistula_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The known page roles, per docs/project-architecture.md §4.4. Stage 3
 * populates the actual role => Page ID mapping; this constant only fixes
 * the vocabulary so later stages (and this file) agree on role names.
 */
if ( ! defined( 'VISTULA_PAGE_ROLES' ) ) {
	define(
		'VISTULA_PAGE_ROLES',
		array(
			'home',
			'tours',
			'destinations',
			'travel_guide',
			'about',
			'faq',
			'contact',
			'booking',
			'privacy',
			'cookies',
			'terms',
		)
	);
}

if ( ! function_exists( 'vistula_is_valid_page_role' ) ) {
	/**
	 * Whether $role is a known page role. This is the "mechanism" part of
	 * the registry — it lets callers (and vistula_page_url() itself) fail
	 * loudly on a typo'd role instead of silently returning a dead link.
	 *
	 * @param string $role Role to check.
	 * @return bool
	 */
	function vistula_is_valid_page_role( string $role ): bool {
		return in_array( $role, VISTULA_PAGE_ROLES, true );
	}
}

if ( ! function_exists( 'vistula_page_url' ) ) {
	/**
	 * Resolve a page role (e.g. 'contact') to its URL.
	 *
	 * @param string $role    One of VISTULA_PAGE_ROLES.
	 * @param string $default Fallback URL until Stage 3 implements real resolution.
	 * @return string
	 */
	function vistula_page_url( string $role, string $default = '#' ): string {
		if ( ! vistula_is_valid_page_role( $role ) ) {
			_doing_it_wrong(
				__FUNCTION__,
				sprintf(
					/* translators: %s: the unrecognised page-role key passed in. */
					esc_html__( '"%s" is not a registered Vistula page role. See VISTULA_PAGE_ROLES.', 'vistula-core' ),
					esc_html( $role )
				),
				'0.1.0'
			);
			return $default;
		}

		// TODO(Stage 3): resolve $role to a real Page ID via the mapping
		// built on the vistula-core settings page, then return get_permalink().
		return $default;
	}
}
