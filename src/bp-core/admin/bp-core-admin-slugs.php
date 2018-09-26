<?php
/**
 * BuddyPress Admin Slug Functions.
 *
 * @package BuddyBoss
 * @subpackage CoreAdministration
 * @since BuddyPress 2.3.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Generate a list of directory pages, for use when building Components panel markup.
 *
 * @since BuddyPress 2.4.1
 *
 * @return array
 */
function bp_core_admin_get_directory_pages() {
	$bp = buddypress();
	$directory_pages = array();

	// Loop through loaded components and collect directories.
	if ( is_array( $bp->loaded_components ) ) {
		foreach( $bp->loaded_components as $component_slug => $component_id ) {

			// Only components that need directories should be listed here.
			if ( isset( $bp->{$component_id} ) && !empty( $bp->{$component_id}->has_directory ) ) {

				// The component->name property was introduced in BP 1.5, so we must provide a fallback.
				$directory_pages[$component_id] = !empty( $bp->{$component_id}->name ) ? $bp->{$component_id}->name : ucwords( $component_id );
			}
		}
	}

	/** Directory Display *****************************************************/

	/**
	 * Filters the loaded components needing directory page association to a WordPress page.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param array $directory_pages Array of available components to set associations for.
	 */
	return apply_filters( 'bp_directory_pages', $directory_pages );
}

/**
 * Generate a list of static pages, for use when building Components panel markup.
 *
 * By default, this list contains 'register' and 'activate'.
 *
 * @since BuddyPress 2.4.1
 *
 * @return array
 */
function bp_core_admin_get_static_pages() {
	$static_pages = array(
		'register' => __( 'Register Form', 'buddyboss' ),
		'activate' => __( 'Activation', 'buddyboss' ),
	);

	/**
	 * Filters the default static pages for BuddyPress setup.
	 *
	 * @since BuddyPress 1.6.0
	 *
	 * @param array $static_pages Array of static default static pages.
	 */
	return apply_filters( 'bp_static_pages', $static_pages );
}

/**
 * Handle saving of the BuddyPress slugs.
 *
 * @since BuddyPress 1.6.0
 * @todo Use settings API
 */
function bp_core_admin_slugs_setup_handler() {

	if ( isset( $_POST['bp-admin-pages-submit'] ) ) {
		if ( !check_admin_referer( 'bp-admin-pages-setup' ) )
			return false;

		// Then, update the directory pages.
		if ( isset( $_POST['bp_pages'] ) ) {
			$valid_pages = array_merge( bp_core_admin_get_directory_pages(), bp_core_admin_get_static_pages() );

			$new_directory_pages = array();
			foreach ( (array) $_POST['bp_pages'] as $key => $value ) {
				if ( isset( $valid_pages[ $key ] ) ) {
					$new_directory_pages[ $key ] = (int) $value;
				}
			}
			bp_core_update_directory_page_ids( $new_directory_pages );
		}

		$base_url = bp_get_admin_url( add_query_arg( array( 'page' => 'bp-page-settings', 'updated' => 'true' ), 'admin.php' ) );

		wp_redirect( $base_url );
	}
}
add_action( 'bp_admin_init', 'bp_core_admin_slugs_setup_handler' );
