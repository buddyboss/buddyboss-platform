<?php
defined( 'ABSPATH' ) || exit;

/**
 * Broadcast_Email_Delivery
 *
 * Hooks into the WordPress mail system to route email through the configured
 * delivery method: SMTP (via phpmailer_init), API providers (via pre_wp_mail),
 * or SES SMTP (via phpmailer_init with the SES endpoint).
 *
 * Also registers the test email AJAX handler.
 */
class Broadcast_Email_Delivery {

	/**
	 * Register hooks based on the active delivery method.
	 */
	public static function init() {
		$settings = Broadcast_Email_Settings::get();

		// SMTP: hook phpmailer_init for smtp and ses methods.
		if ( in_array( $settings['method'], array( 'smtp', 'ses' ), true ) ) {
			add_action( 'phpmailer_init', array( __CLASS__, 'configure_smtp' ) );
		}

		// API providers: hook pre_wp_mail for mailgun and sendgrid.
		if ( in_array( $settings['method'], array( 'mailgun', 'sendgrid' ), true ) ) {
			add_filter( 'pre_wp_mail', array( __CLASS__, 'route_via_api' ), 10, 2 );
			// Force BuddyBoss emails through wp_mail() so pre_wp_mail fires.
			add_filter( 'bp_email_use_wp_mail', '__return_true' );
		}

		// Test email AJAX — always register (admin only).
		add_action( 'wp_ajax_broadcast_test_email', array( __CLASS__, 'handle_test_email' ) );
	}

	/**
	 * Configure PHPMailer for SMTP or SES SMTP delivery.
	 *
	 * Called from the phpmailer_init action, which passes $phpmailer by reference.
	 *
	 * @param object $phpmailer PHPMailer instance.
	 */
	public static function configure_smtp( $phpmailer ) {
		$settings = Broadcast_Email_Settings::get();

		if ( 'ses' === $settings['method'] ) {
			$host       = 'email-smtp.' . $settings['ses_region'] . '.amazonaws.com';
			$port       = 587;
			$encryption = 'tls';
			$username   = Broadcast_Email_Settings::decrypt( $settings['ses_access_key_enc'] );
			$password   = Broadcast_Email_Settings::decrypt( $settings['ses_secret_key_enc'] );
		} elseif ( 'smtp' === $settings['method'] ) {
			$host       = $settings['smtp_host'];
			$port       = $settings['smtp_port'];
			$encryption = $settings['smtp_encryption'];
			$username   = $settings['smtp_username'];
			$password   = Broadcast_Email_Settings::decrypt( $settings['smtp_password_enc'] );
		} else {
			return;
		}

		$phpmailer->IsSMTP();
		$phpmailer->Host     = $host;
		$phpmailer->Port     = (int) $port;
		$phpmailer->Username = $username;
		$phpmailer->Password = $password;
		$phpmailer->SMTPAuth = ! empty( $username );

		// Encryption constants — use PHPMailer class constants when available,
		// fall back to string values for test environments.
		if ( 'ssl' === $encryption ) {
			if ( class_exists( 'PHPMailer\PHPMailer\PHPMailer' ) ) {
				$phpmailer->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
			} else {
				$phpmailer->SMTPSecure = 'ssl';
			}
		} elseif ( 'tls' === $encryption ) {
			if ( class_exists( 'PHPMailer\PHPMailer\PHPMailer' ) ) {
				$phpmailer->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
			} else {
				$phpmailer->SMTPSecure = 'tls';
			}
		} else {
			$phpmailer->SMTPSecure = '';
			$phpmailer->SMTPAutoTLS = false;
		}
	}

	/**
	 * Route email via an API provider (Mailgun or SendGrid).
	 *
	 * Called from the pre_wp_mail filter. Returns non-null to short-circuit wp_mail().
	 *
	 * @param null|bool $return Existing filter value (null = pass through).
	 * @param array     $atts   Email attributes: to, subject, message, headers, attachments.
	 * @return null|bool True on successful send, false on failure, null to pass through.
	 */
	public static function route_via_api( $return, $atts ) {
		$settings = Broadcast_Email_Settings::get();

		if ( ! in_array( $settings['method'], array( 'mailgun', 'sendgrid' ), true ) ) {
			return null;
		}

		// Parse From header to extract from_email and from_name.
		$from      = self::parse_from_header( isset( $atts['headers'] ) ? $atts['headers'] : '' );
		$from_email = ! empty( $from['email'] ) ? $from['email'] : $settings['from_email'];
		$from_name  = ! empty( $from['name'] ) ? $from['name'] : $settings['from_name'];

		// Fall back to admin email when nothing else is available.
		if ( empty( $from_email ) ) {
			$from_email = get_option( 'admin_email' );
		}

		switch ( $settings['method'] ) {
			case 'mailgun':
				return self::send_via_mailgun( $atts, $settings, $from_email, $from_name );
			case 'sendgrid':
				return self::send_via_sendgrid( $atts, $settings, $from_email, $from_name );
		}

		return null;
	}

