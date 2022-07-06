<?php
/**
 * Filters related to the Moderation component.
 *
 * @since   BuddyBoss 1.5.6
 * @package BuddyBoss\Moderation
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Load Moderation component after plugin loaded.
 */
function bb_moderation_load() {
	new BP_Core_Suspend();
	new BP_Moderation_Members();
	new BP_Moderation_Comment();

	if ( bp_is_active( 'activity' ) ) {
		new BP_Moderation_Activity();
		new BP_Moderation_Activity_Comment();
	}

	if ( bp_is_active( 'groups' ) ) {
		new BP_Moderation_Groups();
	}

	if ( bp_is_active( 'forums' ) ) {
		new BP_Moderation_Forums();
		new BP_Moderation_Forum_Topics();
		new BP_Moderation_Forum_Replies();
	}

	if ( bp_is_active( 'document' ) ) {
		new BP_Moderation_Folder();
		new BP_Moderation_Document();
	}

	if ( bp_is_active( 'media' ) ) {
		new BP_Moderation_Album();
		new BP_Moderation_Media();
	}

	if ( bp_is_active( 'video' ) ) {
		new BP_Moderation_Video();
	}

	if ( bp_is_active( 'messages' ) ) {
		new BP_Moderation_Message();
	}

	/**
	 * Handle notification.
	 *
	 * @since BuddyBoss 2.0.3
	 */
	if ( bp_is_active( 'notifications' ) ) {
		new BP_Moderation_Notification();
	}
}

add_action( 'bp_init', 'bb_moderation_load', 1 );

/**
 * Update modebypass Param
 *
 * @since BuddyBoss 1.5.6
 *
 * @param Array $params Array of key/value pairs for AJAX usage.
 */
function bp_moderation_js_strings( $params ) {
	$params['modbypass'] = filter_input( INPUT_GET, 'modbypass', FILTER_SANITIZE_NUMBER_INT );

	return $params;
}

add_filter( 'bp_core_get_js_strings', 'bp_moderation_js_strings' );

/**
 * Update modebypass Param for wp redirect
 *
 * @since BuddyBoss 1.5.6
 *
 * @param Array $location Url to redirect
 */
function bp_moderation_wp_redirect( $location ) {
	$modbypass = filter_input( INPUT_GET, 'modbypass', FILTER_SANITIZE_NUMBER_INT );
	if ( ! empty( $modbypass ) ) {

		$query_str = parse_url( $location, PHP_URL_QUERY );
		parse_str( $query_str, $params );

		$params['modbypass'] = $modbypass;

		if ( ! empty( $params ) ) {
			$location = add_query_arg( $params, $location );
		}
	}

	return $location;
}

add_filter( 'wp_redirect', 'bp_moderation_wp_redirect' );

/**
 * Function to handle frontend report form submission.
 *
 * @since BuddyBoss 1.5.6
 */
