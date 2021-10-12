<?php
/**
 * Members: Register screen handler
 *
 * @package BuddyBoss\Members\Screens
 * @since BuddyPress 3.0.0
 */

/**
 * Handle the loading of the signup screen.
 *
 * @since BuddyPress 1.1.0
 */
function bp_core_screen_signup() {
	$bp = buddypress();

	if ( ! bp_is_current_component( 'register' ) || bp_current_action() ) {
		return;
	}

	$allow_custom_registration = bp_allow_custom_registration();
	if ( $allow_custom_registration && '' !== bp_custom_register_page_url() ) {

		// Check it's not a Email Invites
		if ( bp_is_active( 'invites' ) && isset( $_GET ) && isset( $_GET['bp-invites'] ) && 'accept-member-invitation' === $_GET['bp-invites'] ) {
			if ( parse_url( bp_custom_register_page_url(), PHP_URL_QUERY ) ) {
				$email   = isset( $_GET ) && isset( $_GET['email'] ) ? $_GET['email'] : '';
				$inviter = isset( $_GET ) && isset( $_GET['inviter'] ) ? $_GET['inviter'] : '';
				$url = bp_custom_register_page_url() . '&bp-invites=accept-member-invitation&email=' . $email . '&inviter=' .$inviter . '&user_email=' . $email;
			} else {
				$email   = isset( $_GET ) && isset( $_GET['email'] ) ? $_GET['email'] : '';
				$inviter = isset( $_GET ) && isset( $_GET['inviter'] ) ? $_GET['inviter'] : '';
				$url = bp_custom_register_page_url() . '?bp-invites=accept-member-invitation&email=' . $email . '&inviter=' .$inviter . '&user_email=' . $email;
			}
			bp_core_redirect( $url );
			return;
		} else {
			bp_core_redirect( bp_custom_register_page_url() );
			return;
		}
	}

	// Not a directory.
	bp_update_is_directory( false, 'register' );

	// If the user is logged in, redirect away from here.
	if ( is_user_logged_in() ) {

		$redirect_to = bp_is_component_front_page( 'register' )
			? bp_get_members_directory_permalink()
			: bp_get_root_domain();

		/**
		 * Filters the URL to redirect logged in users to when visiting registration page.
		 *
		 * @since BuddyPress 1.5.1
		 *
		 * @param string $redirect_to URL to redirect user to.
		 */
		bp_core_redirect( apply_filters( 'bp_loggedin_register_page_redirect_to', $redirect_to ) );

		return;
	}

	$bp->signup->step = 'request-details';

	if ( ! bp_get_signup_allowed() ) {
		$bp->signup->step = 'registration-disabled';

		// If the signup page is submitted, validate and save.
	} elseif ( isset( $_POST['signup_submit'] ) && bp_verify_nonce_request( 'bp_new_signup' ) ) {

		/**
		 * Fires before the validation of a new signup.
		 *
		 * @since BuddyPress 2.0.0
		 */
		do_action( 'bp_signup_pre_validate' );

		// Check the base account details for problems.
		$account_details = bp_core_validate_user_signup( bp_get_signup_username_value(), bp_get_signup_email_value() );

		$email_opt    = function_exists( 'bp_register_confirm_email' ) && true === bp_register_confirm_email() ? true : false;
		$password_opt = function_exists( 'bp_register_confirm_password' ) && true === bp_register_confirm_password() ? true : false;

		// If there are errors with account details, set them for display.
		if ( ! empty( $account_details['errors']->errors['user_name'] ) ) {
			$nickname_field                        = 'field_' . bp_xprofile_nickname_field_id();
			$bp->signup->errors[ $nickname_field ] = $account_details['errors']->errors['user_name'][0];
		}

		if ( ! empty( $account_details['errors']->errors['user_email'] ) ) {
			$bp->signup->errors['signup_email'] = $account_details['errors']->errors['user_email'][0];
		}

		// Check that both password fields are filled in.
		if ( empty( $_POST['signup_password'] ) ) {
			$bp->signup->errors['signup_password'] = __( 'Please make sure to enter your password.', 'buddyboss' );
		}

		// if email opt enabled.
		if ( true === $email_opt ) {

			// Check that both password fields are filled in.
			if ( empty( $_POST['signup_email'] ) || empty( $_POST['signup_email_confirm'] ) ) {
				$bp->signup->errors['signup_email'] = __( 'Please make sure to enter your email twice.', 'buddyboss' );
			}

			// Check that the passwords match.
			if ( ( ! empty( $_POST['signup_email'] ) && ! empty( $_POST['signup_email_confirm'] ) ) && $_POST['signup_email'] != $_POST['signup_email_confirm'] ) {
				$bp->signup->errors['signup_email'] = __( 'The emails entered do not match.', 'buddyboss' );
			}
		}

		// if password opt enabled.
		if ( true === $password_opt ) {

			// Check that both password fields are filled in.
			if ( empty( $_POST['signup_password'] ) || empty( $_POST['signup_password_confirm'] ) ) {
				$bp->signup->errors['signup_password'] = __( 'Please make sure to enter your password twice.', 'buddyboss' );
			}

			// Check that the passwords match.
			if ( ( ! empty( $_POST['signup_password'] ) && ! empty( $_POST['signup_password_confirm'] ) ) && $_POST['signup_password'] != $_POST['signup_password_confirm'] ) {
				$bp->signup->errors['signup_password'] = __( 'The passwords entered do not match.', 'buddyboss' );
			}
		}

		$bp->signup->username = bp_get_signup_username_value();
		$bp->signup->email    = bp_get_signup_email_value();

		// Now we've checked account details, we can check profile information.
		if ( bp_is_active( 'xprofile' ) ) {

			// Make sure hidden field is passed and populated.
			if ( isset( $_POST['signup_profile_field_ids'] ) && ! empty( $_POST['signup_profile_field_ids'] ) ) {

				// Let's compact any profile field info into an array.
				$profile_field_ids = explode( ',', $_POST['signup_profile_field_ids'] );

				// Loop through the posted fields formatting any datebox values then validate the field.
				foreach ( (array) $profile_field_ids as $field_id ) {
					bp_xprofile_maybe_format_datebox_post_data( $field_id );

					// Trim post fields.
					if ( isset( $_POST[ 'field_' . $field_id ] ) ) {
						if ( is_array( $_POST[ 'field_' . $field_id ] ) ) {
							$_POST[ 'field_' . $field_id ] = array_map( 'trim', $_POST[ 'field_' . $field_id ] );
						} else {
							$_POST[ 'field_' . $field_id ] = trim( $_POST[ 'field_' . $field_id ] );
						}
					}

					// Create errors for required fields without values.
					if ( xprofile_check_is_required_field( $field_id ) && empty( $_POST[ 'field_' . $field_id ] ) && ! bp_current_user_can( 'bp_moderate' ) ) {
						$bp->signup->errors[ 'field_' . $field_id ] = sprintf(
							'<div class="bp-messages bp-feedback error">
								<span class="bp-icon" aria-hidden="true"></span>
								<p>%s</p>
							</div>',
							__( 'This is a required field.', 'buddyboss' )
						);
					} else {                    // Validate xprofile
						if ( isset( $_POST[ 'field_' . $field_id ] ) && $message = xprofile_validate_field( $field_id, $_POST[ 'field_' . $field_id ], '' ) ) {
							$bp->signup->errors[ 'field_' . $field_id ] = sprintf(
								'<div class="bp-messages bp-feedback error">
								<span class="bp-icon" aria-hidden="true"></span>
								<p>%s</p>
							</div>',
								$message
							);
						}
					}
				}

				// This situation doesn't naturally occur so bounce to website root.
			} else {
				bp_core_redirect( bp_get_root_domain() );
			}
		}

		// Finally, let's check the blog details, if the user wants a blog and blog creation is enabled.
		if ( isset( $_POST['signup_with_blog'] ) ) {
			$active_signup = bp_core_get_root_option( 'registration' );

			if ( 'blog' == $active_signup || 'all' == $active_signup ) {
				$blog_details = bp_core_validate_blog_signup( $_POST['signup_blog_url'], $_POST['signup_blog_title'] );

				// If there are errors with blog details, set them for display.
				if ( ! empty( $blog_details['errors']->errors['blogname'] ) ) {
					$bp->signup->errors['signup_blog_url'] = $blog_details['errors']->errors['blogname'][0];
				}

				if ( ! empty( $blog_details['errors']->errors['blog_title'] ) ) {
					$bp->signup->errors['signup_blog_title'] = $blog_details['errors']->errors['blog_title'][0];
				}
			}
		}

		/**
		 * Fires after the validation of a new signup.
		 *
		 * @since BuddyPress 1.1.0
		 */
		do_action( 'bp_signup_validate' );

		// Adding error message for the legal agreement checkbox.
		if ( true === bb_register_legal_agreement() && empty( $_POST['legal_agreement'] ) ) {
			$bp->signup->errors['legal_agreement'] = __( 'This is a required field.', 'buddyboss' );
		}

		// Add any errors to the action for the field in the template for display.
		if ( ! empty( $bp->signup->errors ) ) {
			foreach ( (array) $bp->signup->errors as $fieldname => $error_message ) {
				/**
				 * Filters the error message in the loop.
				 *
				 * @since BuddyPress 1.5.0
				 *
				 * @param string $value Error message wrapped in html.
				 */
				add_action(
					'bp_' . $fieldname . '_errors',
					function() use ( $error_message ) {
						echo apply_filters( 'bp_members_signup_error_message', '<div class="error">' . $error_message . '</div>' );
					}
				);
			}
		} else {
			$bp->signup->step = 'save-details';

			// No errors! Let's register those deets.
			$active_signup = bp_core_get_root_option( 'registration' );

			if ( 'none' != $active_signup ) {

				// Make sure the profiles fields module is enabled.
				if ( bp_is_active( 'xprofile' ) ) {
					// Let's compact any profile field info into usermeta.
					$profile_field_ids = explode( ',', $_POST['signup_profile_field_ids'] );

					/*
					 * Loop through the posted fields, formatting any
					 * datebox values, then add to usermeta.
					 */
					foreach ( (array) $profile_field_ids as $field_id ) {
						bp_xprofile_maybe_format_datebox_post_data( $field_id );

						if ( ! empty( $_POST[ 'field_' . $field_id ] ) ) {
							$usermeta[ 'field_' . $field_id ] = $_POST[ 'field_' . $field_id ];
						}

						if ( ! empty( $_POST[ 'field_' . $field_id . '_visibility' ] ) ) {
							$usermeta[ 'field_' . $field_id . '_visibility' ] = $_POST[ 'field_' . $field_id . '_visibility' ];
						}
					}

					// Store the profile field ID's in usermeta.
					$usermeta['profile_field_ids'] = $_POST['signup_profile_field_ids'];
				}

				// Hash and store the password.
				$usermeta['password'] = wp_hash_password( $_POST['signup_password'] );

				// If the user decided to create a blog, save those details to usermeta.
				if ( 'blog' == $active_signup || 'all' == $active_signup ) {
					$usermeta['public'] = ( isset( $_POST['signup_blog_privacy'] ) && 'public' == $_POST['signup_blog_privacy'] ) ? true : false;
				}

				/**
				 * Filters the user meta used for signup.
				 *
				 * @since BuddyPress 1.1.0
				 *
				 * @param array $usermeta Array of user meta to add to signup.
				 */
				$usermeta = apply_filters( 'bp_signup_usermeta', $usermeta );

				// Finally, sign up the user and/or blog.
				if ( isset( $_POST['signup_with_blog'] ) && is_multisite() ) {
					$wp_user_id = bp_core_signup_blog(
						$blog_details['domain'],
						$blog_details['path'],
						$blog_details['blog_title'],
						bp_get_signup_username_value(),
						bp_get_signup_email_value(),
						$usermeta
					);
				} else {
					$wp_user_id = bp_core_signup_user(
						bp_get_signup_username_value(),
						$_POST['signup_password'],
						bp_get_signup_email_value(),
						$usermeta
					);
				}

				if ( is_wp_error( $wp_user_id ) ) {
					$bp->signup->step = 'request-details';
					bp_core_add_message( $wp_user_id->get_error_message(), 'error' );
				} else {
					if ( ! empty( $wp_user_id ) && ! is_wp_error( $wp_user_id ) && ! empty( $_POST['legal_agreement'] ) ) {
						update_user_meta( $wp_user_id, 'bb_legal_agreement', true );
					}
					$bp->signup->step = 'completed-confirmation';
				}
			}

			/**
			 * Fires after the completion of a new signup.
			 *
			 * @since BuddyPress 1.1.0
			 */
			do_action( 'bp_complete_signup' );
		}
	}

	/**
	 * Fires right before the loading of the Member registration screen template file.
	 *
	 * @since BuddyPress 1.5.0
	 */
	do_action( 'bp_core_screen_signup' );

	/**
	 * Filters the template to load for the Member registration page screen.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param string $value Path to the Member registration template to load.
	 */
	bp_core_load_template( apply_filters( 'bp_core_template_register', array( 'register', 'registration/register' ) ) );
}
add_action( 'bp_screens', 'bp_core_screen_signup' );

