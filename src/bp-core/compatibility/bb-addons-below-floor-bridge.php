<?php
/**
 * Security bridge for BuddyBoss Add-ons builds below the required version.
 *
 * Platform no longer ships Video and Documents, so the BuddyBoss Add-ons copies
 * are the only live code. Add-ons builds below bb_get_addons_required_version()
 * (the released 1.2.3 and older) lack two fixes that Platform 3.6.2 shipped in
 * its own copies:
 *
 * - PROD-10601: the Private Website guard on the document/folder and video
 *   download links (bb_private_network_restrict_download()).
 * - PROD-10531: the REST document-folder rules. An update must not take the
 *   folder's group from the request (moving it into another group) or change the
 *   privacy of a group or child folder, and a new folder must belong to its
 *   parent folder's group.
 *
 * The version floor alone only warns, and the queued Add-ons update can fail
 * (DISALLOW_FILE_MODS, VCS copies, no entitled license, retry limit). Until the
 * site runs a current Add-ons, this file applies the same rules from Platform,
 * without hiding the features. It is inert when Add-ons is inactive, absent or
 * at/above the floor, so current builds keep their own checks and nothing runs
 * twice.
 *
 * The Videos and GIFs checks at the end look for the moved player and GIPHY
 * libraries instead of the version, so they also cover builds at the floor that
 * were cut before those libraries moved into Add-ons.
 *
 * Remove once the Add-ons floor makes these builds unsupported.
 *
 * @package BuddyBoss\Core
 * @since BuddyBoss [BBVERSION]
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether an active BuddyBoss Add-ons build below the required version is running.
 *
 * Reads the version constant defined by the running Add-ons main file, so an
 * inactive or absent Add-ons is never matched and no plugin headers are parsed.
 * Memoised per request.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return bool True when the bridge must apply its rules.
 */
function bb_addons_below_floor_bridge_is_active() {
	static $is_active = null;

	if ( null === $is_active ) {
		$is_active = defined( 'BB_ADDONS_PLUGIN_VERSION' )
			&& function_exists( 'bb_is_addons_below_required_version' )
			// The running build, not the plugin file on disk, decides which rules are live.
			&& bb_is_addons_below_required_version( (string) BB_ADDONS_PLUGIN_VERSION );
	}

	/**
	 * Filters whether the bridge for Add-ons builds below the required version applies.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param bool $is_active Whether an active Add-ons build is below the required version.
	 */
	return (bool) apply_filters( 'bb_addons_below_floor_bridge_is_active', $is_active );
}

/**
 * Private Website: send guests to login before an old Add-ons streams a download.
 *
 * Add-ons 1.2.3 hooks bp_document_download_url_file() and
 * bp_video_download_url_file() on `init` at the default priority and never calls
 * the guard. This runs first, on the same query args and with the same checks
 * Platform 3.6.2 ran before its guard call. Invalid document args are left to the
 * Add-ons handler, which rejects them with a 400 as before.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return void
 */
function bb_addons_below_floor_bridge_restrict_downloads() {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only routing check; the download handlers do the access check.
	if ( is_user_logged_in() || ! function_exists( 'bb_private_network_restrict_download' ) ) {
		return;
	}

	$is_document = isset( $_GET['attachment'], $_GET['download_document_file'], $_GET['document_file'], $_GET['document_type'] );
	$is_video    = isset( $_GET['attachment_id'], $_GET['download_video_file'], $_GET['video_file'], $_GET['video_type'] );

	if ( ( ! $is_document && ! $is_video ) || ! bb_addons_below_floor_bridge_is_active() ) {
		return;
	}

	if ( $is_document ) {
		$document_type = sanitize_text_field( wp_unslash( $_GET['document_type'] ) );

		if (
			absint( $_GET['attachment'] ) > 0 &&
			absint( $_GET['document_file'] ) > 0 &&
			in_array( $document_type, array( 'document', 'folder' ), true )
		) {
			bb_private_network_restrict_download( $document_type );
		}
	}
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	if ( $is_video ) {
		bb_private_network_restrict_download( 'video' );
	}
}
add_action( 'init', 'bb_addons_below_floor_bridge_restrict_downloads', 9 );

/**
 * Get the folder ID when a request targets a single REST document folder.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param WP_REST_Request|mixed $request REST request.
 *
 * @return int Folder ID, or 0 when the request is for another route.
 */