function bp_moderation_content_report() {
	$response = array(
		'message' => '',
	);

	$nonce     = filter_input( INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING );
	$item_id   = filter_input( INPUT_POST, 'content_id', FILTER_SANITIZE_NUMBER_INT );
	$item_type = filter_input( INPUT_POST, 'content_type', FILTER_SANITIZE_STRING );
	$category  = filter_input( INPUT_POST, 'report_category', FILTER_SANITIZE_STRING );
	if ( 'other' !== $category ) {
		$category = filter_input( INPUT_POST, 'report_category', FILTER_SANITIZE_NUMBER_INT );
	}
	$item_note = filter_input( INPUT_POST, 'note', FILTER_SANITIZE_STRING );

	if ( empty( $item_id ) || empty( $item_type ) || empty( $category ) ) {
		$response['message'] = new WP_Error(
			'bp_moderation_missing_data',
			esc_html__( 'Required field missing.', 'buddyboss' )
		);
		wp_send_json_error( $response );
	}

	$reports_terms = get_terms(
		'bpm_category',
		array(
			'hide_empty' => false,
			'fields'     => 'ids',
		)
	);

	if (
		( 'other' === $category && empty( $item_note ) ) ||
		( 'other' !== $category && ! in_array( (int) $category, $reports_terms, true ) )
	) {
		$response['message'] = new WP_Error(
			'bp_moderation_missing_data',
			esc_html__( 'Please specify reason to report this content.', 'buddyboss' )
		);
		wp_send_json_error( $response );
	}

	// Check the current has access to report the item ot not.
	$user_can = bp_moderation_user_can( $item_id, $item_type );
	if ( false === (bool) $user_can ) {
		$response['message'] = new WP_Error(
			'bp_moderation_invalid_access',
			esc_html__( 'Sorry, you are not allowed to report this content.', 'buddyboss' )
		);
		wp_send_json_error( $response );
	}

	/**
	 * If Sub item id and sub type is empty then actual item is reported otherwise Connected item will be reported
	 * Like For Forum create activity, When reporting Activity it'll report actual forum
	 */
	$sub_items     = bp_moderation_get_sub_items( $item_id, $item_type );
	$item_sub_id   = isset( $sub_items['id'] ) ? $sub_items['id'] : $item_id;
	$item_sub_type = isset( $sub_items['type'] ) ? $sub_items['type'] : $item_type;

	if ( bp_moderation_report_exist( $item_sub_id, $item_sub_type ) ) {
		$response['message'] = new WP_Error(
			'bp_moderation_already_reported',
			esc_html__( 'You have already reported this ', 'buddyboss' ) . esc_attr__( $item_sub_type )
		);
		wp_send_json_error( $response );
	}

	if ( wp_verify_nonce( $nonce, 'bp-moderation-content' ) && ! is_wp_error( $response['message'] ) ) {
		$moderation = bp_moderation_add(
			array(
				'content_id'   => $item_sub_id,
				'content_type' => $item_sub_type,
				'category_id'  => $category,
				'note'         => $item_note,
			)
		);

		if ( ! empty( $moderation->id ) && ! empty( $moderation->report_id ) ) {
			$response['moderation'] = $moderation;

			$button_args = array(
				'button_attr' => array(
					'data-bp-content-id'   => $item_id,
					'data-bp-content-type' => $item_type,
				),
			);

			$response['button'] = bp_moderation_get_report_button( $button_args, false );
		}

		$response['message'] = $moderation->errors;
	}

	if ( empty( $response['message'] ) ) {
		$response['message'] = new WP_Error( 'bp_moderation_missing_error', esc_html__( 'Something went wrong. Please try again.', 'buddyboss' ) );
		wp_send_json_error( $response );
	}

	wp_send_json_success( $response );
	exit();
}

add_action( 'wp_ajax_bp_moderation_content_report', 'bp_moderation_content_report' );
add_action( 'wp_ajax_nopriv_bp_moderation_content_report', 'bp_moderation_content_report' );


/**
 * Function to handle frontend block member form submission.
 *
 * @since BuddyBoss 1.5.6
 */
function bp_moderation_block_member() {
	$response = array(
		'message'  => '',
		'redirect' => '',
	);

	$nonce   = filter_input( INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING );
	$item_id = filter_input( INPUT_POST, 'content_id', FILTER_SANITIZE_NUMBER_INT );

	// Member Report only
	$reported  = filter_input( INPUT_POST, 'reported', FILTER_SANITIZE_NUMBER_INT );
	$category  = filter_input( INPUT_POST, 'report_category', FILTER_SANITIZE_STRING );

	if ( empty( $item_id ) ) {
		$response['message'] = new WP_Error( 'bp_moderation_missing_data', esc_html__( 'Required field missing.', 'buddyboss' ) );
		wp_send_json_error( $response );
	}

	if ( bp_moderation_report_exist( $item_id, BP_Moderation_Members::$moderation_type ) ) {
		$response['message'] = new WP_Error( 'bp_moderation_already_reported', esc_html__( 'You have already reported this Member', 'buddyboss' ) );
		wp_send_json_error( $response );
	}

	if ( (int) bp_loggedin_user_id() === (int) $item_id ) {
		$response['message'] = new WP_Error( 'bp_moderation_invalid_item_id', esc_html__( 'Sorry, you can not allowed to block yourself.', 'buddyboss' ) );
		wp_send_json_error( $response );
	}

	// Check the current has access to report the item ot not.
	$user_can = bp_moderation_user_can( $item_id, BP_Moderation_Members::$moderation_type );
	if ( false === (bool) $user_can ) {
		$response['message'] = new WP_Error(
			'bp_moderation_invalid_access',
			esc_html__( 'Sorry, you are not allowed to block this member.', 'buddyboss' )
		);
		wp_send_json_error( $response );
	}

	if ( wp_verify_nonce( $nonce, 'bp-moderation-content' ) && ! is_wp_error( $response['message'] ) ) {
		$moderation = bp_moderation_add(
			array(
				'content_id'   => $item_id,
				'content_type' => BP_Moderation_Members::$moderation_type,
				'note'         => !empty( $reported ) ? esc_html__( 'Member report', 'buddyboss' ) : esc_html__( 'Member block', 'buddyboss' ),
				'category_id'  => !empty( $category ) ? $category : 0,
				'user_report'  => !empty( $reported ) ? 1 : 0,
			)
		);

		if ( ! empty( $moderation->id ) && ! empty( $moderation->report_id ) ) {
			$response['moderation'] = $moderation;

			$friend_status = function_exists( 'bp_is_friend' ) && bp_is_active( 'friends' ) ? bp_is_friend( $item_id ) : array();
			if ( ! empty( $friend_status ) && in_array( $friend_status, array( 'is_friend', 'pending', 'awaiting_response' ), true ) ) {
				friends_remove_friend( bp_loggedin_user_id(), $item_id );
			}
			if ( bp_is_following(
				array(
					'leader_id'   => $item_id,
					'follower_id' => bp_loggedin_user_id(),
				)
			) ) {
				bp_stop_following(
					array(
						'leader_id'   => $item_id,
						'follower_id' => bp_loggedin_user_id(),
					)
				);
			}

			$response['button'] = bp_moderation_get_report_button(
				array(
					'button_attr' => array(
						'data-bp-content-id'   => $item_id,
						'data-bp-content-type' => BP_Moderation_Members::$moderation_type,
					),
				),
				false
			);

			$response['redirect'] = trailingslashit( bp_loggedin_user_domain() . bp_get_settings_slug() ) . '/blocked-members';
		}

		$response['message'] = $moderation->errors;
	}

	if ( empty( $response['message'] ) ) {
		$response['message'] = new WP_Error( 'bp_moderation_missing_error', esc_html__( 'Something went wrong. Please try again.', 'buddyboss' ) );
		wp_send_json_error( $response );
	}

	wp_send_json_success( $response );
	exit();

}

