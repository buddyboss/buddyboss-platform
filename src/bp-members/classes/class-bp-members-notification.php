<?php
/**
 * BuddyBoss Members Account Settings Notification Class.
 *
 * @package BuddyBoss\Activity
 *
 * @since BuddyBoss [BBVERSION]
 */

defined( 'ABSPATH' ) || exit;

/**
 * Set up the BP_Members_Notification class.
 *
 * @since BuddyBoss [BBVERSION]
 */
class BP_Members_Notification extends BP_Core_Notification_Abstract {

	/**
	 * Instance of this class.
	 *
	 * @var object
	 */
	private static $instance = null;

	/**
	 * Get the instance of this class.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return null|BP_Activity_Notification|Controller|object
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor method.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function __construct() {
		// Initialize.
		$this->start();
	}

	/**
	 * Initialize all methods inside it.
	 *
	 * @return mixed|void
	 */
	public function load() {
		$this->register_notification_group(
			'members',
			esc_html__( 'Account Settings', 'buddyboss' ),
			esc_html__( 'Account Settings Notifications', 'buddyboss' ),
			6
		);

		$this->register_notification_for_password_change();
	}

	/**
	 * Register notification for user password change.
	 */
	public function register_notification_for_password_change() {

		$this->register_notification_type(
			'bb_notification_account_password',
			esc_html__( 'Your password is changed', 'buddyboss' ),
			esc_html__( 'A member\'s password is updated', 'buddyboss' ),
			'members'
		);

		$this->register_email_type(
			'settings-password-changed',
			array(
				/* translators: do not remove {} brackets or translate its contents. */
				'email_title'         => __( '[{{{site.name}}}] Your password was changed', 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_content'       => __( "Your password was changed on [{{{site.name}}}]. \n\n If you didn't make this change, please <a href=\"{{{reset.url}}}\">reset your password</a>.", 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_plain_content' => __( "Your password was changed on [{{{site.name}}}] \n\n If you didn't make this change, please reset your password: {{{reset.url}}}", 'buddyboss' ),
				'situation_label'     => __( 'A member\'s password is changed', 'buddyboss' ),
				'unsubscribe_text'    => __( 'You will no longer receive emails when your password is changed.', 'buddyboss' ),
			),
			'bb_notification_account_password'
		);

		$this->register_notification(
			'members',
			'update_member_password',
			'bb_notification_account_password'
		);

	}

	/**
	 * Format the notifications.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param int    $item_id               Notification item ID.
	 * @param int    $secondary_item_id     Notification secondary item ID.
	 * @param int    $action_item_count     Number of notifications with the same action.
	 * @param string $format                Format of return. Either 'string' or 'object'.
	 * @param string $component_action_name Canonical notification action.
	 * @param string $component_name        Notification component ID.
	 * @param int    $notification_id       Notification ID.
	 *
	 * @return array
	 */
	public function format_notification( $item_id, $secondary_item_id, $action_item_count, $format, $component_action_name, $component_name, $notification_id ) {
		return array();
	}
}
