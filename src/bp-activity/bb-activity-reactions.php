<?php
/**
 * BuddyBoss Reaction Functions.
 *
 * Functions for the Reaction functionality.
 *
 * @package BuddyBoss\Activity
 * @since BuddyPress 2.5.20
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

add_action( 'wp_ajax_bb_get_reactions', 'bb_get_activity_reaction_ajax_callback' );
add_action( 'wp_ajax_nopriv_bb_get_reactions', 'bb_get_activity_reaction_ajax_callback' );

add_action( 'bp_activity_after_delete', 'bb_activity_remove_activity_post_reactions', 10, 1 );

/**
 * Get user reacted activity ids.
 *
 * @since BuddyBoss 2.5.20
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
 * Delete all reactions for an activity.
 *
 * @since BuddyBoss 2.5.20
 *
 * @param array|int $activities Array of the activity.
 *
 * @return void
 */
function bb_activity_remove_activity_post_reactions( $activities ) {

	if ( empty( $activities ) ) {
		return;
	}

	$activity_ids = array_column( $activities, 'id' );
	if ( empty( $activity_ids ) ) {
		return;
	}

	foreach ( $activity_ids as $activity_id ) {
		bb_load_reaction()->bb_remove_user_item_reactions(
			array(
				'item_id' => $activity_id,
				'user_id' => 0,
			)
		);
	}
}

/**
 * Get reaction emoticons for activity post.
 *
 * @since BuddyBoss 2.5.20
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
 * @since BuddyBoss 2.5.20
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
 * @since BuddyBoss 2.5.20
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
 * @since BuddyBoss 2.5.20
 *
 * @param integer $item_id         ID of the item.
 * @param string  $item_type       Type of the item.
 * @param integer $no_of_reactions Number of reactions to display.
 *
 * @return array
 */
function bb_get_activity_most_reactions( $item_id = 0, $item_type = 'activity', $no_of_reactions = 0 ) {

	if ( empty( $item_id ) ) {
		return array();
	}

	$reaction_data = bb_load_reaction()->bb_get_reactions_data(
		array(
			'name'     => 'item_summary',
			'rel1'     => $item_type,
			'rel2'     => $item_id,
			'per_page' => 1,
		)
	);

	if ( empty( $reaction_data['reaction_data'] ) ) {
		return array();
	}

	$reaction_data = current( $reaction_data['reaction_data'] );
	$reaction_data = maybe_unserialize( $reaction_data->value );
	$all_reactions = ! empty( $reaction_data['reactions_count'] ) ? $reaction_data['reactions_count'] : array();

	if ( isset( $all_reactions['total'] ) ) {
		unset( $all_reactions['total'] );
	}

	if ( empty( $all_reactions ) ) {
		return array();
	}

	$all_emotions = bb_active_reactions();
	$all_emotions = ( ! empty( $all_emotions ) ? array_flip( array_column( $all_emotions, 'id' ) ) : array() );

	$all_reactions = array_intersect_key( $all_reactions, $all_emotions );
	arsort( $all_reactions );

	if ( empty( $no_of_reactions ) ) {
		$no_of_reactions = 3;
		if ( 'activity_comment' === $item_type ) {
			$no_of_reactions = 2;
		}
	}

	$reactions = array();
	foreach ( $all_reactions as $reaction_id => $reaction_count ) {

		if ( 0 === $no_of_reactions ) {
			break;
		}

		$reactions[] = array(
			'id'    => $reaction_id,
			'count' => $reaction_count,
		);

		$no_of_reactions --;
	}

	return apply_filters( 'bb_get_activity_most_reactions', $reactions );
}

/**
 * Get activity post reaction button html.
 *
 * @since BuddyBoss 2.5.20
 *
 * @param int     $item_id     ID of the Activity/Comment.
 * @param string  $item_type   Type of Activity.
 * @param int     $reaction_id ID of the reaction.
 * @param boolean $has_reacted User has reaction or not.
 *
 * @return string
 */
