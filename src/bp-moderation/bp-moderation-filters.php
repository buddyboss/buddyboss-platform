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

	$nonce     = bb_filter_input_string( INPUT_POST, '_wpnonce' );
	$item_id   = filter_input( INPUT_POST, 'content_id', FILTER_SANITIZE_NUMBER_INT );
	$item_type = bb_filter_input_string( INPUT_POST, 'content_type' );
	$category  = bb_filter_input_string( INPUT_POST, 'report_category' );
	if ( 'other' !== $category ) {
		$category = filter_input( INPUT_POST, 'report_category', FILTER_SANITIZE_NUMBER_INT );
	}
	$item_note = bb_filter_input_string( INPUT_POST, 'note' );

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
			sprintf(
				/* translators: Item type to reported. */
				esc_html__( 'You have already reported this %s', 'buddyboss' ),
				esc_attr( $item_sub_type )
			)
		);
		wp_send_json_error( $response );
	}

	if ( wp_verify_nonce( $nonce, 'bp-moderation-content' ) && ! is_wp_error( $response['message'] ) ) {
		$args = array(
			'content_id'   => $item_sub_id,
			'content_type' => $item_sub_type,
			'category_id'  => $category,
			'note'         => $item_note,
		);

		if ( BP_Moderation_Members::$moderation_type_report === $item_sub_type ) {
			$args['user_report'] = 1;
		}

		$moderation = bp_moderation_add( $args );

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

		$response['message']       = $moderation->errors;
		$response['toast_message'] = sprintf(
			/* translators: Item type reported. */
			esc_html__( 'This %s has been reported.', 'buddyboss' ),
			strtolower( bp_moderation_get_report_type( $item_type, $item_id ) )
		);
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

	$nonce   = bb_filter_input_string( INPUT_POST, '_wpnonce' );
	$item_id = filter_input( INPUT_POST, 'content_id', FILTER_SANITIZE_NUMBER_INT );

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
				'note'         => esc_html__( 'Member block', 'buddyboss' ),
			)
		);

		if ( ! empty( $moderation->id ) && ! empty( $moderation->report_id ) ) {
			$response['moderation'] = $moderation;

			$friend_status = function_exists( 'bp_is_friend' ) && bp_is_active( 'friends' ) ? bp_is_friend( $item_id ) : array();
			if ( ! empty( $friend_status ) && in_array( $friend_status, array( 'is_friend', 'pending', 'awaiting_response' ), true ) ) {
				friends_remove_friend( bp_loggedin_user_id(), $item_id );
			}

			if (
				function_exists( 'bp_is_following' ) &&
				bp_is_following(
					array(
						'leader_id'   => $item_id,
						'follower_id' => bp_loggedin_user_id(),
					)
				)
			) {
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

	$nonce   = bb_filter_input_string( INPUT_POST, 'nonce' );
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

	$nonce      = bb_filter_input_string( INPUT_POST, 'nonce' );
	$item_type  = bb_filter_input_string( INPUT_POST, 'type' );
	$sub_action = bb_filter_input_string( INPUT_POST, 'sub_action' );
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

	$nonce      = bb_filter_input_string( INPUT_POST, 'nonce' );
	$item_type  = bb_filter_input_string( INPUT_POST, 'type' );
	$sub_action = bb_filter_input_string( INPUT_POST, 'sub_action' );
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
		$buttons['member_block'] = __( 'Block', 'buddyboss' );
	}

	if ( bp_is_active( 'moderation' ) && bb_is_moderation_member_reporting_enable() ) {
		$buttons['member_report'] = __( 'Report Member', 'buddyboss' );
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
	$suspend_table            = "{$wpdb->base_prefix}bp_suspend";
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
 * @param int  $item_id Blocking User ID ( Receiver user id ).
 * @param int  $user_id Current User ID.
 *
 * @return bool True if the user blocked/suspended otherwise false.
 */
function bb_moderation_is_recipient_moderated( $retval, $item_id, $user_id ) {
	if (
		bp_moderation_is_user_blocked( $item_id ) ||
		bb_moderation_is_user_blocked_by( $item_id ) ||
		bp_moderation_is_user_suspended( $item_id )
	) {
		return true;
	}

	return (bool) $retval;
}
add_filter( 'bb_is_recipient_moderated', 'bb_moderation_is_recipient_moderated', 10, 3 );

/**
 * Add show when reporting field in reporting categories add page.
 *
 * @since BuddyBoss 2.1.1
 *
 * @return mixed Show when Reporting field.
 */
function bb_category_add_term_fields_show_when_reporting() {
	?>
	<div class="form-field">
		<label for="bb_category_show_when_reporting"><?php esc_html_e( 'Show When Reporting', 'buddyboss' ); ?></label>
		<select name="bb_category_show_when_reporting" id="bb_category_show_when_reporting">
			<?php
			$show_when_options = bb_moderation_get_reporting_category_fields_array();
			foreach ( $show_when_options as $key => $value ) {
				printf( '<option value="%1$s" >%2$s</option>', esc_attr( $key ), esc_attr( $value ) );
			}
			?>
		</select>
	</div>
	<?php
}
add_action( 'bpm_category_add_form_fields', 'bb_category_add_term_fields_show_when_reporting' );

/**
 * Add show when reporting field in reporting categories edit page.
 *
 * @since BuddyBoss 2.1.1
 *
 * @param object $term Reporting category object.
 *
 * @return mixed Show when Reporting field.
 */
function bb_category_edit_term_fields_show_when_reporting( $term ) {
	$value = get_term_meta( $term->term_id, 'bb_category_show_when_reporting', true );
	?>
	<tr class="form-field">
		<th>
			<label for="bb_category_show_when_reporting"><?php esc_html_e( 'Show When Reporting', 'buddyboss' ); ?></label>
		</th>
		<td>
			<select name="bb_category_show_when_reporting" id="bb_category_show_when_reporting">
				<?php
				$show_when_options = bb_moderation_get_reporting_category_fields_array();
				foreach ( $show_when_options as $key => $val ) {
					printf( '<option value="%1$s" %2$s >%3$s</option>', esc_attr( $key ), selected( $value, $key, false ), esc_attr( $val ) );
				}
				?>
			</select>
		</td>
	</tr>
	<?php
}
add_action( 'bpm_category_edit_form_fields', 'bb_category_edit_term_fields_show_when_reporting', 10, 1 );

/**
 * Save show when reporting field in reporting categories.
 *
 * @since BuddyBoss 2.1.1
 *
 * @param int $term_id Show when reporting field term ID.
 */
function bb_category_save_term_fields_show_when_reporting( $term_id ) {

	if ( isset( $_POST['bb_category_show_when_reporting'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		update_term_meta(
			$term_id,
			'bb_category_show_when_reporting',
			sanitize_text_field( wp_unslash( $_POST['bb_category_show_when_reporting'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
		);
	}
}
add_action( 'created_bpm_category', 'bb_category_save_term_fields_show_when_reporting' );
add_action( 'edited_bpm_category', 'bb_category_save_term_fields_show_when_reporting' );

/**
 * Register columns for our taxonomy.
 *
 * @since BuddyBoss 2.1.1
 *
 * @param array $columns List of columns for Reporting category taxonomy.
 *
 * @return array $columns List of columns for Reporting category taxonomy.
 */
function bb_category_show_when_reporting_columns( $columns ) {
	unset( $columns['slug'] );
	unset( $columns['posts'] );
	$columns['bb_category_show_when_reporting'] = __( 'Show When Reporting', 'buddyboss' );
	return $columns;
}
add_filter( 'manage_edit-bpm_category_columns', 'bb_category_show_when_reporting_columns' );

/**
 * Retrieve value for our custom column.
 *
 * @since BuddyBoss 2.1.1
 *
 * @param string $string      Blank string.
 * @param string $column_name Name of the column.
 * @param int    $term_id     Term ID.
 *
 * @return mixed Term meta data.
 */
function bb_category_show_when_reporting_column_display( $string = '', $column_name = '', $term_id = 0 ) {
	$value             = get_term_meta( $term_id, $column_name, true );
	$show_when_options = bb_moderation_get_reporting_category_fields_array();
	return ( isset( $show_when_options[ $value ] ) ? esc_attr( $show_when_options[ $value ] ) : esc_attr__( 'Content', 'buddyboss' ) );
}
add_filter( 'manage_bpm_category_custom_column', 'bb_category_show_when_reporting_column_display', 10, 3 );

/**
 * Display markup or template for custom field.
 *
 * @since BuddyBoss 2.1.1
 *
 * @param string $column_name Column being shown.
 * @param string $screen Post type being shown.
 *
 * @return mixed
 */
function bb_quick_edit_bb_category_show_when_reporting_field( $column_name, $screen ) {
	// If we're not iterating over our custom column, then skip.
	if ( 'bpm_category' !== $screen && 'bb_category_show_when_reporting' !== $column_name ) {
		return false;
	}
	?>
	<fieldset>
		<div id="bb_category_show_when_reporting" class="inline-edit-col">
			<label>
				<span class="title"><?php esc_html_e( 'Show When Reporting', 'buddyboss' ); ?></span>
				<span class="input-text-wrap">
					<select name="bb_category_show_when_reporting" id="bb_category_show_when_reporting">
						<?php
						$show_when_options = bb_moderation_get_reporting_category_fields_array();
						foreach ( $show_when_options as $key => $value ) {
							printf( '<option value="%1$s" >%2$s</option>', esc_attr( $key ), esc_attr( $value ) );
						}
						?>
					</select>
				</span>
			</label>
		</div>
	</fieldset>
	<?php
}
add_action( 'quick_edit_custom_box', 'bb_quick_edit_bb_category_show_when_reporting_field', 10, 2 );

/**
 * Function to change member report type.
 *
 * @since BuddyBoss 2.1.1
 *
 * @param string $content_type Button text.
 * @param int    $item_id      Item id.
 *
 * @return string user report content type text.
 */
function bp_moderation_user_report_content_type( $content_type, $item_id ) {
	return esc_html__( 'Member', 'buddyboss' );
}
add_action( 'bp_moderation_user_report_report_content_type', 'bp_moderation_user_report_content_type', 10, 2 );

/**
 * Filters the labels of a specific taxonomy.
 *
 * @since BuddyBoss 2.1.1
 *
 * @param object $labels Object of labels for taxonomy `bpm_category`.
 *
 * @return object Object of labels for taxonomy `bpm_category`.
 */
function bb_get_bpm_category_labels( $labels ) {
	$labels->name_field_description = esc_html__( 'The name of this reporting category.', 'buddyboss' );
	$labels->desc_field_description = esc_html__( 'A short description of this reporting category.', 'buddyboss' );
	return $labels;
}
add_action( 'taxonomy_labels_bpm_category', 'bb_get_bpm_category_labels' );

/**
 * Prepend taxonomy description for Reporting Category page.
 *
 * @since BuddyBoss 2.1.1
 *
 * @param string $tax_slug Taxonomy slug.
 */
function bb_moderation_category_admin_edit_description( $tax_slug ) {

	// Grab the Taxonomy Object.
	$tax_obj = get_taxonomy( $tax_slug );

	// IF the description is set on our object.
	if ( property_exists( $tax_obj, 'description' ) ) {
		printf( '<p id="bb_reporting_category_description" >%s</p>', esc_attr( $tax_obj->description ) );
	}
}
add_action( 'bpm_category_pre_add_form', 'bb_moderation_category_admin_edit_description' );

/**
 * Filter to update the avatar url for the before activity comment, group posts/comment, group members.
 *
 * @since BuddyBoss 2.1.4
 *
 * @return void
 */
function bb_moderation_before_activity_entry_callback() {
	add_filter( 'bb_get_blocked_avatar_url', 'bb_moderation_fetch_avatar_url_filter', 10, 3 );
}
add_action( 'bp_before_activity_entry', 'bb_moderation_before_activity_entry_callback' );
add_action( 'bp_before_activity_comment_entry', 'bb_moderation_before_activity_entry_callback' );
add_action( 'bp_before_group_members_list', 'bb_moderation_before_activity_entry_callback' );
add_action( 'bp_before_group_manage_members_list', 'bb_moderation_before_activity_entry_callback' );

/**
 * Filter to update the avatar url for the after activity comment, group posts/comment, group members.
 *
 * @since BuddyBoss 2.1.4
 *
 * @return void
 */
function bb_moderation_after_activity_entry_callback() {
	remove_filter( 'bb_get_blocked_avatar_url', 'bb_moderation_fetch_avatar_url_filter', 10, 3 );
}
add_action( 'bp_after_activity_entry', 'bb_moderation_after_activity_entry_callback' );
add_action( 'bp_after_activity_comment_entry', 'bb_moderation_after_activity_entry_callback' );
add_action( 'bp_after_group_members_list', 'bb_moderation_after_activity_entry_callback' );
add_action( 'bp_after_group_manage_members_list', 'bb_moderation_before_activity_entry_callback' );

/**
 * Check for the next process available into the DB with the same item_id then skip the current process.
 *
 * @since BuddyBoss 2.4.20
 *
 * @param object $batch Object of data to process.
 *
 * @return void
 */
function bb_moderation_async_request_batch_process( $batch ) {
	global $bb_background_updater, $wpdb;
	if (
		empty( $batch ) ||
		! property_exists( $batch, 'group' ) ||
		empty( $batch->group ) ||
		false === strpos( $batch->group, 'bb_moderation_' )
	) {
		return;
	}

	$group_name = $batch->group;
	$item_id    = $batch->item_id;
	$type       = $batch->type;

	$next_group_name = '';
	if ( false !== strpos( $group_name, 'unsuspend' ) ) {
		$next_group_name = str_replace( 'unsuspend', 'suspend', $group_name );
	} elseif ( false !== strpos( $group_name, 'suspend' ) ) {
		$next_group_name = str_replace( 'suspend', 'unsuspend', $group_name );
	} elseif ( false !== strpos( $group_name, 'unhide' ) ) {
		$next_group_name = str_replace( 'unhide', 'hide', $group_name );
	} elseif ( false !== strpos( $group_name, 'hide' ) ) {
		$next_group_name = str_replace( 'hide', 'unhide', $group_name );
	}

	if ( empty( $next_group_name ) ) {
		return;
	}

	$table_name = $bb_background_updater::$table_name;

	$sql  = "SELECT * from {$table_name} WHERE `group` = %s AND data_id = %s AND type = %s ORDER BY priority, id ASC LIMIT 1";
	$data = $wpdb->get_results( $wpdb->prepare( $sql, $next_group_name, $item_id, $type ) ); // phpcs:ignore

	if ( ! empty( $data ) ) {
		$next_batch       = current( $data );
		$next_batch->data = maybe_unserialize( $next_batch->data );

		$current_data = ! empty( $batch->data['args'] ) ? $batch->data['args'] : array();
		$next_data    = ! empty( $next_batch->data['args'] ) ? $next_batch->data['args'] : array();

		$current_args = ! empty( $current_data ) ? end( $current_data ) : array();
		$next_args    = ! empty( $next_data ) ? end( $next_data ) : array();

		// Used for suspend/unsuspend.
		if (
			isset( $current_args['action_suspend'] ) &&
			isset( $next_args['action_suspend'] ) &&
			(int) $current_args['action_suspend'] === (int) $next_args['action_suspend']
		) {
			$batch->data = array();
			error_log( sprintf( 'Skip suspend process: `%s` next found: `%s`', $batch->key, $next_batch->id ) );

			// Used for hide_parent argument check.
		} elseif (
			isset( $current_args['hide_parent'] ) &&
			isset( $next_args['hide_parent'] ) &&
			(int) $current_args['hide_parent'] !== (int) $next_args['hide_parent']
		) {
			$batch->data = array();
			error_log( sprintf( 'Skip parent process: `%s` next found: `%s`', $batch->key, $next_batch->id ) );
			// Used for hide_sitewide argument check.
		} elseif (
			! empty( $next_data ) &&
			! empty( $current_data ) &&
			isset( $next_data[1] ) &&
			isset( $current_data[1] ) &&
			(int) $next_data[1] !== (int) $current_data[1]
		) {
			$batch->data = array();
			error_log( sprintf( 'Skip sitewide process: `%s` next found: `%s`', $batch->key, $next_batch->id ) );
		}
	}
}
add_action( 'bb_async_request_batch_process', 'bb_moderation_async_request_batch_process', 10, 1 );

