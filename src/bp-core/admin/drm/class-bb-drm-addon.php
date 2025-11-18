<?php
/**
 * BuddyBoss DRM Add-on
 *
 * Handles DRM checks for specific add-on plugins.
 * This class can be instantiated by any BuddyBoss add-on plugin to enforce license requirements.
 *
 * @package BuddyBoss\Core\Admin\DRM
 * @since 3.0.0
 */

namespace BuddyBoss\Core\Admin\DRM;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * DRM Add-on class for validating individual add-on licenses.
 */
class BB_DRM_Addon extends BB_Base_DRM {

	/**
	 * The product slug for this add-on (e.g., 'buddyboss-platform-pro').
	 *
	 * @var string
	 */
	protected $product_slug = '';

	/**
	 * The add-on plugin name for display.
	 *
	 * @var string
	 */
	protected $plugin_name = '';

	/**
	 * Constructor for the BB_DRM_Addon class.
	 *
	 * @param string $product_slug The Mothership product slug.
	 * @param string $plugin_name  The plugin display name.
	 */
	public function __construct( $product_slug, $plugin_name = '' ) {
		$this->product_slug = $product_slug;
		$this->plugin_name  = $plugin_name ?: $product_slug;
		$this->event_name   = 'addon-' . sanitize_key( $product_slug );

		parent::__construct();

		// Hook into DRM event for this specific add-on.
		add_action( 'bb_drm_addon_event_' . $this->product_slug, array( $this, 'drm_event' ), 10, 3 );
	}

	/**
	 * Runs the DRM check for this add-on.
	 *
	 * @return void
	 */
	public function run() {
		// Check if license is valid for this specific add-on.
		if ( $this->is_addon_licensed() ) {
			// License is valid, no DRM needed.
			return;
		}

		// Get the event for this add-on.
		$event = $this->get_latest_event();

		if ( $event ) {
			$days = BB_DRM_Helper::days_elapsed( $event->created_at );

			// Faster timeline for paid add-ons.
			if ( $days >= 7 && $days <= 13 ) {
				$this->set_status( BB_DRM_Helper::DRM_LOW );
			} elseif ( $days >= 14 && $days <= 20 ) {
				$this->set_status( BB_DRM_Helper::DRM_MEDIUM );
			} elseif ( $days >= 21 ) {
				$this->set_status( BB_DRM_Helper::DRM_LOCKED );
			}
		}

		// DRM status detected - fire the event.
		if ( '' !== $this->drm_status ) {
			do_action( 'bb_drm_addon_event_' . $this->product_slug, $event, $days, $this->drm_status );
		}
	}

	/**
	 * Check if this specific add-on has a valid license.
	 *
	 * @return bool True if licensed, false otherwise.
	 */
	public function is_addon_licensed() {
		// Check if on staging server (always allow).
		if ( $this->is_staging_server() ) {
			return true;
		}

		// Check if Platform has valid license.
		if ( ! BB_DRM_Helper::is_valid() ) {
			return false;
		}

		// Check if this specific product is enabled in Mothership.
		if ( ! class_exists( '\BuddyBoss\Core\Admin\Mothership\BB_Addons_Manager' ) ) {
			return false;
		}

		return \BuddyBoss\Core\Admin\Mothership\BB_Addons_Manager::checkProductBySlug( $this->product_slug );
	}

	/**
	 * Check if running on staging server.
	 *
	 * @return bool True if staging server.
	 */
	private function is_staging_server() {
		// Use Platform Pro function if available.
		if ( function_exists( 'bb_pro_check_staging_server' ) ) {
			return bb_pro_check_staging_server();
		}

		// Otherwise use our own detection.
		return $this->check_staging_environment();
	}

