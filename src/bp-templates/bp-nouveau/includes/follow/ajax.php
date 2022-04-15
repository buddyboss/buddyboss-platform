<?php
/**
 * Follow Ajax functions
 *
 * @since BuddyBoss 1.0.0
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

add_action(
	'admin_init',
	function() {
		$ajax_actions = array(
			array(
				'follow_follow' => array(
					'function' => 'bp_nouveau_ajax_followunfollow_member',
					'nopriv'   => false,
				),
			),
			array(
				'follow_unfollow' => array(
					'function' => 'bp_nouveau_ajax_followunfollow_member',
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
 * Follow/Unfollow a user via a POST request.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_nouveau_ajax_followunfollow_member() {
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

	if ( empty( $_POST['_wpnonce'] ) || empty( $_POST['item_id'] ) ) {
		wp_send_json_error( $response );
	}

	// Use default nonce.
	$nonce = sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) );
	$check = 'bp_nouveau_follow';

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
	$leader_id = (int) $_POST['item_id'];

	$current_page     = isset( $_POST['current_page'] ) ? sanitize_text_field( wp_unslash( $_POST['current_page'] ) ) : '';
	$button_clicked   = isset( $_POST['button_clicked'] ) ? sanitize_text_field( wp_unslash( $_POST['button_clicked'] ) ) : '';
	$button_arguments = function_exists( 'bb_member_get_profile_action_arguments' ) ? bb_member_get_profile_action_arguments( $current_page, $button_clicked ) : array();

	// Actions button arguments to display different style based on arguments.
	$button_arguments = array_merge(
		$button_arguments,
		array(
			'parent_element' => 'li',
			'button_element' => 'button',
		)
	);

	// Check if the user exists.
	if ( isset( $_POST['action'] ) ) {
		$user = get_user_by( 'id', $leader_id );
		if ( ! $user ) {
			wp_send_json_error(
				array(
					'feedback' => sprintf(
						'<div class="bp-feedback error">%s</div>',
						esc_html__( 'No member found with that ID.', 'buddyboss' )
					),
				)
			);
		}
	}

	$is_following = bp_is_following(
		array(
			'leader_id'   => $leader_id,
			'follower_id' => bp_loggedin_user_id(),
		)
	);

	// Trying to unfollow.
	if ( $is_following ) {
		if ( ! bp_stop_following(
			array(
				'leader_id'   => $leader_id,
				'follower_id' => bp_loggedin_user_id(),
			)
		) ) {

			$response['feedback'] = sprintf(
				'<div class="bp-feedback error">%s</div>',
				esc_html__( 'There was a problem when trying to unfollow this user.', 'buddyboss' )
			);

			wp_send_json_error( $response );
		} else {

			ob_start();
			bb_get_followers_count( $leader_id );
			$total_followers = ob_get_clean();

			if ( bp_has_members( 'include=' . $leader_id ) ) {
				while ( bp_members() ) {
					bp_the_member();

					wp_send_json_success(
						array(
							'contents' => bp_get_add_follow_button(
								$leader_id,
								bp_loggedin_user_id(),
								$button_arguments
							),
							'count'    => $total_followers,
						)
					);
				}
			} else {
				wp_send_json_success(
					array(
						'contents' => '',
						'count'    => $total_followers,
					)
				);
			}
		}

		// Trying to follow.
	} elseif ( ! $is_following ) {
		if ( ! bp_start_following(
			array(
				'leader_id'   => $leader_id,
				'follower_id' => bp_loggedin_user_id(),
			)
		) ) {

			$response['feedback'] = sprintf(
				'<div class="bp-feedback error">%s</div>',
				esc_html__( 'There was a problem when trying to follow this user.', 'buddyboss' )
			);

			wp_send_json_error( $response );
		} else {

			ob_start();
			bb_get_followers_count( $leader_id );
			$total_followers = ob_get_clean();

			if ( bp_has_members( 'include=' . $leader_id ) ) {
				while ( bp_members() ) {
					bp_the_member();

					wp_send_json_success(
						array(
							'contents' => bp_get_add_follow_button(
								$leader_id,
								bp_loggedin_user_id(),
								$button_arguments
							),
							'count'    => $total_followers,
						)
					);
				}
			} else {
				wp_send_json_success(
					array(
						'contents' => '',
						'count'    => $total_followers,
					)
				);
			}
		}
	} else {
		$response['feedback'] = sprintf(
			'<div class="bp-feedback error">%s</div>',
			esc_html__( 'Request Pending', 'buddyboss' )
		);

		wp_send_json_error( $response );
	}
}