function bb_get_activity_post_reaction_button_html( $item_id, $item_type = 'activity', $reaction_id = 0, $has_reacted = false ) {

	$reaction_button_class = 'fav reaction';
	if ( bb_activity_is_item_favorite( $item_id, $item_type ) ) {
		$reaction_button_class = 'unfav reaction has-reaction';
		$has_reacted           = true;
	}

	$reaction_data = array();

	if ( ! empty( $reaction_id ) ) {
		$reaction      = get_post_field( 'post_content', (int) $reaction_id );
		$reaction_data = ! empty( $reaction ) ? maybe_unserialize( $reaction ) : array();
	}

	$prepared_icon = bb_activity_get_reaction_button( $reaction_id, $has_reacted );

	if ( $has_reacted ) {
		$reaction_link = ( 'activity' === $item_type ) ? bp_get_activity_unfavorite_link( $item_id ) : bb_get_activity_comment_unfavorite_link( $item_id );
	} else {
		$reaction_link = ( 'activity' === $item_type ) ? bp_get_activity_favorite_link( $item_id ) : bb_get_activity_comment_favorite_link( $item_id );
	}

	return sprintf(
		'<a href="%1$s" class="button bp-like-button bp-secondary-action %5$s" aria-pressed="false" data-reacted-id="%6$s">
			<span class="bp-screen-reader-text">%2$s</span>
			%3$s
			<span class="like-count reactions_item" style="%4$s">%2$s</span>
		</a>',
		$reaction_link,
		! empty( $prepared_icon['icon_text'] ) ? $prepared_icon['icon_text'] : esc_html__( 'Like', 'buddyboss' ),
		$item_type === 'activity' ? $prepared_icon['icon_html'] : '',
		! empty( $reaction_data['text_color'] ) ? esc_attr( 'color:' . $reaction_data['text_color'] ) : '',
		$reaction_button_class,
		$reaction_id,
	);
}

/**
 * Get user reactions list for activity post.
 *
 * @since BuddyBoss 2.5.20
 *
 * @param int    $activity_id Activity/Comment ID.
 * @param string $item_type   Type of Activity.
 * @param bool   $is_popup    Add pop-up when it's true otherwise not add.
 *
 * @return string HTML markup
 */
function bb_get_activity_post_user_reactions_html( $activity_id, $item_type = 'activity', $is_popup = true ) {
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
			$icon    = bb_activity_prepare_emotion_icon( $reaction['id'] );
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

	// Build popup to show reacted items.
	if ( $is_popup ) {
		$output .= '<div class="activity-state-popup">
				<div class="activity-state-popup_overlay"></div>
				<div class="activity-state-popup_inner" id="reaction-content-' . $activity_id . '">
				</div>
			</div>';
	}

	return apply_filters( 'bb_get_activity_post_user_reactions_html', $output );
}

