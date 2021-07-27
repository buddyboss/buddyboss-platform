<?php
/**
 * Filters related to the Moderation component.
 *
 * @since   BuddyBoss 1.5.6
 * @package BuddyBoss\Moderation
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

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

	$reports_terms   = get_terms(
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
			esc_html__( 'Content already reported.', 'buddyboss' )
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

	if ( empty( $item_id ) ) {
		$response['message'] = new WP_Error( 'bp_moderation_missing_data', esc_html__( 'Required field missing.', 'buddyboss' ) );
		wp_send_json_error( $response );
	}

	if ( bp_moderation_report_exist( $item_id, BP_Moderation_Members::$moderation_type ) ) {
		$response['message'] = new WP_Error( 'bp_moderation_already_reported', esc_html__( 'Content already reported.', 'buddyboss' ) );
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

			$friend_status = bp_is_friend( $item_id );
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
	$user_can = bp_moderation_can_report( $item_id, $item_type, 'hide' == $sub_action );
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
	if (  ! current_user_can( 'manage_options' ) || false === (bool) $user_can ) {
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
		include buddypress()->core->path . "bp-moderation/screens/block-member-form.php";
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
	wp_cache_delete( 'bb_check_moderation_' . $moderation_data['item_type'] . '_' . $moderation_data['item_id'], 'bb' );
	wp_cache_delete( 'bb_check_hidden_content_' . $moderation_data['item_type'] . '_' . $moderation_data['item_id'], 'bb' );
	wp_cache_delete( 'bb_check_suspended_content_' . $moderation_data['item_type'] . '_' . $moderation_data['item_id'], 'bb' );
	wp_cache_delete( 'bb_check_user_suspend_user_' . $moderation_data['item_type'] . '_' . $moderation_data['item_id'], 'bb' );
}

add_action( 'bb_suspend_before_add_suspend', 'bb_moderation_clear_suspend_cache' );
add_action( 'bb_suspend_before_remove_suspend', 'bb_moderation_clear_suspend_cache' );

/**
 * Function to clear cache on suspend item delete.
 *
 * @since BuddyBoss 1.6.2
 *
 * @param object $suspend_record suspend item record.
 */
function bb_moderation_clear_delete_cache( $suspend_record ) {
	if ( empty( $suspend_record->item_type ) || empty( $suspend_record->item_id ) ) {
		return;
	}
	wp_cache_delete( 'bb_check_moderation_' . $suspend_record->item_type . '_' . $suspend_record->item_id, 'bb' );
	wp_cache_delete( 'bb_check_hidden_content_' . $suspend_record->item_type . '_' . $suspend_record->item_id, 'bb' );
	wp_cache_delete( 'bb_check_suspended_content_' . $suspend_record->item_type . '_' . $suspend_record->item_id, 'bb' );
	wp_cache_delete( 'bb_check_user_suspend_user_' . $suspend_record->item_type . '_' . $suspend_record->item_id, 'bb' );
}

add_action( 'bp_moderation_after_save', 'bb_moderation_clear_delete_cache' );
add_action( 'suspend_after_delete', 'bb_moderation_clear_delete_cache' );
add_action( 'bp_moderation_after_hide', 'bb_moderation_clear_delete_cache' );
add_action( 'bp_moderation_after_unhide', 'bb_moderation_clear_delete_cache' );

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
	wp_cache_delete( 'bb_check_moderation_' . $content_type . '_' . $content_id, 'bb' );
	wp_cache_delete( 'bb_check_hidden_content_' . $content_type . '_' . $content_id, 'bb' );
	wp_cache_delete( 'bb_check_suspended_content_' . $content_type . '_' . $content_id, 'bb' );
	wp_cache_delete( 'bb_check_user_suspend_user_' . $content_type . '_' . $content_id, 'bb' );
}

add_action( 'bb_suspend_hide_before', 'bb_moderation_clear_status_change_cache', 10, 3 );
add_action( 'bb_suspend_unhide_before', 'bb_moderation_clear_status_change_cache', 10, 3 );

/**
 * Add moderation repair list.
 *
 * @param array $repair_list
 *
 * @since BuddyBoss 1.7.4
 *
 * @return array Repair list items.
 */
function bb_moderation_migrate_old_data( $repair_list ) {
	$repair_list[] = array(
		'bp-repair-moderation-data',
		__( 'Repair moderation data.', 'buddyboss' ),
		'bb_moderation_admin_repair_old_moderation_data',
	);

	return $repair_list;
}

add_filter( 'bp_repair_list', 'bb_moderation_migrate_old_data' );