add_action( 'wp_ajax_bp_moderation_block_member', 'bp_moderation_block_member' );
add_action( 'wp_ajax_nopriv_bp_moderation_block_member', 'bp_moderation_block_member' );

/**
 * Function to handle frontend unblock user request.
 *
 * @since BuddyBoss 1.5.6
 */
function bp_moderation_unblock_user() {
	$response = array(
		'success' => false,
		'message' => '',
	);

	$nonce   = filter_input( INPUT_POST, 'nonce', FILTER_SANITIZE_STRING );
	$item_id = filter_input( INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT );

	if ( empty( $item_id ) ) {
		$response['message'] = new WP_Error( 'bp_moderation_missing_data', esc_html__( 'Required field missing.', 'buddyboss' ) );
		wp_send_json_error( $response );
	}

	if ( ! bp_moderation_report_exist( $item_id, BP_Moderation_Members::$moderation_type ) ) {
		$response['message'] = new WP_Error( 'bp_moderation_not_exit', esc_html__( 'Reported content was not found.', 'buddyboss' ) );
		wp_send_json_error( $response );
	}

	$moderation = new BP_Moderation( $item_id, BP_Moderation_Members::$moderation_type );

	if ( empty( $moderation ) || is_wp_error( $moderation ) || true === $moderation->hide_sitewide ) {
		$response['message'] = new WP_Error( 'bp_rest_invalid_id', esc_html__( 'Sorry, you cannot unblock suspended member.', 'buddyboss' ) );
		wp_send_json_error( $response );
	}

	if ( wp_verify_nonce( $nonce, 'bp-unblock-user' ) && ! is_wp_error( $response['message'] ) ) {
		$moderation = bp_moderation_delete(
			array(
				'content_id'   => $item_id,
				'content_type' => BP_Moderation_Members::$moderation_type,
			)
		);

		if ( empty( $moderation->report_id ) ) {
			$response['success'] = true;
			$response['message'] = esc_html__( 'User unblocked successfully', 'buddyboss' );
		}
	}

	if ( empty( $response['success'] ) && empty( $response['message'] ) ) {
		$response['message'] = new WP_Error( 'bp_moderation_block_error', esc_html__( 'Something went wrong. Please try again.', 'buddyboss' ) );
		wp_send_json_error( $response );
	}

	wp_send_json_success( $response );
	exit();
}

add_action( 'wp_ajax_bp_moderation_unblock_user', 'bp_moderation_unblock_user' );
add_action( 'wp_ajax_nopriv_bp_moderation_unblock_user', 'bp_moderation_unblock_user' );

/**
 * Function to handle moderation request from Backend.
 *
 * @since BuddyBoss 1.5.6
 */
