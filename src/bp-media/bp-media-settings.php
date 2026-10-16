<?php
/**
 * Media Settings
 *
 * @package BuddyBoss\Media
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Checks if media profile media support is enabled.
 *
 * @param $default integer
 *
 * @return bool Is media profile media support enabled or not
 * @since BuddyBoss 1.0.0
 */
function bp_is_profile_media_support_enabled( $default = 1 ) {
	return (bool) apply_filters( 'bp_is_profile_media_support_enabled', (bool) get_option( 'bp_media_profile_media_support', $default ) );
}

/**
 * Checks if media profile albums support is enabled.
 *
 * @param $default integer
 *
 * @return bool Is media profile albums support enabled or not
 * @since BuddyBoss 1.0.0
 */
function bp_is_profile_albums_support_enabled( $default = 1 ) {
	return (bool) apply_filters( 'bp_is_profile_albums_support_enabled', (bool) get_option( 'bp_media_profile_albums_support', $default ) );
}

/**
 * Checks if media group media support is enabled.
 *
 * @param $default integer
 *
 * @return bool Is media group media support enabled or not
 * @since BuddyBoss 1.0.0
 */
function bp_is_group_media_support_enabled( $default = 1 ) {
	return (bool) apply_filters( 'bp_is_group_media_support_enabled', (bool) get_option( 'bp_media_group_media_support', $default ) );
}

/**
 * Checks if media group album support is enabled.
 *
 * @param $default integer
 *
 * @return bool Is media group album support enabled or not
 * @since BuddyBoss 1.0.0
 */
function bp_is_group_albums_support_enabled( $default = 1 ) {
	return (bool) apply_filters( 'bp_is_group_albums_support_enabled', (bool) get_option( 'bp_media_group_albums_support', $default ) );
}

/**
 * Checks if media messages media support is enabled.
 *
 * @param $default integer
 *
 * @return bool Is media messages media support enabled or not
 * @since BuddyBoss 1.0.0
 */
function bp_is_messages_media_support_enabled( $default = 0 ) {
	return (bool) apply_filters( 'bp_is_messages_media_support_enabled', (bool) get_option( 'bp_media_messages_media_support', $default ) );
}

/**
 * Checks if media forums media support is enabled.
 *
 * @param $default integer
 *
 * @return bool Is media forums media support enabled or not
 * @since BuddyBoss 1.0.0
 */
function bp_is_forums_media_support_enabled( $default = 0 ) {
	return (bool) apply_filters( 'bp_is_forums_media_support_enabled', (bool) get_option( 'bp_media_forums_media_support', $default ) );
}

/**
 * Checks if media emoji support is enabled in profiles.
 *
 * @param int $default default value.
 *
 * @return bool Is media emoji support enabled or not in profiles
 * @since BuddyBoss 1.0.0
 */
function bp_is_profiles_emoji_support_enabled( $default = 0 ) {
	return (bool) apply_filters( 'bp_is_profiles_emoji_support_enabled', (bool) get_option( 'bp_media_profiles_emoji_support', $default ) );
}

/**
 * Checks if media emoji support is enabled in groups.
 *
 * @param int $default default value.
 *
 * @return bool Is media emoji support enabled or not in groups
 * @since BuddyBoss 1.0.0
 */
function bp_is_groups_emoji_support_enabled( $default = 0 ) {
	return (bool) apply_filters( 'bp_is_groups_emoji_support_enabled', (bool) get_option( 'bp_media_groups_emoji_support', $default ) );
}

/**
 * Checks if media emoji support is enabled in messages.
 *
 * @param int $default default value.
 *
 * @return bool Is media emoji support enabled or not in messages
 * @since BuddyBoss 1.0.0
 */
function bp_is_messages_emoji_support_enabled( $default = 0 ) {
	return (bool) apply_filters( 'bp_is_messages_emoji_support_enabled', (bool) get_option( 'bp_media_messages_emoji_support', $default ) );
}

/**
 * Checks if media emoji support is enabled in forums.
 *
 * @param int $default default value.
 *
 * @return bool Is media emoji support enabled or not in forums
 * @since BuddyBoss 1.0.0
 */
function bp_is_forums_emoji_support_enabled( $default = 0 ) {
	return (bool) apply_filters( 'bp_is_forums_emoji_support_enabled', (bool) get_option( 'bp_media_forums_emoji_support', $default ) );
}

/**
 * Whether a GIPHY integration provider is available to power the GIF feature.
 *
 * The provider is the code that saves, renders and deletes GIF attachments
 * (`bp_media_activity_embed_gif()` and friends). Historically it ships inside
 * the Platform's media component; it is being extracted to the BuddyBoss
 * Addons plugin, whose copy keeps the same function names. When neither is
 * loaded, every `bp_is_*_gif_support_enabled()` gate returns false (via
 * {@see bb_media_gifs_force_disable()}), so GIF UI never renders against a
 * missing backend.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return bool True when a GIF provider (Platform-bundled or addon) is loaded.
 */
