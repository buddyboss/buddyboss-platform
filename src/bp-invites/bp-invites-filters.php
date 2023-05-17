<?php
/**
 * BuddyBoss Invites Filters.
 *
 * @package BuddyBoss\Invites\Filters
 * @since BuddyPress 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

add_filter( 'bp_sent_invite_email_avatar', 'bb_sent_invite_email_avatar_default_avatar', 10 );

// Add invites field in wp registration form.
add_action( 'register_form', 'bb_invites_add_invite_fields_after_wp_registration_fields' );

// Perform validation before new user is registered.
add_filter( 'registration_errors', 'bb_invites_validate_invitation_before_wp_registration', PHP_INT_MAX, 3 );
add_action( 'bp_signup_pre_validate', 'bb_invites_validate_invitation_before_bb_registration', PHP_INT_MAX );

/**
 * Set default avatar when sent invite.
 *
 * @since BuddyBoss 1.8.6
 *
 * @param string $avatar Default avatar.
 * @return string The default avatar URL based on backend setting.
 */
function bb_sent_invite_email_avatar_default_avatar( $avatar = '' ) {

	if ( empty( $avatar ) ) {
		$show_avatar                 = bp_get_option( 'show_avatars' );
		$profile_avatar_type         = bb_get_profile_avatar_type();
		$default_profile_avatar_type = bb_get_default_profile_avatar_type();

		if ( $show_avatar && 'WordPress' === $profile_avatar_type && 'blank' !== bp_get_option( 'avatar_default', 'mystery' ) ) {
			$avatar = get_avatar_url(
				'',
				array(
					'size' => 300,
				)
			);
		}
	}

	return $avatar;
}

/**
 * Function to add invitation field to use later after submission of form.
 *
 * @since BuddyBoss 2.3.4
 */
function bb_invites_add_invite_fields_after_wp_registration_fields() {

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( bp_is_active( 'invites' ) && isset( $_GET ) && isset( $_GET['bp-invites'] ) && 'accept-member-invitation' === $_GET['bp-invites'] && ! empty( $_GET['action'] ) && 'register' === $_GET['action'] ) {
		$bp_invites = sanitize_text_field( wp_unslash( $_GET['bp-invites'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$inviter    = sanitize_text_field( wp_unslash( $_GET['inviter'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		?>
		<input type="hidden" name="bp-invites" value="<?php echo esc_attr( $bp_invites ); ?>">
		<input type="hidden" name="inviter" value="<?php echo esc_attr( $inviter ); ?>">
		<?php
	}
}

/**
 * Function to validate the invitation.
 *
 * @since BuddyBoss 2.3.4
 *
 * @param string $email User's email.
 * @param string $inviter Inviter user ID.
 *
 * @return bool|string True if valid invitation otherwise return error.
 */
function bb_invites_validate_invitation_before_registration( $email, $inviter ) {
	if ( ! empty( $email ) ) {
		$args = array(
			'post_type'      => bp_get_invite_post_type(),
			'posts_per_page' => -1,
			'meta_query'     => array(
				array(
					'key'     => '_bp_invitee_email',
					'value'   => $email,
					'compare' => '=',
				),
				array(
					'key'     => '_bp_invitee_status',
					'value'   => 0,
					'compare' => '=',
				),
			),
		);

		if ( ! empty( $inviter ) ) {
			$args['posts_author'] = base64_decode( $inviter ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		}

		$bp_get_invitee_email = new WP_Query( $args );

		if ( ! $bp_get_invitee_email->have_posts() ) {
			return __( "We couldn't find any invitations associated with this email address.", 'buddyboss' );
		}
	}

	return true;
}

/**
 * Function to validate the invitation before a new signup for buddyboss registration.
 *
 * @since BuddyBoss 2.3.4
 */
function bb_invites_validate_invitation_before_bb_registration() {

	if ( ! empty( $_REQUEST['inviter'] ) && ! empty( $_REQUEST['signup_email'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$email    = sanitize_text_field( wp_unslash( $_REQUEST['signup_email'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$inviter  = sanitize_text_field( wp_unslash( $_REQUEST['inviter'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$is_valid = bb_invites_validate_invitation_before_registration( $email, $inviter );

		if ( is_string( $is_valid ) ) {
			$bp                                 = buddypress();
			$bp->signup->errors['signup_email'] = $is_valid;
		}
	}
}

/**
 * Validates invitation and appends any errors to prevent new user registration.
 *
 * @since BuddyBoss 2.3.4
 *
 * @param WP_Error $errors               A WP_Error object containing any errors encountered during registration.
 * @param string   $sanitized_user_login User's username after it has been sanitized.
 * @param string   $user_email           User's email.
 *
 * @return WP_Error
 */
function bb_invites_validate_invitation_before_wp_registration( $errors, $sanitized_user_login, $user_email ) {

	if ( ! empty( $_REQUEST['inviter'] ) && ! empty( $user_email ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$inviter  = sanitize_text_field( wp_unslash( $_REQUEST['inviter'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$is_valid = bb_invites_validate_invitation_before_registration( $user_email, $inviter );

		if ( is_string( $is_valid ) ) {
			$errors->add( 'email_error', $is_valid );
		}
	}

	return $errors;
}
