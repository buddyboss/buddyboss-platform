<?php
/**
 * BuddyPress MemberPress emails.
 *
 * @package BuddyBoss
 * @subpackage Core
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'bp_email_mepr_email_params' ) ) {
	/**
	 * memberpress email params
	 *
	 * @param $params
	 *
	 * @return mixed
	 */
	function bp_email_mepr_email_params( $params ) {
		if ( ! empty( $params['user_id'] ) ) {
			$params['user_info'] = $params['user_full_name'] . ' <img src="' . bp_core_fetch_avatar( array(
					'item_id' => $params['user_id'],
					'html'    => false
				) ) . '" " width="34" height="34" style="border: 1px solid #b9babc; border-radius: 50%; margin-left: 12px; vertical-align: middle;" />';
		}

		return $params;
	}

	add_filter( 'mepr_email_send_params', 'bp_email_mepr_email_params', 10 );
}

if ( ! function_exists( 'bp_email_mepr_email_send_body' ) ) {
	/**
	 * memberpress body replace
	 *
	 * @param $body
	 *
	 * @return false|string
	 */
	function bp_email_mepr_email_send_body( $body ) {
		ob_start();

		// Remove 'bp_replace_the_content' filter to prevent infinite loops.
		remove_filter( 'the_content', 'bp_replace_the_content' );

		set_query_var( 'email_content', $body );
		bp_get_template_part( 'assets/emails/memberpress/template' );

		// Remove 'bp_replace_the_content' filter to prevent infinite loops.
		add_filter( 'the_content', 'bp_replace_the_content' );

		// Get the output buffer contents.
		$output = ob_get_clean();

		return $output;
	}

	add_filter( 'mepr_email_send_body', 'bp_email_mepr_email_send_body' );
}

if ( ! function_exists( 'bp_email_mepr_view_paths_get_string' ) ) {
	/**
	 * memberpress template directory path change
	 *
	 * @param $paths
	 * @param $slug
	 *
	 * @return mixed
	 */
	function bp_email_mepr_view_paths_get_string( $paths, $slug ) {
		if ( strpos( $slug, 'emails/' ) !== false ) {
			array_push( $paths, buddypress()->themes_dir . '/bp-nouveau/buddypress/memberpress' );
		}

		return $paths;
	}

	add_filter( 'mepr_view_paths_get_string', 'bp_email_mepr_view_paths_get_string', 10, 2 );
}
