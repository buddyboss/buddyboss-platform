<?php
/**
 * BuddyPress XProfile CSS and JS.
 *
 * @package BuddyBoss\XProfile
 * @since BuddyPress 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Enqueue the CSS for XProfile admin styling.
 *
 * @since BuddyPress 1.1.0
 */
function xprofile_add_admin_css() {
	if ( ! empty( $_GET['page'] ) && strpos( $_GET['page'], 'bp-profile-setup' ) !== false ) {
		$min = bp_core_get_minified_asset_suffix();

		wp_enqueue_style( 'xprofile-admin-css', buddypress()->plugin_url . "bp-xprofile/admin/css/admin{$min}.css", array(), bp_get_version() );

		wp_style_add_data( 'xprofile-admin-css', 'rtl', true );
		if ( $min ) {
			wp_style_add_data( 'xprofile-admin-css', 'suffix', $min );
		}
	}
}
add_action( 'bp_admin_enqueue_scripts', 'xprofile_add_admin_css' );

/**
 * Enqueue the jQuery libraries for handling drag/drop/sort.
 *
 * @since BuddyPress 1.5.0
 * @since BuddyBoss 1.0.0
 * Removed autolink strings as autolink functionality is no longer used.
 */
function xprofile_add_admin_js() {
	if ( ! empty( $_GET['page'] ) && strpos( $_GET['page'], 'bp-profile-setup' ) !== false ) {
		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script( 'jquery-ui-tabs' );
		wp_enqueue_script( 'jquery-ui-mouse' );
		wp_enqueue_script( 'jquery-ui-draggable' );
		wp_enqueue_script( 'jquery-ui-droppable' );
		wp_enqueue_script( 'jquery-ui-sortable' );

		$min = bp_core_get_minified_asset_suffix();
		wp_enqueue_script( 'xprofile-admin-js', buddypress()->plugin_url . "bp-xprofile/admin/js/admin{$min}.js", array( 'jquery', 'jquery-ui-sortable' ), bp_get_version() );

		// Localize strings.
		// supports_options_field_types is a dynamic list of field
		// types that support options, for use in showing/hiding the
		// "please enter options for this field" section.
		$strings = array(
			'do_settings_section_field_types'         => array(),
			'social_networks_provider'                => array(),
			'social_networks_provider_value'          => array(),
			'social_networks_duplicate_value_message' => __( 'You have already selected this option previously.', 'buddyboss' ),
			'confirm_delete_field'                    => __( 'Are you sure you want to delete this field?', 'buddyboss' ),
			'confirm_delete_field_group'              => __( 'Are you sure you want to delete this field set and all of its included fields?', 'buddyboss' ),
			'social_networks_provider_count'          => 0,
		);

		foreach ( bp_xprofile_get_field_types() as $field_type => $field_type_class ) {
			$field = new $field_type_class();
			if ( $field->do_settings_section() ) {
				$strings['do_settings_section_field_types'][] = $field_type;
			}
		}

		$providers                                 = bp_xprofile_social_network_provider();
		$strings['social_networks_provider_count'] = count( $providers );
		foreach ( $providers as $provider ) {
			$strings['social_networks_provider'][]       = $provider->name;
			$strings['social_networks_provider_value'][] = $provider->value;
		}

		// Load 'autolink' setting into JS so that we can provide smart defaults when switching field type.
		if ( ! empty( $_GET['field_id'] ) ) {
			$field_id = intval( $_GET['field_id'] );
		}

		wp_localize_script( 'xprofile-admin-js', 'XProfileAdmin', $strings );
	}

	if ( ! empty( $_GET['page'] ) && strpos( $_GET['page'], 'bp-profile-edit' ) !== false ) {
		$min = bp_core_get_minified_asset_suffix();
		wp_enqueue_script( 'jquery-mask', buddypress()->plugin_url . "bp-core/js/vendor/jquery.mask{$min}.js", array( 'jquery' ), '1.14.15' );
	}
}
add_action( 'bp_admin_enqueue_scripts', 'xprofile_add_admin_js', 1 );
