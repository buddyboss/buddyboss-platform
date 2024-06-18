<?php
/**
 * Deprecated functions.
 *
 * @deprecated BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Output the latest update of the current member in the loop.
 *
 * @since BuddyPress 1.2.0
 * @deprecated BuddyBoss 1.0.0
 * No longer updating member's last activity
 *
 * @param array|string $args {@see bp_get_member_latest_update()}.
 */
function bp_member_latest_update( $args = '' ) {
	_deprecated_function( __FUNCTION__, '1.0.0' );

	echo bp_get_member_latest_update( $args );
}

	/**
	 * Get the latest update from the current member in the loop.
	 *
	 * @since BuddyPress 1.2.0
	 * @deprecated 3.1.0
	 *
	 * @param array|string $args {
	 *     Array of optional arguments.
	 *     @type int  $length    Truncation length. Default: 225.
	 *     @type bool $view_link Whether to provide a 'View' link for
	 *                           truncated entries. Default: false.
	 * }
	 * @return string
	 */
function bp_get_member_latest_update( $args = '' ) {
	global $members_template;

	_deprecated_function( __FUNCTION__, '1.0.0' );

	global $members_template;

	$defaults = array(
		'length'    => 225,
		'view_link' => true,
	);

	$r = bp_parse_args( $args, $defaults );
	extract( $r );

	if ( ! bp_is_active( 'activity' ) || empty( $members_template->member->latest_update ) || ! $update = maybe_unserialize( $members_template->member->latest_update ) ) {
		return false;
	}

	/**
	 * Filters the excerpt of the latest update for current member in the loop.
	 *
	 * @since 1.2.5
	 * @since 2.6.0 Added the `$r` parameter.
	 *
	 * @param string $value Excerpt of the latest update for current member in the loop.
	 * @param array  $r     Array of parsed arguments.
	 */
	$update_content = apply_filters( 'bp_get_activity_latest_update_excerpt', trim( strip_tags( bp_create_excerpt( $update['content'], $length ) ) ), $r );

	$update_content = sprintf( _x( '- &quot;%s&quot;', 'member latest update in member directory', 'buddyboss' ), $update_content );

	// If $view_link is true and the text returned by bp_create_excerpt() is different from the original text (ie it's
	// been truncated), add the "View" link.
	if ( $view_link && ( $update_content != $update['content'] ) ) {
		$view = __( 'View', 'buddyboss' );

		$update_content .= '<span class="activity-read-more"><a href="' . bp_activity_get_permalink( $update['id'] ) . '" rel="nofollow">' . $view . '</a></span>';
	}

	/**
	 * Filters the latest update from the current member in the loop.
	 *
	 * @since 1.2.0
	 * @since 2.6.0 Added the `$r` parameter.
	 *
	 * @param string $update_content Formatted latest update for current member.
	 * @param array  $r              Array of parsed arguments.
	 */
	return apply_filters( 'bp_get_member_latest_update', $update_content, $r );
}

/**
 * Output the group description excerpt
 *
 * @since BuddyPress 3.0.0
 * @deprecated BuddyBoss 1.0.0
 * No longer updating member's last activity
 *
 * @param object $group Optional. The group being referenced.
 *                      Defaults to the group currently being iterated on in the groups loop.
 * @param int    $length   Optional. Length of returned string, including ellipsis. Default: 100.
 *
 * @return string Excerpt.
 */
function bp_nouveau_group_description_excerpt( $group = null, $length = null ) {
	_deprecated_function( __FUNCTION__, '1.0.0' );

	echo bp_nouveau_get_group_description_excerpt( $group, $length );
}

/**
 * Filters the excerpt of a group description.
 *
 * Checks if the group loop is set as a 'Grid' layout and returns a reduced excerpt.
 *
 * @since BuddyPress 3.0.0
 * @deprecated BuddyBoss 1.0.0
 *
 * @param object $group Optional. The group being referenced. Defaults to the group currently being
 *                      iterated on in the groups loop.
 * @param int    $length   Optional. Length of returned string, including ellipsis. Default: 100.
 *
 * @return string Excerpt.
 */
