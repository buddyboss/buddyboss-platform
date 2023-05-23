<?php
/**
 * BuddyBoss Connections Template Functions.
 *
 * @package BuddyBoss\Connections\Template
 * @since BuddyPress 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Output the friends component slug.
 *
 * @since BuddyPress 1.5.0
 */
function bp_friends_slug() {
	echo bp_get_friends_slug();
}
	/**
	 * Return the friends component slug.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @return string
	 */
function bp_get_friends_slug() {

	/**
	 * Filters the friends component slug.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param string $value Connections component slug.
	 */
	return apply_filters( 'bp_get_friends_slug', buddypress()->friends->slug );
}

/**
 * Output the friends component root slug.
 *
 * @since BuddyPress 1.5.0
 */
function bp_friends_root_slug() {
	echo bp_get_friends_root_slug();
}
	/**
	 * Return the friends component root slug.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @return string
	 */
function bp_get_friends_root_slug() {

	/**
	 * Filters the friends component root slug.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param string $value Connections component root slug.
	 */
	return apply_filters( 'bp_get_friends_root_slug', buddypress()->friends->root_slug );
}

/**
 * Output a block of random friends.
 *
 * No longer used in BuddyPress.
 *
 * @todo Deprecate
 */
function bp_friends_random_friends() {

	if ( ! $friend_ids = wp_cache_get( 'friends_friend_ids_' . bp_displayed_user_id(), 'bp' ) ) {
		$friend_ids = BP_Friends_Friendship::get_random_friends( bp_displayed_user_id() );
		wp_cache_set( 'friends_friend_ids_' . bp_displayed_user_id(), $friend_ids, 'bp' );
	} ?>

	<div class="info-group">
		<h4><?php bp_word_or_name( __( 'My Connections', 'buddyboss' ), __( "%s's Connections", 'buddyboss' ) ); ?>  (<?php echo BP_Friends_Friendship::total_friend_count( bp_displayed_user_id() ); ?>) <span><a href="<?php echo trailingslashit( bp_displayed_user_domain() . bp_get_friends_slug() ); ?>"><?php _e( 'See All', 'buddyboss' ); ?></a></span></h4>

		<?php if ( $friend_ids ) { ?>

			<ul class="horiz-gallery">

			<?php for ( $i = 0, $count = count( $friend_ids ); $i < $count; ++$i ) { ?>

				<li>
					<a href="<?php echo bp_core_get_user_domain( $friend_ids[ $i ] ); ?>">
										<?php
										echo bp_core_fetch_avatar(
											array(
												'item_id' => $friend_ids[ $i ],
												'type'    => 'thumb',
											)
										);
										?>
																							</a>
					<h5><?php echo bp_core_get_userlink( $friend_ids[ $i ] ); ?></h5>
				</li>

			<?php } ?>

			</ul>

		<?php } else { ?>

			<div id="message" class="info">
				<p><?php bp_word_or_name( __( "You haven't made any connections yet.", 'buddyboss' ), __( "%s hasn't created any connections yet.", 'buddyboss' ) ); ?></p>
			</div>

		<?php } ?>

		<div class="clear"></div>
	</div>

	<?php
}

/**
 * Pull up a group of random members, and display some profile data about them.
 *
 * This function is no longer used by BuddyPress core.
 *
 * @todo Deprecate
 *
 * @param int $total_members The number of members to retrieve.
 */
