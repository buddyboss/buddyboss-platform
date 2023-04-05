<?php
/**
 * Connections Ajax functions
 *
 * @since BuddyPress 3.0.0
 * @version 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

add_action(
	'admin_init',
	function() {
		$ajax_actions = array(
			array(
				'friends_remove_friend' => array(
					'function' => 'bp_nouveau_ajax_addremove_friend',
					'nopriv'   => false,
				),
			),
			array(
				'friends_add_friend' => array(
					'function' => 'bp_nouveau_ajax_addremove_friend',
					'nopriv'   => false,
				),
			),
			array(
				'friends_withdraw_friendship' => array(
					'function' => 'bp_nouveau_ajax_addremove_friend',
					'nopriv'   => false,
				),
			),
			array(
				'friends_accept_friendship' => array(
					'function' => 'bp_nouveau_ajax_addremove_friend',
					'nopriv'   => false,
				),
			),
			array(
				'friends_reject_friendship' => array(
					'function' => 'bp_nouveau_ajax_addremove_friend',
					'nopriv'   => false,
				),
			),
		);

		foreach ( $ajax_actions as $ajax_action ) {
			$action = key( $ajax_action );

			add_action( 'wp_ajax_' . $action, $ajax_action[ $action ]['function'] );

			if ( ! empty( $ajax_action[ $action ]['nopriv'] ) ) {
				add_action( 'wp_ajax_nopriv_' . $action, $ajax_action[ $action ]['function'] );
			}
		}
	},
	12
);

/**
 * Friend/un-friend a user via a POST request.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_ajax_addremove_friend() {
	$response = array(
		'feedback' => sprintf(
			'<div class="bp-feedback error bp-ajax-message"><p>%s</p></div>',
			esc_html__( 'There was a problem performing this action. Please try again.', 'buddyboss' )
		),
	);

	// Bail if not a POST action.
	if ( ! bp_is_post_request() ) {
		wp_send_json_error( $response );
	}

	if ( empty( $_POST['nonce'] ) || empty( $_POST['item_id'] ) || ! bp_is_active( 'friends' ) ) {
		wp_send_json_error( $response );
	}

	// Use default nonce.
	$nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ) );
	$check = 'bp_nouveau_friends';

	// Use a specific one for actions needed it.
	if ( ! empty( $_POST['_wpnonce'] ) && ! empty( $_POST['action'] ) ) {
		$nonce = sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) );
		$check = sanitize_text_field( wp_unslash( $_POST['action'] ) );
	}

	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $check ) ) {
		wp_send_json_error( $response );
	}

	// Cast fid as an integer.
	$friend_id = (int) $_POST['item_id'];

	$current_page     = isset( $_POST['current_page'] ) ? sanitize_text_field( wp_unslash( $_POST['current_page'] ) ) : '';
	$button_clicked   = isset( $_POST['button_clicked'] ) ? sanitize_text_field( wp_unslash( $_POST['button_clicked'] ) ) : '';
	$component        = isset( $_POST['component'] ) ? sanitize_text_field( wp_unslash( $_POST['component'] ) ) : '';
	$button_arguments = function_exists( 'bb_member_get_profile_action_arguments' ) ? bb_member_get_profile_action_arguments( $current_page, $button_clicked ) : array();

	// Actions button arguments to display different style based on arguments.
	$button_arguments = array_merge(
		$button_arguments,
		array(
			'parent_element' => 'li',
			'button_element' => 'button',
		)
	);

	if ( 'messages' === $component ) {
		add_filter( 'bp_after_bb_parse_button_args_parse_args', 'bb_messaged_set_friend_button_args' );
	}

	$type = '';

	// Check if the user exists only when the Friend ID is not a Frienship ID.
	if ( isset( $_POST['action'] ) && 'friends_accept_friendship' !== $_POST['action'] && 'friends_reject_friendship' !== $_POST['action'] ) {
		$user = get_user_by( 'id', $friend_id );
		if ( ! $user ) {
			$type     = 'error';
			$response = array(
				'feedback' => sprintf(
					'<div class="bp-feedback error">%s</div>',
					esc_html__( 'No member found by that ID.', 'buddyboss' )
				),
			);
		}
	}

	// In the 2 first cases the $friend_id is a friendship id.
	if ( ! empty( $_POST['action'] ) && 'friends_accept_friendship' === $_POST['action'] ) {
		if ( ! friends_accept_friendship( $friend_id ) ) {

			$type     = 'error';
			$response = array(
				'feedback' => sprintf(
					'<div class="bp-feedback error">%s</div>',
					esc_html__( 'There was a problem accepting that request. Please try again.', 'buddyboss' )
				),
			);

		} else {

			$type     = 'success';
			$response = array(
				'feedback'     => sprintf(
					'<div class="bp-feedback success">%s</div>',
					esc_html__( 'Connection accepted.', 'buddyboss' )
				),
				'type'         => 'success',
				'is_user'      => true,
				'friend_count' => bp_core_number_format( friends_get_friend_count_for_user( bp_loggedin_user_id() ) ),
			);
		}

		// Rejecting a friendship.
	} elseif ( ! empty( $_POST['action'] ) && 'friends_reject_friendship' === $_POST['action'] ) {
		if ( ! friends_reject_friendship( $friend_id ) ) {

			$type     = 'error';
			$response = array(
				'feedback' => sprintf(
					'<div class="bp-feedback error">%s</div>',
					esc_html__( 'There was a problem rejecting that request. Please try again.', 'buddyboss' )
				),
			);
		} else {

			$type     = 'success';
			$response = array(
				'feedback'     => sprintf(
					'<div class="bp-feedback success">%s</div>',
					esc_html__( 'Connection rejected.', 'buddyboss' )
				),
				'type'         => 'success',
				'is_user'      => true,
				'friend_count' => bp_core_number_format( friends_get_friend_count_for_user( bp_loggedin_user_id() ) ),
			);
		}

		// Trying to cancel friendship.
	} elseif ( 'is_friend' === BP_Friends_Friendship::check_is_friend( bp_loggedin_user_id(), $friend_id ) ) {
		if ( ! friends_remove_friend( bp_loggedin_user_id(), $friend_id ) ) {
			$response['feedback'] = sprintf(
				'<div class="bp-feedback error">%s</div>',
				esc_html__( 'Connection could not be cancelled.', 'buddyboss' )
			);

			$type = 'error';
		} else {
			$is_user = bp_is_my_profile();

			if ( ! $is_user ) {
				$response = array(
					'contents'     => bp_get_add_friend_button(
						$friend_id,
						false,
						$button_arguments
					),
					'friend_count' => bp_core_number_format( friends_get_friend_count_for_user( bp_loggedin_user_id() ) ),
				);
			} else {
				$response = array(
					'feedback'     => sprintf(
						'<div class="bp-feedback success">%s</div>',
						esc_html__( 'Connection removed.', 'buddyboss' )
					),
					'type'         => 'success',
					'is_user'      => $is_user,
					'friend_count' => bp_core_number_format( friends_get_friend_count_for_user( bp_loggedin_user_id() ) ),
				);
			}

			$type = 'success';

		}

		// Trying to request friendship.
	} elseif ( 'not_friends' === BP_Friends_Friendship::check_is_friend( bp_loggedin_user_id(), $friend_id ) ) {
		if ( ! friends_add_friend( bp_loggedin_user_id(), $friend_id ) ) {
			$response['feedback'] = sprintf(
				'<div class="bp-feedback error">%s</div>',
				esc_html__( 'Connection could not be requested.', 'buddyboss' )
			);

			$type = 'error';
		} else {

			$type     = 'success';
			$response = array(
				'contents' => bp_get_add_friend_button(
					$friend_id,
					false,
					$button_arguments
				),
			);

		}

		// Trying to cancel pending request.
	} elseif ( 'pending' === BP_Friends_Friendship::check_is_friend( bp_loggedin_user_id(), $friend_id ) ) {
		if ( friends_withdraw_friendship( bp_loggedin_user_id(), $friend_id ) ) {

			$response = array(
				'contents' => bp_get_add_friend_button(
					$friend_id,
					false,
					$button_arguments
				),
			);

			$type = 'success';

		} else {
			$response['feedback'] = sprintf(
				'<div class="bp-feedback error">%s</div>',
				esc_html__( 'Connection request could not be cancelled.', 'buddyboss' )
			);
			$type                 = 'error';
		}

		// Request already pending.
	} else {
		$response['feedback'] = sprintf(
			'<div class="bp-feedback error">%s</div>',
			esc_html__( 'Request Pending', 'buddyboss' )
		);
		$type                 = 'error';

	}

	if ( 'messages' === $component ) {
		remove_filter( 'bp_after_bb_parse_button_args_parse_args', 'bb_messaged_set_friend_button_args' );
	}

	if ( 'error' === $type ) {
		wp_send_json_error( $response );
	} else {
		wp_send_json_success( $response );
	}
}