function bp_nouveau_get_group_description_excerpt( $group = null, $length = null ) {
	global $groups_template;

	_deprecated_function( __FUNCTION__, '1.0.0' );

	if ( ! $group ) {
		$group =& $groups_template->group;
	}

	/**
	* If this is a grid layout but no length is passed in set a shorter
	* default value otherwise use the passed in value.
	* If not a grid then the BP core default is used or passed in value.
	*/
	if ( bp_nouveau_loop_is_grid() && 'groups' === bp_current_component() ) {
		if ( ! $length ) {
			$length = 100;
		} else {
			$length = $length;
		}
	}

	/**
	* Filters the excerpt of a group description.
	*
	* @since BuddyPress 3.0.0
	*
	* @param string $value Excerpt of a group description.
	* @param object $group Object for group whose description is made into an excerpt.
	* @param object $group Object for group whose description is made into an excerpt.
	*/
	return apply_filters( 'bp_nouveau_get_group_description_excerpt', bp_create_excerpt( $group->description, $length ), $group );
}

/**
 * Display the User's WordPress bio info into the default front page?
 *
 * @since BuddyPress 3.0.0
 * @deprecated BuddyBoss 1.0.0
 *
 * @return bool True to display. False otherwise.
 */
function bp_nouveau_members_wp_bio_info() {
	_deprecated_function( __FUNCTION__, '1.0.0' );

	return false;

	$user_settings = bp_nouveau_get_appearance_settings();

	return ! empty( $user_settings['user_front_page'] ) && ! empty( $user_settings['user_front_bio'] );
}

/**
 * Display the Member description making sure linefeeds are taking in account
 *
 * @since BuddyPress 3.0.0
 * @deprecated BuddyBoss 1.0.0
 *
 * @param int $user_id Optional.
 *
 * @return string HTML output.
 */
function bp_nouveau_member_description( $user_id = 0 ) {
	_deprecated_function( __FUNCTION__, '1.0.0' );

	if ( ! $user_id ) {
		$user_id = bp_loggedin_user_id();

		if ( bp_displayed_user_id() ) {
			$user_id = bp_displayed_user_id();
		}
	}

	// @todo This hack is too brittle.
	add_filter( 'the_author_description', 'make_clickable', 9 );
	add_filter( 'the_author_description', 'wpautop' );
	add_filter( 'the_author_description', 'wptexturize' );
	add_filter( 'the_author_description', 'convert_smilies' );
	add_filter( 'the_author_description', 'convert_chars' );
	add_filter( 'the_author_description', 'stripslashes' );

	the_author_meta( 'description', $user_id );

	remove_filter( 'the_author_description', 'make_clickable', 9 );
	remove_filter( 'the_author_description', 'wpautop' );
	remove_filter( 'the_author_description', 'wptexturize' );
	remove_filter( 'the_author_description', 'convert_smilies' );
	remove_filter( 'the_author_description', 'convert_chars' );
	remove_filter( 'the_author_description', 'stripslashes' );
}

/**
 * Display the Edit profile link (temporary).
 *
 * @since BuddyPress 3.0.0
 * @deprecated BuddyBoss 1.0.0
 *
 * @todo replace with Ajax feature
 *
 * @return string HTML Output
 */
function bp_nouveau_member_description_edit_link() {
	_deprecated_function( __FUNCTION__, '1.0.0' );
	echo bp_nouveau_member_get_description_edit_link();
}

	/**
	 * Get the Edit profile link (temporary)
	 *
	 * @todo  replace with Ajax featur
	 *
	 * @since BuddyPress 3.0.0
	 * @deprecated BuddyBoss 1.0.0
	 *
	 * @return string HTML Output
	 */