function bp_moderation_content_actions_request() {
	$response = array(
		'success' => false,
		'message' => '',
	);

	$nonce      = filter_input( INPUT_POST, 'nonce', FILTER_SANITIZE_STRING );
	$item_type  = filter_input( INPUT_POST, 'type', FILTER_SANITIZE_STRING );
	$sub_action = filter_input( INPUT_POST, 'sub_action', FILTER_SANITIZE_STRING );
	$item_id    = filter_input( INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT );

	if ( empty( $item_id ) || empty( $item_type ) ) {
		$response['message'] = new WP_Error( 'bp_moderation_missing_data', esc_html__( 'Required field missing.', 'buddyboss' ) );
		wp_send_json_error( $response );
	}

	// Check the current has access to report the item ot not.
	$user_can = bp_moderation_can_report( $item_id, $item_type, ( 'hide' === $sub_action ) ) || current_user_can( 'administrator' );
	if ( ! current_user_can( 'manage_options' ) || false === (bool) $user_can ) {
		$response['message'] = new WP_Error( 'bp_moderation_invalid_access', esc_html__( 'Sorry, you are not allowed to report this content.', 'buddyboss' ) );
		wp_send_json_error( $response );
	}

	if ( wp_verify_nonce( $nonce, 'bp-hide-unhide-moderation' ) && ! is_wp_error( $response['message'] ) ) {
		if ( 'hide' === $sub_action ) {
			$moderation = bp_moderation_hide(
				array(
					'content_id'   => $item_id,
					'content_type' => $item_type,
				)
			);
			if ( 1 === $moderation->hide_sitewide ) {
				$response['success'] = true;
				$response['message'] = esc_html__( 'Content has been successfully hidden.', 'buddyboss' );
			}
		} else {
			$moderation = bp_moderation_unhide(
				array(
					'content_id'   => $item_id,
					'content_type' => $item_type,
				)
			);
			if ( 0 === $moderation->hide_sitewide ) {
				$response['success'] = true;
				$response['message'] = esc_html__( 'Content has been successfully unhidden.', 'buddyboss' );
			}
		}
	}

	if ( empty( $response['success'] ) && empty( $response['message'] ) ) {
		$response['message'] = new WP_Error( 'bp_moderation_content_actions_request', esc_html__( 'Something went wrong. Please try again.', 'buddyboss' ) );
		wp_send_json_error( $response );
	}

	wp_send_json_success( $response );
	exit();
}

add_action( 'wp_ajax_bp_moderation_content_actions_request', 'bp_moderation_content_actions_request' );
add_action( 'wp_ajax_nopriv_bp_moderation_content_actions_request', 'bp_moderation_content_actions_request' );

/**
 * Function to handle moderation request for user from backend.
 *
 * @since BuddyBoss 1.5.6
 */
function bp_moderation_user_actions_request() {
	$response = array(
		'success' => false,
		'message' => '',
	);

	$nonce      = filter_input( INPUT_POST, 'nonce', FILTER_SANITIZE_STRING );
	$item_type  = filter_input( INPUT_POST, 'type', FILTER_SANITIZE_STRING );
	$sub_action = filter_input( INPUT_POST, 'sub_action', FILTER_SANITIZE_STRING );
	$item_id    = filter_input( INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT );

	if ( empty( $item_id ) || empty( $item_type ) ) {
		$response['message'] = new WP_Error( 'bp_moderation_user_missing_data', esc_html__( 'Required field missing.', 'buddyboss' ) );
		wp_send_json_error( $response );
	}

	// Check the current has access to report the item ot not.
	$user_can = bp_moderation_can_report( $item_id, $item_type );
	if ( ! current_user_can( 'manage_options' ) || false === (bool) $user_can ) {
		$response['message'] = new WP_Error( 'bp_moderation_invalid_access', esc_html__( 'Sorry, you are not allowed to report this content.', 'buddyboss' ) );
		wp_send_json_error( $response );
	}

	if ( wp_verify_nonce( $nonce, 'bp-hide-unhide-moderation' ) && ! is_wp_error( $response['message'] ) ) {

		if ( 'suspend' === $sub_action ) {
			BP_Suspend_Member::suspend_user( $item_id );
			$response['success'] = true;
			$response['message'] = esc_html__( 'Member has been successfully suspended.', 'buddyboss' );
		} elseif ( 'unsuspend' === $sub_action ) {
			BP_Suspend_Member::unsuspend_user( $item_id );
			$response['success'] = true;
			$response['message'] = esc_html__( 'Member has been successfully unsuspended.', 'buddyboss' );
		}
	}

	if ( empty( $response['success'] ) && empty( $response['message'] ) ) {
		$response['message'] = new WP_Error( 'bp_moderation_user_missing_data', esc_html__( 'Something went wrong. Please try again.', 'buddyboss' ) );
		wp_send_json_error( $response );
	}

	wp_send_json_success( $response );
	exit();
}

add_action( 'wp_ajax_bp_moderation_user_actions_request', 'bp_moderation_user_actions_request' );
add_action( 'wp_ajax_nopriv_bp_moderation_user_actions_request', 'bp_moderation_user_actions_request' );

/**
 * Function to Popup markup for moderation content report
 *
 * @since BuddyBoss 1.5.6
 */
