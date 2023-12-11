<?php
/**
 * BuddyBoss Reaction Functions.
 *
 * Functions for the Reaction functionality.
 *
 * @package BuddyBoss\Activity
 * @since BuddyPress [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

add_action( 'wp_ajax_bb_update_reaction', 'bb_update_activity_reaction_ajax_callback' );
add_action( 'wp_ajax_bb_remove_reaction', 'bb_remove_activity_reaction_ajax_callback' );
add_action( 'wp_ajax_bb_get_reactions', 'bb_get_activity_reaction_ajax_callback' );
add_action( 'wp_ajax_bb_user_reactions', 'bb_get_user_reactions_ajax_callback' );

add_action( 'bp_activity_deleted_activities', 'bb_activity_remove_activity_post_reactions', 10, 1 );
add_action( 'bp_activity_action_delete_activity', 'bb_activity_remove_activity_post_reactions', 10, 1 );

/**
 * Add user reaction for an activity post.
 *
 * @param int     $activity_id    The activity ID.
 * @param int     $reaction_id    The reaction ID.
 * @param string  $activity_type  The activity type.
 * @param integer $user_id        Current logged in user ID.
 *
 * @return mixed
 */
function bp_activity_add_user_reaction( $activity_id, $reaction_id = 0, $activity_type = 'activity', $user_id = 0 ) {

	if ( empty( $activity_id ) ) {
		return false;
	}

	if ( empty( $reaction_id ) && bb_is_reaction_emotions_enabled() ) {
		$reaction_id = bb_load_reaction()->bb_reactions_get_first_emotion_reaction_id();
	} elseif( empty( $reaction_id ) ) {
		$reaction_id = bb_load_reaction()->bb_reactions_get_like_reaction_id();
	}

	if ( empty( $user_id ) ) {
		$user_id = bp_loggedin_user_id();
	}

	if ( empty( $reaction_id ) || empty( $user_id ) ) {
		return false;
	}

	$reaction = bb_load_reaction()->bb_add_user_item_reaction(
		array(
			'reaction_id' => $reaction_id,
			'item_id'     => $activity_id,
			'user_id'     => $user_id,
			'item_type'   => $activity_type,
			'error_type'  => 'wp_error',
		)
	);

	return $reaction;
}

/**
 * Remove user reaction for an activity post.
 *
 * @param int     $activity_id   The activity ID.
 * @param string  $activity_type The activity type.
 * @param integer $user_id       Current logged in user ID.
 *
 * @return mixed
 */
function bp_activity_remove_user_reaction( $activity_id, $activity_type = 'activity', $user_id = 0 ) {

	if ( empty( $activity_id ) ) {
		return false;
	}

	if ( empty( $user_id ) ) {
		$user_id = bp_loggedin_user_id();
	}

	$status = bb_load_reaction()->bb_remove_user_item_reactions(
		array(
			'item_id'    => $activity_id,
			'user_id'    => $user_id,
			'item_type'  => $activity_type,
			'error_type' => 'wp_error',
		)
	);

	return $status;
}

/**
 * Get user reacted activity ids.
 *
 * @param integer $user_id       User Id.
 * @param string  $activity_type Activity type.
 *
 * @return array
 */
function bb_activity_get_user_reacted_item_ids( $user_id = 0, $activity_type = 'activity' ) {

	if ( empty( $user_id ) ) {
		$user_id = bp_displayed_user_id() ? bp_displayed_user_id() : bp_loggedin_user_id();
	}

	$reaction_data = bb_load_reaction()->bb_get_user_reactions(
		array(
			'user_id'   => $user_id,
			'item_type' => $activity_type,
			'fields'    => 'item_id',
		)
	);

	$item_ids = array();
	if ( ! empty( $reaction_data['reactions'] ) ) {
		$item_ids = $reaction_data['reactions'];
	}

	return apply_filters( 'bb_activity_get_user_reacted_item_ids', $item_ids, $user_id, $activity_type );
}

/**
 * Get total count of reactions for a user.
 *
 * @param integer $user_id The user ID.
 * @param string  $activity_type The activity type.
 *
 * @return int
 */
