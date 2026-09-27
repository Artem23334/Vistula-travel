<?php
/**
 * Global Settings schema (TD-17; field catalogue per docs/data-model.md §13).
 *
 * This is the single source of truth for what a setting is, its type, and
 * its default. Both the admin page renderer (src/Settings/admin-page.php)
 * and the sanitizer below read this schema, rather than each hard-coding
 * its own copy of the field list.
 *
 * Field `type` values and what they mean for storage/rendering/sanitizing:
 * - text            single-line string
 * - textarea        multi-line string
 * - email           string, validated as an email address
 * - checkbox        bool
 * - number          float (used for coordinates)
 * - select          string, constrained to `options`
 * - attachment_id    int, a Media Library attachment ID (0 = none). See the
 *                    Stage 3 report for why this is a plain number field
 *                    rather than a media-library picker button.
 * - hours           fixed 7-day group, each day => [open, close, closed]
 * - social_links    fixed set of known networks => URL
 *
 * Every field not of type `hours` or `social_links` is a flat scalar under
 * its own key in the `vistula_settings` option.
 *
 * i18n note: per data-model.md §13, several of these fields are marked "T"
 * (translatable — one value per language once a multilingual plugin
 * exists). Stage 3 stores a single value for every field, since no
 * multilingual plugin exists yet (TD-19/20, Stage 11) — implementing
 * per-language storage now would be guessing at that plugin's data shape.
 * This is a deliberate, documented simplification, not an oversight.
 *
 * @package Vistula_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The days used by the `hours` field type, in display order.
 */
