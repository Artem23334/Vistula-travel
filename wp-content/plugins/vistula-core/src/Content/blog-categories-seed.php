<?php
/**
 * Travel Guide (Blog) category seed terms (data-model.md §6.2, Prompt).
 *
 * Uses WordPress core `category` — no custom taxonomy, per TD-05 ("are
 * standard Posts correct? Yes"). Same idempotent, transient-guarded
 * `admin_init` pattern already used for page-roles (Stage 3) and tour
 * taxonomy terms (Stage 5).
 *
 * @package Vistula_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The five agreed Travel Guide categories, in display order.
 *
 * @return string[]
 */
function vistula_core_blog_category_terms(): array {
	return array( 'Travel Tips', 'Things To Do', 'Polish Food', 'Culture', 'Poland Travel' );
}

/**
 * Creates the five categories if they don't already exist. Never renames
 * or removes a category an editor has since changed.
 */
function vistula_core_seed_blog_categories(): void {
	if ( get_transient( 'vistula_core_blog_categories_seeded' ) ) {
		return;
	}

	foreach ( vistula_core_blog_category_terms() as $term ) {
		if ( ! term_exists( $term, 'category' ) ) {
			wp_insert_term( $term, 'category' );
		}
	}

	set_transient( 'vistula_core_blog_categories_seeded', true, 5 * MINUTE_IN_SECONDS );
}
add_action( 'admin_init', 'vistula_core_seed_blog_categories', 20 );
