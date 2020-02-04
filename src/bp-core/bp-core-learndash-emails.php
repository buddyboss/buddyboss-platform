<?php
/**
 * BuddyPress LearnDash emails.
 *
 * @package BuddyBoss\Core
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'bp_email_ld_group_email_users_args' ) ) {
	/**
	 * Return email template for LearnDash groups.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	function bp_email_ld_group_email_users_args( $mail_args ) {

		add_filter( 'wp_mail_content_type', 'bp_email_set_content_type' ); // add this to support html in email

		$mail_args['message'] = bp_email_core_wp_get_template( $mail_args['message'] );

		return $mail_args;
	}

	add_filter( 'ld_group_email_users_args', 'bp_email_ld_group_email_users_args' );
}

if ( ! function_exists( 'bp_email_learndash_quiz_email' ) ) {
	/**
	 * Return email template for LearnDash quizzes.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	function bp_email_learndash_quiz_email( $email_params ) {

		add_filter( 'wp_mail_content_type', 'bp_email_set_content_type' ); // add this to support html in email

		$email_params['msg'] = bp_email_core_wp_get_template( $email_params['msg'], get_user_by( 'email', $email_params['email'] ) );

		return $email_params;
	}

	add_filter( 'learndash_quiz_email', 'bp_email_learndash_quiz_email' );
	add_filter( 'learndash_quiz_email_admin', 'bp_email_learndash_quiz_email' );
	add_filter( 'learndash_quiz_completed_email', 'bp_email_learndash_quiz_email' );
	add_filter( 'learndash_quiz_completed_email_admin', 'bp_email_learndash_quiz_email' );
}
