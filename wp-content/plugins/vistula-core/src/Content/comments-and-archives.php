<?php
/**
 * Content-policy baseline (TD-41): comments off, author/tag archives noindex.
 *
 * A business rule ("this site has no comments, no author pages") that
 * should survive a theme change — hence it lives here, not in the theme,
 * per docs/project-architecture.md §3.3 rule 3.
 *
 * @package Vistula_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Remove comment/trackback support from every post type WordPress ships
 * with. Custom post types (Stage 5+) simply never declare 'comments'
 * support, so nothing further is needed once they exist.
 */
function vistula_core_disable_comments_support(): void {
	foreach ( get_post_types( array( 'public' => true ) ) as $post_type ) {
		if ( post_type_supports( $post_type, 'comments' ) ) {
			remove_post_type_support( $post_type, 'comments' );
		}
		if ( post_type_supports( $post_type, 'trackbacks' ) ) {
			remove_post_type_support( $post_type, 'trackbacks' );
		}
	}
}
add_action( 'init', 'vistula_core_disable_comments_support', 100 );

/**
 * Close comments/pings on every post at the query level, so even content
 * imported with comments already open behaves correctly.
 *
 * @param bool $open Whether comments are open — unused, always forced closed.
 * @return bool
 */
function vistula_core_force_comments_closed( bool $open ): bool {
	unset( $open );
	return false;
}
add_filter( 'comments_open', 'vistula_core_force_comments_closed', 20 );
add_filter( 'pings_open', 'vistula_core_force_comments_closed', 20 );

/**
 * Hide the admin "Comments" menu and dashboard widget — there is nothing
 * to moderate on a site with comments disabled.
 */
function vistula_core_remove_comments_admin_menu(): void {
	remove_menu_page( 'edit-comments.php' );
}
add_action( 'admin_menu', 'vistula_core_remove_comments_admin_menu' );

/**
 * Remove the "Comments" bubble from the admin toolbar.
 *
 * @param \WP_Admin_Bar $wp_admin_bar Core admin bar instance.
 */
function vistula_core_remove_comments_admin_bar( \WP_Admin_Bar $wp_admin_bar ): void {
	$wp_admin_bar->remove_node( 'comments' );
}
add_action( 'wp_before_admin_bar_render', 'vistula_core_remove_comments_admin_bar' );

/**
 * Noindex author archives and tag archives (TD-41). The site has no
 * multi-author byline strategy and no tag taxonomy in the approved content
 * model (docs/data-model.md), so both archive types are thin/duplicate
 * content by default rather than something worth indexing.
 *
 * Uses the core `wp_robots` filter (WordPress 5.7+) — the single, filterable
 * seam architecture §13 (SEO ownership) requires meta output to go through,
 * rather than a competing custom <meta name="robots"> tag in the theme.
 *
 * @param array $robots Robots directives keyed by directive name.
 * @return array
 */
function vistula_core_noindex_author_and_tag_archives( array $robots ): array {
	if ( is_author() || is_tag() ) {
		$robots['noindex']  = true;
		$robots['follow']   = true;
	}
	return $robots;
}
add_filter( 'wp_robots', 'vistula_core_noindex_author_and_tag_archives' );