	/**
	 * Send email via the Mailgun HTTP API.
	 *
	 * @param array  $atts       Email attributes from wp_mail().
	 * @param array  $settings   Email settings from Broadcast_Email_Settings::get().
	 * @param string $from_email Sender email address.
	 * @param string $from_name  Sender display name.
	 * @return bool True on success, false on failure.
	 */
	private static function send_via_mailgun( $atts, $settings, $from_email, $from_name ) {
		$api_key = Broadcast_Email_Settings::decrypt( $settings['mailgun_api_key_enc'] );
		$base    = 'eu' === $settings['mailgun_region']
			? 'https://api.eu.mailgun.net'
			: 'https://api.mailgun.net';

		$from_formatted = $from_name ? "{$from_name} <{$from_email}>" : $from_email;

		$to = $atts['to'];
		if ( is_array( $to ) ) {
			$to = implode( ', ', $to );
		}

		$response = wp_remote_post(
			"{$base}/v3/{$settings['mailgun_domain']}/messages",
			array(
				'headers' => array(
					'Authorization' => 'Basic ' . base64_encode( 'api:' . $api_key ),
				),
				'body'    => array(
					'from'    => $from_formatted,
					'to'      => $to,
					'subject' => $atts['subject'],
					'html'    => $atts['message'],
					'text'    => wp_strip_all_tags( $atts['message'] ),
				),
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$code = wp_remote_retrieve_response_code( $response );
		return ( $code >= 200 && $code < 300 );
	}

	/**
	 * Send email via the SendGrid v3 HTTP API.
	 *
	 * @param array  $atts       Email attributes from wp_mail().
	 * @param array  $settings   Email settings from Broadcast_Email_Settings::get().
	 * @param string $from_email Sender email address.
	 * @param string $from_name  Sender display name.
	 * @return bool True on success, false on failure.
	 */
	private static function send_via_sendgrid( $atts, $settings, $from_email, $from_name ) {
		$api_key = Broadcast_Email_Settings::decrypt( $settings['sendgrid_api_key_enc'] );

		$to = $atts['to'];
		if ( ! is_array( $to ) ) {
			$to = array( $to );
		}

		$to_recipients = array();
		foreach ( $to as $recipient ) {
			$to_recipients[] = array( 'email' => trim( $recipient ) );
		}

		$from_obj = array( 'email' => $from_email );
		if ( ! empty( $from_name ) ) {
			$from_obj['name'] = $from_name;
		}

		$body = array(
			'personalizations' => array(
				array( 'to' => $to_recipients ),
			),
			'from'             => $from_obj,
			'subject'          => $atts['subject'],
			'content'          => array(
				array(
					'type'  => 'text/html',
					'value' => $atts['message'],
				),
				array(
					'type'  => 'text/plain',
					'value' => wp_strip_all_tags( $atts['message'] ),
				),
			),
		);

		$response = wp_remote_post(
			'https://api.sendgrid.com/v3/mail/send',
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode( $body ),
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$code = wp_remote_retrieve_response_code( $response );
		return ( $code >= 200 && $code < 300 );
	}

	/**
	 * Handle the broadcast_test_email AJAX action.
	 *
	 * Sends a test email through the currently active delivery configuration.
	 * Returns JSON success or error.
	 */
	public static function handle_test_email() {
		check_ajax_referer( 'broadcast_email_test', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'broadcast' ) ), 403 );
			return;
		}

		$to = isset( $_POST['to'] ) ? sanitize_email( $_POST['to'] ) : '';
		if ( empty( $to ) ) {
			$to = get_option( 'admin_email' );
		}

		$subject = __( 'Broadcast — test email', 'broadcast' );
		$message = __( 'This is a test email sent by Broadcast to verify your active sending configuration is working correctly.', 'broadcast' );

		$sent = wp_mail( $to, $subject, $message );

		if ( $sent ) {
			wp_send_json_success(
				array(
					'message' => sprintf(
						/* translators: %s: recipient email address */
						__( 'Test email sent to %s.', 'broadcast' ),
						$to
					),
				)
			);
		} else {
			wp_send_json_error(
				array(
					'message' => __( 'Send failed. Check your configuration and server error logs.', 'broadcast' ),
				)
			);
		}
	}

	/**
	 * Parse the From header from email headers.
	 *
	 * Accepts a string (newline-separated) or array of header strings.
	 * Returns the first From header found with email and name components.
	 *
	 * @param string|array $headers Raw email headers.
	 * @return array Associative array with 'email' and 'name' keys (may be empty strings).
	 */
	public static function parse_from_header( $headers ) {
		$result = array(
			'email' => '',
			'name'  => '',
		);

		if ( empty( $headers ) ) {
			return $result;
		}

		// Normalise to array of individual header lines.
		if ( is_string( $headers ) ) {
			$headers = explode( "\n", str_replace( "\r\n", "\n", $headers ) );
		}

		foreach ( $headers as $header ) {
			$header = trim( $header );
			if ( stripos( $header, 'From:' ) !== 0 ) {
				continue;
			}

			// Strip the "From:" prefix.
			$value = trim( substr( $header, 5 ) );

			// Format: "Name <email@example.com>" or just "email@example.com".
			if ( preg_match( '/^(.+)\s*<([^>]+)>$/', $value, $matches ) ) {
				$result['name']  = trim( $matches[1], ' "' );
				$result['email'] = trim( $matches[2] );
			} else {
				$result['email'] = $value;
			}

			break;
		}

		return $result;
	}
}
