<?php
/**
 * BuddyBoss DRM Registry
 *
 * Central registry for managing DRM across multiple add-on plugins.
 * Add-ons register themselves here to participate in the DRM system.
 *
 * @package BuddyBoss\Core\Admin\DRM
 * @since BuddyBoss 2.16.0
 */

namespace BuddyBoss\Core\Admin\DRM;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * DRM Registry class for managing multiple add-on DRM instances.
 */
class BB_DRM_Registry {

	/**
	 * Registered add-ons.
	 *
	 * @var array
	 */
	private static $addons = array();

	/**
	 * The single instance of the class.
	 *
	 * @var BB_DRM_Registry
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @since BuddyBoss 2.16.0
	 *
	 * @return BB_DRM_Registry
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since BuddyBoss 2.16.0
	 */
	private function __construct() {
		// Private constructor to enforce singleton.
		// Hook consolidated admin notices.
		add_action( 'admin_notices', array( $this, 'render_consolidated_admin_notices' ), 5 );

		// Hook consolidated Site Health test.
		add_filter( 'site_status_tests', array( $this, 'add_addon_site_health_tests' ) );
	}

	/**
	 * Register an add-on for DRM management.
	 *
	 * Add-on plugins should call this during their initialisation.
	 *
	 * Example usage from Platform Pro:
	 * ```php
	 * BB_DRM_Registry::register_addon(
	 *     'buddyboss-platform-pro',
	 *     'BuddyBoss Platform Pro',
	 *     array(
	 *         'version' => '2.10.0',
	 *         'file' => BB_PLATFORM_PRO_PLUGIN_FILE
	 *     )
	 * );
	 * ```
	 *
	 * @since BuddyBoss 2.16.0
	 *
	 * @param string $product_slug The Mothership product slug.
	 * @param string $plugin_name  The plugin display name.
	 * @param array  $args         Additional arguments (version, file, etc.).
	 * @return BB_DRM_Addon|false The DRM addon instance or false on error.
	 */
	public static function register_addon( $product_slug, $plugin_name, $args = array() ) {
		// Validate inputs.
		if ( empty( $product_slug ) || empty( $plugin_name ) ) {
			return false;
		}

		// Check if already registered.
		if ( isset( self::$addons[ $product_slug ] ) ) {
			return self::$addons[ $product_slug ]['drm'];
		}

		// Create DRM instance for this add-on.
		$drm_addon = new BB_DRM_Addon( $product_slug, $plugin_name );

		// Store in registry.
		self::$addons[ $product_slug ] = array(
			'drm'         => $drm_addon,
			'plugin_name' => $plugin_name,
			'args'        => $args,
			'registered'  => time(),
		);

		// Hook into admin_init to run DRM checks.
		add_action(
			'admin_init',
			function () use ( $drm_addon, $product_slug ) {
				self::run_addon_drm_check( $product_slug );
			},
			25
		);

		return $drm_addon;
	}

	/**
	 * Run DRM check for a specific add-on.
	 *
	 * @since BuddyBoss 2.16.0
	 *
	 * @param string $product_slug The product slug.
	 */
	private static function run_addon_drm_check( $product_slug ) {
		if ( ! isset( self::$addons[ $product_slug ] ) ) {
			return;
		}

		$addon = self::$addons[ $product_slug ];
		$drm   = $addon['drm'];

		// Check if development environment - clean up any DRM state and skip checks.
		if ( BB_DRM_Helper::is_dev_environment() ) {
			self::cleanup_addon_drm( $product_slug );
			return;
		}

		// Check if license is valid.
		if ( $drm->is_addon_licensed() ) {
			// License valid - clean up any DRM state.
			self::cleanup_addon_drm( $product_slug );
			return;
		}

		// Create or update event (duplicate prevention handled internally).
		$event = $drm->get_event();
		if ( ! $event ) {
			$drm->create_event();
		}

		// Run DRM checks.
		$drm->run();

		// After all checks, update consolidated in-plugin notification.
		add_action( 'admin_init', array( self::instance(), 'update_consolidated_notification' ), 30 );

		// Send consolidated email if needed.
		add_action( 'admin_init', array( self::instance(), 'send_consolidated_email' ), 31 );
	}

	/**
	 * Cleanup DRM state for an add-on when the license becomes valid.
	 *
	 * @since BuddyBoss 2.16.0
	 *
	 * @param string $product_slug The product slug.
	 */
	private static function cleanup_addon_drm( $product_slug ) {
		// Delete option-based event data (legacy).
		$event_key = 'bb_drm_event_addon-' . sanitize_key( $product_slug );
		delete_option( $event_key );

		// Delete database event.
		$event_name = 'addon-' . $product_slug;
		$event      = BB_DRM_Event::latest( $event_name );
		if ( $event ) {
			$event->destroy();
		}

		// Dismiss addon-specific notifications.
		$notifications = new BB_Notifications();
		$notifications->dismiss_events( 'bb-drm-addon-' . $product_slug );

		// Check if there are any remaining add-ons with DRM issues.
		// If not, clear consolidated notifications as well.
		$grouped    = self::get_addons_by_drm_status();
		$has_issues = ! empty( $grouped['low'] ) || ! empty( $grouped['medium'] ) || ! empty( $grouped['high'] ) || ! empty( $grouped['locked'] );

		if ( ! $has_issues ) {
			// No more add-ons with DRM issues - clear all consolidated notifications.
			$notifications->dismiss_events( 'bb-drm-consolidated' );
		}
	}

