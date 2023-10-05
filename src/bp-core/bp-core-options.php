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
 * these causing load order problems with `bp_core_clear_root_options_cache()`; see #BP7227.
 *
 * @since BuddyPress 1.6.0
 *
 * @return array Filtered option names and values.
 */
function bp_get_default_options() {

	// Default options.
	$options = array(

		/* Components ********************************************************/

		'bp-deactivated-components'                  => array(),

		/* XProfile **********************************************************/

		// Default profile groups name.
		'bp-xprofile-base-group-name'                => __( 'Details', 'buddyboss' ),

		// Default fullname field name.
		'bp-xprofile-firstname-field-name'           => __( 'First Name', 'buddyboss' ),

		// Default fullname field name.
		'bp-xprofile-lastname-field-name'            => __( 'Last Name', 'buddyboss' ),

		// Default fullname field name.
		'bp-xprofile-nickname-field-name'            => __( 'Nickname', 'buddyboss' ),

		// Default fullname field name. (for backward compat).
		'bp-xprofile-fullname-field-name'            => __( 'Name', 'buddyboss' ),

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

		// Group Subscriptions.
		'bb_enable_group_subscriptions'              => true,

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
function bp_add_options() {

	// Get the default options and values.
	$options = bp_get_default_options();

	// Add default options.
	foreach ( $options as $key => $value ) {
		bp_add_option( $key, $value );
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
function bp_delete_options() {

	// Get the default options and values.
	$options = bp_get_default_options();

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
function bp_setup_option_filters() {

	// Get the default options and values.
	$options = bp_get_default_options();

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
function bp_pre_get_option( $value = false ) {
	$bp = buddypress();

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
function bp_get_option( $option_name, $default = '' ) {
	$value = get_blog_option( bp_get_root_blog_id(), $option_name, $default );

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
function bp_add_option( $option_name, $value ) {
	return add_blog_option( bp_get_root_blog_id(), $option_name, $value );
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
function bp_update_option( $option_name, $value ) {
	return update_blog_option( bp_get_root_blog_id(), $option_name, $value );
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
function bp_delete_option( $option_name ) {
	return delete_blog_option( bp_get_root_blog_id(), $option_name );
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
function bp_core_activate_site_options( $keys = array() ) {

	if ( ! empty( $keys ) && is_array( $keys ) ) {
		$bp = buddypress();

		$errors = false;

		foreach ( $keys as $key => $default ) {
			if ( empty( $bp->site_options[ $key ] ) ) {
				$bp->site_options[ $key ] = bp_get_option( $key, $default );

				if ( ! bp_update_option( $key, $bp->site_options[ $key ] ) ) {
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
function bp_core_get_root_options() {
	global $wpdb;

	// Get all the BuddyPress settings, and a few useful WP ones too.
	$root_blog_options                   = bp_get_default_options();
	$root_blog_options['registration']   = '0';
	$root_blog_options['avatar_default'] = 'mysteryman';
	$root_blog_option_keys               = array_keys( $root_blog_options );

	// Do some magic to get all the root blog options in 1 swoop
	// Check cache first - We cache here instead of using the standard WP
	// settings cache because the current blog may not be the root blog,
	// and it's not practical to access the cache across blogs.
	$root_blog_options_meta = wp_cache_get( 'root_blog_options', 'bp' );

	if ( false === $root_blog_options_meta ) {
		$blog_options_keys      = "'" . join( "', '", (array) $root_blog_option_keys ) . "'";
		$blog_options_table     = bp_is_multiblog_mode() ? $wpdb->options : $wpdb->get_blog_prefix( bp_get_root_blog_id() ) . 'options';
		$blog_options_query     = "SELECT option_name AS name, option_value AS value FROM {$blog_options_table} WHERE option_name IN ( {$blog_options_keys} )";
		$root_blog_options_meta = $wpdb->get_results( $blog_options_query );

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
			$sitemeta_options_keys  = "'" . join( "', '", (array) $network_option_keys ) . "'";
			$sitemeta_options_query = $wpdb->prepare( "SELECT meta_key AS name, meta_value AS value FROM {$wpdb->sitemeta} WHERE meta_key IN ( {$sitemeta_options_keys} ) AND site_id = %d", $current_site->id );
			$network_options_meta   = $wpdb->get_results( $sitemeta_options_query );

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
 * time during a pageload and stored in `buddypress()->site_options` to prevent future lookups.
 * See {@see bp_core_get_root_options()}.
 *
 * @since BuddyPress 2.3.0
 *
 * @param string $option Name of the option key.
 * @return mixed Value, if found.
 */
function bp_core_get_root_option( $option ) {
	$bp = buddypress();

	if ( ! isset( $bp->site_options ) ) {
		$bp->site_options = bp_core_get_root_options();
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
function bp_disable_profile_sync( $default = false ) {

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
function bp_disable_advanced_profile_search( $default = false ) {

	/**
	 * Filters whether or not profile search is disabled.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param bool $value Whether or not profile search is disabled.
	 */
	return (bool) apply_filters( 'bp_disable_advanced_profile_search', ! (bool) bp_get_option( 'bp-enable-profile-search', $default ) );
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
function bp_hide_loggedout_adminbar( $default = true ) {

	/**
	 * Filters whether or not the toolbar is hidden for logged out users.
	 *
	 * @since BuddyPress 1.6.0
	 *
	 * @param bool $value Whether or not the toolbar is hidden.
	 */
	return (bool) apply_filters( 'bp_hide_loggedout_adminbar', (bool) bp_get_option( 'hide-loggedout-adminbar', $default ) );
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
function bp_disable_avatar_uploads( $default = true ) {

	/**
	 * Filters whether or not members are able to upload their own avatars.
	 *
	 * @since BuddyPress 1.6.0
	 *
	 * @param bool $value Whether or not members are able to upload their own avatars.
	 */
	return (bool) apply_filters( 'bp_disable_avatar_uploads', (bool) bp_get_option( 'bp-disable-avatar-uploads', $default ) );
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
function bp_disable_cover_image_uploads( $default = false ) {

	/**
	 * Filters whether or not members are able to upload their own cover photos.
	 *
	 * @since BuddyPress 2.4.0
	 *
	 * @param bool $value Whether or not members are able to upload their own cover photos.
	 */
	return (bool) apply_filters( 'bp_disable_cover_image_uploads', (bool) bp_get_option( 'bp-disable-cover-image-uploads', $default ) );
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
 *                           Defaults to the value of `bp_disable_avatar_uploads()`.
 * @return bool True if group avatar uploads are disabled, otherwise false.
 */
function bp_disable_group_avatar_uploads( $default = null ) {
	$disabled = bp_get_option( 'bp-disable-group-avatar-uploads', '' );

	if ( '' === $disabled ) {
		if ( is_null( $default ) ) {
			$disabled = bp_disable_avatar_uploads();
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
function bp_disable_group_cover_image_uploads( $default = false ) {

	/**
	 * Filters whether or not members are able to upload group cover photos.
	 *
	 * @since BuddyPress 2.4.0
	 *
	 * @param bool $value Whether or not members are able to upload thier groups cover photos.
	 */
	return (bool) apply_filters( 'bp_disable_group_cover_image_uploads', (bool) bp_get_option( 'bp-disable-group-cover-image-uploads', $default ) );
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
function bp_disable_group_type_creation( $default = false ) {

	/**
	 * Filters whether or not members are able to create group types.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param bool $value Whether or not members are able to create groups types.
	 */
	return (bool) apply_filters( 'bp_disable_group_type_creation', (bool) bp_get_option( 'bp-disable-group-type-creation', $default ) );
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
function bp_enable_group_hierarchies( $default = false ) {

	/**
	 * Filters whether or not groups are able to have a parent and sub groups.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param bool $value whether or not groups are able to have a parent and sub groups.
	 */
	return (bool) apply_filters( 'bp_enable_group_hierarchies', (bool) bp_get_option( 'bp-enable-group-hierarchies', $default ) );
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
function bp_enable_group_hide_subgroups( $default = false ) {

	/**
	 * Filters whether or not group hide subgroups from the main Groups Directory
	 *
	 * @since BuddyBoss 1.5.1
	 *
	 * @param bool $value whether or not group hide subgroups from the main Groups Directory.
	 */
	return (bool) apply_filters( 'bp_enable_group_hide_subgroups', (bool) bp_get_option( 'bp-enable-group-hide-subgroups', $default ) );
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
function bp_enable_group_restrict_invites( $default = false ) {

	/**
	 * Filters whether group restrict invites to members who already in specific parent group?
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param bool $value whether or not group restrict invites to members who already in specific parent group?
	 */
	return (bool) apply_filters( 'bp_enable_group_restrict_invites', (bool) bp_get_option( 'bp-enable-group-restrict-invites', $default ) );
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
function bp_enable_group_auto_join( $default = false ) {

	/**
	 * Filters whether or not groups auto approve membership.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param bool $value whether or not groups auto approve membership.
	 */
	return (bool) apply_filters( 'bp_enable_group_auto_join', (bool) bp_get_option( 'bp-enable-group-auto-join', $default ) );
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
function bp_disable_account_deletion( $default = false ) {

	/**
	 * Filters whether or not members are able to delete their own accounts.
	 *
	 * @since BuddyPress 1.6.0
	 *
	 * @param bool $value Whether or not members are able to delete their own accounts.
	 */
	return apply_filters( 'bp_disable_account_deletion', (bool) bp_get_option( 'bp-disable-account-deletion', $default ) );
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
function bp_enable_private_network( $default = false ) {
	global $bp;

	if ( isset( $bp ) && isset( $bp->site_options ) && is_array( $bp->site_options ) && isset( $bp->site_options['bp-enable-private-network'] ) ) {
		$val = (bool) $bp->site_options['bp-enable-private-network'];
	} else {
		$val = (bool) bp_get_option( 'bp-enable-private-network', $default );
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
function bp_disable_blogforum_comments( $default = false ) {
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
	$disable = (bool) bb_is_post_type_feed_comment_enable( $post->post_type, $default ) ? false : true;

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
function bb_is_post_type_feed_comment_enable( $post_type, $default = false ) {
	$option_name = bb_post_type_feed_comment_option_name( $post_type );

	/**
	 * Filters whether or not custom post type feed comments are enable.
	 *
	 * @since BuddyBoss 1.7.2
	 *
	 * @param bool $value Whether or not custom post type activity feed comments are enable.
	 */
	return (bool) apply_filters( 'bb_is_post_type_feed_comment_enable', (bool) bp_get_option( $option_name, $default ), $post_type );
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
function bp_restrict_group_creation( $default = true ) {

	/**
	 * Filters whether or not group creation is turned off.
	 *
	 * @since BuddyPress 1.6.0
	 *
	 * @param bool $value Whether or not group creation is turned off.
	 */
	return (bool) apply_filters( 'bp_restrict_group_creation', (bool) bp_get_option( 'bp_restrict_group_creation', $default ) );
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
function bp_force_buddybar( $default = true ) {

	/**
	 * Filters whether or not BuddyBar should be forced in place of WP Admin Bar.
	 *
	 * @since BuddyPress 1.6.0
	 *
	 * @param bool $value Whether or not BuddyBar should be forced in place of WP Admin Bar.
	 */
	return (bool) apply_filters( 'bp_force_buddybar', (bool) bp_get_option( '_bp_force_buddybar', $default ) );
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
function bp_is_akismet_active( $default = true ) {

	/**
	 * Filters whether or not Akismet is enabled.
	 *
	 * @since BuddyPress 1.6.0
	 *
	 * @param bool $value Whether or not Akismet is enabled.
	 */
	return (bool) apply_filters( 'bp_is_akismet_active', (bool) bp_get_option( '_bp_enable_akismet', $default ) );
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
function bp_is_activity_autoload_active( $default = true ) {

	/**
	 * Filters whether or not Activity Autoload is enabled.
	 *
	 * @since BuddyPress 2.0.0
	 *
	 * @param bool $value Whether or not Activity Autoload is enabled.
	 */
	return (bool) apply_filters( 'bp_is_activity_autoload_active', (bool) bp_get_option( '_bp_enable_activity_autoload', $default ) );
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
function bp_is_activity_edit_enabled( $default = false ) {

	/**
	 * Filters whether or not Activity edit is enabled.
	 *
	 * @since BuddyBoss 1.5.0
	 *
	 * @param bool $value Whether or not Activity edit is enabled.
	 */
	return (bool) apply_filters( 'bp_is_activity_edit_enabled', (bool) bp_get_option( '_bp_enable_activity_edit', $default ) );
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
function bp_is_relevant_feed_enabled( $default = false ) {

	/**
	 * Filters whether or not relevant feed is enabled.
	 *
	 * @since BuddyBoss 1.5.5
	 *
	 * @param bool $value Whether or not relevant feed is enabled.
	 */

	return (bool) apply_filters( 'bp_is_relevant_feed_enabled', (bool) bp_get_option( '_bp_enable_relevant_feed', $default ) );
}

/**
 * Single time slot by time key.
 *
 * @param null $time Return single time slot by time key.
 *
 * @return mixed|void
 * @since BuddyBoss 1.5.0
 */
function bp_activity_edit_times( $time = null ) {

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
 * Get BuddyBoss Activity Time option.
 *
 * @param bool $default when option not found, function will return $default value.
 *
 * @return mixed|void
 *
 * @since BuddyBoss 1.5.0
 */
function bp_get_activity_edit_time( $default = false ) {
	return apply_filters( 'bp_get_activity_edit_time', bp_get_option( '_bp_activity_edit_time', $default ) );
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
function bp_is_activity_tabs_active( $default = false ) {

	/**
	 * Filters whether or not Activity Tabs are enabled.
	 *
	 * @since BuddyBoss 1.1.6
	 *
	 * @param bool $value Whether or not Activity Tabs are enabled.
	 */
	return (bool) apply_filters( 'bp_is_activity_tabs_active', (bool) bp_get_option( '_bp_enable_activity_tabs', $default ) );
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
function bp_is_activity_heartbeat_active( $default = true ) {

	/**
	 * Filters whether or not Activity Heartbeat refresh is enabled.
	 *
	 * @since BuddyPress 2.0.0
	 *
	 * @param bool $value Whether or not Activity Heartbeat refresh is enabled.
	 */
	return (bool) apply_filters( 'bp_is_activity_heartbeat_active', (bool) bp_get_option( '_bp_enable_heartbeat_refresh', $default ) );
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
function bp_is_activity_follow_active( $default = false ) {

	/**
	 * Filters whether or not Activity Follow is enabled.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param bool $value Whether or not Activity Follow is enabled.
	 */
	return (bool) apply_filters( 'bp_is_activity_follow_active', (bool) bp_get_option( '_bp_enable_activity_follow', $default ) );
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
function bp_is_activity_like_active( $default = true ) {

	/**
	 * Filters whether or not Activity Like is enabled.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param bool $value Whether or not Activity Like is enabled.
	 */
	return (bool) apply_filters( 'bp_is_activity_like_active', (bool) bp_get_option( '_bp_enable_activity_like', $default ) );
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
function bp_is_activity_link_preview_active( $default = false ) {

	/**
	 * Filters whether or not Activity Link Preview is enabled.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param bool $value Whether or not Activity Link Preview is enabled.
	 */
	return (bool) apply_filters( 'bp_is_activity_link_preview_active', (bool) bp_get_option( '_bp_enable_activity_link_preview', $default ) );
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
function bp_get_theme_package_id( $default = 'nouveau' ) {

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
function bp_force_friendship_to_message( $default = false ) {

	$value = (bool) bp_get_option( 'bp-force-friendship-to-message', $default );
	if ( ( ! is_admin() && bp_current_user_can( 'bp_moderate' ) ) || ( defined( 'DOING_AJAX' ) && true === DOING_AJAX && bp_current_user_can( 'bp_moderate' ) ) ) {
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
function bb_is_friends_auto_follow_active( $default = false ) {

	/**
	 * Filter whether the activity auto follow enabled or not.
	 *
	 * @since BuddyBoss 2.3.1
	 *
	 * @param bool $value Whether the activity auto follow enabled or not.
	 */
	return (bool) apply_filters( 'bb_is_friends_auto_follow_active', bp_is_active( 'activity' ) && bp_get_option( 'bb_enable_friends_auto_follow', $default ) );
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
function bp_member_type_enable_disable( $default = false ) {

	/**
	 * Filters whether member type is enabled or not.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param bool $value Whether member type is enabled or not.
	 */
	return (bool) apply_filters( 'bp_member_type_enable_disable', (bool) bp_get_option( 'bp-member-type-enable-disable', $default ) );
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
function bp_member_type_display_on_profile( $default = false ) {

	/**
	 * Filters whether display member type on profile is enabled or not.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param bool $value Whether display member type on profile is enabled or not.
	 */
	return (bool) apply_filters( 'bp_member_type_display_on_profile', (bool) bp_get_option( 'bp-member-type-display-on-profile', $default ) );
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
function bp_disable_invite_member_email_subject( $default = false ) {

	/**
	 * Filters whether email subject customize is enabled or not.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param bool $value Whether email subject customize is enabled or not.
	 */
	return (bool) apply_filters( 'bp_disable_invite_member_email_subject', (bool) bp_get_option( 'bp-disable-invite-member-email-subject', $default ) );
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
function bp_disable_invite_member_email_content( $default = true ) {

	/**
	 * Filters whether email content customize is enabled or not.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param bool $value Whether email content customize is enabled or not.
	 */
	return (bool) apply_filters( 'bp_disable_invite_member_email_content', (bool) bp_get_option( 'bp-disable-invite-member-email-content', $default ) );
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
function bp_disable_invite_member_type( $default = false ) {

	/**
	 * Filters whether allow users to sign up the profile types to personal inviting is enabled or not.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param bool $value Whether allow users to sign up the profile types to personal inviting is enabled or not.
	 */
	return (bool) apply_filters( 'bp_disable_invite_member_type', (bool) bp_get_option( 'bp-disable-invite-member-type', $default ) );
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
function bp_is_post_type_feed_enable( $post_type, $default = false ) {

	/**
	 * Filters whether post type feed enabled or not.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param bool $value Whether post type feed enabled or not.
	 */
	return (bool) apply_filters( 'bp_is_post_type_feed_enable', (bool) bp_get_option( bb_post_type_feed_option_name( $post_type ), $default ) );
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
function bp_is_custom_post_type_feed_enable( $default = false ) {

	/**
	 * Filters whether custom post type feed enabled or not.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param bool $value Whether custom post type feed enabled or not.
	 */
	return (bool) apply_filters( 'bp_is_custom_post_type_feed_enable', (bool) bp_get_option( 'bp-enable-custom-post-type-feed', $default ) );
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
function bp_platform_is_feed_enable( $activity_type, $default = true ) {

	/**
	 * Filters whether specified $activity_type should be enabled or no.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param bool $value Whether or not the feed enable or not.
	 */
	return (bool) apply_filters( 'bp_platform_is_feed_enable', (bool) bp_get_option( $activity_type, $default ) );
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
function bp_enable_site_registration( $default = false ) {

	/**
	 * Filters whether or not the registration enable.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param bool $value Whether or not the registration enable.
	 */
	return (bool) apply_filters( 'bp_enable_site_registration', (bool) bp_get_option( 'bp-enable-site-registration', $default ) );
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
function bp_member_type_default_on_registration( $default = '' ) {

	/**
	 * Filters whether default profile type set on registration.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param string $value Whether default profile type set on registration.
	 */
	return apply_filters( 'bp_member_type_default_on_registration', bp_get_option( 'bp-member-type-default-on-registration', $default ) );
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
function bp_enable_send_invite_member_type( $member_type, $default = false ) {

	/**
	 * Filters whether specified $member_type should be allowed to send invites.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param bool $value whether or not allow member type invitations.
	 */
	return (bool) apply_filters( 'bp_enable_send_invite_member_type', (bool) bp_get_option( $member_type, $default ) );
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
function bp_enable_private_network_public_content( $default = '' ) {

	/**
	 * Filters private network's public content.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param bool $value Private network's public content.
	 */
	return apply_filters( 'bp_enable_private_network_public_content', bp_get_option( 'bp-enable-private-network-public-content', '' ) );
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
function bp_show_login_adminbar( $default = true ) {

	/**
	 * Filters whether or not the toolbar is hidden for logged in users (non-admins).
	 *
	 * @since BuddyBoss 1.1.0
	 *
	 * @param bool $value Whether or not the toolbar is hidden.
	 */
	return (bool) apply_filters( 'bp_show_login_adminbar', (bool) bp_get_option( 'show-login-adminbar', $default ) );
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
function bp_show_admin_adminbar( $default = true ) {

	/**
	 * Filters whether or not the toolbar is hidden for admin users.
	 *
	 * @since BuddyBoss 1.1.0
	 *
	 * @param bool $value Whether or not the toolbar is hidden.
	 */
	return (bool) apply_filters( 'bp_show_admin_adminbar', (bool) bp_get_option( 'show-admin-adminbar', $default ) );
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
function bp_enable_profile_gravatar( $default = false ) {

	/**
	 * Filters whether or not members are able to use gravatars.
	 *
	 * @since BuddyBoss 1.0.9
	 *
	 * @param bool $value Whether or not members are able to use gravatars.
	 */
	return (bool) apply_filters( 'bp_enable_profile_gravatar', (bool) ( bp_get_option( 'bp-enable-profile-gravatar', $default ) ) );
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
function bp_hide_last_name( $default = true ) {

	/**
	 * Filters whether or not members are able to hide last name field.
	 *
	 * @since BuddyBoss 1.1.1
	 *
	 * @param bool $value Whether or not members are able to hide last name field.
	 */
	return (bool) apply_filters( 'bp_hide_last_name', (bool) bp_get_option( 'bp-hide-last-name', $default ) );
}

/**
 * Allow members to hide first name field if in display format nick name selected.
 *
 * @since BuddyBoss 1.1.1
 *
 * @param bool $default Optional. Fallback value if not found in the database.
 *                      Default: true.
 */
function bp_hide_nickname_first_name( $default = true ) {

	/**
	 * Filters whether or not members are able to hide first name field if in display format nick name selected.
	 *
	 * @since BuddyBoss 1.1.1
	 *
	 * @param bool $value Whether or not members are able to hide first name field if in display format nick name selected.
	 */
	return (bool) apply_filters( 'bp_hide_nickname_first_name', (bool) bp_get_option( 'bp-hide-nickname-first-name', $default ) );
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
function bp_hide_nickname_last_name( $default = true ) {

	/**
	 * Filters whether or not members are able to hide last name field if in display format nick name selected.
	 *
	 * @since BuddyBoss 1.1.1
	 *
	 * @param bool $value Whether or not members are able to hide last name field if in display format nick name selected.
	 */
	return (bool) apply_filters( 'bp_hide_nickname_last_name', (bool) bp_get_option( 'bp-hide-nickname-last-name', $default ) );
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
function bp_register_confirm_email( $default = false ) {

	/**
	 * Filters whether or not display email confirmation field in registrations.
	 *
	 * @since BuddyBoss 1.1.6
	 *
	 * @param bool $value whether or not display email confirmation field in registrations.
	 */
	return (bool) apply_filters( 'bp_register_confirm_email', (bool) bp_get_option( 'register-confirm-email', $default ) );
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
function bb_register_legal_agreement( $default = false ) {

	/**
	 * Filters whether or not display legal agreement field in registrations.
	 *
	 * @since BuddyBoss 1.5.8.3
	 *
	 * @param bool $value whether or not display legal agreement field in registrations.
	 */
	return (bool) apply_filters( 'bb_register_legal_agreement', (bool) bp_get_option( 'register-legal-agreement', $default ) );
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
function bp_register_confirm_password( $default = false ) {

	/**
	 * Filters whether or not display password confirmation field in registrations.
	 *
	 * @since BuddyBoss 1.1.6
	 *
	 * @param bool $value whether or not display password confirmation field in registrations.
	 */
	return (bool) apply_filters( 'bp_register_confirm_password', (bool) bp_get_option( 'register-confirm-password', $default ) );
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
function bp_profile_layout_default_format( $default = 'grid' ) {

	/**
	 * Filters profile layout format.
	 *
	 * @since BuddyBoss 1.2.0
	 *
	 * @param bool $value Profile layout format.
	 */
	return apply_filters( 'bp_profile_layout_default_format', bp_get_option( 'bp-profile-layout-default-format', $default ) );
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
function bp_group_layout_default_format( $default = 'grid' ) {

	/**
	 * Filters group layout format.
	 *
	 * @since BuddyBoss 1.2.0
	 *
	 * @param bool $value Group layout format.
	 */
	return apply_filters( 'bp_group_layout_default_format', bp_get_option( 'bp-group-layout-default-format', $default ) );
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
function bp_allow_custom_registration( $default = false ) {

	/**
	 * Filters whether or not allow custom registrations.
	 *
	 * @since BuddyBoss 1.2.8
	 *
	 * @param bool $value whether or not allow custom registrations.
	 */
	return (bool) apply_filters( 'bp_allow_custom_registration', (bool) bp_get_option( 'allow-custom-registration', $default ) );
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
function bp_custom_register_page_url( $default = '' ) {

	/**
	 * Filters custom registration page URL.
	 *
	 * @since BuddyBoss 1.2.8
	 *
	 * @param string $value custom registration page URL.
	 */
	return apply_filters( 'bp_custom_register_page_url', bp_get_option( 'register-page-url', $default ) );
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
function bp_disable_group_messages( $default = false ) {

	/**
	 * Filters whether or not group organizer and moderator allowed to send group message.
	 *
	 * @since BuddyBoss 1.2.3
	 *
	 * @param bool $value whether or not group organizer and moderator allowed to send group message.
	 */
	return (bool) apply_filters( 'bp_disable_group_messages', (bool) bp_get_option( 'bp-disable-group-messages', $default ) );
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
function bp_core_display_name_format( $default = 'first_name' ) {

	/**
	 * Filters default display name format.
	 *
	 * @since BuddyBoss 1.5.1
	 *
	 * @param string $value Default display name format.
	 */
	return apply_filters( 'bp_core_display_name_format', bp_get_option( 'bp-display-name-format', $default ) );
}

/**
 * Enable private REST APIs.
 * - Wrapper function to check settings with BuddyBoss APP and Platform both.
 *
 * @since BuddyBoss 1.5.7
 *
 * @return bool True if  private REST APIs is enabled, otherwise false.
 */
function bp_rest_enable_private_network() {

	$retval = false;
	if (
		true === (bool) bp_enable_private_rest_apis() &&
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
		if ( ! empty( $current_rest_url ) && bb_is_allowed_endpoint( $current_rest_url ) ) {
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
function bb_enable_symlinks( $default = false ) {

	/**
	 * Filters whether or not the symlink is enabled in Media, Document & Video.
	 *
	 * @since BuddyBoss 1.7.0
	 *
	 * @param bool $value Whether or not the symlink is enabled in Media, Document & Video enable.
	 */
	return (bool) apply_filters( 'bb_enable_symlinks', (bool) bp_get_option( 'bp_media_symlink_support', $default ) );
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
function bb_post_type_feed_option_name( $post_type ) {
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
function bb_post_type_feed_comment_option_name( $post_type ) {
	return 'bp-feed-custom-post-type-' . $post_type . '-comments';
}

/**
 * Custom post types for activity settings.
 *
 * @since BuddyBoss 1.7.2
 *
 * @return array.
 */
function bb_feed_post_types() {
	// Get all active custom post type.
	$post_types = get_post_types( array( 'public' => true ) );

	// Exclude BP CPT.
	$bp_exclude_cpt = array( 'forum', 'topic', 'reply', 'page', 'attachment', 'bp-group-type', 'bp-member-type' );

	$bp_excluded_cpt = array();

	foreach ( $post_types as $post_type ) {
		// Exclude all the custom post type which is already in BuddyPress Activity support.
		if ( in_array( $post_type, $bp_exclude_cpt, true ) ) {
			continue;
		}

		$bp_excluded_cpt[] = $post_type;
	}

	return $bp_excluded_cpt;
}

/**
 * Custom post types for activity settings.
 *
 * @since BuddyBoss 1.7.2
 *
 * @return array.
 */
function bb_feed_not_allowed_comment_post_types() {
	// Exclude BP CPT.
	return array( 'forum', 'product', 'topic', 'reply', 'page', 'attachment', 'bp-group-type', 'bp-member-type' );
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
function bp_enable_private_rest_apis( $default = false ) {
	global $bp;

	if ( isset( $bp ) && isset( $bp->site_options ) && is_array( $bp->site_options ) && isset( $bp->site_options['bb-enable-private-rest-apis'] ) ) {
		$val = (bool) $bp->site_options['bb-enable-private-rest-apis'];
	} else {
		$val = (bool) bp_get_option( 'bb-enable-private-rest-apis', $default );
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
function bb_enable_private_rest_apis_public_content( $default = '' ) {

	/**
	 * Filters Private REST APIs public content.
	 *
	 * @since BuddyBoss 1.8.6
	 *
	 * @param bool $value Private REST APIs public content.
	 */
	return apply_filters( 'bb_enable_private_rest_apis_public_content', bp_get_option( 'bb-enable-private-rest-apis-public-content', '' ) );
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
function bp_enable_private_rss_feeds( $default = false ) {
	global $bp;

	if ( isset( $bp ) && isset( $bp->site_options ) && is_array( $bp->site_options ) && isset( $bp->site_options['bb-enable-private-rss-feeds'] ) ) {
		$val = (bool) $bp->site_options['bb-enable-private-rss-feeds'];
	} else {
		$val = (bool) bp_get_option( 'bb-enable-private-rss-feeds', $default );
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
function bb_enable_private_rss_feeds_public_content( $default = '' ) {

	/**
	 * Filters Private REST APIs public content.
	 *
	 * @since BuddyBoss 1.8.6
	 *
	 * @param bool $value Private REST APIs public content.
	 */
	return apply_filters( 'bb_enable_private_rss_feeds_public_content', bp_get_option( 'bb-enable-private-rss-feeds-public-content', '' ) );
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
function bb_get_profile_avatar_type( $default = 'BuddyBoss' ) {

	/**
	 * Filters profile avatar type.
	 *
	 * @since BuddyBoss 1.8.6
	 *
	 * @param string $value Profile avatar type.
	 */
	return apply_filters( 'bb_get_profile_avatar_type', bp_get_option( 'bp-profile-avatar-type', $default ) );
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
function bb_get_default_profile_avatar_type( $default = 'buddyboss' ) {

	/**
	 * Filters default profile avatar type.
	 *
	 * @since BuddyBoss 1.8.6
	 *
	 * @param string $value Default profile avatar type.
	 */
	return apply_filters( 'bb_get_default_profile_avatar_type', bp_get_option( 'bp-default-profile-avatar-type', $default ) );
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
function bb_get_default_custom_upload_profile_avatar( $default = '', $size = 'full' ) {
	$custom_avatar_url = bp_get_option( 'bp-default-custom-profile-avatar', $default );

	if ( ! empty( $custom_avatar_url ) && 'full' !== $size ) {
		$custom_avatar_url = bb_get_default_custom_avatar();
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
function bb_get_default_profile_cover_type( $default = 'buddyboss' ) {

	/**
	 * Filters default profile cover type.
	 *
	 * @since BuddyBoss 1.8.6
	 *
	 * @param string $value Default profile cover type.
	 */
	return apply_filters( 'bb_get_default_profile_cover_type', bp_get_option( 'bp-default-profile-cover-type', $default ) );
}

/**
 * Get default custom upload profile cover URL.
 *
 * @since BuddyBoss 1.8.6
 *
 * @return string Return default custom upload profile cover URL.
 */
function bb_get_default_custom_upload_profile_cover() {
	/**
	 * Filters to change default custom upload cover image.
	 *
	 * @since BuddyBoss 1.8.6
	 *
	 * @param string $value Default custom upload profile cover URL.
	 */
	return apply_filters( 'bb_get_default_custom_upload_profile_cover', bp_get_option( 'bp-default-custom-profile-cover' ) );
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
function bb_get_default_group_avatar_type( $default = 'buddyboss' ) {

	/**
	 * Filters default group avatar type.
	 *
	 * @since BuddyBoss 1.8.6
	 *
	 * @param string $value Default group avatar type.
	 */
	return apply_filters( 'bb_get_default_group_avatar_type', bp_get_option( 'bp-default-group-avatar-type', $default ) );
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
function bb_get_default_custom_upload_group_avatar( $default = '', $size = 'full' ) {
	$custom_group_avatar_url = bp_get_option( 'bp-default-custom-group-avatar', $default );

	if ( ! empty( $custom_group_avatar_url ) && 'full' !== $size ) {
		$custom_group_avatar_url = bb_get_default_custom_avatar( 'group' );
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
function bb_get_default_group_cover_type( $default = 'buddyboss' ) {

	/**
	 * Filters default group cover type.
	 *
	 * @since BuddyBoss 1.8.6
	 *
	 * @param string $value Default group cover type.
	 */
	return apply_filters( 'bb_get_default_group_cover_type', bp_get_option( 'bp-default-group-cover-type', $default ) );
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
function bb_get_default_custom_upload_group_cover() {
	/**
	 * Filters default custom upload cover image URL.
	 *
	 * @since BuddyBoss 1.8.6
	 *
	 * @param string $value Default custom upload group cover URL.
	 */
	return apply_filters( 'bb_get_default_custom_upload_group_cover', bp_get_option( 'bp-default-custom-group-cover' ) );
}

/**
 * Is group subscription turned off?
 *
 * @since BuddyBoss 2.2.8
 *
 * @param bool $default Optional. Fallback value if not found in the database.
 *                      Default: true.
 *
 * @return bool True if group subscription is enabled, otherwise false.
 */
function bb_enable_group_subscriptions( $default = true ) {

	/**
	 * Filters whether group subscription is turned off.
	 *
	 * @since BuddyBoss 2.2.8
	 *
	 * @param bool $value Whether group subscription is turned off.
	 */
	return (bool) apply_filters( 'bb_enable_group_subscriptions', (bool) bp_get_option( 'bb_enable_group_subscriptions', $default ) );
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
function bb_get_profile_slug_format( $default = 'username' ) {

	/**
	 * Filters default profile slug format.
	 *
	 * @since BuddyBoss 2.3.1
	 *
	 * @param string $value Default profile slug format.
	 */
	return apply_filters( 'bb_get_profile_slug_format', bp_get_option( 'bb_profile_slug_format', $default ) );
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
function bb_domain_restrictions_setting( $default = array() ) {

	/**
	 * Filters domain restriction settings.
	 *
	 * @since BuddyBoss 2.4.11
	 *
	 * @param array $value Domain restrictions setting value.
	 */
	return apply_filters( 'bb_domain_restrictions_setting', bp_get_option( 'bb-domain-restrictions', $default ) );
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
function bb_email_restrictions_setting( $default = array() ) {

	/**
	 * Filters email restriction settings.
	 *
	 * @since BuddyBoss 2.4.11
	 *
	 * @param array $value Email restrictions setting value.
	 */
	return apply_filters( 'bb_email_restrictions_setting', bp_get_option( 'bb-email-restrictions', $default ) );
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
function bb_is_activity_comment_edit_enabled( $default = false ) {

	/**
	 * Filters whether Activity comment edit is enabled.
	 *
	 * @since BuddyBoss 2.4.40
	 *
	 * @param bool $value Whether Activity comment edit is enabled.
	 */
	return (bool) apply_filters( 'bb_is_activity_comment_edit_enabled', (bool) bp_get_option( '_bb_enable_activity_comment_edit', $default ) );
}

/**
 * Get BuddyBoss activity comment Time option.
 *
 * @since BuddyBoss 2.4.40
 *
 * @param bool $default when option not found, function will return $default value.
 *
 * @return mixed|void
 */
function bb_get_activity_comment_edit_time( $default = false ) {
	return apply_filters( 'bb_get_activity_comment_edit_time', bp_get_option( '_bb_activity_comment_edit_time', $default ) );
}
