<?php
/**
 * Common functions only loaded on AJAX requests.
 *
 * @since BuddyPress 3.0.0
 * @version 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Load the template loop for the current object.
 *
 * @since BuddyPress 3.0.0
 *
 * @return string Template loop for the specified object.
 */
function bp_nouveau_ajax_object_template_loader() {
	if ( ! bp_is_post_request() ) {
		wp_send_json_error();
	}

	if ( empty( $_POST['object'] ) ) {
		wp_send_json_error();
	}

	$object = sanitize_title( $_POST['object'] );

	// Bail if object is not an active component to prevent arbitrary file inclusion.
	if ( ! bp_is_active( $object ) ) {
		wp_send_json_error();
	}

	// Nonce check!
	if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'bp_nouveau_' . $object ) ) {
		wp_send_json_error();
	}

	$result = array();

	if ( 'activity' === $object ) {
		$scope = '';
		if ( ! empty( $_POST['scope'] ) ) {
			$scope = sanitize_text_field( $_POST['scope'] );
		}

		// We need to calculate and return the feed URL for each scope.
		switch ( $scope ) {
			case 'friends':
				$feed_url = bp_loggedin_user_domain() . bp_get_activity_slug() . '/friends/feed/';
				break;
			case 'groups':
				$feed_url = bp_loggedin_user_domain() . bp_get_activity_slug() . '/groups/feed/';
				break;
			case 'favorites':
				$feed_url = bp_loggedin_user_domain() . bp_get_activity_slug() . '/favorites/feed/';
				break;
			case 'mentions':
				$feed_url = bp_loggedin_user_domain() . bp_get_activity_slug() . '/mentions/feed/';

				// Get user new mentions.
				$new_mentions = bp_get_user_meta( bp_loggedin_user_id(), 'bp_new_mentions', true );

				// If we have some, include them into the returned json before deleting them.
				if ( is_array( $new_mentions ) ) {
					$result['new_mentions'] = $new_mentions;

					// Clear new mentions.
					bp_activity_clear_new_mentions( bp_loggedin_user_id() );
				}

				break;
			default:
				$feed_url = bp_get_sitewide_activity_feed_link();
				break;
		}

		/**
		 * Filters the browser URL for the template loader.
		 *
		 * @since BuddyPress 3.0.0
		 *
		 * @param string $feed_url Template feed url.
		 * @param string $scope    Current component scope.
		 */
		$result['feed_url'] = apply_filters( 'bp_nouveau_ajax_object_template_loader', $feed_url, $scope );
	}

	/*
	 * AJAX requests happen too early to be seen by bp_update_is_directory()
	 * so we do it manually here to ensure templates load with the correct
	 * context. Without this check, templates will load the 'single' version
	 * of themselves rather than the directory version.
	 */
	if ( ! bp_current_action() ) {
		bp_update_is_directory( true, bp_current_component() );
	}

	// Get the template path based on the 'template' variable via the AJAX request.
	$template = isset( $_POST['template'] ) ? wp_unslash( $_POST['template'] ) : '';

	switch ( $template ) {
		case 'group_members':
		case 'groups/single/members':
			$template_part = 'groups/single/members-loop.php';
			break;

		case 'group_requests':
			$template_part = 'groups/single/requests-loop.php';
			break;

		case 'member_notifications':
			$template_part = 'members/single/notifications/notifications-loop.php';
			break;

		default:
			$template_part = $object . '/' . $object . '-loop.php';
			break;
	}

	ob_start();

	$template_path = bp_locate_template( array( $template_part ), false );

	/**
	 * Filters the server path for the template loader.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param string Template file path.
	 */
	$template_path = apply_filters( 'bp_nouveau_object_template_path', $template_path );

	load_template( $template_path );
	$result['contents'] = ob_get_contents();
	ob_end_clean();

	if ( 'members' === $object && ! empty( $GLOBALS['members_template'] ) ) {
		$result['count'] = $GLOBALS['members_template']->total_member_count;
	} elseif ( 'groups' === $object && ! empty( $GLOBALS['groups_template'] ) ) {
		$result['count'] = $GLOBALS['groups_template']->group_count;
	} elseif ( 'activity' === $object ) {
		// $result['count'] = $GLOBALS["activities_template"]->activity_count;
	}

	$result = apply_filters( 'bp_nouveau_object_template_result', $result, $object );

	// Locate the object template.
	wp_send_json_success( $result );
}

