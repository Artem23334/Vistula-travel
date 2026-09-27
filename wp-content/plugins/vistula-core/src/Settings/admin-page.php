<?php
/**
 * Global Settings admin page (TD-17).
 *
 * A single settings page, one registered option (`vistula_settings`), one
 * form. "Tabs" are anchor-linked sections on one page (WordPress core's
 * `.nav-tab-wrapper` styling, no custom JS/CSS) — this sidesteps the
 * classic Settings-API bug where submitting only one tab's fields would
 * wipe every other tab's stored values, since every field is always
 * present in the one form that gets submitted.
 *
 * @package Vistula_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the `vistula_settings` option and its sections/fields from
 * the schema. Hooked on `admin_init`, per WordPress convention.
 */
function vistula_core_register_settings(): void {
	register_setting(
		'vistula_settings_group',
		'vistula_settings',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'vistula_core_sanitize_settings',
			'default'           => vistula_core_settings_defaults(),
		)
	);

	foreach ( vistula_core_settings_schema() as $tab_key => $tab ) {
		$section_id = 'vistula_section_' . $tab_key;

		add_settings_section(
			$section_id,
			$tab['label'],
			static function () use ( $tab_key ): void {
				printf( '<div id="%s"></div>', esc_attr( 'vistula-tab-' . $tab_key ) );
			},
			'vistula-settings'
		);

		foreach ( $tab['fields'] as $key => $field ) {
			add_settings_field(
				'vistula_field_' . $key,
				esc_html( $field['label'] ),
				static function () use ( $key, $field ): void {
					vistula_core_render_settings_field( $key, $field );
				},
				'vistula-settings',
				$section_id
			);
		}
	}
}
add_action( 'admin_init', 'vistula_core_register_settings' );

/**
 * Renders a single field's input(s), reading the current stored value.
 *
 * @param string $key   Settings array key.
 * @param array  $field Field definition from the schema.
 */
