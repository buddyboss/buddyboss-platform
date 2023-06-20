<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BB_Woocommerce_Helpers Class
 *
 * This class handles compatibility code for third party plugins used in conjunction with Platform
 */
class BB_Woocommerce_Helpers {

	/**
	 * The single instance of the class.
	 *
	 * @var self
	 */
	private static $instance = null;

	/**
	 * BB_Woocommerce_Helpers constructor.
	 */
	public function __construct() {

		$this->compatibility_init();
	}

	/**
	 * Get the instance of this class.
	 *
	 * @return Controller|null
	 */
	public static function instance() {

		if ( null === self::$instance ) {
			$class_name     = __CLASS__;
			self::$instance = new $class_name();
		}

		return self::$instance;
	}

	/**
	 * Register the compatibility hooks for the plugin.
	 */
	public function compatibility_init() {

		add_filter( 'bb_is_enable_custom_registration', array( $this, 'bb_check_woocommerce_enable_myaccount_registration' ), 9, 2 );

		if ( class_exists( 'WC_Subscriptions' ) ) {
			add_action( 'bp_init', array( $this, 'bb_wcs_add_subscription_compatibility' ), 9999 );
		}

		// Perform validation before new user is registered.
		add_action( 'woocommerce_process_registration_errors', array( $this, 'bb_invites_validate_invitation_before_wc_registration' ), PHP_INT_MAX, 4 );
	}

	/**
	 * Function to set the true if custom registration is enable otherwise return default value.
	 *
	 * @since BuddyBoss 1.7.9
	 *
	 * @param bool $validate default false.
	 * @param int  $page_id current page id.
	 *
	 * @return bool|mixed
	 */
	public function bb_check_woocommerce_enable_myaccount_registration( $validate, $page_id ) {

		if ( class_exists( 'WooCommerce' ) ) {
			if (
				'yes' !== get_option( 'woocommerce_enable_myaccount_registration' )
				|| (
					'yes' === get_option( 'woocommerce_enable_myaccount_registration' )
					&& ( get_option( 'woocommerce_myaccount_page_id' ) !== $page_id )
				)
			) {
				return true;
			}
		}

		return $validate;
	}

	/**
	 * Function to make compatible WooCommerce and BuddyBoss subscriptions.
	 *
	 * @since BuddyBoss 2.2.9.1
	 *
	 * @return void
	 */
	public function bb_wcs_add_subscription_compatibility() {
		add_filter( 'woocommerce_get_query_vars', array( $this, 'bb_wcs_remove_query_vars' ) );
	}

	/**
	 * Function to set query vars to enable BuddyBoss subscriptions.
	 *
	 * @since BuddyBoss 2.2.9.1
	 *
	 * @param array $q_vars Query vars.
	 *
	 * @return array
	 */
	public function bb_wcs_remove_query_vars( $q_vars ) {
		if ( 'subscriptions' === bp_action_variable() && isset( $q_vars['subscriptions'] ) ) {
			unset( $q_vars['subscriptions'] );
		}

		return $q_vars;
	}

	/**
	 * Validates the invitation before a new signup for woocommerce registration.
	 *
	 * @since BuddyBoss 2.3.4
	 *
	 * @param WP_Error $validation_error A WP_Error object containing any errors encountered during registration.
	 * @param string   $username         User's username after it has been sanitized.
	 * @param string   $password         User's password.
	 * @param string   $email            User's email.
	 *
	 * @return WP_Error
	 */
	public function bb_invites_validate_invitation_before_wc_registration( $validation_error, $username, $password, $email ) {

		if ( bp_is_active( 'invites' ) && ! empty( $_REQUEST['inviter'] ) && ! empty( $email ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$inviter  = sanitize_text_field( wp_unslash( $_REQUEST['inviter'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$is_valid = bb_invites_validate_invitation_before_registration( $email, $inviter );

			if ( is_string( $is_valid ) ) {
				$validation_error->add( 'email_error', $is_valid );
			}
		}

		return $validation_error;
	}
}

BB_Woocommerce_Helpers::instance();