function bp_friends_random_members( $total_members = 5 ) {

	if ( ! $user_ids = wp_cache_get( 'friends_random_users', 'bp' ) ) {
		$user_ids = BP_Core_User::get_users( 'random', $total_members );
		wp_cache_set( 'friends_random_users', $user_ids, 'bp' );
	}

	?>

	<?php if ( $user_ids['users'] ) { ?>

		<ul class="item-list" id="random-members-list">

		<?php for ( $i = 0, $count = count( $user_ids['users'] ); $i < $count; ++$i ) { ?>

			<li>
				<a href="<?php echo bp_core_get_user_domain( $user_ids['users'][ $i ]->id ); ?>">
									<?php
									echo bp_core_fetch_avatar(
										array(
											'item_id' => $user_ids['users'][ $i ]->id,
											'type'    => 'thumb',
										)
									);
									?>
																									</a>
				<h5><?php echo bp_core_get_userlink( $user_ids['users'][ $i ]->id ); ?></h5>

				<?php if ( bp_is_active( 'xprofile' ) ) { ?>

					<?php $random_data = xprofile_get_random_profile_data( $user_ids['users'][ $i ]->id, true ); ?>

					<div class="profile-data">
						<p class="field-name"><?php echo $random_data[0]->name; ?></p>

						<?php echo $random_data[0]->value; ?>

					</div>

				<?php } ?>

				<div class="action">

					<?php if ( bp_is_active( 'friends' ) ) { ?>

						<?php bp_add_friend_button( $user_ids['users'][ $i ]->id ); ?>

					<?php } ?>

				</div>
			</li>

		<?php } ?>

		</ul>

	<?php } else { ?>

		<div id="message" class="info">
			<p><?php _e( "There aren't enough site members to show a random sample just yet.", 'buddyboss' ); ?></p>
		</div>

	<?php } ?>
	<?php
}

/**
 * Display a Connections search form.
 *
 * No longer used in BuddyPress.
 *
 * @todo Deprecate
 */
function bp_friend_search_form() {

	$action = bp_displayed_user_domain() . bp_get_friends_slug() . '/my-friends/search/';
	$label  = __( 'Filter Connections', 'buddyboss' );
	?>

		<form action="<?php echo $action; ?>" id="friend-search-form" method="post">

			<label for="friend-search-box" id="friend-search-label"><?php echo $label; ?></label>
			<input type="search" name="friend-search-box" id="friend-search-box" value="<?php echo $value; ?>"<?php echo $disabled; ?> />

			<?php wp_nonce_field( 'friends_search', '_wpnonce_friend_search' ); ?>

			<input type="hidden" name="initiator" id="initiator" value="<?php echo esc_attr( bp_displayed_user_id() ); ?>" />

		</form>

	<?php
}

/**
 * Output the "Connect" button in the member loop.
 *
 * @since BuddyPress 1.2.6
 */
function bp_member_add_friend_button() {
	bp_add_friend_button( bp_get_member_user_id() );
}
add_action( 'bp_directory_members_actions', 'bp_member_add_friend_button' );

/**
 * Output the friend count for the current member in the loop.
 *
 * @since BuddyPress 1.2.0
 */
function bp_member_total_friend_count() {
	echo bp_get_member_total_friend_count();
}
	/**
	 * Return the friend count for the current member in the loop.
	 *
	 * Return value is a string of the form "x friends".
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @return string A string of the form "x friends".
	 * @todo clean this up with _n()
	 */
function bp_get_member_total_friend_count() {
	global $members_template;

	if ( 1 == (int) $members_template->member->total_friend_count ) {

		/**
		 * Filters text used to denote total friend count.
		 *
		 * @since BuddyPress 1.2.0
		 *
		 * @param string $value String of the form "x friends".
		 * @param int    $value Total friend count for current member in the loop.
		 */
		return apply_filters( 'bp_get_member_total_friend_count', sprintf( __( '%d connection', 'buddyboss' ), (int) $members_template->member->total_friend_count ) );
	} else {

		/** This filter is documented in bp-friends/bp-friends-template.php */
		return apply_filters( 'bp_get_member_total_friend_count', sprintf( __( '%d connections', 'buddyboss' ), (int) $members_template->member->total_friend_count ) );
	}
}

/**
 * Output the ID of the current user in the friend request loop.
 *
 * @since BuddyPress 1.2.6
 *
 * @see bp_get_potential_friend_id() for a description of arguments.
 *
 * @param int $user_id See {@link bp_get_potential_friend_id()}.
 */