add_filter( 'bp_nouveau_object_template_result', 'bp_nouveau_object_template_results_members_tabs', 10, 2 );
/**
 * Object template results members tabs.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_nouveau_object_template_results_members_tabs( $results, $object ) {
	if ( $object != 'members' ) {
		return $results;
	}

	$results['scopes'] = array();

	add_filter( 'bp_ajax_querystring', 'bp_member_object_template_results_members_all_scope', 20, 2 );
	bp_has_members( bp_ajax_querystring( 'members' ) );
	$results['scopes']['all'] = $GLOBALS['members_template']->total_member_count;
	remove_filter( 'bp_ajax_querystring', 'bp_member_object_template_results_members_all_scope', 20, 2 );

	add_filter( 'bp_ajax_querystring', 'bp_nouveau_object_template_results_members_personal_scope', 20, 2 );
	bp_has_members( bp_ajax_querystring( 'members' ) );
	$results['scopes']['personal'] = $GLOBALS['members_template']->total_member_count;
	remove_filter( 'bp_ajax_querystring', 'bp_nouveau_object_template_results_members_personal_scope', 20, 2 );

	if ( bp_is_active( 'activity' ) && bp_is_activity_follow_active() ) {
		$counts = bp_total_follow_counts();
		if ( ! empty( $counts['following'] ) ) {
			add_filter( 'bp_ajax_querystring', 'bp_nouveau_object_template_results_members_following_scope', 20, 2 );
			bp_has_members( bp_ajax_querystring( 'members' ) );
			$results['scopes']['following'] = $GLOBALS['members_template']->total_member_count;
			remove_filter( 'bp_ajax_querystring', 'bp_nouveau_object_template_results_members_following_scope', 20, 2 );
		}
	}

	return $results;
}

add_filter( 'bp_nouveau_object_template_result', 'bp_nouveau_object_template_results_groups_tabs', 10, 2 );
/**
 * Object template results groups tabs.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_nouveau_object_template_results_groups_tabs( $results, $object ) {
	if ( 'groups' !== $object ) {
		return $results;
	}

	$results['scopes'] = array();

	add_filter( 'bp_ajax_querystring', 'bp_group_object_template_results_groups_all_scope', 20, 2 );
	bp_has_groups( bp_ajax_querystring( 'groups' ) );
	$results['scopes']['all'] = $GLOBALS['groups_template']->total_group_count;
	remove_filter( 'bp_ajax_querystring', 'bp_group_object_template_results_groups_all_scope', 20, 2 );

	add_filter( 'bp_ajax_querystring', 'bp_nouveau_object_template_results_groups_personal_scope', 20, 2 );
	bp_has_groups( bp_ajax_querystring( 'groups' ) );
	$results['scopes']['personal'] = $GLOBALS['groups_template']->total_group_count;
	remove_filter( 'bp_ajax_querystring', 'bp_nouveau_object_template_results_groups_personal_scope', 20, 2 );

	return $results;
}

/**
 * Object template results members personal scope.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_nouveau_object_template_results_members_personal_scope( $querystring, $object ) {
	if ( 'members' !== $object ) {
		return $querystring;
	}

	$querystring = wp_parse_args( $querystring );

	if ( bp_is_active( 'activity' ) && bp_is_activity_follow_active() ) {
		$counts = bp_total_follow_counts();
		if ( ! empty( $counts['following'] ) ) {
			if ( isset( $querystring['scope'] ) && 'following' === $querystring['scope'] ) {
				unset( $querystring['include'] );
			}
		}
	}

	$querystring['scope']    = 'personal';
	$querystring['page']     = 1;
	$querystring['per_page'] = '1';
	$querystring['user_id']  = ( bp_displayed_user_id() ) ? bp_displayed_user_id() : bp_loggedin_user_id();
	return http_build_query( $querystring );
}

/**
 * Object template results members following scope.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_nouveau_object_template_results_members_following_scope( $querystring, $object ) {
	if ( 'members' !== $object ) {
		return $querystring;
	}

	$querystring = wp_parse_args( $querystring );

	$args                             = array(
		'user_id' => ( bp_displayed_user_id() ) ? bp_displayed_user_id() : bp_loggedin_user_id(),
	);
	$following_comma_separated_string = bp_get_following_ids( $args );
	$querystring['include']           = $following_comma_separated_string;
	$querystring['scope']             = 'following';
	$querystring['page']              = 1;
	$querystring['per_page']          = '1';
	if ( isset( $querystring['user_id'] ) && ! empty( $querystring['user_id'] ) ) {
		unset( $querystring['user_id'] );
	}

	return http_build_query( $querystring );
}

/**
 * Object template results members groups personal scope.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_nouveau_object_template_results_groups_personal_scope( $querystring, $object ) {
	if ( 'groups' !== $object ) {
		return $querystring;
	}

	$querystring             = wp_parse_args( $querystring );
	$querystring['scope']    = 'personal';
	$querystring['page']     = 1;
	$querystring['per_page'] = '1';
	$querystring['user_id']  = ( bp_displayed_user_id() ) ? bp_displayed_user_id() : bp_loggedin_user_id();
	return http_build_query( $querystring );
}

add_action( 'wp_ajax_save_cover_position', 'bp_nouveau_ajax_save_cover_position' );

/**
 * Save Cover image position for group and member.
 *
 * @since BuddyBoss 1.5.1
 */
