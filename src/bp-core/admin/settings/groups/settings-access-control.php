<?php
/**
 * BuddyBoss Admin Settings - Groups Access Controls.
 *
 * Registers the Access Controls side panel, section, and fields for the
 * Groups feature in the Settings 2.0 registry.
 *
 * All access-control logic lives in this file so it can be easily
 * extracted to Pro in the future. Pro populates the actual data
 * (types, options) via PHP filters.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Access Controls side panel, section, and fields for Groups.
 *
 * Called from bb-admin-settings-groups.php after all other panels are
 * registered. Fires a hook so Pro (or third-party) can register
 * additional fields in the same panel.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_groups_register_access_control_fields() {

	// -------------------------------------------------------------------------
	// SECTION: Member Access Controls.
	// -------------------------------------------------------------------------
	bb_register_feature_section(
		'groups',
		'access_controls',
		'member_access_controls',
		array(
			'title' => __( 'Member Access Controls', 'buddyboss' ),
			'order' => 10,
		)
	);

	// FIELD: Create Groups access control.
	bb_register_feature_field(
		'groups',
		'access_controls',
		'member_access_controls',
		array(
			'name'              => 'bb-access-control-create-groups',
			'label'             => __( 'Create Groups', 'buddyboss' ),
			'type'              => 'access_control',
			'description'       => __( 'Select which members can create groups based on:', 'buddyboss' ),
			'default'           => '',
			'pro_only'          => true,
			'order'             => 10,
			'sanitize_callback' => 'bb_sanitize_access_control_field',
		)
	);

	// FIELD: Join Groups access control.
	bb_register_feature_field(
		'groups',
		'access_controls',
		'member_access_controls',
		array(
			'name'              => 'bb-access-control-join-groups',
			'label'             => __( 'Join Groups', 'buddyboss' ),
			'type'              => 'access_control',
			'description'       => __( 'Select which members can join public groups or request to join private groups based on:', 'buddyboss' ),
			'default'           => '',
			'pro_only'          => true,
			'order'             => 20,
			'sanitize_callback' => 'bb_sanitize_access_control_field',
		)
	);

	// FIELD: Admin notice (displayed once at the end of the section).
	bb_register_feature_field(
		'groups',
		'access_controls',
		'member_access_controls',
		array(
			'name'        => 'bb-groups-access-control-notice',
			'label'       => '',
			'type'        => 'notice',
			'description' => __( 'These settings do not apply to administrators.', 'buddyboss' ),
			'notice_type' => 'info',
			'order'       => 100,
		)
	);

	/**
	 * Fires after the core Groups access-control fields are registered.
	 *
	 * Pro or third-party plugins can hook here to register additional
	 * access-control fields in the same side panel.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	do_action( 'bb_groups_access_control_after_register_fields' );
}

// =========================================================================
// SANITIZE CALLBACK (fallback when Activity is disabled)
// =========================================================================

// The sanitize callback may already be registered by Activity settings.
// Define it here too so Groups access controls work even when Activity is disabled.
if ( ! function_exists( 'bb_sanitize_access_control_field' ) ) {

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
		// Note: wp_unslash() is already applied by the AJAX handler before this callback.
		if ( is_string( $value ) ) {
			$value = json_decode( $value, true );
		}

		if ( ! is_array( $value ) ) {
			return array();
		}

		// Recursively sanitize each value in the array.
		$value = map_deep( $value, 'sanitize_text_field' );

		/**
		 * Filters the access-control settings before saving.
		 *
		 * Pro hooks here to validate / sanitize the value.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array $value The sanitized value.
		 */
		$value = apply_filters( 'bb_access_control_sanitize_settings', $value );

		return $value;
	}
}

// =========================================================================
// AJAX DATA ENRICHMENT
// =========================================================================

// The access_control enrichment filter may already be registered by Activity.
// Register it here too so Groups access controls work even when Activity is disabled.
if ( ! function_exists( 'bb_access_control_enrich_field_data' ) ) {

	/**
	 * Enrich access_control field data at AJAX time.
	 *
	 * Adds the `access_control_data` key so the React component receives
	 * types, options, and the currently-selected type. Pro populates these
	 * via the `bb_access_control_field_data` filter.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array  $field_data Formatted field data.
	 * @param array  $field      Original field registration data.
	 * @param string $feature_id Feature ID (e.g. 'activity', 'groups').
	 *
	 * @return array
	 */
	function bb_access_control_enrich_field_data( $field_data, $field, $feature_id = '' ) {

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
		 * @param string $feature_id Feature ID (e.g. 'activity', 'groups').
		 */
		$field_data['access_control_data'] = apply_filters(
			'bb_access_control_field_data',
			array(
				'types'              => array(),
				'current_type'       => '',
				'options'            => array(),
				'select_placeholder' => __( 'Select Role', 'buddyboss' ),
			),
			$field_data['name'],
			$feature_id
		);

		// Load saved value from db.
		$saved = bp_get_option( $field_data['name'], '' );
		if ( ! empty( $saved ) && is_array( $saved ) ) {
			$field_data['value'] = $saved;
		}

		return $field_data;
	}

	add_filter( 'bb_admin_settings_format_field_data', 'bb_access_control_enrich_field_data', 10, 3 );
}
