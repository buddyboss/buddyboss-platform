<?php
/**
 * BuddyPress Admin Slug Functions.
 *
 * @package BuddyBoss\Core\Administration
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
	$bp              = buddypress();
	$directory_pages = array();

	// Loop through loaded components and collect directories.
	if ( is_array( $bp->loaded_components ) ) {
		foreach ( $bp->loaded_components as $component_slug => $component_id ) {

			// Only components that need directories should be listed here.
			if ( isset( $bp->{$component_id} ) && ! empty( $bp->{$component_id}->has_directory ) ) {

				// The component->name property was introduced in BP 1.5, so we must provide a fallback.
				$directory_pages[ $component_id ] = ! empty( $bp->{$component_id}->name ) ? $bp->{$component_id}->name : ucwords( $component_id );
			}
		}
	}

	if ( function_exists( 'bp_nouveau_get_appearance_settings' ) ) {
		if ( bp_nouveau_get_appearance_settings( 'user_front_page' ) ) {
			$directory_pages['profile_dashboard'] = __( 'Profile Dashboard', 'buddyboss' );
		}
	}

	// Add new Forums option into the Pages.
	if ( bp_is_active( 'forums' ) ) {

		if ( bp_is_active( 'groups' ) ) {
			$directory_pages = array_insert_after( $directory_pages, 'groups', array( 'new_forums_page' => __( 'Forums', 'buddyboss' ) ) );
		} else {
			$directory_pages = array_insert_after( $directory_pages, 'members', array( 'new_forums_page' => __( 'Forums', 'buddyboss' ) ) );
		}
	}

	if ( bp_is_active( 'media' ) && ( bp_is_profile_document_support_enabled() || bp_is_forums_document_support_enabled() || bp_is_group_document_support_enabled() || bp_is_messages_document_support_enabled() ) ) {
		$directory_pages['document'] = __( 'Documents', 'buddyboss' );
	}

	if ( bp_is_active( 'media' ) && function_exists( 'bp_is_profile_video_support_enabled' ) &&  ( bp_is_profile_video_support_enabled() || bp_is_forums_video_support_enabled() || bp_is_group_video_support_enabled() || bp_is_messages_video_support_enabled() ) ) {
		$directory_pages['video'] = __( 'Videos', 'buddyboss' );
	}

	/** Directory Display */

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
 * Insert a value or key/value pair after a specific key in an array.  If key doesn't exist, value is appended
 * to the end of the array.
 *
 * @param array  $array
 * @param string $key
 * @param array  $new
 *
 * @return array
 */
function array_insert_after( array $array, $key, array $new ) {
	$keys  = array_keys( $array );
	$index = array_search( $key, $keys );
	$pos   = false === $index ? count( $array ) : $index + 1;
	return array_merge( array_slice( $array, 0, $pos ), $new, array_slice( $array, $pos ) );
}

/**
 * Generate a list of static pages, for use when building Components panel markup.
 *
 * By default, this list contains 'register', 'activate', 'terms' & 'privacy'.
 *
 * @since BuddyPress 2.4.1
 *
 * @return array
 */
function bp_core_admin_get_static_pages() {
	$static_pages = array(
		'register' => __( 'Register Form', 'buddyboss' ),
		'terms'    => __( 'Terms of Service', 'buddyboss' ),
		'privacy'  => __( 'Privacy Policy', 'buddyboss' ),
		'activate' => __( 'Activate Account', 'buddyboss' ),
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
// function bp_core_admin_slugs_setup_handler() {

// if ( isset( $_POST['bp-admin-pages-submit'] ) ) {
// if ( !check_admin_referer( 'bp-admin-pages-setup' ) )
// return false;

// Then, update the directory pages.
// if ( isset( $_POST['bp_pages'] ) ) {
// $valid_pages = array_merge( bp_core_admin_get_directory_pages(), bp_core_admin_get_static_pages() );

// $new_directory_pages = array();
// foreach ( (array) $_POST['bp_pages'] as $key => $value ) {
// if ( isset( $valid_pages[ $key ] ) ) {
// $new_directory_pages[ $key ] = (int) $value;
// }
// }
// bp_core_update_directory_page_ids( $new_directory_pages );
// }

// $base_url = bp_get_admin_url( add_query_arg( array( 'page' => 'bp-page-settings', 'updated' => 'true' ), 'admin.php' ) );

// wp_redirect( $base_url );
// }
// }
// add_action( 'bp_admin_init', 'bp_core_admin_slugs_setup_handler' );
