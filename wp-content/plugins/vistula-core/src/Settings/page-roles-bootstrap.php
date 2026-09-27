<?php
/**
 * Page-roles bootstrap (architecture §4.4; Stage 3 task 4).
 *
 * Creates a placeholder WordPress Page for each of the seven roles the
 * Stage 3 brief names (home, tours, destinations, travel_guide, about,
 * faq, contact) if — and only if — no valid Page is currently mapped to
 * that role. Booking and the legal roles (privacy/cookies/terms) are
 * deliberately left unmapped: Stage 3's brief says not to build them as
 * "full implementation" yet, and nothing in the approved architecture
 * requires a placeholder Page for them this early (Booking is Stage 12;
 * legal-page placeholders are explicitly Stage 9 work, per
 * docs/implementation-roadmap.md).
 *
 * Hooked on `admin_init` rather than only on plugin activation, so it is
 * self-healing: if a placeholder Page is later trashed by mistake, the
 * next wp-admin load recreates it rather than leaving a dangling role.
 * The check is a handful of `get_post_status()` calls, so the no-op path
 * (everything already mapped) costs nothing meaningful.
 *
 * @package Vistula_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Roles this bootstrap is responsible for, and the Page title to give each.
 * Order matches the Stage 3 brief.
 *
 * @return array<string, string>
 */
function vistula_core_bootstrapped_page_roles(): array {
	return array(
		'home'         => __( 'Home', 'vistula-core' ),
		'tours'        => __( 'Tours', 'vistula-core' ),
		'destinations' => __( 'Destinations', 'vistula-core' ),
		'travel_guide' => __( 'Travel Guide', 'vistula-core' ),
		'about'        => __( 'About', 'vistula-core' ),
		'faq'          => __( 'FAQ', 'vistula-core' ),
		'contact'      => __( 'Contact', 'vistula-core' ),
	);
}

/**
 * Ensures every role above has a real, non-trashed Page, creating one as a
 * draft placeholder if it's missing. Does not touch a role's Page once
 * mapped, even if the title was since changed by an editor.
 */
function vistula_core_ensure_page_role_pages(): void {
	// Self-healing, but not on every single admin request — an unchanged
	// site would otherwise pay a handful of extra queries on every
	// wp-admin page load for no reason. Re-checks at most every 5 minutes.
	if ( get_transient( 'vistula_core_page_roles_checked' ) ) {
		return;
	}

	$settings    = get_option( 'vistula_settings', array() );
	$page_roles  = wp_parse_args( $settings['page_roles'] ?? array(), array_fill_keys( VISTULA_PAGE_ROLES, 0 ) );
	$changed     = false;

	foreach ( vistula_core_bootstrapped_page_roles() as $role => $title ) {
		$existing_id = (int) ( $page_roles[ $role ] ?? 0 );

		if ( $existing_id > 0 ) {
			$status = get_post_status( $existing_id );
			$type   = get_post_type( $existing_id );
			if ( $status && 'trash' !== $status && 'page' === $type ) {
				continue; // Already mapped to a real, non-trashed Page — leave it alone.
			}
		}

		$new_id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_title'   => $title,
				'post_status'  => 'draft', // Placeholder only — no design/content yet.
				'post_content' => '',
			),
			true
		);

		if ( is_wp_error( $new_id ) ) {
			// Do not silently ignore — surface it where an admin will see it.
			add_action(
				'admin_notices',
				static function () use ( $role, $new_id ): void {
					printf(
						'<div class="notice notice-error"><p>%s</p></div>',
						esc_html(
							sprintf(
								/* translators: 1: page role key, 2: WP_Error message. */
								__( 'Vistula Travel: could not create the placeholder Page for the "%1$s" role: %2$s', 'vistula-core' ),
								$role,
								$new_id->get_error_message()
							)
						)
					);
				}
			);
			continue;
		}

		$page_roles[ $role ] = $new_id;
		$changed              = true;
	}

	if ( $changed ) {
		$settings['page_roles'] = $page_roles;
		update_option( 'vistula_settings', $settings );
	}

	set_transient( 'vistula_core_page_roles_checked', true, 5 * MINUTE_IN_SECONDS );
}
add_action( 'admin_init', 'vistula_core_ensure_page_role_pages', 20 );
