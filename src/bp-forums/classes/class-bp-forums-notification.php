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

		$this->bb_register_subscription_type(
			array(
				'label'              => __( 'Discussions', 'buddyboss' ),
				'subscription_type'  => 'topic',
				'items_callback'     => array( $this, 'bb_render_forums_subscribed_reply' ),
				'send_callback'      => array( $this, 'bb_send_forums_subscribed_reply' ),
				'notification_type'  => 'bb_forums_subscribed_reply',
				'notification_group' => 'forums',
			)
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

		$this->bb_register_subscription_type(
			array(
				'label'              => __( 'Forums', 'buddyboss' ),
				'subscription_type'  => 'forum',
				'items_callback'     => array( $this, 'bb_render_forums_subscribed_discussion' ),
				'send_callback'      => array( $this, 'bb_send_forums_subscribed_discussion' ),
				'notification_type'  => 'bb_forums_subscribed_discussion',
				'notification_group' => 'forums',
			)
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

	/**
	 * Render callback function on frontend.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $items Array of subscription list.
	 *
	 * @return array
	 */
	public function bb_render_forums_subscribed_discussion( $items ) {

		if ( ! empty( $items ) ) {
			foreach ( $items as $item_key => $item ) {
				$subscription = bp_parse_args(
					$item,
					array(
						'id'                => 0,
						'user_id'           => 0,
						'item_id'           => 0,
						'secondary_item_id' => 0,
					)
				);

				if (
					empty( $subscription['id'] ) ||
					empty( $subscription['item_id'] )
				) {
					continue;
				}

				$data = array(
					'title'            => bbp_get_forum_title( $subscription['item_id'] ),
					'description_html' => '',
					'parent_html'      => '',
					'icon'             => array(),
					'link'             => bbp_get_forum_permalink( $subscription['item_id'] ),
				);

				$data['icon']['full'] = (string) (
				function_exists( 'bbp_get_forum_thumbnail_src' )
					? bbp_get_forum_thumbnail_src( $subscription['item_id'], 'full', 'full' )
					: get_the_post_thumbnail_url( $subscription['item_id'], 'full' )
				);

				$data['icon']['thumb'] = (string) (
				function_exists( 'bbp_get_forum_thumbnail_src' )
					? bbp_get_forum_thumbnail_src( $subscription['item_id'], 'thumbnail', 'large' )
					: get_the_post_thumbnail_url( $subscription['item_id'], 'thumbnail' )
				);

				if ( empty( $data['icon']['full'] ) ) {
					$data['icon']['full'] = bb_attachments_get_default_profile_group_avatar_image(
						array(
							'object' => 'user',
						)
					);
				}

				if ( empty( $data['icon']['thumb'] ) ) {
					$data['icon']['thumb'] = bb_attachments_get_default_profile_group_avatar_image(
						array(
							'object' => 'user',
							'size'   => 'thumbnail',
						)
					);
				}

				if ( ! empty( $subscription['secondary_item_id'] ) ) {
					$data['parent_html'] = bbp_get_forum_title( $subscription['secondary_item_id'] );
				}

				$items[ $item_key ] = (object) array_merge( (array) $item, $data );
			}
		}

		return $items;
	}

	/**
	 * Render callback function on frontend.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $items Array of subscription list.
	 *
	 * @return array
	 */
	public function bb_render_forums_subscribed_reply( $items ) {

		if ( ! empty( $items ) ) {
			foreach ( $items as $item_key => $item ) {
				$subscription = bp_parse_args(
					$item,
					array(
						'id'                => 0,
						'user_id'           => 0,
						'item_id'           => 0,
						'secondary_item_id' => 0,
					)
				);

				if (
					empty( $subscription['id'] ) ||
					empty( $subscription['item_id'] )
				) {
					continue;
				}

				$data = array(
					'title'            => bbp_get_topic_title( $subscription['item_id'] ),
					'description_html' => '',
					'parent_html'      => '',
					'icon'             => array(),
					'link'             => bbp_get_topic_permalink( $subscription['item_id'] ),
				);

				$data['icon']['full'] = (string) bp_core_fetch_avatar(
					array(
						'item_id' => $subscription['user_id'],
						'html'    => false,
						'type'    => 'full',
					)
				);

				$data['icon']['thumb'] = (string) bp_core_fetch_avatar(
					array(
						'item_id' => $subscription['user_id'],
						'html'    => false,
					)
				);

				if ( empty( $data['icon']['full'] ) ) {
					$data['icon']['full'] = (string) bb_attachments_get_default_profile_group_avatar_image(
						array(
							'object' => 'user',
						)
					);
				}

				if ( empty( $data['icon']['thumb'] ) ) {
					$data['icon']['thumb'] = (string) bb_attachments_get_default_profile_group_avatar_image(
						array(
							'object' => 'user',
							'size'   => 'thumbnail',
						)
					);
				}

				if ( ! empty( $subscription['secondary_item_id'] ) ) {
					$data['parent_html'] = bbp_get_topic_title( $subscription['secondary_item_id'] );
				}

				$items[ $item_key ] = (object) array_merge( (array) $item, $data );
			}
		}

		return $items;
	}

	/**
	 * Send callback function for forum type notification.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $args Array of arguments.
	 *
	 * @return bool|void
	 */
	public function bb_send_forums_subscribed_discussion( $args ) {

		$r = bp_parse_args(
			$args,
			array(
				'type'              => '',
				'item_id'           => '',
				'data'              => array(),
				'notification_type' => '',
				'user_ids'          => array(),
			)
		);

		if ( empty( $r['user_ids'] ) ) {
			return;
		}

		$type_key = 'notification_forums_following_topic';
		if ( ! bb_enabled_legacy_email_preference() ) {
			$type_key = bb_get_prefences_key( 'legacy', $type_key );
		}

		$topic_id     = ! empty( $r['data']['topic_id'] ) ? $r['data']['topic_id'] : 0;
		$author_id    = ! empty( $r['data']['author_id'] ) ? $r['data']['author_id'] : bbp_get_topic_author_id( $topic_id );
		$email_tokens = ! empty( $r['data']['email_tokens'] ) ? $r['data']['email_tokens'] : array();

		if ( ! empty( $author_id ) ) {
			// Remove topic author from the users.
			$unset_topic_key = array_search( $author_id, $r['user_ids'], true );
			if ( false !== $unset_topic_key ) {
				unset( $r['user_ids'][ $unset_topic_key ] );
			}
		}

		foreach ( $r['user_ids'] as $user_id ) {
			// Bail if member opted out of receiving this email.
			// Check the sender is blocked by recipient or not.
			if (
				true === bb_is_notification_enabled( $user_id, $type_key ) &&
				true !== (bool) apply_filters( 'bb_is_recipient_moderated', false, $user_id, $author_id )
			) {
				$unsubscribe_args = array(
					'user_id'           => $user_id,
					'notification_type' => 'bbp-new-forum-topic',
				);

				$email_tokens['tokens']['unsubscribe'] = esc_url( bp_email_get_unsubscribe_link( $unsubscribe_args ) );

				// Send notification email.
				bp_send_email( 'bbp-new-forum-topic', (int) $user_id, $email_tokens );
			}

			if ( bp_is_active( 'notifications' ) ) {
				bp_notifications_add_notification(
					array(
						'user_id'           => $user_id,
						'item_id'           => $topic_id,
						'secondary_item_id' => $author_id,
						'component_name'    => bbp_get_component_name(),
						'component_action'  => 'bb_forums_subscribed_discussion',
						'date_notified'     => bp_core_current_time(),
						'is_new'            => 1,
					)
				);
			}
		}

		return true;
	}

	/**
	 * Send callback function for topic type notification.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $args Array of arguments.
	 *
	 * @return bool|void
	 */
	public function bb_send_forums_subscribed_reply( $args ) {

		$r = bp_parse_args(
			$args,
			array(
				'type'              => '',
				'item_id'           => '',
				'data'              => array(),
				'notification_type' => '',
				'user_ids'          => array(),
			)
		);

		if ( empty( $r['user_ids'] ) ) {
			return;
		}

		// @todo needs to perform code for send notification and email.

		return true;
	}

}