function bb_moderation_content_report_popup() {

	if ( file_exists( buddypress()->core->path . 'bp-moderation/screens/content-report-form.php' ) ) {
		include buddypress()->core->path . 'bp-moderation/screens/content-report-form.php';
	}
	if ( file_exists( buddypress()->core->path . 'bp-moderation/screens/block-member-form.php' ) ) {
		include buddypress()->core->path . 'bp-moderation/screens/block-member-form.php';
	}
	if ( file_exists( buddypress()->core->path . 'bp-moderation/screens/reported-content-popup.php' ) ) {
		include buddypress()->core->path . 'bp-moderation/screens/reported-content-popup.php';
	}
}

add_action( 'wp_footer', 'bb_moderation_content_report_popup' );

/**
 * Function to add the block user button in customizer section
 *
 * @since BuddyBoss 1.5.6
 *
 * @param array $buttons buttons array.
 *
 * @return mixed
 */
function bp_moderation_block_user_profile_button( $buttons ) {

	if ( bp_is_active( 'moderation' ) && bp_is_moderation_member_blocking_enable() ) {
		$buttons['member_report'] = __( 'Block', 'buddyboss' );
	}

	return $buttons;
}

add_filter( 'bp_nouveau_customizer_user_profile_actions', 'bp_moderation_block_user_profile_button' );

/**
 * Removed Moderation report entries after the suspend record delete.
 *
 * @since BuddyBoss 1.5.6
 *
 * @param object $recode Suspended record object.
 */
function bb_moderation_suspend_after_delete( $recode ) {

	if ( empty( $recode ) ) {
		return;
	}

	BP_Moderation::delete_moderation_by_id( $recode->id );

}
add_action( 'suspend_after_delete', 'bb_moderation_suspend_after_delete' );

/**
 * Function to clear the cache data on item suspend.
 *
 * @since BuddyBoss 1.6.2
 *
 * @param array $moderation_data moderation item data.
 */
function bb_moderation_clear_suspend_cache( $moderation_data ) {
	if ( empty( $moderation_data['item_type'] ) || empty( $moderation_data['item_id'] ) ) {
		return;
	}

	// Needs to flush all cache with other component as well.
	wp_cache_flush();

	// wp_cache_delete( 'bb_check_moderation_' . $moderation_data['item_type'] . '_' . $moderation_data['item_id'], 'bp_moderation' );
	// wp_cache_delete( 'bb_check_hidden_content_' . $moderation_data['item_type'] . '_' . $moderation_data['item_id'], 'bp_moderation' );
	// wp_cache_delete( 'bb_check_suspended_content_' . $moderation_data['item_type'] . '_' . $moderation_data['item_id'], 'bp_moderation' );
	// wp_cache_delete( 'bb_check_user_suspend_user_' . $moderation_data['item_type'] . '_' . md5( serialize( $moderation_data['item_id'] ) ), 'bp_moderation' );
	// wp_cache_delete( 'bb_get_recode_' . $moderation_data['item_type'] . '_' . $moderation_data['item_id'], 'bp_moderation' );
	// wp_cache_delete( 'bb_get_specific_moderation_' . $moderation_data['item_type'] . '_' . $moderation_data['item_id'], 'bp_moderation' );
}

add_action( 'bb_suspend_before_add_suspend', 'bb_moderation_clear_suspend_cache' );
add_action( 'bb_suspend_before_remove_suspend', 'bb_moderation_clear_suspend_cache' );

add_action( 'bb_suspend_before_add_suspend', 'bp_core_clear_cache' );
add_action( 'bb_suspend_before_remove_suspend', 'bp_core_clear_cache' );

/**
 * Function to clear cache on suspend item delete.
 *
 * @since BuddyBoss 1.6.2
 *
 * @param object $suspend_record suspend item record.
 */
function bb_moderation_clear_delete_cache( $suspend_record ) {

	if ( ! empty( $suspend_record->id ) ) {
		wp_cache_delete( 'bb_suspend_' . $suspend_record->id, 'bp_moderation' );
	}

	if ( empty( $suspend_record->item_type ) || empty( $suspend_record->item_id ) ) {
		return;
	}

	// Needs to flush all cache with other component as well.
	wp_cache_flush();

	// wp_cache_delete( 'bb_check_moderation_' . $suspend_record->item_type . '_' . $suspend_record->item_id, 'bp_moderation' );
	// wp_cache_delete( 'bb_check_hidden_content_' . $suspend_record->item_type . '_' . $suspend_record->item_id, 'bp_moderation' );
	// wp_cache_delete( 'bb_check_suspended_content_' . $suspend_record->item_type . '_' . $suspend_record->item_id, 'bp_moderation' );
	// wp_cache_delete( 'bb_check_user_suspend_user_' . $suspend_record->item_type . '_' . md5( serialize( $suspend_record->user_id ) ), 'bp_moderation' );
	// wp_cache_delete( 'bb_get_recode_' . $suspend_record->item_type . '_' . $suspend_record->item_id, 'bp_moderation' );
}

