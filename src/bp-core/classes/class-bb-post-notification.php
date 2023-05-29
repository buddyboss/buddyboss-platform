<?php
/**
 * BuddyBoss Post Notification Class.
 *
 * @package BuddyBoss\Core
 *
 * @since BuddyBoss [BBVERSION]
 */

defined( 'ABSPATH' ) || exit;

/**
 * Set up the BB_Post_Notification class.
 *
 * @since BuddyBoss [BBVERSION]
 */
class BB_Post_Notification extends BP_Core_Notification_Abstract {

	/**
	 * Instance of this class.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var object
	 */
	private static $instance = null;

	/**
	 * Get the instance of this class.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return null|BB_Post_Notification|Controller|object
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
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return mixed|void
	 */
	public function load() {
		$this->register_notification_group(
			'posts',
			esc_html__( 'Posts', 'buddyboss' ),
			esc_html__( 'Posts', 'buddyboss' ),
			5
		);

		$this->register_notification_for_post_comment_reply();
	}

	/**
	 * Register notification for posts.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function register_notification_for_post_comment_reply() {

		$default                 = false;
		$bb_enabled_notification = bp_get_option( 'bb_enabled_notification', false );
		if (
			false !== get_transient( '_bp_is_new_install' ) ||
			(
				empty( $bb_enabled_notification ) &&
				true === (bool) bp_get_option( 'bb_posts_new_comment_reply_default_setting', false )
			)
		) {
			$default = true;
			bp_update_option( 'bb_posts_new_comment_reply_default_setting', true );
		}

		$this->register_notification_type(
			'bb_posts_new_comment_reply',
			esc_html__( 'A member replies to your post comment', 'buddyboss' ),
			esc_html__( 'A member receives a reply to their WordPress post comment', 'buddyboss' ),
			'posts',
			$default
		);

		$this->register_email_type(
			'new-comment-reply',
			array(
				/* translators: do not remove {} brackets or translate its contents. */
				'email_title'         => __( '[{{{site.name}}}] {{commenter.name}} replied to your comment', 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_content'       => __( "<a href=\"{{{commenter.url}}}\">{{commenter.name}}</a> replied to your comment:\n\n{{{comment_reply}}}", 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_plain_content' => __( "{{commenter.name}} replied to your comment:\n\n{{{comment_reply}}}\n\nView the comment: {{{comment.url}}}", 'buddyboss' ),
				'situation_label'     => __( 'A member receives a reply to their WordPress post comment', 'buddyboss' ),
				'unsubscribe_text'    => __( 'You will no longer receive emails when someone replies to your comments.', 'buddyboss' ),

			),
			'bb_posts_new_comment_reply'
		);

		$this->register_notification(
			'core',
			'bb_posts_new_comment_reply',
			'bb_posts_new_comment_reply',
			'bb-icon-f bb-icon-comment',
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
		if ( ! empty( $notification ) && 'bb_posts_new_comment_reply' === $notification->component_action &&
			in_array( $notification->component_name, array( 'core' ), true ) ) {
			$comment           = get_comment( $notification->item_id );
			$excerpt           = wp_strip_all_tags( $comment->comment_content );
			$notification_link = add_query_arg( 'cid', (int) $notification_id, get_comment_link( $comment ) );
			$comment_author    = get_user_by( 'email', $comment->comment_author_email );
			$commenter_name    = ! empty( $comment_author ) ? bp_core_get_user_displayname( $comment_author->ID ) : $comment->comment_author;

			if ( '&nbsp;' === $excerpt ) {
				$excerpt = '';
			} else {
				$excerpt = '"' . bp_create_excerpt(
					$excerpt,
					50,
					array(
						'ending' => __( '&hellip;', 'buddyboss' ),
					)
				) . '"';

				$excerpt = str_replace( '&hellip;"', '&hellip;', $excerpt );
				$excerpt = str_replace( '""', '', $excerpt );
			}

			$amount = 'single';

			if ( 'web_push' === $screen ) {
				if ( ! empty( $excerpt ) ) {
					$text = sprintf(
						/* translators: Excerpt. */
						__( 'Replied to your comment: %s', 'buddyboss' ),
						$excerpt
					);
				} else {
					$text = __( 'Replied to your comment', 'buddyboss' );
				}
			} else {
				if ( (int) $total_items > 1 ) {
					$text   = sprintf(
					/* translators: %s: Total new comment reply count. */
						__( 'You have %1$d new comment reply', 'buddyboss' ),
						(int) $total_items
					);
					$amount = 'multiple';
				} else {
					if ( ! empty( $excerpt ) ) {
						$text = sprintf(
						/* translators: 1: Commenter name, 2: Excerpt. */
							esc_html__( '%1$s replied to your comment: %2$s', 'buddyboss' ),
							$commenter_name,
							$excerpt
						);
					} else {
						$text = sprintf(
						/* translators: %s: Commenter name. */
							esc_html__( '%1$s replied to your comment', 'buddyboss' ),
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
}
