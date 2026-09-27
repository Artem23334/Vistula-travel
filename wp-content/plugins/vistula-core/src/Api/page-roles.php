<?php
/**
 * Data-access contract: page-roles registry — real resolution (Stage 3).
 *
 * The *set of roles* is the registry defined in docs/project-architecture.md
 * §4.4. The role => Page ID mapping is populated by
 * src/Settings/page-roles-bootstrap.php and stored under
 * `vistula_settings['page_roles']`. No template hard-codes a slug like
 * "/contact/" — every internal link goes through vistula_page_url().
 *
 * @package Vistula_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The known page roles, per docs/project-architecture.md §4.4. Not every
 * role has a Page yet: Stage 3 maps home/tours/destinations/travel_guide/
 * about/faq/contact; booking (Stage 12) and the legal roles (Stage 9)
 * remain unmapped (page ID 0) until their own stage creates them.
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

if ( ! function_exists( 'vistula_page_id' ) ) {
	/**
	 * Resolve a page role to its Page ID (0 if the role is unmapped, e.g.
	 * `booking` before Stage 12, or the role is unknown).
	 *
	 * @param string $role One of VISTULA_PAGE_ROLES.
	 * @return int
	 */
	function vistula_page_id( string $role ): int {
		if ( ! vistula_is_valid_page_role( $role ) ) {
			return 0;
		}

		$page_roles = vistula_setting( 'page_roles', array() );
		$page_id    = isset( $page_roles[ $role ] ) ? (int) $page_roles[ $role ] : 0;

		// A mapped ID whose Page was trashed/deleted outside the bootstrap's
		// self-healing window is treated as unmapped rather than linking to
		// a dead page.
		if ( $page_id > 0 && 'page' !== get_post_type( $page_id ) ) {
			return 0;
		}

		return $page_id;
	}
}

if ( ! function_exists( 'vistula_page_url' ) ) {
	/**
	 * Resolve a page role (e.g. 'contact') to its URL.
	 *
	 * @param string $role    One of VISTULA_PAGE_ROLES.
	 * @param string $default Fallback URL if the role is unknown or not yet mapped to a Page.
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

		$page_id = vistula_page_id( $role );
		if ( 0 === $page_id ) {
			// Not yet mapped (e.g. 'booking' before Stage 12) — not an error.
			return $default;
		}

		$permalink = get_permalink( $page_id );
		return false !== $permalink ? $permalink : $default;
	}
}
