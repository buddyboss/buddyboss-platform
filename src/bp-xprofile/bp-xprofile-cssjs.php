<?php
/**
 * BuddyPress XProfile CSS and JS.
 *
 * @package BuddyBoss
 * @subpackage XProfileScripts
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
	if ( !empty( $_GET['page'] ) && strpos( $_GET['page'], 'bp-profile-setup' ) !== false ) {
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
 * @since BuddyBoss 3.1.1 Removed autolink strings as autolink functionality is no longer used.
 */
function xprofile_add_admin_js() {
	if ( !empty( $_GET['page'] ) && strpos( $_GET['page'], 'bp-profile-setup' ) !== false ) {
		wp_enqueue_script( 'jquery-ui-core'      );
		wp_enqueue_script( 'jquery-ui-tabs'      );
		wp_enqueue_script( 'jquery-ui-mouse'     );
		wp_enqueue_script( 'jquery-ui-draggable' );
		wp_enqueue_script( 'jquery-ui-droppable' );
		wp_enqueue_script( 'jquery-ui-sortable'  );

		$min = bp_core_get_minified_asset_suffix();
		wp_enqueue_script( 'xprofile-admin-js', buddypress()->plugin_url . "bp-xprofile/admin/js/admin{$min}.js", array( 'jquery', 'jquery-ui-sortable' ), bp_get_version() );

		// Localize strings.
		// supports_options_field_types is a dynamic list of field
		// types that support options, for use in showing/hiding the
		// "please enter options for this field" section.
		$strings = array(
			'do_settings_section_field_types' => array(),
		);

		foreach ( bp_xprofile_get_field_types() as $field_type => $field_type_class ) {
			$field = new $field_type_class();
			if ( $field->do_settings_section() ) {
				$strings['do_settings_section_field_types'][] = $field_type;
			}
		}

		// Load 'autolink' setting into JS so that we can provide smart defaults when switching field type.
		if ( ! empty( $_GET['field_id'] ) ) {
			$field_id = intval( $_GET['field_id'] );
		}

		wp_localize_script( 'xprofile-admin-js', 'XProfileAdmin', $strings );
	}
}
add_action( 'bp_admin_enqueue_scripts', 'xprofile_add_admin_js', 1 );
