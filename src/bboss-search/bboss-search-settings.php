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
function bboss_search_form_option( $option, $default = '', $slug = false ) {
	echo esc_attr( bboss_search_get_form_option( $option, $default, $slug ) );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_search_form_option' ) && ! function_exists( 'bp_search_form_option' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_search_form_option()}.
	 *
	 * @deprecated Use bboss_search_form_option() instead.
	 */
	function bp_search_form_option( $option, $default = '', $slug = false ) {
		return bboss_search_form_option( $option, $default, $slug );
	}
}
// @bbwt-com-end compat-layer


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
function bboss_search_get_form_option( $option, $default = '', $slug = false ) {

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
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_search_get_form_option' ) && ! function_exists( 'bp_search_get_form_option' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_search_get_form_option()}.
	 *
	 * @deprecated Use bboss_search_get_form_option() instead.
	 */
	function bp_search_get_form_option( $option, $default = '', $slug = false ) {
		return bboss_search_get_form_option( $option, $default, $slug );
	}
}
// @bbwt-com-end compat-layer


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
function bboss_is_search_autocomplete_enable( $default = 1 ) {
	return (bool) apply_filters( 'bp_is_search_autocomplete_enable', (bool) get_option( 'bp_search_autocomplete', $default ) );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_is_search_autocomplete_enable' ) && ! function_exists( 'bp_is_search_autocomplete_enable' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_is_search_autocomplete_enable()}.
	 *
	 * @deprecated Use bboss_is_search_autocomplete_enable() instead.
	 */
	function bp_is_search_autocomplete_enable( $default = 1 ) {
		return bboss_is_search_autocomplete_enable( $default );
	}
}
// @bbwt-com-end compat-layer


/**
 * Checks if members search feature is enabled.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $default bool Optional.Default value true
 *
 * @uses get_option() To get the BBoss_Search_Members option
 * @return bool Is members search enabled or not
 */
function bboss_is_search_members_enable( $default = 1 ) {
	return (bool) apply_filters( 'bp_is_search_members_enable', (bool) get_option( 'bp_search_members', $default ) );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_is_search_members_enable' ) && ! function_exists( 'bp_is_search_members_enable' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_is_search_members_enable()}.
	 *
	 * @deprecated Use bboss_is_search_members_enable() instead.
	 */
	function bp_is_search_members_enable( $default = 1 ) {
		return bboss_is_search_members_enable( $default );
	}
}
// @bbwt-com-end compat-layer


/**
 * Checks if xprofile field search is enabled.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $id integer
 *
 * @uses get_option() To get the BBoss_Search_Members option
 * @return bool Is members search enabled or not
 */
function bboss_is_search_xprofile_enable( $id ) {
	return (bool) apply_filters( 'bp_is_search_xprofile_enable', (bool) get_option( "bp_search_xprofile_$id" ) );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_is_search_xprofile_enable' ) && ! function_exists( 'bp_is_search_xprofile_enable' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_is_search_xprofile_enable()}.
	 *
	 * @deprecated Use bboss_is_search_xprofile_enable() instead.
	 */
	function bp_is_search_xprofile_enable( $id ) {
		return bboss_is_search_xprofile_enable( $id );
	}
}
// @bbwt-com-end compat-layer


/**
 * Checks if xprofile field search is enabled.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $id integer
 *
 * @uses get_option() To get the BBoss_Search_Members option
 * @return bool Is members search enabled or not
 */
function bboss_is_search_user_field_enable( $id ) {
	return (bool) apply_filters( 'bp_is_search_user_field_enable', (bool) get_option( "bp_search_user_field_$id" ) );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_is_search_user_field_enable' ) && ! function_exists( 'bp_is_search_user_field_enable' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_is_search_user_field_enable()}.
	 *
	 * @deprecated Use bboss_is_search_user_field_enable() instead.
	 */
	function bp_is_search_user_field_enable( $id ) {
		return bboss_is_search_user_field_enable( $id );
	}
}
// @bbwt-com-end compat-layer


/**
 * Checks if post type search is enabled.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $post_type string
 *
 * @return bool Is members search enabled or not
 */