function bb_activity_total_reactions_count_for_user( $user_id = 0, $activity_type = '' ) {

	if ( empty( $user_id ) ) {
		$user_id = bp_displayed_user_id() ? bp_displayed_user_id() : bp_loggedin_user_id();
	}

	$reaction_count = bb_load_reaction()->bb_get_user_reactions_count(
		array(
			'user_id'   => $user_id,
			'item_type' => $activity_type,
		)
	);

	return apply_filters( 'bb_activity_total_reactions_for_user', $reaction_count, $user_id );
}

/**
 * Delete all reactions for an activity.
 *
 * @param array|int $activity_ids ID of the activity.
 * @return void
 */
function bb_activity_remove_activity_post_reactions( $activity_ids ) {

	if ( empty( $activity_ids ) ) {
		return;
	}

	bb_load_reaction()->bb_remove_user_item_reactions(
		array(
			'item_id' => $activity_ids,
			'user_id' => 0,
		)
	);
}

/**
 * Get reaction emoticons for activity post.
 *
 * @return string
 */
function bb_get_activity_post_emotions_popup() {
	$output = '';

	if (
		bb_is_reaction_emotions_enabled() &&
		bb_is_reaction_activity_posts_enabled()
	) {
		$output = bb_activity_prepare_web_emotions();
	}

	return apply_filters( 'bb_get_activity_post_emotions_popup', $output );
}

/**
 * Get reaction emoticons for activity post comment.
 *
 * @return string
 */
function bb_get_activity_post_comment_emotions_popup() {
	$output = '';

	if (
		bb_is_reaction_emotions_enabled() &&
		bb_is_reaction_activity_comments_enabled()
	) {
		$output = bb_activity_prepare_web_emotions();
	}

	return apply_filters( 'bb_get_activity_post_comment_emotions_popup', $output );
}

/**
 * Prepare a reaction emoticons list for web to show on hover.
 *
 * @return string
 */
function bb_activity_prepare_web_emotions() {

	$output       = '<div class="ac-emotions_list">';
	$all_emotions = bb_load_reaction()->bb_get_reactions( 'emotions' );

	foreach ( $all_emotions as $emotion ) {

		if ( 'bb-icons' === $emotion['type'] ) {
			$icon = sprintf(
				'<i class="bb-icon-%s" style="font-weight:200;color:%s;"></i>',
				esc_attr( $emotion['icon'] ),
				esc_attr( $emotion['icon_color'] )
			);
		} else {
			$icon = sprintf(
				'<img src="%s" class="%s" alt="%s" />',
				esc_url( $emotion['icon_path'] ),
				esc_attr( $emotion['type'] ),
				esc_attr( $emotion['icon_text'] )
			);
		}

		$output .= sprintf(
			'<div class="ac-emotion_item" data-reaction-id="%s">
				<a href="#" class="ac-emotion_btn" data-bp-tooltip-pos="up" data-bp-tooltip="%s">
				%s
				</a>
			</div>',
			$emotion['id'],
			$emotion['icon_text'],
			$icon,
		);
	}

	$output .= '</div>';

	return apply_filters( 'bb_activity_prepare_web_emotions', $output );
}

/**
 * Get most reactions for activity.
 *
 * @param integer $item_id   ID of the item.
 * @param string  $item_type Type of the item.
 * @param integer $no_of_reactions Number of reactions to display.
 *
 * @return array|bool
 */
function bb_get_activity_most_reactions( $item_id = 0, $item_type = 'activity', $no_of_reactions = 3 ) {

	if ( empty( $item_id ) ) {
		return false;
	}

	$reaction_data = bb_load_reaction()->bb_get_reaction_reactions_count(
		array(
			'item_type' => $item_type,
			'item_id'   => $item_id,
		)
	);

	if ( empty( $reaction_data ) ) {
		return false;
	}

	usort(
		$reaction_data,
		function( $a, $b ) {
			return $b->total <=> $a->total;
		}
	);

	$reactions = array();
	foreach ( $reaction_data as $reaction ) {

		if ( 0 === $no_of_reactions ) {
			break;
		}

		$reaction_post = get_post( $reaction->reaction_id );
		if ( empty( $reaction_post->post_content ) ) {
			continue;
		}

		$reaction_meta = get_post_meta( $reaction->reaction_id );

		// If emotion is not active then skip this reaction.
		if (
			isset( $reaction_meta['is_emotion'] ) &&
			(
				empty( $reaction_meta['is_emotion'][0] ) ||
				empty( $reaction_meta['is_emotion_active'][0] )
			)
		) {
			continue;
		}

		$reaction_content          = maybe_unserialize( $reaction_post->post_content );
		$reaction_content['id']    = $reaction_post->ID;
		$reaction_content['total'] = $reaction->total;
		$reactions[]               = $reaction_content;

		$no_of_reactions--;
	}

	return apply_filters( 'bb_get_activity_most_reactions', $reactions );
}

