<?php
/**
 * BuddyBoss Compatibility Integration Loader.
 *
 * @since BuddyBoss 1.1.5
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Prints out all settings sections added to a particular settings page
 *
 * Part of the Settings API. Use this in a settings page callback function
 * to output all the sections and fields that were added to that $page with
 * add_settings_section() and add_settings_field()
 *
 * @global $wp_settings_sections Storage array of all settings sections added to admin pages.
 * @global $wp_settings_fields Storage array of settings fields and info about their pages/sections.
 * @since BuddyBoss 1.1.6
 *
 * @param string $page The slug name of the page whose settings sections you want to output.
 */
function bboss_core_compatibility_do_settings_sections( $page ) {
	global $wp_settings_sections, $wp_settings_fields;

	if ( ! isset( $wp_settings_sections[ $page ] ) ) {
		return;
	}

	foreach ( (array) $wp_settings_sections[ $page ] as $section ) {

		if ( isset( $wp_settings_fields[ $page ][ $section['id'] ] ) ) {

			if ( $section['title'] ) {
				echo '<h3>' . esc_html( $section['title'] ) . "</h3>\n";
			}

			if ( $section['callback'] ) {
				call_user_func( $section['callback'], $section );
			}

			if ( ! isset( $wp_settings_fields ) || ! isset( $wp_settings_fields[ $page ] ) || ! isset( $wp_settings_fields[ $page ][ $section['id'] ] ) ) {
				continue;
			}

			echo '<table class="form-table">';
			do_settings_fields( $page, $section['id'] );
			echo '</table>';
		}
	}
}

/**
 * Set up the bp compatibility integration.
 *
 * @since BuddyBoss 1.1.5
 */
function bboss_register_compatibility_integration() {
	require_once dirname( __FILE__ ) . '/bboss-compatibility-integration.php';
	bboss()->integrations['compatibility'] = new BBoss_Compatibility_Integration();
}
add_action( 'bp_setup_integrations', 'bp_register_compatibility_integration' );

// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename names of this file's symbols (decision 0013): declared
// here, guarded on the renamed declaration, so each old name exists exactly when the new one does.
if ( function_exists( 'bboss_core_compatibility_do_settings_sections' ) && ! function_exists( 'bp_core_compatibility_do_settings_sections' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_core_compatibility_do_settings_sections()}.
	 *
	 * @deprecated Use bboss_core_compatibility_do_settings_sections() instead.
	 */
	function bp_core_compatibility_do_settings_sections( $page ) {
		return bboss_core_compatibility_do_settings_sections( $page );
	}
}
if ( function_exists( 'bboss_register_compatibility_integration' ) && ! function_exists( 'bp_register_compatibility_integration' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_register_compatibility_integration()}.
	 *
	 * @deprecated Use bboss_register_compatibility_integration() instead.
	 */
	function bp_register_compatibility_integration() {
		return bboss_register_compatibility_integration();
	}
}
// @bbwt-com-end compat-layer