function bboss_is_search_post_type_enable( $post_type, $default = 1 ) {
	return (bool) apply_filters( 'bp_is_search_post_type_enable', (bool) get_option( "bp_search_post_type_$post_type", $default ) );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_is_search_post_type_enable' ) && ! function_exists( 'bp_is_search_post_type_enable' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_is_search_post_type_enable()}.
	 *
	 * @deprecated Use bboss_is_search_post_type_enable() instead.
	 */
	function bp_is_search_post_type_enable( $post_type, $default = 1 ) {
		return bboss_is_search_post_type_enable( $post_type, $default );
	}
}
// @bbwt-com-end compat-layer


/**
 * Checks if post type Meta search is enabled.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $post_type string
 *
 * @return bool Is post type meta search enabled or not
 */
function bboss_is_search_post_type_meta_enable( $post_type ) {
	return (bool) apply_filters( 'bp_is_search_post_type_meta_enable', (bool) get_option( "bp_search_post_type_meta_$post_type" ) );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_is_search_post_type_meta_enable' ) && ! function_exists( 'bp_is_search_post_type_meta_enable' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_is_search_post_type_meta_enable()}.
	 *
	 * @deprecated Use bboss_is_search_post_type_meta_enable() instead.
	 */
	function bp_is_search_post_type_meta_enable( $post_type ) {
		return bboss_is_search_post_type_meta_enable( $post_type );
	}
}
// @bbwt-com-end compat-layer


/**
 * Checks if groups search is enabled.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $default integer
 *
 * @return bool Is groups search enabled or not
 */
function bboss_is_search_groups_enable( $default = 1 ) {
	return (bool) apply_filters( 'bp_is_search_groups_enable', (bool) get_option( 'bp_search_groups', $default ) );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_is_search_groups_enable' ) && ! function_exists( 'bp_is_search_groups_enable' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_is_search_groups_enable()}.
	 *
	 * @deprecated Use bboss_is_search_groups_enable() instead.
	 */
	function bp_is_search_groups_enable( $default = 1 ) {
		return bboss_is_search_groups_enable( $default );
	}
}
// @bbwt-com-end compat-layer


/**
 * Checks if photos search is enabled.
 *
 * @since BuddyBoss 1.5.0
 *
 * @param $default integer
 *
 * @return bool Is media search enabled or not
 */
function bboss_is_search_photos_enable( $default = 0 ) {
	return (bool) apply_filters( 'bp_is_search_photos_enable', (bool) get_option( 'bp_search_photos', $default ) );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_is_search_photos_enable' ) && ! function_exists( 'bp_is_search_photos_enable' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_is_search_photos_enable()}.
	 *
	 * @deprecated Use bboss_is_search_photos_enable() instead.
	 */
	function bp_is_search_photos_enable( $default = 0 ) {
		return bboss_is_search_photos_enable( $default );
	}
}
// @bbwt-com-end compat-layer


/**
 * Checks if albums search is enabled.
 *
 * @since BuddyBoss 1.5.0
 *
 * @param $default integer
 *
 * @return bool Is albums search enabled or not
 */
function bboss_is_search_albums_enable( $default = 0 ) {
	return (bool) apply_filters( 'bp_is_search_albums_enable', (bool) get_option( 'bp_search_albums', $default ) );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_is_search_albums_enable' ) && ! function_exists( 'bp_is_search_albums_enable' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_is_search_albums_enable()}.
	 *
	 * @deprecated Use bboss_is_search_albums_enable() instead.
	 */
	function bp_is_search_albums_enable( $default = 0 ) {
		return bboss_is_search_albums_enable( $default );
	}
}
// @bbwt-com-end compat-layer


/**
 * Checks if video search is enabled.
 *
 * @since BuddyBoss 1.7.0
 *
 * @param int $default whether video search enabled or not.
 *
 * @return bool Is video media search enabled or not.
 */