	/**
	 * Staging environment detection.
	 *
	 * @return bool True if staging environment detected.
	 */
	private function check_staging_environment() {
		$raw_domain = site_url();

		// Reserved hosting provider domains.
		$reserved_hosting_provider_domains = array(
			'accessdomain',
			'cloudwaysapps',
			'flywheelsites',
			'kinsta',
			'mybluehost',
			'myftpupload',
			'netsolhost',
			'pantheonsite',
			'sg-host',
			'wpengine',
			'wpenginepowered',
			'rapydapps.cloud',
		);

		// Reserved words.
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

		// Reserved TLDs.
		$reserved_tlds = array(
			'local',
			'localhost',
			'test',
			'example',
			'invalid',
			'dev',
		);

		// Check for reserved hosting provider domains.
		foreach ( $reserved_hosting_provider_domains as $provider_domain ) {
			if ( stripos( $raw_domain, $provider_domain ) !== false ) {
				return true;
			}
		}

		// Parse the domain.
		$parsed_url = wp_parse_url( $raw_domain );
		$host       = isset( $parsed_url['host'] ) ? $parsed_url['host'] : '';

		// Check for reserved words in domain.
		foreach ( $reserved_words as $word ) {
			if ( stripos( $host, $word ) !== false ) {
				return true;
			}
		}

		// Check for reserved TLDs.
		$host_parts = explode( '.', $host );
		$tld        = end( $host_parts );
		if ( in_array( strtolower( $tld ), $reserved_tlds, true ) ) {
			return true;
		}

		// Check for IP addresses.
		if ( filter_var( $host, FILTER_VALIDATE_IP ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get DRM information specific to this add-on.
	 *
	 * @param string $drm_status The DRM status.
	 * @param string $purpose    The purpose of the information.
	 * @return array The DRM information.
	 */
	protected function get_addon_drm_info( $drm_status, $purpose ) {
		$account_link = BB_DRM_Helper::get_drm_link( $drm_status, $purpose, 'account' );
		$support_link = BB_DRM_Helper::get_drm_link( $drm_status, $purpose, 'support' );
		$pricing_link = BB_DRM_Helper::get_drm_link( $drm_status, $purpose, 'pricing' );

		$activation_link = bp_get_admin_url( 'admin.php?page=buddyboss-license' );

		switch ( $drm_status ) {
			case BB_DRM_Helper::DRM_LOW:
				$heading = sprintf(
					/* translators: %s: plugin name */
					__( '%s: License Required', 'buddyboss' ),
					$this->plugin_name
				);
				$message = sprintf(
					/* translators: %s: plugin name */
					__( '%s requires an active license to continue working. Please activate your license key.', 'buddyboss' ),
					$this->plugin_name
				);
				$color   = 'orange';
				$label   = __( 'Warning', 'buddyboss' );
				break;

			case BB_DRM_Helper::DRM_MEDIUM:
				$heading = sprintf(
					/* translators: %s: plugin name */
					__( '%s: URGENT - License Required', 'buddyboss' ),
					$this->plugin_name
				);
				$message = sprintf(
					/* translators: %s: plugin name */
					__( '%s will be disabled soon without an active license. Please activate immediately.', 'buddyboss' ),
					$this->plugin_name
				);
				$color   = 'orange';
				$label   = __( 'Critical', 'buddyboss' );
				break;

			case BB_DRM_Helper::DRM_LOCKED:
				$heading = sprintf(
					/* translators: %s: plugin name */
					__( '%s: Features Disabled', 'buddyboss' ),
					$this->plugin_name
				);
				$message = sprintf(
					/* translators: %s: plugin name */
					__( '%s features have been disabled due to an inactive license. Activate your license to restore functionality.', 'buddyboss' ),
					$this->plugin_name
				);
				$color   = 'red';
				$label   = __( 'Critical', 'buddyboss' );
				break;

			default:
				$heading = '';
				$message = '';
				$color   = '';
				$label   = '';
		}

		return compact( 'heading', 'color', 'message', 'label', 'activation_link', 'account_link', 'support_link', 'pricing_link' );
	}

	/**
	 * Creates an in-plugin notification for this add-on.
	 *
	 * @param string $drm_status The DRM status.
	 */
	protected function create_inplugin_notification( $drm_status ) {
		$drm_info = $this->get_addon_drm_info( $drm_status, 'inplugin' );
		if ( empty( $drm_info['heading'] ) ) {
			return;
		}

		$icon_url = buddypress()->plugin_url . 'bp-core/admin/assets/images/alert-icon.png';

		$notifications = new BB_Notifications();
		$notifications->add(
			array(
				'id'      => 'addon_' . $this->product_slug . '_' . time(),
				'title'   => $drm_info['heading'],
				'content' => $drm_info['message'],
				'type'    => 'bb-drm-addon-' . $this->product_slug,
				'segment' => '',
				'saved'   => time(),
				'end'     => '',
				'icon'    => $icon_url,
				'buttons' => array(
					'main' => array(
						'text'   => __( 'Activate License', 'buddyboss' ),
						'url'    => $drm_info['activation_link'],
						'target' => '_self',
					),
				),
			)
		);
	}

	/**
	 * Sends an email notification for this add-on.
	 *
	 * @param string $drm_status The DRM status.
	 */
	protected function send_email( $drm_status ) {
		$drm_info = $this->get_addon_drm_info( $drm_status, 'email' );
		if ( empty( $drm_info['heading'] ) ) {
			return;
		}

		$subject = $drm_info['heading'];
		$message = $this->get_email_message( $drm_info );

		$headers = array(
			sprintf( 'Content-type: text/html; charset=%s', get_bloginfo( 'charset' ) ),
		);

		$admin_email = get_option( 'admin_email' );
		wp_mail( $admin_email, $subject, $message, $headers );
	}

	/**
	 * Displays admin notices related to DRM for this add-on.
	 *
	 * Overrides parent method to use addon-specific DRM info.
	 */
	public function admin_notices() {
		if ( ! $this->event instanceof BB_DRM_Event ) {
			return;
		}

		$drm_status = BB_DRM_Helper::get_status();

		if ( '' !== $drm_status ) {
			// Use addon-specific DRM info instead of Platform info.
			$drm_info                = $this->get_addon_drm_info( $drm_status, 'admin_notices' );
			$drm_info['notice_key']  = BB_DRM_Helper::get_status_key( $drm_status );
			$drm_info['event_name']  = $this->event_name;

			// Add support link if not provided.
			if ( ! isset( $drm_info['support_link'] ) ) {
				$drm_info['support_link'] = $drm_info['activation_link'];
			}

			// Add help message if not provided.
			if ( ! isset( $drm_info['help_message'] ) ) {
				$drm_info['help_message'] = __( 'Activate License', 'buddyboss' );
			}

			$notice_user_key = BB_DRM_Helper::prepare_dismissable_notice_key( $drm_info['notice_key'] );

			// Get event args.
			$args       = $this->event->get_args();
			$event_data = is_object( $args ) ? (array) $args : ( is_array( $args ) ? $args : array() );

			$is_dismissed = BB_DRM_Helper::is_dismissed( $event_data, $notice_user_key );
			if ( ! $is_dismissed ) {
				$this->render_admin_notice( $drm_info );
			}
		}
	}

	/**
	 * Check if this add-on's features should be locked.
	 *
	 * @return bool True if features should be locked.
	 */
	public function should_lock_features() {
		// First check if license is valid.
		if ( $this->is_addon_licensed() ) {
			return false;
		}

		// Check if there's a DRM event for this addon.
		$event = $this->get_latest_event();
		if ( ! $event ) {
			return false;
		}

		// Calculate days elapsed.
		$days = BB_DRM_Helper::days_elapsed( $event->created_at );

		// Lock if grace period has expired (21+ days for addons).
		return $days >= 21;
	}
}