function bp_potential_friend_id( $user_id = 0 ) {
	echo bp_get_potential_friend_id( $user_id );
}
	/**
	 * Return the ID of current user in the friend request loop.
	 *
	 * @since BuddyPress 1.2.6
	 *
	 * @global object $friends_template
	 *
	 * @param int $user_id Optional. If provided, the function will simply
	 *                     return this value.
	 * @return int ID of potential friend.
	 */
function bp_get_potential_friend_id( $user_id = 0 ) {
	global $friends_template;

	if ( empty( $user_id ) && isset( $friends_template->friendship->friend ) ) {
		$user_id = $friends_template->friendship->friend->id;
	} elseif ( empty( $user_id ) && ! isset( $friends_template->friendship->friend ) ) {
		$user_id = bp_displayed_user_id();
	}

	/**
	 * Filters the ID of current user in the friend request loop.
	 *
	 * @since BuddyPress 1.2.10
	 *
	 * @param int $user_id ID of current user in the friend request loop.
	 */
	return apply_filters( 'bp_get_potential_friend_id', (int) $user_id );
}

/**
 * Check whether a given user is a friend of the logged-in user.
 *
 * Returns - 'is_friend', 'not_friends', 'pending'.
 *
 * @since BuddyPress 1.2.6
 *
 * @param int $user_id ID of the potential friend. Default: the value of
 *                     {@link bp_get_potential_friend_id()}.
 * @return string 'is_friend', 'not_friends', or 'pending'.
 */
function bp_is_friend( $user_id = 0 ) {

	if ( ! is_user_logged_in() ) {
		return false;
	}

	if ( empty( $user_id ) ) {
		$user_id = bp_get_potential_friend_id( $user_id );
	}

	if ( bp_loggedin_user_id() == $user_id ) {
		return false;
	}

	/**
	 * Filters the status of friendship between logged in user and given user.
	 *
	 * @since BuddyPress 1.2.10
	 *
	 * @param string $value String status of friendship. Possible values are 'is_friend', 'not_friends', 'pending'.
	 */
	return apply_filters( 'bp_is_friend', friends_check_friendship_status( bp_loggedin_user_id(), $user_id ), $user_id );
}

/**
 * Output the Connect button.
 *
 * @since BuddyPress 1.0.0
 *
 * @see bp_get_add_friend_button() for information on arguments.
 *
 * @param int      $potential_friend_id See {@link bp_get_add_friend_button()}.
 * @param int|bool $friend_status       See {@link bp_get_add_friend_button()}.
 * @param array    $button_args         See {@link BP_Button class}.
 */
function bp_add_friend_button( $potential_friend_id = 0, $friend_status = false, $button_args = array() ) {
	echo bp_get_add_friend_button( $potential_friend_id, $friend_status, $button_args );
}

/**
 * Create the Connect button.
 *
 * @since BuddyPress 1.1.0
 *
 * @param int   $potential_friend_id ID of the user to whom the button applies. Default: value of {@link bp_get_potential_friend_id()}.
 * @param bool  $friend_status       Not currently used.
 * @param array $button_args         See {@link BP_Button class}.
 * @return false|string HTML for the Connect button.
 */
