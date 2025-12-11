<?php
/**
 * BuddyBoss Base DRM
 *
 * Abstract base class for DRM functionality.
 * Handles notification generation, email sending, and admin notices.
 *
 * @package BuddyBoss\Core\Admin\DRM
 * @since BuddyBoss [BBVERSION]
 */

namespace BuddyBoss\Core\Admin\DRM;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Abstract Base DRM class.
 */
abstract class BB_Base_DRM {

	/**
	 * The DRM event object.
	 *
	 * @var object|null
	 */
	protected $event = null;

	/**
	 * The name of the DRM event.
	 *
	 * @var string
	 */
	protected $event_name = '';

	/**
	 * The current DRM status.
	 *
	 * @var string
	 */
	protected $drm_status = '';

	/**
	 * Constructor for the BB_Base_DRM class.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Initializes the DRM status.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	protected function init() {
		$this->drm_status = '';
	}

	/**
	 * Checks if the DRM status is locked.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return bool True if locked, false otherwise.
	 */
	public function is_locked() {
		return BB_DRM_Helper::is_locked( $this->drm_status );
	}

	/**
	 * Checks if the DRM status is medium.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return bool True if medium, false otherwise.
	 */
	public function is_medium() {
		return BB_DRM_Helper::is_medium( $this->drm_status );
	}

	/**
	 * Checks if the DRM status is low.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return bool True if low, false otherwise.
	 */
	public function is_low() {
		return BB_DRM_Helper::is_low( $this->drm_status );
	}

	/**
	 * Sets the DRM status.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $status The DRM status to set.
	 */
	protected function set_status( $status ) {
		$this->drm_status = $status;

		// Set global value.
		BB_DRM_Helper::set_status( $status );
	}

	/**
	 * Creates a DRM event.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return int|false Event ID on success, false on failure.
	 */
	public function create_event() {
		// Determine event entity type.
		$evt_id_type = 'platform';
		$evt_id      = 1;

		// Check if this is an addon event.
		if ( strpos( $this->event_name, 'addon-' ) === 0 ) {
			$evt_id_type = 'addon';
		}

		// Create event in database.
		return BB_DRM_Event::record( $this->event_name, $evt_id, $evt_id_type, array() );
	}

	/**
	 * Gets the latest DRM event.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return BB_DRM_Event|null The event object or null.
	 */
	protected function get_latest_event() {
		return BB_DRM_Event::latest( $this->event_name );
	}

	/**
	 * Public method to get the latest DRM event.
	 * Wrapper for protected get_latest_event() for external access.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return BB_DRM_Event|null The event object or null.
	 */
	public function get_event() {
		return $this->get_latest_event();
	}

	/**
	 * Updates a DRM event with new data.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param BB_DRM_Event $event The event to update.
	 * @param mixed        $data  The data to update the event with.
	 * @return int|false Event ID on success, false on failure.
	 */
	protected function update_event( $event, $data ) {
		if ( ! $event instanceof BB_DRM_Event || $event->id <= 0 ) {
			return false;
		}

		// Update event args with new data.
		if ( is_array( $data ) || is_object( $data ) ) {
			$event->args = wp_json_encode( $data );
		} else {
			$event->args = $data;
		}

		return $event->store();
	}

	/**
	 * Handles a DRM event.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param BB_DRM_Event $event      The event to handle.
	 * @param integer      $days       The number of days since the event.
	 * @param string       $drm_status The DRM status.
	 */
	public function drm_event( $event, $days, $drm_status ) {
		$this->event = $event;

		// Get event args (stored as JSON in database).
		$args          = $event->get_args();
		$event_data    = is_object( $args ) ? (array) $args : ( is_array( $args ) ? $args : array() );
		$drm_event_key = BB_DRM_Helper::get_status_key( $drm_status );

		// Send email and mark as sent only once per status level.
		if ( ! isset( $event_data[ $drm_event_key ] ) ) {
			// Send email.
			$this->send_email( $drm_status );

			// Mark event complete.
			$event_data[ $drm_event_key ] = current_time( 'mysql' );

			$this->update_event( $event, $event_data );
		}

		// Always create/update in-plugin notification.
		// The notification system handles duplicates via consistent IDs.
		$this->create_inplugin_notification( $drm_status );

		add_action( 'admin_notices', array( $this, 'admin_notices' ), 11 );

		if ( BB_DRM_Helper::is_locked() ) {
			add_action( 'admin_body_class', array( $this, 'admin_body_class' ), 20 );
		}
	}

