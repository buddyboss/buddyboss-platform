<?php
/**
 * BuddyBoss Members Mentions Notification Class.
 *
 * @package BuddyBoss\Core
 *
 * @since BuddyBoss 1.9.3
 */

defined( 'ABSPATH' ) || exit;

/**
 * Set up the BP_Mentions_Notification class.
 *
 * @since BuddyBoss 1.9.3
 */
class BP_Members_Mentions_Notification extends BP_Core_Notification_Abstract {

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
			'mentions',
			esc_html__( 'Mentions', 'buddyboss' ),
			esc_html__( 'Mentions', 'buddyboss' ),
			3
		);

		$this->register_notification_for_mentions();
	}

	/**
	 * Register notification for user mention.
	 *
	 * @since BuddyBoss 1.9.3
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
				'email_title'         => __( '[{{{site.name}}}] {{poster.name}} mentioned you in a {{mentioned.type}}', 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_content'       => __( "<a href=\"{{{poster.url}}}\">{{poster.name}}</a> mentioned you in a {{mentioned.type}}:\n\n{{{mentioned.content}}}", 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_plain_content' => __( "{{poster.name}} mentioned you in a {{mentioned.type}}:\n\n{{{mentioned.content}}}\n\nView the {{mentioned.type}}: {{{mentioned.url}}}", 'buddyboss' ),
				'situation_label'     => __( 'A member is mentioned by another member', 'buddyboss' ),
				'unsubscribe_text'    => __( 'You will no longer receive emails when you are mentioned.', 'buddyboss' ),
			),
			'bb_new_mention'
		);

		$this->register_email_type(
			'new-mention-group',
			array(
				/* translators: do not remove {} brackets or translate its contents. */
				'email_title'         => __( '[{{{site.name}}}] {{poster.name}} mentioned you in a group {{mentioned.type}}', 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_content'       => __( "<a href=\"{{{poster.url}}}\">{{poster.name}}</a> mentioned you in a {{mentioned.type}} in the <b>{{group.name}}</b> group:\n\n{{{mentioned.content}}}", 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_plain_content' => __( "{{poster.name}} mentioned you in a {{mentioned.type}} in the {{group.name}} group:\n\n{{{mentioned.content}}}\n\nView the {{mentioned.type}}: {{{mentioned.url}}}", 'buddyboss' ),
				'situation_label'     => __( 'A member is mentioned in a group', 'buddyboss' ),
				'unsubscribe_text'    => __( 'You will no longer receive emails when you are mentioned.', 'buddyboss' ),
			),
			'bb_new_mention'
		);

		$this->register_notification(
			'members',
			'bb_new_mention',
			'bb_new_mention',
			'bb-icon-f bb-icon-activity'
		);

		$this->register_notification_filter(
			esc_html__( 'New mentions', 'buddyboss' ),
			array( 'bb_new_mention' ),
			10
		);

		add_filter( 'bp_forums_bb_new_mention_notification', array( $this, 'bb_render_mention_notification' ), 10, 7 );
		add_filter( 'bp_activity_bb_new_mention_notification', array( $this, 'bb_render_mention_notification' ), 10, 7 );
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
	 * @return array|string
	 */
	public function format_notification( $content, $item_id, $secondary_item_id, $total_items, $component_action_name, $component_name, $notification_id, $screen ) {

		$notification = bp_notifications_get_notification( $notification_id );

		if (
			! empty( $notification ) &&
			'bb_new_mention' === $notification->component_action &&
			in_array( $notification->component_name, array( 'core' ), true )
		) {
			$comment                = get_comment( $notification->item_id );
			$notification_type_html = esc_html__( 'comment', 'buddyboss' );
			$notification_link      = bp_get_notifications_permalink();
			$commenter_name         = '';

			if ( ! empty( $comment ) ) {
				$comment_author    = get_user_by( 'email', $comment->comment_author_email );
				$notification_link = get_comment_link( $comment );
				$commenter_name    = ! empty( $comment_author ) ? bp_core_get_user_displayname( $comment_author->ID ) : $comment->comment_author;
			}

			$notification_link = add_query_arg( 'cid', (int) $notification_id, $notification_link );
			$amount            = 'single';

			if ( 'web_push' === $screen ) {
				if ( ! empty( $notification_type_html ) ) {
					$text = sprintf(
					/* translators: Activity type. */
						__( 'Mentioned you in a %s', 'buddyboss' ),
						$notification_type_html
					);
				} else {
					$text = __( 'Mentioned you', 'buddyboss' );
				}
			} else {
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
							esc_html__( '%1$s mentioned you in a %2$s', 'buddyboss' ),
							$commenter_name,
							$notification_type_html
						);
					} else {
						$text = sprintf(
						/* translators: %s: User full name. */
							esc_html__( '%1$s mentioned you', 'buddyboss' ),
							$commenter_name
						);
					}
				}
			}

			$content = apply_filters(
				'bb_core_' . $amount . '_' . $notification->component_action . '_notification',
				array(
					'link'  => $notification_link,
					'text'  => $text,
					'title' => $commenter_name,
					'image' => bb_notification_avatar_url( $notification ),
				),
				$notification,
				$notification_link,
				$text,
				$screen
			);
		}

		return $content;
	}

	/**
	 * Format the notifications.
	 *
	 * @since BuddyBoss 1.9.3
	 *
	 * @param string $content           Notification content.
	 * @param int    $item_id           Notification item ID.
	 * @param int    $secondary_item_id Notification secondary item ID.
	 * @param int    $total_items       Number of notifications with the same action.
	 * @param string $format            Format of return. Either 'string' or 'object'.
	 * @param int    $notification_id   Notification ID.
	 * @param string $screen            Notification Screen type.
	 *
	 * @return array|string
	 */
	public function bb_render_mention_notification( $content, $item_id, $secondary_item_id, $total_items, $format, $notification_id, $screen ) {
		$notification           = bp_notifications_get_notification( $notification_id );
		$user_id                = $secondary_item_id;
		$user_fullname          = bp_core_get_user_displayname( $user_id );
		$notification_type_html = '';

		if (
			! empty( $notification ) &&
			'bb_new_mention' === $notification->component_action &&
			in_array( $notification->component_name, array( 'activity', 'forums', 'members' ), true )
		) {

			$notification_type = bp_notifications_get_meta( $notification_id, 'type', true );
			$notification_link = trailingslashit( bp_core_get_user_domain( $user_id ) );

			if ( $notification_type ) {
				if ( 'post_comment' === $notification_type ) {
					$notification_type_html = esc_html__( 'comment', 'buddyboss' );

					if ( bp_is_active( 'activity' ) ) {
						$notification_link = bp_activity_get_permalink( $item_id );
						$notification_link = apply_filters( 'bp_activity_new_at_mention_permalink', $notification_link, $item_id, $secondary_item_id, $total_items );
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

			$amount = 'single';

			if ( 'web_push' === $screen ) {
				if ( ! empty( $notification_type_html ) ) {
					$text = sprintf(
						/* translators: Activity type. */
						__( 'Mentioned you in a %s', 'buddyboss' ),
						$notification_type_html
					);
				} else {
					$text = __( 'Mentioned you', 'buddyboss' );
				}
			} else {
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
							esc_html__( '%1$s mentioned you in a %2$s', 'buddyboss' ),
							$user_fullname,
							$notification_type_html
						);
					} else {
						$text = sprintf(
						/* translators: %s: User full name. */
							esc_html__( '%1$s mentioned you', 'buddyboss' ),
							$user_fullname
						);
					}
				}
			}

			$notification_link = add_query_arg( 'rid', (int) $notification_id, $notification_link );

			$content = apply_filters(
				'bb_members_' . $amount . '_' . $notification->component_action . '_notification',
				array(
					'link'  => $notification_link,
					'text'  => $text,
					'title' => $user_fullname,
					'image' => bb_notification_avatar_url( $notification ),
				),
				$notification,
				$notification_link,
				$text,
				$screen
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
					'text'  => $content['text'],
					'link'  => $content['link'],
					'title' => ( isset( $content['title'] ) ? $content['title'] : '' ),
					'image' => ( isset( $content['image'] ) ? $content['image'] : '' ),
				);
			}
		}

		return $content;
	}

}