/**
 * Get activity post reaction button html.
 *
 * @param int     $item_id     ID of the Activity/Comment.
 * @param string  $item_type   Type of Activity.
 * @param int     $reaction_id ID of the reaction.
 * @param boolean $has_reacted User has reaction or not.
 *
 * @return mixed
 */
function bb_get_activity_post_reaction_button_html( $item_id, $item_type = 'activity', $reaction_id = 0, $has_reacted = false ) {

	$reaction_button_class = '';

	$like_reaction_id = bb_load_reaction()->bb_reactions_get_like_reaction_id();
	if ( empty( $reaction_id ) || $reaction_id === $like_reaction_id ) {
		$reaction_id           = $like_reaction_id;
		$reaction_button_class = $has_reacted ? ' has-like' : '';
	}

	if ( $has_reacted ) {
		$reaction_button_class .= ' has-emotion';
	}

	$reaction_post = get_post( $reaction_id );
	$reaction_data = ! empty( $reaction_post->post_content ) ? maybe_unserialize( $reaction_post->post_content ) : array();
	$prepared_icon = bb_activity_prepare_emotion_icon_with_text( $reaction_data );

	if ( $has_reacted ) {
		$reaction_link = ( 'activity' === $item_type ) ? bp_get_activity_unfavorite_link( $item_id ) : bb_get_activity_comment_unfavorite_link( $item_id );
	} else {
		$reaction_link = ( 'activity' === $item_type ) ? bp_get_activity_favorite_link( $item_id ) : bb_get_activity_comment_favorite_link( $item_id );
	}

	$reaction_button = sprintf(
		'<a href="%1$s" class="button bp-like-button bp-secondary-action %5$s" aria-pressed="false">
			<span class="bp-screen-reader-text">%2$s</span>
			%3$s
			<span class="like-count reactions_item" style="color:%4$s">%2$s</span>
		</a>',
		$reaction_link,
		esc_html( $prepared_icon['icon_text'] ),
		$prepared_icon['icon_html'],
		! empty( $reaction_data['text_color'] ) ? esc_attr( $reaction_data['text_color'] ) : '#385DFF',
		! empty( $reaction_button_class ) ? esc_attr( $reaction_button_class ) : 'fav',
	);

	return $reaction_button;
}

/**
 * Get user reactions list for activity post.
 *
 * @param int    $activity_id Activity/Comment ID.
 * @param string $item_type   Type of Activity.
 *
 * @return string HTML markup
 */
function bb_get_activity_post_user_reactions_html( $activity_id, $item_type = 'activity' ) {
	$output = '';

	if ( empty( $activity_id ) ) {
		return $output;
	}

	$reaction_count_class = 'activity-reactions_count';
	if ( 'activity_comment' === $item_type ) {
		$reaction_count_class = 'comment-reactions_count';
	}

	$most_reactions = bb_get_activity_most_reactions( $activity_id, $item_type );
	if ( ! empty( $most_reactions ) ) {
		$output .= '<div class="activity-state-reactions">';

		foreach ( $most_reactions as $reaction ) {
			$icon   = bb_activity_prepare_emotion_icon( $reaction );
			$output .= sprintf(
				'<div class="reactions_item">
				%s
				</div>',
				$icon
			);
		}

		$name_and_count = bb_activity_reaction_names_and_count( $activity_id, $item_type );
		if ( ! empty( $name_and_count ) ) {
			$output .= sprintf(
				'<div class="%1$s">%2$s</div>',
				$reaction_count_class,
				$name_and_count
			);
		}

		$output .= '</div>';
	}

	return apply_filters( 'bb_get_activity_post_user_reactions_html', $output );
}

/**
 * Ajax callback for Reaction.
 *
 * @return mixed
 */