add_action( 'bp_moderation_after_save', 'bb_moderation_clear_delete_cache' );
add_action( 'suspend_after_delete', 'bb_moderation_clear_delete_cache' );
add_action( 'bp_moderation_after_hide', 'bb_moderation_clear_delete_cache' );
add_action( 'bp_moderation_after_unhide', 'bb_moderation_clear_delete_cache' );

add_action( 'bp_moderation_after_save', 'bp_core_clear_cache' );
add_action( 'suspend_after_delete', 'bp_core_clear_cache' );
add_action( 'bp_moderation_after_hide', 'bp_core_clear_cache' );
add_action( 'bp_moderation_after_unhide', 'bp_core_clear_cache' );

/**
 * Function to clear cache when item hide/unhide
 *
 * @since BuddyBoss 1.6.2
 *
 * @param string $content_type content type.
 * @param int    $content_id   content id.
 * @param array  $args         item arguments.
 */
function bb_moderation_clear_status_change_cache( $content_type, $content_id, $args ) {
	if ( empty( $content_type ) || empty( $content_id ) ) {
		return;
	}

	// Needs to flush all cache with other component as well.
	wp_cache_flush();

	// wp_cache_delete( 'bb_check_moderation_' . $content_type . '_' . $content_id, 'bp_moderation' );
	// wp_cache_delete( 'bb_check_hidden_content_' . $content_type . '_' . $content_id, 'bp_moderation' );
	// wp_cache_delete( 'bb_check_suspended_content_' . $content_type . '_' . $content_id, 'bp_moderation' );
	// wp_cache_delete( 'bb_get_recode_' . $content_type . '_' . $content_id, 'bp_moderation' );
	// wp_cache_delete( 'bb_check_user_suspend_user_' . $content_type . '_' . md5( serialize( $content_id ) ), 'bp_moderation' );
	// wp_cache_delete( 'bb_is_content_reported_hidden_' . $content_type . '_' . $content_id, 'bp_moderation' );
	//
	// $blocked_user = ! empty( $args['blocked_user'] ) ? $args['blocked_user'] : '';
	// if ( ! empty( $blocked_user ) ) {
	// wp_cache_delete( 'bb_check_blocked_user_content_' . $blocked_user . '_' . $content_type . '_' . $content_id, 'bp_moderation' );
	// }
}

add_action( 'bb_suspend_hide_before', 'bb_moderation_clear_status_change_cache', 10, 3 );
add_action( 'bb_suspend_unhide_before', 'bb_moderation_clear_status_change_cache', 10, 3 );

add_action( 'bb_suspend_hide_before', 'bp_core_clear_cache' );
add_action( 'bb_suspend_unhide_before', 'bp_core_clear_cache' );

/**
 * Add moderation repair list.
 *
 * @param array $repair_list Repair list.
 *
 * @since BuddyBoss 1.7.5
 *
 * @return array Repair list items.
 */
function bb_moderation_migrate_old_data( $repair_list ) {
	$repair_list[] = array(
		'bp-repair-moderation-data',
		esc_html__( 'Repair moderation data', 'buddyboss' ),
		'bb_moderation_admin_repair_old_moderation_data',
	);

	return $repair_list;
}

add_filter( 'bp_repair_list', 'bb_moderation_migrate_old_data' );

/**
 * Function to admin repair tool for fix moderation data.
 *
 * @since BuddyBoss 1.7.5
 *
 * @return array
 */
function bb_moderation_admin_repair_old_moderation_data() {
	global $wpdb;
	$suspend_table            = "{$wpdb->prefix}bp_suspend";
	$offset                   = isset( $_POST['offset'] ) ? (int) ( $_POST['offset'] ) : 0;
	$sql_offset               = $offset - 1;
	$moderated_activities_sql = $wpdb->prepare( "SELECT id,item_id,item_type FROM {$suspend_table} WHERE item_type IN ('media','video','document') GROUP BY id ORDER BY id DESC LIMIT 10 OFFSET %d", $sql_offset );
	$moderated_activities     = $wpdb->get_results( $moderated_activities_sql );

	if ( ! empty( $moderated_activities ) ) {
		$offset          = bb_moderation_update_suspend_data( $moderated_activities, $offset );
		$records_updated = sprintf( __( '%s moderation item updated successfully.', 'buddyboss' ), bp_core_number_format( $offset ) );

		return array(
			'status'  => 'running',
			'offset'  => $offset,
			'records' => $records_updated,
		);
	} else {
		return array(
			'status'  => 1,
			'message' => __( 'Repairing moderation data &hellip; Complete!', 'buddyboss' ),
		);
	}
}