function vistula_core_render_settings_field( string $key, array $field ): void {
	$settings = wp_parse_args( get_option( 'vistula_settings', array() ), vistula_core_settings_defaults() );
	$value    = $settings[ $key ] ?? '';
	$name     = "vistula_settings[{$key}]";

	switch ( $field['type'] ) {
		case 'textarea':
			printf(
				'<textarea name="%s" rows="3" class="large-text">%s</textarea>',
				esc_attr( $name ),
				esc_textarea( (string) $value )
			);
			break;

		case 'checkbox':
			printf(
				'<label><input type="checkbox" name="%s" value="1" %s /> %s</label>',
				esc_attr( $name ),
				checked( ! empty( $value ), true, false ),
				esc_html__( 'Enabled', 'vistula-core' )
			);
			break;

		case 'select':
			printf( '<select name="%s">', esc_attr( $name ) );
			foreach ( $field['options'] as $option_value => $option_label ) {
				printf(
					'<option value="%s" %s>%s</option>',
					esc_attr( $option_value ),
					selected( $value, $option_value, false ),
					esc_html( $option_label )
				);
			}
			echo '</select>';
			break;

		case 'number':
			printf(
				'<input type="number" step="any" name="%s" value="%s" class="regular-text" />',
				esc_attr( $name ),
				esc_attr( (string) $value )
			);
			break;

		case 'email':
			printf(
				'<input type="email" name="%s" value="%s" class="regular-text" />',
				esc_attr( $name ),
				esc_attr( (string) $value )
			);
			break;

		case 'attachment_id':
			printf(
				'<input type="number" min="0" step="1" name="%s" value="%s" class="small-text" placeholder="0" />',
				esc_attr( $name ),
				esc_attr( (string) $value )
			);
			echo ' <span class="description">' . esc_html__( 'Media Library attachment ID, or 0 for none.', 'vistula-core' ) . '</span>';
			if ( ! empty( $value ) && wp_attachment_is_image( (int) $value ) ) {
				echo '<br />' . wp_get_attachment_image( (int) $value, array( 80, 80 ) );
			}
			break;

		case 'hours':
			echo '<table class="widefat" style="max-width:600px"><thead><tr>';
			echo '<th>' . esc_html__( 'Day', 'vistula-core' ) . '</th>';
			echo '<th>' . esc_html__( 'Open', 'vistula-core' ) . '</th>';
			echo '<th>' . esc_html__( 'Close', 'vistula-core' ) . '</th>';
			echo '<th>' . esc_html__( 'Closed', 'vistula-core' ) . '</th>';
			echo '</tr></thead><tbody>';
			foreach ( VISTULA_SETTINGS_HOURS_DAYS as $day ) {
				$row = $value[ $day ] ?? array(
					'open'   => '',
					'close'  => '',
					'closed' => false,
				);
				printf( '<tr><td>%s</td>', esc_html( vistula_core_hours_day_labels()[ $day ] ?? $day ) );
				printf(
					'<td><input type="time" name="%s[%s][open]" value="%s" /></td>',
					esc_attr( $name ),
					esc_attr( $day ),
					esc_attr( $row['open'] )
				);
				printf(
					'<td><input type="time" name="%s[%s][close]" value="%s" /></td>',
					esc_attr( $name ),
					esc_attr( $day ),
					esc_attr( $row['close'] )
				);
				printf(
					'<td><input type="checkbox" name="%s[%s][closed]" value="1" %s /></td>',
					esc_attr( $name ),
					esc_attr( $day ),
					checked( ! empty( $row['closed'] ), true, false )
				);
				echo '</tr>';
			}
			echo '</tbody></table>';
			break;

		case 'social_links':
			foreach ( VISTULA_SETTINGS_SOCIAL_NETWORKS as $network => $label ) {
				printf(
					'<p><label style="display:inline-block;width:100px">%s</label> <input type="url" name="%s[%s]" value="%s" class="regular-text" placeholder="https://" /></p>',
					esc_html( $label ),
					esc_attr( $name ),
					esc_attr( $network ),
					esc_attr( $value[ $network ] ?? '' )
				);
			}
			break;

		case 'text':
		default:
			printf(
				'<input type="text" name="%s" value="%s" class="regular-text" />',
				esc_attr( $name ),
				esc_attr( (string) $value )
			);
			break;
	}

	if ( ! empty( $field['description'] ) ) {
		echo '<p class="description">' . esc_html( $field['description'] ) . '</p>';
	}
}

/**
 * Registers the "Vistula Travel" top-level admin menu and its single
 * "Company" settings page.
 */
function vistula_core_register_admin_menu(): void {
	add_menu_page(
		__( 'Vistula Travel', 'vistula-core' ),
		__( 'Vistula Travel', 'vistula-core' ),
		'manage_options',
		'vistula-settings',
		'vistula_core_render_settings_page',
		'dashicons-palmtree',
		58
	);
}
add_action( 'admin_menu', 'vistula_core_register_admin_menu' );

/**
 * Renders the settings page: a nav-tab bar (anchors into the one form
 * below, not separate submissions) followed by every section/field.
 */
function vistula_core_render_settings_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'vistula-core' ) );
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Vistula Travel — Company Settings', 'vistula-core' ); ?></h1>

		<h2 class="nav-tab-wrapper">
			<?php foreach ( vistula_core_settings_schema() as $tab_key => $tab ) : ?>
				<a href="#vistula-tab-<?php echo esc_attr( $tab_key ); ?>" class="nav-tab">
					<?php echo esc_html( $tab['label'] ); ?>
				</a>
			<?php endforeach; ?>
		</h2>

		<form action="options.php" method="post">
			<?php
			settings_fields( 'vistula_settings_group' );
			do_settings_sections( 'vistula-settings' );
			submit_button();
			?>
		</form>
	</div>
	<?php
}