/**
 * Retrieves the user reactions for a specific activity.
 *
 * @since BuddyBoss 2.5.20
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

	// Fetch all likes and emotions.
	$all_reactions = bb_active_reactions();

	if ( ! empty( $reaction_data['reactions'] ) ) {
		foreach ( $reaction_data['reactions'] as $reaction ) {

			$reaction_meta = ! empty( $all_reactions[ $reaction->reaction_id ] ) ? $all_reactions[ $reaction->reaction_id ] : array();
			$type          = function_exists( 'bp_get_member_type_object' ) ? bp_get_member_type( $reaction->user_id ) : '';
			$type_obj      = function_exists( 'bp_get_member_type_object' ) && ! empty( $type ) ? bp_get_member_type_object( $type ) : '';
			$color_data    = function_exists( 'bb_get_member_type_label_colors' ) && ! empty( $type ) ? bb_get_member_type_label_colors( $type ) : '';
			$member_type   = '';
			if ( ! empty( $type_obj ) ) {
				$member_type = $type_obj->labels['singular_name'];
			}

			$user_reactions[] = array(
				'id'          => $reaction->id,
				'name'        => bp_core_get_user_displayname( $reaction->user_id ),
				'member_type' => array(
					'label' => $member_type ?? $type,
					'color' => array(
						'background' => ! empty( $color_data['background-color'] ) ? $color_data['background-color'] : '',
						'text'       => ! empty( $color_data['color'] ) ? $color_data['color'] : '',
					),
				),
				'avatar'      => bp_core_fetch_avatar(
					array(
						'item_id' => $reaction->user_id,
						'html'    => false,
					)
				),
				'profile_url' => bp_core_get_user_domain( $reaction->user_id ),
				'reaction'    => $reaction_meta,
			);
		}

		$reaction_data['reactions'] = $user_reactions;
	}

	return apply_filters( 'bb_activity_get_reacted_users_data', $reaction_data, $args );
}

/**
 * Get reactions data for an activity.
 *
 * @since BuddyBoss 2.5.20
 *
 * @return void
 */
function bb_get_activity_reaction_ajax_callback() {

	if ( ! bp_is_post_request() ) {
		wp_send_json_error();
	}

	// Nonce check!
	if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'bp_nouveau_activity' ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'Nonce verification failed.', 'buddyboss' ),
				'type'    => 'error'
			)
		);
	}

	if ( empty( $_POST['item_id'] ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'Item ID is required.', 'buddyboss' ),
				'type'    => 'error'
			)
		);
	}

	$item_id     = sanitize_text_field( $_POST['item_id'] );
	$item_type   = sanitize_text_field( $_POST['item_type'] );
	$paged       = ! empty( $_POST['page'] ) ? (int) sanitize_text_field( $_POST['page'] ) : 1;
	$reaction_id = ! empty( $_POST['reaction_id'] ) ? (int) sanitize_text_field( $_POST['reaction_id'] ) : 0;
	$before      = ! empty( $_POST['before'] ) ? (int) sanitize_text_field( $_POST['before'] ) : 0;
	$per_page    = 20; // Fixed per page.

	if ( 1 === $paged && empty( $reaction_id ) ) {
		$most_reacted = bb_get_activity_most_reactions( $item_id, $item_type, 7 );
		if ( empty( $most_reacted ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'No reactions found!', 'buddyboss' ),
					'type'    => 'info'
				)
			);
		}

		$tabs = array();

		if ( bb_is_reaction_emotions_enabled() ) {
			$popup_heading = __( 'Reactions', 'buddyboss' );
		} else {
			$popup_heading = __( 'Likes', 'buddyboss' );
		}

		$all_emotions  = bb_active_reactions();

		foreach ( $most_reacted as $reaction ) {
			$tab                     = ! empty( $all_emotions[ $reaction['id'] ] ) ? $all_emotions[ $reaction['id'] ] : array();
			$tab['paged']            = 1;
			$tab['total_pages']      = ceil( $reaction['count'] / $per_page );
			$tab['total_count']      = bb_format_reaction_count( $reaction['count'] );
			$tabs[ $reaction['id'] ] = $tab;
		}

		$popup_heading_count = 0;
		$total_pages         = 0;
		if ( 1 === count( $tabs ) ) {
			$current_tabs = current( $tabs );

			if ( 0 === $reaction_id ) {
				$reaction_id = $current_tabs['id'];
			}

			$current_reacted = bb_activity_get_reacted_users_data(
				array(
					'reaction_id' => $current_tabs['id'],
					'item_id'     => $item_id,
					'item_type'   => $item_type,
					'paged'       => $paged,
					'per_page'    => $per_page,
					'before'      => $before,
				)
			);

			$tab_content         = $current_reacted['reactions'];
			$popup_heading       = $current_tabs['icon_text'];
			$popup_heading_count = $current_tabs['total_count'];
			$total_pages         = $current_tabs['total_pages'];

			// No required tab when only 1 tab available.
			$tabs = array();
		} else {
			$all_reacted = bb_activity_get_reacted_users_data(
				array(
					'item_id'   => $item_id,
					'item_type' => $item_type,
					'paged'     => $paged,
					'per_page'  => $per_page,
					'before'      => $before,
				)
			);
			$tab_content = $all_reacted['reactions'];

			array_unshift(
				$tabs,
				array(
					'name'        => 'All',
					'type'        => 'all',
					'icon'        => '',
					'icon_text'   => esc_html__( 'All', 'buddyboss' ),
					'paged'       => 1,
					'total_pages' => ceil( $all_reacted['total'] / $per_page ),
					'total_count' => $all_reacted['total'],
				)
			);
		}

		wp_send_json_success(
			array(
				'item_id'             => $item_id,
				'page'                => 1,
				'reaction_mode'       => bb_get_reaction_mode(),
				'popup_heading'       => $popup_heading,
				'total_pages'         => $total_pages,
				'popup_heading_count' => $popup_heading_count,
				'reacted_users'       => $tab_content,
				'reacted_tabs'        => $tabs,
				'reaction_id'         => $reaction_id,
			)
		);
	} else {

		$users_data = bb_activity_get_reacted_users_data(
			array(
				'item_id'     => $item_id,
				'item_type'   => $item_type,
				'reaction_id' => $reaction_id,
				'paged'       => $paged,
				'per_page'    => $per_page,
				'before'      => $before,
			)
		);

		wp_send_json_success(
			array(
				'item_id'       => $item_id,
				'page'          => $paged,
				'reaction_mode' => bb_get_reaction_mode(),
				'reaction_id'   => $reaction_id,
				'reacted_users' => ! empty( $users_data['reactions'] ) ? $users_data['reactions'] : array(),
			)
		);
	}
}

