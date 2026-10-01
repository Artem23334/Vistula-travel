<?php
/**
 * Destination custom post type (data-model.md §5.2; this CMS/Data block).
 *
 * Mirrors `tour.php` deliberately: same has_archive=false rationale
 * (TD-18 — listing is a real Page), same with_front=false requirement,
 * same supports list. No taxonomies on destination (data-model §5.2
 * explicitly: "Taxonomies: none initially").
 *
 * @package Vistula_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The rewrite base for the `destination` CPT, kept in one place like
 * vistula_core_tour_rewrite_base().
 *
 * @return string
 */
function vistula_core_destination_rewrite_base(): string {
	return 'destinations';
}

/**
 * Registers the `destination` post type.
 */
function vistula_core_register_destination_post_type(): void {
	$labels = array(
		'name'                   => __( 'Destinations', 'vistula-core' ),
		'singular_name'          => __( 'Destination', 'vistula-core' ),
		'add_new'                => __( 'Add New', 'vistula-core' ),
		'add_new_item'           => __( 'Add New Destination', 'vistula-core' ),
		'edit_item'              => __( 'Edit Destination', 'vistula-core' ),
		'new_item'               => __( 'New Destination', 'vistula-core' ),
		'view_item'              => __( 'View Destination', 'vistula-core' ),
		'view_items'             => __( 'View Destinations', 'vistula-core' ),
		'search_items'           => __( 'Search Destinations', 'vistula-core' ),
		'not_found'              => __( 'No destinations found', 'vistula-core' ),
		'not_found_in_trash'     => __( 'No destinations found in Trash', 'vistula-core' ),
		'all_items'              => __( 'All Destinations', 'vistula-core' ),
		'archives'               => __( 'Destination Archives', 'vistula-core' ),
		'attributes'             => __( 'Destination Attributes', 'vistula-core' ),
		'insert_into_item'       => __( 'Insert into destination', 'vistula-core' ),
		'uploaded_to_this_item'  => __( 'Uploaded to this destination', 'vistula-core' ),
		'featured_image'         => __( 'Featured Image', 'vistula-core' ),
		'set_featured_image'     => __( 'Set featured image', 'vistula-core' ),
		'remove_featured_image'  => __( 'Remove featured image', 'vistula-core' ),
		'use_featured_image'     => __( 'Use as featured image', 'vistula-core' ),
		'menu_name'              => __( 'Destinations', 'vistula-core' ),
		'filter_items_list'      => __( 'Filter destinations list', 'vistula-core' ),
		'items_list_navigation'  => __( 'Destinations list navigation', 'vistula-core' ),
		'items_list'             => __( 'Destinations list', 'vistula-core' ),
		'item_published'         => __( 'Destination published.', 'vistula-core' ),
		'item_updated'           => __( 'Destination updated.', 'vistula-core' ),
		'item_reverted_to_draft' => __( 'Destination reverted to draft.', 'vistula-core' ),
	);

	register_post_type(
		'destination',
		array(
			'labels'              => $labels,
			'description'         => __( 'A place Vistula Travel offers tours to — a hub for its tours, guide articles and media.', 'vistula-core' ),
			'public'              => true,
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => true,
			'show_in_rest'        => true,
			'rest_base'           => 'destinations',
			'menu_icon'           => 'dashicons-location-alt',
			'menu_position'       => 21, // Directly after Tours (20).
			'hierarchical'        => false,
			'has_archive'         => false, // TD-18: listing is a real Page.
			'exclude_from_search' => false,
			'query_var'           => 'destination',
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
			'rewrite'             => array(
				'slug'       => vistula_core_destination_rewrite_base(),
				'with_front' => false,
			),
			'supports'            => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'page-attributes' ),
			'taxonomies'          => array(), // data-model.md §5.2 — none initially.
		)
	);
}
add_action( 'init', 'vistula_core_register_destination_post_type' );