	/**
	 * Adds custom classes to the admin body tag.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $classes The current classes.
	 * @return string The modified classes.
	 */
	public function admin_body_class( $classes ) {
		$classes .= ' bb-drm-locked';
		return $classes;
	}

	/**
	 * Displays admin notices related to DRM.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function admin_notices() {
		if ( ! $this->event instanceof BB_DRM_Event ) {
			return;
		}

		$drm_status = BB_DRM_Helper::get_status();

		if ( '' !== $drm_status ) {
			$drm_info               = BB_DRM_Helper::get_info( $drm_status, $this->event_name, 'admin_notices' );
			$drm_info['notice_key'] = BB_DRM_Helper::get_status_key( $drm_status );
			$drm_info['event_name'] = $this->event_name;

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
	 * Renders an admin notice.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $drm_info The DRM information.
	 */
	protected function render_admin_notice( $drm_info ) {
		// Return early if no heading or message.
		if ( empty( $drm_info['heading'] ) || empty( $drm_info['message'] ) ) {
			return;
		}

		// Determine if this is a warning (dismissible) or error (not dismissible).
		$is_locked    = BB_DRM_Helper::is_locked();
		$is_warning   = BB_DRM_Helper::is_low() || BB_DRM_Helper::is_medium();
		$notice_class = 'notice notice-error';

		if ( $is_warning ) {
			// Warnings (LOW/MEDIUM) are dismissible.
			$notice_class = 'notice notice-warning is-dismissible';
		}

		// Default values for optional keys.
		$support_link = isset( $drm_info['support_link'] ) ? $drm_info['support_link'] : bp_get_admin_url( 'admin.php?page=buddyboss-license' );
		$help_message = isset( $drm_info['help_message'] ) ? $drm_info['help_message'] : __( 'Activate Your License', 'buddyboss' );

		// Generate unique notice ID for dismissal (only for warnings).
		$notice_key = $drm_info['notice_key'] ?? '';
		$event_name = $drm_info['event_name'] ?? '';
		$notice_id  = 'bb-drm-notice-' . sanitize_key( $notice_key );

		// Create security hash for AJAX (only for warnings).
		$secret = $is_warning ? hash( 'sha256', $notice_key ) . '-' . hash( 'sha256', $event_name ) : '';
		?>
		<div id="<?php echo esc_attr( $notice_id ); ?>"
			class="<?php echo esc_attr( $notice_class ); ?> bb-drm-notice"
			style="padding: 15px; border-left-width: 4px; position: relative;"
			<?php if ( $is_warning ) : ?>
			data-notice-key="<?php echo esc_attr( $notice_key ); ?>"
			data-secret="<?php echo esc_attr( $secret ); ?>"
			<?php endif; ?>>
			<h3 style="margin-top: 0;"><?php echo esc_html( $drm_info['heading'] ); ?></h3>
			<div style="margin-bottom: 10px;"><?php echo wp_kses_post( $drm_info['message'] ); ?></div>
			<p style="margin-bottom: 0;">
				<a href="<?php echo esc_url( $support_link ); ?>" class="button button-primary">
					<?php echo esc_html( $help_message ); ?>
				</a>
				<?php if ( $is_warning ) : ?>
				<button type="button" class="notice-dismiss bb-drm-dismiss" aria-label="<?php esc_attr_e( 'Dismiss this notice for 24 hours', 'buddyboss' ); ?>">
					<span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice for 24 hours.', 'buddyboss' ); ?></span>
				</button>
				<?php endif; ?>
			</p>
		</div>
		<?php

		// Enqueue dismiss script only if this is a dismissible warning.
		if ( $is_warning ) {
			$this->enqueue_dismiss_script();
		}
	}

