<?php
/**
 * BuddyBoss DRM Helper
 *
 * Helper class for DRM (Digital Rights Management) functionality.
 * Manages license validation, status checks, and DRM messaging.
 *
 * @package BuddyBoss\Core\Admin\DRM
 * @since 3.0.0
 */

namespace BuddyBoss\Core\Admin\DRM;

use BuddyBossPlatform\GroundLevel\Mothership\Credentials;
use BuddyBoss\Core\Admin\Mothership\BB_Plugin_Connector;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * DRM Helper class for managing license validation and status.
 */
class BB_DRM_Helper {

	/**
	 * Event name for missing license.
	 */
	const NO_LICENSE_EVENT = 'no-license';

	/**
	 * Event name for invalid/expired license.
	 */
	const INVALID_LICENSE_EVENT = 'invalid-license';

	/**
	 * DRM status: Low (warning).
	 */
	const DRM_LOW = 'low';

	/**
	 * DRM status: Medium (critical warning).
	 */
	const DRM_MEDIUM = 'medium';

	/**
	 * DRM status: Locked (backend disabled).
	 */
	const DRM_LOCKED = 'locked';

	/**
	 * Current DRM status.
	 *
	 * @var string
	 */
	private static $drm_status = '';

	/**
	 * DRM links for different statuses.
	 *
	 * @var array|null
	 */
	private static $drm_links = null;

	/**
	 * Fallback links.
	 *
	 * @var array
	 */
	private static $fallback_links = array(
		'account' => 'https://www.buddyboss.com/my-account/',
		'support' => 'https://www.buddyboss.com/support/',
		'pricing' => 'https://www.buddyboss.com/pricing/',
	);

	/**
	 * Set the DRM status.
	 *
	 * @param string $status The DRM status to set.
	 */
	public static function set_status( $status ) {
		self::$drm_status = $status;
	}

	/**
	 * Get the current DRM status.
	 *
	 * @return string The current DRM status.
	 */
	public static function get_status() {
		return self::$drm_status;
	}

	/**
	 * Check if a license key exists.
	 *
	 * @return bool True if a license key exists, false otherwise.
	 */
	public static function has_key() {
		try {
			$key = Credentials::getLicenseKey();
			return ! empty( $key );
		} catch ( \Exception $e ) {
			return false;
		}
	}

	/**
	 * Get the license key.
	 *
	 * @return string The license key.
	 */
	public static function get_key() {
		try {
			return Credentials::getLicenseKey();
		} catch ( \Exception $e ) {
			return '';
		}
	}

	/**
	 * Check if the license is valid.
	 *
	 * @return bool True if the license is valid, false otherwise.
	 */
	public static function is_valid() {
		// Check if license key exists.
		if ( ! self::has_key() ) {
			return false;
		}

		// Get plugin connector to check activation status.
		$plugin_connector = new BB_Plugin_Connector();
		$is_active        = $plugin_connector->getLicenseActivationStatus();

		return $is_active;
	}

	/**
	 * Calculate the number of days elapsed since a given date.
	 *
	 * @param string $created_at The date to calculate from.
	 * @return int The number of days elapsed.
	 */
	public static function days_elapsed( $created_at ) {
		$timestamp = strtotime( $created_at );

		if ( false === $timestamp ) {
			return 0;
		}

		$start_date = new \DateTime( gmdate( 'Y-m-d' ) );
		$end_date   = new \DateTime( gmdate( 'Y-m-d', $timestamp ) );
		$difference = $end_date->diff( $start_date );

		return absint( $difference->format( '%a' ) );
	}

	/**
	 * Determine the DRM status, using a default if not provided.
	 *
	 * @param string $drm_status The DRM status to check.
	 * @return string The determined DRM status.
	 */
	protected static function maybe_drm_status( $drm_status = '' ) {
		if ( empty( $drm_status ) ) {
			$drm_status = self::$drm_status;
		}

		return $drm_status;
	}

	/**
	 * Check if the DRM status is locked.
	 *
	 * @param string $drm_status The DRM status to check.
	 * @return bool True if locked, false otherwise.
	 */
	public static function is_locked( $drm_status = '' ) {
		return ( self::DRM_LOCKED === self::maybe_drm_status( $drm_status ) );
	}

