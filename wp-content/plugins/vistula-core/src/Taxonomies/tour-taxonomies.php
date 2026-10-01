<?php
/**
 * Tour taxonomies (data-model.md §4.1, §4.2; TD-09).
 *
 * `tour_category` (hierarchical, theme of the tour) and `tour_type`
 * (flat, format of the tour) are deliberately two orthogonal, filterable
 * axes — see TD-09's rationale for why this splits the Spec's single flat
 * category list. No other tour taxonomy is introduced (Stage 5 brief).
 *
 * @package Vistula_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers `tour_category` and `tour_type`.
 */
function vistula_core_register_tour_taxonomies(): void {
	register_taxonomy(
		'tour_category',
		array( 'tour' ),
		array(
			'labels'            => array(
				'name'              => __( 'Tour Categories', 'vistula-core' ),
				'singular_name'     => __( 'Tour Category', 'vistula-core' ),
				'search_items'      => __( 'Search Categories', 'vistula-core' ),
				'all_items'         => __( 'All Categories', 'vistula-core' ),
				'parent_item'       => __( 'Parent Category', 'vistula-core' ),
				'parent_item_colon' => __( 'Parent Category:', 'vistula-core' ),
				'edit_item'         => __( 'Edit Category', 'vistula-core' ),
				'update_item'       => __( 'Update Category', 'vistula-core' ),
				'add_new_item'      => __( 'Add New Category', 'vistula-core' ),
				'new_item_name'     => __( 'New Category Name', 'vistula-core' ),
				'menu_name'         => __( 'Categories', 'vistula-core' ),
			),
			'hierarchical'      => true, // data-model.md §4.1 — allows future nesting (e.g. Nature → Mountains).
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => true,
			'show_in_rest'      => true,
			'rest_base'         => 'tour-categories',
			'query_var'         => true,
			'rewrite'           => array(
				'slug'       => vistula_core_tour_rewrite_base() . '/category',
				'with_front' => false,
			),
		)
	);

	register_taxonomy(
		'tour_type',
		array( 'tour' ),
		array(
			'labels'            => array(
				'name'              => __( 'Tour Types', 'vistula-core' ),
				'singular_name'     => __( 'Tour Type', 'vistula-core' ),
				'search_items'      => __( 'Search Types', 'vistula-core' ),
				'all_items'         => __( 'All Types', 'vistula-core' ),
				'edit_item'         => __( 'Edit Type', 'vistula-core' ),
				'update_item'       => __( 'Update Type', 'vistula-core' ),
				'add_new_item'      => __( 'Add New Type', 'vistula-core' ),
				'new_item_name'     => __( 'New Type Name', 'vistula-core' ),
				'menu_name'         => __( 'Types', 'vistula-core' ),
			),
			'hierarchical'      => false, // data-model.md §4.2.
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => true,
			'show_in_rest'      => true,
			'rest_base'         => 'tour-types',
			'query_var'         => true,
			'rewrite'           => array(
				'slug'       => vistula_core_tour_rewrite_base() . '/type',
				'with_front' => false,
			),
		)
	);
}
add_action( 'init', 'vistula_core_register_tour_taxonomies', 5 ); // Before the CPT registration's default priority, so `taxonomies` in tour.php can reference them safely either order — WordPress resolves this at the 'init' action regardless, priority 5 here is just for clarity of intent.

/**
 * Seed terms (data-model.md §4.1/§4.2) — created once, idempotently, the
 * same way the Stage 3 page-roles bootstrap works: checked on `admin_init`
 * behind a transient, not on every request, and never re-created if a
 * term already exists (an editor may have renamed/removed one on purpose).
 *
 * Reconciliation note (F-01/F-08/OQ-08): these seed terms are exactly the
 * data-model's own list; if the owner prefers the Spec's flat category
 * list instead, this function is the one place to change.
 */
function vistula_core_seed_tour_taxonomy_terms(): void {
	if ( get_transient( 'vistula_core_tour_terms_seeded' ) ) {
		return;
	}

	$category_terms = array( 'City Tours', 'Nature', 'Mountains', 'History', 'Culture & Food' );
	foreach ( $category_terms as $term ) {
		if ( ! term_exists( $term, 'tour_category' ) ) {
			wp_insert_term( $term, 'tour_category' );
		}
	}

	$type_terms = array( 'Group Tour', 'Private Tour', 'Weekend Trip' );
	foreach ( $type_terms as $term ) {
		if ( ! term_exists( $term, 'tour_type' ) ) {
			wp_insert_term( $term, 'tour_type' );
		}
	}

	set_transient( 'vistula_core_tour_terms_seeded', true, 5 * MINUTE_IN_SECONDS );
}
add_action( 'admin_init', 'vistula_core_seed_tour_taxonomy_terms', 20 );