function bb_update_activity_reaction_ajax_callback() {

	if ( ! bp_is_post_request() ) {
		wp_send_json_error();
	}

	// Nonce check!
	if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'bp_nouveau_activity' ) ) {
		wp_send_json_error(
			__( 'Nonce verification failed', 'buddyboss' )
		);
	}

	if ( ! empty( $_POST['reaction_id'] ) ) {
		$reaction_id = sanitize_text_field( $_POST['reaction_id'] );
	} elseif( empty( $_POST['reaction_id'] ) && bb_is_reaction_emotions_enabled() ) {
		$reaction_id = bb_load_reaction()->bb_reactions_get_first_emotion_reaction_id();
	} else {
		$reaction_id = bb_load_reaction()->bb_reactions_get_like_reaction_id();
	}

	$item_id   = sanitize_text_field( $_POST['item_id'] );
	$item_type = sanitize_text_field( $_POST['item_type'] );

	$reaction = bp_activity_add_user_reaction(
		$item_id,
		$reaction_id,
		$item_type
	);

	if ( is_wp_error( $reaction ) || empty( $reaction ) ) {
		wp_send_json_error( $reaction->get_error_message() );
	}

	$response = array(
		'reaction_counts' => bb_get_activity_post_user_reactions_html( $item_id, $item_type ),
		'reaction_button' => bb_get_activity_post_reaction_button_html( $item_id, $item_type, $reaction_id, true ),
	);

	// Add likes/reacted tab when first time user react or like.
	$current_user_fav_count = (int) bb_activity_total_reactions_count_for_user( bp_loggedin_user_id() );
	if ( 1 === $current_user_fav_count ) {
		$directory_tab = sprintf(
			'<li id="activity-favorites" data-bp-scope="favorites" data-bp-object="activity">
				<a href="%1$s">%2$s</a>
			</li>',
			esc_url( bp_loggedin_user_domain() . bp_get_activity_slug() . '/favorites/' ),
			bb_is_reaction_emotions_enabled() ? esc_html__( 'Reacted to', 'buddyboss' ) : esc_html__( 'Likes', 'buddyboss' )
		);

		$response['directory_tab'] = $directory_tab;
	} else {
		$response['user_fav_count'] = $current_user_fav_count;
	}

	wp_send_json_success( $response );
}

/**
 * Ajax callback for Reaction.
 *
 * @return mixed
 */
function bb_remove_activity_reaction_ajax_callback() {

	if ( ! bp_is_post_request() ) {
		wp_send_json_error();
	}

	// Nonce check!
	if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'bp_nouveau_activity' ) ) {
		wp_send_json_error(
			__( 'Nonce verification failed', 'buddyboss' )
		);
	}

	if ( empty( $_POST['item_id'] ) ) {
		wp_send_json_error(
			array(
				'no_item_id' => esc_html__( 'No item id', 'buddyboss' ),
			)
		);
	}

	$item_id   = sanitize_text_field( $_POST['item_id'] );
	$item_type = sanitize_text_field( $_POST['item_type'] );

	$status = bp_activity_remove_user_reaction( $item_id, $item_type );

	if ( is_wp_error( $status ) || empty( $status ) ) {
		wp_send_json_error( $status->get_error_message() );
	}

	$response = array(
		'reaction_counts' => bb_get_activity_post_user_reactions_html( $item_id, $item_type ),
		'reaction_button' => bb_get_activity_post_reaction_button_html( $item_id, $item_type ),
	);

	// If no likes/reacted activity found then remove tab and show no activity message.
	$current_user_fav_count = (int) bb_activity_total_reactions_count_for_user( bp_loggedin_user_id() );
	if ( 0 === $current_user_fav_count ) {
		$no_activity_found = sprintf(
			'<aside class="bp-feedback bp-messages info">
				<span class="bp-icon" aria-hidden="true"></span>
				<p>%s</p>
			</aside>',
			esc_html__( 'Sorry, there was no activity found.', 'buddyboss' )
		);

		$response['no_activity_found'] = $no_activity_found;
	}

	wp_send_json_success( $response );
}

/**
 * Retrieves the user reactions for a specific activity.
 *
 * @param array $args The arguments for retrieving the user reactions.
 *                    - reaction_id (int) The ID of the reaction (default: 0).
 *                    - item_id (int) The ID of the item (default: 0).
 *                    - user_id (int) The ID of the user (default: 0).
 *                    - paged (int) The page number (default: 1).
 *
 * @return array The user reactions.
 */
