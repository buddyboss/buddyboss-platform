<?php
/**
 * Filters related to the Moderation component.
 *
 * @package BuddyBoss\Moderation
 * @since   BuddyBoss 1.5.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

new BP_Moderation_Activity();
new BP_Moderation_Activity_Comment();
new BP_Moderation_Groups();
new BP_Moderation_Members();
new BP_Moderation_Members_Suspend();
new BP_Moderation_Forums();
new BP_Moderation_Forum_Topics();
new BP_Moderation_Forum_Replies();
new BP_Moderation_Document();
new BP_Moderation_Media();
new BP_Moderation_Messages();

/**
 * Function to handle frontend report form submission.
 *
 * @since BuddyBoss 1.5.4
 */
function bp_moderation_content_report() {
	$response = array(
		'success' => false,
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
		$response['message'] = new WP_Error( 'bp_moderation_missing_data', esc_html__( 'Required field missing.', 'buddyboss' ) );
	}

	if ( bp_is_moderation_exist( array(
		'content_id'   => $item_id,
		'content_type' => $item_type,
	) ) ) {
		$response['message'] = new WP_Error( 'bp_moderation_already_reported', esc_html__( 'Already reported this item.', 'buddyboss' ) );
	}

	if ( wp_verify_nonce( $nonce, 'bp-moderation-content' ) && ! is_wp_error( $response['message'] ) ) {
		$moderation = bp_moderation_add( array(
			'content_id'   => $item_id,
			'content_type' => $item_type,
			'category_id'  => $category,
			'note'         => $item_note,
		) );

		if ( ! empty( $moderation->id ) && ! empty( $moderation->report_id ) ) {
			$response['success']    = true;
			$response['moderation'] = $moderation;

			$response['button'] = bp_get_moderation_report_button( array(
				'button_attr' => array(
					'data-bp-content-id'   => $item_id,
					'data-bp-content-type' => $item_type,
				)
			), false );
		}

		$response['message'] = $moderation->errors;
	}

	if ( empty( $response['success'] ) && empty( $response['message'] ) ) {
		$response['message'] = new WP_Error( 'bp_moderation_missing_error', esc_html__( 'Sorry, Something happened wrong', 'buddyboss' ) );
	}

	echo wp_json_encode( $response );
	exit();
}

add_action( 'wp_ajax_bp_moderation_content_report', 'bp_moderation_content_report' );
add_action( 'wp_ajax_nopriv_bp_moderation_content_report', 'bp_moderation_content_report' );

/**
 * Function to handle frontend unblock user request.
 *
 * @since BuddyBoss 1.5.4
 */
function bp_moderation_unblock_user() {
	$response = array(
		'success' => false,
		'message' => '',
	);

	$nonce = filter_input( INPUT_POST, 'nonce', FILTER_SANITIZE_STRING );
	$type  = filter_input( INPUT_POST, 'type', FILTER_SANITIZE_STRING );
	$id    = filter_input( INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT );

	if ( wp_verify_nonce( $nonce, 'bp-unblock-user' ) ) {

		$unlock = bp_moderation_delete_reported_item( $id, $type );

		if ( ! empty( $unlock ) ) {
			$response['success'] = true;
			$response['message'] = esc_html__( 'User unblocked successfully', 'buddyboss' );
		}
	}

	if ( empty( $response['success'] ) && empty( $response['message'] ) ) {
		$response['message'] = new WP_Error( 'bp_moderation_block_error', esc_html__( 'Sorry, Something happened wrong', 'buddyboss' ) );
	}

	echo wp_json_encode( $response );
	exit();
}

add_action( 'wp_ajax_bp_moderation_unblock_user', 'bp_moderation_unblock_user' );
add_action( 'wp_ajax_nopriv_bp_moderation_unblock_user', 'bp_moderation_unblock_user' );

function bp_moderation_hide_request() {
	$response = array(
		'success' => false,
		'message' => '',
	);

	$nonce      = filter_input( INPUT_POST, 'nonce', FILTER_SANITIZE_STRING );
	$type       = filter_input( INPUT_POST, 'type', FILTER_SANITIZE_STRING );
	$sub_action = filter_input( INPUT_POST, 'sub_action', FILTER_SANITIZE_STRING );
	$id         = filter_input( INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT );

	if ( wp_verify_nonce( $nonce, 'bp-hide-unhide-moderation' ) ) {
		$action = bp_moderation_hide_unhide_request( $id, $type, $sub_action );
		if ( true === $action ) {
			$response['success'] = true;
			$response['message'] = esc_html__( 'Moderation item updated successfully', 'buddyboss' );
		}
	}

	if ( empty( $response['success'] ) && empty( $response['message'] ) ) {
		$response['message'] = new WP_Error( 'bp_moderation_hide_request_error', esc_html__( 'Sorry, Something happened wrong', 'buddyboss' ) );
	}

	echo wp_json_encode( $response );
	exit();
}

add_action( 'wp_ajax_bp_moderation_hide_request', 'bp_moderation_hide_request' );
add_action( 'wp_ajax_nopriv_bp_moderation_hide_request', 'bp_moderation_hide_request' );

/**
 * Function to Popup markup for moderation content report
 * @since BuddyBoss 1.5.4
 */
function bb_moderation_content_report_popup() {
	include BP_PLUGIN_DIR . 'src/bp-moderation/screens/content-report-form.php';
}

add_action( 'wp_footer', 'bb_moderation_content_report_popup' );