/**
 * Get reaction count for activity.
 *
 * @since BuddyBoss 2.5.20
 *
 * @param int    $activity_id   Post Id.
 * @param string $activity_type Activity type.
 * @param int    $name_count    Name count to display.
 *
 * @return string
 */
function bb_activity_reaction_names_and_count( $activity_id, $activity_type = 'activity', $name_count = 2 ) {

	if (
		( 'activity' === $activity_type && ! bb_is_reaction_activity_posts_enabled() ) ||
		( 'activity_comment' === $activity_type && ! bb_is_reaction_activity_comments_enabled() )
	) {
		return '';
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

	$reacted_users  = ! empty( $reaction_data['reactions'] ) ? $reaction_data['reactions'] : array();
	$reaction_count = ! empty( $reaction_data['total'] ) ? absint( $reaction_data['total'] ) : 0;

	if (
		empty( $reaction_count ) ||
		100 <= $reaction_count ||
		'activity_comment' === $activity_type
	) {
		return bb_format_reaction_count( $reaction_count );
	}

	$is_current_user_reacted = false;
	$current_logged_user_id  = get_current_user_id();
	$current_key             = array_search( $current_logged_user_id, $reacted_users );

	if ( ! empty( $current_logged_user_id ) && false !== $current_key ) {
		$is_current_user_reacted = true;
		if ( ! empty( $reacted_users ) ) {
			unset( $reacted_users[ $current_key ] );
		}
	}

	$friend_users = array();

	// Get friends and followers.
	if (
		bp_is_active( 'friends' ) &&
		! empty( $current_logged_user_id ) &&
		! empty( $reacted_users ) &&
		count( $reacted_users ) > 1
	) {

		$friend_users = friends_get_friend_user_ids( $current_logged_user_id );
		if ( ! empty( $friend_users ) ) {
			$index_map = array_flip( $friend_users );
			usort(
				$reacted_users,
				function ( $a, $b ) use ( $index_map ) {
					$index_a = $index_map[ $a ] ?? PHP_INT_MAX;
					$index_b = $index_map[ $b ] ?? PHP_INT_MAX;

					// Compare the positions.
					return $index_a - $index_b;
				}
			);
		}
	}

	if (
		empty( $friend_users ) &&
		bp_is_activity_follow_active() &&
		function_exists( 'bp_get_followers' ) &&
		! empty( $current_logged_user_id ) &&
		! empty( $reacted_users ) &&
		count( $reacted_users ) > 1
	) {
		$followers = bp_get_followers(
			array(
				'user_id'  => $current_logged_user_id,
				'per_page' => - 1,
			)
		);

		if ( ! empty( $followers ) ) {
			$index_map = array_flip( $followers );
			usort(
				$reacted_users,
				function ( $a, $b ) use ( $index_map ) {
					$index_a = $index_map[ $a ] ?? PHP_INT_MAX;
					$index_b = $index_map[ $b ] ?? PHP_INT_MAX;

					// Compare the positions.
					return $index_a - $index_b;
				}
			);
		}
	}

	$display_names = array();
	if ( true === $is_current_user_reacted ) {
		$display_names[] = esc_html__( 'You', 'buddyboss' );
		$name_count--;
	}

	if ( ! empty( $reacted_users ) ) {
		$user_keys = [];
		foreach ( $reacted_users as $k => $user_id ) {
			if ( 0 === $name_count ) {
				break;
			}

			$user_keys[] = $k;
			$display_names[] = bp_core_get_user_displayname( $user_id );
			$name_count --;
		}

		$reacted_users = array_diff_key( $reacted_users, array_flip( $user_keys ) );
	}

	if ( ! empty( $reacted_users ) && count( $reacted_users ) > 0 ) {
		if ( count( $reacted_users ) === 1 ) {
			$display_names[] = bp_core_get_user_displayname( current( $reacted_users ) );
		} else {
			$display_names[] = sprintf(
				__( '%s others', 'buddyboss' ),
				bb_format_reaction_count( count( $reacted_users ) )
			);
		}
	}

	// Get all names except the last one
	$names_except_last = array_slice( $display_names, 0, - 1 );

	// Concatenate names with commas.
	$return_str = implode( ', ', $names_except_last );

	// Add the last name with "and".
	if ( $return_str ) {
		$return_str .= __( ' and ', 'buddyboss' ) . end( $display_names );
	} else {
		$return_str = end( $display_names );
	}

	return $return_str;
}

/**
 * Get the formatted reaction count.
 *
 * @since BuddyBoss 2.5.20
 *
 * @param int $count The reaction count.
 *
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
 * Check whether the current item is in the user's favorites.
 *
 * @since BuddyBoss 2.5.20
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

	$args = array(
		'item_type' => $item_type,
		'item_id'   => $item_id,
		'user_id'   => $user_id,
	);

	return (bool) bb_load_reaction()->bb_get_user_reactions_count( $args );
}

/**
 * Get user reaction by item.
 *
 * @since BuddyBoss 2.5.20
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
			'item_type'   => $item_type,
			'item_id'     => $item_id,
			'user_id'     => $user_id,
			'fields'      => 'reaction_id',
			'reaction_id' => bb_is_reaction_emotions_enabled() ? 0 : bb_load_reaction()->bb_reactions_get_like_reaction_id(),
		)
	);

	if ( empty( $user_reaction['reactions'] ) ) {
		return false;
	}

	$reaction_id  = current( $user_reaction['reactions'] );
	$all_emotions = bb_active_reactions();

	if ( ! empty( $all_emotions ) && isset( $all_emotions[ $reaction_id ] ) ) {
		return $all_emotions[ $reaction_id ];
	}

	return array();
}

/**
 * Prepare emotion icon with text.
 *
 * @since BuddyBoss 2.5.20
 *
 * @param int  $reaction_id Reaction ID.
 * @param bool $has_reacted Whether reacted or not.
 *
 * @return array
 */
function bb_activity_get_reaction_button( $reaction_id, $has_reacted = false ) {

	$settings  = bb_get_reaction_button_settings();
	$icon_text = ! empty( $settings['text'] ) && ! $has_reacted ? $settings['text'] : esc_html__( 'Like', 'buddyboss' );
	$icon      = ! empty( $settings['icon'] ) && ! $has_reacted ? $settings['icon'] : 'thumbs-up bb-icon-f';
	$icon_html = '<i class="bb-icon-' . $icon . ' "></i>';

	$retval = array(
		'icon_text' => $icon_text,
		'icon_html' => $icon_html,
	);

	$all_emotions = bb_active_reactions();

	if (
		empty( $all_emotions ) ||
		empty( $reaction_id ) ||
		! isset( $all_emotions[ $reaction_id ] )
	) {
		return $retval;
	}

	$reaction  = $all_emotions[ $reaction_id ];
	$icon_html = bb_activity_prepare_emotion_icon( $reaction_id );

	if (
		! empty( $reaction['type'] ) ||
		! empty( $reaction['icon_path'] )
	) {
		$icon_text = sanitize_text_field( $reaction['icon_text'] );
	}

	$retval['icon_text'] = $icon_text;
	$retval['icon_html'] = $icon_html;

	return $retval;
}

/**
 * Prepare emotion icon.
 *
 * @since BuddyBoss 2.5.20
 *
 * @param int|array|WP_Post $reaction_id Accepts a post ID, Emotion array, WP_Post object.
 *
 * @return string
 */
function bb_activity_prepare_emotion_icon( $reaction_id ) {
	$icon_html = '';

	$all_emotions = bb_active_reactions();

	if (
		empty( $all_emotions ) ||
		empty( $reaction_id ) ||
		! isset( $all_emotions[ $reaction_id ] )
	) {
		return $icon_html;
	}

	$reaction = $all_emotions[ $reaction_id ];

	if ( ! empty( $reaction['type'] ) && 'bb-icons' === $reaction['type'] ) {
		$icon_html = sprintf(
			'<i class="bb-icon-%s" style="color:%s;"></i>',
			esc_attr( $reaction['icon'] ),
			esc_attr( $reaction['icon_color'] ),
		);
	} elseif ( ! empty( $reaction['icon_path'] ) ) {
		$icon_html = sprintf(
			'<img src="%s" class="%s" alt="%s"/>',
			esc_url( $reaction['icon_path'] ),
			esc_attr( $reaction['type'] ),
			esc_attr( $reaction['icon_text'] )
		);
	} elseif ( empty( $reaction['type'] ) && ! empty( $reaction['name'] ) ) {
		$icon_html = '<i class="bb-icon-thumbs-up bb-icon-rf default-like"></i>';
	} else {
		$settings  = bb_get_reaction_button_settings();
		$icon_html = ! empty( $settings['icon'] ) ?
			sprintf( '<i class="bb-icon-%s"></i>',
				esc_attr( $settings['icon'] )
			) :
			'<i class="bb-icon-thumbs-up"></i>';
	}

	return $icon_html;
}

/**
 * Get reaction button settings.
 *
 * @since BuddyBoss 2.5.20
 *
 * @return array
 */
function bb_get_reaction_button_settings() {

	$args = array(
		'icon' => 'thumbs-up',
		'text' => esc_html__( 'Like', 'buddyboss' ),
	);

	if (
		! class_exists( 'BB_Reactions' ) ||
		! function_exists( 'bbp_pro_is_license_valid' ) ||
		! bbp_pro_is_license_valid() ||
		! bb_is_reaction_emotions_enabled()
	) {
		return $args;
	}

	$settings = bb_reaction_button_options();
	if ( ! empty( $settings['icon'] ) ) {
		$args['icon'] = $settings['icon'];
	}

	if ( ! empty( $settings['text'] ) ) {
		$args['text'] = $settings['text'];
	}

	return $args;
}
