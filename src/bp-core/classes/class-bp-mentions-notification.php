<?php
/**
 * BuddyBoss Mentions Notification Class.
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
class BP_Mentions_Notification extends BP_Core_Notification_Abstract {

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
	 * @return null|BP_Mentions_Notification|Controller|object
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
				bp_activity_get_user_mentionname( get_current_user_id() )
			),
			esc_html__( 'A member is mentioned by another member', 'buddyboss' ),
			'mentions'
		);

		$this->register_email_type(
			'activity-at-message',
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
			'groups-at-message',
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
			'mentions',
			'bb_new_mention',
			'bb_new_mention',
			true,
			__( 'New mentions', 'buddyboss' ),
			5
		);

		add_filter( 'bp_activity_bb_new_mention_notification', array( $this, 'bb_activity_format_notification' ), 10, 7 );
		add_filter( 'bp_activity_bb_activity_comment_notification', array( $this, 'bb_activity_format_notification' ), 10, 7 );

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
		return $content;
	}

	/**
	 * Format Activity notifications.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $content               Notification content.
	 * @param int    $item_id               Notification item ID.
	 * @param int    $secondary_item_id     Notification secondary item ID.
	 * @param int    $total_items           Number of notifications with the same action.
	 * @param string $format                Format of return. Either 'string' or 'object'.
	 * @param int    $notification_id       Notification ID.
	 * @param string $screen                Notification Screen type.
	 *
	 * @return array
	 */
	public function bb_activity_format_notification( $content, $item_id, $secondary_item_id, $total_items, $format, $notification_id, $screen ) {

		$notification = bp_notifications_get_notification( $notification_id );

		if ( ! empty( $notification ) && 'activity' === $notification->component_name ) {

			$action                 = $notification->component_action;
			$activity_id            = $item_id;
			$user_id                = $secondary_item_id;
			$user_fullname          = bp_core_get_user_displayname( $user_id );
			$notification_type_html = '';

			$notification_type = bp_notifications_get_meta( $notification_id, 'type', true );


			// Get activity by activity ID.
			$activity         = new BP_Activity_Activity( $activity_id );
			$activity_excerpt = bp_create_excerpt(
				wp_strip_all_tags( $activity->content ),
				50,
				array(
					'ending' => __( '&hellip;', 'buddyboss' ),
				)
			);

			if ( '&nbsp;' === $activity_excerpt ) {
				$activity_excerpt = '';
			}

			switch ( $action ) {
				case 'bb_new_mention':
					$link  = bp_activity_get_permalink( $item_id );
					$title = sprintf(
					/* translators: %s: The user full name. */
						__( '@%s Mentions', 'buddyboss' ),
						bp_get_loggedin_user_username()
					);
					$amount = 'single';

					if ( $notification_type ) {
						$notification_type_html = esc_html__( 'activity post', 'buddyboss' );
						if ( 'post_comment' === $notification_type ) {
							$notification_type_html = esc_html__( 'post comment', 'buddyboss' );
						} elseif ( 'activity_comment' === $notification_type ) {
							$notification_type_html = esc_html__( 'activity comment', 'buddyboss' );
						}
					}

					/**
					 * Filters the mention notification permalink.
					 *
					 * The two possible hooks are bp_activity_new_at_mention_permalink
					 * or activity_get_notification_permalink.
					 *
					 * @since BuddyBoss 1.2.5
					 *
					 * @param string $link          HTML anchor tag for the interaction.
					 * @param int    $item_id            The permalink for the interaction.
					 * @param int    $secondary_item_id     How many items being notified about.
					 * @param int    $total_items     ID of the activity item being formatted.
					 */
					$link = apply_filters( 'bp_activity_new_at_mention_permalink', $link, $item_id, $secondary_item_id, $total_items );

					if ( (int) $total_items > 1 ) {
						$text = sprintf(
							/* translators: %s: Total mentioned count. */
							__( 'You have %1$d new mentions', 'buddyboss' ),
							(int) $total_items
						);
						$amount = 'multiple';
					} else {

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

					break;

				case 'bb_activity_comment':
					$link   = bp_get_notifications_permalink();
					$title  = __( 'New Activity reply', 'buddyboss' );
					$amount = 'single';

					if ( $notification_type ) {
						$notification_type_html = esc_html__( 'post', 'buddyboss' );
						if ( 'activity_comment' === $notification_type ) {
							$notification_type_html = esc_html__( 'comment', 'buddyboss' );
						}
					}

					if ( (int) $total_items > 1 ) {
						$link = add_query_arg( 'type', $action, $link );
						$text = sprintf(
						/* translators: %s: Total reply count. */
							__( 'You have %1$d new replies', 'buddyboss' ),
							(int) $total_items
						);
						$amount = 'multiple';
					} else {
						$link = add_query_arg( 'rid', (int) $notification_id, bp_activity_get_permalink( $activity_id ) );

						if ( ! empty( $notification_type_html ) ) {
							if ( ! empty( $activity_excerpt ) ) {
								$text = sprintf(
								/* translators: 1: User full name, 2: Activity type, 3: Activity content. */
									__( '%1$s replied to your %2$s: "%3$s"', 'buddyboss' ),
									$user_fullname,
									$notification_type_html,
									$activity_excerpt
								);
							} else {
								$text = sprintf(
								/* translators: 1: User full name, 2: Activity type. */
									__( '%1$s replied to your %2$s', 'buddyboss' ),
									$user_fullname,
									$notification_type_html
								);
							}
						} else {
							if ( ! empty( $activity_excerpt ) ) {
								$text = sprintf(
								/* translators: 1: User full name, 2: Activity content. */
									__( '%1$s replied: "%2$s"', 'buddyboss' ),
									$user_fullname,
									$activity_excerpt
								);
							} else {
								$text = sprintf(
								/* translators: %s: User full name. */
									__( '%1$s replied', 'buddyboss' ),
									$user_fullname
								);
							}
						}
					}
					break;

			}

			$content = apply_filters(
				'bb_activity_' . $amount . '_' . $action . '_notification',
				array(
					'link' => $link,
					'text' => $text,
				),
				$link,
				$title,
				$text,
				$link
			);
		}

		// Validate the return value & return if validated.
		if (
			! empty( $content ) &&
			is_array( $content ) &&
			isset( $content['text'] ) &&
			isset( $content['link'] )
		) {
			if ( 'string' === $format ) {
				if ( empty( $content['link'] ) ) {
					$content = esc_html( $content['text'] );
				} else {
					$content = '<a href="' . esc_url( $content['link'] ) . '">' . esc_html( $content['text'] ) . '</a>';
				}
			} else {
				$content = array(
					'text' => $content['text'],
					'link' => $content['link'],
				);
			}
		}

		return $content;
	}
}
