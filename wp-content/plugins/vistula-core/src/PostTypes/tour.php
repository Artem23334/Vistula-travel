<?php
/**
 * Tour custom post type (data-model.md §2.2; Stage 5).
 *
 * `has_archive` is deliberately false: the Tours listing is a real Page
 * (TD-18), not a CPT archive — see docs/technical-decisions.md TD-18 and
 * the still-pending rewrite-flush test it now finally has a real CPT to
 * run against. `with_front: false` is required per data-model.md's own
 * note: otherwise the Travel Guide's post-permalink front-base would leak
 * into tour URLs.
 *
 * @package Vistula_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The rewrite base for the `tour` CPT, kept in one place (data-model.md
 * §2.2: "Slug/base configurable in one config file so translated bases
 * can be added later").
 *
 * @return string
 */
function vistula_core_tour_rewrite_base(): string {
	return 'tours';
}

/**
 * Registers the `tour` post type.
 */
function vistula_core_register_tour_post_type(): void {
	$labels = array(
		'name'                     => __( 'Tours', 'vistula-core' ),
		'singular_name'            => __( 'Tour', 'vistula-core' ),
		'add_new'                  => __( 'Add New', 'vistula-core' ),
		'add_new_item'             => __( 'Add New Tour', 'vistula-core' ),
		'edit_item'                => __( 'Edit Tour', 'vistula-core' ),
		'new_item'                 => __( 'New Tour', 'vistula-core' ),
		'view_item'                => __( 'View Tour', 'vistula-core' ),
		'view_items'               => __( 'View Tours', 'vistula-core' ),
		'search_items'             => __( 'Search Tours', 'vistula-core' ),
		'not_found'                => __( 'No tours found', 'vistula-core' ),
		'not_found_in_trash'       => __( 'No tours found in Trash', 'vistula-core' ),
		'all_items'                => __( 'All Tours', 'vistula-core' ),
		'archives'                 => __( 'Tour Archives', 'vistula-core' ),
		'attributes'               => __( 'Tour Attributes', 'vistula-core' ),
		'insert_into_item'         => __( 'Insert into tour', 'vistula-core' ),
		'uploaded_to_this_item'    => __( 'Uploaded to this tour', 'vistula-core' ),
		'featured_image'           => __( 'Featured Image', 'vistula-core' ),
		'set_featured_image'       => __( 'Set featured image', 'vistula-core' ),
		'remove_featured_image'    => __( 'Remove featured image', 'vistula-core' ),
		'use_featured_image'       => __( 'Use as featured image', 'vistula-core' ),
		'menu_name'                => __( 'Tours', 'vistula-core' ),
		'filter_items_list'        => __( 'Filter tours list', 'vistula-core' ),
		'items_list_navigation'    => __( 'Tours list navigation', 'vistula-core' ),
		'items_list'               => __( 'Tours list', 'vistula-core' ),
		'item_published'           => __( 'Tour published.', 'vistula-core' ),
		'item_updated'             => __( 'Tour updated.', 'vistula-core' ),
		'item_reverted_to_draft'   => __( 'Tour reverted to draft.', 'vistula-core' ),
	);

	register_post_type(
		'tour',
		array(
			'labels'              => $labels,
			'description'         => __( 'A sellable guided tour or experience.', 'vistula-core' ),
			'public'              => true,
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => true,
			'show_in_rest'        => true,
			'rest_base'           => 'tours',
			'menu_icon'           => 'dashicons-palmtree',
			'menu_position'       => 20,
			'hierarchical'        => false,
			'has_archive'         => false, // TD-18: listing is a real Page.
			'exclude_from_search' => false,
			'query_var'           => 'tour',
			'capability_type'     => 'post', // Default post capabilities — client role is Editor (data-model.md §2.2).
			'map_meta_cap'        => true,
			'rewrite'             => array(
				'slug'       => vistula_core_tour_rewrite_base(),
				'with_front' => false,
			),
			'supports'            => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'page-attributes' ),
			'taxonomies'          => array( 'tour_category', 'tour_type' ),
		)
	);
}
add_action( 'init', 'vistula_core_register_tour_post_type' );