function bp_nouveau_member_get_description_edit_link() {

	_deprecated_function( __FUNCTION__, '1.0.0' );

	remove_filter( 'edit_profile_url', 'bp_members_edit_profile_url', 10, 3 );

	if ( is_multisite() && ! current_user_can( 'read' ) ) {
	 $link = get_dashboard_url( bp_displayed_user_id(), 'profile.php' );
	} else {
	 $link = get_edit_profile_url( bp_displayed_user_id() );
	}

	add_filter( 'edit_profile_url', 'bp_members_edit_profile_url', 10, 3 );
	$link .= '#description';

	return sprintf( '<a href="%1$s">%2$s</a>', esc_url( $link ), esc_html__( 'Edit your bio', 'buddyboss' ) );
}

/**
 * Output button for sending a public message (an @-mention).
 *
 * @since BuddyPress 1.2.0
 * @deprecated BuddyBoss 1.0.0
 *
 * @see bp_get_send_public_message_button() for description of parameters.
 *
 * @param array|string $args See {@link bp_get_send_public_message_button()}.
 */
function bp_send_public_message_button( $args = '' ) {
	_deprecated_function( __FUNCTION__, '1.0.0' );

	echo bp_get_send_public_message_button( $args );
}

	/**
	 * Return button for sending a public message (an @-mention).
	 *
	 * @since BuddyPress 1.2.0
	 * @deprecated BuddyBoss 1.0.0
	 *
	 * @param array|string $args {
	 *     All arguments are optional. See {@link BP_Button} for complete
	 *     descriptions.
	 *     @type string $id                Default: 'public_message'.
	 *     @type string $component         Default: 'activity'.
	 *     @type bool   $must_be_logged_in Default: true.
	 *     @type bool   $block_self        Default: true.
	 *     @type string $wrapper_id        Default: 'post-mention'.
	 *     @type string $link_href         Default: the public message link for
	 *                                     the current member in the loop.
	 *     @type string $link_text         Default: 'Public Mention'.
	 *     @type string $link_class        Default: 'activity-button mention'.
	 * }
	 * @return string The button for sending a public message.
	 */
function bp_get_send_public_message_button( $args = '' ) {
	_deprecated_function( __FUNCTION__, '1.0.0' );

	$r = bp_parse_args(
		$args,
		array(
			'id'                => 'public_message',
			'component'         => 'activity',
			'must_be_logged_in' => true,
			'block_self'        => true,
			'wrapper_id'        => 'post-mention',
			'link_href'         => bp_get_send_public_message_link(),
			'link_text'         => __( 'Public Mention', 'buddyboss' ),
			'link_class'        => 'activity-button mention'
		)
	);

	/**
	* Filters the public message button HTML.
	*
	* @since BuddyPress 1.2.10
	*
	* @param array $r Array of arguments for the public message button HTML.
	*/
	return bp_get_button( apply_filters( 'bp_get_send_public_message_button', $r ) );
}

/**
 * Fire the 'bp_register_theme_directory' action.
 *
 * The main action used registering theme directories.
 *
 * @since BuddyPress 1.5.0
 * @deprecated BuddyBoss 1.0.0
 */
function bp_register_theme_directory() {
	_deprecated_function( __FUNCTION__, '1.0.0' );

	/**
	 * Fires inside the 'bp_register_theme_directory' function.
	 *
	 * The main action used registering theme directories.
	 *
	 * @since BuddyPress 1.7.0
	 * @deprecated BuddyBoss 1.0.0
	 */
	do_action( 'bp_register_theme_directory' );
}

/**
 * Determine whether BuddyPress should register the bp-themes directory.
 *
 * @since BuddyPress 1.9.0
 * @deprecated BuddyBoss 1.0.0
 *
 * @return bool True if bp-themes should be registered, false otherwise.
 */
