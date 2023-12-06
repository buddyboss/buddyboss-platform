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

add_filter( 'bp_nouveau_get_activity_entry_buttons', 'bb_nouveau_update_activity_post_reaction_button', 10, 2 );
add_action( 'bp_activity_action_delete_activity', 'bb_activity_remove_activity_post_reactions', 10, 1 );
add_filter( 'bb_get_user_reactions_join_sql', 'bb_update_user_reactions_join_sql', 10, 1 );

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

	if ( empty( $reaction_id ) ) {
		$reaction_id = bb_load_reaction()->bb_reactions_get_like_reaction_id();
	}

	if ( empty( $user_id ) ) {
		$user_id = bp_loggedin_user_id();
	}

	if ( empty( $reaction_id ) || empty( $user_id ) ) {
		return;
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
 *
 * @return int
 */
function bb_activity_total_reactions_count_for_user( $user_id = 0 ) {

	if ( empty( $user_id ) ) {
		$user_id = bp_displayed_user_id() ? bp_displayed_user_id() : bp_loggedin_user_id();
	}

	$reaction_count = bb_load_reaction()->bb_get_user_reactions_count(
		array(
			'user_id' => $user_id,
		)
	);

	return apply_filters( 'bb_activity_total_reactions_for_user', $reaction_count, $user_id );
}

/**
 * Delete all reactions for an activity.
 *
 * @param int $activity_id ID of the activity.
 * @return void
 */
function bb_activity_remove_activity_post_reactions( $activity_id ) {

	if ( empty( $activity_id ) ) {
		return;
	}

	bb_load_reaction()->bb_remove_user_item_reactions(
		array(
			'item_type' => 'activity',
			'item_id'   => $activity_id,
			'user_id'   => 0,
		)
	);
}

/**
 * Function will replace the like/unlike button text with reaction text.
 *
 * @param array $buttons     Array of buttons.
 * @param int   $activity_id Activity ID.
 *
 * @return mixed
 *
 * @since BuddyBoss 1.7.8
 */
function bb_nouveau_update_activity_post_reaction_button( $buttons, $activity_id ) {

	if ( empty( $buttons['activity_favorite'] ) ) {
		return $buttons;
	}

	$user_reaction = bb_load_reaction()->bb_get_user_reactions(
		array(
			'item_type' => 'activity',
			'item_id'   => $activity_id,
			'user_id'   => bp_loggedin_user_id(),
		)
	);

	if ( empty( $user_reaction ) ) {
		return $buttons;
	}

	$user_reaction = current( $user_reaction['reactions'] );
	if ( empty( $user_reaction ) ) {
		return $buttons;
	}

	$reaction_meta = get_post_meta( $user_reaction->reaction_id );
	// If reaction is not active then avoid button changes.
	if (
		isset( $reaction_meta['is_emotion'] ) &&
		(
			empty( $reaction_meta['is_emotion'][0] ) ||
			empty( $reaction_meta['is_emotion_active'][0] )
		)
	) {
		return $buttons;
	}

	$reaction      = get_post( $user_reaction->reaction_id );
	$reaction_data = maybe_unserialize( $reaction->post_content );

	if ( empty( $reaction_data ) ) {
		return $buttons;
	}

	$icon_text = $reaction_data['icon_text'];
	$icon      = '';
	if ( 'bb-icons' === $reaction_data['type'] ) {
		$icon = sprintf(
			'<i class="bb-icon-thumbs-up" style="font-weight:200;color:%s;"></i>',
			esc_attr( $reaction_data['icon_color'] ),
		);
	} elseif ( ! empty( $reaction_data['icon_path'] ) ) {
		$icon = sprintf(
			'<img src="%s" alt="%s" style="width:20px"/>',
			esc_url( $reaction_data['icon_path'] ),
			esc_attr( $reaction_data['icon_text'] )
		);
	} else {
		$icon_text = sprintf( 'Like', 'buddyboss' );
	}

	$text_color = ! empty( $reaction_data['text_color'] ) ? $reaction_data['text_color'] : '#385DFF';

	$buttons['activity_favorite']['link_text'] = sprintf(
		'<span class="bp-screen-reader-text">%1$s</span>
		%2$s
		<span class="like-count reactions_item" style="color:%3$s">%1$s</span>',
		esc_html( $icon_text ),
		$icon,
		esc_attr( $text_color )
	);

	$class_name = 'has-emotion';
	if ( empty( $reaction_data['type'] ) ) {
		$class_name .= ' has-like';
	}

	$buttons['activity_favorite']['button_attr']['class'] = 'button bp-secondary-action ' . $class_name;

	return $buttons;
}

/**
 * Get reaction emoticons for activity post.
 *
 * @return HTML markup
 */
function bb_get_activity_post_emotions_popup() {

	$output = '';

	if (
		function_exists( 'bb_pro_get_reactions' ) &&
		bb_is_reaction_activity_posts_enabled() &&
		'emotions' == bb_get_reaction_mode()
	) {
		$output      .= '<div class="ac-emotions_list">';
		$all_emotions = bb_pro_get_reactions( 'emotions' );

		foreach ( $all_emotions as $key => $emotion ) {
			$icon = '';

			if ( 'bb-icons' === $emotion['type'] ) {
				$icon = sprintf(
					'<i class="bb-icon-%s" style="font-weight:200;color:%s;"></i>',
					esc_attr( $emotion['icon'] ),
					esc_attr( $emotion['icon_color'] )
				);
			} else {
				$icon = sprintf(
					'<img src="%s" alt="%s" />',
					esc_url( $emotion['icon_path'] ),
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
	}

	return apply_filters( 'bb_get_activity_post_emotions_popup', $output );
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
		return;
	}

	$reaction_data = bb_load_reaction()->bb_get_reaction_reactions_count(
		array(
			'item_type' => $item_type,
			'item_id'   => $item_id,
		)
	);

	if ( empty( $reaction_data ) ) {
		return;
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
 * @param int     $reaction_id      ID of the reaction.
 * @param boolean $has_reacted User has reaction or not.
 *
 * @return mixed
 */
function bb_get_activity_post_reaction_button_html( $reaction_id = 0, $has_reacted = false ) {

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
	$icon_html     = '';

	if ( ! empty( $reaction_data['type'] ) && 'bb-icons' === $reaction_data['type'] ) {
		$icon_text = $reaction_data['icon_text'];
		$icon_html = sprintf(
			'<i class="bb-icon-%s" style="font-weight:200;color:%s;"></i>',
			esc_attr( $reaction_data['icon'] ),
			esc_attr( $reaction_data['icon_color'] ),
		);

	} elseif ( ! empty( $reaction_data['icon_path'] ) ) {
		$icon_text = $reaction_data['icon_text'];
		$icon_html = sprintf(
			'<img src="%s" alt="%s" style="width:20px"/>',
			esc_url( $reaction_data['icon_path'] ),
			esc_attr( $reaction_data['icon_text'] )
		);
	} else {
		$icon_text = sprintf( 'Like', 'buddyboss' );
	}

	$reaction_button = sprintf(
		'<a href="%1$s" class="button bp-secondary-action %5$s" aria-pressed="false">
			<span class="bp-screen-reader-text">%2$s</span>
			%3$s
			<span class="like-count reactions_item" style="color:%4$s">%2$s</span>
		</a>',
		$has_reacted ? bp_get_activity_unfavorite_link() : bp_get_activity_favorite_link(),
		esc_html( $icon_text ),
		$icon_html,
		! empty( $reaction_data['text_color'] ) ? esc_attr( $reaction_data['text_color'] ) : '#385DFF',
		! empty( $reaction_button_class ) ? esc_attr( $reaction_button_class ) : 'fav',
	);

	return $reaction_button;
}

/**
 * Get user reactions list for activity post.
 *
 * @param int $activity_id Activity Id.
 *
 * @return HTML markup
 */
function bb_get_activity_post_user_reactions_html( $activity_id ) {

	if ( empty( $activity_id ) ) {
		return;
	}

	$most_reactions = bb_get_activity_most_reactions( $activity_id );
	$output         = '';

	if ( ! empty( $most_reactions ) ) {
		$output .= '<div class="activity-state-reactions">';

		foreach ( $most_reactions as $reaction ) {
			$icon = '';

			if ( 'bb-icons' === $reaction['type'] ) {
				$icon = sprintf(
					'<i class="bb-icon-%s" style="font-weight:200;color:%s;"></i>',
					esc_attr( $reaction['icon'] ),
					esc_attr( $reaction['icon_color'] ),
				);
			} elseif ( ! empty( $reaction['icon_path'] ) ) {
				$icon = sprintf(
					'<img src="%s" alt="%s" />',
					esc_url( $reaction['icon_path'] ),
					esc_attr( $reaction['icon_text'] )
				);
			} else {
				$icon = sprintf(
					'<i class="bb-icon-thumbs-up" style="font-weight:200;color:#385DFF;"></i>',
				);
			}

			$output .= sprintf(
				'<div class="reactions_item">
				%s
				</div>',
				$icon
			);
		}

		$name_and_count = bb_activity_reaction_names_and_count( $activity_id );
		if ( ! empty( $name_and_count ) ) {
			$output .= sprintf(
				'<div class="activity-reactions_count">%s</div>',
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

	if ( empty( $_POST['reaction_id'] ) ) {
		$reaction_id = bb_load_reaction()->bb_reactions_get_like_reaction_id();
	} else {
		$reaction_id = sanitize_text_field( $_POST['reaction_id'] );
	}

	if ( empty( $reaction_id ) ) {
		wp_send_json_error(
			array(
				'no_reaction_id' => esc_html__( 'No reaction id', 'buddyboss' ),
			)
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
	$reaction  = bp_activity_add_user_reaction(
		$item_id,
		$reaction_id,
		$item_type
	);

	if ( is_wp_error( $reaction ) ) {
		wp_send_json_error( $reaction->get_error_message() );
	}

	wp_send_json_success(
		array(
			'item_id'         => $item_id,
			'item_type'       => $item_type,
			'reaction_id'     => $reaction_id,
			'reaction_counts' => bb_get_activity_post_user_reactions_html( $item_id ),
			'reaction_button' => bb_get_activity_post_reaction_button_html( $reaction_id, true ),
		)
	);
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
	$status    = bp_activity_remove_user_reaction( $item_id, $item_type );

	if ( is_wp_error( $status ) ) {
		wp_send_json_error( $status->get_error_message() );
	}

	wp_send_json_success(
		array(
			'item_id'         => $item_id,
			'item_type'       => $item_type,
			'reaction_counts' => bb_get_activity_post_user_reactions_html( $item_id ),
			'reaction_button' => bb_get_activity_post_reaction_button_html(),
		)
	);
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
function bb_get_activity_user_reactions( $args ) {

	$args = bp_parse_args(
		$args,
		array(
			'reaction_id' => 0,
			'item_id'     => 0,
			'user_id'     => 0,
			'paged'       => 1,
		),
		'bb_get_activity_user_reactions_args'
	);

	$reaction_data  = bb_load_reaction()->bb_get_user_reactions( $args );
	$user_reactions = array();

	if ( ! empty( $reaction_data['reactions'] ) ) {
		foreach ( $reaction_data['reactions'] as $reaction ) {
			$user_data     = get_userdata( $reaction->user_id );
			$reaction_meta = get_post_field( 'post_content', $reaction->reaction_id );

			$user_reactions[] = array(
				'id'          => $reaction->user_id,
				'name'        => $user_data->display_name,
				'role'        => ! empty( $user_data->roles ) ? $user_data->roles[0] : '',
				'avatar'      => get_avatar_url( $reaction->user_id ),
				'profile_url' => bbp_get_user_profile_url( $reaction->user_id ),
				'reaction'    => maybe_unserialize( $reaction_meta ),
			);
		}
	}

	return apply_filters( 'bb_get_activity_user_reactions', $user_reactions, $args );
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
		$reaction_data[ $key ]['total'] = bb_format_reaction_count( $reaction_data[ $key ]['total'] );
		$reaction_data[ $key ]['users'] = bb_get_activity_user_reactions(
			array(
				'item_id'     => $item_id,
				'item_type'   => $item_type,
				'reaction_id' => $reaction['id'],
			)
		);
	}

	if ( count( $reaction_data ) >= 2 ) {
		array_unshift(
			$reaction_data,
			array(
				'name'      => 'All',
				'type'      => 'all',
				'icon'      => '',
				'icon_text' => esc_html__( 'All', 'buddyboss' ),
				'users'     => bb_get_activity_user_reactions(
					array(
						'item_id'   => $item_id,
						'item_type' => $item_type,
					)
				),
			)
		);
	}

	wp_send_json_success(
		array(
			'item_id'   => $item_id,
			'reactions' => (object) $reaction_data,
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
			'per_page'    => 1000,
			'count_total' => true,
		)
	);

	if ( empty( $reaction_data['total'] ) || 1000 === $reaction_data['total'] ) {
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

		if ( function_exists( 'friends_get_friend_user_ids' ) ) {
			$friends      = friends_get_friend_user_ids( $current_logged_user_id );
			$friend_users = array_diff( $friends, $reacted_users );
		}

		if ( function_exists( 'bp_get_followers' ) ) {
			$followers      = bp_get_followers();
			$follower_users = array_diff( $followers, $reacted_users );
		}
	}

	$return_str = '';
	if ( 1 === $reaction_count ) {
		$user_id      = bb_get_reacted_person( $reacted_users, $friend_users, $follower_users );
		$display_name = bp_core_get_user_displayname( $user_id );
		$display_name = ! empty( $display_name ) ? $display_name : esc_html__( 'Unknown', 'buddyboss' );
		$return_str   = $is_current_user_reacted ? esc_html__( 'You', 'buddyboss' ) : $display_name;
	} elseif ( 2 === $reaction_count ) {
		$user_id     = bb_get_reacted_person( $reacted_users, $friend_users, $follower_users );
		$first_name  = bp_core_get_user_displayname( $user_id ) ?? esc_html__( 'Unknown', 'buddyboss' );
		$user_id     = bb_get_reacted_person( $reacted_users, $friend_users, $follower_users );
		$second_name = bp_core_get_user_displayname( $user_id ) ?? esc_html__( 'Unknown', 'buddyboss' );

		$return_str = $is_current_user_reacted
		? sprintf( esc_html__( 'You and %s', 'buddyboss' ), $first_name )
		: sprintf( esc_html__( '%1$s and %2$s', 'buddyboss' ), $first_name, $second_name );
	} elseif ( 3 <= $reaction_count && 1000 >= $reaction_count ) {
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
 * Update user reactions sql to check reaction is active.
 *
 * @param string $join_sql The join sql.
 *
 * @return string The join sql.
 */
function bb_update_user_reactions_join_sql( $join_sql = '' ) {
	global $wpdb;

	$join_sql .= 'INNER JOIN '. $wpdb->postmeta . ' pm ON pm.post_id = ur.reaction_id';
	$join_sql .= ' AND( ( pm.meta_key = "is_like" AND pm.meta_value = 1 ) OR ( pm.meta_key = "is_emotion_active" AND pm.meta_value = 1 ) )';

	return $join_sql;
}
