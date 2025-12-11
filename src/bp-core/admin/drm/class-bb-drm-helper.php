<?php
/**
 * BuddyBoss DRM Helper
 *
 * Helper class for DRM (Digital Rights Management) functionality.
 * Manages license validation, status checks, and DRM messaging.
 *
 * @package BuddyBoss\Core\Admin\DRM
 * @since BuddyBoss [BBVERSION]
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
	 * DRM status: Low (informational notification - days 7-13).
	 */
	const DRM_LOW = 'low';

	/**
	 * DRM status: Medium (yellow warning - days 14-20).
	 */
	const DRM_MEDIUM = 'medium';

	/**
	 * DRM status: High (orange warning - days 21-29).
	 */
	const DRM_HIGH = 'high';

	/**
	 * DRM status: Locked (red warning, backend disabled - days 30+).
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
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $status The DRM status to set.
	 */
	public static function set_status( $status ) {
		self::$drm_status = $status;
	}

	/**
	 * Get the current DRM status.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return string The current DRM status.
	 */
	public static function get_status() {
		return self::$drm_status;
	}

	/**
	 * Check if a license key exists.
	 *
	 * @since BuddyBoss [BBVERSION]
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
	 * @since BuddyBoss [BBVERSION]
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
	 * Includes automatic development environment bypass for better developer experience.
	 * No configuration needed - automatically detects localhost, .local, .test, etc.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return bool True if the license is valid, false otherwise.
	 */
	public static function is_valid() {
		// CRITICAL: Automatic dev environment bypass (no configuration needed).
		// This prevents DRM from triggering on localhost, staging, etc.
		if ( self::is_dev_environment() ) {
			return true;
		}

		// Check manual activation override (for special cases).
		if ( get_option( 'bb_drm_activation_override', false ) ) {
			return true;
		}

		// Check global DRM enable filter.
		if ( ! apply_filters( 'bb_drm_enabled', true ) ) {
			return true;
		}

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
	 * Check if current environment is a development environment.
	 *
	 * Automatically detects:
	 * - WordPress environment type (local, development, staging)
	 * - Development URLs (localhost, .local, .test, .dev domains)
	 * - Local IP addresses (127.0.0.1, 192.168.x.x, 10.x.x.x)
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return bool True if development environment detected.
	 */
	public static function is_dev_environment() {
		// Check WordPress environment type (WordPress 5.5+).
		if ( function_exists( 'wp_get_environment_type' ) ) {
			$env = wp_get_environment_type();

			// Bypass for non-production environments.
			if ( in_array( $env, array( 'local', 'development', 'staging' ), true ) ) {
				return true;
			}
		}

		// Check if URL is a development URL.
		return self::is_dev_url();
	}

	/**
	 * Detect if the current site URL is a development URL.
	 *
	 * Comprehensive staging/development detection matching bb_pro_check_staging_server().
	 * Checks for:
	 * - localhost variations (localhost, 127.0.0.1, ::1)
	 * - Development TLDs (.local, .test, .dev, .localhost, .invalid, .example, .staging)
	 * - Local IP addresses (10.x.x.x, 172.16-31.x.x, 192.168.x.x)
	 * - Reserved testing/staging keywords in domain (dev, develop, test, staging, etc.)
	 * - Hosting provider staging domains (Kinsta, WP Engine, Cloudways, etc.)
	 * - Local development tool domains (DDEV, Lando, ngrok, etc.)
	 * - Non-standard ports (anything except 80, 443)
	 * - WordPress staging constants (WP_STAGING, etc.)
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return bool True if development URL detected.
	 */
	private static function is_dev_url() {
		$url        = site_url();
		$parsed_url = wp_parse_url( $url );
		$host       = isset( $parsed_url['host'] ) ? $parsed_url['host'] : '';

		if ( empty( $host ) ) {
			return false;
		}

		// Remove www prefix if present.
		$domain = preg_replace( '/^www\./i', '', $host );

		// Check if domain is localhost or an IP address.
		if ( 'localhost' === $domain || filter_var( $domain, FILTER_VALIDATE_IP ) ) {
			return true;
		}

		// Check for port numbers (often indicates local development).
		if ( isset( $parsed_url['port'] ) && ! in_array( $parsed_url['port'], array( 80, 443 ), true ) ) {
			return true;
		}

		// Reserved TLDs for local development.
		$reserved_tlds = array(
			'local',
			'localhost',
			'test',
			'example',
			'invalid',
			'dev',
			'staging',
		);

		// Extract domain parts.
		$domain_parts = explode( '.', $domain );
		$tld          = end( $domain_parts );

		// Check for reserved TLDs.
		if ( in_array( $tld, $reserved_tlds, true ) ) {
			return true;
		}

		// Reserved words that indicate testing/staging environments.
		$reserved_words = array(
			'dev',
			'develop',
			'development',
			'test',
			'testing',
			'stg',
			'stage',
			'staging',
			'demo',
			'sandbox',
			'preview',
		);

		// Check for reserved testing words in subdomains.
		$subdomain_pattern = '/(\.|-)(' . implode( '|', $reserved_words ) . ')(\.|-)|(^(' . implode( '|', $reserved_words ) . ')\.)/i';
		if ( preg_match( $subdomain_pattern, $domain ) ) {
			return true;
		}

		// Reserved hosting provider domains that indicate staging/development.
		$reserved_hosting_provider_domains = array(
			'accessdomain',     // Generic hosting.
			'cloudwaysapps',    // Cloudways.
			'flywheelsites',    // Flywheel.
			'kinsta',           // Kinsta.
			'mybluehost',       // BlueHost.
			'myftpupload',      // GoDaddy.
			'netsolhost',       // Network Solutions.
			'pantheonsite',     // Pantheon.
			'sg-host',          // SiteGround.
			'wpengine',         // WP Engine (old).
			'wpenginepowered',  // WP Engine.
			'rapydapps.cloud',  // Rapyd.
		);

		// Check for known hosting provider staging domains.
		$hosting_pattern = '/\.(' . implode( '|', $reserved_hosting_provider_domains ) . ')\./i';
		if ( preg_match( $hosting_pattern, '.' . $domain . '.' ) ) {
			return true;
		}

		// Known local development tool domains.
		$reserved_local_domains = array(
			'lndo.site',        // Lando.
			'ddev.site',        // DDEV.
			'docksal',          // Docksal.
			'localwp.com',      // Local by Flywheel.
			'local.test',       // Generic local.
			'docker.internal',  // Docker.
			'ngrok.io',         // ngrok tunneling.
			'localtunnel.me',   // localtunnel.
		);

		// Check for known development tool domains.
		$dev_tools_pattern = '/(' . implode( '|', array_map( 'preg_quote', $reserved_local_domains ) ) . ')$/i';
		if ( preg_match( $dev_tools_pattern, $domain ) ) {
			return true;
		}

		// Check for common WordPress staging constants.
		if ( defined( 'WP_STAGING' ) && WP_STAGING ) {
			return true;
		}

		// Additional WordPress multisite check.
		if ( is_multisite() ) {
			$network_domain = parse_url( network_site_url(), PHP_URL_HOST );
			if ( $network_domain !== $domain ) {
				// Check if this is a staging subdomain in multisite.
				if ( preg_match( $subdomain_pattern, $network_domain ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Calculate the number of days elapsed since a given date.
	 *
	 * @since BuddyBoss [BBVERSION]
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
	 * @since BuddyBoss [BBVERSION]
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
	 * @since BuddyBoss [BBVERSION]
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
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $drm_status The DRM status to check.
	 * @return bool True if medium, false otherwise.
	 */
	public static function is_medium( $drm_status = '' ) {
		return ( self::DRM_MEDIUM === self::maybe_drm_status( $drm_status ) );
	}

	/**
	 * Check if the DRM status is high.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $drm_status The DRM status to check.
	 * @return bool True if high, false otherwise.
	 */
	public static function is_high( $drm_status = '' ) {
		return ( self::DRM_HIGH === self::maybe_drm_status( $drm_status ) );
	}

	/**
	 * Check if the DRM status is low.
	 *
	 * @since BuddyBoss [BBVERSION]
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
	 * @since BuddyBoss [BBVERSION]
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
			case self::DRM_HIGH:
				$out = 'dh';
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
	 * @since BuddyBoss [BBVERSION]
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
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return array The DRM links.
	 */
	protected static function get_drm_links() {
		if ( null === self::$drm_links ) {
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
				self::DRM_HIGH   => array(
					'email'   => array(
						'home'    => 'https://www.buddyboss.com/drmhigh/email',
						'account' => 'https://www.buddyboss.com/drmhigh/email/acct',
						'support' => 'https://www.buddyboss.com/drmhigh/email/support',
						'pricing' => 'https://www.buddyboss.com/drmhigh/email/pricing',
					),
					'general' => array(
						'home'    => 'https://www.buddyboss.com/drmhigh/ipm',
						'account' => 'https://www.buddyboss.com/drmhigh/ipm/account',
						'support' => 'https://www.buddyboss.com/drmhigh/ipm/support',
						'pricing' => 'https://www.buddyboss.com/drmhigh/ipm/pricing',
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
	 * @since BuddyBoss [BBVERSION]
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
	 * Get DRM information for a no-license event.
	 *
	 * @since BuddyBoss [BBVERSION]
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
				// 7-13 days: Plugin Notification only (per BuddyBoss DRM Messaging.md)
				$admin_notice_view = 'low_notification';
				$heading           = __( 'License Activation Needed', 'buddyboss' );
				$color             = 'FFA500'; // Orange.
				$simple_message    = __( 'We couldn\'t verify an active license for your BuddyBoss features. Please activate your license to continue using them.', 'buddyboss' );
				$help_message      = __( 'Activate Your License', 'buddyboss' );
				$label             = __( 'Notice', 'buddyboss' );
				$activation_link   = bp_get_admin_url( 'admin.php?page=buddyboss-license' );
				$message           = sprintf(
					'<p>%s</p><p>%s</p>',
					$simple_message,
					sprintf(
						/* translators: %1$s: opening anchor tag for account page, %2$s: closing anchor tag, %3$s: opening anchor tag for support */
						__( 'Need your license key? Visit %1$sYour Account Page%2$s. Having trouble? %3$sContact Support%2$s.', 'buddyboss' ),
						'<a href="' . esc_url( $account_link ) . '" target="_blank">',
						'</a>',
						'<a href="' . esc_url( $support_link ) . '" target="_blank">'
					)
				);
				break;

			case self::DRM_MEDIUM:
				// 14-21 days: Admin Notice (Yellow) + Plugin Notification + Site Health (per BuddyBoss DRM Messaging.md)
				$admin_notice_view = 'medium_warning';
				$heading           = __( 'License Required', 'buddyboss' );
				$color             = 'FFA500'; // Yellow/Orange.
				$simple_message    = __( 'An active license is required to use BuddyBoss features. Without activation, these features will stop working.', 'buddyboss' );
				$help_message      = __( 'Activate Your License', 'buddyboss' );
				$label             = __( 'Warning', 'buddyboss' );
				$activation_link   = bp_get_admin_url( 'admin.php?page=buddyboss-license' );
				$message           = sprintf(
					'<p>%s</p><p>%s</p>',
					$simple_message,
					sprintf(
						/* translators: %1$s: opening anchor tag for account page, %2$s: closing anchor tag, %3$s: opening anchor tag for support */
						__( 'Need your license key? Visit %1$sYour Account Page%2$s. Having trouble? %3$sContact Support%2$s.', 'buddyboss' ),
						'<a href="' . esc_url( $account_link ) . '" target="_blank">',
						'</a>',
						'<a href="' . esc_url( $support_link ) . '" target="_blank">'
					)
				);
				break;

			case self::DRM_HIGH:
				// 21-30 days: Admin Notice (Orange) + Plugin Notification + Site Health + Email (per BuddyBoss DRM Messaging.md)
				$admin_notice_view = 'high_warning';
				$heading           = __( 'License Activation Required', 'buddyboss' );
				$color             = 'FF8C00'; // Dark Orange.
				$simple_message    = __( 'Your BuddyBoss features will be disabled soon. Activate your license now to avoid interruption.', 'buddyboss' );
				$help_message      = __( 'Activate Your License', 'buddyboss' );
				$label             = __( 'Critical', 'buddyboss' );
				$activation_link   = bp_get_admin_url( 'admin.php?page=buddyboss-license' );
				$message           = sprintf(
					'<p>%s</p><p>%s</p>',
					$simple_message,
					sprintf(
						/* translators: %1$s: opening anchor tag for account page, %2$s: closing anchor tag, %3$s: opening anchor tag for support */
						__( 'Need your license key? Visit %1$sYour Account Page%2$s. Having trouble? %3$sContact Support%2$s.', 'buddyboss' ),
						'<a href="' . esc_url( $account_link ) . '" target="_blank">',
						'</a>',
						'<a href="' . esc_url( $support_link ) . '" target="_blank">'
					)
				);
				break;

			case self::DRM_LOCKED:
				// 30+ days: Features Disabled (Red) + Plugin Notification + Site Health + Email (per BuddyBoss DRM Messaging.md)
				$admin_notice_view = 'locked_warning';
				$heading           = __( 'BuddyBoss Features Disabled', 'buddyboss' );
				$color             = 'dc3232'; // Red.
				$simple_message    = __( ' The following features have been disabled because no active license was found. Activate your license to restore them.', 'buddyboss' );
				$help_message      = __( 'Activate Your License', 'buddyboss' );
				$label             = __( 'Critical', 'buddyboss' );
				$activation_link   = bp_get_admin_url( 'admin.php?page=buddyboss-license' );
				$message           = sprintf(
					'<p>%s</p><p>%s</p><p>%s</p>',
					$simple_message,
					__( 'If you no longer need these features, you can deactivate the premium add-ons in your Plugins page to continue using BuddyBoss Platform for free.', 'buddyboss' ),
					sprintf(
						/* translators: %1$s: opening anchor tag for account page, %2$s: closing anchor tag, %3$s: opening anchor tag for support */
						__( 'Need your license key? Visit %1$sYour Account Page%2$s. Having trouble? %3$sContact Support%2$s.', 'buddyboss' ),
						'<a href="' . esc_url( $account_link ) . '" target="_blank">',
						'</a>',
						'<a href="' . esc_url( $support_link ) . '" target="_blank">'
					)
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
	 * Get DRM information for an invalid license event.
	 *
	 * @since BuddyBoss [BBVERSION]
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
			case self::DRM_LOW:
				// 7-13 days: Plugin Notification only (per BuddyBoss DRM Messaging.md)
				$admin_notice_view = 'low_notification';
				$heading           = __( 'BuddyBoss Pro/Plus: License Activation Needed', 'buddyboss' );
				$color             = 'FFA500'; // Orange.
				$simple_message    = __( 'We couldn\'t verify an active license for your BuddyBoss Pro/Plus features. Please activate your license to continue using them.', 'buddyboss' );
				$help_message      = __( 'Activate Your License', 'buddyboss' );
				$label             = __( 'Notice', 'buddyboss' );
				$activation_link   = bp_get_admin_url( 'admin.php?page=buddyboss-license' );
				$message           = sprintf(
					'<p>%s</p><p>%s</p>',
					$simple_message,
					sprintf(
						/* translators: %1$s: opening anchor tag for account page, %2$s: closing anchor tag, %3$s: opening anchor tag for support */
						__( 'Need your license key? Visit %1$sYour Account Page%2$s. Having trouble? %3$sContact Support%2$s.', 'buddyboss' ),
						'<a href="' . esc_url( $account_link ) . '" target="_blank">',
						'</a>',
						'<a href="' . esc_url( $support_link ) . '" target="_blank">'
					)
				);
				break;

			case self::DRM_MEDIUM:
				// 14-21 days: Admin Notice (Yellow) + Plugin Notification + Site Health (per BuddyBoss DRM Messaging.md)
				$admin_notice_view = 'medium_warning';
				$heading           = __( 'BuddyBoss Pro/Plus: License Required', 'buddyboss' );
				$color             = 'FFA500'; // Yellow/Orange.
				$simple_message    = __( 'An active license is required to use BuddyBoss Pro/Plus features. Without activation, these features will stop working.', 'buddyboss' );
				$help_message      = __( 'Activate Your License', 'buddyboss' );
				$label             = __( 'Warning', 'buddyboss' );
				$activation_link   = bp_get_admin_url( 'admin.php?page=buddyboss-license' );
				$message           = sprintf(
					'<p>%s</p><p>%s</p>',
					$simple_message,
					sprintf(
						/* translators: %1$s: opening anchor tag for account page, %2$s: closing anchor tag, %3$s: opening anchor tag for support */
						__( 'Need your license key? Visit %1$sYour Account Page%2$s. Having trouble? %3$sContact Support%2$s.', 'buddyboss' ),
						'<a href="' . esc_url( $account_link ) . '" target="_blank">',
						'</a>',
						'<a href="' . esc_url( $support_link ) . '" target="_blank">'
					)
				);
				break;

			case self::DRM_HIGH:
				// 21-30 days: Admin Notice (Orange) + Plugin Notification + Site Health + Email (per BuddyBoss DRM Messaging.md)
				$admin_notice_view = 'high_warning';
				$heading           = __( 'BuddyBoss Pro/Plus: Activation Required', 'buddyboss' );
				$color             = 'FF8C00'; // Dark Orange.
				$simple_message    = __( 'Your BuddyBoss Pro/Plus features will be disabled soon. Activate your license now to avoid interruption.', 'buddyboss' );
				$help_message      = __( 'Activate Your License', 'buddyboss' );
				$label             = __( 'Critical', 'buddyboss' );
				$activation_link   = bp_get_admin_url( 'admin.php?page=buddyboss-license' );
				$message           = sprintf(
					'<p>%s</p><p>%s</p>',
					$simple_message,
					sprintf(
						/* translators: %1$s: opening anchor tag for account page, %2$s: closing anchor tag, %3$s: opening anchor tag for support */
						__( 'Need your license key? Visit %1$sYour Account Page%2$s. Having trouble? %3$sContact Support%2$s.', 'buddyboss' ),
						'<a href="' . esc_url( $account_link ) . '" target="_blank">',
						'</a>',
						'<a href="' . esc_url( $support_link ) . '" target="_blank">'
					)
				);
				break;

			case self::DRM_LOCKED:
				// 30+ days: Features Disabled (Red) + Plugin Notification + Site Health + Email (per BuddyBoss DRM Messaging.md)
				$admin_notice_view = 'locked_warning';
				$label             = __( 'Critical', 'buddyboss' );
				$heading           = __( 'BuddyBoss Pro/Plus: Features Disabled', 'buddyboss' );
				$color             = 'dc3232'; // Red.
				$simple_message    = __( 'The following features have been disabled because no active license was found. Activate your license to restore them.', 'buddyboss' );
				$activation_link   = bp_get_admin_url( 'admin.php?page=buddyboss-license' );
				$message           = sprintf(
					'<p>%s</p><p>%s</p><p>%s</p>',
					$simple_message,
					__( 'If you no longer need these features, you can deactivate the premium add-ons in your Plugins page to continue using BuddyBoss Platform for free.', 'buddyboss' ),
					sprintf(
						/* translators: %1$s: opening anchor tag for account page, %2$s: closing anchor tag, %3$s: opening anchor tag for support */
						__( 'Need your license key? Visit %1$sYour Account Page%2$s. Having trouble? %3$sContact Support%2$s.', 'buddyboss' ),
						'<a href="' . esc_url( $account_link ) . '" target="_blank">',
						'</a>',
						'<a href="' . esc_url( $support_link ) . '" target="_blank">'
					)
				);
				$help_message = __( 'Activate Your License', 'buddyboss' );
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
	 * NOTE: This method is kept for backward compatibility with external plugins.
	 * It is not used internally by BuddyBoss DRM.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $args The JSON string of arguments.
	 * @return array The parsed event arguments.
	 */
	public static function parse_event_args( $args ) {
		$parsed = json_decode( $args, true );
		return is_array( $parsed ) ? $parsed : array();
	}

	/**
	 * Prepare a dismissible notice key.
	 *
	 * @since BuddyBoss [BBVERSION]
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
	 * Per-user dismissal tracking: each user can dismiss notices independently.
	 * Dismissals are stored in event_data with format:
	 * - dismissed_users => array( user_id => timestamp, ... )
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array  $event_data The event data.
	 * @param string $notice_key The notice key.
	 * @return bool True if dismissed, false otherwise.
	 */
	public static function is_dismissed( $event_data, $notice_key ) {
		// Ensure event_data is an array (handle both array and object).
		if ( is_object( $event_data ) ) {
			$event_data = (array) $event_data;
		}

		// If not an array or empty, return false.
		if ( ! is_array( $event_data ) || empty( $event_data ) ) {
			return false;
		}

		$user_id = get_current_user_id();

		// Check per-user dismissal (new method).
		if ( isset( $event_data['dismissed_users'] ) ) {
			// Ensure dismissed_users is also an array (handle nested objects).
			$dismissed_users = $event_data['dismissed_users'];
			if ( is_object( $dismissed_users ) ) {
				$dismissed_users = (array) $dismissed_users;
			}

			if ( is_array( $dismissed_users ) && isset( $dismissed_users[ $notice_key ] ) ) {
				// Ensure the notice_key array is also an array.
				$notice_data = $dismissed_users[ $notice_key ];
				if ( is_object( $notice_data ) ) {
					$notice_data = (array) $notice_data;
				}

				if ( is_array( $notice_data ) && isset( $notice_data[ $user_id ] ) ) {
					$dismissed_time = $notice_data[ $user_id ];
					$diff           = (int) abs( time() - $dismissed_time );

					// Dismissed for 24 hours.
					if ( $diff <= ( HOUR_IN_SECONDS * 24 ) ) {
						return true;
					}
				}
			}
		}

		// Fallback to old global dismissal method (for backwards compatibility).
		if ( isset( $event_data[ $notice_key ] ) ) {
			$diff = (int) abs( time() - $event_data[ $notice_key ] );
			if ( $diff <= ( HOUR_IN_SECONDS * 24 ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Record a per-user notice dismissal.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param BB_DRM_Event $event      The DRM event.
	 * @param string       $notice_key The notice key.
	 * @return bool True on success, false on failure.
	 */
	public static function dismiss_notice_for_user( $event, $notice_key ) {
		if ( ! $event instanceof BB_DRM_Event || $event->id <= 0 ) {
			return false;
		}

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return false;
		}

		// Get current event data.
		$args       = $event->get_args();
		$event_data = is_object( $args ) ? (array) $args : ( is_array( $args ) ? $args : array() );

		// Initialize dismissed_users structure if not exists.
		if ( ! isset( $event_data['dismissed_users'] ) ) {
			$event_data['dismissed_users'] = array();
		}

		if ( ! isset( $event_data['dismissed_users'][ $notice_key ] ) ) {
			$event_data['dismissed_users'][ $notice_key ] = array();
		}

		// Record dismissal for this user.
		$event_data['dismissed_users'][ $notice_key ][ $user_id ] = time();

		// Update event with new data.
		$event->args = wp_json_encode( $event_data );
		return (bool) $event->store();
	}
}
