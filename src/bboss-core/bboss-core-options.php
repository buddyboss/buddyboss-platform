<?php
/**
 * BuddyPress Options.
 *
 * @package BuddyBoss\Options
 * @since BuddyPress 1.6.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Get the default site options and their values.
 *
 * Default values should not be set by calls to `get_option()` or `get_site_option()` due to
 * these causing load order problems with `bboss_core_clear_root_options_cache()`; see #BP7227.
 *
 * @since BuddyPress 1.6.0
 *
 * @return array Filtered option names and values.
 */
function bboss_get_default_options() {

	// Default options.
	$options = array(

		/* Components ********************************************************/

		'bp-deactivated-components'                  => array(),

		/* XProfile **********************************************************/

		// Default profile groups name.
		'bp-xprofile-base-group-name'                => 'Details',

		// Default fullname field name.
		'bp-xprofile-firstname-field-name'           => 'First Name',

		// Default fullname field name.
		'bp-xprofile-lastname-field-name'            => 'Last Name',

		// Default fullname field name.
		'bp-xprofile-nickname-field-name'            => 'Nickname',

		// Default fullname field name. (for backward compat).
		'bp-xprofile-fullname-field-name'            => 'Name',

		'bp-display-name-format'                     => 'first_name',

		// Default profile slug format.
		'bb_profile_slug_format'                     => 'username',

		// Enable/Disable Profile Type.
		'bp-member-type-enable-disable'              => false,

		// Enable/Disable Display on profiles.
		'bp-member-type-display-on-profile'          => false,

		/* Blogs *************************************************************/

		// Used to decide if blogs need indexing.
		'bp-blogs-first-install'                     => false,

		/* Settings **********************************************************/

		// Disable the WP to BP profile sync.
		'bp-disable-profile-sync'                    => false,

		// Hide the Toolbar for logged out users.
		'hide-loggedout-adminbar'                    => false,

		// Avatar uploads.
		'bp-disable-avatar-uploads'                  => false,

		// Avatar type.
		'bp-profile-avatar-type'                     => 'BuddyBoss',

		// Default Avatar type.
		'bp-default-profile-avatar-type'             => 'buddyboss',

		// Cover type.
		'bp-default-profile-cover-type'              => 'buddyboss',

		// cover photo uploads.
		'bp-disable-cover-image-uploads'             => false,

		// Group Profile Photos.
		'bp-disable-group-avatar-uploads'            => false,

		// Group Photos Type.
		'bp-default-group-avatar-type'               => 'buddyboss',

		// Group Cover Type.
		'bp-default-group-cover-type'                => 'buddyboss',

		// Group cover photo uploads.
		'bp-disable-group-cover-image-uploads'       => false,

		// Group Types.
		'bp-disable-group-type-creation'             => false,

		// Auto Group Membership Approval.
		'bp-enable-group-auto-join'                  => false,

		// Group restrict invites to members who already in specific parent group.
		'bp-enable-group-restrict-invites'           => false,

		// Allow users to delete their own accounts.
		'bp-disable-account-deletion'                => false,

		// Allow site owner to enable private network.
		'bp-enable-private-network'                  => true,

		// Allow comments on post and comment activity items.
		'bp-disable-blogforum-comments'              => true,

		// The ID for the current theme package.
		'_bp_theme_package_id'                       => 'nouveau',

		// Email unsubscribe salt.
		'bp-emails-unsubscribe-salt'                 => '',

		// Profile Enable Gravatar.
		'bp-enable-profile-gravatar'                 => false,

		/* Groups ************************************************************/

		// @todo Move this into the groups component
		// Restrict group creation to super admins.
		'bp_restrict_group_creation'                 => false,

		/* Akismet ***********************************************************/

		// Users from all sites can post.
		'_bp_enable_akismet'                         => true,

		/* Activity HeartBeat ************************************************/

		// HeartBeat is on to refresh activities.
		'_bp_enable_heartbeat_refresh'               => true,

		/* BuddyBar **********************************************************/

		// Force the BuddyBar.
		'_bp_force_buddybar'                         => false,

		/* Legacy *********************************************/

		// Do not register the bp-default themes directory.
		'_bp_retain_bp_default'                      => false,

		// Ignore deprecated code.
		'_bp_ignore_deprecated_code'                 => true,

		/* Invites ************************************************************/

		'bp-disable-invite-member-email-subject'     => false,
		'bp-disable-invite-member-email-content'     => true,
		'bp-disable-invite-member-type'              => false,

		/* Widgets **************************************************/
		'widget_bp_core_login_widget'                => false,
		'widget_bp_core_members_widget'              => false,
		'widget_bp_core_whos_online_widget'          => false,
		'widget_bp_core_recently_active_widget'      => false,
		'widget_bp_groups_widget'                    => false,
		'widget_bp_messages_sitewide_notices_widget' => false,

		// Enabled activity pinned posts.
		'_bb_enable_activity_pinned_posts'           => true,

		// Enabled reactions and their mode.
		'bb_all_reactions'                           => array(
			'activity'         => true,
			'activity_comment' => true,
		),
		'bb_reaction_mode'                           => 'likes',
		'bb_reaction_button'                         => array(
			array(
				'text' => '',
				'icon' => 'thumbs-up',
			),
		),

		// Performance Settings.
		'bb_ajax_request_page_load'                  => 1,
		'bb_load_activity_per_request'               => 10,
		'bb_activity_load_type'                      => 'infinite',

		'bb-enable-content-counts'                   => 0,
		'bb-enable-sso'                              => false,
	);

	/**
	 * Filters the default options to be set upon activation.
	 *
	 * @since BuddyPress 1.6.0
	 *
	 * @param array $options Array of default options to set.
	 */
	return apply_filters( 'bp_get_default_options', $options );
}

/**
 * Add default options when BuddyPress is first activated.
 *
 * Only called once when BuddyPress is activated.
 * Non-destructive, so existing settings will not be overridden.
 *
 * @since BuddyPress 1.6.0
 */
function bboss_add_options() {

	// Get the default options and values.
	$options = bboss_get_default_options();

	// Add default options.
	foreach ( $options as $key => $value ) {
		bboss_add_option( $key, $value );
	}

	/**
	 * Fires after the addition of default options when BuddyPress is first activated.
	 *
	 * Allows previously activated plugins to append their own options.
	 *
	 * @since BuddyPress 1.6.0
	 */
	do_action( 'bp_add_options' );
}

/**
 * Delete default options.
 *
 * Hooked to bp_uninstall, it is only called once when BuddyPress is uninstalled.
 * This is destructive, so existing settings will be destroyed.
 *
 * Currently unused.
 *
 * @since BuddyPress 1.6.0
 */
function bboss_delete_options() {

	// Get the default options and values.
	$options = bboss_get_default_options();

	// Add default options.
	foreach ( array_keys( $options ) as $key ) {
		delete_option( $key );
	}

	/**
	 * Fires after the deletion of default options when BuddyPress is first deactivated.
	 *
	 * Allows previously activated plugins to append their own options.
	 *
	 * @since BuddyPress 1.6.0
	 */
	do_action( 'bp_delete_options' );
}

/**
 * Add filters to each BP option, allowing them to be overloaded from inside the $bp->options array.
 *
 * @since BuddyPress 1.6.0
 */
function bboss_setup_option_filters() {

	// Get the default options and values.
	$options = bboss_get_default_options();

	// Add filters to each BuddyPress option.
	foreach ( array_keys( $options ) as $key ) {
		add_filter( 'pre_option_' . $key, 'bp_pre_get_option' );
	}

	/**
	 * Fires after the addition of filters to each BuddyPress option.
	 *
	 * Allows previously activated plugins to append their own options.
	 *
	 * @since BuddyPress 1.6.0
	 */
	do_action( 'bp_setup_option_filters' );
}

/**
 * Filter default options and allow them to be overloaded from inside the $bp->options array.
 *
 * @since BuddyPress 1.6.0
 *
 * @param bool $value Optional. Default value false.
 * @return mixed False if not overloaded, mixed if set.
 */
function bboss_pre_get_option( $value = false ) {
	$bp = bboss();

	// Remove the filter prefix.
	$option = str_replace( 'pre_option_', '', current_filter() );

	// Check the options global for preset value.
	if ( ! empty( $bp->options[ $option ] ) ) {
		$value = $bp->options[ $option ];
	}

	// Always return a value, even if false.
	return $value;
}

/**
 * Retrieve an option.
 *
 * This is a wrapper for {@link get_blog_option()}, which in turn stores settings data
 * (such as bp-pages) on the appropriate blog, given your current setup.
 *
 * The 'bp_get_option' filter is primarily for backward-compatibility.
 *
 * @since BuddyPress 1.5.0
 *
 * @param string $option_name The option to be retrieved.
 * @param string $default     Optional. Default value to be returned if the option
 *                            isn't set. See {@link get_blog_option()}.
 * @return mixed The value for the option.
 */
function bboss_get_option( $option_name, $default = '' ) {
	$value = get_blog_option( bboss_get_root_blog_id(), $option_name, $default );

	/**
	 * Filters the option value for the requested option.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param mixed $value The value for the option.
	 */
	return apply_filters( 'bp_get_option', $value );
}

/**
 * Add an option.
 *
 * This is a wrapper for {@link add_blog_option()}, which in turn stores
 * settings data on the appropriate blog, given your current setup.
 *
 * @since BuddyPress 2.0.0
 *
 * @param string $option_name The option key to be set.
 * @param mixed  $value       The value to be set.
 * @return bool True on success, false on failure.
 */
function bboss_add_option( $option_name, $value ) {
	return add_blog_option( bboss_get_root_blog_id(), $option_name, $value );
}

/**
 * Save an option.
 *
 * This is a wrapper for {@link update_blog_option()}, which in turn stores
 * settings data (such as bp-pages) on the appropriate blog, given your current
 * setup.
 *
 * @since BuddyPress 1.5.0
 *
 * @param string $option_name The option key to be set.
 * @param mixed  $value       The value to be set.
 * @return bool True on success, false on failure.
 */
function bboss_update_option( $option_name, $value ) {
	return update_blog_option( bboss_get_root_blog_id(), $option_name, $value );
}

/**
 * Delete an option.
 *
 * This is a wrapper for {@link delete_blog_option()}, which in turn deletes
 * settings data (such as bp-pages) on the appropriate blog, given your current
 * setup.
 *
 * @since BuddyPress 1.5.0
 *
 * @param string $option_name The option key to be deleted.
 * @return bool True on success, false on failure.
 */
function bboss_delete_option( $option_name ) {
	return delete_blog_option( bboss_get_root_blog_id(), $option_name );
}

/**
 * Copy BP options from a single site to multisite config.
 *
 * Run when switching from single to multisite and we need to copy blog options
 * to site options.
 *
 * This function is no longer used.
 *
 * @since BuddyPress 1.2.4
 * @deprecated 1.6.0
 *
 * @param array $keys Array of site options.
 * @return bool
 */
