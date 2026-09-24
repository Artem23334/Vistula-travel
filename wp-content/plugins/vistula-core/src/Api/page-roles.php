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

if ( ! function_exists( 'vistula_page_url' ) ) {
	/**
	 * Resolve a page role (e.g. 'contact') to its URL.
	 *
	 * @param string $role    One of VISTULA_PAGE_ROLES.
	 * @param string $default Fallback URL until Stage 3 implements real resolution.
	 * @return string
	 */
	function vistula_page_url( string $role, string $default = '#' ): string {
		// TODO(Stage 3): resolve $role to a real Page ID via the registry
		// built on the vistula-core settings page, then return get_permalink().
		return $default;
	}
}
