<?php
/**
 * Activity: RSS feed actions
 *
 * @package BuddyBoss\Activity\Actions
 * @since BuddyPress 3.0.0
 */

/**
 * Load the sitewide activity feed.
 *
 * @since BuddyPress 1.0.0
 *
 * @return bool False on failure.
 */
function bp_activity_action_sitewide_feed() {
	$bp = buddypress();

	if ( ! bp_is_activity_component() || ! bp_is_current_action( 'feed' ) || bp_is_user() || ! empty( $bp->groups->current_group ) ) {
		return false;
	}

	// Setup the feed.
	buddypress()->activity->feed = new BP_Activity_Feed(
		array(
			'id'            => 'sitewide',

			/* translators: Sitewide activity RSS title - "[Site Name] | Site Wide Activity" */
			'title'         => sprintf( __( '%s | Site-Wide Activity', 'buddyboss' ), bp_get_site_name() ),

			'link'          => bp_get_activity_directory_permalink(),
			'description'   => __( 'Activity feed for the entire site.', 'buddyboss' ),
			'activity_args' => 'display_comments=threaded',
		)
	);
}
add_action( 'bp_actions', 'bp_activity_action_sitewide_feed' );

/**
 * Load a user's personal activity feed.
 *
 * @since BuddyPress 1.0.0
 *
 * @return bool False on failure.
 */
function bp_activity_action_personal_feed() {
	if ( ! bp_is_user_activity() || ! bp_is_current_action( 'feed' ) ) {
		return false;
	}

	// Setup the feed.
	buddypress()->activity->feed = new BP_Activity_Feed(
		array(
			'id'            => 'personal',

			/* translators: Personal activity RSS title - "[Site Name] | [User Display Name] | Activity" */
			'title'         => sprintf( __( '%1$s | %2$s | Activity', 'buddyboss' ), bp_get_site_name(), bp_get_displayed_user_fullname() ),

			'link'          => trailingslashit( bp_displayed_user_domain() . bp_get_activity_slug() ),
			/* translators: Activity feed for [User Display Name]. */
			'description'   => sprintf( __( 'Activity feed for %s.', 'buddyboss' ), bp_get_displayed_user_fullname() ),
			'activity_args' => 'user_id=' . bp_displayed_user_id(),
		)
	);
}
add_action( 'bp_actions', 'bp_activity_action_personal_feed' );

/**
 * Load a user's friends' activity feed.
 *
 * @since BuddyPress 1.0.0
 *
 * @return bool False on failure.
 */
function bp_activity_action_friends_feed() {
	if ( ! bp_is_active( 'friends' ) || ! bp_is_user_activity() || ! bp_is_current_action( bp_get_friends_slug() ) || ! bp_is_action_variable( 'feed', 0 ) ) {
		return false;
	}

	// Setup the feed.
	buddypress()->activity->feed = new BP_Activity_Feed(
		array(
			'id'            => 'friends',

			/* translators: Connections activity RSS title - "[Site Name] | [User Display Name] | Connections Activity" */
			'title'         => sprintf( __( '%1$s | %2$s | Connections Activity', 'buddyboss' ), bp_get_site_name(), bp_get_displayed_user_fullname() ),

			'link'          => trailingslashit( bp_displayed_user_domain() . bp_get_activity_slug() . '/' . bp_get_friends_slug() ),
			/* translators: Activity feed for [User Display Name]'s connections. */
			'description'   => sprintf( __( "Activity feed for %s's connections.", 'buddyboss' ), bp_get_displayed_user_fullname() ),
			'activity_args' => 'scope=friends',
		)
	);
}
add_action( 'bp_actions', 'bp_activity_action_friends_feed' );

/**
 * Load the activity feed for a user's groups.
 *
 * @since BuddyPress 1.2.0
 *
 * @return bool False on failure.
 */