function bb_activity_get_reacted_users_data( $args ) {

	$args = bp_parse_args(
		$args,
		array(
			'reaction_id' => 0,
			'item_id'     => 0,
			'user_id'     => 0,
			'paged'       => 1,
			'count_total' => true,
		),
		'bb_activity_get_reacted_users_data_args'
	);

	$reaction_data  = bb_load_reaction()->bb_get_user_reactions( $args );
	$user_reactions = array();

	if ( ! empty( $reaction_data['reactions'] ) ) {
		foreach ( $reaction_data['reactions'] as $reaction ) {
			$user_data     = get_userdata( $reaction->user_id );
			$reaction_meta = get_post_field( 'post_content', $reaction->reaction_id );
			$type          = function_exists( 'bp_get_member_type_object' ) ? bp_get_member_type( $reaction->user_id ) : '';
			$type_obj      = function_exists( 'bp_get_member_type_object' ) ? bp_get_member_type_object( $type ) : '';
			$color_data    = function_exists( 'bb_get_member_type_label_colors' ) ? bb_get_member_type_label_colors( $type ) : '';

			$member_type = esc_html__( 'Member', 'buddyboss' );
			if ( ! empty( $type_obj ) ) {
				$member_type = $type_obj->labels['singular_name'];
			}

			//$member_type = wp_kses_post( bp_get_user_member_type( bp_get_member_user_id() ) );

			$user_reactions[] = array(
				'id'          => $reaction->user_id,
				'name'        => $user_data->display_name,
				'member_type' => array(
					'label' => ! empty( $member_type ) ? $member_type : $type,
					'color' => array(
						'background' => ! empty( $color_data['background-color'] ) ? $color_data['background-color'] : '',
						'text'       => ! empty( $color_data['color'] ) ? $color_data['color'] : '',
					)
				),
				'avatar'      => get_avatar_url( $reaction->user_id ),
				'profile_url' => bbp_get_user_profile_url( $reaction->user_id ),
				'reaction'    => maybe_unserialize( $reaction_meta ),
			);
		}

		$reaction_data['reactions'] = $user_reactions;
	}

	return apply_filters( 'bb_activity_get_reacted_users_data', $reaction_data, $args );
}

/**
 * Get reactions data for an activity.
 *
 * @return mixed
 */
function bb_get_activity_reaction_ajax_callback() {

	if ( ! bp_is_post_request() ) {
		wp_send_json_error();
	}

	// Nonce check!
	if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'bp_nouveau_activity' ) ) {
		wp_send_json_error(
			__( 'Nonce verification failed', 'buddyboss' )
		);
	}

	if ( empty( $_POST['item_id'] ) ) {
		wp_send_json_error(
			array(
				'no_item_id' => esc_html__( 'No item id', 'buddyboss' ),
			)
		);
	}

	$item_id       = sanitize_text_field( $_POST['item_id'] );
	$item_type     = sanitize_text_field( $_POST['item_type'] );
	$reaction_data = bb_get_activity_most_reactions( $item_id, $item_type, 7 );

	foreach ( $reaction_data as $key => $reaction ) {
		$users_data = bb_activity_get_reacted_users_data(
			array(
				'item_id'     => $item_id,
				'item_type'   => $item_type,
				'reaction_id' => $reaction['id'],
				'per_page'    => 20,
			)
		);

		$reaction_data[ $key ]['users']       = $users_data['reactions'];
		$reaction_data[ $key ]['paged']       = 1;
		$reaction_data[ $key ]['total_pages'] = ceil( $reaction_data[ $key ]['total'] / 20 );
		$reaction_data[ $key ]['total_count'] = bb_format_reaction_count( $reaction_data[ $key ]['total'] );
	}

	if ( count( $reaction_data ) >= 2 ) {

		$users_data = bb_activity_get_reacted_users_data(
			array(
				'item_id'   => $item_id,
				'item_type' => $item_type,
				'per_page'  => 20,
			)
		);

		array_unshift(
			$reaction_data,
			array(
				'name'        => 'All',
				'type'        => 'all',
				'icon'        => '',
				'icon_text'   => esc_html__( 'All', 'buddyboss' ),
				'users'       => $users_data['reactions'],
				'paged'       => 1,
				'total_pages' => ceil( $users_data['total'] / 20 ),
				'total_count' => $users_data['total'],
			)
		);
	}

	wp_send_json_success(
		array(
			'item_id'       => $item_id,
			'reaction_mode' => bb_get_reaction_mode(),
			'reactions'     => (object) $reaction_data,
		)
	);
}