	/**
	 * Check if the DRM status is medium.
	 *
	 * @param string $drm_status The DRM status to check.
	 * @return bool True if medium, false otherwise.
	 */
	public static function is_medium( $drm_status = '' ) {
		return ( self::DRM_MEDIUM === self::maybe_drm_status( $drm_status ) );
	}

	/**
	 * Check if the DRM status is low.
	 *
	 * @param string $drm_status The DRM status to check.
	 * @return bool True if low, false otherwise.
	 */
	public static function is_low( $drm_status = '' ) {
		return ( self::DRM_LOW === self::maybe_drm_status( $drm_status ) );
	}

	/**
	 * Get the status key for a given DRM status.
	 *
	 * @param string $drm_status The DRM status.
	 * @return string The status key.
	 */
	public static function get_status_key( $drm_status ) {
		$out = '';
		switch ( $drm_status ) {
			case self::DRM_LOW:
				$out = 'dl';
				break;
			case self::DRM_MEDIUM:
				$out = 'dm';
				break;
			case self::DRM_LOCKED:
				$out = 'dll';
				break;
		}

		return $out;
	}

	/**
	 * Get DRM information based on status, event, and purpose.
	 *
	 * @param string $drm_status The DRM status.
	 * @param string $event_name The event name.
	 * @param string $purpose    The purpose of the information.
	 * @return array The DRM information.
	 */
	public static function get_info( $drm_status, $event_name, $purpose ) {
		$out = array();

		switch ( $event_name ) {
			case self::NO_LICENSE_EVENT:
				$out = self::drm_info_no_license( $drm_status, $purpose );
				break;
			case self::INVALID_LICENSE_EVENT:
				$out = self::drm_info_invalid_license( $drm_status, $purpose );
				break;
		}

		return apply_filters( 'bb_drm_info', $out, $drm_status, $event_name, $purpose );
	}

	/**
	 * Get DRM links.
	 *
	 * @return array The DRM links.
	 */
	protected static function get_drm_links() {
		if ( self::$drm_links === null ) {
			self::$drm_links = array(
				self::DRM_LOW    => array(
					'email'   => array(
						'home'    => 'https://www.buddyboss.com/drmlow/email',
						'account' => 'https://www.buddyboss.com/drmlow/email/acct',
						'support' => 'https://www.buddyboss.com/drmlow/email/support',
						'pricing' => 'https://www.buddyboss.com/drmlow/email/pricing',
					),
					'general' => array(
						'home'    => 'https://www.buddyboss.com/drmlow/ipm',
						'account' => 'https://www.buddyboss.com/drmlow/ipm/account',
						'support' => 'https://www.buddyboss.com/drmlow/ipm/support',
						'pricing' => 'https://www.buddyboss.com/drmlow/ipm/pricing',
					),
				),
				self::DRM_MEDIUM => array(
					'email'   => array(
						'home'    => 'https://www.buddyboss.com/drmmed/email',
						'account' => 'https://www.buddyboss.com/drmmed/email/acct',
						'support' => 'https://www.buddyboss.com/drmmed/email/support',
						'pricing' => 'https://www.buddyboss.com/drmmed/email/pricing',
					),
					'general' => array(
						'home'    => 'https://www.buddyboss.com/drmmed/ipm',
						'account' => 'https://www.buddyboss.com/drmmed/ipm/account',
						'support' => 'https://www.buddyboss.com/drmmed/ipm/support',
						'pricing' => 'https://www.buddyboss.com/drmmed/ipm/pricing',
					),
				),
				self::DRM_LOCKED => array(
					'email'   => array(
						'home'    => 'https://www.buddyboss.com/drmlock/email',
						'account' => 'https://www.buddyboss.com/drmlock/email/acct',
						'support' => 'https://www.buddyboss.com/drmlock/email/support',
						'pricing' => 'https://www.buddyboss.com/drmlock/email/pricing',
					),
					'general' => array(
						'home'    => 'https://www.buddyboss.com/drmlock/ipm',
						'account' => 'https://www.buddyboss.com/drmlock/ipm/account',
						'support' => 'https://www.buddyboss.com/drmlock/ipm/support',
						'pricing' => 'https://www.buddyboss.com/drmlock/ipm/pricing',
					),
				),
			);
		}

		return apply_filters( 'bb_drm_links', self::$drm_links );
	}