function bp_get_add_friend_button( $potential_friend_id = 0, $friend_status = false, $button_args = array() ) {

	if ( empty( $potential_friend_id ) ) {
		$potential_friend_id = bp_get_potential_friend_id( $potential_friend_id );
	}

	$is_friend = bp_is_friend( $potential_friend_id );

	if ( empty( $is_friend ) ) {
		return false;
	}

	$button_args = bp_parse_args( $button_args, get_class_vars( 'BP_Button' ) );

	switch ( $is_friend ) {
		case 'pending':
			$button = bp_parse_args(
				array(
					'id'                  => 'pending',
					'component'           => 'friends',
					'must_be_logged_in'   => true,
					'block_self'          => true,
					'potential_friend_id' => $potential_friend_id,
					'wrapper_class'       => 'friendship-button pending_friend',
					'wrapper_id'          => 'friendship-button-' . $potential_friend_id,
					'link_href'           => wp_nonce_url( bp_loggedin_user_domain() . bp_get_friends_slug() . '/requests/cancel/' . $potential_friend_id . '/', 'friends_withdraw_friendship' ),
					'link_text'           => esc_html__( 'Request Sent', 'buddyboss' ),
					'link_id'             => 'friend-' . $potential_friend_id,
					'link_rel'            => 'remove',
					'link_class'          => 'friendship-button pending_friend requested',
					'button_attr'         => array(
						'hover_type'           => $button_args['button_attr']['hover_type'] ?? false,
						'data-title'           => esc_html__( 'Cancel Request', 'buddyboss' ),
						'data-title-displayed' => esc_html__( 'Request Sent', 'buddyboss' ),
					),
				),
				$button_args
			);
			break;

		case 'awaiting_response':
			$button = bp_parse_args(
				array(
					'id'                  => 'awaiting_response',
					'component'           => 'friends',
					'must_be_logged_in'   => true,
					'block_self'          => true,
					'potential_friend_id' => $potential_friend_id,
					'wrapper_class'       => 'friendship-button awaiting_response_friend',
					'wrapper_id'          => 'friendship-button-' . $potential_friend_id,
					'link_href'           => bp_loggedin_user_domain() . bp_get_friends_slug() . '/requests/',
					'link_text'           => esc_html__( 'Pending Request', 'buddyboss' ),
					'link_id'             => 'friend-' . $potential_friend_id,
					'link_rel'            => 'remove',
					'link_class'          => 'friendship-button awaiting_response_friend requested',
					'button_attr'         => array(
						'hover_type'           => $button_args['button_attr']['hover_type'] ?? false,
						'data-title'           => esc_html__( 'Review Request', 'buddyboss' ),
						'data-title-displayed' => esc_html__( 'Pending Request', 'buddyboss' ),
					),
				),
				$button_args
			);
			break;

		case 'is_friend':
			$button = bp_parse_args(
				array(
					'id'                  => 'is_friend',
					'component'           => 'friends',
					'potential_friend_id' => $potential_friend_id,
					'must_be_logged_in'   => true,
					'block_self'          => false,
					'wrapper_class'       => 'friendship-button is_friend',
					'wrapper_id'          => 'friendship-button-' . $potential_friend_id,
					'link_href'           => wp_nonce_url( bp_loggedin_user_domain() . bp_get_friends_slug() . '/remove-friend/' . $potential_friend_id . '/', 'friends_remove_friend' ),
					'link_text'           => esc_html__( 'Connected', 'buddyboss' ),
					'link_id'             => 'friend-' . $potential_friend_id,
					'link_rel'            => 'remove',
					'link_class'          => 'friendship-button is_friend remove',
					'button_attr'         => array(
						'data-bb-user-name'    => bp_core_get_user_displayname( $potential_friend_id ),
						'data-bb-user-link'    => bp_core_get_user_domain( $potential_friend_id ),
						'hover_type'           => $button_args['button_attr']['hover_type'] ?? false,
						'data-title'           => esc_html__( 'Remove Connection', 'buddyboss' ),
						'data-title-displayed' => esc_html__( 'Connected', 'buddyboss' ),
					),
				),
				$button_args
			);
			break;

		default:
			$button = bp_parse_args(
				array(
					'id'                  => 'not_friends',
					'component'           => 'friends',
					'potential_friend_id' => $potential_friend_id,
					'must_be_logged_in'   => true,
					'block_self'          => true,
					'wrapper_class'       => 'friendship-button not_friends',
					'wrapper_id'          => 'friendship-button-' . $potential_friend_id,
					'link_href'           => wp_nonce_url( bp_loggedin_user_domain() . bp_get_friends_slug() . '/add-friend/' . $potential_friend_id . '/', 'friends_add_friend' ),
					'link_text'           => esc_html__( 'Connect', 'buddyboss' ),
					'link_id'             => 'friend-' . $potential_friend_id,
					'link_rel'            => 'add',
					'link_class'          => 'friendship-button not_friends add',
					'button_attr'         => array(
						'hover_type'           => $button_args['button_attr']['hover_type'] ?? false,
						'data-title'           => '',
						'data-title-displayed' => '',
					),
				),
				$button_args
			);
			break;
	}

	/**
	 * Filters the HTML for the add friend button.
	 *
	 * @since BuddyPress 1.1.0
	 *
	 * @param string $button HTML markup for add friend button.
	 */
	return bp_get_button( apply_filters( 'bp_get_add_friend_button', $button ) );
}