function bp_do_register_theme_directory() {
	_deprecated_function( __FUNCTION__, '1.0.0' );

	// If bp-default exists in another theme directory, bail.
	// This ensures that the version of bp-default in the regular themes
	// directory will always take precedence, as part of a migration away
	// from the version packaged with BuddyPress.
	foreach ( array_values( (array) $GLOBALS['wp_theme_directories'] ) as $directory ) {
		if ( is_dir( $directory . '/bp-default' ) ) {
			return false;
		}
	}

	// If the current theme is bp-default (or a bp-default child), BP
	// should register its directory.
	$register = 'bp-default' === get_stylesheet() || 'bp-default' === get_template();

	// Legacy sites continue to have the theme registered.
	if ( empty( $register ) && ( 1 == get_site_option( '_bp_retain_bp_default' ) ) ) {
		$register = true;
	}

	/**
	* Filters whether BuddyPress should register the bp-themes directory.
	*
	* @since BuddyPress 1.9.0
	*
	* @param bool $register If bp-themes should be registered.
	*/
	return apply_filters( 'bp_do_register_theme_directory', $register );
}


/**
 * Setup the theme's features.
 *
 * Note: BP Legacy's buddypress-functions.php is not loaded in WP Administration
 * as it's loaded using bp_locate_template(). That's why this function is here.
 *
 * @since BuddyPress 2.4.0
 * @deprecated BuddyBoss 1.0.0
 *
 * @global string $content_width the content width of the theme
 */
function bp_register_theme_compat_default_features() {

	_deprecated_function( __FUNCTION__, '1.0.0' );

	global $content_width;

	// Do not set up default features on deactivation.
	if ( bp_is_deactivation() ) {
		return;
	}

	// If the current theme doesn't need theme compat, bail at this point.
	if ( ! bp_use_theme_compat_with_current_theme() ) {
		return;
	}

	// Make sure BP Legacy is the Theme Compat in use.
	if ( 'legacy' !== bp_get_theme_compat_id() ) {
		return;
	}

	// Get the theme.
	$current_theme = wp_get_theme();
	$theme_handle  = $current_theme->get_stylesheet();
	$parent        = $current_theme->parent();

	if ( $parent ) {
		$theme_handle = $parent->get_stylesheet();
	}

	/**
	* Since Companion stylesheets, the $content_width is smaller
	* than the width used by BuddyPress, so we need to manually set the
	* content width for the concerned themes.
	*
	* Example: array( stylesheet => content width used by BuddyPress )
	*/
	$bp_content_widths = array(
		'twentyfifteen'  => 1300,
		'twentyfourteen' => 955,
		'twentythirteen' => 890,
	);

	// Default values.
	$bp_content_width = (int) $content_width;
	$bp_handle        = 'bp-legacy-css';

	// Specific to themes having companion stylesheets.
	if ( isset( $bp_content_widths[ $theme_handle ] ) ) {
		$bp_content_width = $bp_content_widths[ $theme_handle ];
		$bp_handle        = 'bp-' . $theme_handle;
	}

	if ( is_rtl() ) {
		$bp_handle .= '-rtl';
	}

	$top_offset    = 150;
	$avatar_height = apply_filters( 'bp_core_avatar_full_height', $top_offset );

	if ( $avatar_height > $top_offset ) {
		$top_offset = $avatar_height;
	}

	bp_set_theme_compat_feature(
		'legacy',
		array(
			'name'     => 'cover_image',
			'settings' => array(
				'components'   => array( 'xprofile', 'groups' ),
				'width'        => $bp_content_width,
				'height'       => $top_offset + round( $avatar_height / 2 ),
				'callback'     => 'bp_legacy_theme_cover_image',
				'theme_handle' => $bp_handle,
			),
		)
	);
}

/**
 * Form element to change the active template pack.
 *
 * @deprecated BuddyBoss 1.0.0
 */