/**
 * Added magnific popup as dependencies.
 *
 * @param array $js_handles Array of handles.
 *
 * @return array|mixed
 */
function bp_moderation_get_js_dependencies( $js_handles = array() ) {
	if ( bp_is_active( 'forums' ) && ( bbp_is_single_topic() || bbp_is_single_forum() ) ) { // Topic or forum detail page.
		$js_handles[] = 'bp-nouveau-magnific-popup';
	}

	return $js_handles;
}

add_filter( 'bp_core_get_js_dependencies', 'bp_moderation_get_js_dependencies', 10, 1 );

/**
 * Check the user blocked/suspended or not?
 *
 * @since BuddyBoss 2.0.3
 *
 * @param bool $retval  Default false.
 * @param int  $item_id Blocking User ID.
 * @param int  $user_id Blocked User ID.
 *
 * @return bool True if the user blocked/suspended otherwise false.
 */
function bb_moderation_is_recipient_moderated( $retval, $item_id, $user_id ) {
	if ( bp_moderation_is_user_blocked( $user_id, $item_id ) ) {
		return true;
	} elseif ( bp_moderation_is_user_suspended( $item_id ) ) {
		return true;
	}

	return (bool) $retval;
}
add_filter( 'bb_is_recipient_moderated', 'bb_moderation_is_recipient_moderated', 10, 3 );

/**
 * Add show when reporting field in reporting categories add page.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $taxonomy  Reporting category taxonomy.
 *
 * @return mixed Show when Reporting field.
 */
function bpm_category_add_term_fields_show_when_reporting( $taxonomy ) {
	?>
	<div class="form-field">
		<label for="bpm_category_show_when_reporting"><?php esc_html_e( 'Show When Reporting', 'buddyboss' ); ?></label>
		<select name="bpm_category_show_when_reporting" id="bpm_category_show_when_reporting">
			<option value="content"><?php esc_html_e( 'Content', 'buddyboss' ); ?></option>
			<option value="members"><?php esc_html_e( 'Members', 'buddyboss' ); ?></option>
			<option value="content_members"><?php esc_html_e( 'Content & Members', 'buddyboss' ); ?></option>
		</select>
	</div>
	<?php
}
add_action( 'bpm_category_add_form_fields', 'bpm_category_add_term_fields_show_when_reporting' );

/**
 * Add show when reporting field in reporting categories edit page.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param object $term  Reporting category object.
 * @param string $taxonomy  Reporting category taxonomy.
 *
 * @return mixed Show when Reporting field.
 */
function bpm_category_edit_term_fields_show_when_reporting( $term, $taxonomy ) {
	$value = get_term_meta( $term->term_id, 'bpm_category_show_when_reporting', true );
	?>
	<tr class="form-field">
		<th>
			<label for="bpm_category_show_when_reporting"><?php esc_html_e( 'Show When Reporting', 'buddyboss' ); ?></label>
		</th>
		<td>
			<select name="bpm_category_show_when_reporting" id="bpm_category_show_when_reporting">
				<option value="content" <?php echo 'content' === $value ? 'selected' : ''; ?>><?php esc_html_e( 'Content', 'buddyboss' ); ?></option>
				<option value="members" <?php echo 'members' === $value ? 'selected' : ''; ?>><?php esc_html_e( 'Members', 'buddyboss' ); ?></option>
				<option value="content_members" <?php echo 'content_members' === $value ? 'selected' : ''; ?>><?php esc_html_e( 'Content & Members', 'buddyboss' ); ?></option>
			</select>
		</td>
	</tr>
	<?php
}
add_action( 'bpm_category_edit_form_fields', 'bpm_category_edit_term_fields_show_when_reporting', 10, 2 );

/**
 * Save show when reporting field in reporting categories.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param int $term_id  Show when reporting field term ID.
 */
function bpm_category_save_term_fields_show_when_reporting( $term_id ) {

	if ( isset( $_POST['bpm_category_show_when_reporting'] ) ) { // phpcs:ignore
		update_term_meta(
			$term_id,
			'bpm_category_show_when_reporting',
			sanitize_text_field( wp_unslash( $_POST['bpm_category_show_when_reporting'] ) ) // phpcs:ignore
		);
	}
}
add_action( 'created_bpm_category', 'bpm_category_save_term_fields_show_when_reporting' );
add_action( 'edited_bpm_category', 'bpm_category_save_term_fields_show_when_reporting' );

/**
 * Register columns for our taxonomy.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array $columns List of columns for Reporting categort taxonomy.
 *
 * @return array $columns List of columns for Reporting categort taxonomy
 */
