<?php
/**
 * BuddyBoss Base DRM
 *
 * Abstract base class for DRM functionality.
 * Handles notification generation, email sending, and admin notices.
 *
 * @package BuddyBoss\Core\Admin\DRM
 * @since 3.0.0
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
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Initializes the DRM status.
	 */
	protected function init() {
		$this->drm_status = '';
	}

	/**
	 * Checks if the DRM status is locked.
	 *
	 * @return bool True if locked, false otherwise.
	 */
	public function is_locked() {
		return BB_DRM_Helper::is_locked( $this->drm_status );
	}

	/**
	 * Checks if the DRM status is medium.
	 *
	 * @return bool True if medium, false otherwise.
	 */
	public function is_medium() {
		return BB_DRM_Helper::is_medium( $this->drm_status );
	}

	/**
	 * Checks if the DRM status is low.
	 *
	 * @return bool True if low, false otherwise.
	 */
	public function is_low() {
		return BB_DRM_Helper::is_low( $this->drm_status );
	}

	/**
	 * Sets the DRM status.
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
	 * @return BB_DRM_Event|null The event object or null.
	 */
	protected function get_latest_event() {
		return BB_DRM_Event::latest( $this->event_name );
	}

	/**
	 * Public method to get the latest DRM event.
	 * Wrapper for protected get_latest_event() for external access.
	 *
	 * @since 3.0.0
	 * @return BB_DRM_Event|null The event object or null.
	 */
	public function get_event() {
		return $this->get_latest_event();
	}

	/**
	 * Updates a DRM event with new data.
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

		// Just make sure we run this once.
		if ( ! isset( $event_data[ $drm_event_key ] ) ) {
			// Send email.
			$this->send_email( $drm_status );

			// Create in-plugin notification.
			$this->create_inplugin_notification( $drm_status );

			// Mark event complete.
			$event_data[ $drm_event_key ] = current_time( 'mysql' );

			$this->update_event( $event, $event_data );
		}

		add_action( 'admin_notices', array( $this, 'admin_notices' ), 11 );

		if ( BB_DRM_Helper::is_locked() ) {
			add_action( 'admin_body_class', array( $this, 'admin_body_class' ), 20 );
		}
	}

	/**
	 * Adds custom classes to the admin body tag.
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
	 */
	public function admin_notices() {
		if ( ! $this->event instanceof BB_DRM_Event ) {
			return;
		}

		$drm_status = BB_DRM_Helper::get_status();

		if ( '' !== $drm_status ) {
			$drm_info                = BB_DRM_Helper::get_info( $drm_status, $this->event_name, 'admin_notices' );
			$drm_info['notice_key']  = BB_DRM_Helper::get_status_key( $drm_status );
			$drm_info['event_name']  = $this->event_name;

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
	 * @param array $drm_info The DRM information.
	 */
	protected function render_admin_notice( $drm_info ) {
		// Return early if no heading or message.
		if ( empty( $drm_info['heading'] ) || empty( $drm_info['message'] ) ) {
			return;
		}

		$notice_class = 'notice notice-error';
		if ( BB_DRM_Helper::is_low() ) {
			$notice_class = 'notice notice-warning';
		}

		// Default values for optional keys.
		$support_link = isset( $drm_info['support_link'] ) ? $drm_info['support_link'] : bp_get_admin_url( 'admin.php?page=buddyboss-license' );
		$help_message = isset( $drm_info['help_message'] ) ? $drm_info['help_message'] : __( 'Activate License', 'buddyboss' );
		?>
		<div class="<?php echo esc_attr( $notice_class ); ?> bb-drm-notice" style="padding: 15px; border-left-width: 4px;">
			<h3 style="margin-top: 0;"><?php echo esc_html( $drm_info['heading'] ); ?></h3>
			<div style="margin-bottom: 10px;"><?php echo wp_kses_post( $drm_info['message'] ); ?></div>
			<p style="margin-bottom: 0;">
				<a href="<?php echo esc_url( $support_link ); ?>" class="button button-primary">
					<?php echo esc_html( $help_message ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	/**
	 * Sends an email notification based on DRM status.
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
	 * @param string $drm_status The DRM status.
	 */
	protected function create_inplugin_notification( $drm_status ) {
		$drm_info = BB_DRM_Helper::get_info( $drm_status, $this->event_name, 'inplugin' );
		if ( empty( $drm_info['heading'] ) ) {
			return;
		}

		// Get the icon URL.
		$icon_url = buddypress()->plugin_url . 'bp-core/admin/assets/images/alert-icon.png';

		$notifications = new BB_Notifications();
		$notifications->add(
			array(
				'id'      => 'event_' . time(),
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
	 */
	abstract public function run();
}
