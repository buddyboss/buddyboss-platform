<?php
/**
 * BuddyBoss Members Account Settings Notification Class.
 *
 * @package BuddyBoss\Activity
 *
 * @since BuddyBoss 1.9.3
 */

defined( 'ABSPATH' ) || exit;

/**
 * Set up the BP_Members_Notification class.
 *
 * @since BuddyBoss 1.9.3
 */
class BP_Members_Notification extends BP_Core_Notification_Abstract {

	/**
	 * Instance of this class.
	 *
	 * @since BuddyBoss 1.9.3
	 *
	 * @var object
	 */
	private static $instance = null;

	/**
	 * Get the instance of this class.
	 *
	 * @since BuddyBoss 1.9.3
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
	 * @since BuddyBoss 1.9.3
	 */
	public function __construct() {
		// Initialize.
		$this->start();
	}

	/**
	 * Initialize all methods inside it.
	 *
	 * @since BuddyBoss 1.9.3
	 *
	 * @return mixed|void
	 */
	public function load() {
		$this->register_notification_group(
			'members',
			esc_html__( 'Account Settings', 'buddyboss' ),
			esc_html__( 'Account Settings', 'buddyboss' ),
			6
		);

		$this->register_notification_for_password_change();
	}

	/**
	 * Register notification for user password change.
	 *
	 * @since BuddyBoss 1.9.3
	 */
	public function register_notification_for_password_change() {

		$this->register_notification_type(
			'bb_account_password',
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
			'bb_account_password'
		);

		$this->register_notification(
			'members',
			'bb_account_password',
			'bb_account_password',
			'bb-icon-f bb-icon-key'
		);

		$this->register_notification_filter(
			esc_html__( 'Password changed', 'buddyboss' ),
			array( 'bb_account_password' ),
			125
		);

	}

	/**
	 * Format the notifications.
	 *
	 * @since BuddyBoss 1.9.3
	 *
	 * @param string $content               Notification content.
	 * @param int    $item_id               Notification item ID.
	 * @param int    $secondary_item_id     Notification secondary item ID.
	 * @param int    $total_items           Number of notifications with the same action.
	 * @param string $component_action_name Canonical notification action.
	 * @param string $component_name        Notification component ID.
	 * @param int    $notification_id       Notification ID.
	 * @param string $screen                Notification Screen type.
	 *
	 * @return array
	 */
	public function format_notification( $content, $item_id, $secondary_item_id, $total_items, $component_action_name, $component_name, $notification_id, $screen ) {

		$notification = bp_notifications_get_notification( $notification_id );

		if ( 'members' === $component_name && 'bb_account_password' === $component_action_name ) {
			$amount = 'single';

			if ( 'web_push' === $screen ) {
				$settings_link = trailingslashit( bp_core_get_user_domain( $notification->user_id ) . bp_get_settings_slug() );
				$settings_link = add_query_arg( 'rid', (int) $notification->id, $settings_link );
				$text          = __( 'Your password was changed. If you didn\'t make this change, please reset your password.', 'buddyboss' );
			} else {
				$settings_link = trailingslashit( bp_loggedin_user_domain() . bp_get_settings_slug() );
				$settings_link = add_query_arg( 'rid', (int) $notification_id, $settings_link );

				// Set up the string and the filter.
				if ( (int) $total_items > 1 ) {
					$text   = sprintf( __( '%d Your password was changed', 'buddyboss' ), (int) $total_items );
					$amount = 'multiple';
				} else {
					$text = __( 'Your password was changed', 'buddyboss' );
				}
			}

			return apply_filters(
				'bb_members_' . $amount . '_' . $component_action_name . '_notification',
				array(
					'link'  => $settings_link,
					'text'  => $text,
					'title' => bp_get_site_name(),
					'image' => bb_notification_avatar_url( $notification ),
				),
				$notification,
				$text,
				$settings_link,
				$screen
			);
		}

		return $content;
	}

}