function bpm_category_show_when_reporting_columns( $columns ) {
	unset($columns['slug']);
	unset($columns['posts']);
	$columns['bpm_category_show_when_reporting'] = __( 'Show When Reporting', 'buddyboss' );
	return $columns;
}
add_filter( 'manage_edit-bpm_category_columns', 'bpm_category_show_when_reporting_columns' );

/**
 * Retrieve value for our custom column
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $string      Blank string.
 * @param string $column_name Name of the column.
 * @param int    $term_id     Term ID.
 *
 * @return mixed Term meta data
 */
function bpm_category_show_when_reporting_column_display( $string = '', $column_name, $term_id ) {
	$value = get_term_meta( $term_id, $column_name, true );
	switch ( $value ) {
		case 'members':
			return esc_html__( 'Members', 'buddyboss' );
		case 'content_members':
			return esc_html__( 'Content & Members', 'buddyboss' );
		default:
			return esc_html__( 'Content', 'buddyboss' );
	}
}
add_filter( 'manage_bpm_category_custom_column', 'bpm_category_show_when_reporting_column_display', 10, 3 );

/**
 * Display markup or template for custom field
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $column_name Column being shown.
 * @param string $screen Post type being shown.
 *
 * @return mixed
 */
function bb_quick_edit_bpm_category_show_when_reporting_field( $column_name, $screen ) {
	// If we're not iterating over our custom column, then skip.
	if ( 'bpm_category' !== $screen && 'bpm_category_show_when_reporting' !== $column_name ) {
		return false;
	}
	?>
	<fieldset>
		<div id="bpm_category_show_when_reporting" class="inline-edit-col">
			<label>
				<span class="title"><?php esc_html_e( 'Show When Reporting', 'buddyboss' ); ?></span>
				<span class="input-text-wrap">
					<select name="bpm_category_show_when_reporting" id="bpm_category_show_when_reporting">
						<option value="content"><?php esc_html_e( 'Content', 'buddyboss' ); ?></option>
						<option value="members"><?php esc_html_e( 'Members', 'buddyboss' ); ?></option>
						<option value="content_members"><?php esc_html_e( 'Content & Members', 'buddyboss' ); ?></option>
					</select>
				</span>
			</label>
		</div>
	</fieldset>
	<?php
}
add_action( 'quick_edit_custom_box', 'bb_quick_edit_bpm_category_show_when_reporting_field', 10, 2 );


/**
 * Front-end stuff for pulling in user-input values dynamically into our input field.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_quickedit_bpm_category_show_when_reporting_javascript() {
	$current_screen = get_current_screen();

	if ( 'edit-bpm_category' !== $current_screen->id || 'bpm_category' !== $current_screen->taxonomy ) {
		return;
	}

	// Ensure jQuery library is loaded.
	wp_enqueue_script( 'jquery' );
	?>
	<script type="text/javascript">
		/*global jQuery*/
		jQuery(function($) {
			$('span:contains("Slug")').each(function (i) {
                $(this).parent().remove();
            });
			$('#the-list').on('click', 'button.editinline', function(e) {
				e.preventDefault();
				var $tr = $(this).closest('tr');
				var val = $tr.find('td.bpm_category_show_when_reporting').text();
				if( val != '') {
					$('tr.inline-edit-row select[name="bpm_category_show_when_reporting"] option').removeAttr('selected');
					$('tr.inline-edit-row select[name="bpm_category_show_when_reporting"] option').filter(function() {
						return this.text == val; 
					}).attr('selected', 'selected');
				}
			});
		});
	</script>
	<?php
}
add_action( 'admin_print_footer_scripts-edit-tags.php', 'bb_quickedit_bpm_category_show_when_reporting_javascript' );

/**
 * Added style to hide slug field from add/edit forms.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_quickedit_bpm_category_hide_slug_style() {
	$current_screen = get_current_screen();

	error_log(print_r($current_screen, true));
	if ( 'edit-bpm_category' !== $current_screen->id && 'bpm_category' !== $current_screen->taxonomy ) {
		return;
	}
	?>
	<style type="text/css">
		.term-slug-wrap { display: none;}
	</style>
	<?php
}
add_action( 'admin_print_footer_scripts-edit-tags.php', 'bb_quickedit_bpm_category_hide_slug_style' );
add_action( 'admin_print_footer_scripts-term.php', 'bb_quickedit_bpm_category_hide_slug_style' );


add_action( 'bp_moderation_user_report_report_content_type', 'bp_moderation_user_report_content_type', 10, 2 );

/**
 * Function to change member report type.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $content_type Button text.
 * @param int    $item_id     Item id.
 *
 * @return string
 */
function bp_moderation_user_report_content_type( $content_type, $item_id ) {
	return esc_html__( 'Member', 'buddyboss' );
}