function bp_activity_action_my_groups_feed() {
	if ( ! bp_is_active( 'groups' ) || ! bp_is_user_activity() || ! bp_is_current_action( bp_get_groups_slug() ) || ! bp_is_action_variable( 'feed', 0 ) ) {
		return false;
	}

	// Get displayed user's group IDs.
	$groups    = groups_get_user_groups();
	$group_ids = implode( ',', $groups['groups'] );

	// Setup the feed.
	buddypress()->activity->feed = new BP_Activity_Feed(
		array(
			'id'            => 'mygroups',

			/* translators: Member groups activity RSS title - "[Site Name] | [User Display Name] | Groups Activity" */
			'title'         => sprintf( __( '%1$s | %2$s | Group Activity', 'buddyboss' ), bp_get_site_name(), bp_get_displayed_user_fullname() ),

			'link'          => trailingslashit( bp_displayed_user_domain() . bp_get_activity_slug() . '/' . bp_get_groups_slug() ),

			/* translators: Public group activity feed of which [User Display Name] is a member. */
			'description'   => sprintf( __( 'Public group activity feed of which %s is a member.', 'buddyboss' ), bp_get_displayed_user_fullname() ),
			'activity_args' => array(
				'object'           => buddypress()->groups->id,
				'primary_id'       => $group_ids,
				'display_comments' => 'threaded',
			),
		)
	);
}
add_action( 'bp_actions', 'bp_activity_action_my_groups_feed' );

/**
 * Load a user's @mentions feed.
 *
 * @since BuddyPress 1.2.0
 *
 * @return bool False on failure.
 */
function bp_activity_action_mentions_feed() {
	if ( ! bp_activity_do_mentions() ) {
		return false;
	}

	if ( ! bp_is_user_activity() || ! bp_is_current_action( 'mentions' ) || ! bp_is_action_variable( 'feed', 0 ) ) {
		return false;
	}

	// Setup the feed.
	buddypress()->activity->feed = new BP_Activity_Feed(
		array(
			'id'            => 'mentions',

			/* translators: User mentions activity RSS title - "[Site Name] | [User Display Name] | Mentions" */
			'title'         => sprintf( __( '%1$s | %2$s | Mentions', 'buddyboss' ), bp_get_site_name(), bp_get_displayed_user_fullname() ),

			'link'          => bp_displayed_user_domain() . bp_get_activity_slug() . '/mentions/',

			/* translators: Activity feed mentioning [User Display Name]. */
			'description'   => sprintf( __( 'Activity feed mentioning %s.', 'buddyboss' ), bp_get_displayed_user_fullname() ),
			'activity_args' => array(
				'search_terms' => '@' . bp_core_get_username( bp_displayed_user_id() ),
			),
		)
	);
}
add_action( 'bp_actions', 'bp_activity_action_mentions_feed' );

/**
 * Load a user's favorites feed.
 *
 * @since BuddyPress 1.2.0
 *
 * @return bool False on failure.
 */
function bp_activity_action_favorites_feed() {
	if ( ! bp_is_user_activity() || ! bp_is_current_action( 'favorites' ) || ! bp_is_action_variable( 'feed', 0 ) ) {
		return false;
	}

	// Get displayed user's favorite activity IDs.
	$favs    = bp_activity_get_user_favorites( bp_displayed_user_id() );
	$fav_ids = implode( ',', (array) $favs );

	// Setup the feed.
	buddypress()->activity->feed = new BP_Activity_Feed(
		array(
			'id'            => 'favorites',

			/* translators: User activity saved RSS title - "[Site Name] | [User Display Name] | Favorites" */
			'title'         => sprintf( __( '%1$s | %2$s | Favorites', 'buddyboss' ), bp_get_site_name(), bp_get_displayed_user_fullname() ),

			'link'          => bp_displayed_user_domain() . bp_get_activity_slug() . '/favorites/',

			/* translators: Activity feed of [User Display Name]'s saved posts. */
			'description'   => sprintf( __( "Activity feed of %s's saved posts.", 'buddyboss' ), bp_get_displayed_user_fullname() ),
			'activity_args' => 'include=' . $fav_ids,
		)
	);
}
add_action( 'bp_actions', 'bp_activity_action_favorites_feed' );