/**
 * Get a comma-separated list of IDs of a user's friends.
 *
 * @since BuddyPress 1.2.0
 *
 * @param int $user_id Optional. Default: the displayed user's ID, or the
 *                     logged-in user's ID.
 * @return false|string A comma-separated list of friend IDs if any are found,
 *                      otherwise false.
 */
function bp_get_friend_ids( $user_id = 0 ) {

	if ( empty( $user_id ) ) {
		$user_id = ( bp_displayed_user_id() ) ? bp_displayed_user_id() : bp_loggedin_user_id();
	}

	$friend_ids = friends_get_friend_user_ids( $user_id );

	if ( empty( $friend_ids ) ) {
		return false;
	}

	return implode( ',', friends_get_friend_user_ids( $user_id ) );
}

/**
 * Get a user's connection requests.
 *
 * Note that we return a 0 if no pending requests are found. This is necessary
 * because of the structure of the $include parameter in bp_has_members().
 *
 * @since BuddyPress 1.2.0
 *
 * @param int $user_id ID of the user whose requests are being retrieved.
 *                     Defaults to displayed user.
 * @return array|int An array of user IDs if found, or a 0 if none are found.
 */
function bp_get_friendship_requests( $user_id = 0 ) {
	if ( ! $user_id ) {
		$user_id = bp_displayed_user_id();
	}

	if ( ! $user_id ) {
		return 0;
	}

	$requests = friends_get_friendship_request_user_ids( $user_id );

	if ( ! empty( $requests ) ) {
		$requests = implode( ',', (array) $requests );
	} else {
		$requests = 0;
	}

	/**
	 * Filters the total pending connection requests for a user.
	 *
	 * @since BuddyPress 1.2.0
	 * @since BuddyPress 2.6.0 Added the `$user_id` parameter.
	 *
	 * @param array|int $requests An array of user IDs if found, or a 0 if none are found.
	 * @param int       $user_id  ID of the queried user.
	 */
	return apply_filters( 'bp_get_friendship_requests', $requests, $user_id );
}

/**
 * Get a user's mutual connections with logged in user.
 *
 * Note that we return a 0 if no mutual connections are found. This is necessary
 * because of the structure of the $include parameter in bp_has_members().
 *
 * @since BuddyBoss 1.0.0
 *
 * @param int $user_id ID of the user whose mutual connections are being retrieved.
 *                     Defaults to displayed user.
 * @return array|int An array of user IDs if found, or a 0 if none are found.
 */
function bp_get_mutual_friendships( $user_id = 0 ) {

	if ( ! bp_loggedin_user_id() ) {
		return 0;
	}

	if ( ! $user_id ) {
		$user_id = bp_displayed_user_id();
	}

	if ( ! $user_id ) {
		return 0;
	}

	// get displayed user's connections
	$displayed_user_friends = friends_get_friend_user_ids( $user_id );

	// get logged in user's connections
	$logged_in_user_friends = friends_get_friend_user_ids( bp_loggedin_user_id() );

	$mutual_friends = array_intersect( $logged_in_user_friends, $displayed_user_friends );

	if ( ! empty( $mutual_friends ) ) {
		$mutual_friends = implode( ',', (array) $mutual_friends );
	} else {
		$mutual_friends = 0;
	}

	/**
	 * Filters the total mutual connections for a user.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param array|int $requests An array of user IDs if found, or a 0 if none are found.
	 * @param int       $user_id  ID of the queried user.
	 */
	return apply_filters( 'bp_get_mutual_friendships', $mutual_friends, $user_id );
}