function bboss_is_search_videos_enable( $default = 0 ) {
	return (bool) apply_filters( 'bp_is_search_videos_enable', (bool) get_option( 'bp_search_videos', $default ) );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_is_search_videos_enable' ) && ! function_exists( 'bp_is_search_videos_enable' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_is_search_videos_enable()}.
	 *
	 * @deprecated Use bboss_is_search_videos_enable() instead.
	 */
	function bp_is_search_videos_enable( $default = 0 ) {
		return bboss_is_search_videos_enable( $default );
	}
}
// @bbwt-com-end compat-layer


/**
 * Checks if document search is enabled.
 *
 * @since BuddyBoss 1.4.0
 *
 * @param $default integer
 *
 * @return bool Is documents search enabled or not
 */
function bboss_is_search_documents_enable( $default = 0 ) {
	return (bool) apply_filters( 'bp_is_search_documents_enable', (bool) get_option( 'bp_search_documents', $default ) );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_is_search_documents_enable' ) && ! function_exists( 'bp_is_search_documents_enable' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_is_search_documents_enable()}.
	 *
	 * @deprecated Use bboss_is_search_documents_enable() instead.
	 */
	function bp_is_search_documents_enable( $default = 0 ) {
		return bboss_is_search_documents_enable( $default );
	}
}
// @bbwt-com-end compat-layer


/**
 * Checks if folder search is enabled.
 *
 * @since BuddyBoss 1.4.0
 *
 * @param $default integer
 *
 * @return bool Is documents search enabled or not
 */
function bboss_is_search_folders_enable( $default = 0 ) {
	return (bool) apply_filters( 'bp_is_search_folders_enable', (bool) get_option( 'bp_search_folders', $default ) );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_is_search_folders_enable' ) && ! function_exists( 'bp_is_search_folders_enable' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_is_search_folders_enable()}.
	 *
	 * @deprecated Use bboss_is_search_folders_enable() instead.
	 */
	function bp_is_search_folders_enable( $default = 0 ) {
		return bboss_is_search_folders_enable( $default );
	}
}
// @bbwt-com-end compat-layer


/**
 * Checks if Activity search is enabled.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $default integer
 *
 * @return bool Is Activity search enabled or not
 */
function bboss_is_search_activity_enable( $default = 1 ) {
	return (bool) apply_filters( 'bp_is_search_activity_enable', (bool) get_option( 'bp_search_activity', $default ) );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_is_search_activity_enable' ) && ! function_exists( 'bp_is_search_activity_enable' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_is_search_activity_enable()}.
	 *
	 * @deprecated Use bboss_is_search_activity_enable() instead.
	 */
	function bp_is_search_activity_enable( $default = 1 ) {
		return bboss_is_search_activity_enable( $default );
	}
}
// @bbwt-com-end compat-layer


/**
 * Checks if Activity Comments search is enabled.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $default integer
 *
 * @return bool Is Activity Comments search enabled or not
 */
function bboss_is_search_activity_comments_enable( $default = 1 ) {
	return (bool) apply_filters( 'bp_is_search_activity_comments_enable', (bool) get_option( 'bp_search_activity_comments', $default ) );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_is_search_activity_comments_enable' ) && ! function_exists( 'bp_is_search_activity_comments_enable' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_is_search_activity_comments_enable()}.
	 *
	 * @deprecated Use bboss_is_search_activity_comments_enable() instead.
	 */
	function bp_is_search_activity_comments_enable( $default = 1 ) {
		return bboss_is_search_activity_comments_enable( $default );
	}
}
// @bbwt-com-end compat-layer


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
function bboss_is_search_post_type_taxonomy_enable( $taxonomy, $post_type ) {
	return (bool) apply_filters( 'bp_is_search_post_type_taxonomy_enable', (bool) get_option( "bp_search_{$post_type}_tax_{$taxonomy}" ) );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_is_search_post_type_taxonomy_enable' ) && ! function_exists( 'bp_is_search_post_type_taxonomy_enable' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_is_search_post_type_taxonomy_enable()}.
	 *
	 * @deprecated Use bboss_is_search_post_type_taxonomy_enable() instead.
	 */
	function bp_is_search_post_type_taxonomy_enable( $taxonomy, $post_type ) {
		return bboss_is_search_post_type_taxonomy_enable( $taxonomy, $post_type );
	}
}
// @bbwt-com-end compat-layer

