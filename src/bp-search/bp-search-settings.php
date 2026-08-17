<?php
/**
 * BuddyBoss Search Settings
 *
 * Public API functions for checking search settings state. These functions are
 * used by the search engine, templates, REST endpoints, and forums filters.
 *
 * Legacy WordPress Settings API sections, fields, and callbacks have been
 * removed — Search settings are now managed by Settings 2.0 (React admin UI).
 *
 * @package BuddyBoss\Search
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Output settings API option
 *
 * @since BuddyBoss 1.0.0
 *
 * @param string $option
 * @param string $default
 * @param bool   $slug
 */
function bp_search_form_option( $option, $default = '', $slug = false ) {
	echo bp_search_get_form_option( $option, $default, $slug ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bp_search_get_form_option() returns an esc_attr'd value.
}

/**
 * Return settings API option
 *
 * @since BuddyBoss 1.0.0
 *
 * @uses get_option()
 * @uses esc_attr()
 * @uses apply_filters()
 *
 * @param string $option
 * @param string $default
 * @param bool   $slug
 *
 * @return mixed
 */
function bp_search_get_form_option( $option, $default = '', $slug = false ) {

	// Get the option and sanitize it
	$value = get_option( $option, $default );

	// Slug?
	if ( true === $slug ) {
		$value = esc_attr( apply_filters( 'editable_slug', $value ) );

		// Not a slug
	} else {
		$value = esc_attr( $value );
	}

	// Fallback to default
	if ( empty( $value ) ) {
		$value = $default;
	}

	// Allow plugins to further filter the output
	return apply_filters( 'bp_search_get_form_option', $value, $option );
}

/**
 * Checks if search autocomplete feature is enabled.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $default bool Optional.Default value true
 *
 * @uses get_option() To get the bp_search_autocomplete option
 * @return bool Is search autocomplete enabled or not
 */
function bp_is_search_autocomplete_enable( $default = 1 ) {
	return (bool) apply_filters( 'bp_is_search_autocomplete_enable', (bool) get_option( 'bp_search_autocomplete', $default ) );
}

/**
 * Checks if members search feature is enabled.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $default bool Optional.Default value true
 *
 * @uses get_option() To get the bp_search_members option
 * @return bool Is members search enabled or not
 */
function bp_is_search_members_enable( $default = 1 ) {
	return (bool) apply_filters( 'bp_is_search_members_enable', (bool) get_option( 'bp_search_members', $default ) );
}

/**
 * Checks if xprofile field search is enabled.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $id integer
 *
 * @uses get_option() To get the bp_search_members option
 * @return bool Is members search enabled or not
 */
function bp_is_search_xprofile_enable( $id ) {
	return (bool) apply_filters( 'bp_is_search_xprofile_enable', (bool) get_option( "bp_search_xprofile_$id" ) );
}

/**
 * Checks if xprofile field search is enabled.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $id integer
 *
 * @uses get_option() To get the bp_search_members option
 * @return bool Is members search enabled or not
 */
function bp_is_search_user_field_enable( $id ) {
	return (bool) apply_filters( 'bp_is_search_user_field_enable', (bool) get_option( "bp_search_user_field_$id" ) );
}

/**
 * Checks if post type search is enabled.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $post_type string
 *
 * @return bool Is members search enabled or not
 */
function bp_is_search_post_type_enable( $post_type, $default = 1 ) {
	return (bool) apply_filters( 'bp_is_search_post_type_enable', (bool) get_option( "bp_search_post_type_$post_type", $default ) );
}

/**
 * Checks if post type Meta search is enabled.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $post_type string
 *
 * @return bool Is post type meta search enabled or not
 */
function bp_is_search_post_type_meta_enable( $post_type ) {
	return (bool) apply_filters( 'bp_is_search_post_type_meta_enable', (bool) get_option( "bp_search_post_type_meta_$post_type" ) );
}

/**
 * Checks if groups search is enabled.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $default integer
 *
 * @return bool Is groups search enabled or not
 */
function bp_is_search_groups_enable( $default = 1 ) {
	return (bool) apply_filters( 'bp_is_search_groups_enable', (bool) get_option( 'bp_search_groups', $default ) );
}

/**
 * Checks if photos search is enabled.
 *
 * @since BuddyBoss 1.5.0
 *
 * @param $default integer
 *
 * @return bool Is media search enabled or not
 */
function bp_is_search_photos_enable( $default = 0 ) {
	return (bool) apply_filters( 'bp_is_search_photos_enable', (bool) get_option( 'bp_search_photos', $default ) );
}

/**
 * Checks if albums search is enabled.
 *
 * @since BuddyBoss 1.5.0
 *
 * @param $default integer
 *
 * @return bool Is albums search enabled or not
 */
function bp_is_search_albums_enable( $default = 0 ) {
	return (bool) apply_filters( 'bp_is_search_albums_enable', (bool) get_option( 'bp_search_albums', $default ) );
}

/**
 * Checks if video search is enabled.
 *
 * @since BuddyBoss 1.7.0
 *
 * @param int $default whether video search enabled or not.
 *
 * @return bool Is video media search enabled or not.
 */
function bp_is_search_videos_enable( $default = 0 ) {
	return (bool) apply_filters( 'bp_is_search_videos_enable', (bool) get_option( 'bp_search_videos', $default ) );
}

/**
 * Checks if document search is enabled.
 *
 * @since BuddyBoss 1.4.0
 *
 * @param $default integer
 *
 * @return bool Is documents search enabled or not
 */
function bp_is_search_documents_enable( $default = 0 ) {
	return (bool) apply_filters( 'bp_is_search_documents_enable', (bool) get_option( 'bp_search_documents', $default ) );
}

/**
 * Checks if folder search is enabled.
 *
 * @since BuddyBoss 1.4.0
 *
 * @param $default integer
 *
 * @return bool Is documents search enabled or not
 */
function bp_is_search_folders_enable( $default = 0 ) {
	return (bool) apply_filters( 'bp_is_search_folders_enable', (bool) get_option( 'bp_search_folders', $default ) );
}

/**
 * Checks if Activity search is enabled.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $default integer
 *
 * @return bool Is Activity search enabled or not
 */
function bp_is_search_activity_enable( $default = 1 ) {
	return (bool) apply_filters( 'bp_is_search_activity_enable', (bool) get_option( 'bp_search_activity', $default ) );
}

/**
 * Checks if Activity Comments search is enabled.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $default integer
 *
 * @return bool Is Activity Comments search enabled or not
 */
function bp_is_search_activity_comments_enable( $default = 1 ) {
	return (bool) apply_filters( 'bp_is_search_activity_comments_enable', (bool) get_option( 'bp_search_activity_comments', $default ) );
}

/**
 * Checks if post type Taxonomy search is enabled.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $post_type string post type name.
 * @param $taxonomy  string taxonomy name.
 *
 * @return bool Is post type Taxonomy search enabled or not
 */
function bp_is_search_post_type_taxonomy_enable( $taxonomy, $post_type ) {
	return (bool) apply_filters( 'bp_is_search_post_type_taxonomy_enable', (bool) get_option( "bp_search_{$post_type}_tax_{$taxonomy}" ) );
}