function bp_nouveau_ajax_save_cover_position() {

	if ( ! bp_is_post_request() ) {
		wp_send_json_error();
	}

	if ( ! isset( $_POST['position'] ) ) {
		wp_send_json_error();
	}

	$position = floatval( $_POST['position'] );
	$updated  = false;

	if ( bp_is_active( 'groups' ) && bp_is_group() ) {
		$updated = groups_update_groupmeta( bp_get_current_group_id(), 'bp_cover_position', $position );
	} else if ( bp_is_user() ) {
		$updated = bp_update_user_meta( bp_displayed_user_id(), 'bp_cover_position', $position );
	}

	if ( empty( $updated ) ) {
		wp_send_json_error();
	}

	$result['content'] = $position;

	wp_send_json_success( $result );
}

/**
 * Function which get next recepients list for block member in message section and message header.
 */
function bb_nouveau_next_recepient_list_for_blocks() {
	$post_data = filter_input( INPUT_POST, 'post_data', FILTER_SANITIZE_STRING, FILTER_REQUIRE_ARRAY );
	$user_id   = bp_loggedin_user_id() ? (int) bp_loggedin_user_id() : '';

	if ( ! isset( $post_data['thread_id'] ) ) {
		$response['message'] = new WP_Error( 'bp_error_get_recepient_list_for_blocks', esc_html__( 'Missing thread id.', 'buddyboss' ) );
		wp_send_json_error( $response );
	}

	if ( ! isset( $post_data['page_no'] ) ) {
		$response['message'] = new WP_Error( 'bp_error_get_recepient_list_for_blocks', esc_html__( 'Invalid page number.', 'buddyboss' ) );
		wp_send_json_error( $response );
	}

	// Get all admin ids.
	$adminstrator_ids = function_exists( 'bb_get_all_admin_users' ) ? bb_get_all_admin_users() : '';
	$moderation_type  = BP_Moderation_Members::$moderation_type;

	$args = array();
	if ( isset( $post_data['action'] ) && 'bp_load_more' === $post_data['action'] ) {
		$args['exclude_admin_user'] = $adminstrator_ids;
	}
	$args['page'] = (int) $post_data['page_no'];
	$thread       = new BP_Messages_Thread();
	$results      = $thread->get_pagination_recipients( $post_data['thread_id'], $args );

	if ( is_array( $results ) ) {
		$count          = 1;
		$recipients_arr = array();
		foreach ( $results as $recipient ) {
			if ( (int) $recipient->user_id !== $user_id ) {
				if ( empty( $recipient->is_deleted ) ) {
					$recipients_arr['members'][ $count ] = array(
						'avatar'     => esc_url(
							bp_core_fetch_avatar(
								array(
									'item_id' => $recipient->user_id,
									'object'  => 'user',
									'type'    => 'thumb',
									'width'   => BP_AVATAR_THUMB_WIDTH,
									'height'  => BP_AVATAR_THUMB_HEIGHT,
									'html'    => false,
								)
							)
						),
						'user_link'  => bp_core_get_userlink( $recipient->user_id, false, true ),
						'user_name'  => bp_core_get_user_displayname( $recipient->user_id ),
						'is_deleted' => empty( get_userdata( $recipient->user_id ) ) ? 1 : 0,
						'is_you'     => $recipient->user_id === bp_loggedin_user_id(),
						'id'         => $recipient->user_id,
					);
					if ( bp_is_active( 'moderation' ) ) {
						$recipients_arr['members'][ $count ]['is_blocked']     = bp_moderation_is_user_blocked( $recipient->user_id );
						$recipients_arr['members'][ $count ]['can_be_blocked'] = ( ! in_array( (int) $recipient->user_id, $adminstrator_ids, true ) && false === bp_moderation_is_user_suspended( $recipient->user_id ) ) ? true : false;
					}
					$count ++;
				}
			}
		}
	}
	$recipients_arr['moderation_type'] = $moderation_type;
	wp_send_json_success(
		array(
			'recipients' => $recipients_arr,
			'type'       => 'success',
		)
	);
	wp_die();
}

add_action( 'wp_ajax_next_recepient_list_for_blocks', 'bb_nouveau_next_recepient_list_for_blocks' );
add_action( 'wp_ajax_nopriv_next_recepient_list_for_blocks', 'bb_nouveau_next_recepient_list_for_blocks' );
