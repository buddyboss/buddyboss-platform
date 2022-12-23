<?php
/**
 * BuddyBoss Forums Notification Class.
 *
 * @package BuddyBoss\Forums
 *
 * @since BuddyBoss 1.9.3
 */

defined( 'ABSPATH' ) || exit;

/**
 * Set up the BP_Forums_Notification class.
 *
 * @since BuddyBoss 1.9.3
 */
class BP_Forums_Notification extends BP_Core_Notification_Abstract {

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
	 * @return null|BP_Forums_Notification|Controller|object
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
			'forums',
			esc_html__( 'Discussion Forums', 'buddyboss' ),
			esc_html__( 'Discussion Forums', 'buddyboss' ),
			15
		);

		// Creates discussion in a forum you are subscribed.
		$this->register_notification_for_forums_following_topic();

		// Replies to a discussion you are subscribed.
		$this->register_notification_for_forums_following_reply();

		$this->register_notification_filter(
			esc_html__( 'Forum subscriptions', 'buddyboss' ),
			array( 'bb_forums_subscribed_discussion', 'bb_forums_subscribed_reply' ),
			110
		);
	}

	/**
	 * Register notification for replies to a discussion you are subscribed.
	 *
	 * @since BuddyBoss 1.9.3
	 */
	public function register_notification_for_forums_following_reply() {
		$this->register_notification_type(
			'bb_forums_subscribed_reply',
			__( 'New reply in a discussion you\'re subscribed to', 'buddyboss' ),
			esc_html__( 'A new reply in a discussion a member is subscribed to', 'buddyboss' ),
			'forums'
		);

		$this->register_email_type(
			'bbp-new-forum-reply',
			array(
				/* translators: do not remove {} brackets or translate its contents. */
				'email_title'         => __( '[{{{site.name}}}] {{poster.name}} replied to one of your forum discussions', 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_content'       => __( "{{poster.name}} replied to the discussion <a href=\"{{discussion.url}}\">{{discussion.title}}</a> in the forum <a href=\"{{forum.url}}\">{{forum.title}}</a>:\n\n{{{reply.content}}}", 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_plain_content' => __( "{{poster.name}} replied to the discussion {{discussion.title}} in the forum {{forum.title}}:\n\n{{{reply.content}}}\n\nPost Link: {{reply.url}}", 'buddyboss' ),
				'situation_label'     => __( 'A new reply in a discussion a member is subscribed to', 'buddyboss' ),
				'unsubscribe_text'    => __( 'You will no longer receive emails when a member will reply to one of your forum discussions.', 'buddyboss' ),
			),
			'bb_forums_subscribed_reply'
		);

		$this->register_notification(
			'forums',
			'bb_forums_subscribed_reply',
			'bb_forums_subscribed_reply',
			'bb-icon-f bb-icon-reply'
		);

	}

	/**
	 * Register notification for creates discussion in a forum you are subscribed.
	 *
	 * @since BuddyBoss 1.9.3
	 */
	public function register_notification_for_forums_following_topic() {
		$this->register_notification_type(
			'bb_forums_subscribed_discussion',
			__( 'New discussion in a forum you\'re subscribed to', 'buddyboss' ),
			esc_html__( 'A new discussion in a forum a member is subscribed to', 'buddyboss' ),
			'forums'
		);

		$this->register_email_type(
			'bbp-new-forum-topic',
			array(
				/* translators: do not remove {} brackets or translate its contents. */
				'email_title'         => __( '[{{{site.name}}}] New discussion: {{discussion.title}}', 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_content'       => __( "{{poster.name}} started a new discussion <a href=\"{{discussion.url}}\">{{discussion.title}}</a> in the forum <a href=\"{{forum.url}}\">{{forum.title}}</a>:\n\n{{{discussion.content}}}", 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_plain_content' => __( "{{poster.name}} started a new discussion {{discussion.title}} in the forum {{forum.title}}:\n\n{{{discussion.content}}}\n\nDiscussion Link: {{discussion.url}}", 'buddyboss' ),
				'situation_label'     => __( 'A new discussion in a forum a member is subscribed to', 'buddyboss' ),
				'unsubscribe_text'    => __( 'You will no longer receive emails when a member will create a new forum discussion.', 'buddyboss' ),
			),
			'bb_forums_subscribed_discussion'
		);

		$this->register_notification(
			'forums',
			'bb_forums_subscribed_discussion',
			'bb_forums_subscribed_discussion',
			'bb-icon-f bb-icon-comment-square-dots'
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

		if ( 'forums' === $component_name && 'bb_forums_subscribed_reply' === $component_action_name ) {
			$notification = bp_notifications_get_notification( $notification_id );
			$topic_id     = bbp_get_reply_topic_id( $item_id );
			$topic_title  = bbp_get_topic_title( $topic_id );
			$topic_link   = wp_nonce_url(
				add_query_arg(
					array(
						'action'   => 'bbp_mark_read',
						'topic_id' => $topic_id,
						'reply_id' => $item_id,
					),
					bbp_get_reply_url( $item_id )
				),
				'bbp_mark_topic_' . $topic_id
			);

			$except = '"' . bbp_get_reply_excerpt( $item_id, 50 ) . '"';
			$except = str_replace( '&hellip;"', '&hellip;', $except );
			$except = str_replace( '""', '', $except );

			$media_ids    = get_post_meta( $item_id, 'bp_media_ids', true );
			$document_ids = get_post_meta( $item_id, 'bp_document_ids', true );
			$video_ids    = get_post_meta( $item_id, 'bp_video_ids', true );
			$gif_data     = get_post_meta( $item_id, '_gif_data', true );

			$title = bp_get_site_name();

			if ( 'web_push' === $screen ) {
				if ( ! empty( $notification->secondary_item_id ) ) {
					if ( ! empty( $except ) ) {
						$text = sprintf(
						/* translators: 1. Member display name. 2. excerpt. */
							__( '%1$s replied to a discussion: %2$s', 'buddyboss' ),
							bp_core_get_user_displayname( $notification->secondary_item_id ),
							$except
						);
					} elseif ( ! empty( $media_ids ) ) {
						$media_ids = array_filter( explode( ',', $media_ids ) );
						if ( count( $media_ids ) > 1 ) {
							$text = sprintf(
							/* translators: Member display name. */
								esc_html__( '%s replied to a discussion: some photos', 'buddyboss' ),
								bp_core_get_user_displayname( $notification->secondary_item_id )
							);
						} else {
							$text = sprintf(
							/* translators: Member display name. */
								esc_html__( '%s replied to a discussion: a photo', 'buddyboss' ),
								bp_core_get_user_displayname( $notification->secondary_item_id )
							);
						}
					} elseif ( ! empty( $document_ids ) ) {
						$document_ids = array_filter( explode( ',', $document_ids ) );
						if ( count( $document_ids ) > 1 ) {
							$text = sprintf(
							/* translators: Member display name. */
								esc_html__( '%s replied to a discussion: some documents', 'buddyboss' ),
								bp_core_get_user_displayname( $notification->secondary_item_id )
							);
						} else {
							$text = sprintf(
							/* translators: Member display name. */
								esc_html__( '%s replied to a discussion: a document', 'buddyboss' ),
								bp_core_get_user_displayname( $notification->secondary_item_id )
							);
						}
					} elseif ( ! empty( $video_ids ) ) {
						$video_ids = array_filter( explode( ',', $video_ids ) );
						if ( count( $video_ids ) > 1 ) {
							$text = sprintf(
							/* translators: Member display name. */
								esc_html__( '%s replied to a discussion: some videos', 'buddyboss' ),
								bp_core_get_user_displayname( $notification->secondary_item_id )
							);
						} else {
							$text = sprintf(
							/* translators: Member display name. */
								esc_html__( '%s replied to a discussion: a video', 'buddyboss' ),
								bp_core_get_user_displayname( $notification->secondary_item_id )
							);
						}
					} elseif ( ! empty( $gif_data ) ) {
						$text = sprintf(
						/* translators: Member display name. */
							esc_html__( '%s replied to a discussion: a gif', 'buddyboss' ),
							bp_core_get_user_displayname( $notification->secondary_item_id )
						);
					} else {
						$text = sprintf(
						/* translators: Member display name. */
							__( '%s replied to a discussion', 'buddyboss' ),
							bp_core_get_user_displayname( $notification->secondary_item_id )
						);
					}
				} else {
					$text = sprintf(
					/* translators: topic title. */
						__( 'You have a new reply to %s', 'buddyboss' ),
						$topic_title
					);
				}

				$forum_id  = bbp_get_topic_forum_id( $topic_id );
				$group_ids = bbp_get_forum_group_ids( $forum_id );

				if ( bp_is_active( 'groups' ) && ! empty( $group_ids ) ) {
					$title = bp_get_group_name( groups_get_group( current( $group_ids ) ) );
				}
			} else {
				if ( (int) $total_items > 1 ) {
					$text = sprintf(
					/* translators: replies count. */
						esc_html__( 'You have %d new replies', 'buddyboss' ),
						(int) $total_items
					);
				} else {
					if ( ! empty( $secondary_item_id ) ) {
						if ( ! empty( $except ) ) {
							$text = sprintf(
								/* translators: 1. Member display name. 2. excerpt. */
								esc_html__( '%1$s replied to a discussion: %2$s', 'buddyboss' ),
								bp_core_get_user_displayname( $secondary_item_id ),
								$except
							);
						} elseif ( $media_ids ) {
							$media_ids = array_filter( explode( ',', $media_ids ) );
							if ( count( $media_ids ) > 1 ) {
								$text = sprintf(
								/* translators: Member display name. */
									esc_html__( '%s replied to a discussion: some photos', 'buddyboss' ),
									bp_core_get_user_displayname( $secondary_item_id )
								);
							} else {
								$text = sprintf(
								/* translators: Member display name. */
									esc_html__( '%s replied to a discussion: a photo', 'buddyboss' ),
									bp_core_get_user_displayname( $secondary_item_id )
								);
							}
						} elseif ( $document_ids ) {
							$document_ids = array_filter( explode( ',', $document_ids ) );
							if ( count( $document_ids ) > 1 ) {
								$text = sprintf(
								/* translators: Member display name. */
									esc_html__( '%s replied to a discussion: some documents', 'buddyboss' ),
									bp_core_get_user_displayname( $secondary_item_id )
								);
							} else {
								$text = sprintf(
								/* translators: Member display name. */
									esc_html__( '%s replied to a discussion: a document', 'buddyboss' ),
									bp_core_get_user_displayname( $secondary_item_id )
								);
							}
						} elseif ( $video_ids ) {
							$video_ids = array_filter( explode( ',', $video_ids ) );
							if ( count( $video_ids ) > 1 ) {
								$text = sprintf(
								/* translators: Member display name. */
									esc_html__( '%s replied to a discussion: some videos', 'buddyboss' ),
									bp_core_get_user_displayname( $secondary_item_id )
								);
							} else {
								$text = sprintf(
								/* translators: Member display name. */
									esc_html__( '%s replied to a discussion: a video', 'buddyboss' ),
									bp_core_get_user_displayname( $secondary_item_id )
								);
							}
						} elseif ( ! empty( $gif_data ) ) {
							$text = sprintf(
							/* translators: Member display name. */
								esc_html__( '%s replied to a discussion: a gif', 'buddyboss' ),
								bp_core_get_user_displayname( $secondary_item_id )
							);
						} else {
							$text = sprintf(
							/* translators: Member display name. */
								esc_html__( '%s replied to a discussion', 'buddyboss' ),
								bp_core_get_user_displayname( $secondary_item_id )
							);
						}
					} else {
						$text = sprintf(
							/* translators: topic title. */
							esc_html__( 'You have a new reply to %s', 'buddyboss' ),
							$topic_title
						);
					}
				}
			}

			$content = array(
				'text'  => $text,
				'link'  => $topic_link,
				'title' => $title,
				'image' => bb_notification_avatar_url( $notification ),
			);
		}

		if ( 'forums' === $component_name && 'bb_forums_subscribed_discussion' === $component_action_name ) {
			$notification = bp_notifications_get_notification( $notification_id );
			$topic_id     = bbp_get_topic_id( $item_id );
			$topic_title  = '"' . bp_create_excerpt(
					wp_strip_all_tags( bbp_get_topic_title( $topic_id ) ),
					50,
					array(
						'ending' => __( '&hellip;', 'buddyboss' ),
					)
				) . '"';

			$topic_title = str_replace( '&hellip;"', '&hellip;', $topic_title );

			$topic_link = wp_nonce_url(
				add_query_arg(
					array(
						'action'   => 'bbp_mark_read',
						'topic_id' => $topic_id,
					),
					bbp_get_topic_permalink( $topic_id )
				),
				'bbp_mark_topic_' . $topic_id
			);

			$title = bp_get_site_name();

			if ( 'web_push' === $screen ) {
				if ( ! empty( $notification->secondary_item_id ) ) {
					$text = sprintf(
					/* translators: 1.Member display name 2. discussions title. */
						__( '%1$s started a discussion: %2$s', 'buddyboss' ),
						bp_core_get_user_displayname( $notification->secondary_item_id ),
						$topic_title
					);
				} else {
					$text = sprintf(
					/* translators: discussions title. */
						__( 'You have a new discussion: %s', 'buddyboss' ),
						$topic_title
					);
				}

				$forum_id  = bbp_get_topic_forum_id( $topic_id );
				$group_ids = bbp_get_forum_group_ids( $forum_id );

				if ( bp_is_active( 'groups' ) && ! empty( $group_ids ) ) {
					$title = bp_get_group_name( groups_get_group( current( $group_ids ) ) );
				}
			} else {
				if ( (int) $total_items > 1 ) {
					/* translators: discussions count. */
					$text = sprintf( __( 'You have %d new discussion', 'buddyboss' ), (int) $total_items );
				} else {

					if ( ! empty( $secondary_item_id ) ) {
						$text = sprintf(
						/* translators: 1.Member display name 2. discussions title. */
							esc_html__( '%1$s started a discussion: %2$s', 'buddyboss' ),
							bp_core_get_user_displayname( $secondary_item_id ),
							$topic_title
						);
					} else {
						$text = sprintf(
						/* translators: discussions title. */
							esc_html__( 'You have a new discussion: %s', 'buddyboss' ),
							$topic_title
						);
					}
				}
			}

			$content = array(
				'text'  => $text,
				'link'  => $topic_link,
				'title' => $title,
				'image' => bb_notification_avatar_url( $notification ),
			);
		}

		return $content;
	}
}
