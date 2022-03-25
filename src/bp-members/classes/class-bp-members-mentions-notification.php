<?php
/**
 * BuddyBoss Members Mentions Notification Class.
 *
 * @package BuddyBoss\Core
 *
 * @since BuddyBoss [BBVERSION]
 */

defined( 'ABSPATH' ) || exit;

/**
 * Set up the BP_Mentions_Notification class.
 *
 * @since BuddyBoss [BBVERSION]
 */
class BP_Members_Mentions_Notification extends BP_Core_Notification_Abstract {

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
	 * @return null|BP_Members_Mentions_Notification|Controller|object
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
			'mentions',
			esc_html__( 'Mentions', 'buddyboss' ),
			esc_html__( 'Mentions Notifications', 'buddyboss' ),
			3
		);

		$this->register_notification_for_mentions();
	}

	/**
	 * Register notification for user mention.
	 */
	public function register_notification_for_mentions() {
		$this->register_notification_type(
			'bb_new_mention',
			sprintf(
				/* translators: %s: users mention name. */
				__( 'A member mentions you using "@%s"', 'buddyboss' ),
				bb_members_get_user_mentionname( get_current_user_id() )
			),
			esc_html__( 'A member is mentioned by another member', 'buddyboss' ),
			'mentions'
		);

		$this->register_email_type(
			'new-mention',
			array(
				/* translators: do not remove {} brackets or translate its contents. */
				'email_title'         => __( '[{{{site.name}}}] {{poster.name}} mentioned you in a status update', 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_content'       => __( "<a href=\"{{{poster.url}}}\">{{poster.name}}</a> mentioned you in a status update:\n\n{{{status_update}}}", 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_plain_content' => __( "{{poster.name}} mentioned you in a status update:\n\n{{{status_update}}}\n\nGo to the discussion to reply or catch up on the conversation: {{{mentioned.url}}}", 'buddyboss' ),
				'situation_label'     => __( 'A member is mentioned by another member', 'buddyboss' ),
				'unsubscribe_text'    => __( 'You will no longer receive emails when you are mentioned.', 'buddyboss' ),
			),
			'bb_new_mention'
		);

		$this->register_email_type(
			'new-mention-group',
			array(
				/* translators: do not remove {} brackets or translate its contents. */
				'email_title'         => __( '[{{{site.name}}}] {{poster.name}} mentioned you in a group update', 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_content'       => __( "<a href=\"{{{poster.url}}}\">{{poster.name}}</a> mentioned you in the group \"<a href=\"{{{group.url}}}\">{{group.name}}</a>\":\n\n{{{status_update}}}", 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_plain_content' => __( "{{poster.name}} mentioned you in the group \"{{group.name}}\":\n\n{{{status_update}}}\n\nGo to the discussion to reply or catch up on the conversation: {{{mentioned.url}}}", 'buddyboss' ),
				'situation_label'     => __( 'A member is mentioned in a group', 'buddyboss' ),
				'unsubscribe_text'    => __( 'You will no longer receive emails when you are mentioned.', 'buddyboss' ),
			),
			'bb_new_mention'
		);

		$this->register_notification(
			'members',
			'bb_new_mention',
			'bb_new_mention',
			true,
			__( 'New mentions', 'buddyboss' ),
			5
		);
	}

	/**
	 * Format the notifications.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $content               Notification content.
	 * @param int    $item_id               Notification item ID.
	 * @param int    $secondary_item_id     Notification secondary item ID.
	 * @param int    $action_item_count     Number of notifications with the same action.
	 * @param string $format                Format of return. Either 'string' or 'object'.
	 * @param string $component_action_name Canonical notification action.
	 * @param string $component_name        Notification component ID.
	 * @param int    $notification_id       Notification ID.
	 * @param string $screen                Notification Screen type.
	 *
	 * @return array|string
	 */
	public function format_notification( $content, $item_id, $secondary_item_id, $action_item_count, $format, $component_action_name, $component_name, $notification_id, $screen ) {

		$notification           = bp_notifications_get_notification( $notification_id );
		$user_id                = $secondary_item_id;
		$user_fullname          = bp_core_get_user_displayname( $user_id );
		$notification_type_html = '';

		if ( ! empty( $notification ) && 'bb_new_mention' === $component_action_name && in_array( $component_name, array( 'activity', 'forums' ), true ) ) {

			$notification_type = bp_notifications_get_meta( $notification_id, 'type', true );
			$notification_link = trailingslashit( bp_core_get_user_domain( $user_id ) );

			if ( $notification_type ) {
				if ( 'post_comment' === $notification_type ) {
					$notification_type_html = esc_html__( 'comment', 'buddyboss' );

					if ( bp_is_active( 'activity' ) ) {
						$notification_link = bp_activity_get_permalink( $item_id );
						$notification_link = apply_filters( 'bp_activity_new_at_mention_permalink', $notification_link, $item_id, $secondary_item_id, $action_item_count );
					}
				} elseif ( 'activity_comment' === $notification_type || 'activity_post' === $notification_type ) {
					$notification_type_html = esc_html__( 'post', 'buddyboss' );
					if ( bp_is_active( 'activity' ) ) {
						$notification_link = bp_activity_get_permalink( $item_id );
					}
				} elseif ( 'forum_reply' === $notification_type || 'forum_topic' === $notification_type ) {
					$notification_type_html = esc_html__( 'discussion', 'buddyboss' );

					if ( bp_is_active( 'forums' ) ) {
						$notification_link = bbp_get_reply_url( $item_id );
					}
				}
			}

			if ( (int) $action_item_count > 1 ) {
				$text = sprintf(
				/* translators: %s: Total mentioned count. */
					__( 'You have %1$d new mentions', 'buddyboss' ),
					(int) $action_item_count
				);
				$amount = 'multiple';
			} else {
				$amount = 'single';
				if ( ! empty( $notification_type_html ) ) {
					$text = sprintf(
						/* translators: 1: User full name, 2: Activity type. */
						__( '%1$s mentioned you in %2$s', 'buddyboss' ),
						$user_fullname,
						$notification_type_html
					);
				} else {
					$text = sprintf(
					/* translators: %s: User full name. */
						__( '%1$s mentioned you', 'buddyboss' ),
						$user_fullname
					);
				}
			}

			$notification_link = add_query_arg( 'rid', (int) $notification_id, $notification_link );

			$content = apply_filters(
				'bb_members_' . $action_item_count . '_' . $component_action_name . '_notification',
				array(
					'link' => $notification_link,
					'text' => $text,
				),
				$notification,
				$notification_link,
				$text
			);

		}

		return $content;
	}
}