/**
 * Get reaction count for activity.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param int    $activity_id Post Id.
 * @param string $activity_type Activity type.
 *
 * @return int|string
 */
function bb_activity_reaction_names_and_count( $activity_id, $activity_type = 'activity' ) {

	if ( ! bp_is_activity_like_active() ) {
		return 0;
	}

	$reaction_data = bb_load_reaction()->bb_get_user_reactions(
		array(
			'item_id'     => $activity_id,
			'item_type'   => $activity_type,
			'fields'      => 'user_id',
			'per_page'    => 99,
			'count_total' => true,
		)
	);

	if ( empty( $reaction_data['total'] ) || 100 <= $reaction_data['total'] ) {
		return bb_format_reaction_count( $reaction_data['total'] );
	}

	$reacted_users  = ! empty( $reaction_data['reactions'] ) ? $reaction_data['reactions'] : array();
	$reaction_count = ! empty( $reaction_data['total'] ) ? absint( $reaction_data['total'] ) : 0;

	$is_current_user_reacted = false;
	$current_logged_user_id  = bp_loggedin_user_id();
	$current_key             = array_search( $current_logged_user_id, $reacted_users );

	if ( ! empty( $current_logged_user_id ) && false !== $current_key ) {
		$is_current_user_reacted = true;
		if ( $reaction_count > 1 ) {
			unset( $reacted_users[ $current_key ] );
		}
	}

	$friend_users   = array();
	$follower_users = array();

	// Get friends and followers.
	if (
		function_exists( 'friends_get_friend_user_ids' ) &&
		$current_logged_user_id &&
		(
			(
				$is_current_user_reacted &&
				2 <= $reaction_count
			) ||
			(
				! $is_current_user_reacted &&
				1 <= $reaction_count
			)
		)
	) {
		$friends      = friends_get_friend_user_ids( $current_logged_user_id );
		$friend_users = array_intersect( $friends, $reacted_users );

		if ( count( $friend_users ) < $reaction_count && function_exists( 'bp_get_followers' ) ) {
			$followers = bp_get_followers(
				array(
					'user_id'  => $current_logged_user_id,
					'per_page' => -1,
				)
			);

			$follower_users = array_intersect( $followers, $reacted_users );
		}
	}

	$return_str = '';
	if ( 1 === $reaction_count ) {

		if ( $is_current_user_reacted ) {
			return esc_html__( 'You', 'buddyboss' );
		}

		$user_id      = bb_get_reacted_person( $reacted_users, $friend_users, $follower_users );
		$display_name = bp_core_get_user_displayname( $user_id );
		$display_name = ! empty( $display_name ) ? $display_name : esc_html__( 'Unknown', 'buddyboss' );
		return $display_name;
	} elseif ( 2 === $reaction_count ) {
		$user_id    = bb_get_reacted_person( $reacted_users, $friend_users, $follower_users );
		$first_name = bp_core_get_user_displayname( $user_id ) ?? esc_html__( 'Unknown', 'buddyboss' );

		// If current user reacted and next related user is also reacted.
		if ( $is_current_user_reacted ) {
			return sprintf( esc_html__( 'You and %s', 'buddyboss' ), $first_name );
		}

		// If current user not reacted and next 2 related user reacted.
		$user_id     = bb_get_reacted_person( $reacted_users, $friend_users, $follower_users );
		$second_name = bp_core_get_user_displayname( $user_id ) ?? esc_html__( 'Unknown', 'buddyboss' );
		return sprintf( esc_html__( '%1$s and %2$s', 'buddyboss' ), $first_name, $second_name );
	} elseif ( 3 <= $reaction_count && 99 >= $reaction_count ) {
		$reaction_count -= 2;
		$user_id         = bb_get_reacted_person( $reacted_users, $friend_users, $follower_users );
		$first_name      = bp_core_get_user_displayname( $user_id ) ?? esc_html__( 'Unknown', 'buddyboss' );
		$user_id         = bb_get_reacted_person( $reacted_users, $friend_users, $follower_users );
		$second_name     = bp_core_get_user_displayname( $user_id ) ?? esc_html__( 'Unknown', 'buddyboss' );

		$return_str = $is_current_user_reacted
		? sprintf( esc_html__( 'You, %s and ', 'buddyboss' ), $first_name )
		: sprintf( esc_html__( '%1$s, %2$s and ', 'buddyboss' ), $first_name, $second_name );

		$return_str .= $reaction_count > 1
		? sprintf( esc_html__( '%d others', 'buddyboss' ), bb_format_reaction_count( $reaction_count ) )
		: sprintf( esc_html__( '%d other', 'buddyboss' ), bb_format_reaction_count( $reaction_count ) );
	} else {
		$return_str = bb_format_reaction_count( $reaction_count );
	}

	return $return_str;
}