function bp_admin_setting_callback_theme_package_id() {

	_deprecated_function( __FUNCTION__, '1.0.0' );

	$options = '';

	/*
	 * Note: This should never be empty. /bp-templates/ is the
	 * canonical backup if no other packages exist. If there's an error here,
	 * something else is wrong.
	 *
	 * See BuddyPress::register_theme_packages()
	 */
	foreach ( (array) buddypress()->theme_compat->packages as $id => $theme ) {
		$options .= sprintf(
			'<option value="%1$s" %2$s>%3$s</option>',
			esc_attr( $id ),
			selected( $theme->id, bp_get_theme_package_id(), false ),
			esc_html( $theme->name )
		);
	}

	if ( $options ) : ?>
		<select name="_bp_theme_package_id" id="_bp_theme_package_id" aria-describedby="_bp_theme_package_description"><?php echo $options; ?></select>
		<p id="_bp_theme_package_description" class="description"><?php esc_html_e( 'The selected Template Pack will serve all BuddyPress templates.', 'buddyboss' ); ?></p>

	<?php else : ?>
		<p><?php esc_html_e( 'No template packages available.', 'buddyboss' ); ?></p>

	<?php endif;
}


/**
 * Load the Messages > Sent screen.
 *
 * @since BuddyPress 1.0.0
 * @deprecated BuddyBoss 1.0.0
 */
function messages_screen_sentbox() {
	_deprecated_function( __FUNCTION__, '1.0.0' );

	if ( bp_action_variables() ) {
		bp_do_404();
		return;
	}

	if ( bp_action_variables() ) {
		bp_do_404();
		return;
	}

	/**
	 * Fires right before the loading of the Messages sentbox screen template file.
	 *
	 * @since 1.0.0
	 */
	do_action( 'messages_screen_sentbox' );

	/**
	 * Filters the template to load for the Messages sentbox screen.
	 *
	 * @since 1.0.0
	 *
	 * @param string $template Path to the messages template to load.
	 */
	bp_core_load_template( apply_filters( 'messages_template_sentbox', 'members/single/home' ) );
}

/**
 * Is the current page a user's Messages Sentbox?
 *
 * Eg http://example.com/members/joe/messages/sentbox/.
 *
 * @since BuddyPress 1.1.0
 * @deprecated BuddyBoss 1.0.0
 *
 * @return bool True if the current page is a user's Messages Sentbox.
 */
function bp_is_messages_sentbox() {
	_deprecated_function( __FUNCTION__, '1.0.0' );
	return (bool) ( bp_is_user_messages() && bp_is_current_action( 'sentbox' ) );
}

/** Single Topic **************************************************************/

/**
 * Output a fancy description of the current topic, including total topics,
 * total replies, and last activity.
 *
 * @since bbPress (r2860)
 * @deprecated BuddyBoss 1.0.0
 *
 * @param array $args See {@link bbp_get_single_topic_description()}
 * @uses bbp_get_single_topic_description() Return the eventual output
 */
function bbp_single_topic_description( $args = '' ) {
	echo bbp_get_single_topic_description( $args );
}
	/**
	 * Return a fancy description of the current topic, including total topics,
	 * total replies, and last activity.
	 *
	 * @since bbPress (r2860)
	 *
	 * @param mixed $args This function supports these arguments:
	 *  - topic_id: Topic id
	 *  - before: Before the text
	 *  - after: After the text
	 *  - size: Size of the avatar
	 * @uses bbp_get_topic_id() To get the topic id
	 * @uses bbp_get_topic_voice_count() To get the topic voice count
	 * @uses bbp_get_topic_reply_count() To get the topic reply count
	 * @uses bbp_get_topic_freshness_link() To get the topic freshness link
	 * @uses bbp_get_topic_last_active_id() To get the topic last active id
	 * @uses bbp_get_reply_author_link() To get the reply author link
	 * @uses apply_filters() Calls 'bbp_get_single_topic_description' with
	 *                        the description and args
	 * @return string Filtered topic description
	 */