	/**
	 * Enqueue script for dismissing notices.
	 *
	 * @since BuddyBoss [BBVERSION]
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
					// Fallback to default WordPress notice dismiss behavior.
					$notice.fadeOut();
					return;
				}

				// Fade out immediately for better UX.
				$notice.fadeOut();

				// Send AJAX request to dismiss.
				$.post(ajaxurl, {
					action: 'bb_dismiss_notice_drm',
					notice: noticeKey,
					secret: secret,
					nonce: '<?php echo wp_create_nonce( 'bb_dismiss_notice' ); ?>'
				}, function(response) {
					if (response.success) {
						console.log('DRM notice dismissed for 24 hours');
					} else {
						console.error('Failed to dismiss DRM notice:', response.data);
					}
				}).fail(function() {
					console.error('AJAX error dismissing DRM notice');
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
			-webkit-font-smoothing: antialiased;
			-moz-osx-font-smoothing: grayscale;
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
	 * Sends an email notification based on DRM status.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $drm_status The DRM status.
	 */
	protected function send_email( $drm_status ) {
		$drm_info = BB_DRM_Helper::get_info( $drm_status, $this->event_name, 'email' );
		if ( empty( $drm_info['heading'] ) ) {
			return;
		}

		$subject = $drm_info['heading'];
		$message = $this->get_email_message( $drm_info );

		$headers = array(
			sprintf( 'Content-type: text/html; charset=%s', get_bloginfo( 'charset' ) ),
		);

		// Send to admin email.
		$admin_email = get_option( 'admin_email' );
		wp_mail( $admin_email, $subject, $message, $headers );
	}

	/**
	 * Gets the email message HTML.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $drm_info The DRM information.
	 * @return string The email message HTML.
	 */
	protected function get_email_message( $drm_info ) {
		ob_start();
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<meta charset="utf-8">
			<title><?php echo esc_html( $drm_info['heading'] ); ?></title>
		</head>
		<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
			<div style="max-width: 600px; margin: 0 auto; padding: 20px;">
				<h2 style="color: #<?php echo esc_attr( $drm_info['color'] ); ?>;">
					<?php echo esc_html( $drm_info['heading'] ); ?>
				</h2>
				<div style="background: #f9f9f9; padding: 20px; border-radius: 5px;">
					<?php echo wp_kses_post( $drm_info['message'] ); ?>
				</div>
				<p style="margin-top: 20px;">
					<a href="<?php echo esc_url( $drm_info['support_link'] ); ?>"
						style="background: #0073aa; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 3px; display: inline-block;">
						<?php echo esc_html( $drm_info['help_message'] ); ?>
					</a>
				</p>
				<p style="color: #666; font-size: 12px; margin-top: 30px;">
					<?php echo esc_html( $drm_info['additional_instructions'] ); ?>
				</p>
			</div>
		</body>
		</html>
		<?php
		return ob_get_clean();
	}

	/**
	 * Creates an in-plugin notification based on DRM status.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $drm_status The DRM status.
	 */
	protected function create_inplugin_notification( $drm_status ) {
		$drm_info = BB_DRM_Helper::get_info( $drm_status, $this->event_name, 'inplugin' );
		if ( empty( $drm_info['heading'] ) ) {
			return;
		}

		// Determine icon based on severity.
		if ( BB_DRM_Helper::DRM_LOCKED === $drm_status || BB_DRM_Helper::DRM_HIGH === $drm_status ) {
			$icon_url = buddypress()->plugin_url . 'bp-core/images/dh-icon.png'; // Red.
		} else {
			$icon_url = buddypress()->plugin_url . 'bp-core/images/dl-icon.png'; // Yellow.
		}

		$notifications = new BB_Notifications();
		// Use a consistent ID based on event name and status to prevent duplicate notifications.
		// The notification system will update existing notification if ID matches.
		$notification_id = 'license_' . $this->event_name . '_' . BB_DRM_Helper::get_status_key( $drm_status );
		$notifications->add(
			array(
				'id'      => $notification_id,
				'title'   => $drm_info['heading'],
				'content' => $drm_info['message'],
				'type'    => 'bb-drm',
				'segment' => '',
				'saved'   => time(),
				'end'     => '',
				'icon'    => $icon_url,
				'buttons' => array(
					'main' => array(
						'text'   => __( 'Contact Us', 'buddyboss' ),
						'url'    => $drm_info['support_link'],
						'target' => '_blank',
					),
				),
			)
		);
	}

	/**
	 * Creates a DRM event if none exists within the last 30 days.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function maybe_create_event() {
		// Check if event exists within the last 30 days.
		$event = $this->get_latest_event();

		if ( ! $event ) {
			$this->create_event();
			return;
		}

		// Check if event is older than 30 days.
		$days = BB_DRM_Helper::days_elapsed( $event->created_at );
		if ( $days >= 30 ) {
			$this->create_event();
		}
	}

	/**
	 * Abstract method to run the DRM process.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	abstract public function run();
}