/**
 * Get the formatted reaction count.
 *
 * @param int $count The reaction count.
 * @return int|string The reaction count
 */
function bb_format_reaction_count( $count ) {
	if ( $count >= 1000000 ) {
		return round( $count / 1000000, 1 ) . 'M';
	} elseif ( $count >= 1000 ) {
		return round( $count / 1000, 1 ) . 'K';
	} else {
		return $count;
	}
}

/**
 * Get the reacted user id.
 *
 * @param array $reacted_users The reacted user IDs.
 * @param array $friends       The friends user IDs.
 * @param array $followers     The friends user IDs.
 *
 * @return int The user id.
 */
function bb_get_reacted_person( &$reacted_users, &$friends, &$followers ) {
	if ( ! empty( $friends ) ) {
		return array_pop( $friends );
	} elseif ( ! empty( $followers ) ) {
		return array_pop( $followers );
	} else {
		return array_pop( $reacted_users );
	}
}

/**
 * Check whether the current item is in the user's favorites.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param int    $item_id   The ID of activity/activity comment.
 * @param string $item_type The item type.
 * @param int    $user_id   The user ID.
 *
 * @return bool
 */
function bb_activity_is_item_favorite( $item_id, $item_type = 'activity', $user_id = 0 ) {

	if ( empty( $item_id ) ) {
		return false;
	}

	if ( empty( $user_id ) ) {
		$user_id = bp_loggedin_user_id();
	}

	return (bool) bb_load_reaction()->bb_get_user_reactions_count(
		array(
			'item_type' => $item_type,
			'item_id'   => $item_id,
			'user_id'   => $user_id,
		)
	);
}

/**
 * Get paginated user reactions data from ajax.
 *
 * @return void
 */
function bb_get_user_reactions_ajax_callback() {

	if ( ! bp_is_post_request() ) {
		wp_send_json_error();
	}

	// Nonce check!
	if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'bp_nouveau_activity' ) ) {
		wp_send_json_error(
			__( 'Nonce verification failed', 'buddyboss' )
		);
	}

	if ( empty( $_POST['item_id'] ) ) {
		wp_send_json_error(
			array(
				'no_item_id' => esc_html__( 'No item id', 'buddyboss' ),
			)
		);
	}

	$item_id     = sanitize_text_field( $_POST['item_id'] );
	$item_type   = sanitize_text_field( $_POST['item_type'] );
	$reaction_id = sanitize_text_field( $_POST['reaction_id'] );
	$paged       = sanitize_text_field( $_POST['paged'] );

	$users_data = bb_activity_get_reacted_users_data(
		array(
			'item_id'     => $item_id,
			'item_type'   => $item_type,
			'reaction_id' => $reaction_id,
			'paged'       => $paged,
			'per_page'    => 20,
		)
	);

	$user_list = '';
	foreach ( $users_data['reactions'] as $user ) {

		$icon_html   = bb_activity_prepare_emotion_icon( $user['reaction'] );
		$member_type = sprintf( '<div class="activity-state_user__role">%s</div>', $user['member_type'] );

		$user_list .= sprintf(
			'<li class="activity-state_user">
				<div class="activity-state_user__avatar">
					<a href="%1$s">
						<img class="avatar" src="%2$s" alt="%3$s">
						<div class="activity-state_user__reaction">%4$s</div>
					</a>
				</div>
				<div class="activity-state_user__name">
					<a href="%1$s">%3$s</a>
				</div>
				%5$s
			</li>',
			$user['profile_url'],
			$user['avatar'],
			$user['name'],
			$icon_html,
			$member_type
		);
	}

	wp_send_json_success(
		array(
			'user_list' => $user_list,
		)
	);

}

/**
 * Get user reaction by item.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param int    $item_id   The ID of activity/activity comment.
 * @param string $item_type The item type.
 * @param int    $user_id   The user ID.
 *
 * @return mixed
 */