function bb_addons_below_floor_bridge_get_rest_folder_id( $request ) {
	if ( ! $request instanceof WP_REST_Request || ! function_exists( 'bp_rest_namespace' ) ) {
		return 0;
	}

	// WordPress matches REST routes case-insensitively, so this match must too.
	$pattern = '#^/' . preg_quote( bp_rest_namespace() . '/' . bp_rest_version(), '#' ) . '/document/folder/(\d+)$#i';

	if ( ! preg_match( $pattern, (string) $request->get_route(), $matches ) ) {
		return 0;
	}

	return (int) $matches[1];
}

/**
 * Apply the newer document-folder update permission rules for old Add-ons.
 *
 * Mirrors update_item_permissions_check() of Add-ons 1.2.4 / Platform 3.6.2 on
 * top of the 1.2.3 result. Only a passing result is tightened: the upload check
 * uses the folder's own group (never the request's), a `group_id` other than the
 * folder's is rejected, and group folders need document-manage rights in their
 * group. Runs first on the filter so later callbacks see the same value they
 * would see with a current Add-ons.
 *
 * The filter name is shared with the document endpoint, so the route is checked.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param bool|WP_Error   $retval  Permission result from the endpoint.
 * @param WP_REST_Request $request REST request.
 *
 * @return bool|WP_Error
 */