function bbp_get_single_topic_description( $args = '' ) {

	// Parse arguments against default values
	$r = bbp_parse_args(
		$args,
		array(
			'topic_id' => 0,
			'before'   => '<div class="bp-feedback info"><span class="bp-icon" aria-hidden="true"></span><p class="bbp-topic-description">',
			'after'    => '</p></div>',
			'size'     => 14,
		),
		'get_single_topic_description'
	);

	// Validate topic_id
	$topic_id = bbp_get_topic_id( $r['topic_id'] );

	// Unhook the 'view all' query var adder
	remove_filter( 'bbp_get_topic_permalink', 'bbp_add_view_all' );

	// Build the topic description
	$vc_int      = bbp_get_topic_voice_count( $topic_id, true );
	$voice_count = bbp_get_topic_voice_count( $topic_id, false );
	$reply_count = bbp_get_topic_replies_link( $topic_id );
	$time_since  = bbp_get_topic_freshness_link( $topic_id );

	// Singular/Plural
	$voice_count = sprintf( _n( '%s member', '%s members', $vc_int, 'buddyboss' ), $voice_count );

	// Topic has replies
	$last_reply = bbp_get_topic_last_reply_id( $topic_id );
	if ( ! empty( $last_reply ) ) {
		$last_updated_by = bbp_get_author_link(
			array(
				'post_id' => $last_reply,
				'size'    => $r['size'],
			)
		);
		$retstr          = sprintf( esc_html__( 'This discussion contains %1$s, has %2$s, and was last updated by %3$s %4$s.', 'buddyboss' ), $reply_count, $voice_count, $last_updated_by, $time_since );

		// Topic has no replies
	} elseif ( ! empty( $voice_count ) && ! empty( $reply_count ) ) {
		$retstr = sprintf( esc_html__( 'This discussion contains %1$s and has %2$s.', 'buddyboss' ), $voice_count, $reply_count );

		// Topic has no replies and no members
	} elseif ( empty( $voice_count ) && empty( $reply_count ) ) {
		$retstr = sprintf( esc_html__( 'This discussion has no replies.', 'buddyboss' ), $voice_count, $reply_count );
	}

	// Add the 'view all' filter back
	add_filter( 'bbp_get_topic_permalink', 'bbp_add_view_all' );

	// Combine the elements together
	$retstr = $r['before'] . $retstr . $r['after'];

	// Return filtered result
	return apply_filters( 'bbp_get_single_topic_description', $retstr, $r );
}

/** Single Forum **************************************************************/

/**
 * Output a fancy description of the current forum, including total topics,
 * total replies, and last activity.
 *
 * @since bbPress (r2860)
 * @deprecated BuddyBoss 1.0.0
 *
 * @param array $args Arguments passed to alter output
 * @uses bbp_get_single_forum_description() Return the eventual output
 */
function bbp_single_forum_description( $args = '' ) {
	echo bbp_get_single_forum_description( $args );
}
	/**
	 * Return a fancy description of the current forum, including total
	 * topics, total replies, and last activity.
	 *
	 * @since bbPress (r2860)
	 *
	 * @param mixed $args This function supports these arguments:
	 *  - forum_id: Forum id
	 *  - before: Before the text
	 *  - after: After the text
	 *  - size: Size of the avatar
	 * @uses bbp_get_forum_id() To get the forum id
	 * @uses bbp_get_forum_topic_count() To get the forum topic count
	 * @uses bbp_get_forum_reply_count() To get the forum reply count
	 * @uses bbp_get_forum_freshness_link() To get the forum freshness link
	 * @uses bbp_get_forum_last_active_id() To get the forum last active id
	 * @uses bbp_get_author_link() To get the author link
	 * @uses add_filter() To add the 'view all' filter back
	 * @uses apply_filters() Calls 'bbp_get_single_forum_description' with
	 *                        the description and args
	 * @return string Filtered forum description
	 */