function bb_activity_get_user_reaction_by_item( $item_id, $item_type = 'activity', $user_id = 0 ) {

	if ( empty( $item_id ) ) {
		return false;
	}

	if ( empty( $user_id ) ) {
		$user_id = bp_loggedin_user_id();
	}

	$user_reaction = bb_load_reaction()->bb_get_user_reactions(
		array(
			'item_type' => $item_type,
			'item_id'   => $item_id,
			'user_id'   => $user_id,
			'fields'    => 'reaction_id',
		)
	);

	if ( empty( $user_reaction['reactions'] ) ) {
		return false;
	}

	$reaction_id   = current( $user_reaction['reactions'] );
	$reaction      = get_post_field( 'post_content', $reaction_id );
	$reaction_data = array(
		'reaction_id' => $reaction_id,
		'reaction'    => maybe_unserialize( $reaction ),
	);

	return $reaction_data;
}

/**
 * Prepare emotion icon with text.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param int|array|WP_Post $id_or_post_or_reaction Accepts a post ID, Emotion array, WP_Post object.
 *
 * @return array
 */
function bb_activity_prepare_emotion_icon_with_text( $id_or_post_or_reaction ) {
	$reaction_data = array();

	// Process the identifier.
	if ( is_array( $id_or_post_or_reaction ) ) {
		$reaction_data = $id_or_post_or_reaction;
	} elseif ( is_object( $id_or_post_or_reaction ) ) {
		$reaction_data = ! empty( $id_or_post_or_reaction->post_content ) ? maybe_unserialize( $id_or_post_or_reaction->post_content ) : array();
	} elseif ( is_numeric( $id_or_post_or_reaction ) ) {
		$id_or_post_or_reaction = get_post( absint( $id_or_post_or_reaction ) );
		$reaction_data          = ! empty( $id_or_post_or_reaction->post_content ) ? maybe_unserialize( $id_or_post_or_reaction->post_content ) : array();
	}

	$icon_html = bb_activity_prepare_emotion_icon( $reaction_data );

	if ( ! empty( $reaction_data['type'] ) || ! empty( $reaction_data['icon_path'] ) ) {
		$icon_text = sanitize_text_field( $reaction_data['icon_text'] );
	} else {
		$icon_text = __( 'Like', 'buddyboss' );
		$icon_html = '';
	}

	return array(
		'icon_text' => $icon_text,
		'icon_html' => $icon_html,
	);
}

/**
 * Prepare emotion icon.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param int|array|WP_Post $id_or_post_or_reaction Accepts a post ID, Emotion array, WP_Post object.
 *
 * @return string
 */
function bb_activity_prepare_emotion_icon( $id_or_post_or_reaction ) {
	$reaction_data = array();

	// Process the identifier.
	if ( is_array( $id_or_post_or_reaction ) ) {
		$reaction_data = $id_or_post_or_reaction;
	} elseif ( is_object( $id_or_post_or_reaction ) ) {
		$reaction_data = ! empty( $id_or_post_or_reaction->post_content ) ? maybe_unserialize( $id_or_post_or_reaction->post_content ) : array();
	} elseif ( is_numeric( $id_or_post_or_reaction ) ) {
		$id_or_post_or_reaction = get_post( absint( $id_or_post_or_reaction ) );
		$reaction_data          = ! empty( $id_or_post_or_reaction->post_content ) ? maybe_unserialize( $id_or_post_or_reaction->post_content ) : array();
	}

	if ( ! empty( $reaction_data['type'] ) && 'bb-icons' === $reaction_data['type'] ) {
		$icon_html = sprintf(
			'<i class="bb-icon-%s" style="font-weight:200;color:%s;"></i>',
			esc_attr( $reaction_data['icon'] ),
			esc_attr( $reaction_data['icon_color'] ),
		);
	} elseif ( ! empty( $reaction_data['icon_path'] ) ) {
		$icon_html = sprintf(
			'<img src="%s" class="%s" alt="%s"/>',
			esc_url( $reaction_data['icon_path'] ),
			esc_attr( $reaction_data['type'] ),
			esc_attr( $reaction_data['icon_text'] )
		);
	} else {
		$icon_html = '<i class="bb-icon-thumbs-up" style="font-weight:200;color:#385DFF;"></i>';
	}

	return $icon_html;
}
