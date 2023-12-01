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

add_filter( 'bp_nouveau_get_activity_entry_buttons', 'bb_nouveau_get_activity_post_reaction_button', 10, 2 );
add_action( 'bp_activity_action_delete_activity', 'bb_activity_delete_reactions', 10, 1 );


/**
 * Delete all reactions for an activity.
 *
 * @param int $activity_id ID of the activity.
 * @return void
 */
function bb_activity_delete_reactions( $activity_id ) {
	$bb_reaction = BB_Reaction::instance();
	$bb_reaction->bb_remove_user_item_reactions(
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
function bb_nouveau_get_activity_post_reaction_button( $buttons, $activity_id ) {

	if ( empty( $buttons['activity_favorite'] ) ) {
		return $buttons;
	}

	$bb_reaction   = BB_Reaction::instance();
	$user_reaction = $bb_reaction->bb_get_user_reactions(
		array(
			'item_type' => 'activity',
			'item_id'   => $activity_id,
			'user_id'   => bp_loggedin_user_id(),
		)
	);

	$user_reaction = current( $user_reaction['reactions'] );
	if ( empty( $user_reaction ) ) {
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

	if ( ! empty( $reaction_data['type'] ) ) {
		$buttons['activity_favorite']['button_attr']['class'] = 'button has-reactions bp-secondary-action';
	}

	return $buttons;
}

/**
 * Get activity post reaction markup.
 *
 * @return HTML markup
 */
function bb_get_activity_post_reaction_markup() {

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

	return apply_filters( 'bb_get_activity_post_reaction_markup', $output );
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

	$bb_reaction   = BB_Reaction::instance();
	$reaction_data = $bb_reaction->bb_get_reaction_reactions_count(
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
		function( $first, $second ) {
			return $first->total - $second->total;
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

		// $is_active = get_post_meta( $reaction->reaction_id, 'is_emotion_active', true );
		// if ( ! $is_active ) {
		// continue;
		// }

		$reaction    = maybe_unserialize( $reaction_post->post_content );
		$reactions[] = $reaction;

		$no_of_reactions --;
	}

	return apply_filters( 'bb_get_activity_user_reactions', $reactions );
}

/**
 * Get user reactions list for activity post.
 *
 * @param int $activity_id Activity Id.
 *
 * @return HTML markup
 */
function bb_get_activity_post_user_reaction_markup( $activity_id ) {

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

	return apply_filters( 'bb_get_activity_post_user_reaction_markup', $output );
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

	// error_log( print_r( $_POST, true ) );

	// Nonce check!
	if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'bp_nouveau_activity' ) ) {
		wp_send_json_error(
			__( 'Nonce verification failed', 'buddyboss' )
		);
	}

	if ( empty( $_POST['reaction_id'] ) ) {
		wp_send_json_error(
			array(
				'no_reaction_id' => esc_html__( 'No reaction id', 'buddyboss' ),
			)
		);
	}

	$reaction_id = sanitize_text_field( $_POST['reaction_id'] );

	if ( empty( $_POST['item_id'] ) ) {
		wp_send_json_error(
			array(
				'no_item_id' => esc_html__( 'No item id', 'buddyboss' ),
			)
		);
	}

	$item_id   = sanitize_text_field( $_POST['item_id'] );
	$item_type = sanitize_text_field( $_POST['item_type'] );

	$bb_reaction = BB_Reaction::instance();
	$bb_reaction->bb_add_user_item_reaction(
		array(
			'reaction_id' => $reaction_id,
			'item_id'     => $item_id,
			'user_id'     => get_current_user_id(),
			'item_type'   => $item_type,
		)
	);

	$reaction_post = get_post( $reaction_id );
	$reaction_data = maybe_unserialize( $reaction_post->post_content );

	if ( ! empty( $reaction_data ) ) {
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

		$reaction_button = sprintf(
			'<a href="%1$s" class="button has-reactions bp-secondary-action" aria-pressed="false">
				<span class="bp-screen-reader-text">%2$s</span>
				%3$s
				<span class="like-count reactions_item" style="color:%4$s">%2$s</span>
			</a>',
			bp_get_activity_unfavorite_link(),
			esc_html( $icon_text ),
			$icon,
			esc_attr( $text_color )
		);
	}

	wp_send_json_success(
		array(
			'item_id'         => $item_id,
			'reaction_id'     => $reaction_id,
			'reaction_counts' => bb_get_activity_post_user_reaction_markup( $item_id ),
			'reaction_button' => $reaction_button,
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

	$bb_reaction = BB_Reaction::instance();
	$bb_reaction->bb_remove_user_item_reactions(
		array(
			'item_id'   => $item_id,
			'user_id'   => get_current_user_id(),
			'item_type' => $item_type,
		)
	);

	wp_send_json_success(
		array(
			'item_id'         => $item_id,
			'reaction_counts' => bb_get_activity_post_user_reaction_markup( $item_id ),
			'reaction_button' => sprintf(
				'<a href="%1$s" class="button fav bp-secondary-action" aria-pressed="false">
					<span class="bp-screen-reader-text">%2$s</span>
					<span class="like-count">%2$s</span>
				</a>',
				bp_get_activity_favorite_link(),
				esc_html__( 'Likes', 'buddyboss' )
			),
		)
	);
}

/**
 * Get reaction count for activity
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param int $activity_id Post Id.
 * @return int|string
 */
function bb_activity_reaction_names_and_count( $activity_id, $activity_type = 'activity' ) {

	if ( ! bp_is_activity_like_active() ) {
		return 0;
	}

	$bb_reaction   = BB_Reaction::instance();
	$reaction_data = $bb_reaction->bb_get_user_reactions(
		array(
			'item_id'   => $activity_id,
			'item_type' => $activity_type,
			'fields'    => 'user_id',
		)
	);

	$reacted_users  = ! empty( $reaction_data['reactions'] ) ? $reaction_data['reactions'] : array();
	$reaction_count = is_countable( $reacted_users ) ? count( $reacted_users ) : 0;

	if ( 0 === $reaction_count ) {
		return 0;
	}

	$is_current_user_reacted = false;
	$current_logged_user_id  = bp_loggedin_user_id();
	$current_key             = array_search( $current_logged_user_id, $reacted_users );

	if ( ! empty( $current_logged_user_id ) && false !== $current_key ) {
		$is_current_user_reacted = true;
		if ( $reaction_count > 1 ) {
			unset( $reacted_users[ $current_key ] );
		}

		$friends   = friends_get_friend_user_ids( $current_logged_user_id );
		$followers = bp_get_followers();

		$friend_users   = array_diff( $friends, $reacted_users );
		$follower_users = array_diff( $followers, $reacted_users );
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
	} elseif ( 3 <= $reaction_count && 100 > $reaction_count ) {
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


