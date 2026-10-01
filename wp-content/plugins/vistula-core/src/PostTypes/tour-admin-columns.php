<?php
/**
 * Tour admin list-table columns (Stage 5 brief §6 — "admin usable for
 * Tour management").
 *
 * @package Vistula_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Defines the columns shown on the Tours list table, replacing the
 * default title-only view with the fields an editor actually needs to
 * scan at a glance.
 *
 * @param array<string, string> $columns Existing columns.
 * @return array<string, string>
 */
function vistula_core_tour_admin_columns( array $columns ): array {
	$new = array();
	foreach ( $columns as $key => $label ) {
		$new[ $key ] = $label;
		if ( 'title' === $key ) {
			$new['tour_price']        = __( 'Price', 'vistula-core' );
			$new['tour_duration']     = __( 'Duration', 'vistula-core' );
			$new['tour_difficulty']   = __( 'Difficulty', 'vistula-core' );
			$new['tour_destinations'] = __( 'Destinations', 'vistula-core' );
			$new['tour_popular']      = __( 'Popular', 'vistula-core' );
			$new['tour_booking']      = __( 'Booking', 'vistula-core' );
		}
	}
	// date/comments columns aren't useful here (comments disabled — TD-41).
	unset( $new['comments'] );
	return $new;
}
add_filter( 'manage_tour_posts_columns', 'vistula_core_tour_admin_columns' );

/**
 * Renders each custom column's content.
 *
 * @param string $column  Column key.
 * @param int    $post_id Post ID.
 */
function vistula_core_render_tour_admin_column( string $column, int $post_id ): void {
	switch ( $column ) {
		case 'tour_price':
			$price = get_field( 'tour_price_from', $post_id );
			$type  = get_field( 'tour_price_type', $post_id );
			if ( '' !== $price && null !== $price ) {
				printf(
					'%s PLN <span class="description">%s</span>',
					esc_html( number_format_i18n( (float) $price, 0 ) ),
					esc_html( 'per_group' === $type ? __( '/ group', 'vistula-core' ) : __( '/ person', 'vistula-core' ) )
				);
			} else {
				echo '&#8212;';
			}
			break;

		case 'tour_duration':
			$minutes = get_post_meta( $post_id, '_duration_minutes', true );
			$bucket  = get_post_meta( $post_id, '_duration_bucket', true );
			if ( $minutes ) {
				$hours = round( (int) $minutes / 60, 1 );
				printf(
					'%s %s',
					esc_html( (string) $hours ),
					esc_html__( 'h', 'vistula-core' )
				);
				if ( $bucket ) {
					$labels = vistula_core_enum_duration_bucket();
					echo ' <span class="description">(' . esc_html( $labels[ $bucket ] ?? $bucket ) . ')</span>';
				}
			} else {
				echo '&#8212;';
			}
			break;

		case 'tour_difficulty':
			$key    = get_field( 'tour_difficulty', $post_id );
			$labels = vistula_core_enum_difficulty();
			echo $key ? esc_html( $labels[ $key ] ?? $key ) : '&#8212;';
			break;

		case 'tour_destinations':
			$ids = (array) get_field( 'tour_destinations', $post_id );
			if ( empty( $ids ) ) {
				echo '<span class="description">' . esc_html__( 'None yet', 'vistula-core' ) . '</span>';
				break;
			}
			$titles = array_map( 'get_the_title', array_map( 'intval', $ids ) );
			echo esc_html( implode( ', ', $titles ) );
			break;

		case 'tour_popular':
			echo get_field( 'tour_is_popular', $post_id )
				? '<span class="dashicons dashicons-star-filled" style="color:#b08a4e" aria-label="' . esc_attr__( 'Popular', 'vistula-core' ) . '"></span>'
				: '&#8212;';
			break;

		case 'tour_booking':
			echo get_field( 'tour_booking_enabled', $post_id )
				? '<span style="color:#2b4438">' . esc_html__( 'Enabled', 'vistula-core' ) . '</span>'
				: '<span class="description">' . esc_html__( 'Enquire only', 'vistula-core' ) . '</span>';
			break;
	}
}
add_action( 'manage_tour_posts_custom_column', 'vistula_core_render_tour_admin_column', 10, 2 );

/**
 * Makes Price and Duration sortable, backed by the derived `_price_sort`/
 * `_duration_minutes` postmeta (Class C — see tour-derived-fields.php)
 * rather than an ACF field directly, since those are the indexed numeric
 * values meant for exactly this kind of query.
 *
 * @param array<string, string> $columns Sortable columns map.
 * @return array<string, string>
 */
function vistula_core_tour_sortable_columns( array $columns ): array {
	$columns['tour_price']    = 'tour_price';
	$columns['tour_duration'] = 'tour_duration';
	return $columns;
}
add_filter( 'manage_edit-tour_sortable_columns', 'vistula_core_tour_sortable_columns' );

/**
 * Wires the sortable columns above to the actual postmeta keys.
 *
 * @param \WP_Query $query The current admin list-table query.
 */
function vistula_core_tour_admin_sort( \WP_Query $query ): void {
	if ( ! is_admin() || ! $query->is_main_query() || 'tour' !== $query->get( 'post_type' ) ) {
		return;
	}

	$orderby = $query->get( 'orderby' );
	if ( 'tour_price' === $orderby ) {
		$query->set( 'meta_key', '_price_sort' );
		$query->set( 'orderby', 'meta_value_num' );
	} elseif ( 'tour_duration' === $orderby ) {
		$query->set( 'meta_key', '_duration_minutes' );
		$query->set( 'orderby', 'meta_value_num' );
	}
}
add_action( 'pre_get_posts', 'vistula_core_tour_admin_sort' );

/**
 * Admin-only filter dropdowns for Category/Type/Popular above the list
 * table, so an editor can narrow a long tour list without leaving admin.
 */
function vistula_core_tour_admin_filters(): void {
	global $typenow;
	if ( 'tour' !== $typenow ) {
		return;
	}

	foreach ( array( 'tour_category', 'tour_type' ) as $taxonomy ) {
		$object = get_taxonomy( $taxonomy );
		$selected = $_GET[ $taxonomy ] ?? ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only list filter, not a state change.
		wp_dropdown_categories(
			array(
				'show_option_all' => $object->labels->all_items,
				'taxonomy'        => $taxonomy,
				'name'            => $taxonomy,
				'orderby'         => 'name',
				'selected'        => sanitize_text_field( is_string( $selected ) ? $selected : '' ),
				'hierarchical'    => $object->hierarchical,
				'value_field'     => 'slug',
			)
		);
	}
}
add_action( 'restrict_manage_posts', 'vistula_core_tour_admin_filters' );
