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

add_action( 'wp_ajax_bb_reaction_action', 'bb_reaction_ajax_action' );
add_filter( 'bp_nouveau_get_activity_entry_buttons', 'bb_nouveau_get_activity_post_reaction_button', 10, 2 );

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
			'item_type'   => 'activity',
			'item_id'     => $activity_id,
			'user_id'     => bp_loggedin_user_id(),
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
	$icon_text = sprintf( 'Like', 'buddyboss' );

	if ( 'bb-icons' === $reaction_data['type'] ) {
		$icon = sprintf(
			'<i class="bb-icon-thumbs-up" style="font-weight:200;color:#aeae16;"></i>',
		);

	} elseif ( ! empty( $reaction_data['icon_path'] ) ) {
		$icon = sprintf(
			'<img src="%s" alt="%s" style="width:20px"/>',
			esc_url( $reaction_data['icon_path'] ),
			esc_attr( $reaction_data['icon_text'] )
		);

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

	$buttons['activity_favorite']['button_attr']['class'] = $buttons['activity_favorite']['button_attr']['class'] . ' has_reaction';

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
 * Get user reactions list for activity post.
 *
 * @param int $activity_id Activity Id.
 *
 * @return HTML markup
 */
function bb_get_activity_post_user_reaction_markup( $activity_id ) {

	$bb_reaction   = BB_Reaction::instance();
	$reaction_data = $bb_reaction->bb_get_reactions_data(
		array(
			'name' => 'item_summary',
			'rel1' => 'activity',
			'rel2' => $activity_id,
		)
	);

	$reaction_data   = current( $reaction_data['reaction_data'] );
	$reaction_data   = maybe_unserialize( $reaction_data->value );
	$reactions_count = $reaction_data['reactions_count'];

	$output = '';

	if ( ! empty( $reaction_data['last_10_reactions'] ) ) {
		$last_3_reactions = array_slice( $reaction_data['last_10_reactions'], -3 );

		$output .= '<div class="activity-state-reactions">';

		foreach ( $last_3_reactions as $reaction ) {
			if ( empty( $reaction->reaction_id ) ) {
				continue;
			}

			$reaction_post = get_post( $reaction->reaction_id );

			if ( empty( $reaction_post->post_content ) ) {
				continue;
			}

			// $is_active = get_post_meta( $reaction->reaction_id, 'is_emotion_active', true );
			// if ( ! $is_active ) {
			// 	continue;
			// }

			$reaction_content = maybe_unserialize( $reaction_post->post_content );

			// error_log( print_r( $reaction_content, true ) );

			$icon = '';
			if ( 'bb-icons' === $reaction_content['type'] ) {
				$icon = sprintf(
					'<i class="bb-icon-thumbs-up" style="font-weight:200;color:#aeae16;"></i>',
				);
			} elseif( ! empty( $reaction_content['icon_path'] ) ) {
				$icon = sprintf(
					'<img src="%s" alt="%s" />',
					esc_url( $reaction_content['icon_path'] ),
					esc_attr( $reaction_content['icon_text'] )
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

		$output .= sprintf(
			'<div class="activity-reactions_count">%s</div>',
			$reactions_count['total']
		);

		$output .= '</div>';

		error_log( 'activity id' . $activity_id );
		error_log( print_r( $output, true ) );
	}

	return apply_filters( 'bb_get_activity_post_user_reaction_markup', $output );
}

/**
 * Ajax callback for Reaction.
 *
 * @return mixed
 */
function bb_reaction_ajax_action() {

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

	wp_send_json_success(
		array(
			'item_id'     => $item_id,
			'reaction_id' => $reaction_id,
			'result'      => true,
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
function bb_activity_reaction_count_string( $activity_id ) {

	if ( ! bp_is_activity_like_active() ) {
		return 0;
	}

	$like_count      = bp_activity_get_meta( $activity_id, 'favorite_count', true );
	$like_count      = ( isset( $like_count ) && ! empty( $like_count ) ) ? $like_count : 0;
	$favorited_users = bp_activity_get_meta( $activity_id, 'bp_favorite_users', true );

	if ( empty( $favorited_users ) || ! is_array( $favorited_users ) ) {
		return 0;
	}

	if ( $like_count > sizeof( $favorited_users ) ) {
		$like_count = sizeof( $favorited_users );
	}

	$current_user_fav = false;
	if ( bp_loggedin_user_id() && in_array( bp_loggedin_user_id(), $favorited_users ) ) {
		$current_user_fav = true;
		if ( sizeof( $favorited_users ) > 1 ) {
			$pos = array_search( bp_loggedin_user_id(), $favorited_users );
			unset( $favorited_users[ $pos ] );
		}
	}

	$return_str = '';
	if ( 1 == $like_count ) {
		if ( $current_user_fav ) {
			$return_str = __( 'You like this', 'buddyboss' );
		} else {
			$user_data         = get_userdata( array_pop( $favorited_users ) );
			$user_display_name = ! empty( $user_data ) ? bp_core_get_user_displayname( $user_data->ID ) : __( 'Unknown', 'buddyboss' );
			$return_str        = $user_display_name . ' ' . __( 'likes this', 'buddyboss' );
		}
	} elseif ( 2 == $like_count ) {
		if ( $current_user_fav ) {
			$return_str .= __( 'You and', 'buddyboss' ) . ' ';

			$user_data         = get_userdata( array_pop( $favorited_users ) );
			$user_display_name = ! empty( $user_data ) ? bp_core_get_user_displayname( $user_data->ID ) : __( 'Unknown', 'buddyboss' );
			$return_str       .= $user_display_name . ' ' . __( 'like this', 'buddyboss' );
		} else {
			$user_data         = get_userdata( array_pop( $favorited_users ) );
			$user_display_name = ! empty( $user_data ) ? bp_core_get_user_displayname( $user_data->ID ) : __( 'Unknown', 'buddyboss' );
			$return_str       .= $user_display_name . ' ' . __( 'and', 'buddyboss' ) . ' ';

			$user_data         = get_userdata( array_pop( $favorited_users ) );
			$user_display_name = ! empty( $user_data ) ? bp_core_get_user_displayname( $user_data->ID ) : __( 'Unknown', 'buddyboss' );
			$return_str       .= $user_display_name . ' ' . __( 'like this', 'buddyboss' );
		}
	} elseif ( 3 == $like_count ) {

		if ( $current_user_fav ) {
			$return_str .= __( 'You,', 'buddyboss' ) . ' ';

			$user_data         = get_userdata( array_pop( $favorited_users ) );
			$user_display_name = ! empty( $user_data ) ? bp_core_get_user_displayname( $user_data->ID ) : __( 'Unknown', 'buddyboss' );
			$return_str       .= $user_display_name . ' ' . __( 'and', 'buddyboss' ) . ' ';

			$return_str .= ' ' . __( '1 other like this', 'buddyboss' );
		} else {

			$user_data         = get_userdata( array_pop( $favorited_users ) );
			$user_display_name = ! empty( $user_data ) ? bp_core_get_user_displayname( $user_data->ID ) : __( 'Unknown', 'buddyboss' );
			$return_str       .= $user_display_name . ', ';

			$user_data         = get_userdata( array_pop( $favorited_users ) );
			$user_display_name = ! empty( $user_data ) ? bp_core_get_user_displayname( $user_data->ID ) : __( 'Unknown', 'buddyboss' );
			$return_str       .= $user_display_name . ' ' . __( 'and', 'buddyboss' ) . ' ';

			$return_str .= ' ' . __( '1 other like this', 'buddyboss' );
		}
	} elseif ( 3 < $like_count ) {

		$like_count = ( isset( $like_count ) && ! empty( $like_count ) ) ? (int) $like_count - 2 : 0;

		if ( $current_user_fav ) {
			$return_str .= __( 'You,', 'buddyboss' ) . ' ';

			$user_data         = get_userdata( array_pop( $favorited_users ) );
			$user_display_name = ! empty( $user_data ) ? bp_core_get_user_displayname( $user_data->ID ) : __( 'Unknown', 'buddyboss' );
			$return_str       .= $user_display_name . ' ' . __( 'and', 'buddyboss' ) . ' ';
		} else {
			$user_data         = get_userdata( array_pop( $favorited_users ) );
			$user_display_name = ! empty( $user_data ) ? bp_core_get_user_displayname( $user_data->ID ) : __( 'Unknown', 'buddyboss' );
			$return_str       .= $user_display_name . ', ';

			$user_data         = get_userdata( array_pop( $favorited_users ) );
			$user_display_name = ! empty( $user_data ) ? bp_core_get_user_displayname( $user_data->ID ) : __( 'Unknown', 'buddyboss' );
			$return_str       .= $user_display_name . ' ' . __( 'and', 'buddyboss' ) . ' ';
		}

		if ( $like_count > 1 ) {
			$return_str .= $like_count . ' ' . __( 'others like this', 'buddyboss' );
		} else {
			$return_str .= $like_count . ' ' . __( 'other like this', 'buddyboss' );
		}
	} else {
		$return_str = $like_count;
	}

	return $return_str;
}