/**
 * Function to admin repair tool for fix moderation data.
 *
 * @since BuddyBoss 1.7.4
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
		$records_updated = sprintf( __( '%s moderation item updated successfully.', 'buddyboss' ), number_format_i18n( $offset ) );

		return array(
			'status'  => 'running',
			'offset'  => $offset,
			'records' => $records_updated,
		);
	} else {
		return array(
			'status'  => 1,
			'message' => __( 'Moderation update complete!', 'buddyboss' ),
		);
	}
}

/**
 * Function to check if media record is exist.
 *
 * @param int    $id   media id
 * @param string $type media type
 *
 * @since BuddyBoss 1.7.4
 *
 * @return null|array|object|void
 */
function bb_moderation_get_media_record_by_id( $id, $type ) {
	global $wpdb;

	$record         = array();
	$media_table    = "{$wpdb->prefix}bp_media";
	$document_table = "{$wpdb->prefix}bp_document";

	if ( in_array( $type, array( 'media', 'video' ) ) ) {
		$media_sql = $wpdb->prepare( "SELECT activity_id FROM {$media_table} WHERE id=%d", $id );
		$record    = $wpdb->get_row( $media_sql );
	}

	if ( 'document' === $type ) {
		$document_sql = $wpdb->prepare( "SELECT activity_id FROM {$document_table} WHERE id=%d", $id );
		$record       = $wpdb->get_row( $document_sql );
	}

	return $record;
}

/**
 * Function to check if suspend record is exist.
 *
 * @param int $id id
 *
 * @since BuddyBoss 1.7.4
 *
 * @return null|array|object|void
 */
function bb_moderation_suspend_record_exist( $id ) {
	global $wpdb;

	$record = array();

	if ( ! $id ) {
		return $record;
	}

	$suspend_table = "{$wpdb->prefix}bp_suspend";

	$suspend_record_sql = $wpdb->prepare( "SELECT id,item_id,item_type,reported FROM {$suspend_table} WHERE item_id=%d", $id );
	$record             = $wpdb->get_row( $suspend_record_sql );

	return $record;
}

/**
 * Function to update suspend data.
 *
 * @param object $moderated_activities suspend records
 * @param int    $offset               pagination object
 *
 * @since BuddyBoss 1.7.4
 *
 * @return int|mixed
 */
function bb_moderation_update_suspend_data( $moderated_activities, $offset = 0 ) {
	global $wpdb;

	$suspend_table = "{$wpdb->prefix}bp_suspend";

	if ( ! empty( $moderated_activities ) ) {
		foreach ( $moderated_activities as $moderated_activity ) {

			if ( in_array( $moderated_activity->item_type, array( 'media', 'video' ) ) ) {
				$media_results = bb_moderation_get_media_record_by_id( $moderated_activity->item_id, $moderated_activity->item_type );
				if ( ! empty( $media_results ) ) {
					$suspend_record = bb_moderation_suspend_record_exist( $media_results->activity_id );
					if ( ! empty( $suspend_record ) && 1 === (int) $suspend_record->reported ) {
						$wpdb->update( $suspend_table, array(
							'item_id'   => $suspend_record->item_id,
							'item_type' => $suspend_record->item_type,
						), array( 'id' => $moderated_activity->id ) );

						$wpdb->update( $suspend_table, array(
							'item_id'   => $moderated_activity->item_id,
							'item_type' => $moderated_activity->item_type,
						), array( 'id' => $suspend_record->id ) );
					}
				}
			}

			if ( 'document' === $moderated_activity->item_type ) {
				$document_results = bb_moderation_get_media_record_by_id( $moderated_activity->item_id, 'document' );
				if ( ! empty( $document_results ) ) {
					$suspend_record = bb_moderation_suspend_record_exist( $document_results->activity_id );
					if ( ! empty( $suspend_record ) && 1 === (int) $suspend_record->reported ) {
						$wpdb->update( $suspend_table, array(
							'item_id'   => $suspend_record->item_id,
							'item_type' => $suspend_record->item_type,
						), array( 'id' => $moderated_activity->id ) );

						$wpdb->update( $suspend_table, array(
							'item_id'   => $moderated_activity->item_id,
							'item_type' => $moderated_activity->item_type,
						), array( 'id' => $suspend_record->id ) );
					}
				}
			}
			$offset ++;
		}
	}

	return $offset;
}

/**
 * Function to update moderation data on plugin update.
 *
 * @since BuddyBoss 1.7.4
 *
 * @return int|mixed|void
 */
function bb_moderation_bg_update_moderation_data() {
	global $wpdb;
	$suspend_table = "{$wpdb->prefix}bp_suspend";
	$table_exists  = (bool) $wpdb->get_results( "DESCRIBE {$suspend_table}" );

	if ( ! $table_exists ) {
		return;
	}

	$moderated_activities = $wpdb->get_results( "SELECT id,item_id,item_type FROM {$suspend_table} WHERE item_type IN ('media','video','document') GROUP BY id ORDER BY id DESC" );
	if ( ! empty( $moderated_activities ) ) {
		bb_moderation_update_suspend_data( $moderated_activities, 0 );
	}
}