function bbp_get_single_forum_description( $args = '' ) {

	// Parse arguments against default values
	$r = bbp_parse_args(
		$args,
		array(
			'forum_id' => 0,
			'before'   => '<div class="bp-feedback info"><span class="bp-icon" aria-hidden="true"></span><p class="bbp-forum-description">',
			'after'    => '</p></div>',
			'size'     => 14,
			'feed'     => true,
		),
		'get_single_forum_description'
	);

	// Validate forum_id
	$forum_id = bbp_get_forum_id( $r['forum_id'] );

	// Unhook the 'view all' query var adder
	remove_filter( 'bbp_get_forum_permalink', 'bbp_add_view_all' );

	// Get some forum data
	$tc_int      = bbp_get_forum_topic_count( $forum_id, false );
	$rc_int      = bbp_get_forum_reply_count( $forum_id, false );
	$topic_count = bbp_get_forum_topic_count( $forum_id );
	$reply_count = bbp_get_forum_reply_count( $forum_id );
	$last_active = bbp_get_forum_last_active_id( $forum_id );

	// Has replies
	if ( ! empty( $reply_count ) ) {
		$reply_text = sprintf( _n( '%s reply', '%s replies', $rc_int, 'buddyboss' ), $reply_count );
	}

	// Forum has active data
	if ( ! empty( $last_active ) ) {
		$topic_text      = bbp_get_forum_topics_link( $forum_id );
		$time_since      = bbp_get_forum_freshness_link( $forum_id );
		$last_updated_by = bbp_get_author_link(
			array(
				'post_id' => $last_active,
				'size'    => $r['size'],
			)
		);

		// Forum has no last active data
	} else {
		$topic_text = sprintf( _n( '%s discussion', '%s discussions', $tc_int, 'buddyboss' ), $topic_count );
	}

	// Forum has active data
	if ( ! empty( $last_active ) ) {

		if ( ! empty( $reply_count ) ) {

			if ( bbp_is_forum_category( $forum_id ) ) {
				$retstr = sprintf( esc_html__( 'This category contains %1$s and %2$s, and was last updated by %3$s %4$s.', 'buddyboss' ), $topic_text, $reply_text, $last_updated_by, $time_since );
			} else {
				$retstr = sprintf( esc_html__( 'This forum contains %1$s and %2$s, and was last updated by %3$s %4$s.', 'buddyboss' ), $topic_text, $reply_text, $last_updated_by, $time_since );
			}
		} else {

			if ( bbp_is_forum_category( $forum_id ) ) {
				$retstr = sprintf( esc_html__( 'This category contains %1$s, and was last updated by %2$s %3$s.', 'buddyboss' ), $topic_text, $last_updated_by, $time_since );
			} else {
				$retstr = sprintf( esc_html__( 'This forum contains %1$s, and was last updated by %2$s %3$s.', 'buddyboss' ), $topic_text, $last_updated_by, $time_since );
			}
		}

		// Forum has no last active data
	} else {

		if ( ! empty( $reply_count ) ) {

			if ( bbp_is_forum_category( $forum_id ) ) {
				$retstr = sprintf( esc_html__( 'This category contains %1$s and %2$s.', 'buddyboss' ), $topic_text, $reply_text );
			} else {
				$retstr = sprintf( esc_html__( 'This forum contains %1$s and %2$s.', 'buddyboss' ), $topic_text, $reply_text );
			}
		} else {

			if ( ! empty( $topic_count ) ) {

				if ( bbp_is_forum_category( $forum_id ) ) {
					$retstr = sprintf( esc_html__( 'This category contains %1$s.', 'buddyboss' ), $topic_text );
				} else {
					$retstr = sprintf( esc_html__( 'This forum contains %1$s.', 'buddyboss' ), $topic_text );
				}
			} else {
				$retstr = esc_html__( 'This forum is empty.', 'buddyboss' );
			}
		}
	}

	// Add feeds
	// $feed_links = ( !empty( $r['feed'] ) ) ? bbp_get_forum_topics_feed_link ( $forum_id ) . bbp_get_forum_replies_feed_link( $forum_id ) : '';

	// Add the 'view all' filter back
	add_filter( 'bbp_get_forum_permalink', 'bbp_add_view_all' );

	// Combine the elements together
	$retstr = $r['before'] . $retstr . $r['after'];

	// Return filtered result
	return apply_filters( 'bbp_get_single_forum_description', $retstr, $r );
}