/**
 * Output the ID of the friendship between the logged-in user and the current user in the loop.
 *
 * @since BuddyPress 1.2.0
 */
function bp_friend_friendship_id() {
	echo bp_get_friend_friendship_id();
}
	/**
	 * Return the ID of the friendship between the logged-in user and the current user in the loop.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @return int ID of the friendship.
	 */
function bp_get_friend_friendship_id() {
	global $members_template;

	if ( ! $friendship_id = wp_cache_get( 'friendship_id_' . $members_template->member->id . '_' . bp_loggedin_user_id(), 'bp' ) ) {
		$friendship_id = friends_get_friendship_id( $members_template->member->id, bp_loggedin_user_id() );
		wp_cache_set( 'friendship_id_' . $members_template->member->id . '_' . bp_loggedin_user_id(), $friendship_id, 'bp' );
	}

	/**
	 * Filters the ID of the friendship between the logged in user and the current user in the loop.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param int $friendship_id ID of the friendship.
	 */
	return apply_filters( 'bp_get_friend_friendship_id', $friendship_id );
}

/**
 * Output the URL for accepting the current friendship request in the loop.
 *
 * @since BuddyPress 1.0.0
 */
function bp_friend_accept_request_link() {
	echo bp_get_friend_accept_request_link();
}
	/**
	 * Return the URL for accepting the current friendship request in the loop.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @return string accept-friendship URL.
	 */
function bp_get_friend_accept_request_link() {
	global $members_template;

	if ( ! $friendship_id = wp_cache_get( 'friendship_id_' . $members_template->member->id . '_' . bp_loggedin_user_id(), 'bp' ) ) {
		$friendship_id = friends_get_friendship_id( $members_template->member->id, bp_loggedin_user_id() );
		wp_cache_set( 'friendship_id_' . $members_template->member->id . '_' . bp_loggedin_user_id(), $friendship_id, 'bp' );
	}

	/**
	 * Filters the URL for accepting the current friendship request in the loop.
	 *
	 * @since BuddyPress 1.0.0
	 * @since BuddyPress 2.6.0 Added the `$friendship_id` parameter.
	 *
	 * @param string $value         Accept-friendship URL.
	 * @param int    $friendship_id ID of the friendship.
	 */
	return apply_filters( 'bp_get_friend_accept_request_link', wp_nonce_url( trailingslashit( bp_loggedin_user_domain() . bp_get_friends_slug() . '/requests/accept/' . $friendship_id ), 'friends_accept_friendship' ), $friendship_id );
}

/**
 * Output the URL for rejecting the current friendship request in the loop.
 *
 * @since BuddyPress 1.0.0
 */
function bp_friend_reject_request_link() {
	echo bp_get_friend_reject_request_link();
}
	/**
	 * Return the URL for rejecting the current friendship request in the loop.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @return string reject-friendship URL.
	 */
function bp_get_friend_reject_request_link() {
	global $members_template;

	if ( ! $friendship_id = wp_cache_get( 'friendship_id_' . $members_template->member->id . '_' . bp_loggedin_user_id(), 'bp' ) ) {
		$friendship_id = friends_get_friendship_id( $members_template->member->id, bp_loggedin_user_id() );
		wp_cache_set( 'friendship_id_' . $members_template->member->id . '_' . bp_loggedin_user_id(), $friendship_id, 'bp' );
	}

	/**
	 * Filters the URL for rejecting the current friendship request in the loop.
	 *
	 * @since BuddyPress 1.0.0
	 * @since BuddyPress 2.6.0 Added the `$friendship_id` parameter.
	 *
	 * @param string $value         Reject-friendship URL.
	 * @param int    $friendship_id ID of the friendship.
	 */
	return apply_filters( 'bp_get_friend_reject_request_link', wp_nonce_url( trailingslashit( bp_loggedin_user_domain() . bp_get_friends_slug() . '/requests/reject/' . $friendship_id ), 'friends_reject_friendship' ), $friendship_id );
}

/**
 * Output the total friend count for a given user.
 *
 * @since BuddyPress 1.2.0
 *
 * @param int $user_id See {@link friends_get_total_friend_count()}.
 */
