<?php
/**
 * BuddyBoss Admin Settings - Activity Access Controls.
 *
 * Registers the Access Controls side panel, section, and field for the
 * Activity feature in the Settings 2.0 registry.
 *
 * All access-control logic lives in this file so it can be easily
 * extracted to Pro in the future. Pro populates the actual data
 * (types, options) via PHP filters.
 *
 * @package BuddyBoss\Core\Administration
 * @since   BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Access Controls side panel, section, and field for Activity.
 *
 * Called from bb-admin-settings-activity.php after all other panels are
 * registered. Fires a hook so Pro (or third-party) can register
 * additional fields in the same panel.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_activity_register_access_control_fields() {

	// -------------------------------------------------------------------------
	// SECTION: Member Access Controls.
	// -------------------------------------------------------------------------
	bb_register_feature_section(
		'activity',
		'access_controls',
		'member_access_controls',
		array(
			'title' => __( 'Member Access Controls', 'buddyboss' ),
			'order' => 10,
		)
	);

	// FIELD: Activity Posts access control.
	bb_register_feature_field(
		'activity',
		'access_controls',
		'member_access_controls',
		array(
			'name'              => 'bb-access-control-create-activity',
			'label'             => __( 'Activity Posts', 'buddyboss' ),
			'type'              => 'access_control',
			'description'       => __( 'Select which members can create activity posts based on:', 'buddyboss' ),
			'default'           => '',
			'pro_only'          => true,
			'order'             => 10,
			'sanitize_callback' => 'bb_sanitize_access_control_field',
		)
	);

	/**
	 * Fires after the core Activity access-control fields are registered.
	 *
	 * Pro or third-party plugins can hook here to register additional
	 * access-control fields in the same side panel.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	do_action( 'bb_activity_access_control_after_register_fields' );
}

// =========================================================================
// AJAX DATA ENRICHMENT
// =========================================================================

/**
 * Enrich access_control field data at AJAX time.
 *
 * Adds the `access_control_data` key so the React component receives
 * types, options, and the currently-selected type. Pro populates these
 * via the `bb_access_control_field_data` filter.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array $field_data Formatted field data.
 * @param array $field      Original field registration data.
 *
 * @return array
 */
function bb_access_control_enrich_field_data( $field_data, $field ) {

	if ( 'access_control' !== ( $field_data['type'] ?? '' ) ) {
		return $field_data;
	}

	/**
	 * Filters the access-control data attached to the field.
	 *
	 * Pro hooks here to populate `types` (dropdown options),
	 * `current_type`, and `options` (toggle list for the selected type).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array  $data       Default (empty) access control data.
	 * @param string $field_name The field option name.
	 * @param string $feature_id Feature ID (e.g. 'activity').
	 */
	$field_data['access_control_data'] = apply_filters(
		'bb_access_control_field_data',
		array(
			'types'              => array(),
			'current_type'       => '',
			'options'            => array(),
			'notice'             => __( 'These settings do not apply to administrators.', 'buddyboss' ),
			'select_placeholder' => __( 'Select Role', 'buddyboss' ),
		),
		$field_data['name'],
		'activity'
	);

	// Load saved value from db.
	$saved = bp_get_option( $field_data['name'], '' );
	if ( ! empty( $saved ) && is_array( $saved ) ) {
		$field_data['value'] = $saved;
	}

	return $field_data;
}

add_filter( 'bb_admin_settings_format_field_data', 'bb_access_control_enrich_field_data', 10, 2 );

// =========================================================================
// SANITIZE CALLBACK
// =========================================================================

/**
 * Sanitize callback for access_control fields.
 *
 * The core save loop applies this before persisting the value via
 * bp_update_option. Without this, the default `sanitize_text_field`
 * would flatten the array to an empty string.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The submitted value (array or string).
 *
 * @return array Sanitized access-control value.
 */
function bb_sanitize_access_control_field( $value ) {

	// Handle JSON-encoded string from frontend.
	if ( is_string( $value ) ) {
		$value = json_decode( wp_unslash( $value ), true );
	}

	if ( ! is_array( $value ) ) {
		return array();
	}

	// Sanitize each key/value in the array.
	foreach ( $value as $key => $val ) {
		if ( is_array( $val ) ) {
			$value[ $key ] = array_map( 'sanitize_text_field', $val );
		} else {
			$value[ $key ] = sanitize_text_field( $val );
		}
	}

	/**
	 * Filters the access-control settings before saving.
	 *
	 * Pro hooks here to validate / sanitize the value.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array  $value      The sanitized value.
	 * @param string $field_name The field option name.
	 */
	$value = apply_filters( 'bb_access_control_sanitize_settings', $value, '' );

	return $value;
}