function bb_giphy_provider_available() {
	$available = function_exists( 'bp_media_activity_embed_gif' );

	/**
	 * Filters whether a GIPHY integration provider is available.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param bool $available True when the GIF save/render/delete functions are loaded.
	 */
	return (bool) apply_filters( 'bb_giphy_module_active', $available );
}

/**
 * Return GIFs API Key
 *
 * @param string $default Optional. Fallback value if not found in the database.
 *                      Default: true.
 *
 * @return GIF Api Key if, empty string.
 * @since BuddyBoss 1.0.0
 */
function bp_media_get_gif_api_key( $default = '' ) {

	/**
	 * Filters whether GIF key.
	 *
	 * @param GIF Api Key if, empty sting.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	return apply_filters( 'bp_media_get_gif_api_key', bp_get_option( 'bp_media_gif_api_key', $default ) );
}

/**
 * Checks if media gif support is enabled in profiles.
 *
 * @param $default integer
 *
 * @return bool Is media gif support enabled or not in profiles
 * @since BuddyBoss 1.0.0
 */
function bp_is_profiles_gif_support_enabled( $default = 0 ) {
	$result = false;
	if ( function_exists( 'bb_check_valid_giphy_api_key' ) && bb_check_valid_giphy_api_key() ) {
		$result = (bool) get_option( 'bp_media_profiles_gif_support', $default );
	}
	return (bool) apply_filters( 'bp_is_profiles_gif_support_enabled', $result );
}

/**
 * Checks if media gif support is enabled in groups.
 *
 * @param $default integer
 *
 * @return bool Is media gif support enabled or not in groups
 * @since BuddyBoss 1.0.0
 */
function bp_is_groups_gif_support_enabled( $default = 0 ) {
	$result = false;
	if ( function_exists( 'bb_check_valid_giphy_api_key' ) && bb_check_valid_giphy_api_key() ) {
		$result = (bool) get_option( 'bp_media_groups_gif_support', $default );
	}
	return (bool) apply_filters( 'bp_is_groups_gif_support_enabled', $result );
}

/**
 * Checks if media gif support is enabled in messages.
 *
 * @param $default integer
 *
 * @return bool Is media gif support enabled or not in messages
 * @since BuddyBoss 1.0.0
 */
function bp_is_messages_gif_support_enabled( $default = 0 ) {
	$result = false;
	if ( function_exists( 'bb_check_valid_giphy_api_key' ) && bb_check_valid_giphy_api_key() ) {
		$result = (bool) get_option( 'bp_media_messages_gif_support', $default );
	}
	return (bool) apply_filters( 'bp_is_messages_gif_support_enabled', $result );
}

/**
 * Checks if media gif support is enabled in forums.
 *
 * @param $default integer
 *
 * @return bool Is media gif support enabled or not in forums
 * @since BuddyBoss 1.0.0
 */
function bp_is_forums_gif_support_enabled( $default = 0 ) {
	$result = false;
	if ( function_exists( 'bb_check_valid_giphy_api_key' ) && bb_check_valid_giphy_api_key() ) {
		$result = (bool) get_option( 'bp_media_forums_gif_support', $default );
	}
	return (bool) apply_filters( 'bp_is_forums_gif_support_enabled', $result );
}

/**
 * Checks if media messages doc support is enabled.
 *
 * @param $default integer
 *
 * @return bool Is media messages doc support enabled or not
 * @since BuddyBoss 1.2.3
 */
function bp_is_messages_document_support_enabled( $default = 0 ) {
	return (bool) apply_filters( 'bp_is_messages_document_support_enabled', (bool) get_option( 'bp_media_messages_document_support', $default ) );
}

/**
 * Checks if media group document support is enabled.
 *
 * @param $default integer
 *
 * @return bool Is media group document support enabled or not
 * @since BuddyBoss 1.2.3
 */
function bp_is_group_document_support_enabled( $default = 0 ) {
	return (bool) apply_filters( 'bp_is_group_document_support_enabled', (bool) get_option( 'bp_media_group_document_support', $default ) );
}

/**
 * Checks if media forums document support is enabled.
 *
 * @param $default integer
 *
 * @return bool Is media forums document support enabled or not
 * @since BuddyBoss 1.2.3
 */
function bp_is_forums_document_support_enabled( $default = 0 ) {
	return (bool) apply_filters( 'bp_is_forums_document_support_enabled', (bool) get_option( 'bp_media_forums_document_support', $default ) );
}

/**
 * Checks if media forums document support is enabled.
 *
 * @param $default false.
 *
 * @return bool Is media forums document support enabled or not
 * @since BuddyBoss 1.2.3
 */
function bp_is_profile_document_support_enabled( $default = 0 ) {
	return (bool) apply_filters( 'bp_is_profile_document_support_enabled', (bool) get_option( 'bp_media_profile_document_support', $default ) );
}

/*
 * Video support helpers.
 *
 * These mirror the document twins above and live in the Platform (not the video
 * component) on purpose: the video component was extracted to the buddyboss-addons
 * plugin, but Platform code (REST endpoints, group settings, templates) calls these
 * helpers directly and unconditionally. Keeping them here means those callers resolve
 * whether or not the addon is present. When the video component is inactive,
 * bb_media_videos_force_disable() (bp-media-filters.php) forces all four to false via
 * their filters — so an unlicensed/absent addon degrades cleanly instead of fataling.
 * Option names are the frozen video contract (bp_video_*_video_support).
 *
 * @since BuddyBoss [BBVERSION]
 */

/**
 * Checks if profile video support is enabled.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param int $default Default value.
 * @return bool Whether profile video support is enabled.
 */
function bp_is_profile_video_support_enabled( $default = 0 ) {
	return (bool) apply_filters( 'bp_is_profile_video_support_enabled', (bool) get_option( 'bp_video_profile_video_support', $default ) );
}

/**
 * Checks if group video support is enabled.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param int $default Default value.
 * @return bool Whether group video support is enabled.
 */
function bp_is_group_video_support_enabled( $default = 0 ) {
	return (bool) apply_filters( 'bp_is_group_video_support_enabled', (bool) get_option( 'bp_video_group_video_support', $default ) );
}

/**
 * Checks if messages video support is enabled.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param int $default Default value.
 * @return bool Whether messages video support is enabled.
 */
function bp_is_messages_video_support_enabled( $default = 0 ) {
	return (bool) apply_filters( 'bp_is_messages_video_support_enabled', (bool) get_option( 'bp_video_messages_video_support', $default ) );
}

/**
 * Checks if forums video support is enabled.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param int $default Default value.
 * @return bool Whether forums video support is enabled.
 */
function bp_is_forums_video_support_enabled( $default = 0 ) {
	return (bool) apply_filters( 'bp_is_forums_video_support_enabled', (bool) get_option( 'bp_video_forums_video_support', $default ) );
}

/**
 * Checks if extension support is enabled.
 *
 * @return array Is media extension support enabled or not
 * @since BuddyBoss 1.0.0
 */
function bp_document_extensions_list() {

	$default = bp_media_allowed_document_type();
	$saved   = bp_get_option( 'bp_document_extensions_support', $default );
	$merge   = array_merge( $default, $saved );
	$final   = array_unique( $merge, SORT_REGULAR );

	/**
	 * Filter to alllow the document extensions list.
	 *
	 * @param array $final List of extensions.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bp_document_extensions_list', $final );
}

/**
 * Allowed upload file size for the media.
 *
 * @return int Allowed upload file size for the media.
 * @since BuddyBoss 1.4.8
 */
function bp_media_allowed_upload_media_size() {

	$max_size = bp_core_upload_max_size();
	$default  = bp_media_format_size_units( $max_size, false, 'MB' );
	return (int) apply_filters( 'bp_media_allowed_upload_media_size', (int) get_option( 'bp_media_allowed_size', $default ) );
}

/**
 * Allowed upload file size for the document.
 *
 * @return int Allowed upload file size for the document.
 *
 * @since BuddyBoss 1.4.8
 */
function bp_media_allowed_upload_document_size() {
	$max_size = bp_core_upload_max_size();
	$default  = function_exists( 'bp_document_format_size_units' ) ? bp_document_format_size_units( $max_size, false, 'MB' ) : bp_media_format_size_units( $max_size, false, 'MB' );
	return (int) apply_filters( 'bp_media_allowed_upload_document_size', (int) get_option( 'bp_document_allowed_size', $default ) );
}

/**
 * Allowed per batch for the media.
 *
 * @return int Allowed upload per batch for the media.
 * @since BuddyBoss 1.5.6
 */
function bp_media_allowed_upload_media_per_batch() {

	$default = apply_filters( 'bp_media_upload_chunk_limit', 10 );
	return (int) apply_filters( 'bp_media_allowed_upload_media_per_batch', (int) get_option( 'bp_media_allowed_per_batch', $default ) );
}

/**
 * Allowed per batch for the document.
 *
 * @return int Allowed per batch for the document.
 * @since BuddyBoss 1.5.6
 */
function bp_media_allowed_upload_document_per_batch() {

	/**
	 * Filter to allow document upload per batch.
	 *
	 * @param int $default Per batch.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	$default = apply_filters( 'bp_document_upload_chunk_limit', 10 );

	/**
	 * Filter to allow document upload per batch.
	 *
	 * @param int $default Per batch.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return (int) apply_filters( 'bp_media_allowed_upload_document_per_batch', (int) get_option( 'bp_document_allowed_per_batch', $default ) );
}

/**
 * Checks if extension support is enabled.
 *
 * @return array Is video extension support enabled or not
 * @since BuddyBoss 1.7.0
 */
function bp_video_extensions_list() {
	return apply_filters( 'bp_video_extensions_list', bp_get_option( 'bp_video_extensions_support', bp_video_allowed_video_type() ) );
}
