<?php
/**
 * Destination admin list-table columns (data-model.md §5.5 — "admin
 * columns show tour count (C)").
 *
 * The tour count is computed live via Vistula\Core\Queries, not stored:
 * at six destinations and a few dozen tours there is no performance
 * reason to cache it, and a cached count would need its own
 * invalidation hook every time a tour's destinations change.
 *
 * @package Vistula_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @param array<string, string> $columns Existing columns.
 * @return array<string, string>
 */
function vistula_core_destination_admin_columns( array $columns ): array {
	$new = array();
	foreach ( $columns as $key => $label ) {
		$new[ $key ] = $label;
		if ( 'title' === $key ) {
			$new['destination_type']  = __( 'Type', 'vistula-core' );
			$new['destination_tours'] = __( 'Tours', 'vistula-core' );
		}
	}
	unset( $new['comments'] ); // Comments disabled site-wide (TD-41).
	return $new;
}
add_filter( 'manage_destination_posts_columns', 'vistula_core_destination_admin_columns' );

/**
 * @param string $column  Column key.
 * @param int    $post_id Post ID.
 */
function vistula_core_render_destination_admin_column( string $column, int $post_id ): void {
	switch ( $column ) {
		case 'destination_type':
			$key    = get_field( 'destination_type', $post_id );
			$labels = vistula_core_enum_destination_type();
			echo $key ? esc_html( $labels[ $key ] ?? $key ) : '&#8212;';
			break;

		case 'destination_tours':
			// Admin context: count every linked tour regardless of status,
			// not just published — an editor needs to see drafts too.
			// posts_per_page => -1 is acceptable here specifically because
			// this is a bounded, internal admin count (dozens of tours at
			// most), not a public listing query.
			$count = count(
				\Vistula\Core\Queries::tours_for_destination(
					$post_id,
					array(
						'post_status'    => array( 'publish', 'draft' ),
						'posts_per_page' => -1,
					)
				)
			);
			echo esc_html( (string) $count );
			break;
	}
}
add_action( 'manage_destination_posts_custom_column', 'vistula_core_render_destination_admin_column', 10, 2 );