	/**
	 * Get a specific DRM link.
	 *
	 * @param string $drm_status The DRM status.
	 * @param string $purpose    The purpose of the link.
	 * @param string $type       The type of link.
	 * @return string The DRM link.
	 */
	public static function get_drm_link( $drm_status, $purpose, $type ) {
		$drm_links = self::get_drm_links();

		if ( isset( $drm_links[ $drm_status ] ) ) {
			if ( ! isset( $drm_links[ $drm_status ][ $purpose ] ) ) {
				$purpose = 'general';
			}

			if ( isset( $drm_links[ $drm_status ][ $purpose ] ) ) {
				$data = $drm_links[ $drm_status ][ $purpose ];
				if ( isset( $data[ $type ] ) ) {
					return $data[ $type ];
				}
			}
		}

		// Fallback links.
		if ( isset( self::$fallback_links[ $type ] ) ) {
			return self::$fallback_links[ $type ];
		}

		return '';
	}

	/**
	 * Get DRM information for no license event.
	 *
	 * @param string $drm_status The DRM status.
	 * @param string $purpose    The purpose of the information.
	 * @return array The DRM information.
	 */
	protected static function drm_info_no_license( $drm_status, $purpose ) {
		$account_link            = self::get_drm_link( $drm_status, $purpose, 'account' );
		$support_link            = self::get_drm_link( $drm_status, $purpose, 'support' );
		$pricing_link            = self::get_drm_link( $drm_status, $purpose, 'pricing' );
		$additional_instructions = sprintf(
			/* translators: %s: site URL */
			__( 'This is an automated message from %s.', 'buddyboss' ),
			esc_url( home_url() )
		);

		switch ( $drm_status ) {
			case self::DRM_LOW:
				$admin_notice_view = 'low_warning';
				$heading           = __( 'BuddyBoss: Did You Forget Something?', 'buddyboss' );
				$color             = 'orange';
				$simple_message    = __( 'Oops! It looks like your BuddyBoss license key is missing. Here\'s how to fix the problem fast and easy:', 'buddyboss' );
				$help_message      = __( 'We're here if you need any help.', 'buddyboss' );
				$label             = __( 'Alert', 'buddyboss' );
				$activation_link   = bp_get_admin_url( 'admin.php?page=buddyboss-license' );
				$message           = sprintf(
					'<p>%s</p><ul><li>%s</li><li>%s</li><li>%s</li></ul>',
					$simple_message,
					sprintf(
						/* translators: %1$s: opening anchor tag, %2$s: closing anchor tag */
						__( 'Grab your key from your %1$sAccount Page%2$s.', 'buddyboss' ),
						'<a href="' . esc_url( $account_link ) . '">',
						'</a>'
					),
					sprintf(
						/* translators: %1$s: opening anchor tag, %2$s: closing anchor tag */
						__( '%1$sClick here%2$s to enter and activate it.', 'buddyboss' ),
						'<a href="' . esc_url( $activation_link ) . '">',
						'</a>'
					),
					__( 'That's it!', 'buddyboss' )
				);
				break;

			case self::DRM_MEDIUM:
				$admin_notice_view = 'medium_warning';
				$heading           = __( 'BuddyBoss: WARNING! Your Community is at Risk', 'buddyboss' );
				$color             = 'orange';
				$simple_message    = __( 'To continue using BuddyBoss without interruption, you need to enter your license key right away. Here's how:', 'buddyboss' );
				$help_message      = __( 'Let us know if you need assistance.', 'buddyboss' );
				$label             = __( 'Critical', 'buddyboss' );
				$activation_link   = bp_get_admin_url( 'admin.php?page=buddyboss-license' );
				$message           = sprintf(
					'<p>%s</p><ul><li>%s</li><li>%s</li><li>%s</li></ul>',
					$simple_message,
					sprintf(
						/* translators: %1$s: opening anchor tag, %2$s: closing anchor tag */
						__( 'Grab your key from your %1$sAccount Page%2$s.', 'buddyboss' ),
						'<a href="' . esc_url( $account_link ) . '">',
						'</a>'
					),
					sprintf(
						/* translators: %1$s: opening anchor tag, %2$s: closing anchor tag */
						__( '%1$sClick here%2$s to enter and activate it.', 'buddyboss' ),
						'<a href="' . esc_url( $activation_link ) . '">',
						'</a>'
					),
					__( 'That's it!', 'buddyboss' )
				);
				break;

			case self::DRM_LOCKED:
				$admin_notice_view = 'locked_warning';
				$heading           = __( 'ALERT! BuddyBoss Backend is Deactivated', 'buddyboss' );
				$color             = 'red';
				$simple_message    = __( 'Because your license key is inactive, you can no longer manage BuddyBoss on the backend. Fortunately, this problem is easy to fix!', 'buddyboss' );
				$help_message      = __( 'We\'re here to help you get things up and running. Let us know if you need assistance.', 'buddyboss' );
				$label             = __( 'Critical', 'buddyboss' );
				$activation_link   = bp_get_admin_url( 'admin.php?page=buddyboss-license' );
				$message           = sprintf(
					'<p>%s</p><ul><li>%s</li><li>%s</li><li>%s</li></ul>',
					$simple_message,
					sprintf(
						/* translators: %1$s: opening anchor tag, %2$s: closing anchor tag */
						__( 'Grab your key from your %1$sAccount Page%2$s.', 'buddyboss' ),
						'<a href="' . esc_url( $account_link ) . '">',
						'</a>'
					),
					sprintf(
						/* translators: %1$s: opening anchor tag, %2$s: closing anchor tag */
						__( '%1$sClick here%2$s to enter and activate it.', 'buddyboss' ),
						'<a href="' . esc_url( $activation_link ) . '">',
						'</a>'
					),
					__( 'That's it!', 'buddyboss' )
				);
				break;

			default:
				$heading                 = '';
				$color                   = '';
				$message                 = '';
				$help_message            = '';
				$label                   = '';
				$activation_link         = '';
				$admin_notice_view       = '';
				$simple_message          = '';
				$additional_instructions = '';
		}

		return compact( 'heading', 'color', 'message', 'simple_message', 'help_message', 'label', 'activation_link', 'account_link', 'support_link', 'pricing_link', 'admin_notice_view', 'additional_instructions' );
	}

	/**
	 * Get DRM information for invalid license event.
	 *
	 * @param string $drm_status The DRM status.
	 * @param string $purpose    The purpose of the information.
	 * @return array The DRM information.
	 */
	protected static function drm_info_invalid_license( $drm_status, $purpose ) {
		$account_link            = self::get_drm_link( $drm_status, $purpose, 'account' );
		$support_link            = self::get_drm_link( $drm_status, $purpose, 'support' );
		$pricing_link            = self::get_drm_link( $drm_status, $purpose, 'pricing' );
		$additional_instructions = sprintf(
			/* translators: %1$s: home URL, %2$s: home URL */
			__( 'This is an automated message from %1$s. If you continue getting these messages, please try deactivating and then re-activating your license key on %2$s.', 'buddyboss' ),
			esc_url( home_url() ),
			esc_url( home_url() )
		);

		switch ( $drm_status ) {
			case self::DRM_MEDIUM:
				$admin_notice_view = 'medium_warning';
				$heading           = __( 'BuddyBoss: WARNING! Your Community is at Risk', 'buddyboss' );
				$color             = 'orange';
				$simple_message    = __( 'Your BuddyBoss license key is expired, but is required to continue using BuddyBoss. Fortunately, it's easy to renew your license key. Just do the following:', 'buddyboss' );
				$help_message      = __( 'Let us know if you need assistance.', 'buddyboss' );
				$label             = __( 'Critical', 'buddyboss' );
				$activation_link   = bp_get_admin_url( 'admin.php?page=buddyboss-license' );
				$message           = sprintf(
					'<p>%s</p><ul><li>%s</li><li>%s</li><li>%s</li></ul>',
					$simple_message,
					sprintf(
						/* translators: %1$s: opening anchor tag, %2$s: closing anchor tag */
						__( 'Go to BuddyBoss.com and make your selection. %1$sPricing%2$s.', 'buddyboss' ),
						'<a href="' . esc_url( $pricing_link ) . '">',
						'</a>'
					),
					sprintf(
						/* translators: %1$s: opening anchor tag, %2$s: closing anchor tag */
						__( '%1$sClick here%2$s to enter and activate your new license key.', 'buddyboss' ),
						'<a href="' . esc_url( $activation_link ) . '">',
						'</a>'
					),
					__( 'That's it!', 'buddyboss' )
				);
				break;

			case self::DRM_LOCKED:
				$admin_notice_view = 'locked_warning';
				$label             = __( 'Critical', 'buddyboss' );
				$heading           = __( 'ALERT! BuddyBoss Backend is Deactivated', 'buddyboss' );
				$color             = 'red';
				$simple_message    = __( 'Without an active license key, BuddyBoss cannot be managed on the backend. Your frontend will remain intact, but you can\'t manage your community. Fortunately, this problem is easy to fix by doing the following:', 'buddyboss' );
				$activation_link   = bp_get_admin_url( 'admin.php?page=buddyboss-license' );
				$message           = sprintf(
					'<p>%s</p><ul><li>%s</li><li>%s</li><li>%s</li></ul>',
					$simple_message,
					sprintf(
						/* translators: %1$s: opening anchor tag, %2$s: closing anchor tag */
						__( 'Go to BuddyBoss.com and make your selection. %1$sPricing%2$s.', 'buddyboss' ),
						'<a href="' . esc_url( $pricing_link ) . '">',
						'</a>'
					),
					sprintf(
						/* translators: %1$s: opening anchor tag, %2$s: closing anchor tag */
						__( '%1$sClick here%2$s to enter and activate your new license key.', 'buddyboss' ),
						'<a href="' . esc_url( $activation_link ) . '">',
						'</a>'
					),
					__( 'That's it!', 'buddyboss' )
				);
				$help_message      = __( 'We're here to help you get things back up and running. Let us know if you need assistance.', 'buddyboss' );
				break;

			default:
				$heading                 = '';
				$color                   = '';
				$message                 = '';
				$help_message            = '';
				$label                   = '';
				$activation_link         = '';
				$admin_notice_view       = '';
				$simple_message          = '';
				$additional_instructions = '';
		}

		return compact( 'heading', 'color', 'message', 'simple_message', 'help_message', 'label', 'activation_link', 'account_link', 'support_link', 'admin_notice_view', 'pricing_link', 'additional_instructions' );
	}

	/**
	 * Parse event arguments from a JSON string.
	 *
	 * @param string $args The JSON string of arguments.
	 * @return array The parsed event arguments.
	 */
	public static function parse_event_args( $args ) {
		$parsed = json_decode( $args, true );
		return is_array( $parsed ) ? $parsed : array();
	}

	/**
	 * Prepare a dismissable notice key.
	 *
	 * @param string $notice The notice identifier.
	 * @return string The dismissable notice key.
	 */
	public static function prepare_dismissable_notice_key( $notice ) {
		$notice = sanitize_key( $notice );
		return "{$notice}_u" . get_current_user_id();
	}

	/**
	 * Check if a notice is dismissed.
	 *
	 * @param array  $event_data The event data.
	 * @param string $notice_key The notice key.
	 * @return bool True if dismissed, false otherwise.
	 */
	public static function is_dismissed( $event_data, $notice_key ) {
		if ( isset( $event_data[ $notice_key ] ) ) {
			$diff = (int) abs( time() - $event_data[ $notice_key ] );
			if ( $diff <= ( HOUR_IN_SECONDS * 24 ) ) {
				return true;
			}
		}

		return false;
	}
}