	/**
	 * Get all registered add-ons.
	 *
	 * @since BuddyBoss 2.16.0
	 *
	 * @return array Array of registered add-ons.
	 */
	public static function get_registered_addons() {
		return self::$addons;
	}

	/**
	 * Check if a specific add-on is registered.
	 *
	 * @since BuddyBoss 2.16.0
	 *
	 * @param string $product_slug The product slug.
	 * @return bool True if registered.
	 */
	public static function is_addon_registered( $product_slug ) {
		return isset( self::$addons[ $product_slug ] );
	}

	/**
	 * Get DRM instance for a specific add-on.
	 *
	 * @since BuddyBoss 2.16.0
	 *
	 * @param string $product_slug The product slug.
	 * @return BB_DRM_Addon|null The DRM instance or null.
	 */
	public static function get_addon_drm( $product_slug ) {
		if ( ! isset( self::$addons[ $product_slug ] ) ) {
			return null;
		}
		return self::$addons[ $product_slug ]['drm'];
	}

	/**
	 * Check if any features should be locked for a specific add-on.
	 *
	 * Add-ons should call this before enabling their features.
	 *
	 * Example usage:
	 * ```php
	 * if ( BB_DRM_Registry::should_lock_addon_features( 'buddyboss-platform-pro' ) ) {
	 *     // Don't load features
	 *     return;
	 * }
	 * ```
	 *
	 * @since BuddyBoss 2.16.0
	 *
	 * @param string $product_slug The product slug.
	 * @return bool True if features should be locked.
	 */
	public static function should_lock_addon_features( $product_slug ) {
		$drm = self::get_addon_drm( $product_slug );
		if ( ! $drm ) {
			// Not registered - allow by default (or you could lock by default).
			return false;
		}

		return $drm->should_lock_features();
	}

	/**
	 * Get lockout message for display.
	 *
	 * @since BuddyBoss 2.16.0
	 *
	 * @param string $product_slug The product slug.
	 * @return string The lockout message.
	 */
	public static function get_lockout_message( $product_slug ) {
		if ( ! isset( self::$addons[ $product_slug ] ) ) {
			return '';
		}

		$plugin_name = self::$addons[ $product_slug ]['plugin_name'];

		return sprintf(
			/* translators: %1$s: plugin name, %2$s: license page URL */
			__( '%1$s features are currently disabled. Please <a href="%2$s">activate your license</a> to restore functionality.', 'buddyboss' ),
			$plugin_name,
			bp_get_admin_url( 'admin.php?page=buddyboss-license' )
		);
	}

	/**
	 * Helper function to display a lockout notice.
	 * Add-ons can use this to show a consistent lockout message.
	 *
	 * @since BuddyBoss 2.16.0
	 *
	 * @param string $product_slug The product slug.
	 * @param string $context      The context (admin_notice, inline, modal).
	 */
	public static function display_lockout_notice( $product_slug, $context = 'admin_notice' ) {
		$message = self::get_lockout_message( $product_slug );

		if ( empty( $message ) ) {
			return;
		}

		switch ( $context ) {
			case 'admin_notice':
				?>
				<div class="notice notice-error bb-drm-lockout-notice">
					<p><strong><?php echo wp_kses_post( $message ); ?></strong></p>
				</div>
				<?php
				break;

			case 'inline':
				?>
				<div class="bb-drm-lockout-inline">
					<p><?php echo wp_kses_post( $message ); ?></p>
				</div>
				<?php
				break;

			case 'modal':
				?>
				<div class="bb-drm-lockout-modal" style="padding: 20px; background: #f9f9f9; border: 2px solid #dc3232; border-radius: 4px; margin: 20px 0;">
					<h3 style="color: #dc3232; margin-top: 0;"><?php esc_html_e( 'License Required', 'buddyboss' ); ?></h3>
					<p><?php echo wp_kses_post( $message ); ?></p>
				</div>
				<?php
				break;
		}
	}

	/**
	 * Clear the addons status cache.
	 * Call this after updating event data to ensure fresh data on next call.
	 *
	 * @since BuddyBoss 2.16.0
	 */
	private static function clear_addons_status_cache() {
		// Use a static variable reference to clear the cache in get_addons_by_drm_status().
		static $cache_cleared = false;
		$cache_cleared        = true;
	}

	/**
	 * Get all addons grouped by DRM status.
	 *
	 * @since BuddyBoss 2.16.0
	 *
	 * @param bool $force_refresh Force refresh from database, ignoring cache.
	 * @return array Array of addons grouped by status: ['low' => [], 'medium' => [], 'locked' => []].
	 */
	public static function get_addons_by_drm_status( $force_refresh = false ) {
		// Cache result per request to avoid repeated calculations.
		static $cache = null;

		if ( null !== $cache && ! $force_refresh ) {
			return $cache;
		}

		$grouped = array(
			'low'    => array(),
			'medium' => array(),
			'high'   => array(),
			'locked' => array(),
		);

		foreach ( self::$addons as $product_slug => $addon_data ) {
			$drm   = $addon_data['drm'];
			$event = $drm->get_event();

			if ( ! $event || $drm->is_addon_licensed() ) {
				continue; // Skip if no event or license is valid.
			}

			$days   = BB_DRM_Helper::days_elapsed( $event->created_at );
			$status = null;

			// Determine status based on days elapsed.
			// Match timeline from BuddyBoss DRM Messaging.md
			if ( $days >= 7 && $days <= 13 ) {
				$status = 'low';
			} elseif ( $days >= 14 && $days <= 21 ) {
				$status = 'medium';
			} elseif ( $days >= 22 && $days <= 30 ) {
				$status = 'high';
			} elseif ( $days >= 31 ) {
				$status = 'locked';
			}

			if ( $status ) {
				$grouped[ $status ][] = array(
					'product_slug' => $product_slug,
					'plugin_name'  => $addon_data['plugin_name'],
					'drm'          => $drm,
					'event'        => $event,
					'days'         => $days,
				);
			}
		}

		$cache = $grouped;
		return $grouped;
	}