const VISTULA_SETTINGS_HOURS_DAYS = array( 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' );

/**
 * Translatable labels for the fixed set of days used by the `hours` field
 * type, keyed by the machine day-key in VISTULA_SETTINGS_HOURS_DAYS. Mirrors
 * the VISTULA_SETTINGS_SOCIAL_NETWORKS pattern below: machine key in code,
 * label via gettext (architecture §0.2 rule 3 / M-6) — never translated
 * text stored as the key itself.
 *
 * @return array<string, string>
 */
function vistula_core_hours_day_labels(): array {
	return array(
		'monday'    => __( 'Monday', 'vistula-core' ),
		'tuesday'   => __( 'Tuesday', 'vistula-core' ),
		'wednesday' => __( 'Wednesday', 'vistula-core' ),
		'thursday'  => __( 'Thursday', 'vistula-core' ),
		'friday'    => __( 'Friday', 'vistula-core' ),
		'saturday'  => __( 'Saturday', 'vistula-core' ),
		'sunday'    => __( 'Sunday', 'vistula-core' ),
	);
}

/**
 * The fixed set of social networks offered on the Social tab.
 * A network here with an empty URL is simply omitted from
 * vistula_setting( 'social_links' )'s return value.
 *
 * DEVIATION FROM data-model.md §13 (documented, deliberate — see Stage 3
 * cleanup notes in docs/technical-decisions.md TD-17): §13 marks
 * `social_links` as `group ✔` (repeatable/multi-value — an arbitrary
 * number of network+URL pairs). This implementation instead offers one
 * fixed slot per known network. A true repeatable structure was evaluated
 * and rejected for Stage 3: it would require either an ad-hoc JS add/remove
 * mechanism (duplicating the Repeater field Stage 5 already brings via the
 * TD-12 field framework) or a storage-shape change that also touches the
 * theme's consumption of this setting — both are broader than a Global
 * Settings page. Revisit once Stage 5's field framework is in place, if a
 * client genuinely needs e.g. two Instagram accounts.
 */
const VISTULA_SETTINGS_SOCIAL_NETWORKS = array(
	'facebook'  => 'Facebook',
	'instagram' => 'Instagram',
	'youtube'   => 'YouTube',
	'tiktok'    => 'TikTok',
	'linkedin'  => 'LinkedIn',
);

/**
 * Returns the full settings schema: tab => [ field_key => field_def ].
 *
 * Kept as a function (not a constant) so translators can filter labels
 * later without redefining the whole schema, and so it can reference the
 * page-roles registry for the CTA target options.
 *
 * @return array<string, array<string, array<string, mixed>>>
 */
function vistula_core_settings_schema(): array {
	return array(
		'company' => array(
			'label'  => __( 'Company', 'vistula-core' ),
			'fields' => array(
				'company_name'        => array(
					'label' => __( 'Brand name', 'vistula-core' ),
					'type'  => 'text',
				),
				'company_legal_name'  => array(
					'label' => __( 'Legal name', 'vistula-core' ),
					'type'  => 'text',
				),
				'company_tax_id'      => array(
					'label' => __( 'Tax ID (NIP)', 'vistula-core' ),
					'type'  => 'text',
				),
				'company_registry_id' => array(
					'label' => __( 'Registry number', 'vistula-core' ),
					'type'  => 'text',
				),
				'tagline'             => array(
					'label' => __( 'Tagline', 'vistula-core' ),
					'type'  => 'text',
				),
				'logo_id'             => array(
					'label' => __( 'Logo (Media Library attachment ID)', 'vistula-core' ),
					'type'  => 'attachment_id',
				),
				'logo_dark_id'        => array(
					'label' => __( 'Logo, dark variant (attachment ID)', 'vistula-core' ),
					'type'  => 'attachment_id',
				),
				'default_og_image_id' => array(
					'label' => __( 'Default share image (attachment ID)', 'vistula-core' ),
					'type'  => 'attachment_id',
				),
			),
		),
		'contact' => array(
			'label'  => __( 'Contact', 'vistula-core' ),
			'fields' => array(
				'phone'                  => array(
					'label' => __( 'Phone', 'vistula-core' ),
					'type'  => 'text',
				),
				'email'                  => array(
					'label' => __( 'Public email', 'vistula-core' ),
					'type'  => 'email',
				),
				'contact_form_recipient' => array(
					'label' => __( 'Contact form notifications go to', 'vistula-core' ),
					'type'  => 'email',
				),
				'address_street'         => array(
					'label' => __( 'Street address', 'vistula-core' ),
					'type'  => 'text',
				),
				'address_postcode'       => array(
					'label' => __( 'Postcode', 'vistula-core' ),
					'type'  => 'text',
				),
				'address_city'           => array(
					'label' => __( 'City', 'vistula-core' ),
					'type'  => 'text',
				),
				'address_country'        => array(
					'label' => __( 'Country', 'vistula-core' ),
					'type'  => 'text',
				),
				'geo_lat'                => array(
					'label' => __( 'Latitude', 'vistula-core' ),
					'type'  => 'number',
				),
				'geo_lng'                => array(
					'label' => __( 'Longitude', 'vistula-core' ),
					'type'  => 'number',
				),
				'directions_note'        => array(
					'label' => __( 'Directions note', 'vistula-core' ),
					'type'  => 'text',
				),
			),
		),
		'hours'   => array(
			'label'  => __( 'Hours', 'vistula-core' ),
			'fields' => array(
				'hours'      => array(
					'label' => __( 'Opening hours', 'vistula-core' ),
					'type'  => 'hours',
				),
				'hours_note' => array(
					'label' => __( 'Hours note', 'vistula-core' ),
					'type'  => 'text',
				),
			),
		),
		'social'  => array(
			'label'  => __( 'Social', 'vistula-core' ),
			'fields' => array(
				'social_links' => array(
					'label' => __( 'Social links', 'vistula-core' ),
					'type'  => 'social_links',
				),
			),
		),
		'cta'     => array(
			'label'  => __( 'CTA', 'vistula-core' ),
			'fields' => array(
				'cta_label'       => array(
					'label'   => __( 'Default CTA label', 'vistula-core' ),
					'type'    => 'text',
					'default' => __( 'Book Now', 'vistula-core' ),
				),
				'cta_target_role' => array(
					'label'   => __( 'CTA target', 'vistula-core' ),
					'type'    => 'select',
					// OQ-14: defaults to `tours` because `booking` has no page yet.
					'options' => array(
						'tours'   => __( 'Tours', 'vistula-core' ),
						'contact' => __( 'Contact', 'vistula-core' ),
						'booking' => __( 'Booking (not available yet)', 'vistula-core' ),
					),
					'default' => 'tours',
				),
			),
		),
		'footer'  => array(
			'label'  => __( 'Footer & Legal', 'vistula-core' ),
			'fields' => array(
				'footer_text'        => array(
					'label' => __( 'Footer blurb', 'vistula-core' ),
					'type'  => 'textarea',
				),
				'copyright_text'     => array(
					'label'       => __( 'Copyright line', 'vistula-core' ),
					'type'        => 'text',
					'description' => __( 'You may use {year} and {company} as placeholders.', 'vistula-core' ),
				),
				'demo_notice_enabled' => array(
					'label'       => __( 'Show demo-site disclosure', 'vistula-core' ),
					'type'        => 'checkbox',
					'default'     => false,
					'description' => __( 'Off by default until the disclosure text below is written (TD-50).', 'vistula-core' ),
				),
				'demo_notice_text'   => array(
					'label' => __( 'Demo-site disclosure text', 'vistula-core' ),
					'type'  => 'textarea',
				),
			),
		),
		'seo'     => array(
			'label'  => __( 'SEO Defaults', 'vistula-core' ),
			'fields' => array(
				'seo_default_description' => array(
					'label' => __( 'Default meta description (fallback only)', 'vistula-core' ),
					'type'  => 'textarea',
				),
			),
		),
		'integrations' => array(
			'label'  => __( 'Integrations', 'vistula-core' ),
			'fields' => array(
				'booking_provider' => array(
					'label'   => __( 'Booking provider', 'vistula-core' ),
					'type'    => 'select',
					'options' => array( 'none' => __( 'None (Stage 12)', 'vistula-core' ) ),
					'default' => 'none',
				),
				'maps_provider'    => array(
					'label'       => __( 'Maps provider', 'vistula-core' ),
					'type'        => 'select',
					'options'     => array( 'none' => __( 'None', 'vistula-core' ) ),
					'default'     => 'none',
					'description' => __( 'Defaults to none for privacy (TD-48) — no embed loads third-party scripts until a provider is chosen.', 'vistula-core' ),
				),
			),
		),
	);
}

/**
 * Flattened defaults for every schema field, plus the non-form-rendered
 * system keys (`page_roles`). Used both as register_setting()'s 'default'
 * and to backfill missing keys when reading an option saved by an older
 * version of the schema.
 *
 * @return array<string, mixed>
 */
function vistula_core_settings_defaults(): array {
	$defaults = array();

	foreach ( vistula_core_settings_schema() as $tab ) {
		foreach ( $tab['fields'] as $key => $field ) {
			if ( 'hours' === $field['type'] ) {
				$defaults[ $key ] = array_fill_keys(
					VISTULA_SETTINGS_HOURS_DAYS,
					array(
						'open'   => '',
						'close'  => '',
						'closed' => false,
					)
				);
				continue;
			}
			if ( 'social_links' === $field['type'] ) {
				$defaults[ $key ] = array_fill_keys( array_keys( VISTULA_SETTINGS_SOCIAL_NETWORKS ), '' );
				continue;
			}
			if ( 'checkbox' === $field['type'] ) {
				$defaults[ $key ] = $field['default'] ?? false;
				continue;
			}
			if ( 'attachment_id' === $field['type'] ) {
				$defaults[ $key ] = 0;
				continue;
			}
			$defaults[ $key ] = $field['default'] ?? '';
		}
	}

	// System-managed, never rendered as a form field — see page-roles-bootstrap.php.
	$defaults['page_roles'] = array_fill_keys( VISTULA_PAGE_ROLES, 0 );

	return $defaults;
}

/**
 * Sanitizes a full settings submission against the schema, merging onto
 * the currently stored option so that system-managed keys (`page_roles`)
 * and any field not present in $input (there shouldn't be any — every
 * field is always rendered in the single-page form) are preserved rather
 * than dropped.
 *
 * @param array $input Raw $_POST-derived array for the `vistula_settings` option.
 * @return array Sanitized, merged settings array.
 */
function vistula_core_sanitize_settings( array $input ): array {
	$current = get_option( 'vistula_settings', array() );
	$current = wp_parse_args( $current, vistula_core_settings_defaults() );

	foreach ( vistula_core_settings_schema() as $tab ) {
		foreach ( $tab['fields'] as $key => $field ) {
			switch ( $field['type'] ) {
				case 'hours':
					$raw   = $input[ $key ] ?? array();
					$hours = array();
					foreach ( VISTULA_SETTINGS_HOURS_DAYS as $day ) {
						$hours[ $day ] = array(
							'open'   => sanitize_text_field( $raw[ $day ]['open'] ?? '' ),
							'close'  => sanitize_text_field( $raw[ $day ]['close'] ?? '' ),
							'closed' => ! empty( $raw[ $day ]['closed'] ),
						);
					}
					$current[ $key ] = $hours;
					break;

				case 'social_links':
					$raw     = $input[ $key ] ?? array();
					$sanitized = array();
					foreach ( array_keys( VISTULA_SETTINGS_SOCIAL_NETWORKS ) as $network ) {
						$url = isset( $raw[ $network ] ) ? esc_url_raw( trim( (string) $raw[ $network ] ) ) : '';
						if ( '' !== $url ) {
							$sanitized[ $network ] = $url;
						}
					}
					$current[ $key ] = $sanitized;
					break;

				case 'checkbox':
					$current[ $key ] = ! empty( $input[ $key ] );
					break;

				case 'number':
					$current[ $key ] = isset( $input[ $key ] ) && '' !== $input[ $key ]
						? (float) $input[ $key ]
						: '';
					break;

				case 'attachment_id':
					$current[ $key ] = isset( $input[ $key ] ) ? absint( $input[ $key ] ) : 0;
					break;

				case 'email':
					$current[ $key ] = isset( $input[ $key ] ) ? sanitize_email( $input[ $key ] ) : '';
					break;

				case 'textarea':
					$current[ $key ] = isset( $input[ $key ] ) ? sanitize_textarea_field( $input[ $key ] ) : '';
					break;

				case 'select':
					$options         = array_keys( $field['options'] ?? array() );
					$value           = isset( $input[ $key ] ) ? sanitize_key( $input[ $key ] ) : '';
					$current[ $key ] = in_array( $value, $options, true ) ? $value : ( $field['default'] ?? '' );
					break;

				case 'text':
				default:
					$current[ $key ] = isset( $input[ $key ] ) ? sanitize_text_field( $input[ $key ] ) : '';
					break;
			}
		}
	}

	// 'page_roles' is intentionally not touched here — see page-roles-bootstrap.php.
	return $current;
}
