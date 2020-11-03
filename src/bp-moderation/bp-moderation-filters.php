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
new BP_Moderation_Forums();
new BP_Moderation_Forum_Topics();
new BP_Moderation_Forum_Replies();
new BP_Moderation_Messages();
new BP_Moderation_Media();
new BP_Moderation_Document();

/**
 * Function to handle frontend report form submission.
 *
 * @since BuddyBoss 1.5.4
 */
function bb_report_content() {
	$result            = array();
	$result['success'] = 0;
	$result['msg']     = esc_html__( 'Sorry, Something happened wrong.', 'buddyboss' );
	parse_str( $_POST['form_data'], $form_data_arr );
	echo "<pre>";
	print_r( $form_data_arr );
	echo "</pre>";
	exit;
}

// Ajax call to report content
add_action( 'wp_ajax_bb_report_content', 'bb_report_content' );
add_action( 'wp_ajax_nopriv_bb_report_content', 'bb_report_content' );

add_action( 'wp_footer', function () {
	include BP_PLUGIN_DIR.'src/bp-moderation/screens/content-report-form.php';
} );