function bb_addons_below_floor_bridge_folder_update_permissions( $retval, $request ) {
	if ( true !== $retval || ! bb_addons_below_floor_bridge_is_active() || ! class_exists( 'BP_Document_Folder' ) ) {
		return $retval;
	}

	$folder_id = bb_addons_below_floor_bridge_get_rest_folder_id( $request );
	if ( ! $folder_id ) {
		return $retval;
	}

	$folder = new BP_Document_Folder( $folder_id );
	if ( empty( $folder->id ) ) {
		return $retval;
	}

	if (
		function_exists( 'bb_document_user_can_upload' ) &&
		! bb_document_user_can_upload( bp_loggedin_user_id(), (int) $folder->group_id )
	) {
		return new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you need to be logged in to update this folder.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);
	}

	if (
		// A folder cannot be moved between groups, or in/out of a group, through an update.
		( ! empty( $request['group_id'] ) && (int) $request['group_id'] !== (int) $folder->group_id ) ||
		(
			// Group folders: the actor must still be allowed to manage documents in the folder's group.
			! empty( $folder->group_id ) &&
			(
				! bp_is_active( 'groups' )
				|| ! groups_can_user_manage_document( bp_loggedin_user_id(), (int) $folder->group_id )
			)
		)
	) {
		return new WP_Error(
			'bp_rest_invalid_permission',
			__( 'You don\'t have a permission to edit a folder inside this group.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);
	}

	return $retval;
}
add_filter( 'bp_rest_document_update_item_permissions_check', 'bb_addons_below_floor_bridge_folder_update_permissions', 1, 2 );

/**
 * Apply the newer document-folder create rule for old Add-ons.
 *
 * Mirrors create_item_permissions_check() of Add-ons 1.2.4 / Platform 3.6.2:
 * a `group_id` that differs from the parent folder's group is rejected.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param bool|WP_Error   $retval  Permission result from the endpoint.
 * @param WP_REST_Request $request REST request.
 *
 * @return bool|WP_Error
 */
function bb_addons_below_floor_bridge_folder_create_permissions( $retval, $request ) {
	if (
		true !== $retval ||
		empty( $request['parent'] ) ||
		empty( $request['group_id'] ) ||
		! bb_addons_below_floor_bridge_is_active() ||
		! class_exists( 'BP_Document_Folder' )
	) {
		return $retval;
	}

	$parent_folder = new BP_Document_Folder( $request['parent'] );

	if ( ! empty( $parent_folder->id ) && (int) $request['group_id'] !== (int) $parent_folder->group_id ) {
		return new WP_Error(
			'bp_rest_invalid_permission',
			__( 'You don\'t have a permission to create a folder inside this folder.', 'buddyboss' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);
	}

	return $retval;
}
add_filter( 'bp_rest_document_folder_create_items_permissions_check', 'bb_addons_below_floor_bridge_folder_create_permissions', 1, 2 );

/**
 * Rebuild an old Add-ons folder update with the newer group and privacy rules.
 *
 * Add-ons 1.2.3 update_item() copies `group_id` from the request (forcing
 * 'grouponly'), applies any requested privacy, and then cascades
 * `$request['privacy']` to the folder tree. Add-ons 1.2.4 / Platform 3.6.2 never
 * take the group from the request and change privacy only for a personal root
 * folder whose privacy actually changes (not to 'grouponly'); a new parent
 * still sets the parent's privacy without a cascade.
 *
 * This resets the group and privacy in the update arguments to those rules and,
 * when the newer code would not cascade, removes `privacy` from the request so
 * the 1.2.3 cascade is skipped. Runs first on the filter so later callbacks see
 * the corrected arguments.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array           $args    Folder update arguments.
 * @param WP_REST_Request $request REST request.
 *
 * @return array
 */
function bb_addons_below_floor_bridge_folder_update_args( $args, $request ) {
	if (
		! is_array( $args ) ||
		empty( $args['id'] ) ||
		! bb_addons_below_floor_bridge_is_active() ||
		! class_exists( 'BP_Document_Folder' ) ||
		bb_addons_below_floor_bridge_get_rest_folder_id( $request ) !== (int) $args['id']
	) {
		return $args;
	}

	// Not saved yet, so this is the folder as stored.
	$folder = new BP_Document_Folder( (int) $args['id'] );
	if ( empty( $folder->id ) ) {
		return $args;
	}

	$args['group_id'] = $folder->group_id;
	$args['privacy']  = $folder->privacy;

	$privacy_changed = false;
	if (
		! empty( $request['privacy'] ) &&
		'grouponly' !== $request['privacy'] &&
		empty( $folder->group_id ) &&
		0 === (int) $folder->parent &&
		$folder->privacy !== $request['privacy']
	) {
		$args['privacy'] = $request['privacy'];
		$privacy_changed = true;
	}

	if ( ! empty( $request['parent'] ) ) {
		$parent_folder   = new BP_Document_Folder( $request['parent'] );
		$args['privacy'] = $parent_folder->privacy;
		$privacy_changed = false;
	}

	if ( ! $privacy_changed && isset( $request['privacy'] ) ) {
		unset( $request['privacy'] );
	}

	return $args;
}
add_filter( 'bp_rest_document_folder_update_items_query_args', 'bb_addons_below_floor_bridge_folder_update_args', 1, 2 );

/**
 * Whether the active Add-ons build is missing a player or picker library that moved into it.
 *
 * The video.js player and the GIPHY library moved from Platform into Add-ons.
 * Add-ons builds without them still provide Videos and GIFs, so those features
 * would render with no library behind them. This checks the running code, not the
 * version number, so any build without the assets is matched.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $register_function Add-ons function that registers the library.
 *
 * @return bool True when Add-ons is active and does not ship the library.
 */
function bb_addons_lacks_moved_assets( $register_function ) {
	return defined( 'BB_ADDONS_PLUGIN_VERSION' ) && ! function_exists( $register_function );
}

/**
 * GIFs: keep the GIF picker off while Add-ons runs without the GIPHY library.
 *
 * GIFs stay off until Add-ons is updated; saved GIFs are kept.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param bool $available Whether a GIF provider is loaded.
 *
 * @return bool
 */
function bb_addons_below_floor_bridge_gif_provider( $available ) {
	if ( $available && bb_addons_lacks_moved_assets( 'bb_addons_giphy_register_scripts' ) ) {
		return false;
	}

	return $available;
}
add_filter( 'bb_giphy_module_active', 'bb_addons_below_floor_bridge_gif_provider' );

/**
 * Videos: keep video uploads off while Add-ons runs without the video.js player.
 *
 * Without the player the upload buttons would have no script behind them. Video
 * uploads stay off until Add-ons is updated; existing videos are kept.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param bool $enabled Whether video support is enabled.
 *
 * @return bool
 */
function bb_addons_below_floor_bridge_video_support( $enabled ) {
	if ( $enabled && bb_addons_lacks_moved_assets( 'bb_addons_video_register_scripts' ) ) {
		return false;
	}

	return $enabled;
}
add_filter( 'bp_is_profile_video_support_enabled', 'bb_addons_below_floor_bridge_video_support' );
add_filter( 'bp_is_group_video_support_enabled', 'bb_addons_below_floor_bridge_video_support' );
add_filter( 'bp_is_messages_video_support_enabled', 'bb_addons_below_floor_bridge_video_support' );
add_filter( 'bp_is_forums_video_support_enabled', 'bb_addons_below_floor_bridge_video_support' );