/**
 * for check email id exist or not on ajax submit.
 *
 * @since BuddyBoss 1.2.1
 */
function bp_signup_check_email_username() {

	$signup_username = '';
	$signup_email    = '';
	$account_details = bp_core_validate_user_signup( bp_get_signup_username_value(), bp_get_signup_email_value() );
	$email_opt       = function_exists( 'bp_register_confirm_email' ) && true === bp_register_confirm_email() ? true : false;
	$password_opt    = function_exists( 'bp_register_confirm_password' ) && true === bp_register_confirm_password() ? true : false;
	// If there are errors with account details, set them for display.
	if ( ! empty( $account_details['errors']->errors['user_name'] ) ) {
		$signup_username = $account_details['errors']->errors['user_name'][0];
	}

	if ( ! empty( $account_details['errors']->errors['user_email'] ) ) {
		$signup_email = $account_details['errors']->errors['user_email'][0];
	}
	// if email opt enabled.
	if ( true === $email_opt ) {

		// Check that both password fields are filled in.
		if ( empty( $_POST['signup_email'] ) || empty( $_POST['signup_email_confirm'] ) ) {
			$signup_email = __( 'Please make sure to enter your email twice.', 'buddyboss' );
		}

		// Check that the passwords match.
		if ( ( ! empty( $_POST['signup_email'] ) && ! empty( $_POST['signup_email_confirm'] ) ) && $_POST['signup_email'] != $_POST['signup_email_confirm'] ) {
			$signup_email = __( 'The emails entered do not match.', 'buddyboss' );
		}
	}
	$nickname_field = 'field_' . bp_xprofile_nickname_field_id();
	$return         = array(
		'field_id'        => bp_xprofile_nickname_field_id(),
		'signup_email'    => $signup_email,
		'signup_username' => $signup_username,

	);

	wp_send_json( $return, true );
}

add_action( 'wp_ajax_nopriv_check_email', 'bp_signup_check_email_username' );