	/**
	 * Render consolidated admin notices for all addons.
	 *
	 * @since BuddyBoss 2.16.0
	 *
	 * This replaces individual addon notices with a single grouped notice.
	 */
	public function render_consolidated_admin_notices() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Early return if development environment detected.
		if ( BB_DRM_Helper::is_dev_environment() ) {
			return;
		}

		// Early return if no addons are registered.
		if ( empty( self::$addons ) ) {
			return;
		}

		$grouped = self::get_addons_by_drm_status();

		// Render LOCKED notice (highest priority).
		if ( ! empty( $grouped['locked'] ) ) {
			$this->render_grouped_notice( $grouped['locked'], BB_DRM_Helper::DRM_LOCKED );
		}

		// Render HIGH notice.
		if ( ! empty( $grouped['high'] ) ) {
			$this->render_grouped_notice( $grouped['high'], BB_DRM_Helper::DRM_HIGH );
		}

		// Render MEDIUM notice.
		if ( ! empty( $grouped['medium'] ) ) {
			$this->render_grouped_notice( $grouped['medium'], BB_DRM_Helper::DRM_MEDIUM );
		}

		// Render LOW notice (no admin notice for LOW - only plugin notification).
		if ( ! empty( $grouped['low'] ) ) {
			$this->render_grouped_notice( $grouped['low'], BB_DRM_Helper::DRM_LOW );
		}
	}

	/**
	 * Render a single grouped notice for addons of the same status.
	 *
	 * @since BuddyBoss 2.16.0
	 *
	 * @param array  $addons     Array of addon data.
	 * @param string $drm_status The DRM status (low, medium, locked).
	 */
	private function render_grouped_notice( $addons, $drm_status ) {
		if ( empty( $addons ) ) {
			return;
		}

		// Check if this notice type has been dismissed.
		$notice_key = BB_DRM_Helper::get_status_key( $drm_status );
		$is_warning = ( BB_DRM_Helper::DRM_LOW === $drm_status || BB_DRM_Helper::DRM_MEDIUM === $drm_status );

		// For dismissal, we'll use the first addon's event data.
		$first_addon     = $addons[0];
		$event           = $first_addon['event'];
		$notice_user_key = BB_DRM_Helper::prepare_dismissable_notice_key( $notice_key );

		// Get event args for dismissal check.
		$args       = $event->get_args();
		$event_data = is_object( $args ) ? (array) $args : ( is_array( $args ) ? $args : array() );

		// Check if dismissed.
		if ( $is_warning && BB_DRM_Helper::is_dismissed( $event_data, $notice_user_key ) ) {
			return;
		}

		// Prepare notice data.
		$count       = count( $addons );
		$addon_names = array_map(
			function ( $addon ) {
				return esc_html( $addon['plugin_name'] );
			},
			$addons
		);

		$activation_link = bp_get_admin_url( 'admin.php?page=buddyboss-license' );

		// Build heading and message based on status and count.
		// Headings match BuddyBoss DRM Messaging.pdf specification.
		switch ( $drm_status ) {
			case BB_DRM_Helper::DRM_LOW:
				// 7-13 days: No admin notice, only plugin notification
				// Skip rendering admin notice for LOW status
				return;

			case BB_DRM_Helper::DRM_MEDIUM:
				// 14-21 days: Yellow notice
				$heading      = __( 'License required for BuddyBoss features.', 'buddyboss' );
				$message      = '';
				$notice_class = 'notice notice-warning is-dismissible';
				$color        = 'FFA500'; // Yellow/Orange
				break;

			case BB_DRM_Helper::DRM_HIGH:
				// 21-30 days: Orange notice
				$heading      = __( 'BuddyBoss features will be disabled soon.', 'buddyboss' );
				$message      = '';
				$notice_class = 'notice notice-warning is-dismissible';
				$color        = 'FF8C00'; // Dark Orange
				break;

			case BB_DRM_Helper::DRM_LOCKED:
				// 30+ days: Red notice
				$heading      = __( 'BuddyBoss features have been disabled.', 'buddyboss' );
				$message      = '';
				$notice_class = 'notice notice-error';
				$color        = 'dc3232'; // Red
				break;

			default:
				return;
		}

		// Build add-on list to display below the heading
		$addon_list_html = '';
		if ( $count > 0 ) {
			$addon_list_html = '<ul>';
			foreach ( $addon_names as $name ) {
				$addon_list_html .= '<li>' . esc_html( $name ) . '</li>';
			}
			$addon_list_html .= '</ul>';
		}

		// Generate unique notice ID and secret for dismissal.
		$notice_id = 'bb-drm-consolidated-notice-' . sanitize_key( $notice_key );
		$secret    = $is_warning ? hash( 'sha256', $notice_key ) . '-' . hash( 'sha256', $event->event ) : '';

		?>
		<div id="<?php echo esc_attr( $notice_id ); ?>"
			class="<?php echo esc_attr( $notice_class ); ?> bb-drm-notice"
			style="padding: 15px; padding-right: 38px; border-left-width: 4px; position: relative;"
			<?php if ( $is_warning ) : ?>
			data-notice-key="<?php echo esc_attr( $notice_key ); ?>"
			data-secret="<?php echo esc_attr( $secret ); ?>"
			<?php endif; ?>>
			<?php if ( $is_warning ) : ?>
			<button type="button" class="notice-dismiss bb-drm-dismiss" style="position: absolute; top: 0; right: 1px; padding: 9px; border: none; background: none; color: #787c82; cursor: pointer;" aria-label="<?php esc_attr_e( 'Dismiss this notice for 24 hours', 'buddyboss' ); ?>">
				<span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice for 24 hours.', 'buddyboss' ); ?></span>
			</button>
			<?php endif; ?>
			<div style="display: flex; align-items: flex-start; justify-content: space-between;">
				<div style="flex: 1; padding-right: 15px;">
					<p style="margin: 0 0 5px 0;"><strong><?php echo esc_html( $heading ); ?></strong></p>
					<?php if ( ! empty( $addon_list_html ) ) : ?>
						<?php echo wp_kses_post( $addon_list_html ); ?>
					<?php endif; ?>
				</div>
				<div style="white-space: nowrap;">
					<a href="<?php echo esc_url( $activation_link ); ?>" class="button button-primary">
						<?php esc_html_e( 'Activate Your License', 'buddyboss' ); ?>
					</a>
				</div>
			</div>
		</div>
		<?php

		// Enqueue dismiss script if warning.
		if ( $is_warning ) {
			$this->enqueue_dismiss_script();
		}
	}

	/**
	 * Enqueue dismiss script for consolidated notices.
	 *
	 * @since BuddyBoss 2.16.0
	 */
	private function enqueue_dismiss_script() {
		static $enqueued = false;
		if ( $enqueued ) {
			return;
		}
		$enqueued = true;
		?>
		<script type="text/javascript">
		jQuery(document).ready(function($) {
			$('.bb-drm-notice').on('click', '.bb-drm-dismiss, .notice-dismiss', function(e) {
				e.preventDefault();
				var $notice = $(this).closest('.bb-drm-notice');
				var noticeKey = $notice.data('notice-key');
				var secret = $notice.data('secret');

				if (!noticeKey || !secret) {
					$notice.fadeOut();
					return;
				}

				$notice.fadeOut();

				$.post(ajaxurl, {
					action: 'bb_dismiss_notice_drm',
					notice: noticeKey,
					secret: secret,
					nonce: '<?php echo wp_create_nonce( 'bb_dismiss_notice' ); ?>'
				}, function(response) {
					if (response.success) {
						console.log('DRM notice dismissed for 24 hours');
					}
				});
			});
		});
		</script>
		<style type="text/css">
		.bb-drm-notice .notice-dismiss {
			position: absolute;
			top: 0;
			right: 1px;
			padding: 9px;
			border: none;
			background: none;
			color: #787c82;
			cursor: pointer;
		}
		.bb-drm-notice .notice-dismiss:before {
			content: '\f153';
			font: normal 16px/20px dashicons;
			speak: never;
			height: 20px;
			width: 20px;
			text-align: center;
		}
		.bb-drm-notice .notice-dismiss:hover,
		.bb-drm-notice .notice-dismiss:active {
			color: #d63638;
		}
		.bb-drm-notice .notice-dismiss:focus {
			outline: 1px solid #4f94d4;
			box-shadow: 0 0 0 1px #4f94d4;
		}
		</style>
		<?php
	}

	/**
	 * Update consolidated in-plugin notification.
	 *
	 * Creates a single notification listing all addons with license issues
	 * instead of separate notifications for each addon.
	 *
	 * @since BuddyBoss 2.16.0
	 */
	public function update_consolidated_notification() {
		static $updated = false;
		if ( $updated ) {
			return;
		}
		$updated = true;

		// Skip on development environments.
		if ( BB_DRM_Helper::is_dev_environment() ) {
			return;
		}

		$grouped = self::get_addons_by_drm_status();

		// Initialize notifications system.
		$notifications = new BB_Notifications();

		// Create separate notifications for each priority level.
		// This ensures all addon statuses are visible, not just the highest priority.
		$priority_levels = array(
			'locked' => BB_DRM_Helper::DRM_LOCKED,
			'high'   => BB_DRM_Helper::DRM_HIGH,
			'medium' => BB_DRM_Helper::DRM_MEDIUM,
			'low'    => BB_DRM_Helper::DRM_LOW,
		);

		$has_notifications = false;

		foreach ( $priority_levels as $key => $drm_status ) {
			if ( empty( $grouped[ $key ] ) ) {
				// Dismiss notification for this priority if no addons affected.
				$notifications->dismiss_events( 'bb-drm-consolidated-' . $key );
				continue;
			}

			$has_notifications = true;
			$affected_addons   = $grouped[ $key ];
			$count             = count( $affected_addons );
			$addon_names       = wp_list_pluck( $affected_addons, 'plugin_name' );

			// Determine icon based on severity.
			if ( BB_DRM_Helper::DRM_LOCKED === $drm_status || BB_DRM_Helper::DRM_HIGH === $drm_status ) {
				$icon_url = buddypress()->plugin_url . 'bp-core/images/dh-icon.png'; // Red.
			} else {
				$icon_url = buddypress()->plugin_url . 'bp-core/images/dl-icon.png'; // Yellow.
			}

			// Build title and content based on DRM status.
			// Titles and messages match BuddyBoss DRM Messaging.pdf specification.
			if ( BB_DRM_Helper::DRM_LOW === $drm_status ) {
				// 7-13 days
				$title   = __( 'License Activation Needed', 'buddyboss' );
				$content = '<p>' . __( 'We couldn\'t verify an active license for your BuddyBoss features. Please activate your license to continue using them.', 'buddyboss' ) . '</p>';
			} elseif ( BB_DRM_Helper::DRM_MEDIUM === $drm_status ) {
				// 14-21 days
				$title   = __( 'License Required', 'buddyboss' );
				$content = '<p>' . __( 'An active license is required to use BuddyBoss features. Without activation, these features will stop working.', 'buddyboss' ) . '</p>';
			} elseif ( BB_DRM_Helper::DRM_HIGH === $drm_status ) {
				// 21-30 days
				$title   = __( 'License Activation Required', 'buddyboss' );
				$content = '<p>' . __( 'Your BuddyBoss features will be disabled soon. Activate your license now to avoid interruption.', 'buddyboss' ) . '</p>';
			} elseif ( BB_DRM_Helper::DRM_LOCKED === $drm_status ) {
				// 30+ days
				$title   = __( 'BuddyBoss Features Disabled', 'buddyboss' );
				$content = '<p>' . __( 'The following features have been disabled because no active license was found. Activate your license to restore them.', 'buddyboss' ) . '</p>';
			} else {
				$title   = '';
				$content = '';
			}

			// Add feature list for all statuses
			if ( $count > 0 ) {
				// Build HTML list for notification.
				// Feature list heading varies by status per BuddyBoss DRM Messaging.pdf.
				$addon_list = '<p><strong>';
				if ( BB_DRM_Helper::DRM_LOCKED === $drm_status ) {
					$addon_list .= __( 'The following features have been disabled:', 'buddyboss' );
				} elseif ( BB_DRM_Helper::DRM_HIGH === $drm_status ) {
					$addon_list .= __( 'The following features will be disabled without an active license:', 'buddyboss' );
				} else {
					$addon_list .= __( 'The following features require an active license:', 'buddyboss' );
				}
				$addon_list .= '</strong></p><ul>';
				foreach ( $addon_names as $name ) {
					$addon_list .= '<li>' . esc_html( $name ) . '</li>';
				}
				$addon_list .= '</ul>';

				$content .= $addon_list;
			}

			// Add deactivation option for LOCKED status (comes after feature list and button).
			// Per PDF spec, this appears after [Activate Your License] button.
			if ( BB_DRM_Helper::DRM_LOCKED === $drm_status ) {
				$content .= '<p>' . __( 'If you no longer need these features, you can deactivate the premium add-ons in your Plugins page to continue using BuddyBoss Platform for free.', 'buddyboss' ) . '</p>';
			}

			// Add footer links to content
			$account_link = 'https://www.buddyboss.com/my-account/';
			$support_link = 'https://www.buddyboss.com/support/';

			$content .= '<p style="margin-top: 15px; font-size: 13px;">';
			$content .= sprintf(
				/* translators: %1$s: opening link tag, %2$s: closing link tag */
				__( 'Need your license key? Visit %1$sYour Account Page%2$s. Having trouble? %3$sContact Support%4$s.', 'buddyboss' ),
				'<a href="' . esc_url( $account_link ) . '" target="_blank">',
				'</a>',
				'<a href="' . esc_url( $support_link ) . '" target="_blank">',
				'</a>'
			);
			$content .= '</p>';

			// Create consolidated notification for this priority level.
			// Use priority-specific ID to allow multiple notifications.
			$notifications->add(
				array(
					'id'      => 'bb_drm_consolidated_license_notice_' . $key,
					'title'   => $title,
					'content' => $content,
					'type'    => 'bb-drm-consolidated',
					'segment' => '',
					'saved'   => time(),
					'end'     => '',
					'icon'    => $icon_url,
					'buttons' => array(
						'main' => array(
							'text'   => __( 'Activate Your License', 'buddyboss' ),
							'url'    => bp_get_admin_url( 'admin.php?page=buddyboss-license' ),
							'target' => '_self',
						),
					),
				)
			);
		}

		// If no addons have issues at all, dismiss all consolidated notifications.
		if ( ! $has_notifications ) {
			$notifications->dismiss_events( 'bb-drm-consolidated' );
		}
	}

	/**
	 * Send a consolidated email for all addons with license issues.
	 *
	 * Sends a single email listing all affected addons instead of separate emails.
	 *
	 * @since BuddyBoss 2.16.0
	 */
	public function send_consolidated_email() {
		static $sent = false;
		if ( $sent ) {
			return;
		}
		$sent = true;

		// Skip on development environments.
		if ( BB_DRM_Helper::is_dev_environment() ) {
			return;
		}

		$grouped = self::get_addons_by_drm_status();

		// Determine the highest priority status that needs email.
		$highest_priority  = null;
		$affected_addons   = array();
		$status_key        = '';
		$should_send_email = false;

		if ( ! empty( $grouped['locked'] ) ) {
			$highest_priority  = BB_DRM_Helper::DRM_LOCKED;
			$affected_addons   = $grouped['locked'];
			$status_key        = 'drm_locked';
			$should_send_email = $this->should_send_status_email( $grouped['locked'], $status_key );
		} elseif ( ! empty( $grouped['high'] ) ) {
			$highest_priority  = BB_DRM_Helper::DRM_HIGH;
			$affected_addons   = $grouped['high'];
			$status_key        = 'drm_high';
			$should_send_email = $this->should_send_status_email( $grouped['high'], $status_key );
		} elseif ( ! empty( $grouped['medium'] ) ) {
			$highest_priority  = BB_DRM_Helper::DRM_MEDIUM;
			$affected_addons   = $grouped['medium'];
			$status_key        = 'drm_medium';
			$should_send_email = $this->should_send_status_email( $grouped['medium'], $status_key );
		} elseif ( ! empty( $grouped['low'] ) ) {
			$highest_priority  = BB_DRM_Helper::DRM_LOW;
			$affected_addons   = $grouped['low'];
			$status_key        = 'drm_low';
			$should_send_email = $this->should_send_status_email( $grouped['low'], $status_key );
		}

		// If no addons need emails, return.
		if ( empty( $affected_addons ) || ! $should_send_email ) {
			return;
		}

		// Build email content.
		$count       = count( $affected_addons );
		$addon_names = wp_list_pluck( $affected_addons, 'plugin_name' );

		// Build subject and message based on status.
		// Note: LOW status (7-13 days) does not send email.
		switch ( $highest_priority ) {
			case BB_DRM_Helper::DRM_LOW:
				// 7-13 days: No email sent
				return;

			case BB_DRM_Helper::DRM_MEDIUM:
				// 14-21 days: No email according to spec
				return;

			case BB_DRM_Helper::DRM_HIGH:
				// 21-30 days: Send email
				$subject = __( 'Your BuddyBoss license needs activation', 'buddyboss' );
				$heading = $subject;
				$message = sprintf(
					/* translators: %s: site name/URL */
					__( 'Your site %s is using BuddyBoss features that require an active license.', 'buddyboss' ),
					get_bloginfo( 'name' ) . ' (' . site_url() . ')'
				);
				$color = 'FF8C00'; // Dark Orange.
				break;

			case BB_DRM_Helper::DRM_LOCKED:
				// 30+ days: Send email
				$subject = __( 'Your BuddyBoss features have now been disabled on your site', 'buddyboss' );
				$heading = $subject;
				$message = sprintf(
					/* translators: %s: site name/URL */
					__( 'The BuddyBoss features on your site %s have been disabled because no active license was found.', 'buddyboss' ),
					get_bloginfo( 'name' ) . ' (' . site_url() . ')'
				);
				$color = 'dc3232'; // Red.
				break;

			default:
				return;
		}

		// Build HTML list for multiple addons.
		$addon_list_html = '';
		if ( $count > 0 ) {
			$addon_list_html = '<ul style="margin: 10px 0; padding-left: 20px;">';
			foreach ( $addon_names as $name ) {
				$addon_list_html .= '<li><strong>' . esc_html( $name ) . '</strong></li>';
			}
			$addon_list_html .= '</ul>';
		}

		// Build email HTML.
		$activation_link = bp_get_admin_url( 'admin.php?page=buddyboss-license' );
		$email_html      = $this->get_consolidated_email_html(
			array(
				'heading'         => $heading,
				'message'         => $message,
				'addon_list_html' => $addon_list_html,
				'color'           => $color,
				'activation_link' => $activation_link,
				'status'          => $highest_priority,
			)
		);

		// Send consolidated email.
		$headers     = array(
			sprintf( 'Content-type: text/html; charset=%s', get_bloginfo( 'charset' ) ),
		);
		$admin_email = get_option( 'admin_email' );
		wp_mail( $admin_email, $subject, $email_html, $headers );

		// Mark email as sent for all affected addons.
		foreach ( $affected_addons as $addon ) {
			$event = $addon['event'];

			// Ensure we have a valid event ID.
			if ( empty( $event->id ) || $event->id <= 0 ) {
				error_log( sprintf( 'BB DRM: Cannot mark %s email as sent for addon %s - invalid event ID', $status_key, $addon['product_slug'] ) );
				continue;
			}

			$args = $event->get_args();
			$data = is_object( $args ) ? (array) $args : ( is_array( $args ) ? $args : array() );

			// Store sent timestamp.
			$data[ $status_key . '_sent' ] = current_time( 'mysql' );

			// Update the event in the database directly using wpdb.
			global $wpdb;
			$table_name = BB_DRM_Event::get_table_name();
			$result     = $wpdb->update(
				$table_name,
				array( 'args' => wp_json_encode( $data ) ),
				array( 'id' => $event->id ),
				array( '%s' ),
				array( '%d' )
			);

			// Log if update failed.
			if ( false === $result ) {
				error_log( sprintf( 'BB DRM: Failed to mark %s email as sent for addon: %s (event ID: %d, wpdb error: %s)', $status_key, $addon['product_slug'], $event->id, $wpdb->last_error ) );
			} else {
				error_log( sprintf( 'BB DRM: Marked %s email as sent for addon: %s (event ID: %d)', $status_key, $addon['product_slug'], $event->id ) );
			}
		}
	}

	/**
	 * Check if the email should be sent for this status.
	 *
	 * @since BuddyBoss 2.16.0
	 *
	 * @param array  $addons     Array of addon data.
	 * @param string $status_key Status key (drm_low, drm_medium, drm_locked).
	 * @return bool True if email should be sent.
	 */
	private function should_send_status_email( $addons, $status_key ) {
		// Check if any addon hasn't received email for this status yet.
		foreach ( $addons as $addon ) {
			$event = $addon['event'];
			$args  = $event->get_args();
			$data  = is_object( $args ) ? (array) $args : ( is_array( $args ) ? $args : array() );

			$sent_key = $status_key . '_sent';

			// If this status email hasn't been sent, we should send it.
			if ( ! isset( $data[ $sent_key ] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get consolidated email HTML template.
	 *
	 * Follows the exact messaging from BuddyBoss DRM Messaging specification.
	 *
	 * @since BuddyBoss 2.16.0
	 *
	 * @param array $data Email data (heading, message, addon_list_html, color, activation_link, status).
	 * @return string Email HTML.
	 */
	private function get_consolidated_email_html( $data ) {
		$account_link = 'https://www.buddyboss.com/my-account/';
		$support_link = 'https://www.buddyboss.com/support/';
		$status       = isset( $data['status'] ) ? $data['status'] : '';

		ob_start();
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<meta charset="utf-8">
			<title><?php echo esc_html( $data['heading'] ); ?></title>
		</head>
		<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
			<div style="max-width: 600px; margin: 0 auto; padding: 20px;">
				<p><?php esc_html_e( 'Hi,', 'buddyboss' ); ?></p>

				<p><?php echo wp_kses_post( $data['message'] ); ?></p>

				<?php if ( ! empty( $data['addon_list_html'] ) ) : ?>
					<p><strong>
					<?php
					if ( BB_DRM_Helper::DRM_LOCKED === $status ) {
						esc_html_e( 'The following features have been disabled:', 'buddyboss' );
					} else {
						esc_html_e( 'The following features will be disabled without an active license:', 'buddyboss' );
					}
					?>
					</strong></p>
					<?php echo wp_kses_post( $data['addon_list_html'] ); ?>
				<?php endif; ?>

				<?php if ( BB_DRM_Helper::DRM_LOCKED === $status ) : ?>
					<p><?php esc_html_e( 'To restore these features, activate your license in your WordPress admin.', 'buddyboss' ); ?></p>
					<p><?php esc_html_e( 'If you no longer need these features, you can deactivate the premium add-ons in your Plugins page to continue using BuddyBoss Platform for free.', 'buddyboss' ); ?></p>
				<?php else : ?>
					<p><?php esc_html_e( 'To keep these features working, activate your license in your WordPress admin.', 'buddyboss' ); ?></p>
				<?php endif; ?>

				<p>
					<?php
					printf(
						/* translators: %1$s: opening link tag, %2$s: closing link tag */
						esc_html__( 'Need your license key? Log in to your BuddyBoss account: %1$sYour Account Page%2$s', 'buddyboss' ),
						'<a href="' . esc_url( $account_link ) . '" style="color: #0073aa;">',
						'</a>'
					);
					?>
				</p>

				<p>
					<?php
					printf(
						/* translators: %1$s: opening link tag, %2$s: closing link tag */
						esc_html__( 'Having trouble? Our support team is here to help: %1$sContact Support%2$s', 'buddyboss' ),
						'<a href="' . esc_url( $support_link ) . '" style="color: #0073aa;">',
						'</a>'
					);
					?>
				</p>

				<p style="margin-top: 30px;"><?php esc_html_e( 'Thanks,', 'buddyboss' ); ?><br><?php esc_html_e( 'BuddyBoss', 'buddyboss' ); ?></p>
			</div>
		</body>
		</html>
		<?php
		return ob_get_clean();
	}

	/**
	 * Add Site Health tests for add-ons.
	 *
	 * Adds consolidated Site Health tests grouped by priority level.
	 * Shows all affected addons within each priority level (LOCKED, HIGH, MEDIUM, LOW).
	 *
	 * @since BuddyBoss 2.16.0
	 *
	 * @param array $tests Site Health tests array.
	 * @return array Modified tests array.
	 */
	public function add_addon_site_health_tests( $tests ) {
		// Early return if development environment detected.
		if ( BB_DRM_Helper::is_dev_environment() ) {
			return $tests;
		}

		$grouped = self::get_addons_by_drm_status();

		// Add test for each priority level that has affected addons.
		// NOTE: LOW status (7-13 days) does NOT show Site Health test.
		// Site Health only shows for MEDIUM (14-21 days), HIGH (22-30 days), and LOCKED (31+ days).
		// Timeline per BuddyBoss DRM Messaging.md:
		// - 0-7 days: Grace period (no messaging)
		// - 7-13 days: Plugin Notification only (no Site Health)
		// - 14-21 days: Plugin Notification + Admin Notice + Site Health
		// - 22-30 days: Plugin Notification + Admin Notice + Site Health + Email
		// - 31+ days: Plugin Notification + Admin Notice + Site Health + Email + Features Disabled.

		if ( ! empty( $grouped['locked'] ) ) {
			$tests['direct']['buddyboss_addons_locked'] = array(
				'label' => __( 'BuddyBoss Pro/Plus License - Features Disabled', 'buddyboss' ),
				'test'  => array( $this, 'site_health_locked_test' ),
			);
		}

		if ( ! empty( $grouped['high'] ) ) {
			$tests['direct']['buddyboss_addons_high'] = array(
				'label' => __( 'BuddyBoss Pro/Plus License - Urgent Action Required', 'buddyboss' ),
				'test'  => array( $this, 'site_health_high_test' ),
			);
		}

		if ( ! empty( $grouped['medium'] ) ) {
			$tests['direct']['buddyboss_addons_medium'] = array(
				'label' => __( 'BuddyBoss Pro/Plus License - Action Required', 'buddyboss' ),
				'test'  => array( $this, 'site_health_medium_test' ),
			);
		}

		// LOW status (7-13 days) does NOT register Site Health test.
		// Only plugin notification is shown during this period.

		return $tests;
	}

	/**
	 * Site Health test for LOCKED status (31+ days).
	 *
	 * @since BuddyBoss 2.16.0
	 *
	 * @return array Test result.
	 */
	public function site_health_locked_test() {
		$grouped = self::get_addons_by_drm_status();

		if ( empty( $grouped['locked'] ) ) {
			return $this->get_site_health_pass();
		}

		$addons      = $grouped['locked'];
		$addon_names = wp_list_pluck( $addons, 'plugin_name' );

		$activation_link = bp_get_admin_url( 'admin.php?page=buddyboss-license' );
		$account_link    = 'https://www.buddyboss.com/my-account/';
		$support_link    = 'https://www.buddyboss.com/support/';
		$pricing_link    = 'https://www.buddyboss.com/pricing/';

		// Title and message match BuddyBoss DRM Messaging.pdf specification.
		$title = __( 'Your BuddyBoss features have now been disabled', 'buddyboss' );

		$message = __( 'BuddyBoss features on your site have been disabled because no active license was found. Activate your license to restore functionality, or deactivate premium add-ons in your Plugins page to continue using BuddyBoss Platform for free.', 'buddyboss' );

		return array(
			'label'       => $title,
			'status'      => 'critical',
			'badge'       => array(
				'label' => __( 'BuddyBoss', 'buddyboss' ),
				'color' => 'red',
			),
			'description' => sprintf( '<p>%s</p>', $message ),
			'actions'     => sprintf(
				'<p><a href="%s" class="button button-primary">%s</a></p><p><a href="%s" target="_blank" class="button">%s</a></p>',
				esc_url( $activation_link ),
				esc_html__( 'Activate Your License', 'buddyboss' ),
				esc_url( $pricing_link ),
				esc_html__( 'Purchase License', 'buddyboss' )
			),
			'test'        => 'buddyboss_addons_locked',
		);
	}

	/**
	 * Site Health test for HIGH status (22-30 days).
	 *
	 * @since BuddyBoss 2.16.0
	 *
	 * @return array Test result.
	 */
	public function site_health_high_test() {
		$grouped = self::get_addons_by_drm_status();

		if ( empty( $grouped['high'] ) ) {
			return $this->get_site_health_pass();
		}

		$addons      = $grouped['high'];
		$addon_names = wp_list_pluck( $addons, 'plugin_name' );

		$activation_link = bp_get_admin_url( 'admin.php?page=buddyboss-license' );
		$account_link    = 'https://www.buddyboss.com/my-account/';
		$support_link    = 'https://www.buddyboss.com/support/';

		// Title and message match BuddyBoss DRM Messaging.pdf specification.
		$title = __( 'Your BuddyBoss license requires activation', 'buddyboss' );

		$message = __( 'Your site is using BuddyBoss features without an active license. These features will be disabled if a license is not activated soon.', 'buddyboss' );

		return array(
			'label'       => $title,
			'status'      => 'critical',
			'badge'       => array(
				'label' => __( 'BuddyBoss', 'buddyboss' ),
				'color' => 'orange',
			),
			'description' => sprintf( '<p>%s</p>', $message ),
			'actions'     => sprintf(
				'<p><a href="%s" class="button button-primary">%s</a></p>',
				esc_url( $activation_link ),
				esc_html__( 'Activate Your License', 'buddyboss' )
			),
			'test'        => 'buddyboss_addons_high',
		);
	}

	/**
	 * Site Health test for MEDIUM status (14-21 days).
	 *
	 * @since BuddyBoss 2.16.0
	 *
	 * @return array Test result.
	 */
	public function site_health_medium_test() {
		$grouped = self::get_addons_by_drm_status();

		if ( empty( $grouped['medium'] ) ) {
			return $this->get_site_health_pass();
		}

		$addons      = $grouped['medium'];
		$addon_names = wp_list_pluck( $addons, 'plugin_name' );

		$activation_link = bp_get_admin_url( 'admin.php?page=buddyboss-license' );
		$account_link    = 'https://www.buddyboss.com/my-account/';
		$support_link    = 'https://www.buddyboss.com/support/';

		// Title and message match BuddyBoss DRM Messaging.pdf specification.
		$title = __( 'Your BuddyBoss license is not activated', 'buddyboss' );

		$message = __( 'Your site is using BuddyBoss features that require an active license. Activate your license to ensure continued access to these features.', 'buddyboss' );

		return array(
			'label'       => $title,
			'status'      => 'recommended',
			'badge'       => array(
				'label' => __( 'BuddyBoss', 'buddyboss' ),
				'color' => 'orange',
			),
			'description' => sprintf( '<p>%s</p>', $message ),
			'actions'     => sprintf(
				'<p><a href="%s" class="button button-primary">%s</a></p>',
				esc_url( $activation_link ),
				esc_html__( 'Activate Your License', 'buddyboss' )
			),
			'test'        => 'buddyboss_addons_medium',
		);
	}

	/**
	 * Get Site Health pass result.
	 *
	 * @since BuddyBoss 2.16.0
	 *
	 * @return array Site Health result.
	 */
	private function get_site_health_pass() {
		return array(
			'label'       => __( 'BuddyBoss Pro/Plus license is active', 'buddyboss' ),
			'status'      => 'good',
			'badge'       => array(
				'label' => __( 'BuddyBoss', 'buddyboss' ),
				'color' => 'blue',
			),
			'description' => sprintf(
				'<p>%s</p>',
				__( 'All BuddyBoss Pro/Plus addon licenses are valid and active.', 'buddyboss' )
			),
			'test'        => 'buddyboss_addons_license',
		);
	}
}