function bp_total_friend_count( $user_id = 0 ) {
	echo bp_get_total_friend_count( $user_id );
}
	/**
	 * Return the total friend count for a given user.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param int $user_id See {@link friends_get_total_friend_count()}.
	 * @return int Total friend count.
	 */
function bp_get_total_friend_count( $user_id = 0 ) {

	/**
	 * Filters the total friend count for a given user.
	 *
	 * @since BuddyPress 1.2.0
	 * @since BuddyPress 2.6.0 Added the `$user_id` parameter.
	 *
	 * @param int $value   Total friend count.
	 * @param int $user_id ID of the queried user.
	 */
	return apply_filters( 'bp_get_total_friend_count', friends_get_total_friend_count( $user_id ), $user_id );
}

/**
 * Output the total friendship request count for a given user.
 *
 * @since BuddyPress 1.2.0
 *
 * @see bp_friend_get_total_requests_count() for description of arguments.
 *
 * @param int $user_id See {@link bp_friend_get_total_requests_count().
 */
function bp_friend_total_requests_count( $user_id = 0 ) {
	echo bp_friend_get_total_requests_count( $user_id );
}
	/**
	 * Return the total friendship request count for a given user.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param int $user_id ID of the user whose requests are being counted.
	 *                     Default: ID of the logged-in user.
	 * @return int Connection count.
	 */
function bp_friend_get_total_requests_count( $user_id = 0 ) {
	if ( empty( $user_id ) ) {
		$user_id = bp_loggedin_user_id();
	}

	/**
	 * Filters the total friendship request count for a given user.
	 *
	 * @since BuddyPress 1.2.0
	 * @since BuddyPress 2.6.0 Added the `$user_id` parameter.
	 *
	 * @param int $value   Friendship request count.
	 * @param int $user_id ID of the queried user.
	 */
	return apply_filters( 'bp_friend_get_total_requests_count', count( BP_Friends_Friendship::get_friend_user_ids( $user_id, true ) ), $user_id );
}

/** Stats **********************************************************************/

/**
 * Display the number of friends in user's profile.
 *
 * @since BuddyPress 2.0.0
 *
 * @param array|string $args before|after|user_id.
 */
function bp_friends_profile_stats( $args = '' ) {
	echo bp_friends_get_profile_stats( $args );
}
add_action( 'bp_members_admin_user_stats', 'bp_friends_profile_stats', 7, 1 );

/**
 * Return the number of friends in user's profile.
 *
 * @since BuddyPress 2.0.0
 *
 * @param array|string $args before|after|user_id.
 * @return string HTML for stats output.
 */
function bp_friends_get_profile_stats( $args = '' ) {

	// Parse the args.
	$r = bp_parse_args(
		$args,
		array(
			'before'  => '<li class="bp-friends-profile-stats">',
			'after'   => '</li>',
			'user_id' => bp_displayed_user_id(),
			'friends' => 0,
			'output'  => '',
		),
		'friends_get_profile_stats'
	);

	// Allow completely overloaded output.
	if ( empty( $r['output'] ) ) {

		// Only proceed if a user ID was passed.
		if ( ! empty( $r['user_id'] ) ) {

			// Get the user's friends.
			if ( empty( $r['friends'] ) ) {
				$r['friends'] = absint( friends_get_total_friend_count( $r['user_id'] ) );
			}

			// If friends exist, show some formatted output.
			$r['output'] = $r['before'] . sprintf( _n( '%s connection', '%s connections', $r['friends'], 'buddyboss' ), '<strong>' . $r['friends'] . '</strong>' ) . $r['after'];
		}
	}

	/**
	 * Filters the number of friends in user's profile.
	 *
	 * @since BuddyPress 2.0.0
	 *
	 * @param string $value Formatted string displaying total friends count.
	 * @param array  $r     Array of arguments for string formatting and output.
	 */
	return apply_filters( 'bp_friends_get_profile_stats', $r['output'], $r );
}