function bboss_core_activate_site_options( $keys = array() ) {

	if ( ! empty( $keys ) && is_array( $keys ) ) {
		$bp = bboss();

		$errors = false;

		foreach ( $keys as $key => $default ) {
			if ( empty( $bp->site_options[ $key ] ) ) {
				$bp->site_options[ $key ] = bboss_get_option( $key, $default );

				if ( ! bboss_update_option( $key, $bp->site_options[ $key ] ) ) {
					$errors = true;
				}
			}
		}

		if ( empty( $errors ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Fetch global BP options.
 *
 * BuddyPress uses common options to store configuration settings. Many of these
 * settings are needed at run time. Instead of fetching them all and adding many
 * initial queries to each page load, let's fetch them all in one go.
 *
 * @since BuddyPress 1.5.0
 *
 * @todo Use settings API and audit these methods.
 *
 * @return array $root_blog_options_meta List of options.
 */
function bboss_core_get_root_options() {
	global $wpdb;

	// Get all the BuddyPress settings, and a few useful WP ones too.
	$root_blog_options                   = bboss_get_default_options();
	$root_blog_options['registration']   = '0';
	$root_blog_options['avatar_default'] = 'mysteryman';
	$root_blog_option_keys               = array_keys( $root_blog_options );

	// Do some magic to get all the root blog options in 1 swoop
	// Check cache first - We cache here instead of using the standard WP
	// settings cache because the current blog may not be the root blog,
	// and it's not practical to access the cache across blogs.
	$root_blog_options_meta = wp_cache_get( 'root_blog_options', 'bp' );

	if ( false === $root_blog_options_meta ) {
		$blog_options_table     = bboss_is_multiblog_mode() ? $wpdb->options : $wpdb->get_blog_prefix( bboss_get_root_blog_id() ) . 'options';
		$blog_options_query     = $wpdb->prepare( "SELECT option_name AS name, option_value AS value FROM {$blog_options_table} WHERE option_name IN ( " . implode( ', ', array_fill( 0, count( $root_blog_option_keys ), '%s' ) ) . ' )', $root_blog_option_keys ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- options table from $wpdb; one %s per option name.
		$root_blog_options_meta = $wpdb->get_results( $blog_options_query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- $blog_options_query is $wpdb->prepare()'d above.

		// On Multisite installations, some options must always be fetched from sitemeta.
		if ( is_multisite() ) {

			/**
			 * Filters multisite options retrieved from sitemeta.
			 *
			 * @since BuddyPress 1.5.0
			 *
			 * @param array $value Array of multisite options from sitemeta table.
			 */
			$network_options = apply_filters(
				'bp_core_network_options',
				array(
					'tags_blog_id'       => '0',
					'sitewide_tags_blog' => '',
					'registration'       => '0',
					'fileupload_maxk'    => '1500',
				)
			);

			$current_site           = get_current_site();
			$network_option_keys    = array_keys( $network_options );
			$sitemeta_options_query = $wpdb->prepare( "SELECT meta_key AS name, meta_value AS value FROM {$wpdb->sitemeta} WHERE meta_key IN ( " . implode( ', ', array_fill( 0, count( $network_option_keys ), '%s' ) ) . ' ) AND site_id = %d', array_merge( $network_option_keys, array( $current_site->id ) ) ); // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- one %s per meta key, then the site id.
			$network_options_meta   = $wpdb->get_results( $sitemeta_options_query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- $sitemeta_options_query is $wpdb->prepare()'d above.

			// Sitemeta comes second in the merge, so that network 'registration' value wins.
			$root_blog_options_meta = array_merge( $root_blog_options_meta, $network_options_meta );
		}

		// Loop through our results and make them usable.
		foreach ( $root_blog_options_meta as $root_blog_option ) {
			$root_blog_options[ $root_blog_option->name ] = $root_blog_option->value;
		}

		// Copy the options no the return val.
		$root_blog_options_meta = $root_blog_options;

		// Clean up our temporary copy.
		unset( $root_blog_options );

		wp_cache_set( 'root_blog_options', $root_blog_options_meta, 'bp' );
	}

	/**
	 * Filters the global BP options.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param array $root_blog_options_meta Array of global BP options.
	 */
	return apply_filters( 'bp_core_get_root_options', $root_blog_options_meta );
}

/**
 * Get a root option.
 *
 * "Root options" are those that apply across an entire installation, and are fetched only a single
 * time during a pageload and stored in `bboss()->site_options` to prevent future lookups.
 * See {@see bboss_core_get_root_options()}.
 *
 * @since BuddyPress 2.3.0
 *
 * @param string $option Name of the option key.
 * @return mixed Value, if found.
 */
function bboss_core_get_root_option( $option ) {
	$bp = bboss();

	if ( ! isset( $bp->site_options ) ) {
		$bp->site_options = bboss_core_get_root_options();
	}

	$value = '';
	if ( isset( $bp->site_options[ $option ] ) ) {
		$value = $bp->site_options[ $option ];
	}

	return $value;
}

/** Active? *******************************************************************/

/**
 * Is profile syncing disabled?
 *
 * @since BuddyPress 1.6.0
 *
 * @param bool $default Optional. Fallback value if not found in the database.
 *                      Default: true.
 * @return bool True if profile sync is enabled, otherwise false.
 */
function bboss_disable_profile_sync( $default = false ) {

	/**
	 * Filters whether or not profile syncing is disabled.
	 *
	 * @since BuddyPress 1.6.0
	 *
	 * @param bool $value Whether or not syncing is disabled.
	 */
	return (bool) apply_filters( 'bp_disable_profile_sync', $default );
}

/**
 * Is advanced profile search disabled?
 *
 * @since BuddyBoss 1.0.0
 *
 * @param bool $default Optional. Fallback value if not found in the database.
 *                      Default: true.
 * @return bool True if profile search is enabled, otherwise false.
 */
function bboss_disable_advanced_profile_search( $default = false ) {

	/**
	 * Filters whether or not profile search is disabled.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param bool $value Whether or not profile search is disabled.
	 */
	return (bool) apply_filters( 'bp_disable_advanced_profile_search', ! (bool) bboss_get_option( 'bp-enable-profile-search', $default ) );
}

/**
 * Is the Toolbar hidden for logged out users?
 *
 * @since BuddyPress 1.6.0
 *
 * @param bool $default Optional. Fallback value if not found in the database.
 *                      Default: true.
 * @return bool True if the admin bar should be hidden for logged-out users,
 *              otherwise false.
 */
function bboss_hide_loggedout_adminbar( $default = true ) {

	/**
	 * Filters whether or not the toolbar is hidden for logged out users.
	 *
	 * @since BuddyPress 1.6.0
	 *
	 * @param bool $value Whether or not the toolbar is hidden.
	 */
	return (bool) apply_filters( 'bp_hide_loggedout_adminbar', (bool) bboss_get_option( 'hide-loggedout-adminbar', $default ) );
}

/**
 * Are members able to upload their own avatars?
 *
 * @since BuddyPress 1.6.0
 *
 * @param bool $default Optional. Fallback value if not found in the database.
 *                      Default: true.
 * @return bool True if avatar uploads are disabled, otherwise false.
 */
function bboss_disable_avatar_uploads( $default = true ) {

	/**
	 * Filters whether or not members are able to upload their own avatars.
	 *
	 * @since BuddyPress 1.6.0
	 *
	 * @param bool $value Whether or not members are able to upload their own avatars.
	 */
	return (bool) apply_filters( 'bp_disable_avatar_uploads', (bool) bboss_get_option( 'bp-disable-avatar-uploads', $default ) );
}

/**
 * Are members able to upload their own cover photos?
 *
 * @since BuddyPress 2.4.0
 *
 * @param bool $default Optional. Fallback value if not found in the database.
 *                      Default: false.
 * @return bool True if cover photo uploads are disabled, otherwise false.
 */
function bboss_disable_cover_image_uploads( $default = false ) {

	/**
	 * Filters whether or not members are able to upload their own cover photos.
	 *
	 * @since BuddyPress 2.4.0
	 *
	 * @param bool $value Whether or not members are able to upload their own cover photos.
	 */
	return (bool) apply_filters( 'bp_disable_cover_image_uploads', (bool) bboss_get_option( 'bp-disable-cover-image-uploads', $default ) );
}

/**
 * Are group avatars disabled?
 *
 * For backward compatibility, this option falls back on the value of 'bp-disable-avatar-uploads' when no value is
 * found in the database.
 *
 * @since BuddyPress 2.3.0
 *
 * @param bool|null $default Optional. Fallback value if not found in the database.
 *                           Defaults to the value of `bboss_disable_avatar_uploads()`.
 * @return bool True if group avatar uploads are disabled, otherwise false.
 */
function bboss_disable_group_avatar_uploads( $default = null ) {
	$disabled = bboss_get_option( 'bp-disable-group-avatar-uploads', '' );

	if ( '' === $disabled ) {
		if ( is_null( $default ) ) {
			$disabled = bboss_disable_avatar_uploads();
		} else {
			$disabled = $default;
		}
	}

	/**
	 * Filters whether or not members are able to upload group avatars.
	 *
	 * @since BuddyPress 2.3.0
	 *
	 * @param bool $disabled Whether or not members are able to upload their groups avatars.
	 * @param bool $default  Default value passed to the function.
	 */
	return (bool) apply_filters( 'bp_disable_group_avatar_uploads', $disabled, $default );
}

/**
 * Are group cover photos disabled?
 *
 * @since BuddyPress 2.4.0
 *
 * @param bool $default Optional. Fallback value if not found in the database.
 *                      Default: false.
 * @return bool True if group cover photo uploads are disabled, otherwise false.
 */
function bboss_disable_group_cover_image_uploads( $default = false ) {

	/**
	 * Filters whether or not members are able to upload group cover photos.
	 *
	 * @since BuddyPress 2.4.0
	 *
	 * @param bool $value Whether or not members are able to upload thier groups cover photos.
	 */
	return (bool) apply_filters( 'bp_disable_group_cover_image_uploads', (bool) bboss_get_option( 'bp-disable-group-cover-image-uploads', $default ) );
}

/**
 * Are group types creation disabled?
 *
 * @since BuddyBoss 1.0.0
 *
 * @param bool $default Optional. Fallback value if not found in the database.
 *                      Default: false.
 * @return bool True if group types are disabled, otherwise false.
 */
function bboss_disable_group_type_creation( $default = false ) {

	/**
	 * Filters whether or not members are able to create group types.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param bool $value Whether or not members are able to create groups types.
	 */
	return (bool) apply_filters( 'bp_disable_group_type_creation', (bool) bboss_get_option( 'bp-disable-group-type-creation', $default ) );
}

/**
 * Are group hierarchies enabled?
 *
 * @since BuddyBoss 1.0.0
 *
 * @param bool $default Optional. Fallback value if not found in the database.
 *                      Default: false.
 * @return bool True if group hierarchies are enabled, otherwise false.
 */
function bboss_enable_group_hierarchies( $default = false ) {

	/**
	 * Filters whether or not groups are able to have a parent and sub groups.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param bool $value whether or not groups are able to have a parent and sub groups.
	 */
	return (bool) apply_filters( 'bp_enable_group_hierarchies', (bool) bboss_get_option( 'bp-enable-group-hierarchies', $default ) );
}

/**
 * Are group hide subgroups from the main Groups Directory?
 *
 * @since BuddyBoss 1.5.1
 *
 * @param bool $default Optional. Fallback value if not found in the database.
 *                      Default: false.
 * @return bool True if group hide subgroups from the main Groups Directory, otherwise false.
 */
function bboss_enable_group_hide_subgroups( $default = false ) {

	/**
	 * Filters whether or not group hide subgroups from the main Groups Directory
	 *
	 * @since BuddyBoss 1.5.1
	 *
	 * @param bool $value whether or not group hide subgroups from the main Groups Directory.
	 */
	return (bool) apply_filters( 'bp_enable_group_hide_subgroups', (bool) bboss_get_option( 'bp-enable-group-hide-subgroups', $default ) );
}

/**
 * Are group restrict invites to members who already in specific parent group?
 *
 * @since BuddyBoss 1.0.0
 *
 * @param bool $default Optional. Fallback value if not found in the database.
 *                      Default: false.
 * @return bool True if group restrict invites to members who already in specific parent group are enabled, otherwise false.
 */
function bboss_enable_group_restrict_invites( $default = false ) {

	/**
	 * Filters whether group restrict invites to members who already in specific parent group?
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param bool $value whether or not group restrict invites to members who already in specific parent group?
	 */
	return (bool) apply_filters( 'bp_enable_group_restrict_invites', (bool) bboss_get_option( 'bp-enable-group-restrict-invites', $default ) );
}

/**
 * Is Auto Group Membership Approval enabled?
 *
 * @since BuddyBoss 1.0.0
 *
 * @param bool $default Optional. Fallback value if not found in the database.
 *                      Default: false.
 * @return bool True if Auto Group Membership Approval is enabled, otherwise false.
 */
function bboss_enable_group_auto_join( $default = false ) {

	/**
	 * Filters whether or not groups auto approve membership.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param bool $value whether or not groups auto approve membership.
	 */
	return (bool) apply_filters( 'bp_enable_group_auto_join', (bool) bboss_get_option( 'bp-enable-group-auto-join', $default ) );
}

/**
 * Are members able to delete their own accounts?
 *
 * @since BuddyPress 1.6.0
 *
 * @param bool $default Optional. Fallback value if not found in the database.
 *                      Default: true.
 * @return bool True if users are able to delete their own accounts, otherwise
 *              false.
 */
function bboss_disable_account_deletion( $default = false ) {

	/**
	 * Filters whether or not members are able to delete their own accounts.
	 *
	 * @since BuddyPress 1.6.0
	 *
	 * @param bool $value Whether or not members are able to delete their own accounts.
	 */
	return apply_filters( 'bp_disable_account_deletion', (bool) bboss_get_option( 'bp-disable-account-deletion', $default ) );
}

/**
 * Enable private network for site owner.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param bool $default Optional. Fallback value if not found in the database.
 *                      Default: true.
 * @return bool True if private network for site is enabled, otherwise
 *              false.
 */
function bboss_enable_private_network( $default = false ) {
	global $bp;

	if ( isset( $bp ) && isset( $bp->site_options ) && is_array( $bp->site_options ) && isset( $bp->site_options['bp-enable-private-network'] ) ) {
		$val = (bool) $bp->site_options['bp-enable-private-network'];
	} else {
		$val = (bool) bboss_get_option( 'bp-enable-private-network', $default );
	}

	/**
	 * Filters whether private network for site is enabled.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param bool $value Whether private network for site is enabled.
	 */
	return apply_filters( 'bp_enable_private_network', $val );
}

/**
 * Are post/comment activity feed comments disabled?
 *
 * @since BuddyPress 1.6.0
 *
 * @todo split and move into blog and forum components.
 *
 * @param bool $default Optional. Fallback value if not found in the database.
 *                      Default: false.
 * @return bool True if activity comments are disabled for blog and forum
 *              items, otherwise false.
 */
function bboss_disable_blogforum_comments( $default = false ) {
	global $activities_template;

	// When here is not activity.
	if ( empty( $activities_template->activity ) ) {
		return $default;
	}

	if ( empty( $activities_template->activity->component ) ) {
		return $default;
	}

	if ( 'blogs' !== $activities_template->activity->component ) {
		return $default;
	}

	$post = get_post( $activities_template->activity->secondary_item_id );

	if ( ! isset( $post->post_type ) ) {
		return $default;
	}

	// Does not allow comment for WooCommerce product.
	if ( 'product' === $post->post_type ) {
		return true;
	}

	// Filters whether or not blog and forum and custom post type activity feed comments are enable.
	$disable = (bool) bboss_is_post_type_feed_comment_enable( $post->post_type, $default ) ? false : true;

	/**
	 * Filters whether or not blog and forum activity feed comments are disabled.
	 *
	 * @since BuddyPress 1.6.0
	 *
	 * @param bool $value Whether or not blog and forum activity feed comments are disabled.
	 */
	return (bool) apply_filters( 'bp_disable_blogforum_comments', $disable );
}

/**
 * Describe the activity comment is enable or not for custom post type.
 *
 * @since BuddyBoss 1.7.2
 *
 * @param bool $post_type custom post type.
 * @param bool $default   Optional. Fallback value if not found in the database.
 *                        Default: false.
 * @return bool True if activity comments are enable for blog and forum
 *              items, otherwise false.
 */
function bboss_is_post_type_feed_comment_enable( $post_type, $default = false ) {
	$option_name  = bboss_post_type_feed_comment_option_name( $post_type );
	$option_value = (bool) bboss_get_option( $option_name, $default );
	$is_enabled   = bboss_activity_is_enabled_cpt_global_comment( $post_type );
	/**
	 * Filters whether or not custom post type feed comments are enable.
	 *
	 * @since BuddyBoss 1.7.2
	 *
	 * @param bool $value Whether or not custom post type activity feed comments are enable.
	 */
	return (bool) apply_filters( 'bb_is_post_type_feed_comment_enable', ( $is_enabled && $option_value ), $post_type );
}

/**
 * Is group creation turned off?
 *
 * @since BuddyPress 1.6.0
 *
 * @todo Move into groups component.
 *
 * @param bool $default Optional. Fallback value if not found in the database.
 *                      Default: true.
 * @return bool True if group creation is restricted, otherwise false.
 */
function bboss_restrict_group_creation( $default = true ) {

	/**
	 * Filters whether or not group creation is turned off.
	 *
	 * @since BuddyPress 1.6.0
	 *
	 * @param bool $value Whether or not group creation is turned off.
	 */
	return (bool) apply_filters( 'bp_restrict_group_creation', (bool) bboss_get_option( 'bp_restrict_group_creation', $default ) );
}

/**
 * Should the old BuddyBar be forced in place of the WP admin bar?
 *
 * @since BuddyPress 1.6.0
 *
 * @param bool $default Optional. Fallback value if not found in the database.
 *                      Default: true.
 * @return bool True if the BuddyBar should be forced on, otherwise false.
 */
function bboss_force_buddybar( $default = true ) {

	/**
	 * Filters whether or not BuddyBar should be forced in place of WP Admin Bar.
	 *
	 * @since BuddyPress 1.6.0
	 *
	 * @param bool $value Whether or not BuddyBar should be forced in place of WP Admin Bar.
	 */
	return (bool) apply_filters( 'bp_force_buddybar', (bool) bboss_get_option( '_bp_force_buddybar', $default ) );
}

/**
 * Check whether Akismet is enabled.
 *
 * @since BuddyPress 1.6.0
 *
 * @param bool $default Optional. Fallback value if not found in the database.
 *                      Default: true.
 * @return bool True if Akismet is enabled, otherwise false.
 */
function bboss_is_akismet_active( $default = true ) {

	/**
	 * Filters whether or not Akismet is enabled.
	 *
	 * @since BuddyPress 1.6.0
	 *
	 * @param bool $value Whether or not Akismet is enabled.
	 */
	return (bool) apply_filters( 'bp_is_akismet_active', (bool) bboss_get_option( '_bp_enable_akismet', $default ) );
}

/**
 * Check whether Activity Autoload is enabled.
 *
 * @since BuddyPress 2.0.0
 *
 * @param bool $default Optional. Fallback value if not found in the database.
 *                      Default: true.
 * @return bool True if Autoload is enabled, otherwise false.
 */
function bboss_is_activity_autoload_active( $default = true ) {

	$default_val = true === $default ? 'infinite' : 'load_more';

	/**
	 * Filters whether Activity Autoload is enabled.
	 *
	 * @since BuddyBoss 2.5.80
	 *
	 * @param bool $value true if Autoload is enabled, otherwise false.
	 */
	return (bool) apply_filters( 'bp_is_activity_autoload_active', ( 'infinite' === bboss_get_option( 'bb_activity_load_type', $default_val ) ) );
}

/**
 * Check whether Activity edit is enabled.
 *
 * @since BuddyBoss 1.5.0
 *
 * @param bool $default Optional. Fallback value if not found in the database.
 *                      Default: false.
 * @return bool True if Edit is enabled, otherwise false.
 */
function bboss_is_activity_edit_enabled( $default = false ) {

	/**
	 * Filters whether or not Activity edit is enabled.
	 *
	 * @since BuddyBoss 1.5.0
	 *
	 * @param bool $value Whether or not Activity edit is enabled.
	 */
	return (bool) apply_filters( 'bp_is_activity_edit_enabled', (bool) bboss_get_option( '_bp_enable_activity_edit', $default ) );
}

/**
 * Check whether relevant feed is enabled.
 *
 * @since BuddyBoss 1.5.5
 *
 * @param bool $default Optional. Fallback value if not found in the database.
 *                      Default: false.
 * @return bool True if Edit is enabled, otherwise false.
 */
function bboss_is_relevant_feed_enabled( $default = false ) {

	/**
	 * Filters whether or not relevant feed is enabled.
	 *
	 * @since BuddyBoss 1.5.5
	 *
	 * @param bool $value Whether or not relevant feed is enabled.
	 */

	return (bool) apply_filters( 'bp_is_relevant_feed_enabled', (bool) bboss_get_option( '_bp_enable_relevant_feed', $default ) );
}

/**
 * Single time slot by time key.
 *
 * @param null $time Return single time slot by time key.
 *
 * @return mixed|void
 * @since BuddyBoss 1.5.0
 */
function bboss_activity_edit_times( $time = null ) {

	$times = apply_filters(
		'bp_activity_edit_times',
		array(
			'thirty_days' => array(
				'value' => ( 60 * 60 * 24 * 30 ),
				'label' => __( '30 Days', 'buddyboss' ),
			),
			'seven_days'  => array(
				'value' => ( 60 * 60 * 24 * 7 ),
				'label' => __( '7 Days', 'buddyboss' ),
			),
			'one_day'     => array(
				'value' => ( 60 * 60 * 24 ),
				'label' => __( '1 Day', 'buddyboss' ),
			),
			'one_hour'    => array(
				'value' => ( 60 * 60 ),
				'label' => __( '1 Hour', 'buddyboss' ),
			),
			'ten_minutes' => array(
				'value' => ( 60 * 10 ),
				'label' => __( '10 Minutes', 'buddyboss' ),
			),
		)
	);

	if ( $time && isset( $times[ $time ] ) ) {
		return $times[ $time ];
	}

	return $times;
}

/**
 * Durations the activity/comment edit settings accept.
 *
 * `-1` means "forever"; the rest come from bboss_activity_edit_times(). This lives here rather
 * than in the admin settings callbacks because the edit duration is read on the front end,
 * where the admin files are not loaded.
 *
 * @since BuddyBoss 3.0.0
 * @since BuddyBoss 3.5.1 Moved here from the admin settings callbacks so the front end can read it.
 *
 * @return array Allowed integer values.
 */
function bboss_activity_get_allowed_edit_times() {
	// Deliberately not memoised: bp_activity_edit_times() is filterable and the first read can
	// happen before every filter is registered (field registration runs on bb_register_features).
	$allowed = array( -1 );
	foreach ( bboss_activity_edit_times() as $time ) {
		$allowed[] = intval( $time['value'] );
	}

	return $allowed;
}

/**
 * Resolve an edit duration to one the settings control can actually represent.
 *
 * The duration is chosen from a <select> offering a fixed set of values, and HTML has no
 * "no match" state: given a value matching no <option>, the browser silently selects the
 * first one. So a stored value outside the allowed set does not read as unset, it reads as
 * whichever option happens to be first — and `(int) ''` is 0, which
 * bboss_activity_user_can_edit() treats as a zero-second window, i.e. editing switched off.
 *
 * The write path already coerces out-of-range values to the default via
 * bboss_activity_sanitize_edit_time(); this applies the same rule on read, so installs whose
 * stored value is already invalid (migrations, partial saves, direct database edits) behave
 * as documented instead of silently disabling the feature.
 *
 * @since BuddyBoss 3.5.1
 *
 * @param mixed $value   Stored value.
 * @param mixed $default Value to fall back to. Must itself be an allowed duration, otherwise 600
 *                       (10 minutes) is used. Default 600.
 *
 * @return int A duration from bboss_activity_get_allowed_edit_times().
 */
function bboss_activity_normalize_edit_time( $value, $default = 600 ) {
	$allowed = bboss_activity_get_allowed_edit_times();

	if ( is_numeric( $value ) && in_array( intval( $value ), $allowed, true ) ) {
		return intval( $value );
	}

	// The fallback has to be representable too, or the return contract above is broken.
	if ( is_numeric( $default ) && in_array( intval( $default ), $allowed, true ) ) {
		return intval( $default );
	}

	return 600;
}

/**
 * Resolve an activity comment threading depth to a level the settings control can represent.
 *
 * The depth <select> offers 1-4. As with the edit duration, a stored value outside that range
 * does not read as unset — it renders as whichever <option> comes first — so the same rule has
 * to apply on read as on write, or the screen reports a depth the front end does not use.
 *
 * Lives here rather than in the admin settings callbacks because the depth is read on the front
 * end, where the admin files are not loaded.
 *
 * @since BuddyBoss 3.5.1
 *
 * @param mixed $value Stored value.
 *
 * @return int A depth between 1 and 4. Anything else resolves to 3.
 */
function bboss_activity_normalize_comment_threading_depth( $value ) {
	$value = absint( $value );

	if ( $value < 1 || $value > 4 ) {
		return 3;
	}

	return $value;
}

/**
 * Get BuddyBoss Activity Time option.
 *
 * The stored value is normalised with bboss_activity_normalize_edit_time(): anything outside
 * bboss_activity_get_allowed_edit_times(), including an empty row, resolves to 600 (10 minutes).
 *
 * @since BuddyBoss 1.5.0
 * @since BuddyBoss 3.5.1 The return value is normalised to an allowed duration.
 *
 * @param mixed $default Value used when the option row is absent. Honoured only if it is an allowed duration.
 *
 * @return int Edit duration in seconds, or -1 for no limit.
 */
function bboss_get_activity_edit_time( $default = false ) {
	$edit_time = bboss_activity_normalize_edit_time( bboss_get_option( '_bp_activity_edit_time', $default ) );

	return apply_filters( 'bp_get_activity_edit_time', $edit_time );
}

/**
 * Check whether Activity Tabs are enabled.
 *
 * @since BuddyBoss 1.1.6
 *
 * @param bool $default Optional. Fallback value if not found in the database.
 *                      Default: false.
 * @return bool True if Tabs are enabled, otherwise false.
 */
function bboss_is_activity_tabs_active( $default = false ) {

	/* Update for the backward compatibility since setting is removed. */
	// Retrieve the saved options.
	$filters = bboss_get_enabled_activity_filter_options();

	// Common function to get only allowed ones.
	$filters = bboss_filter_activity_filter_scope_keys( $filters );

	// Get only enabled options.
	$filters = array_filter(
		$filters,
		function ( $value ) {
			return 1 === (int) $value;
		}
	);

	if ( ! empty( $filters ) && count( $filters ) > 1 ) {
		$default = true;
	} else {
		$default = false;
	}

	/**
	 * Filters whether or not Activity Tabs are enabled.
	 *
	 * @since BuddyBoss 1.1.6
	 *
	 * @param bool $default Whether or not Activity Tabs are enabled.
	 */
	return (bool) apply_filters( 'bp_is_activity_tabs_active', $default );
}

/**
 * Check whether Activity Heartbeat refresh is enabled.
 *
 * @since BuddyPress 2.0.0
 *
 * @param bool $default Optional. Fallback value if not found in the database.
 *                      Default: true.
 * @return bool True if Heartbeat refresh is enabled, otherwise false.
 */
function bboss_is_activity_heartbeat_active( $default = true ) {

	/**
	 * Filters whether or not Activity Heartbeat refresh is enabled.
	 *
	 * @since BuddyPress 2.0.0
	 *
	 * @param bool $value Whether or not Activity Heartbeat refresh is enabled.
	 */
	return (bool) apply_filters( 'bp_is_activity_heartbeat_active', (bool) bboss_get_option( '_bp_enable_heartbeat_refresh', $default ) );
}

/**
 * Check whether Activity Follow is enabled.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param bool $default Optional. Fallback value if not found in the database.
 *                      Default: true.
 * @return bool True if Follow is enabled, otherwise false.
 */
function bboss_is_activity_follow_active( $default = false ) {

	/**
	 * Filters whether or not Activity Follow is enabled.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param bool $value Whether or not Activity Follow is enabled.
	 */
	return (bool) apply_filters( 'bp_is_activity_follow_active', (bool) bboss_get_option( '_bp_enable_activity_follow', $default ) );
}

/**
 * Check whether Activity Like is enabled.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param bool $default Optional. Fallback value if not found in the database.
 *                      Default: true.
 * @return bool True if Like is enabled, otherwise false.
 */
function bboss_is_activity_like_active( $default = true ) {

	$is_activity_post_like_active = false;
	if ( bboss_is_reaction_activity_posts_enabled( $default ) ) {
		$is_activity_post_like_active = true;
	}

	/**
	 * Filters whether or not Activity Like is enabled.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param bool $value Whether or not Activity Like is enabled.
	 */
	return (bool) apply_filters( 'bp_is_activity_like_active', $is_activity_post_like_active );
}


/**
 * Check whether Activity Link Preview is enabled.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param bool $default Optional. Fallback value if not found in the database.
 *                      Default: true.
 * @return bool True if Link Preview is enabled, otherwise false.
 */
function bboss_is_activity_link_preview_active( $default = false ) {

	/**
	 * Filters whether or not Activity Link Preview is enabled.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param bool $value Whether or not Activity Link Preview is enabled.
	 */
	return (bool) apply_filters( 'bp_is_activity_link_preview_active', (bool) bboss_get_option( '_bp_enable_activity_link_preview', $default ) );
}

/**
 * Get the current theme package ID.
 *
 * @since BuddyPress 1.7.0
 *
 * @param string $default Optional. Fallback value if not found in the database.
 *                        Default: 'nouveau'.
 * @return string ID of the theme package.
 */
function bboss_get_theme_package_id( $default = 'nouveau' ) {

	/**
	 * Filters the current theme package ID.
	 *
	 * @since BuddyPress 1.7.0
	 *
	 * @param string $value The current theme package ID.
	 */
	return apply_filters( 'bp_get_theme_package_id', $default );
}

/**
 * Is force friendship to message disabled?
 *
 * @since BuddyBoss 1.0.0
 *
 * @param bool $default Optional. Fallback value if not found in the database.
 *                      Default: false.
 * @return bool True if friendship is forced to message, otherwise false.
 */
function bboss_force_friendship_to_message( $default = false ) {

	$value = (bool) bboss_get_option( 'bp-force-friendship-to-message', $default );
	if ( ( ! is_admin() && bboss_current_user_can( 'bp_moderate' ) ) || ( defined( 'DOING_AJAX' ) && true === DOING_AJAX && bboss_current_user_can( 'bp_moderate' ) ) ) {
		$value = false;
	}

	/**
	 * Filters whether or not friendship is forced to message each other.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param bool $value Whether or not friendship is forced to message each other.
	 */
	return (bool) apply_filters( 'bp_force_friendship_to_message', $value );
}

/**
 * Check the activity auto follow enabled or not.
 *
 * @since BuddyBoss 2.3.1
 *
 * @param bool $default Optional. Fallback value if not found in the database.
 *                      Default: false.
 *
 * @return bool True if Auto Follow is enabled, otherwise false.
 */
function bboss_is_friends_auto_follow_active( $default = false ) {

	/**
	 * Filter whether the activity auto follow enabled or not.
	 *
	 * @since BuddyBoss 2.3.1
	 *
	 * @param bool $value Whether the activity auto follow enabled or not.
	 */
	return (bool) apply_filters( 'bb_is_friends_auto_follow_active', bboss_is_active( 'activity' ) && bboss_get_option( 'bb_enable_friends_auto_follow', $default ) );
}

/**
 * Is member type disabled?
 *
 * @since BuddyBoss 1.0.0
 *
 * @param bool $default Optional. Fallback value if not found in the database.
 *                      Default: false.
 * @return bool True if member type enabled, otherwise false.
 */
function bboss_member_type_enable_disable( $default = false ) {

	/**
	 * Filters whether member type is enabled or not.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param bool $value Whether member type is enabled or not.
	 */
	return (bool) apply_filters( 'bp_member_type_enable_disable', (bool) bboss_get_option( 'bp-member-type-enable-disable', $default ) );
}

/**
 * Is display on profile disabled?
 *
 * @since BuddyBoss 1.0.0
 *
 * @param bool $default Optional. Fallback value if not found in the database.
 *                      Default: false.
 * @return bool True if display member type on profile is enabled, otherwise false.
 */
function bboss_member_type_display_on_profile( $default = false ) {

	/**
	 * Filters whether display member type on profile is enabled or not.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param bool $value Whether display member type on profile is enabled or not.
	 */
	return (bool) apply_filters( 'bp_member_type_display_on_profile', (bool) bboss_get_option( 'bp-member-type-display-on-profile', $default ) );
}

/**
 * Is invite email subject customize disabled?
 *
 * @since BuddyBoss 1.0.0
 *
 * @param bool $default Optional. Fallback value if not found in the database.
 *                      Default: false.
 * @return bool True if email subject customize enabled, otherwise false.
 */
function bboss_disable_invite_member_email_subject( $default = false ) {

	/**
	 * Filters whether email subject customize is enabled or not.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param bool $value Whether email subject customize is enabled or not.
	 */
	return (bool) apply_filters( 'bp_disable_invite_member_email_subject', (bool) bboss_get_option( 'bp-disable-invite-member-email-subject', $default ) );
}

/**
 * Is invite email content customize disabled?
 *
 * @since BuddyBoss 1.0.0
 *
 * @param bool $default Optional. Fallback value if not found in the database.
 *                      Default: false.
 * @return bool True if email content customize enabled, otherwise false.
 */
function bboss_disable_invite_member_email_content( $default = true ) {

	/**
	 * Filters whether email content customize is enabled or not.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param bool $value Whether email content customize is enabled or not.
	 */
	return (bool) apply_filters( 'bp_disable_invite_member_email_content', (bool) bboss_get_option( 'bp-disable-invite-member-email-content', $default ) );
}

/**
 * Is allow users to sign up the profile types to personal inviting?
 *
 * @since BuddyBoss 1.0.0
 *
 * @param bool $default Optional. Fallback value if not found in the database.
 *                      Default: false.
 * @return bool True if allow users to sign up the profile types to personal inviting enabled, otherwise false.
 */
function bboss_disable_invite_member_type( $default = false ) {

	/**
	 * Filters whether allow users to sign up the profile types to personal inviting is enabled or not.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param bool $value Whether allow users to sign up the profile types to personal inviting is enabled or not.
	 */
	return (bool) apply_filters( 'bp_disable_invite_member_type', (bool) bboss_get_option( 'bp-disable-invite-member-type', $default ) );
}

/**
 * Checks if post type feed is enabled.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param string $post_type Post Type.
 * @param bool   $default Optional. Fallback value if not found in the database.
 *                        Default: false.
 *
 * @return bool Is post type feed enabled or not
 */
function bboss_is_post_type_feed_enable( $post_type, $default = false ) {

	/**
	 * Filters whether post type feed enabled or not.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @since BuddyBoss 2.5.00
	 * Introduce new params $post_type and $default.
	 *
	 * @param bool   $value     Whether post type feed enabled or not.
	 * @param string $post_type Post type.
	 * @param bool   $default   Optional. Fallback value if not found in the database.
	 *                          Default: false.
	 */
	return (bool) apply_filters( 'bp_is_post_type_feed_enable', (bool) bboss_get_option( bboss_post_type_feed_option_name( $post_type ), $default ), $post_type, $default );
}

/**
 * Checks if custom post type feed is enabled.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param bool $default Optional. Fallback value if not found in the database.
 *                      Default: false.
 *
 * @return bool Is post type feed enabled or not
 */
function bboss_is_custom_post_type_feed_enable( $default = false ) {

	/**
	 * Filters whether custom post type feed enabled or not.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param bool $value Whether custom post type feed enabled or not.
	 */
	return (bool) apply_filters( 'bp_is_custom_post_type_feed_enable', (bool) bboss_get_option( 'bp-enable-custom-post-type-feed', $default ) );
}

/**
 * Checks if default platform activity feed is enabled.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param string $activity_type Activity Type.
 * @param bool   $default Optional. Fallback value if not found in the database.
 *                        Default: false.
 *
 * @return bool Is post type feed enabled or not
 */
function bboss_platform_is_feed_enable( $activity_type, $default = true ) {

	/**
	 * Filters whether specified $activity_type should be enabled or no.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param bool $value Whether or not the feed enable or not.
	 */
	return (bool) apply_filters( 'bp_platform_is_feed_enable', (bool) bboss_get_option( $activity_type, $default ) );
}

/**
 * Is the Registration enabled?
 *
 * @since BuddyBoss 1.0.0
 *
 * @param bool $default Optional. Fallback value if not found in the database.
 *                      Default: true.
 * @return bool True if the registration enable,
 *              otherwise false.
 */
function bboss_enable_site_registration( $default = false ) {

	/**
	 * Filters whether or not the registration enable.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param bool $value Whether or not the registration enable.
	 */
	return (bool) apply_filters( 'bp_enable_site_registration', (bool) bboss_get_option( 'bp-enable-site-registration', $default ) );
}

/**
 * Default member type on registration.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param string $default blank. Fallback value if not found in the database.
 *                      Default: blank.
 * @return string member type if member type set,
 *              otherwise blank.
 */
function bboss_member_type_default_on_registration( $default = '' ) {

	/**
	 * Filters whether default profile type set on registration.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param string $value Whether default profile type set on registration.
	 */
	return apply_filters( 'bp_member_type_default_on_registration', bboss_get_option( 'bp-member-type-default-on-registration', $default ) );
}

/**
 * Checks if member type have send invites enabled.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param string $member_type Member type.
 * @param bool   $default Optional. Fallback value if not found in the database.
 *                        Default: true.
 *
 * @return bool Is member type send invites enabled or not
 */
function bboss_enable_send_invite_member_type( $member_type, $default = false ) {

	/**
	 * Filters whether specified $member_type should be allowed to send invites.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param bool $value whether or not allow member type invitations.
	 */
	return (bool) apply_filters( 'bp_enable_send_invite_member_type', (bool) bboss_get_option( $member_type, $default ) );
}

/**
 * Add URL OR URI which will ignore even if private network is enabled.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param string $default Optional. Fallback value if not found in the database.
 *                      Default: Empty string.
 * @return string Private network's public content.
 */
function bboss_enable_private_network_public_content( $default = '' ) {

	/**
	 * Filters private network's public content.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param bool $value Private network's public content.
	 */
	return apply_filters( 'bp_enable_private_network_public_content', bboss_get_option( 'bp-enable-private-network-public-content', '' ) );
}

/**
 * Is the Toolbar hidden for logged in users?
 *
 * @since BuddyBoss 1.0.0
 *
 * @param bool $default Optional. Fallback value if not found in the database.
 *                      Default: true.
 * @return bool True if the admin bar should be hidden for logged in users,
 *              otherwise false.
 */
function bboss_show_login_adminbar( $default = true ) {

	/**
	 * Filters whether or not the toolbar is hidden for logged in users (non-admins).
	 *
	 * @since BuddyBoss 1.1.0
	 *
	 * @param bool $value Whether or not the toolbar is hidden.
	 */
	return (bool) apply_filters( 'bp_show_login_adminbar', (bool) bboss_get_option( 'show-login-adminbar', $default ) );
}

/**
 * Is the Toolbar hidden for admin users?
 *
 * @since BuddyBoss 1.1.0
 *
 * @param bool $default Optional. Fallback value if not found in the database.
 *                      Default: true.
 * @return bool True if the admin bar should be hidden for admin users,
 *              otherwise false.
 */
function bboss_show_admin_adminbar( $default = true ) {

	/**
	 * Filters whether or not the toolbar is hidden for admin users.
	 *
	 * @since BuddyBoss 1.1.0
	 *
	 * @param bool $value Whether or not the toolbar is hidden.
	 */
	return (bool) apply_filters( 'bp_show_admin_adminbar', (bool) bboss_get_option( 'show-admin-adminbar', $default ) );
}

/**
 * Are members able to use gravatars?
 *
 * @since BuddyBoss 1.0.9
 *
 * @param bool $default Optional. Fallback value if not found in the database.
 *                      Default: false.
 * @return bool True if members able to use gravatars, otherwise false.
 */
function bboss_enable_profile_gravatar( $default = false ) {

	/**
	 * Filters whether or not members are able to use gravatars.
	 *
	 * @since BuddyBoss 1.0.9
	 *
	 * @param bool $value Whether or not members are able to use gravatars.
	 */
	return (bool) apply_filters( 'bp_enable_profile_gravatar', (bool) ( bboss_get_option( 'bp-enable-profile-gravatar', $default ) ) );
}

/**
 * Allow members to hide last name field if in display format first name selected.
 *
 * @since BuddyBoss 1.1.1
 *
 * @param bool $default Optional. Fallback value if not found in the database.
 *                      Default: true.
 * @return bool True if avatar uploads are disabled, otherwise false.
 */
function bboss_hide_last_name( $default = true ) {

	/**
	 * Filters whether or not members are able to hide last name field.
	 *
	 * @since BuddyBoss 1.1.1
	 *
	 * @param bool $value Whether or not members are able to hide last name field.
	 */
	return (bool) apply_filters( 'bp_hide_last_name', (bool) bboss_get_option( 'bp-hide-last-name', $default ) );
}

/**
 * Allow members to hide first name field if in display format nick name selected.
 *
 * @since BuddyBoss 1.1.1
 *
 * @param bool $default Optional. Fallback value if not found in the database.
 *                      Default: true.
 */
function bboss_hide_nickname_first_name( $default = true ) {

	/**
	 * Filters whether or not members are able to hide first name field if in display format nick name selected.
	 *
	 * @since BuddyBoss 1.1.1
	 *
	 * @param bool $value Whether or not members are able to hide first name field if in display format nick name selected.
	 */
	return (bool) apply_filters( 'bp_hide_nickname_first_name', (bool) bboss_get_option( 'bp-hide-nickname-first-name', $default ) );
}

/**
 * Allow members to hide last name field if in display format nick name selected.
 *
 * @since BuddyBoss 1.1.1
 *
 * @param bool $default Optional. Fallback value if not found in the database.
 *                      Default: true.
 * @return bool True if Whether or not members are able to hide last name field if in display format nick name selected.
 */
function bboss_hide_nickname_last_name( $default = true ) {

	/**
	 * Filters whether or not members are able to hide last name field if in display format nick name selected.
	 *
	 * @since BuddyBoss 1.1.1
	 *
	 * @param bool $value Whether or not members are able to hide last name field if in display format nick name selected.
	 */
	return (bool) apply_filters( 'bp_hide_nickname_last_name', (bool) bboss_get_option( 'bp-hide-nickname-last-name', $default ) );
}

/**
 * Display email confirmation field in registrations.
 *
 * @since BuddyBoss 1.1.6
 *
 * @param bool $default Optional. Fallback value if not found in the database.
 *                      Default: true.
 * @return bool True if Whether or not display email confirmation field in registrations.
 */
function bboss_register_confirm_email( $default = false ) {

	/**
	 * Filters whether or not display email confirmation field in registrations.
	 *
	 * @since BuddyBoss 1.1.6
	 *
	 * @param bool $value whether or not display email confirmation field in registrations.
	 */
	return (bool) apply_filters( 'bp_register_confirm_email', (bool) bboss_get_option( 'register-confirm-email', $default ) );
}

/**
 * Display legal agreement field in registrations.
 *
 * @since BuddyBoss 1.5.8.3
 *
 * @param bool $default Optional. Fallback value if not found in the database.
 *                      Default: false.
 * @return bool True if Whether or not display legal agreement field in registrations.
 */
function bboss_register_legal_agreement( $default = false ) {

	/**
	 * Filters whether or not display legal agreement field in registrations.
	 *
	 * @since BuddyBoss 1.5.8.3
	 *
	 * @param bool $value whether or not display legal agreement field in registrations.
	 */
	return (bool) apply_filters( 'bb_register_legal_agreement', (bool) bboss_get_option( 'register-legal-agreement', $default ) );
}

/**
 * Display password confirmation field in registrations.
 *
 * @since BuddyBoss 1.1.6
 *
 * @param bool $default Optional. Fallback value if not found in the database.
 *                      Default: true.
 * @return bool True if Whether or not display password confirmation field in registrations.
 */
function bboss_register_confirm_password( $default = false ) {

	/**
	 * Filters whether or not display password confirmation field in registrations.
	 *
	 * @since BuddyBoss 1.1.6
	 *
	 * @param bool $value whether or not display password confirmation field in registrations.
	 */
	return (bool) apply_filters( 'bp_register_confirm_password', (bool) bboss_get_option( 'register-confirm-password', $default ) );
}

/**
 * Default layout option for the members listing
 *
 * @since BuddyBoss 1.2.0
 *
 * @param string $default Optional. Fallback value if not found in the database.
 *                      Default: grid.
 *
 * @return string Profile layout format.
 */
function bboss_profile_layout_default_format( $default = 'grid' ) {

	/**
	 * Filters profile layout format.
	 *
	 * @since BuddyBoss 1.2.0
	 *
	 * @param bool $value Profile layout format.
	 */
	return apply_filters( 'bp_profile_layout_default_format', bboss_get_option( 'bp-profile-layout-default-format', $default ) );
}

/**
 * Default layout option for the groups listing
 *
 * @since BuddyBoss 1.2.0
 *
 * @param string $default Optional. Fallback value if not found in the database.
 *                      Default: grid.
 *
 * @return string Group layout format.
 */
function bboss_group_layout_default_format( $default = 'grid' ) {

	/**
	 * Filters group layout format.
	 *
	 * @since BuddyBoss 1.2.0
	 *
	 * @param bool $value Group layout format.
	 */
	return apply_filters( 'bp_group_layout_default_format', bboss_get_option( 'bp-group-layout-default-format', $default ) );
}

/**
 * Allow custom registration.
 *
 * @since BuddyBoss 1.2.8
 *
 * @param bool $default Optional. Fallback value if not found in the database.
 *                      Default: false.
 * @return bool True if Whether or not allow custom registrations.
 */
function bboss_allow_custom_registration( $default = false ) {

	/**
	 * Filters whether or not allow custom registrations.
	 *
	 * @since BuddyBoss 1.2.8
	 *
	 * @param bool $value whether or not allow custom registrations.
	 */
	return (bool) apply_filters( 'bp_allow_custom_registration', (bool) bboss_get_option( 'allow-custom-registration', $default ) );
}

/**
 * Register page URL.
 *
 * @since BuddyBoss 1.2.8
 *
 * @param string $default Optional. Fallback value if not found in the database.
 *                      Default: Empty string.
 *
 * @return string URL of register page.
 */
function bboss_custom_register_page_url( $default = '' ) {

	/**
	 * Filters custom registration page URL.
	 *
	 * @since BuddyBoss 1.2.8
	 *
	 * @param string $value custom registration page URL.
	 */
	return apply_filters( 'bp_custom_register_page_url', bboss_get_option( 'register-page-url', $default ) );
}

/**
 * Are group messages disabled?
 *
 * @since BuddyBoss 1.2.9
 *
 * @param bool $default Optional. Fallback value if not found in the database.
 *                      Default: false.
 * @return bool True if group message are disabled, otherwise false.
 */
function bboss_disable_group_messages( $default = false ) {

	/**
	 * Filters whether or not group organizer and moderator allowed to send group message.
	 *
	 * @since BuddyBoss 1.2.3
	 *
	 * @param bool $value whether or not group organizer and moderator allowed to send group message.
	 */
	return (bool) apply_filters( 'bp_disable_group_messages', (bool) bboss_get_option( 'bp-disable-group-messages', $default ) );
}

/**
 * Default display name format.
 *
 * @since BuddyBoss 1.5.1
 *
 * @param bool $default Optional. Fallback value if not found in the database.
 *                      Default: first_name.
 * @return string display name format.
 */
function bboss_core_display_name_format( $default = 'first_name' ) {

	/**
	 * Filters default display name format.
	 *
	 * @since BuddyBoss 1.5.1
	 *
	 * @param string $value Default display name format.
	 */
	return apply_filters( 'bp_core_display_name_format', bboss_get_option( 'bp-display-name-format', $default ) );
}

/**
 * Enable private REST APIs.
 * - Wrapper function to check settings with BuddyBoss APP and Platform both.
 *
 * @since BuddyBoss 1.5.7
 *
 * @return bool True if  private REST APIs is enabled, otherwise false.
 */
function bboss_rest_enable_private_network() {

	$retval = false;
	if (
		true === (bool) bboss_enable_private_rest_apis() &&
		(
			(
				function_exists( 'bbapp_is_private_app_enabled' ) // buddyboss-app is active.
				&& true === (bool) bbapp_is_private_app_enabled() // private app is disable.
			)
			|| ! function_exists( 'bbapp_is_private_app_enabled' )
		)
	) {
		$retval = true;
	}

	if ( true === $retval ) {
		$current_rest_url = $GLOBALS['wp']->query_vars['rest_route'];
		if ( ! empty( $current_rest_url ) && bboss_is_allowed_endpoint( $current_rest_url ) ) {
			$retval = false;
		}
	}

	/**
	 * Filters whether private private REST APIs is enabled.
	 *
	 * @since BuddyBoss 1.5.7
	 *
	 * @param bool $value Whether private REST APIs is enabled.
	 */
	return apply_filters( 'bp_rest_enable_private_network', $retval );
}

/**
 * Is the symlink is enabled in Media, Document & Video?
 *
 * @since BuddyBoss 1.7.0
 *
 * @param bool $default Optional. Fallback value if not found in the database.
 *                      Default: false.
 * @return bool True if the symlink is enabled in Media, Document & Video enable,
 *              otherwise false.
 */
function bboss_enable_symlinks( $default = false ) {

	/**
	 * Filters whether or not the symlink is enabled in Media, Document & Video.
	 *
	 * @since BuddyBoss 1.7.0
	 *
	 * @param bool $value Whether or not the symlink is enabled in Media, Document & Video enable.
	 */
	return (bool) apply_filters( 'bb_enable_symlinks', (bool) bboss_get_option( 'bp_media_symlink_support', $default ) );
}

/**
 * Option name for custom post type.
 * From the activity settings whether any custom post enable or disable for timeline feed.
 *
 * @since BuddyBoss 1.7.2
 *
 * @param bool $post_type custom post type.
 *
 * @return string.
 */
function bboss_post_type_feed_option_name( $post_type ) {
	return 'bp-feed-custom-post-type-' . $post_type;
}

/**
 * Option name for custom post type comments.
 * From the activity settings whether any custom post comments are enable or disable for timeline feed.
 *
 * @since BuddyBoss 1.7.2
 *
 * @param bool $post_type custom post type.
 *
 * @return string.
 */
function bboss_post_type_feed_comment_option_name( $post_type ) {
	return 'bp-feed-custom-post-type-' . $post_type . '-comments';
}

/**
 * Custom post types for activity settings.
 *
 * @since BuddyBoss 1.7.2
 *
 * @return array.
 */
function bboss_feed_post_types() {
	// Get all active custom post type.
	$post_types = get_post_types( array( 'public' => true ) );

	// Use array_diff to exclude specific post types.
	return array_diff( $post_types, bboss_feed_excluded_post_types() );
}

/**
 * Custom post types for activity settings.
 *
 * @since BuddyBoss 1.7.2
 *
 * @return array.
 */
function bboss_feed_not_allowed_comment_post_types() {
	$post_types = bboss_feed_post_types();

	$bp_exclude_cpt = array();
	foreach ( $post_types as $post_type ) {
		if ( ! post_type_supports( $post_type, 'comments' ) ) {
			$bp_exclude_cpt[] = $post_type;
		}
	}

	$bp_exclude_cpt = array_merge( array( 'forum', 'product', 'topic', 'reply', 'page', 'attachment', 'bp-group-type', 'bp-member-type' ), $bp_exclude_cpt );

	/**
	 * Function to exclude Custom post types for activity settings.
	 *
	 * @since BuddyBoss 2.4.60
	 */
	return apply_filters( 'bb_feed_not_allowed_comment_post_types', $bp_exclude_cpt );
}

/**
 * Enable private REST apis.
 *
 * @since BuddyBoss 1.8.6
 *
 * @param bool $default Optional. Fallback value if not found in the database.
 *                      Default: true.
 *
 * @return bool True if private network for site is enabled, otherwise
 *              false.
 */
function bboss_enable_private_rest_apis( $default = false ) {
	global $bp;

	if ( isset( $bp ) && isset( $bp->site_options ) && is_array( $bp->site_options ) && isset( $bp->site_options['bb-enable-private-rest-apis'] ) ) {
		$val = (bool) $bp->site_options['bb-enable-private-rest-apis'];
	} else {
		$val = (bool) bboss_get_option( 'bb-enable-private-rest-apis', $default );
	}

	/**
	 * Filters whether private REST apis for site is enabled.
	 *
	 * @since BuddyBoss 1.8.6
	 *
	 * @param bool $value Whether private network for site is enabled.
	 */
	return apply_filters( 'bp_enable_private_rest_apis', $val );
}

/**
 * Add APIs endpoint which will ignore even if private REST APIs is enabled.
 *
 * @since BuddyBoss 1.8.6
 *
 * @param string $default Optional. Fallback value if not found in the database.
 *                        Default: Empty string.
 *
 * @return string Private REST APIs public content.
 */
function bboss_enable_private_rest_apis_public_content( $default = '' ) {

	/**
	 * Filters Private REST APIs public content.
	 *
	 * @since BuddyBoss 1.8.6
	 *
	 * @param bool $value Private REST APIs public content.
	 */
	return apply_filters( 'bb_enable_private_rest_apis_public_content', bboss_get_option( 'bb-enable-private-rest-apis-public-content', '' ) );
}

/**
 * Enable private RSS feeds.
 *
 * @since BuddyBoss 1.8.6
 *
 * @param bool $default Optional. Fallback value if not found in the database.
 *                      Default: true.
 *
 * @return bool True if private network for site is enabled, otherwise
 *              false.
 */
function bboss_enable_private_rss_feeds( $default = false ) {
	global $bp;

	if ( isset( $bp ) && isset( $bp->site_options ) && is_array( $bp->site_options ) && isset( $bp->site_options['bb-enable-private-rss-feeds'] ) ) {
		$val = (bool) $bp->site_options['bb-enable-private-rss-feeds'];
	} else {
		$val = (bool) bboss_get_option( 'bb-enable-private-rss-feeds', $default );
	}

	/**
	 * Filters whether private REST apis for site is enabled.
	 *
	 * @since BuddyBoss 1.8.6
	 *
	 * @param bool $value Whether private network for site is enabled.
	 */
	return apply_filters( 'bp_enable_private_rss_feeds', $val );
}

/**
 * Add RSS feeds endpoint which will ignore even if private RSS feeds is enabled.
 *
 * @since BuddyBoss 1.8.6
 *
 * @param string $default Optional. Fallback value if not found in the database.
 *                        Default: Empty string.
 *
 * @return string Private RSS Feeds public content.
 */
function bboss_enable_private_rss_feeds_public_content( $default = '' ) {

	/**
	 * Filters Private REST APIs public content.
	 *
	 * @since BuddyBoss 1.8.6
	 *
	 * @param bool $value Private REST APIs public content.
	 */
	return apply_filters( 'bb_enable_private_rss_feeds_public_content', bboss_get_option( 'bb-enable-private-rss-feeds-public-content', '' ) );
}


/** Profile Avatar ************************************************************/
/**
 * Which type of profile avatar configured?
 *
 * @since BuddyBoss 1.8.6
 *
 * @param string|null $default Optional. Fallback value if not found in the database.
 *                          Default: 'BuddyBoss'.
 * @return string Return the default profile avatar type.
 */
function bboss_get_profile_avatar_type( $default = 'BuddyBoss' ) {

	/**
	 * Filters profile avatar type.
	 *
	 * @since BuddyBoss 1.8.6
	 *
	 * @param string $value Profile avatar type.
	 */
	return apply_filters( 'bb_get_profile_avatar_type', bboss_get_option( 'bp-profile-avatar-type', $default ) );
}

/**
 * Which type of default profile avatar selected?
 *
 * @since BuddyBoss 1.8.6
 *
 * @param string|null $default Optional. Fallback value if not found in the database.
 *                          Default: 'buddyboss'.
 * @return string Return the default profile avatar type.
 */
function bboss_get_default_profile_avatar_type( $default = 'buddyboss' ) {

	/**
	 * Filters default profile avatar type.
	 *
	 * @since BuddyBoss 1.8.6
	 *
	 * @param string $value Default profile avatar type.
	 */
	return apply_filters( 'bb_get_default_profile_avatar_type', bboss_get_option( 'bp-default-profile-avatar-type', $default ) );
}

/**
 * Get default custom upload avatar URL.
 *
 * @since BuddyBoss 1.8.6
 *
 * @param string $default Optional. Fallback value if not found in the database.
 *                        Default: Empty string.
 * @param string $size    This parameter specifies whether you'd like the 'full' or 'thumb' avatar. Default: 'full'.
 * @return string Return default custom upload avatar URL.
 */
function bboss_get_default_custom_upload_profile_avatar( $default = '', $size = 'full' ) {
	$custom_avatar_url = bboss_get_option( 'bp-default-custom-profile-avatar', $default );

	if ( ! empty( $custom_avatar_url ) && 'full' !== $size ) {
		$custom_avatar_url = bboss_get_default_custom_avatar();
	}

	/**
	 * Filters to change default custom upload avatar image.
	 *
	 * @since BuddyBoss 1.8.6
	 *
	 * @param string $custom_upload_profile_avatar Default custom upload avatar URL.
	 * @param string $size  This parameter specifies whether you'd like the 'full' or 'thumb' avatar.
	 */
	return apply_filters( 'bb_get_default_custom_upload_profile_avatar', $custom_avatar_url, $size );
}

/** Profile Cover ************************************************************/
/**
 * Which type of profile cover selected?
 *
 * @since BuddyBoss 1.8.6
 *
 * @param string|null $default Optional. Fallback value if not found in the database.
 *                          Default: 'buddyboss'.
 * @return string Return the default profile cover type.
 */
function bboss_get_default_profile_cover_type( $default = 'buddyboss' ) {

	/**
	 * Filters default profile cover type.
	 *
	 * @since BuddyBoss 1.8.6
	 *
	 * @param string $value Default profile cover type.
	 */
	return apply_filters( 'bb_get_default_profile_cover_type', bboss_get_option( 'bp-default-profile-cover-type', $default ) );
}

/**
 * Get default custom upload profile cover URL.
 *
 * @since BuddyBoss 1.8.6
 *
 * @return string Return default custom upload profile cover URL.
 */
function bboss_get_default_custom_upload_profile_cover() {
	/**
	 * Filters to change default custom upload cover image.
	 *
	 * @since BuddyBoss 1.8.6
	 *
	 * @param string $value Default custom upload profile cover URL.
	 */
	return apply_filters( 'bb_get_default_custom_upload_profile_cover', bboss_get_option( 'bp-default-custom-profile-cover' ) );
}

/** Group Avatar ************************************************************/
/**
 * Which type of group avatar selected?
 *
 * @since BuddyBoss 1.8.6
 *
 * @param string|null $default Optional. Fallback value if not found in the database.
 *                          Default: 'buddyboss'.
 * @return string Return the default group avatar type.
 */
function bboss_get_default_group_avatar_type( $default = 'buddyboss' ) {

	/**
	 * Filters default group avatar type.
	 *
	 * @since BuddyBoss 1.8.6
	 *
	 * @param string $value Default group avatar type.
	 */
	return apply_filters( 'bb_get_default_group_avatar_type', bboss_get_option( 'bp-default-group-avatar-type', $default ) );
}

/**
 * Get default custom upload avatar URL.
 *
 * @since BuddyBoss 1.8.6
 *
 * @param string $default Optional. Fallback value if not found in the database.
 *                        Default: Empty string.
 * @param string $size    This parameter specifies whether you'd like the 'full' or 'thumb' avatar. Default: 'full'.
 * @return string Return default custom upload avatar URL.
 */
function bboss_get_default_custom_upload_group_avatar( $default = '', $size = 'full' ) {
	$custom_group_avatar_url = bboss_get_option( 'bp-default-custom-group-avatar', $default );

	if ( ! empty( $custom_group_avatar_url ) && 'full' !== $size ) {
		$custom_group_avatar_url = bboss_get_default_custom_avatar( 'group' );
	}

	/**
	 * Filters to change default custom upload avatar image.
	 *
	 * @since BuddyBoss 1.8.6
	 *
	 * @param string $custom_upload_group_avatar Default custom upload avatar URL.
	 * @param string $size  This parameter specifies whether you'd like the 'full' or 'thumb' avatar.
	 */
	return apply_filters( 'bb_get_default_custom_upload_group_avatar', $custom_group_avatar_url, $size );
}

/** Group Cover ************************************************************/
/**
 * Which type of group cover selected?
 *
 * @since BuddyBoss 1.8.6
 *
 * @param string|null $default Optional. Fallback value if not found in the database.
 *                          Default: 'buddyboss'.
 * @return string Return the default group cover type.
 */
function bboss_get_default_group_cover_type( $default = 'buddyboss' ) {

	/**
	 * Filters default group cover type.
	 *
	 * @since BuddyBoss 1.8.6
	 *
	 * @param string $value Default group cover type.
	 */
	return apply_filters( 'bb_get_default_group_cover_type', bboss_get_option( 'bp-default-group-cover-type', $default ) );
}

/**
 * Get default custom upload group cover URL.
 *
 * @since BuddyBoss 1.8.6
 *
 * @param string|null $default Optional. Fallback value if not found in the database.
 *                             Default: null.
 * @return string Return default custom upload group cover URL.
 */
function bboss_get_default_custom_upload_group_cover() {
	/**
	 * Filters default custom upload cover image URL.
	 *
	 * @since BuddyBoss 1.8.6
	 *
	 * @param string $value Default custom upload group cover URL.
	 */
	return apply_filters( 'bb_get_default_custom_upload_group_cover', bboss_get_option( 'bp-default-custom-group-cover' ) );
}

/**
 * Get profile slug format.
 *
 * @since BuddyBoss 2.3.1
 *
 * @param string $default Optional. Fallback value if not found in the database.
 *                      Default: username.
 * @return string profile slug format.
 */
function bboss_get_profile_slug_format( $default = 'username' ) {

	/**
	 * Filters default profile slug format.
	 *
	 * @since BuddyBoss 2.3.1
	 *
	 * @param string $value Default profile slug format.
	 */
	return apply_filters( 'bb_get_profile_slug_format', bboss_get_option( 'bb_profile_slug_format', $default ) );
}

/**
 * Get domain restrictions setting value from the database.
 *
 * @since BuddyBoss 2.4.11
 *
 * @param string $default Optional. Fallback value if not found in the database.
 *                        Default: Empty string.
 * @return array Domain restrictions setting value.
 */
function bboss_domain_restrictions_setting( $default = array() ) {

	/**
	 * Filters domain restriction settings.
	 *
	 * @since BuddyBoss 2.4.11
	 *
	 * @param array $value Domain restrictions setting value.
	 */
	return apply_filters( 'bb_domain_restrictions_setting', bboss_get_option( 'bb-domain-restrictions', $default ) );
}

/**
 * Get email restrictions setting value from the database.
 *
 * @since BuddyBoss 2.4.11
 *
 * @param string $default Optional. Fallback value if not found in the database.
 *                        Default: Empty string.
 * @return array Email restrictions setting value.
 */
function bboss_email_restrictions_setting( $default = array() ) {

	/**
	 * Filters email restriction settings.
	 *
	 * @since BuddyBoss 2.4.11
	 *
	 * @param array $value Email restrictions setting value.
	 */
	return apply_filters( 'bb_email_restrictions_setting', bboss_get_option( 'bb-email-restrictions', $default ) );
}

/**
 * Check whether Activity comment edit is enabled.
 *
 * @since BuddyBoss 2.4.40
 *
 * @param bool $default Optional. Fallback value if not found in the database.
 *                      Default: false.
 * @return bool True if Edit is enabled, otherwise false.
 */
function bboss_is_activity_comment_edit_enabled( $default = false ) {

	/**
	 * Filters whether Activity comment edit is enabled.
	 *
	 * @since BuddyBoss 2.4.40
	 *
	 * @param bool $value Whether Activity comment edit is enabled.
	 */
	return (bool) apply_filters( 'bb_is_activity_comment_edit_enabled', (bool) bboss_get_option( '_bb_enable_activity_comment_edit', $default ) );
}

/**
 * Get BuddyBoss activity comment Time option.
 *
 * The stored value is normalised with bboss_activity_normalize_edit_time(): anything outside
 * bboss_activity_get_allowed_edit_times(), including an empty row, resolves to 600 (10 minutes).
 *
 * @since BuddyBoss 2.4.40
 * @since BuddyBoss 3.5.1 The return value is normalised to an allowed duration.
 *
 * @param mixed $default Value used when the option row is absent. Honoured only if it is an allowed duration.
 *
 * @return int Edit duration in seconds, or -1 for no limit.
 */
function bboss_get_activity_comment_edit_time( $default = false ) {
	$edit_time = bboss_activity_normalize_edit_time( bboss_get_option( '_bb_activity_comment_edit_time', $default ) );

	return apply_filters( 'bb_get_activity_comment_edit_time', $edit_time );
}

/**
 * Function to exclude Custom post types for activity settings.
 *
 * @since BuddyBoss 2.4.60
 *
 * @return array.
 */
function bboss_feed_excluded_post_types() {

	/**
	 * Function to exclude Custom post types for activity settings.
	 *
	 * @since BuddyBoss 2.4.60
	 */
	return apply_filters( 'bb_feed_excluded_post_types', array( 'forum', 'topic', 'reply', 'page', 'attachment', 'bp-group-type', 'bp-member-type' ) );
}

/**
 * Check whether activity pinned posts are enabled.
 *
 * @since BuddyBoss 2.4.60
 *
 * @param bool $default Optional. Fallback value if not found in the database.
 *                      Default: true.
 *
 * @return bool True    If activity pinned posts are enabled, otherwise false.
 */
function bboss_is_active_activity_pinned_posts( $default = false ) {

	$enabled = (bool) bboss_get_option( '_bb_enable_activity_pinned_posts', $default );

	if ( $enabled ) {
		/*
		 * Pinned Posts moved out of the free Platform into the BuddyBoss Addons
		 * plugin. The stored `_bb_enable_activity_pinned_posts` option can remain
		 * enabled from before that move, so the feature is only truly ACTIVE when
		 * the add-on is present AND licensed — otherwise the pin controls, the
		 * REST support and the add-on's own pin logic must all treat it as off.
		 *
		 * Two independent signals so the gate is load-order safe:
		 *   - the add-on's licensed-provider check (grace-period aware — the same
		 *     signal the add-on's own module loaders use), or
		 *   - the add-on's pin mutation function actually being loaded, EXCLUDING
		 *     Platform's own no-op shim. Platform defines a deprecation shim under
		 *     that same name from `bp_init:1` (so un-updated external callers
		 *     degrade instead of fatalling), which means a bare `function_exists()`
		 *     would report a provider on a site that has none. The shim advertises
		 *     itself via `bb_activity_pin_unpin_post_is_stub()`, so testing for its
		 *     absence keeps this term honest.
		 *
		 * LIMITATION: the licence check proves the add-on plugin is active and
		 * entitled, NOT that the Pinned Posts MODULE finished loading (it has its
		 * own guards — activity component active, dormancy marker, module present).
		 * If the module is dormant on a licensed site, this reports the feature
		 * active while no real pin implementation exists; the REST route then
		 * answers a structured 501 (never a fatal) and no pin UI renders.
		 * The licence term is only needed for the early `bb_register_features`
		 * call, before the module loads at bp_include:20.
		 */
		$provider_available =
			bboss_addons_provides_module( 'pinned-posts' )
			|| (
				function_exists( 'bboss_activity_pin_unpin_post' )
				&& ! function_exists( 'bboss_activity_pin_unpin_post_is_stub' )
			);

		if ( ! $provider_available ) {
			$enabled = false;
		}
	}

	/**
	 * Filters whether activity pinned posts are enabled.
	 *
	 * @since BuddyBoss 2.4.60
	 *
	 * @param bool $value Whether activity pinned posts are enabled.
	 */
	return (bool) apply_filters( 'bb_is_active_activity_pinned_posts', $enabled );
}

/**
 * Get login redirection settings value from the database.
 *
 * @since BuddyBoss 2.4.70
 *
 * @param string $default Optional. Fallback value if not found in the database.
 *                        Default: Empty string.
 *
 * @return string Login redirection setting value.
 */
function bboss_login_redirection( $default = '' ) {

	/**
	 * Filters login redirection settings.
	 *
	 * @since BuddyBoss 2.4.70
	 *
	 * @param string $value Login redirection setting value.
	 */
	return apply_filters( 'bb_login_redirection', bboss_get_option( 'bb-login-redirection', $default ) );
}

/**
 * Get logout redirection setting value from the database.
 *
 * @since BuddyBoss 2.4.70
 *
 * @param string $default Optional. Fallback value if not found in the database.
 *                        Default: Empty string.
 *
 * @return string Logout redirection setting value.
 */
function bboss_logout_redirection( $default = '' ) {

	/**
	 * Filters logout redirection settings.
	 *
	 * @since BuddyBoss 2.4.70
	 *
	 * @param string $value Logout redirection setting value.
	 */
	return apply_filters( 'bb_logout_redirection', bboss_get_option( 'bb-logout-redirection', $default ) );
}

/**
 * Get custom login redirection setting value from the database.
 *
 * @since BuddyBoss 2.4.70
 *
 * @param string $default Optional. Fallback value if not found in the database.
 *                        Default: Empty string.
 *
 * @return string Login redirection setting value.
 */
function bboss_custom_login_redirection( $default = '' ) {

	/**
	 * Filters custom login page URL.
	 *
	 * @since BuddyBoss 2.4.70
	 *
	 * @param string $value Custom login page URL.
	 */
	return apply_filters( 'bb_custom_login_redirection', bboss_get_option( 'bb-custom-login-redirection', $default ) );
}

/**
 * Get custom logout redirection setting value from the database.
 *
 * @since BuddyBoss 2.4.70
 *
 * @param string $default Optional. Fallback value if not found in the database.
 *                        Default: Empty string.
 *
 * @return string Logout redirection setting value.
 */
function bboss_custom_logout_redirection( $default = '' ) {

	/**
	 * Filters custom logout page URL.
	 *
	 * @since BuddyBoss 2.4.70
	 *
	 * @param string $value Custom logout page URL.
	 */
	return apply_filters( 'bb_custom_logout_redirection', bboss_get_option( 'bb-custom-logout-redirection', $default ) );
}

/**
 * Get all reaction types.
 *
 * @since BuddyBoss 2.5.20
 *
 * @return array[]
 */
function bboss_get_all_reactions() {
	return array(
		'activity'         => array(
			'label'     => esc_html__( 'Activity posts', 'buddyboss' ),
			'disabled'  => ! bboss_is_active( 'activity' ),
			'component' => 'activity',
		),
		'activity_comment' => array(
			'label'     => esc_html__( 'Activity comments', 'buddyboss' ),
			'disabled'  => ! bboss_is_active( 'activity' ),
			'component' => 'activity',
		),
	);
}

/**
 * Check whether Reaction is enabled based on key.
 *
 * @param string $key Key of the reaction.
 *
 * @since BuddyBoss 2.5.20
 *
 * @return array|bool
 */
function bboss_all_enabled_reactions( $key = '' ) {
	$get_reactions = (array) bboss_get_option( 'bb_all_reactions', array() );
	if ( empty( $key ) ) {
		return $get_reactions;
	}

	$all_reactions = bboss_get_all_reactions();

	return (bool) (
		isset( $get_reactions[ $key ] ) &&
		$all_reactions[ $key ] &&
		! empty( $all_reactions[ $key ]['component'] ) &&
		bboss_is_active( $all_reactions[ $key ]['component'] ) &&
		$get_reactions[ $key ]
	);
}

/**
 * Check whether Reaction for activity posts is enabled.
 *
 * @since BuddyBoss 2.5.20
 *
 * @param bool $default Optional. Fallback value if not found in the database.
 *                      Default: true.
 * @return bool True if reaction for activity posts is enabled, otherwise false.
 */
function bboss_is_reaction_activity_posts_enabled( $default = true ) {
	// First check if reactions feature is enabled via Settings 2.0 toggle.
	if ( ! bboss_is_reactions_feature_enabled() ) {
		return false;
	}

	return (bool) apply_filters( 'bb_is_reaction_activity_posts_enabled', (bool) bboss_all_enabled_reactions( 'activity' ) );
}

/**
 * Check whether Reaction for activity comments is enabled.
 *
 * @since BuddyBoss 2.5.20
 *
 * @param bool $default Optional. Fallback value if not found in the database.
 *                      Default: True.
 * @return bool True if reaction for activity comments is enabled, otherwise false.
 */
function bboss_is_reaction_activity_comments_enabled( $default = true ) {
	// First check if reactions feature is enabled via Settings 2.0 toggle.
	if ( ! bboss_is_reactions_feature_enabled() ) {
		return false;
	}

	return (bool) apply_filters( 'bb_is_reaction_activity_comments_enabled', (bool) bboss_all_enabled_reactions( 'activity_comment' ) );
}

/**
 * Get currently active reaction mode.
 *
 * @since BuddyBoss 2.5.20
 *
 * @param string $default Optional. Fallback value if not found in the database.
 *                        Default: 'likes'
 *
 * @return string The reaction mode, either 'likes' or 'emotions'.
 */
function bboss_get_reaction_mode( $default = 'likes' ) {

	$mode = bboss_get_option( 'bb_reaction_mode', $default );

	if ( 'emotions' === $mode ) {
		// "Emotions" requires the Pro emotion layer, provided by either BuddyBoss
		// Platform Pro (legacy) or the BuddyBoss Addons plugin. Detect provider
		// availability in a load-order-independent way: the BB_Reactions class is
		// loaded late (bb_after_register_features), so a bare class_exists() check
		// run during earlier hooks (e.g. bb_register_features) would spuriously
		// report the layer missing.
		//
		// IMPORTANT: only fall back for DISPLAY — never persist the downgrade.
		// Writing 'likes' back from this getter silently corrupted a valid saved
		// 'emotions' setting on every request where the provider had not booted
		// yet (and, on addon-only sites, permanently), which made the Reactions
		// mode appear un-saveable.
		//
		// The third branch is gated on bp_register_reaction() — a marker defined
		// only by a legacy Pro build that still ships the emotion layer itself.
		// A licensed Pro post-PROD-10191 no longer provides reactions (that moved
		// to the add-on), so bbp_pro_is_license_valid() alone must not be treated
		// as proof of a provider; without this gate a new-Pro + no-add-on +
		// licensed site would report emotions "available" with nothing behind it.
		$emotion_layer_available =
			class_exists( 'BB_Reactions' )
			|| bboss_addons_provides_module( 'reactions' )
			|| (
				function_exists( 'bp_register_reaction' )
				&& function_exists( 'bbp_pro_is_license_valid' )
				&& bbp_pro_is_license_valid()
			);

		if ( ! $emotion_layer_available ) {
			$mode = 'likes';
		}
	}

	return apply_filters( 'bb_get_reaction_mode', $mode );
}

/**
 * Check whether emotions is enabled.
 *
 * @since BuddyBoss 2.5.20
 *
 * @return bool
 */
function bboss_is_reaction_emotions_enabled() {
	// First check if reactions feature is enabled via Settings 2.0 toggle.
	if ( ! bboss_is_reactions_feature_enabled() ) {
		return false;
	}

	return (bool) apply_filters( 'bb_is_reaction_emotions_enabled', (bool) ( bboss_get_reaction_mode() === 'emotions' ) );
}

/**
 * Get data for reaction button options.
 *
 * @since BuddyBoss 2.5.20
 *
 * @param string $key Key name for the option.
 *
 * @return array|mixed
 */
function bboss_reaction_button_options( $key = '' ) {
	$button_settings = (array) bboss_get_option( 'bb_reactions_button', array() );
	if ( empty( $key ) ) {
		return $button_settings;
	}

	return $button_settings[ $key ];
}

/**
 * Fetch the enabled reactions based on mode.
 *
 * @since BuddyBoss 2.5.20
 *
 * @return array
 */
function bboss_active_reactions() {
	$reaction = bboss_load_reaction();
	if ( ! $reaction ) {
		return array();
	}

	if ( bboss_is_reaction_emotions_enabled() ) {
		$all_emotions = $reaction->bb_get_reactions( 'emotions' );
	} else {
		$all_emotions = $reaction->bb_get_reactions();
	}

	return ( ! empty( $all_emotions ) ? array_column( $all_emotions, null, 'id' ) : array() );
}

/**
 * Get Page requests option.
 *
 * @since BuddyBoss 2.5.80
 *
 * @param int $default when option not found, function will return $default value.
 *
 * @return int
 */
function bboss_get_ajax_request_page_load( $default = 2 ) {
	return (int) apply_filters( 'bb_get_ajax_request_page_load', bboss_get_option( 'bb_ajax_request_page_load', $default ) );
}

/**
 * Get an Activity loading option.
 *
 * @since BuddyBoss 2.5.80
 *
 * @param int $default when option not found, function will return $default value.
 *
 * @return int
 */
function bboss_get_load_activity_per_request( $default = 10 ) {
	return (int) apply_filters( 'bb_get_load_activity_per_request', bboss_get_option( 'bb_load_activity_per_request', $default ) );
}

/**
 * Function to check the send ajax request to load main content.
 *
 * @since BuddyBoss 2.5.80
 *
 * @return bool
 */
function bboss_is_send_ajax_request() {
	return (bool) ( 2 === bboss_get_ajax_request_page_load() );
}

/**
 * Determines whether to show counts at the pages like Memebers, Groups etc.
 *
 * @since BuddyBoss 2.8.10
 *
 * @param bool $default Optional. Default value to use if the option is not set. Default true.
 *
 * @return bool true if counts should be displayed, false otherwise.
 */
function bboss_enable_content_counts( $default = false ) {

	/**
	 * Filter to modify the behavior of the group counts feature.
	 *
	 * @since BuddyBoss 2.8.10
	 *
	 * @param bool $show_counts Whether to show group counts. Default is the value retrieved from the settings.
	 * @param int  $default     The default value if the setting is not configured. Default false.
	 */
	return (bool) apply_filters( 'bb_enable_content_counts', (bool) bboss_get_option( 'bb-enable-content-counts', $default ) );
}

/**
 * Get all activity filters option labels.
 *
 * @since BuddyBoss 2.8.20
 * @since BuddyBoss 2.18.0 Added $context parameter for language-specific capitalization.
 *
 * @param string $context The context for labels. Accepts 'default' for dropdown labels,
 *                        'show_context' for "Show: {label}" context (lowercase for proper grammar in some languages).
 *                        Default 'default'.
 *
 * @return array Array of all activity filters option labels.
 */
function bboss_get_activity_filter_options_labels( $context = 'default' ) {
	$filters = array(
		'all'        => array(
			/* translators: Activity filter label shown in dropdown menus */
			'default'      => __( 'All Updates', 'buddyboss' ),
			/* translators: Activity filter label shown after "Show:" text - use lowercase */
			'show_context' => __( 'all updates', 'buddyboss' ),
		),
		'just-me'    => array(
			/* translators: Activity filter label shown in dropdown menus */
			'default'      => __( 'Created by Me', 'buddyboss' ),
			/* translators: Activity filter label shown after "Show:" text - use lowercase */
			'show_context' => __( 'created by me', 'buddyboss' ),
		),
		'favorites'  => array(
			/* translators: Activity filter label shown in dropdown menus */
			'default'      => __( "I've Reacted To", 'buddyboss' ),
			/* translators: Activity filter label shown after "Show:" text - use lowercase */
			'show_context' => __( "I've reacted to", 'buddyboss' ),
		),
		'groups'     => array(
			/* translators: Activity filter label shown in dropdown menus */
			'default'      => __( 'From My Groups', 'buddyboss' ),
			/* translators: Activity filter label shown after "Show:" text - use lowercase */
			'show_context' => __( 'from my groups', 'buddyboss' ),
		),
		'friends'    => array(
			/* translators: Activity filter label shown in dropdown menus */
			'default'      => __( 'From My Connections', 'buddyboss' ),
			/* translators: Activity filter label shown after "Show:" text - use lowercase */
			'show_context' => __( 'from my connections', 'buddyboss' ),
		),
		'mentions'   => array(
			/* translators: Activity filter label shown in dropdown menus */
			'default'      => __( "I'm Mentioned In", 'buddyboss' ),
			/* translators: Activity filter label shown after "Show:" text - use lowercase */
			'show_context' => __( "I'm mentioned in", 'buddyboss' ),
		),
		'following'  => array(
			/* translators: Activity filter label shown in dropdown menus */
			'default'      => __( "I'm Following", 'buddyboss' ),
			/* translators: Activity filter label shown after "Show:" text - use lowercase */
			'show_context' => __( "I'm following", 'buddyboss' ),
		),
		'unanswered' => array(
			/* translators: Activity filter label shown in dropdown menus */
			'default'      => __( 'Unanswered', 'buddyboss' ),
			/* translators: Activity filter label shown after "Show:" text - use lowercase */
			'show_context' => __( 'unanswered', 'buddyboss' ),
		),
	);

	// Extract labels for the requested context.
	$labels = array_map(
		function ( $filter ) use ( $context ) {
			if ( is_array( $filter ) ) {
				return $filter[ $context ] ?? $filter['default'];
			}
			return $filter;
		},
		$filters
	);

	// Common function to get only allowed ones.
	$labels = bboss_filter_activity_filter_scope_keys( $labels );

	/**
	 * Filters the activity filter options labels.
	 *
	 * @since BuddyBoss 2.8.20
	 * @since BuddyBoss 2.18.0 Added $context parameter.
	 *
	 * @param array  $labels  Array of activity filter labels. Keys are scope names (e.g., 'all', 'just-me', 'favorites').
	 *                        Values are the translated label strings for the requested context.
	 * @param string $context The context for labels. 'default' for dropdown labels,
	 *                        'show_context' for labels shown after "Show:" text.
	 */
	return (array) apply_filters( 'bb_get_activity_filter_options_labels', $labels, $context );
}

/**
 * Get enabled activity filters options.
 *
 * @since BuddyBoss 2.8.20
 *
 * @param array $args Array of default activity filter options.
 *
 * @return array Array of enabled activity filters options.
 */
function bboss_get_enabled_activity_filter_options( $args = array() ) {

	// Set default options if not provided.
	$default = array(
		'all'        => 1,
		'just-me'    => 1,
		'favorites'  => 1,
		'groups'     => 1,
		'friends'    => 1,
		'mentions'   => 1,
		'following'  => 1,
		'unanswered' => 1,
	);

	$args    = array_intersect_key( $args, $default );
	$args    = wp_parse_args( $args, $default );
	$options = bboss_get_option( 'bb_activity_filter_options', $args );

	// Always ensure 'all' is enabled.
	if ( empty( $options['all'] ) ) {
		$options['all'] = 1;
	}

	return (array) apply_filters( 'bb_get_enabled_activity_filter_options', $options );
}

/**
 * Get all activity timeline filters option labels.
 *
 * @since BuddyBoss 2.8.20
 * @since BuddyBoss 2.18.0 Added $context parameter for language-specific capitalization.
 *
 * @param string $context The context for labels. Accepts 'default' for dropdown labels,
 *                        'show_context' for "Show: {label}" context (lowercase for proper grammar in some languages).
 *                        Default 'default'.
 *
 * @return array Array of all activity timeline filters option labels.
 */
function bboss_get_activity_timeline_filter_options_labels( $context = 'default' ) {
	$filters = array(
		'just-me'   => array(
			/* translators: Timeline filter label shown in dropdown menus */
			'default'      => __( 'Personal Posts', 'buddyboss' ),
			/* translators: Timeline filter label shown after "Show:" text - use lowercase if grammatically appropriate in your language */
			'show_context' => __( 'personal posts', 'buddyboss' ),
		),
		'favorites' => array(
			/* translators: Timeline filter label shown in dropdown menus */
			'default'      => __( 'Reacted To', 'buddyboss' ),
			/* translators: Timeline filter label shown after "Show:" text - use lowercase if grammatically appropriate in your language */
			'show_context' => __( 'reacted to', 'buddyboss' ),
		),
		'groups'    => array(
			/* translators: Timeline filter label shown in dropdown menus */
			'default'      => __( 'From Groups', 'buddyboss' ),
			/* translators: Timeline filter label shown after "Show:" text - use lowercase if grammatically appropriate in your language */
			'show_context' => __( 'from groups', 'buddyboss' ),
		),
		'friends'   => array(
			/* translators: Timeline filter label shown in dropdown menus */
			'default'      => __( 'From Connections', 'buddyboss' ),
			/* translators: Timeline filter label shown after "Show:" text - use lowercase if grammatically appropriate in your language */
			'show_context' => __( 'from connections', 'buddyboss' ),
		),
		'mentions'  => array(
			/* translators: Timeline filter label shown in dropdown menus */
			'default'      => __( 'Mentioned In', 'buddyboss' ),
			/* translators: Timeline filter label shown after "Show:" text - use lowercase if grammatically appropriate in your language */
			'show_context' => __( 'mentioned in', 'buddyboss' ),
		),
		'following' => array(
			/* translators: Timeline filter label shown in dropdown menus */
			'default'      => __( 'Following', 'buddyboss' ),
			/* translators: Timeline filter label shown after "Show:" text - use lowercase if grammatically appropriate in your language */
			'show_context' => __( 'following', 'buddyboss' ),
		),
	);

	// Extract labels for the requested context.
	$labels = array_map(
		function ( $filter ) use ( $context ) {
			if ( is_array( $filter ) ) {
				return $filter[ $context ] ?? $filter['default'];
			}
			return $filter;
		},
		$filters
	);

	// Common function to get only allowed ones.
	$labels = bboss_filter_activity_filter_scope_keys( $labels );

	/**
	 * Filters the activity timeline filter options labels.
	 *
	 * @since BuddyBoss 2.8.20
	 * @since BuddyBoss 2.18.0 Added $context parameter.
	 *
	 * @param array  $labels  Array of activity timeline filter labels. Keys are scope names (e.g., 'just-me', 'favorites', 'groups').
	 *                        Values are the translated label strings for the requested context.
	 * @param string $context The context for labels. 'default' for dropdown labels,
	 *                        'show_context' for labels shown after "Show:" text.
	 */
	return (array) apply_filters( 'bb_get_activity_timeline_filter_options_labels', $labels, $context );
}

/**
 * Get enabled activity timeline filters options.
 *
 * @since BuddyBoss 2.8.20
 *
 * @param array $args Array of default activity timeline filter options.
 *
 * @return array Array of enabled activity timeline filters options.
 */
function bboss_get_enabled_activity_timeline_filter_options( $args = array() ) {

	// Set default options if not provided.
	$default = array(
		'just-me'   => 1,
		'favorites' => 1,
		'groups'    => 1,
		'friends'   => 1,
		'mentions'  => 1,
		'following' => 1,
	);

	$args    = array_intersect_key( $args, $default );
	$args    = wp_parse_args( $args, $default );
	$options = bboss_get_option( 'bb_activity_timeline_filter_options', $args );

	// Always ensure 'just-me' is enabled.
	if ( empty( $options['just-me'] ) ) {
		$options['just-me'] = 1;
	}

	return (array) apply_filters( 'bb_get_enabled_activity_timeline_filter_options', $options );
}

/**
 * Get all activity sorting options labels.
 *
 * @since BuddyBoss 2.8.20
 * @since BuddyBoss 2.18.0 Added $context parameter for language-specific capitalization.
 *
 * @param string $context The context for labels. Accepts 'default' for dropdown labels,
 *                        'by_context' for "by {label}" context (lowercase for proper grammar in some languages).
 *                        Default 'default'.
 *
 * @return array Array of all activity sorting options labels.
 */
function bboss_get_activity_sorting_options_labels( $context = 'default' ) {
	$sorting_options = array(
		'date_recorded' => array(
			/* translators: Sorting option label shown in dropdown menus */
			'default'    => __( 'New Posts', 'buddyboss' ),
			/* translators: Sorting option label shown after "by" text - use lowercase if grammatically appropriate in your language */
			'by_context' => __( 'new posts', 'buddyboss' ),
		),
		'date_updated'  => array(
			/* translators: Sorting option label shown in dropdown menus */
			'default'    => __( 'Recent Activity', 'buddyboss' ),
			/* translators: Sorting option label shown after "by" text - use lowercase if grammatically appropriate in your language */
			'by_context' => __( 'recent activity', 'buddyboss' ),
		),
	);

	// Extract labels for the requested context.
	$labels = array_map(
		function ( $option ) use ( $context ) {
			if ( is_array( $option ) ) {
				return $option[ $context ] ?? $option['default'];
			}
			return $option;
		},
		$sorting_options
	);

	/**
	 * Filters the activity sorting options labels.
	 *
	 * @since BuddyBoss 2.8.20
	 * @since BuddyBoss 2.18.0 Added $context parameter.
	 *
	 * @param array  $labels  Array of activity sorting labels. Keys are sorting keys (e.g., 'date_recorded', 'date_updated').
	 *                        Values are the translated label strings for the requested context.
	 * @param string $context The context for labels. 'default' for dropdown labels,
	 *                        'by_context' for labels shown after "by" text.
	 */
	return (array) apply_filters( 'bb_get_activity_sorting_options_labels', $labels, $context );
}

/**
 * Get enabled activity sorting options.
 *
 * @since BuddyBoss 2.8.20
 *
 * @param array $args Array of default activity sorting options.
 *
 * @return array Array of enabled activity sorting options.
 */
function bboss_get_enabled_activity_sorting_options( $args = array() ) {

	// Set default options if not provided.
	$default = array(
		'date_recorded' => 1,
		'date_updated'  => 1,
	);

	$args    = array_intersect_key( $args, $default );
	$args    = wp_parse_args( $args, $default );
	$options = bboss_get_option( 'bb_activity_sorting_options', $args );
	$options = array_map( 'intval', $options );

	// Always ensure 'date_recorded' is enabled.
	if ( empty( $options['date_recorded'] ) ) {
		$options['date_recorded'] = 1;
	}

	return (array) apply_filters( 'bb_get_enabled_activity_sorting_options', $options );
}

/**
 * Check whether activity search is enabled.
 *
 * @since BuddyBoss 2.8.20
 *
 * @param bool $default Default: true.
 *
 * @return bool True if activity search enabled.
 */
function bboss_is_activity_search_enabled( $default = true ) {

	/**
	 * Filters whether activity search is enabled.
	 *
	 * @since BuddyBoss 2.8.20
	 *
	 * @param bool $value Is activity search enabled.
	 */
	return (bool) apply_filters( 'bb_is_activity_search_enabled', (bool) bboss_get_option( 'bb_enable_activity_search', $default ) );
}

/**
 * Check whether the Reactions feature is enabled via Settings 2.0 feature toggle.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param bool $reset Optional. Pass true to clear the static cache. Default false.
 *
 * @return bool True if reactions feature is enabled, false otherwise.
 */
function bboss_is_reactions_feature_enabled( $reset = false ) {
	static $is_enabled = null;

	if ( $reset ) {
		$is_enabled = null;
	}

	if ( null !== $is_enabled ) {
		return $is_enabled;
	}

	// Check if the feature registry exists (Settings 2.0).
	if ( ! function_exists( 'bboss_feature_registry' ) ) {
		// Fallback: if no feature registry, assume enabled for backward compatibility.
		$is_enabled = true;
		return $is_enabled;
	}

	// Check the bb-active-features option directly for reactions.
	$active_features = bboss_get_option( 'bb-active-features', array() );

	// Backward compatibility: if 'reactions' key not set (e.g. site not yet saved from Settings 2.0),
	// treat as enabled so existing sites keep reactions. Must match is_active_callback in
	// bboss-features/community/reactions/bboss-feature-config.php so feature card and functionality stay in sync.
	if ( ! array_key_exists( 'reactions', $active_features ) ) {
		$is_enabled = true;
		return $is_enabled;
	}

	$is_enabled = ! empty( $active_features['reactions'] );
	return $is_enabled;
}

/**
 * Reset static cache when a feature is toggled on/off.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param string $feature_id The feature ID that was toggled.
 */
function bboss_reset_reactions_feature_cache( $feature_id ) {
	if ( 'reactions' === $feature_id ) {
		bboss_is_reactions_feature_enabled( true );
	}
}
add_action( 'bb_feature_activated', 'bb_reset_reactions_feature_cache' );
add_action( 'bb_feature_deactivated', 'bb_reset_reactions_feature_cache' );

// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename names of this file's symbols (decision 0013): declared
// here, guarded on the renamed declaration, so each old name exists exactly when the new one does.
if ( function_exists( 'bboss_active_reactions' ) && ! function_exists( 'bb_active_reactions' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_active_reactions()}.
	 *
	 * @deprecated Use bboss_active_reactions() instead.
	 */
	function bb_active_reactions() {
		return bboss_active_reactions();
	}
}
if ( function_exists( 'bboss_activity_get_allowed_edit_times' ) && ! function_exists( 'bb_activity_get_allowed_edit_times' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_activity_get_allowed_edit_times()}.
	 *
	 * @deprecated Use bboss_activity_get_allowed_edit_times() instead.
	 */
	function bb_activity_get_allowed_edit_times() {
		return bboss_activity_get_allowed_edit_times();
	}
}
if ( function_exists( 'bboss_activity_normalize_comment_threading_depth' ) && ! function_exists( 'bb_activity_normalize_comment_threading_depth' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_activity_normalize_comment_threading_depth()}.
	 *
	 * @deprecated Use bboss_activity_normalize_comment_threading_depth() instead.
	 */
	function bb_activity_normalize_comment_threading_depth( $value ) {
		return bboss_activity_normalize_comment_threading_depth( $value );
	}
}
if ( function_exists( 'bboss_activity_normalize_edit_time' ) && ! function_exists( 'bb_activity_normalize_edit_time' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_activity_normalize_edit_time()}.
	 *
	 * @deprecated Use bboss_activity_normalize_edit_time() instead.
	 */
	function bb_activity_normalize_edit_time( $value, $default = 600 ) {
		return bboss_activity_normalize_edit_time( $value, $default );
	}
}
if ( function_exists( 'bboss_all_enabled_reactions' ) && ! function_exists( 'bb_all_enabled_reactions' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_all_enabled_reactions()}.
	 *
	 * @deprecated Use bboss_all_enabled_reactions() instead.
	 */
	function bb_all_enabled_reactions( $key = '' ) {
		return bboss_all_enabled_reactions( $key );
	}
}
if ( function_exists( 'bboss_custom_login_redirection' ) && ! function_exists( 'bb_custom_login_redirection' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_custom_login_redirection()}.
	 *
	 * @deprecated Use bboss_custom_login_redirection() instead.
	 */
	function bb_custom_login_redirection( $default = '' ) {
		return bboss_custom_login_redirection( $default );
	}
}
if ( function_exists( 'bboss_custom_logout_redirection' ) && ! function_exists( 'bb_custom_logout_redirection' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_custom_logout_redirection()}.
	 *
	 * @deprecated Use bboss_custom_logout_redirection() instead.
	 */
	function bb_custom_logout_redirection( $default = '' ) {
		return bboss_custom_logout_redirection( $default );
	}
}
if ( function_exists( 'bboss_domain_restrictions_setting' ) && ! function_exists( 'bb_domain_restrictions_setting' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_domain_restrictions_setting()}.
	 *
	 * @deprecated Use bboss_domain_restrictions_setting() instead.
	 */
	function bb_domain_restrictions_setting( $default = array() ) {
		return bboss_domain_restrictions_setting( $default );
	}
}
if ( function_exists( 'bboss_email_restrictions_setting' ) && ! function_exists( 'bb_email_restrictions_setting' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_email_restrictions_setting()}.
	 *
	 * @deprecated Use bboss_email_restrictions_setting() instead.
	 */
	function bb_email_restrictions_setting( $default = array() ) {
		return bboss_email_restrictions_setting( $default );
	}
}
if ( function_exists( 'bboss_enable_content_counts' ) && ! function_exists( 'bb_enable_content_counts' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_enable_content_counts()}.
	 *
	 * @deprecated Use bboss_enable_content_counts() instead.
	 */
	function bb_enable_content_counts( $default = false ) {
		return bboss_enable_content_counts( $default );
	}
}
if ( function_exists( 'bboss_enable_private_rest_apis_public_content' ) && ! function_exists( 'bb_enable_private_rest_apis_public_content' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_enable_private_rest_apis_public_content()}.
	 *
	 * @deprecated Use bboss_enable_private_rest_apis_public_content() instead.
	 */
	function bb_enable_private_rest_apis_public_content( $default = '' ) {
		return bboss_enable_private_rest_apis_public_content( $default );
	}
}
if ( function_exists( 'bboss_enable_private_rss_feeds_public_content' ) && ! function_exists( 'bb_enable_private_rss_feeds_public_content' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_enable_private_rss_feeds_public_content()}.
	 *
	 * @deprecated Use bboss_enable_private_rss_feeds_public_content() instead.
	 */
	function bb_enable_private_rss_feeds_public_content( $default = '' ) {
		return bboss_enable_private_rss_feeds_public_content( $default );
	}
}
if ( function_exists( 'bboss_enable_symlinks' ) && ! function_exists( 'bb_enable_symlinks' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_enable_symlinks()}.
	 *
	 * @deprecated Use bboss_enable_symlinks() instead.
	 */
	function bb_enable_symlinks( $default = false ) {
		return bboss_enable_symlinks( $default );
	}
}
if ( function_exists( 'bboss_feed_excluded_post_types' ) && ! function_exists( 'bb_feed_excluded_post_types' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_feed_excluded_post_types()}.
	 *
	 * @deprecated Use bboss_feed_excluded_post_types() instead.
	 */
	function bb_feed_excluded_post_types() {
		return bboss_feed_excluded_post_types();
	}
}
if ( function_exists( 'bboss_feed_not_allowed_comment_post_types' ) && ! function_exists( 'bb_feed_not_allowed_comment_post_types' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_feed_not_allowed_comment_post_types()}.
	 *
	 * @deprecated Use bboss_feed_not_allowed_comment_post_types() instead.
	 */
	function bb_feed_not_allowed_comment_post_types() {
		return bboss_feed_not_allowed_comment_post_types();
	}
}
if ( function_exists( 'bboss_feed_post_types' ) && ! function_exists( 'bb_feed_post_types' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_feed_post_types()}.
	 *
	 * @deprecated Use bboss_feed_post_types() instead.
	 */
	function bb_feed_post_types() {
		return bboss_feed_post_types();
	}
}
if ( function_exists( 'bboss_get_activity_comment_edit_time' ) && ! function_exists( 'bb_get_activity_comment_edit_time' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_get_activity_comment_edit_time()}.
	 *
	 * @deprecated Use bboss_get_activity_comment_edit_time() instead.
	 */
	function bb_get_activity_comment_edit_time( $default = false ) {
		return bboss_get_activity_comment_edit_time( $default );
	}
}
if ( function_exists( 'bboss_get_activity_filter_options_labels' ) && ! function_exists( 'bb_get_activity_filter_options_labels' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_get_activity_filter_options_labels()}.
	 *
	 * @deprecated Use bboss_get_activity_filter_options_labels() instead.
	 */
	function bb_get_activity_filter_options_labels( $context = 'default' ) {
		return bboss_get_activity_filter_options_labels( $context );
	}
}
if ( function_exists( 'bboss_get_activity_sorting_options_labels' ) && ! function_exists( 'bb_get_activity_sorting_options_labels' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_get_activity_sorting_options_labels()}.
	 *
	 * @deprecated Use bboss_get_activity_sorting_options_labels() instead.
	 */
	function bb_get_activity_sorting_options_labels( $context = 'default' ) {
		return bboss_get_activity_sorting_options_labels( $context );
	}
}
if ( function_exists( 'bboss_get_activity_timeline_filter_options_labels' ) && ! function_exists( 'bb_get_activity_timeline_filter_options_labels' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_get_activity_timeline_filter_options_labels()}.
	 *
	 * @deprecated Use bboss_get_activity_timeline_filter_options_labels() instead.
	 */
	function bb_get_activity_timeline_filter_options_labels( $context = 'default' ) {
		return bboss_get_activity_timeline_filter_options_labels( $context );
	}
}
if ( function_exists( 'bboss_get_ajax_request_page_load' ) && ! function_exists( 'bb_get_ajax_request_page_load' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_get_ajax_request_page_load()}.
	 *
	 * @deprecated Use bboss_get_ajax_request_page_load() instead.
	 */
	function bb_get_ajax_request_page_load( $default = 2 ) {
		return bboss_get_ajax_request_page_load( $default );
	}
}
if ( function_exists( 'bboss_get_all_reactions' ) && ! function_exists( 'bb_get_all_reactions' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_get_all_reactions()}.
	 *
	 * @deprecated Use bboss_get_all_reactions() instead.
	 */
	function bb_get_all_reactions() {
		return bboss_get_all_reactions();
	}
}
if ( function_exists( 'bboss_get_default_custom_upload_group_avatar' ) && ! function_exists( 'bb_get_default_custom_upload_group_avatar' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_get_default_custom_upload_group_avatar()}.
	 *
	 * @deprecated Use bboss_get_default_custom_upload_group_avatar() instead.
	 */
	function bb_get_default_custom_upload_group_avatar( $default = '', $size = 'full' ) {
		return bboss_get_default_custom_upload_group_avatar( $default, $size );
	}
}
if ( function_exists( 'bboss_get_default_custom_upload_group_cover' ) && ! function_exists( 'bb_get_default_custom_upload_group_cover' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_get_default_custom_upload_group_cover()}.
	 *
	 * @deprecated Use bboss_get_default_custom_upload_group_cover() instead.
	 */
	function bb_get_default_custom_upload_group_cover() {
		return bboss_get_default_custom_upload_group_cover();
	}
}
if ( function_exists( 'bboss_get_default_custom_upload_profile_avatar' ) && ! function_exists( 'bb_get_default_custom_upload_profile_avatar' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_get_default_custom_upload_profile_avatar()}.
	 *
	 * @deprecated Use bboss_get_default_custom_upload_profile_avatar() instead.
	 */
	function bb_get_default_custom_upload_profile_avatar( $default = '', $size = 'full' ) {
		return bboss_get_default_custom_upload_profile_avatar( $default, $size );
	}
}
if ( function_exists( 'bboss_get_default_custom_upload_profile_cover' ) && ! function_exists( 'bb_get_default_custom_upload_profile_cover' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_get_default_custom_upload_profile_cover()}.
	 *
	 * @deprecated Use bboss_get_default_custom_upload_profile_cover() instead.
	 */
	function bb_get_default_custom_upload_profile_cover() {
		return bboss_get_default_custom_upload_profile_cover();
	}
}
if ( function_exists( 'bboss_get_default_group_avatar_type' ) && ! function_exists( 'bb_get_default_group_avatar_type' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_get_default_group_avatar_type()}.
	 *
	 * @deprecated Use bboss_get_default_group_avatar_type() instead.
	 */
	function bb_get_default_group_avatar_type( $default = 'buddyboss' ) {
		return bboss_get_default_group_avatar_type( $default );
	}
}
if ( function_exists( 'bboss_get_default_group_cover_type' ) && ! function_exists( 'bb_get_default_group_cover_type' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_get_default_group_cover_type()}.
	 *
	 * @deprecated Use bboss_get_default_group_cover_type() instead.
	 */
	function bb_get_default_group_cover_type( $default = 'buddyboss' ) {
		return bboss_get_default_group_cover_type( $default );
	}
}
if ( function_exists( 'bboss_get_default_profile_avatar_type' ) && ! function_exists( 'bb_get_default_profile_avatar_type' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_get_default_profile_avatar_type()}.
	 *
	 * @deprecated Use bboss_get_default_profile_avatar_type() instead.
	 */
	function bb_get_default_profile_avatar_type( $default = 'buddyboss' ) {
		return bboss_get_default_profile_avatar_type( $default );
	}
}
if ( function_exists( 'bboss_get_default_profile_cover_type' ) && ! function_exists( 'bb_get_default_profile_cover_type' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_get_default_profile_cover_type()}.
	 *
	 * @deprecated Use bboss_get_default_profile_cover_type() instead.
	 */
	function bb_get_default_profile_cover_type( $default = 'buddyboss' ) {
		return bboss_get_default_profile_cover_type( $default );
	}
}
if ( function_exists( 'bboss_get_enabled_activity_filter_options' ) && ! function_exists( 'bb_get_enabled_activity_filter_options' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_get_enabled_activity_filter_options()}.
	 *
	 * @deprecated Use bboss_get_enabled_activity_filter_options() instead.
	 */
	function bb_get_enabled_activity_filter_options( $args = array() ) {
		return bboss_get_enabled_activity_filter_options( $args );
	}
}
if ( function_exists( 'bboss_get_enabled_activity_sorting_options' ) && ! function_exists( 'bb_get_enabled_activity_sorting_options' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_get_enabled_activity_sorting_options()}.
	 *
	 * @deprecated Use bboss_get_enabled_activity_sorting_options() instead.
	 */
	function bb_get_enabled_activity_sorting_options( $args = array() ) {
		return bboss_get_enabled_activity_sorting_options( $args );
	}
}
if ( function_exists( 'bboss_get_enabled_activity_timeline_filter_options' ) && ! function_exists( 'bb_get_enabled_activity_timeline_filter_options' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_get_enabled_activity_timeline_filter_options()}.
	 *
	 * @deprecated Use bboss_get_enabled_activity_timeline_filter_options() instead.
	 */
	function bb_get_enabled_activity_timeline_filter_options( $args = array() ) {
		return bboss_get_enabled_activity_timeline_filter_options( $args );
	}
}
if ( function_exists( 'bboss_get_load_activity_per_request' ) && ! function_exists( 'bb_get_load_activity_per_request' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_get_load_activity_per_request()}.
	 *
	 * @deprecated Use bboss_get_load_activity_per_request() instead.
	 */
	function bb_get_load_activity_per_request( $default = 10 ) {
		return bboss_get_load_activity_per_request( $default );
	}
}
if ( function_exists( 'bboss_get_profile_avatar_type' ) && ! function_exists( 'bb_get_profile_avatar_type' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_get_profile_avatar_type()}.
	 *
	 * @deprecated Use bboss_get_profile_avatar_type() instead.
	 */
	function bb_get_profile_avatar_type( $default = 'BuddyBoss' ) {
		return bboss_get_profile_avatar_type( $default );
	}
}
if ( function_exists( 'bboss_get_profile_slug_format' ) && ! function_exists( 'bb_get_profile_slug_format' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_get_profile_slug_format()}.
	 *
	 * @deprecated Use bboss_get_profile_slug_format() instead.
	 */
	function bb_get_profile_slug_format( $default = 'username' ) {
		return bboss_get_profile_slug_format( $default );
	}
}
if ( function_exists( 'bboss_get_reaction_mode' ) && ! function_exists( 'bb_get_reaction_mode' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_get_reaction_mode()}.
	 *
	 * @deprecated Use bboss_get_reaction_mode() instead.
	 */
	function bb_get_reaction_mode( $default = 'likes' ) {
		return bboss_get_reaction_mode( $default );
	}
}
if ( function_exists( 'bboss_is_active_activity_pinned_posts' ) && ! function_exists( 'bb_is_active_activity_pinned_posts' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_is_active_activity_pinned_posts()}.
	 *
	 * @deprecated Use bboss_is_active_activity_pinned_posts() instead.
	 */
	function bb_is_active_activity_pinned_posts( $default = false ) {
		return bboss_is_active_activity_pinned_posts( $default );
	}
}
if ( function_exists( 'bboss_is_activity_comment_edit_enabled' ) && ! function_exists( 'bb_is_activity_comment_edit_enabled' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_is_activity_comment_edit_enabled()}.
	 *
	 * @deprecated Use bboss_is_activity_comment_edit_enabled() instead.
	 */
	function bb_is_activity_comment_edit_enabled( $default = false ) {
		return bboss_is_activity_comment_edit_enabled( $default );
	}
}
if ( function_exists( 'bboss_is_activity_search_enabled' ) && ! function_exists( 'bb_is_activity_search_enabled' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_is_activity_search_enabled()}.
	 *
	 * @deprecated Use bboss_is_activity_search_enabled() instead.
	 */
	function bb_is_activity_search_enabled( $default = true ) {
		return bboss_is_activity_search_enabled( $default );
	}
}
if ( function_exists( 'bboss_is_friends_auto_follow_active' ) && ! function_exists( 'bb_is_friends_auto_follow_active' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_is_friends_auto_follow_active()}.
	 *
	 * @deprecated Use bboss_is_friends_auto_follow_active() instead.
	 */
	function bb_is_friends_auto_follow_active( $default = false ) {
		return bboss_is_friends_auto_follow_active( $default );
	}
}
if ( function_exists( 'bboss_is_post_type_feed_comment_enable' ) && ! function_exists( 'bb_is_post_type_feed_comment_enable' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_is_post_type_feed_comment_enable()}.
	 *
	 * @deprecated Use bboss_is_post_type_feed_comment_enable() instead.
	 */
	function bb_is_post_type_feed_comment_enable( $post_type, $default = false ) {
		return bboss_is_post_type_feed_comment_enable( $post_type, $default );
	}
}
if ( function_exists( 'bboss_is_reaction_activity_comments_enabled' ) && ! function_exists( 'bb_is_reaction_activity_comments_enabled' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_is_reaction_activity_comments_enabled()}.
	 *
	 * @deprecated Use bboss_is_reaction_activity_comments_enabled() instead.
	 */
	function bb_is_reaction_activity_comments_enabled( $default = true ) {
		return bboss_is_reaction_activity_comments_enabled( $default );
	}
}
if ( function_exists( 'bboss_is_reaction_activity_posts_enabled' ) && ! function_exists( 'bb_is_reaction_activity_posts_enabled' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_is_reaction_activity_posts_enabled()}.
	 *
	 * @deprecated Use bboss_is_reaction_activity_posts_enabled() instead.
	 */
	function bb_is_reaction_activity_posts_enabled( $default = true ) {
		return bboss_is_reaction_activity_posts_enabled( $default );
	}
}
if ( function_exists( 'bboss_is_reaction_emotions_enabled' ) && ! function_exists( 'bb_is_reaction_emotions_enabled' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_is_reaction_emotions_enabled()}.
	 *
	 * @deprecated Use bboss_is_reaction_emotions_enabled() instead.
	 */
	function bb_is_reaction_emotions_enabled() {
		return bboss_is_reaction_emotions_enabled();
	}
}
if ( function_exists( 'bboss_is_reactions_feature_enabled' ) && ! function_exists( 'bb_is_reactions_feature_enabled' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_is_reactions_feature_enabled()}.
	 *
	 * @deprecated Use bboss_is_reactions_feature_enabled() instead.
	 */
	function bb_is_reactions_feature_enabled( $reset = false ) {
		return bboss_is_reactions_feature_enabled( $reset );
	}
}
if ( function_exists( 'bboss_is_send_ajax_request' ) && ! function_exists( 'bb_is_send_ajax_request' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_is_send_ajax_request()}.
	 *
	 * @deprecated Use bboss_is_send_ajax_request() instead.
	 */
	function bb_is_send_ajax_request() {
		return bboss_is_send_ajax_request();
	}
}
if ( function_exists( 'bboss_login_redirection' ) && ! function_exists( 'bb_login_redirection' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_login_redirection()}.
	 *
	 * @deprecated Use bboss_login_redirection() instead.
	 */
	function bb_login_redirection( $default = '' ) {
		return bboss_login_redirection( $default );
	}
}
if ( function_exists( 'bboss_logout_redirection' ) && ! function_exists( 'bb_logout_redirection' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_logout_redirection()}.
	 *
	 * @deprecated Use bboss_logout_redirection() instead.
	 */
	function bb_logout_redirection( $default = '' ) {
		return bboss_logout_redirection( $default );
	}
}
if ( function_exists( 'bboss_post_type_feed_comment_option_name' ) && ! function_exists( 'bb_post_type_feed_comment_option_name' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_post_type_feed_comment_option_name()}.
	 *
	 * @deprecated Use bboss_post_type_feed_comment_option_name() instead.
	 */
	function bb_post_type_feed_comment_option_name( $post_type ) {
		return bboss_post_type_feed_comment_option_name( $post_type );
	}
}
if ( function_exists( 'bboss_post_type_feed_option_name' ) && ! function_exists( 'bb_post_type_feed_option_name' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_post_type_feed_option_name()}.
	 *
	 * @deprecated Use bboss_post_type_feed_option_name() instead.
	 */
	function bb_post_type_feed_option_name( $post_type ) {
		return bboss_post_type_feed_option_name( $post_type );
	}
}
if ( function_exists( 'bboss_reaction_button_options' ) && ! function_exists( 'bb_reaction_button_options' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_reaction_button_options()}.
	 *
	 * @deprecated Use bboss_reaction_button_options() instead.
	 */
	function bb_reaction_button_options( $key = '' ) {
		return bboss_reaction_button_options( $key );
	}
}
if ( function_exists( 'bboss_register_legal_agreement' ) && ! function_exists( 'bb_register_legal_agreement' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_register_legal_agreement()}.
	 *
	 * @deprecated Use bboss_register_legal_agreement() instead.
	 */
	function bb_register_legal_agreement( $default = false ) {
		return bboss_register_legal_agreement( $default );
	}
}
if ( function_exists( 'bboss_reset_reactions_feature_cache' ) && ! function_exists( 'bb_reset_reactions_feature_cache' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_reset_reactions_feature_cache()}.
	 *
	 * @deprecated Use bboss_reset_reactions_feature_cache() instead.
	 */
	function bb_reset_reactions_feature_cache( $feature_id ) {
		return bboss_reset_reactions_feature_cache( $feature_id );
	}
}
if ( function_exists( 'bboss_activity_edit_times' ) && ! function_exists( 'bp_activity_edit_times' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_activity_edit_times()}.
	 *
	 * @deprecated Use bboss_activity_edit_times() instead.
	 */
	function bp_activity_edit_times( $time = null ) {
		return bboss_activity_edit_times( $time );
	}
}
if ( function_exists( 'bboss_add_option' ) && ! function_exists( 'bp_add_option' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_add_option()}.
	 *
	 * @deprecated Use bboss_add_option() instead.
	 */
	function bp_add_option( $option_name, $value ) {
		return bboss_add_option( $option_name, $value );
	}
}
if ( function_exists( 'bboss_add_options' ) && ! function_exists( 'bp_add_options' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_add_options()}.
	 *
	 * @deprecated Use bboss_add_options() instead.
	 */
	function bp_add_options() {
		return bboss_add_options();
	}
}
if ( function_exists( 'bboss_allow_custom_registration' ) && ! function_exists( 'bp_allow_custom_registration' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_allow_custom_registration()}.
	 *
	 * @deprecated Use bboss_allow_custom_registration() instead.
	 */
	function bp_allow_custom_registration( $default = false ) {
		return bboss_allow_custom_registration( $default );
	}
}
if ( function_exists( 'bboss_core_activate_site_options' ) && ! function_exists( 'bp_core_activate_site_options' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_core_activate_site_options()}.
	 *
	 * @deprecated Use bboss_core_activate_site_options() instead.
	 */
	function bp_core_activate_site_options( $keys = array() ) {
		return bboss_core_activate_site_options( $keys );
	}
}
if ( function_exists( 'bboss_core_display_name_format' ) && ! function_exists( 'bp_core_display_name_format' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_core_display_name_format()}.
	 *
	 * @deprecated Use bboss_core_display_name_format() instead.
	 */
	function bp_core_display_name_format( $default = 'first_name' ) {
		return bboss_core_display_name_format( $default );
	}
}
if ( function_exists( 'bboss_core_get_root_option' ) && ! function_exists( 'bp_core_get_root_option' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_core_get_root_option()}.
	 *
	 * @deprecated Use bboss_core_get_root_option() instead.
	 */
	function bp_core_get_root_option( $option ) {
		return bboss_core_get_root_option( $option );
	}
}
if ( function_exists( 'bboss_core_get_root_options' ) && ! function_exists( 'bp_core_get_root_options' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_core_get_root_options()}.
	 *
	 * @deprecated Use bboss_core_get_root_options() instead.
	 */
	function bp_core_get_root_options() {
		return bboss_core_get_root_options();
	}
}
if ( function_exists( 'bboss_custom_register_page_url' ) && ! function_exists( 'bp_custom_register_page_url' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_custom_register_page_url()}.
	 *
	 * @deprecated Use bboss_custom_register_page_url() instead.
	 */
	function bp_custom_register_page_url( $default = '' ) {
		return bboss_custom_register_page_url( $default );
	}
}
if ( function_exists( 'bboss_delete_option' ) && ! function_exists( 'bp_delete_option' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_delete_option()}.
	 *
	 * @deprecated Use bboss_delete_option() instead.
	 */
	function bp_delete_option( $option_name ) {
		return bboss_delete_option( $option_name );
	}
}
if ( function_exists( 'bboss_delete_options' ) && ! function_exists( 'bp_delete_options' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_delete_options()}.
	 *
	 * @deprecated Use bboss_delete_options() instead.
	 */
	function bp_delete_options() {
		return bboss_delete_options();
	}
}
if ( function_exists( 'bboss_disable_account_deletion' ) && ! function_exists( 'bp_disable_account_deletion' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_disable_account_deletion()}.
	 *
	 * @deprecated Use bboss_disable_account_deletion() instead.
	 */
	function bp_disable_account_deletion( $default = false ) {
		return bboss_disable_account_deletion( $default );
	}
}
if ( function_exists( 'bboss_disable_advanced_profile_search' ) && ! function_exists( 'bp_disable_advanced_profile_search' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_disable_advanced_profile_search()}.
	 *
	 * @deprecated Use bboss_disable_advanced_profile_search() instead.
	 */
	function bp_disable_advanced_profile_search( $default = false ) {
		return bboss_disable_advanced_profile_search( $default );
	}
}
if ( function_exists( 'bboss_disable_avatar_uploads' ) && ! function_exists( 'bp_disable_avatar_uploads' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_disable_avatar_uploads()}.
	 *
	 * @deprecated Use bboss_disable_avatar_uploads() instead.
	 */
	function bp_disable_avatar_uploads( $default = true ) {
		return bboss_disable_avatar_uploads( $default );
	}
}
if ( function_exists( 'bboss_disable_blogforum_comments' ) && ! function_exists( 'bp_disable_blogforum_comments' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_disable_blogforum_comments()}.
	 *
	 * @deprecated Use bboss_disable_blogforum_comments() instead.
	 */
	function bp_disable_blogforum_comments( $default = false ) {
		return bboss_disable_blogforum_comments( $default );
	}
}
if ( function_exists( 'bboss_disable_cover_image_uploads' ) && ! function_exists( 'bp_disable_cover_image_uploads' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_disable_cover_image_uploads()}.
	 *
	 * @deprecated Use bboss_disable_cover_image_uploads() instead.
	 */
	function bp_disable_cover_image_uploads( $default = false ) {
		return bboss_disable_cover_image_uploads( $default );
	}
}
if ( function_exists( 'bboss_disable_group_avatar_uploads' ) && ! function_exists( 'bp_disable_group_avatar_uploads' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_disable_group_avatar_uploads()}.
	 *
	 * @deprecated Use bboss_disable_group_avatar_uploads() instead.
	 */
	function bp_disable_group_avatar_uploads( $default = null ) {
		return bboss_disable_group_avatar_uploads( $default );
	}
}
if ( function_exists( 'bboss_disable_group_cover_image_uploads' ) && ! function_exists( 'bp_disable_group_cover_image_uploads' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_disable_group_cover_image_uploads()}.
	 *
	 * @deprecated Use bboss_disable_group_cover_image_uploads() instead.
	 */
	function bp_disable_group_cover_image_uploads( $default = false ) {
		return bboss_disable_group_cover_image_uploads( $default );
	}
}
if ( function_exists( 'bboss_disable_group_messages' ) && ! function_exists( 'bp_disable_group_messages' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_disable_group_messages()}.
	 *
	 * @deprecated Use bboss_disable_group_messages() instead.
	 */
	function bp_disable_group_messages( $default = false ) {
		return bboss_disable_group_messages( $default );
	}
}
if ( function_exists( 'bboss_disable_group_type_creation' ) && ! function_exists( 'bp_disable_group_type_creation' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_disable_group_type_creation()}.
	 *
	 * @deprecated Use bboss_disable_group_type_creation() instead.
	 */
	function bp_disable_group_type_creation( $default = false ) {
		return bboss_disable_group_type_creation( $default );
	}
}
if ( function_exists( 'bboss_disable_invite_member_email_content' ) && ! function_exists( 'bp_disable_invite_member_email_content' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_disable_invite_member_email_content()}.
	 *
	 * @deprecated Use bboss_disable_invite_member_email_content() instead.
	 */
	function bp_disable_invite_member_email_content( $default = true ) {
		return bboss_disable_invite_member_email_content( $default );
	}
}
if ( function_exists( 'bboss_disable_invite_member_email_subject' ) && ! function_exists( 'bp_disable_invite_member_email_subject' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_disable_invite_member_email_subject()}.
	 *
	 * @deprecated Use bboss_disable_invite_member_email_subject() instead.
	 */
	function bp_disable_invite_member_email_subject( $default = false ) {
		return bboss_disable_invite_member_email_subject( $default );
	}
}
if ( function_exists( 'bboss_disable_invite_member_type' ) && ! function_exists( 'bp_disable_invite_member_type' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_disable_invite_member_type()}.
	 *
	 * @deprecated Use bboss_disable_invite_member_type() instead.
	 */
	function bp_disable_invite_member_type( $default = false ) {
		return bboss_disable_invite_member_type( $default );
	}
}
if ( function_exists( 'bboss_disable_profile_sync' ) && ! function_exists( 'bp_disable_profile_sync' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_disable_profile_sync()}.
	 *
	 * @deprecated Use bboss_disable_profile_sync() instead.
	 */
	function bp_disable_profile_sync( $default = false ) {
		return bboss_disable_profile_sync( $default );
	}
}
if ( function_exists( 'bboss_enable_group_auto_join' ) && ! function_exists( 'bp_enable_group_auto_join' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_enable_group_auto_join()}.
	 *
	 * @deprecated Use bboss_enable_group_auto_join() instead.
	 */
	function bp_enable_group_auto_join( $default = false ) {
		return bboss_enable_group_auto_join( $default );
	}
}
if ( function_exists( 'bboss_enable_group_hide_subgroups' ) && ! function_exists( 'bp_enable_group_hide_subgroups' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_enable_group_hide_subgroups()}.
	 *
	 * @deprecated Use bboss_enable_group_hide_subgroups() instead.
	 */
	function bp_enable_group_hide_subgroups( $default = false ) {
		return bboss_enable_group_hide_subgroups( $default );
	}
}
if ( function_exists( 'bboss_enable_group_hierarchies' ) && ! function_exists( 'bp_enable_group_hierarchies' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_enable_group_hierarchies()}.
	 *
	 * @deprecated Use bboss_enable_group_hierarchies() instead.
	 */
	function bp_enable_group_hierarchies( $default = false ) {
		return bboss_enable_group_hierarchies( $default );
	}
}
if ( function_exists( 'bboss_enable_group_restrict_invites' ) && ! function_exists( 'bp_enable_group_restrict_invites' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_enable_group_restrict_invites()}.
	 *
	 * @deprecated Use bboss_enable_group_restrict_invites() instead.
	 */
	function bp_enable_group_restrict_invites( $default = false ) {
		return bboss_enable_group_restrict_invites( $default );
	}
}
if ( function_exists( 'bboss_enable_private_network' ) && ! function_exists( 'bp_enable_private_network' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_enable_private_network()}.
	 *
	 * @deprecated Use bboss_enable_private_network() instead.
	 */
	function bp_enable_private_network( $default = false ) {
		return bboss_enable_private_network( $default );
	}
}
if ( function_exists( 'bboss_enable_private_network_public_content' ) && ! function_exists( 'bp_enable_private_network_public_content' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_enable_private_network_public_content()}.
	 *
	 * @deprecated Use bboss_enable_private_network_public_content() instead.
	 */
	function bp_enable_private_network_public_content( $default = '' ) {
		return bboss_enable_private_network_public_content( $default );
	}
}
if ( function_exists( 'bboss_enable_private_rest_apis' ) && ! function_exists( 'bp_enable_private_rest_apis' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_enable_private_rest_apis()}.
	 *
	 * @deprecated Use bboss_enable_private_rest_apis() instead.
	 */
	function bp_enable_private_rest_apis( $default = false ) {
		return bboss_enable_private_rest_apis( $default );
	}
}
if ( function_exists( 'bboss_enable_private_rss_feeds' ) && ! function_exists( 'bp_enable_private_rss_feeds' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_enable_private_rss_feeds()}.
	 *
	 * @deprecated Use bboss_enable_private_rss_feeds() instead.
	 */
	function bp_enable_private_rss_feeds( $default = false ) {
		return bboss_enable_private_rss_feeds( $default );
	}
}
if ( function_exists( 'bboss_enable_profile_gravatar' ) && ! function_exists( 'bp_enable_profile_gravatar' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_enable_profile_gravatar()}.
	 *
	 * @deprecated Use bboss_enable_profile_gravatar() instead.
	 */
	function bp_enable_profile_gravatar( $default = false ) {
		return bboss_enable_profile_gravatar( $default );
	}
}
if ( function_exists( 'bboss_enable_send_invite_member_type' ) && ! function_exists( 'bp_enable_send_invite_member_type' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_enable_send_invite_member_type()}.
	 *
	 * @deprecated Use bboss_enable_send_invite_member_type() instead.
	 */
	function bp_enable_send_invite_member_type( $member_type, $default = false ) {
		return bboss_enable_send_invite_member_type( $member_type, $default );
	}
}
if ( function_exists( 'bboss_enable_site_registration' ) && ! function_exists( 'bp_enable_site_registration' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_enable_site_registration()}.
	 *
	 * @deprecated Use bboss_enable_site_registration() instead.
	 */
	function bp_enable_site_registration( $default = false ) {
		return bboss_enable_site_registration( $default );
	}
}
if ( function_exists( 'bboss_force_buddybar' ) && ! function_exists( 'bp_force_buddybar' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_force_buddybar()}.
	 *
	 * @deprecated Use bboss_force_buddybar() instead.
	 */
	function bp_force_buddybar( $default = true ) {
		return bboss_force_buddybar( $default );
	}
}
if ( function_exists( 'bboss_force_friendship_to_message' ) && ! function_exists( 'bp_force_friendship_to_message' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_force_friendship_to_message()}.
	 *
	 * @deprecated Use bboss_force_friendship_to_message() instead.
	 */
	function bp_force_friendship_to_message( $default = false ) {
		return bboss_force_friendship_to_message( $default );
	}
}
if ( function_exists( 'bboss_get_activity_edit_time' ) && ! function_exists( 'bp_get_activity_edit_time' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_get_activity_edit_time()}.
	 *
	 * @deprecated Use bboss_get_activity_edit_time() instead.
	 */
	function bp_get_activity_edit_time( $default = false ) {
		return bboss_get_activity_edit_time( $default );
	}
}
if ( function_exists( 'bboss_get_default_options' ) && ! function_exists( 'bp_get_default_options' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_get_default_options()}.
	 *
	 * @deprecated Use bboss_get_default_options() instead.
	 */
	function bp_get_default_options() {
		return bboss_get_default_options();
	}
}
if ( function_exists( 'bboss_get_option' ) && ! function_exists( 'bp_get_option' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_get_option()}.
	 *
	 * @deprecated Use bboss_get_option() instead.
	 */
	function bp_get_option( $option_name, $default = '' ) {
		return bboss_get_option( $option_name, $default );
	}
}
if ( function_exists( 'bboss_get_theme_package_id' ) && ! function_exists( 'bp_get_theme_package_id' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_get_theme_package_id()}.
	 *
	 * @deprecated Use bboss_get_theme_package_id() instead.
	 */
	function bp_get_theme_package_id( $default = 'nouveau' ) {
		return bboss_get_theme_package_id( $default );
	}
}
if ( function_exists( 'bboss_group_layout_default_format' ) && ! function_exists( 'bp_group_layout_default_format' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_group_layout_default_format()}.
	 *
	 * @deprecated Use bboss_group_layout_default_format() instead.
	 */
	function bp_group_layout_default_format( $default = 'grid' ) {
		return bboss_group_layout_default_format( $default );
	}
}
if ( function_exists( 'bboss_hide_last_name' ) && ! function_exists( 'bp_hide_last_name' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_hide_last_name()}.
	 *
	 * @deprecated Use bboss_hide_last_name() instead.
	 */
	function bp_hide_last_name( $default = true ) {
		return bboss_hide_last_name( $default );
	}
}
if ( function_exists( 'bboss_hide_loggedout_adminbar' ) && ! function_exists( 'bp_hide_loggedout_adminbar' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_hide_loggedout_adminbar()}.
	 *
	 * @deprecated Use bboss_hide_loggedout_adminbar() instead.
	 */
	function bp_hide_loggedout_adminbar( $default = true ) {
		return bboss_hide_loggedout_adminbar( $default );
	}
}
if ( function_exists( 'bboss_hide_nickname_first_name' ) && ! function_exists( 'bp_hide_nickname_first_name' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_hide_nickname_first_name()}.
	 *
	 * @deprecated Use bboss_hide_nickname_first_name() instead.
	 */
	function bp_hide_nickname_first_name( $default = true ) {
		return bboss_hide_nickname_first_name( $default );
	}
}
if ( function_exists( 'bboss_hide_nickname_last_name' ) && ! function_exists( 'bp_hide_nickname_last_name' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_hide_nickname_last_name()}.
	 *
	 * @deprecated Use bboss_hide_nickname_last_name() instead.
	 */
	function bp_hide_nickname_last_name( $default = true ) {
		return bboss_hide_nickname_last_name( $default );
	}
}
if ( function_exists( 'bboss_is_activity_autoload_active' ) && ! function_exists( 'bp_is_activity_autoload_active' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_is_activity_autoload_active()}.
	 *
	 * @deprecated Use bboss_is_activity_autoload_active() instead.
	 */
	function bp_is_activity_autoload_active( $default = true ) {
		return bboss_is_activity_autoload_active( $default );
	}
}
if ( function_exists( 'bboss_is_activity_edit_enabled' ) && ! function_exists( 'bp_is_activity_edit_enabled' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_is_activity_edit_enabled()}.
	 *
	 * @deprecated Use bboss_is_activity_edit_enabled() instead.
	 */
	function bp_is_activity_edit_enabled( $default = false ) {
		return bboss_is_activity_edit_enabled( $default );
	}
}
if ( function_exists( 'bboss_is_activity_follow_active' ) && ! function_exists( 'bp_is_activity_follow_active' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_is_activity_follow_active()}.
	 *
	 * @deprecated Use bboss_is_activity_follow_active() instead.
	 */
	function bp_is_activity_follow_active( $default = false ) {
		return bboss_is_activity_follow_active( $default );
	}
}
if ( function_exists( 'bboss_is_activity_heartbeat_active' ) && ! function_exists( 'bp_is_activity_heartbeat_active' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_is_activity_heartbeat_active()}.
	 *
	 * @deprecated Use bboss_is_activity_heartbeat_active() instead.
	 */
	function bp_is_activity_heartbeat_active( $default = true ) {
		return bboss_is_activity_heartbeat_active( $default );
	}
}
if ( function_exists( 'bboss_is_activity_like_active' ) && ! function_exists( 'bp_is_activity_like_active' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_is_activity_like_active()}.
	 *
	 * @deprecated Use bboss_is_activity_like_active() instead.
	 */
	function bp_is_activity_like_active( $default = true ) {
		return bboss_is_activity_like_active( $default );
	}
}
if ( function_exists( 'bboss_is_activity_link_preview_active' ) && ! function_exists( 'bp_is_activity_link_preview_active' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_is_activity_link_preview_active()}.
	 *
	 * @deprecated Use bboss_is_activity_link_preview_active() instead.
	 */
	function bp_is_activity_link_preview_active( $default = false ) {
		return bboss_is_activity_link_preview_active( $default );
	}
}
if ( function_exists( 'bboss_is_activity_tabs_active' ) && ! function_exists( 'bp_is_activity_tabs_active' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_is_activity_tabs_active()}.
	 *
	 * @deprecated Use bboss_is_activity_tabs_active() instead.
	 */
	function bp_is_activity_tabs_active( $default = false ) {
		return bboss_is_activity_tabs_active( $default );
	}
}
if ( function_exists( 'bboss_is_akismet_active' ) && ! function_exists( 'bp_is_akismet_active' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_is_akismet_active()}.
	 *
	 * @deprecated Use bboss_is_akismet_active() instead.
	 */
	function bp_is_akismet_active( $default = true ) {
		return bboss_is_akismet_active( $default );
	}
}
if ( function_exists( 'bboss_is_custom_post_type_feed_enable' ) && ! function_exists( 'bp_is_custom_post_type_feed_enable' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_is_custom_post_type_feed_enable()}.
	 *
	 * @deprecated Use bboss_is_custom_post_type_feed_enable() instead.
	 */
	function bp_is_custom_post_type_feed_enable( $default = false ) {
		return bboss_is_custom_post_type_feed_enable( $default );
	}
}
if ( function_exists( 'bboss_is_post_type_feed_enable' ) && ! function_exists( 'bp_is_post_type_feed_enable' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_is_post_type_feed_enable()}.
	 *
	 * @deprecated Use bboss_is_post_type_feed_enable() instead.
	 */
	function bp_is_post_type_feed_enable( $post_type, $default = false ) {
		return bboss_is_post_type_feed_enable( $post_type, $default );
	}
}
if ( function_exists( 'bboss_is_relevant_feed_enabled' ) && ! function_exists( 'bp_is_relevant_feed_enabled' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_is_relevant_feed_enabled()}.
	 *
	 * @deprecated Use bboss_is_relevant_feed_enabled() instead.
	 */
	function bp_is_relevant_feed_enabled( $default = false ) {
		return bboss_is_relevant_feed_enabled( $default );
	}
}
if ( function_exists( 'bboss_member_type_default_on_registration' ) && ! function_exists( 'bp_member_type_default_on_registration' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_member_type_default_on_registration()}.
	 *
	 * @deprecated Use bboss_member_type_default_on_registration() instead.
	 */
	function bp_member_type_default_on_registration( $default = '' ) {
		return bboss_member_type_default_on_registration( $default );
	}
}
if ( function_exists( 'bboss_member_type_display_on_profile' ) && ! function_exists( 'bp_member_type_display_on_profile' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_member_type_display_on_profile()}.
	 *
	 * @deprecated Use bboss_member_type_display_on_profile() instead.
	 */
	function bp_member_type_display_on_profile( $default = false ) {
		return bboss_member_type_display_on_profile( $default );
	}
}
if ( function_exists( 'bboss_member_type_enable_disable' ) && ! function_exists( 'bp_member_type_enable_disable' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_member_type_enable_disable()}.
	 *
	 * @deprecated Use bboss_member_type_enable_disable() instead.
	 */
	function bp_member_type_enable_disable( $default = false ) {
		return bboss_member_type_enable_disable( $default );
	}
}
if ( function_exists( 'bboss_platform_is_feed_enable' ) && ! function_exists( 'bp_platform_is_feed_enable' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_platform_is_feed_enable()}.
	 *
	 * @deprecated Use bboss_platform_is_feed_enable() instead.
	 */
	function bp_platform_is_feed_enable( $activity_type, $default = true ) {
		return bboss_platform_is_feed_enable( $activity_type, $default );
	}
}
if ( function_exists( 'bboss_pre_get_option' ) && ! function_exists( 'bp_pre_get_option' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_pre_get_option()}.
	 *
	 * @deprecated Use bboss_pre_get_option() instead.
	 */
	function bp_pre_get_option( $value = false ) {
		return bboss_pre_get_option( $value );
	}
}
if ( function_exists( 'bboss_profile_layout_default_format' ) && ! function_exists( 'bp_profile_layout_default_format' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_profile_layout_default_format()}.
	 *
	 * @deprecated Use bboss_profile_layout_default_format() instead.
	 */
	function bp_profile_layout_default_format( $default = 'grid' ) {
		return bboss_profile_layout_default_format( $default );
	}
}
if ( function_exists( 'bboss_register_confirm_email' ) && ! function_exists( 'bp_register_confirm_email' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_register_confirm_email()}.
	 *
	 * @deprecated Use bboss_register_confirm_email() instead.
	 */
	function bp_register_confirm_email( $default = false ) {
		return bboss_register_confirm_email( $default );
	}
}
if ( function_exists( 'bboss_register_confirm_password' ) && ! function_exists( 'bp_register_confirm_password' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_register_confirm_password()}.
	 *
	 * @deprecated Use bboss_register_confirm_password() instead.
	 */
	function bp_register_confirm_password( $default = false ) {
		return bboss_register_confirm_password( $default );
	}
}
if ( function_exists( 'bboss_rest_enable_private_network' ) && ! function_exists( 'bp_rest_enable_private_network' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_rest_enable_private_network()}.
	 *
	 * @deprecated Use bboss_rest_enable_private_network() instead.
	 */
	function bp_rest_enable_private_network() {
		return bboss_rest_enable_private_network();
	}
}
if ( function_exists( 'bboss_restrict_group_creation' ) && ! function_exists( 'bp_restrict_group_creation' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_restrict_group_creation()}.
	 *
	 * @deprecated Use bboss_restrict_group_creation() instead.
	 */
	function bp_restrict_group_creation( $default = true ) {
		return bboss_restrict_group_creation( $default );
	}
}
if ( function_exists( 'bboss_setup_option_filters' ) && ! function_exists( 'bp_setup_option_filters' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_setup_option_filters()}.
	 *
	 * @deprecated Use bboss_setup_option_filters() instead.
	 */
	function bp_setup_option_filters() {
		return bboss_setup_option_filters();
	}
}
if ( function_exists( 'bboss_show_admin_adminbar' ) && ! function_exists( 'bp_show_admin_adminbar' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_show_admin_adminbar()}.
	 *
	 * @deprecated Use bboss_show_admin_adminbar() instead.
	 */
	function bp_show_admin_adminbar( $default = true ) {
		return bboss_show_admin_adminbar( $default );
	}
}
if ( function_exists( 'bboss_show_login_adminbar' ) && ! function_exists( 'bp_show_login_adminbar' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_show_login_adminbar()}.
	 *
	 * @deprecated Use bboss_show_login_adminbar() instead.
	 */
	function bp_show_login_adminbar( $default = true ) {
		return bboss_show_login_adminbar( $default );
	}
}
if ( function_exists( 'bboss_update_option' ) && ! function_exists( 'bp_update_option' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_update_option()}.
	 *
	 * @deprecated Use bboss_update_option() instead.
	 */
	function bp_update_option( $option_name, $value ) {
		return bboss_update_option( $option_name, $value );
	}
}
// @bbwt-com-end compat-layer
