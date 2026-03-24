<?php
defined( 'ABSPATH' ) || exit;

/**
 * Broadcast_Email_Settings
 *
 * Stores, encrypts, and retrieves email configuration settings.
 * All sensitive fields (passwords, API keys) are encrypted at rest using AES-256-CBC
 * with a key derived from AUTH_KEY so they are never stored as plaintext in the database.
 */
class Broadcast_Email_Settings {

	const CIPHER     = 'AES-256-CBC';
	const OPTION_KEY = 'broadcast_email_settings';

	/**
	 * Encrypt a plaintext value.
	 *
	 * Returns a base64-encoded string containing the IV and ciphertext separated by '::'.
	 * Returns empty string when input is empty.
	 *
	 * @param string $plaintext Value to encrypt.
	 * @return string Encrypted, base64-encoded string; or '' on empty input.
	 */
	public static function encrypt( $plaintext ) {
		if ( '' === (string) $plaintext ) {
			return '';
		}

		$key = substr( hash( 'sha256', AUTH_KEY ), 0, 32 );
		$iv  = openssl_random_pseudo_bytes( openssl_cipher_iv_length( self::CIPHER ) );
		$enc = openssl_encrypt( $plaintext, self::CIPHER, $key, 0, $iv );

		return base64_encode( $iv . '::' . $enc );
	}

	/**
	 * Decrypt a ciphertext value previously encrypted with encrypt().
	 *
	 * Returns empty string on empty input or malformed data.
	 *
	 * @param string $ciphertext Base64-encoded IV::ciphertext string.
	 * @return string Decrypted plaintext; or '' on failure.
	 */
	public static function decrypt( $ciphertext ) {
		if ( '' === (string) $ciphertext ) {
			return '';
		}

		$decoded = base64_decode( $ciphertext, true );
		if ( false === $decoded ) {
			return '';
		}

		$parts = explode( '::', $decoded, 2 );
		if ( 2 !== count( $parts ) ) {
			return '';
		}

		list( $iv, $enc ) = $parts;

		if ( '' === $iv || '' === $enc ) {
			return '';
		}

		$key       = substr( hash( 'sha256', AUTH_KEY ), 0, 32 );
		$plaintext = openssl_decrypt( $enc, self::CIPHER, $key, 0, $iv );

		return ( false === $plaintext ) ? '' : $plaintext;
	}

	/**
	 * Retrieve current email settings merged with defaults.
	 *
	 * @return array Settings array with all keys guaranteed present.
	 */
	public static function get() {
		return wp_parse_args(
			get_option( self::OPTION_KEY, array() ),
			array(
				'method'               => 'none',      // none|smtp|mailgun|sendgrid|ses
				'smtp_host'            => '',
				'smtp_port'            => 587,
				'smtp_encryption'      => 'tls',       // tls|ssl|none
				'smtp_username'        => '',
				'smtp_password_enc'    => '',
				'from_name'            => '',
				'from_email'           => '',
				'mailgun_domain'       => '',
				'mailgun_region'       => 'us',
				'mailgun_api_key_enc'  => '',
				'sendgrid_api_key_enc' => '',
				'ses_access_key_enc'   => '',
				'ses_secret_key_enc'   => '',
				'ses_region'           => 'us-east-1',
			)
		);
	}

	/**
	 * Save raw settings from the admin form.
	 *
	 * Encrypts any credential field that has a non-empty plaintext value before storing.
	 * If a credential field already contains an encrypted value (verified by decrypt returning
	 * a non-empty string), it is kept as-is to avoid double-encrypting on re-save.
	 *
	 * @param array $raw Raw form input.
	 */
	public static function save( array $raw ) {
		$credential_fields = array(
			'smtp_password_enc',
			'mailgun_api_key_enc',
			'sendgrid_api_key_enc',
			'ses_access_key_enc',
			'ses_secret_key_enc',
		);

		$settings = array();

		// Scalar / non-sensitive fields.
		$scalar_fields = array(
			'method',
			'smtp_host',
			'smtp_port',
			'smtp_encryption',
			'smtp_username',
			'mailgun_domain',
			'mailgun_region',
			'ses_region',
		);

		foreach ( $scalar_fields as $field ) {
			if ( isset( $raw[ $field ] ) ) {
				$settings[ $field ] = $raw[ $field ];
			}
		}

		// Sanitize from_name and from_email.
		if ( isset( $raw['from_name'] ) ) {
			$settings['from_name'] = sanitize_text_field( $raw['from_name'] );
		}
		if ( isset( $raw['from_email'] ) ) {
			$settings['from_email'] = sanitize_email( $raw['from_email'] );
		}

		// Credential fields: encrypt if plaintext, preserve if already encrypted.
		foreach ( $credential_fields as $field ) {
			if ( ! isset( $raw[ $field ] ) || '' === $raw[ $field ] ) {
				// Not provided — preserve existing stored value.
				$existing = self::get();
				$settings[ $field ] = $existing[ $field ];
				continue;
			}

			$value = $raw[ $field ];

			// Check if the value is already encrypted by attempting decryption.
			$decrypted = self::decrypt( $value );
			if ( '' !== $decrypted ) {
				// Successfully decrypted — already encrypted; keep as-is.
				$settings[ $field ] = $value;
			} else {
				// Plaintext value — encrypt before storing.
				$settings[ $field ] = self::encrypt( $value );
			}
		}

		update_option( self::OPTION_KEY, $settings );
	}
}
