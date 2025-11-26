<?php
/**
 * BuddyBoss DRM Registry
 *
 * Central registry for managing DRM across multiple add-on plugins.
 * Add-ons register themselves here to participate in the DRM system.
 *
 * @package BuddyBoss\Core\Admin\DRM
 * @since 3.0.0
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
	 */
	private function __construct() {
		// Private constructor to enforce singleton.
		// Hook consolidated admin notices.
		add_action( 'admin_notices', array( $this, 'render_consolidated_admin_notices' ), 5 );
	}

	/**
	 * Register an add-on for DRM management.
	 *
	 * Add-on plugins should call this during their initialization.
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
			function() use ( $drm_addon, $product_slug ) {
				self::run_addon_drm_check( $product_slug );
			},
			25
		);

		return $drm_addon;
	}

	/**
	 * Run DRM check for a specific add-on.
	 *
	 * @param string $product_slug The product slug.
	 */
	private static function run_addon_drm_check( $product_slug ) {
		if ( ! isset( self::$addons[ $product_slug ] ) ) {
			return;
		}

		$addon = self::$addons[ $product_slug ];
		$drm   = $addon['drm'];

		// Check if license is valid.
		if ( $drm->is_addon_licensed() ) {
			// License valid - clean up any DRM state.
			self::cleanup_addon_drm( $product_slug );
			return;
		}

		// Create event if needed.
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
	 * Cleanup DRM state for an add-on when license becomes valid.
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

		// Dismiss notifications for this add-on.
		$notifications = new BB_Notifications();
		$notifications->dismiss_events( 'bb-drm-addon-' . $product_slug );
	}

	/**
	 * Get all registered add-ons.
	 *
	 * @return array Array of registered add-ons.
	 */
	public static function get_registered_addons() {
		return self::$addons;
	}

	/**
	 * Check if a specific add-on is registered.
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
	 *
	 * Add-ons can use this to show a consistent lockout message.
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
	 * Get all addons grouped by DRM status.
	 *
	 * @return array Array of addons grouped by status: ['low' => [], 'medium' => [], 'locked' => []].
	 */
	public static function get_addons_by_drm_status() {
		// Cache result per request to avoid repeated calculations.
		static $cache = null;

		if ( null !== $cache ) {
			return $cache;
		}

		$grouped = array(
			'low'    => array(),
			'medium' => array(),
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
			if ( $days >= 7 && $days <= 13 ) {
				$status = 'low';
			} elseif ( $days >= 14 && $days <= 20 ) {
				$status = 'medium';
			} elseif ( $days >= 21 ) {
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
	 * This replaces individual addon notices with a single grouped notice.
	 */
	public function render_consolidated_admin_notices() {
		if ( ! current_user_can( 'manage_options' ) ) {
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

		// Render MEDIUM notice.
		if ( ! empty( $grouped['medium'] ) ) {
			$this->render_grouped_notice( $grouped['medium'], BB_DRM_Helper::DRM_MEDIUM );
		}

		// Render LOW notice.
		if ( ! empty( $grouped['low'] ) ) {
			$this->render_grouped_notice( $grouped['low'], BB_DRM_Helper::DRM_LOW );
		}
	}

	/**
	 * Render a single grouped notice for addons of the same status.
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
		$first_addon    = $addons[0];
		$event          = $first_addon['event'];
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
			function( $addon ) {
				return esc_html( $addon['plugin_name'] );
			},
			$addons
		);

		// Build HTML list for multiple addons.
		$addon_list_html = '';
		if ( $count > 1 ) {
			$addon_list_html = '<ul style="margin: 10px 0; padding-left: 20px;">';
			foreach ( $addon_names as $name ) {
				$addon_list_html .= '<li><strong>' . $name . '</strong></li>';
			}
			$addon_list_html .= '</ul>';
		}

		$activation_link = bp_get_admin_url( 'admin.php?page=buddyboss-license' );

		// Build heading and message based on status and count.
		switch ( $drm_status ) {
			case BB_DRM_Helper::DRM_LOW:
				if ( $count === 1 ) {
					$heading = sprintf(
						/* translators: %s: plugin name */
						__( '%s: License Required', 'buddyboss' ),
						$addons[0]['plugin_name']
					);
					$message = sprintf(
						/* translators: %s: plugin name */
						__( '%s requires an active license to continue working. Please activate your license key.', 'buddyboss' ),
						$addons[0]['plugin_name']
					);
				} else {
					$heading = sprintf(
						/* translators: %d: number of plugins */
						_n( '%d BuddyBoss Add-on: License Required', '%d BuddyBoss Add-ons: License Required', $count, 'buddyboss' ),
						$count
					);
					$message = __( 'The following add-ons require active licenses to continue working. Please activate your license keys.', 'buddyboss' ) . $addon_list_html;
				}
				$notice_class = 'notice notice-warning is-dismissible';
				$color        = 'orange';
				break;

			case BB_DRM_Helper::DRM_MEDIUM:
				if ( $count === 1 ) {
					$heading = sprintf(
						/* translators: %s: plugin name */
						__( '%s: URGENT - License Required', 'buddyboss' ),
						$addons[0]['plugin_name']
					);
					$message = sprintf(
						/* translators: %s: plugin name */
						__( '%s will be disabled soon without an active license. Please activate immediately.', 'buddyboss' ),
						$addons[0]['plugin_name']
					);
				} else {
					$heading = sprintf(
						/* translators: %d: number of plugins */
						_n( '%d BuddyBoss Add-on: URGENT - License Required', '%d BuddyBoss Add-ons: URGENT - License Required', $count, 'buddyboss' ),
						$count
					);
					$message = __( 'The following add-ons will be disabled soon without active licenses. Please activate immediately.', 'buddyboss' ) . $addon_list_html;
				}
				$notice_class = 'notice notice-warning is-dismissible';
				$color        = 'orange';
				break;

			case BB_DRM_Helper::DRM_LOCKED:
				if ( $count === 1 ) {
					$heading = sprintf(
						/* translators: %s: plugin name */
						__( '%s: Features Disabled', 'buddyboss' ),
						$addons[0]['plugin_name']
					);
					$message = sprintf(
						/* translators: %s: plugin name */
						__( '%s features have been disabled due to an inactive license. Activate your license to restore functionality.', 'buddyboss' ),
						$addons[0]['plugin_name']
					);
				} else {
					$heading = sprintf(
						/* translators: %d: number of plugins */
						_n( '%d BuddyBoss Add-on: Features Disabled', '%d BuddyBoss Add-ons: Features Disabled', $count, 'buddyboss' ),
						$count
					);
					$message = __( 'The following add-ons have been disabled due to inactive licenses. Activate your licenses to restore functionality.', 'buddyboss' ) . $addon_list_html;
				}
				$notice_class = 'notice notice-error';
				$color        = 'red';
				break;

			default:
				return;
		}

		// Generate unique notice ID and secret for dismissal.
		$notice_id = 'bb-drm-consolidated-notice-' . sanitize_key( $notice_key );
		$secret    = $is_warning ? hash( 'sha256', $notice_key ) . '-' . hash( 'sha256', $event->event ) : '';

		?>
		<div id="<?php echo esc_attr( $notice_id ); ?>"
			 class="<?php echo esc_attr( $notice_class ); ?> bb-drm-notice"
			 style="padding: 15px; border-left-width: 4px; position: relative;"
			 <?php if ( $is_warning ) : ?>
			 data-notice-key="<?php echo esc_attr( $notice_key ); ?>"
			 data-secret="<?php echo esc_attr( $secret ); ?>"
			 <?php endif; ?>>
			<h3 style="margin-top: 0;"><?php echo esc_html( $heading ); ?></h3>
			<div style="margin-bottom: 10px;"><?php echo wp_kses_post( $message ); ?></div>
			<p style="margin-bottom: 0;">
				<a href="<?php echo esc_url( $activation_link ); ?>" class="button button-primary">
					<?php esc_html_e( 'Activate License', 'buddyboss' ); ?>
				</a>
				<?php if ( $is_warning ) : ?>
				<button type="button" class="notice-dismiss bb-drm-dismiss" aria-label="<?php esc_attr_e( 'Dismiss this notice for 24 hours', 'buddyboss' ); ?>">
					<span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice for 24 hours.', 'buddyboss' ); ?></span>
				</button>
				<?php endif; ?>
			</p>
		</div>
		<?php

		// Enqueue dismiss script if warning.
		if ( $is_warning ) {
			$this->enqueue_dismiss_script();
		}
	}

	/**
	 * Enqueue dismiss script for consolidated notices.
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
	 */
	public function update_consolidated_notification() {
		static $updated = false;
		if ( $updated ) {
			return;
		}
		$updated = true;

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
			if ( BB_DRM_Helper::DRM_LOCKED === $drm_status ) {
				$icon_url = admin_url( 'images/no.png' ); // Red
			} elseif ( BB_DRM_Helper::DRM_HIGH === $drm_status ) {
				$icon_url = admin_url( 'images/no.png' ); // Orange (using red icon for now)
			} else {
				$icon_url = admin_url( 'images/yes.png' ); // Yellow
			}

			// Build title and content.
			if ( $count === 1 ) {
				$title   = sprintf(
					/* translators: %s: plugin name */
					__( '%s: License Required', 'buddyboss' ),
					$addon_names[0]
				);
				$content = sprintf(
					/* translators: %s: plugin name */
					__( '%s requires an active license to continue working.', 'buddyboss' ),
					$addon_names[0]
				);
			} else {
				$title = sprintf(
					/* translators: %d: number of plugins */
					_n( '%d BuddyBoss Add-on: License Required', '%d BuddyBoss Add-ons: License Required', $count, 'buddyboss' ),
					$count
				);

				// Build HTML list for notification.
				$addon_list = '<ul>';
				foreach ( $addon_names as $name ) {
					$addon_list .= '<li>' . esc_html( $name ) . '</li>';
				}
				$addon_list .= '</ul>';

				$content = __( 'The following add-ons require active licenses:', 'buddyboss' ) . $addon_list;
			}

			// Add urgency based on status.
			if ( BB_DRM_Helper::DRM_LOCKED === $drm_status ) {
				$content .= '<p>' . __( 'Features have been disabled.', 'buddyboss' ) . '</p>';
			} elseif ( BB_DRM_Helper::DRM_HIGH === $drm_status ) {
				$content .= '<p>' . __( 'Features will be disabled soon.', 'buddyboss' ) . '</p>';
			} elseif ( BB_DRM_Helper::DRM_MEDIUM === $drm_status ) {
				$content .= '<p>' . __( 'Please activate your license.', 'buddyboss' ) . '</p>';
			}

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
							'text'   => __( 'Activate Licenses', 'buddyboss' ),
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
	 * Send consolidated email for all addons with license issues.
	 *
	 * Sends a single email listing all affected addons instead of separate emails.
	 */
	public function send_consolidated_email() {
		static $sent = false;
		if ( $sent ) {
			return;
		}
		$sent = true;

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
		switch ( $highest_priority ) {
			case BB_DRM_Helper::DRM_LOW:
				if ( $count === 1 ) {
					$subject = sprintf(
						/* translators: %s: plugin name */
						__( '%s: License Required', 'buddyboss' ),
						$addon_names[0]
					);
					$heading = $subject;
					$message = sprintf(
						/* translators: %s: plugin name */
						__( '%s requires an active license to continue working. Please activate your license key.', 'buddyboss' ),
						$addon_names[0]
					);
				} else {
					$subject = sprintf(
						/* translators: %d: number of plugins */
						_n( '%d BuddyBoss Add-on: License Required', '%d BuddyBoss Add-ons: License Required', $count, 'buddyboss' ),
						$count
					);
					$heading = $subject;
					$message = __( 'The following add-ons require active licenses to continue working:', 'buddyboss' );
				}
				$color = 'FFA500'; // Orange
				break;

			case BB_DRM_Helper::DRM_MEDIUM:
				if ( $count === 1 ) {
					$subject = sprintf(
						/* translators: %s: plugin name */
						__( '%s: URGENT - License Required', 'buddyboss' ),
						$addon_names[0]
					);
					$heading = $subject;
					$message = sprintf(
						/* translators: %s: plugin name */
						__( '%s will be disabled soon without an active license. Please activate immediately.', 'buddyboss' ),
						$addon_names[0]
					);
				} else {
					$subject = sprintf(
						/* translators: %d: number of plugins */
						_n( '%d BuddyBoss Add-on: URGENT - License Required', '%d BuddyBoss Add-ons: URGENT - License Required', $count, 'buddyboss' ),
						$count
					);
					$heading = $subject;
					$message = __( 'The following add-ons will be disabled soon without active licenses. Please activate immediately.', 'buddyboss' );
				}
				$color = 'FFA500'; // Orange
				break;

			case BB_DRM_Helper::DRM_LOCKED:
				if ( $count === 1 ) {
					$subject = sprintf(
						/* translators: %s: plugin name */
						__( '%s: Features Disabled', 'buddyboss' ),
						$addon_names[0]
					);
					$heading = $subject;
					$message = sprintf(
						/* translators: %s: plugin name */
						__( '%s features have been disabled due to an inactive license. Activate your license to restore functionality.', 'buddyboss' ),
						$addon_names[0]
					);
				} else {
					$subject = sprintf(
						/* translators: %d: number of plugins */
						_n( '%d BuddyBoss Add-on: Features Disabled', '%d BuddyBoss Add-ons: Features Disabled', $count, 'buddyboss' ),
						$count
					);
					$heading = $subject;
					$message = __( 'The following add-ons have been disabled due to inactive licenses. Activate your licenses to restore functionality.', 'buddyboss' );
				}
				$color = 'dc3232'; // Red
				break;

			default:
				return;
		}

		// Build HTML list for multiple addons.
		$addon_list_html = '';
		if ( $count > 1 ) {
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
			)
		);

		// Send email.
		$headers     = array(
			sprintf( 'Content-type: text/html; charset=%s', get_bloginfo( 'charset' ) ),
		);
		$admin_email = get_option( 'admin_email' );
		wp_mail( $admin_email, $subject, $email_html, $headers );

		// Mark email as sent for all affected addons.
		foreach ( $affected_addons as $addon ) {
			$event = $addon['event'];
			$args  = $event->get_args();
			$data  = is_object( $args ) ? (array) $args : ( is_array( $args ) ? $args : array() );

			// Store sent timestamp.
			$data[ $status_key . '_sent' ] = current_time( 'mysql' );

			$event->args = wp_json_encode( $data );
			$event->store();
		}
	}

	/**
	 * Check if email should be sent for this status.
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
	 * @param array $data Email data (heading, message, addon_list_html, color, activation_link).
	 * @return string Email HTML.
	 */
	private function get_consolidated_email_html( $data ) {
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
				<h2 style="color: #<?php echo esc_attr( $data['color'] ); ?>;">
					<?php echo esc_html( $data['heading'] ); ?>
				</h2>
				<div style="background: #f9f9f9; padding: 20px; border-radius: 5px;">
					<p><?php echo esc_html( $data['message'] ); ?></p>
					<?php if ( ! empty( $data['addon_list_html'] ) ) : ?>
						<?php echo wp_kses_post( $data['addon_list_html'] ); ?>
					<?php endif; ?>
				</div>
				<p style="margin-top: 20px;">
					<a href="<?php echo esc_url( $data['activation_link'] ); ?>"
					   style="background: #0073aa; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 3px; display: inline-block;">
						<?php esc_html_e( 'Activate License', 'buddyboss' ); ?>
					</a>
				</p>
				<p style="color: #666; font-size: 12px; margin-top: 30px;">
					<?php esc_html_e( 'Please activate your BuddyBoss license to continue using all features.', 'buddyboss' ); ?>
				</p>
			</div>
		</body>
		</html>
		<?php
		return ob_get_clean();
	}
}
