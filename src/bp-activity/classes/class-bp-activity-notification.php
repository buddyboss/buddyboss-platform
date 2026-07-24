<?php
/**
 * BuddyBoss Activity Notification Class.
 *
 * @package BuddyBoss\Activity
 *
 * @since BuddyBoss 1.9.3
 */

defined( 'ABSPATH' ) || exit;

/**
 * Set up the BP_Activity_Notification class.
 *
 * @since BuddyBoss 1.9.3
 */
class BP_Activity_Notification extends BP_Core_Notification_Abstract {

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
		add_action( 'bp_init', array( $this, 'start' ), 5 );
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
			'activity',
			esc_html__( 'Activity Feeds', 'buddyboss-platform' ),
			esc_html__( 'Activity Feeds', 'buddyboss-platform' ),
			6
		);

		$this->register_notification_for_reply();

		$this->register_notification_for_activity_post_following();

		$this->register_notification_for_following();
	}

	/**
	 * Register notification for activity reply.
	 *
	 * @since BuddyBoss 1.9.3
	 */
	public function register_notification_for_reply() {
		$this->register_notification_type(
			'bb_activity_comment',
			esc_html__( 'A member replies to your post or comment', 'buddyboss-platform' ),
			esc_html__( 'A member receives a reply to their post or comment', 'buddyboss-platform' ),
			'activity'
		);

		$this->register_email_type(
			'activity-comment',
			array(
				/* translators: do not remove {} brackets or translate its contents. */
				'email_title'         => __( '[{{{site.name}}}] {{poster.name}} replied to one of your updates', 'buddyboss-platform' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_content'       => __( "<a href=\"{{{poster.url}}}\">{{poster.name}}</a> replied to one of your updates:\n\n{{{activity_reply}}}", 'buddyboss-platform' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_plain_content' => __( "{{poster.name}} replied to one of your updates:\n\n{{{activity_reply}}}\n\nGo to the discussion to reply or catch up on the conversation: {{{thread.url}}}", 'buddyboss-platform' ),
				'situation_label'     => __( 'A member receives a reply to their activity post', 'buddyboss-platform' ),
				'unsubscribe_text'    => __( 'You will no longer receive emails when someone replies to an update or comment you posted.', 'buddyboss-platform' ),
				'group'               => 'activity',
			),
			'bb_activity_comment'
		);

		$this->register_email_type(
			'activity-comment-author',
			array(
				/* translators: do not remove {} brackets or translate its contents. */
				'email_title'         => __( '[{{{site.name}}}] {{poster.name}} replied to one of your comments', 'buddyboss-platform' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_content'       => __( "<a href=\"{{{poster.url}}}\">{{poster.name}}</a> replied to one of your comments:\n\n{{{activity_reply}}}", 'buddyboss-platform' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_plain_content' => __( "{{poster.name}} replied to one of your comments:\n\n{{{activity_reply}}}\n\nGo to the discussion to reply or catch up on the conversation: {{{thread.url}}}", 'buddyboss-platform' ),
				'situation_label'     => __( 'A member receives a reply to their activity comment', 'buddyboss-platform' ),
				'unsubscribe_text'    => __( 'You will no longer receive emails when someone replies to an update or comment you posted.', 'buddyboss-platform' ),
				'group'               => 'activity',
			),
			'bb_activity_comment'
		);

		$this->register_notification(
			'activity',
			'bb_activity_comment',
			'bb_activity_comment',
			'bb-icon-f bb-icon-comment-activity'
		);

		$this->register_notification_filter(
			esc_html__( 'New activity comments', 'buddyboss-platform' ),
			array( 'bb_activity_comment' ),
			15
		);

		add_filter( 'bp_activity_bb_activity_comment_notification', array( $this, 'bb_render_comment_notification' ), 10, 7 );
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

		if ( 'activity' === $component_name && 'bb_following_new' === $component_action_name ) {
			$notification      = bp_notifications_get_notification( $notification_id );
			$user_id           = $secondary_item_id;
			$user_fullname     = bp_core_get_user_displayname( $user_id );
			$notification_link = add_query_arg( 'rid', (int) $notification_id, bp_core_get_user_domain( $user_id ) );

			if ( 'web_push' === $screen ) {
				$text = esc_html__( 'Started following you', 'buddyboss-platform' );
			} else {
				$text = sprintf(
				/* translators: %s: User full name. */
					__( '%1$s started following you', 'buddyboss-platform' ),
					$user_fullname
				);
			}

			$content = apply_filters(
				'bb_activity_single_' . $notification->component_action . '_notification',
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
	public function bb_render_comment_notification( $content, $item_id, $secondary_item_id, $total_items, $format, $notification_id, $screen ) {
		$notification           = bp_notifications_get_notification( $notification_id );
		$user_id                = $secondary_item_id;
		$user_fullname          = bp_core_get_user_displayname( $user_id );
		$notification_type_html = '';

		if ( ! empty( $notification ) && 'bb_activity_comment' === $notification->component_action ) {

			$notification_type = bp_notifications_get_meta( $notification_id, 'type', true );
			$notification_link = bp_get_notifications_permalink();

			if ( $notification_type ) {
				if ( 'activity_comment' === $notification_type ) {
					$notification_type_html = esc_html__( 'comment', 'buddyboss-platform' );
				} elseif ( 'post_comment' === $notification_type || 'activity_post' === $notification_type ) {
					$notification_type_html = esc_html__( 'post', 'buddyboss-platform' );
				}
			}

			$activity         = new BP_Activity_Activity( $item_id );
			$activity_excerpt = bp_create_excerpt(
				wp_strip_all_tags( $activity->content ),
				50,
				array(
					'ending' => __( '&hellip;', 'buddyboss-platform' ),
				)
			);

			if ( '&nbsp;' === $activity_excerpt ) {
				$activity_excerpt = '';
			}

			if ( empty( $activity_excerpt ) && function_exists( 'bp_blogs_activity_comment_content_with_read_more' ) ) {
				$activity_excerpt = bp_blogs_activity_comment_content_with_read_more( '', $activity );

				$activity_excerpt = bp_create_excerpt(
					wp_strip_all_tags( $activity_excerpt ),
					50,
					array(
						'ending' => __( '&hellip;', 'buddyboss-platform' ),
					)
				);

				if ( '&nbsp;' === $activity_excerpt ) {
					$activity_excerpt = '';
				}
			}

			$activity_excerpt = '"' . $activity_excerpt . '"';

			$activity_excerpt = str_replace( '&hellip;"', '&hellip;', $activity_excerpt );
			$activity_excerpt = str_replace( '&#8203;', '', $activity_excerpt );
			$activity_excerpt = str_replace( '""', '', $activity_excerpt );

			$activity_metas = bb_activity_get_metadata( $activity->id );
			$media_ids      = $activity_metas['bp_media_ids'][0] ?? '';
			$document_ids   = $activity_metas['bp_document_ids'][0] ?? '';
			$video_ids      = $activity_metas['bp_video_ids'][0] ?? '';
			$gif_data       = ! empty( $activity_metas['_gif_data'][0] ) ? maybe_unserialize( $activity_metas['_gif_data'][0] ) : array();
			$amount         = 'single';

			if ( 'web_push' === $screen ) {
				$notification_link = add_query_arg( 'rid', (int) $notification_id, bp_activity_get_permalink( $item_id ) );
				if ( ! empty( $notification_type_html ) ) {
					if ( ! empty( $activity_excerpt ) ) {
						$text = sprintf(
						/* translators: 1: Activity type, 2: Activity content. */
							__( 'Replied to your %1$s: %2$s', 'buddyboss-platform' ),
							$notification_type_html,
							$activity_excerpt
						);
					} elseif ( $media_ids ) {
						$media_ids = array_filter( ! is_array( $media_ids ) ? explode( ',', $media_ids ) : $media_ids );
						if ( count( $media_ids ) > 1 ) {
							$text = sprintf(
							/* translators: Activity type. */
								__( 'Replied to your %s: some photos', 'buddyboss-platform' ),
								$notification_type_html
							);
						} else {
							$text = sprintf(
							/* translators: Activity type. */
								__( 'Replied to your %s: a photo', 'buddyboss-platform' ),
								$notification_type_html
							);
						}
					} elseif ( $document_ids ) {
						$document_ids = array_filter( ! is_array( $document_ids ) ? explode( ',', $document_ids ) : $document_ids );
						if ( count( $document_ids ) > 1 ) {
							$text = sprintf(
							/* translators: Activity type. */
								__( 'Replied to your %s: some documents', 'buddyboss-platform' ),
								$notification_type_html
							);
						} else {
							$text = sprintf(
							/* translators: Activity type. */
								__( 'Replied to your %s: a document', 'buddyboss-platform' ),
								$notification_type_html
							);
						}
					} elseif ( $video_ids ) {
						$video_ids = array_filter( ! is_array( $video_ids ) ? explode( ',', $video_ids ) : $video_ids );
						if ( count( $video_ids ) > 1 ) {
							$text = sprintf(
							/* translators: Activity type. */
								__( 'Replied to your %s: some videos', 'buddyboss-platform' ),
								$notification_type_html
							);
						} else {
							$text = sprintf(
							/* translators: Activity type. */
								__( 'Replied to your %s: a video', 'buddyboss-platform' ),
								$notification_type_html
							);
						}
					} elseif ( ! empty( $gif_data ) ) {
						$text = sprintf(
						/* translators: Activity type. */
							__( 'Replied to your %s: a gif', 'buddyboss-platform' ),
							$notification_type_html
						);
					} else {
						$text = sprintf(
						/* translators: Activity type. */
							__( 'Replied to your %s', 'buddyboss-platform' ),
							$notification_type_html
						);
					}
				} else {
					if ( ! empty( $activity_excerpt ) ) {
						$text = sprintf(
						/* translators: Activity content. */
							__( 'Replied: %s', 'buddyboss-platform' ),
							$activity_excerpt
						);
					} elseif ( $media_ids ) {
						$media_ids = array_filter( ! is_array( $media_ids ) ? explode( ',', $media_ids ) : $media_ids );
						if ( count( $media_ids ) > 1 ) {
							$text = __( 'Replied: some photos', 'buddyboss-platform' );
						} else {
							$text = __( 'Replied: a photo', 'buddyboss-platform' );
						}
					} elseif ( $document_ids ) {
						$document_ids = array_filter( ! is_array( $document_ids ) ? explode( ',', $document_ids ) : $document_ids );
						if ( count( $document_ids ) > 1 ) {
							$text = __( 'Replied: some documents', 'buddyboss-platform' );
						} else {
							$text = __( 'Replied: a document', 'buddyboss-platform' );
						}
					} elseif ( $video_ids ) {
						$video_ids = array_filter( ! is_array( $video_ids ) ? explode( ',', $video_ids ) : $video_ids );
						if ( count( $video_ids ) > 1 ) {
							$text = __( 'Replied: some videos', 'buddyboss-platform' );
						} else {
							$text = __( 'Replied: a video', 'buddyboss-platform' );
						}
					} elseif ( ! empty( $gif_data ) ) {
						$text = __( 'Replied: a gif', 'buddyboss-platform' );
					} else {
						$text = __( 'Replied', 'buddyboss-platform' );
					}
				}
			} else {
				if ( (int) $total_items > 1 ) {
					$notification_link = add_query_arg( 'type', $notification->component_action, $notification_link );
					$text              = sprintf(
					/* translators: %s: Total reply count. */
						__( 'You have %1$d new replies', 'buddyboss-platform' ),
						(int) $total_items
					);
					$amount = 'multiple';
				} else {
					$notification_link = add_query_arg( 'rid', (int) $notification_id, bp_activity_get_permalink( $item_id ) );

					if ( ! empty( $notification_type_html ) ) {
						if ( ! empty( $activity_excerpt ) ) {
							$text = sprintf(
							/* translators: 1: User full name, 2: Activity type, 3: Activity content. */
								__( '%1$s replied to your %2$s: %3$s', 'buddyboss-platform' ),
								$user_fullname,
								$notification_type_html,
								$activity_excerpt
							);
						} elseif ( ! empty( $media_ids ) ) {
							$media_ids = array_filter( ! is_array( $media_ids ) ? explode( ',', $media_ids ) : $media_ids );
							if ( count( $media_ids ) > 1 ) {
								$text = sprintf(
								/* translators: 1: User full name, 2: Activity type. */
									__( '%1$s replied to your %2$s: some photos', 'buddyboss-platform' ),
									$user_fullname,
									$notification_type_html
								);
							} else {
								$text = sprintf(
								/* translators: 1: User full name, 2: Activity type. */
									__( '%1$s replied to your %2$s: a photo', 'buddyboss-platform' ),
									$user_fullname,
									$notification_type_html
								);
							}
						} elseif ( ! empty( $document_ids ) ) {
							$document_ids = array_filter( ! is_array( $document_ids ) ? explode( ',', $document_ids ) : $document_ids );
							if ( count( $document_ids ) > 1 ) {
								$text = sprintf(
								/* translators: 1: User full name, 2: Activity type. */
									__( '%1$s replied to your %2$s: some documents', 'buddyboss-platform' ),
									$user_fullname,
									$notification_type_html
								);
							} else {
								$text = sprintf(
								/* translators: 1: User full name, 2: Activity type. */
									__( '%1$s replied to your %2$s: a document', 'buddyboss-platform' ),
									$user_fullname,
									$notification_type_html
								);
							}
						} elseif ( ! empty( $video_ids ) ) {
							$video_ids = array_filter( ! is_array( $video_ids ) ? explode( ',', $video_ids ) : $video_ids );
							if ( count( $video_ids ) > 1 ) {
								$text = sprintf(
								/* translators: 1: User full name, 2: Activity type. */
									__( '%1$s replied to your %2$s: some videos', 'buddyboss-platform' ),
									$user_fullname,
									$notification_type_html
								);
							} else {
								$text = sprintf(
								/* translators: 1: User full name, 2: Activity type. */
									__( '%1$s replied to your %2$s: a video', 'buddyboss-platform' ),
									$user_fullname,
									$notification_type_html
								);
							}
						} elseif ( ! empty( $gif_data ) ) {
							$text = sprintf(
							/* translators: 1: User full name, 2: Activity type. */
								__( '%1$s replied to your %2$s: a gif', 'buddyboss-platform' ),
								$user_fullname,
								$notification_type_html
							);
						} else {
							$text = sprintf(
							/* translators: 1: User full name, 2: Activity type. */
								__( '%1$s replied to your %2$s', 'buddyboss-platform' ),
								$user_fullname,
								$notification_type_html
							);
						}
					} else {
						if ( ! empty( $activity_excerpt ) ) {
							$text = sprintf(
							/* translators: 1: User full name, 2: Activity content. */
								__( '%1$s replied: %2$s', 'buddyboss-platform' ),
								$user_fullname,
								$activity_excerpt
							);
						} elseif ( $media_ids ) {
							$media_ids = array_filter( ! is_array( $media_ids ) ? explode( ',', $media_ids ) : $media_ids );
							if ( count( $media_ids ) > 1 ) {
								$text = sprintf(
								/* translators: User full name. */
									__( '%1$s replied: some photos', 'buddyboss-platform' ),
									$user_fullname
								);
							} else {
								$text = sprintf(
								/* translators: User full name. */
									__( '%1$s replied: a photo', 'buddyboss-platform' ),
									$user_fullname
								);
							}
						} elseif ( $document_ids ) {
							$document_ids = array_filter( ! is_array( $document_ids ) ? explode( ',', $document_ids ) : $document_ids );
							if ( count( $document_ids ) > 1 ) {
								$text = sprintf(
								/* translators: User full name. */
									__( '%1$s replied: some documents', 'buddyboss-platform' ),
									$user_fullname
								);
							} else {
								$text = sprintf(
								/* translators: User full name. */
									__( '%1$s replied: a document', 'buddyboss-platform' ),
									$user_fullname
								);
							}
						} elseif ( $video_ids ) {
							$video_ids = array_filter( ! is_array( $video_ids ) ? explode( ',', $video_ids ) : $video_ids );
							if ( count( $video_ids ) > 1 ) {
								$text = sprintf(
								/* translators: User full name. */
									__( '%1$s replied: some videos', 'buddyboss-platform' ),
									$user_fullname
								);
							} else {
								$text = sprintf(
								/* translators: User full name. */
									__( '%1$s replied: a video', 'buddyboss-platform' ),
									$user_fullname
								);
							}
						} elseif ( ! empty( $gif_data ) ) {
							$text = sprintf(
							/* translators: User full name. */
								__( '%1$s replied: a gif', 'buddyboss-platform' ),
								$user_fullname
							);
						} else {
							$text = sprintf(
							/* translators: %s: User full name. */
								__( '%1$s replied', 'buddyboss-platform' ),
								$user_fullname
							);
						}
					}
				}
			}

			$content = apply_filters(
				'bb_activity_' . $amount . '_' . $notification->component_action . '_notification',
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

	/**
	 * Register notification for followers when new activity posted.
	 *
	 * @since BuddyBoss 2.2.3
	 */
	public function register_notification_for_activity_post_following() {
		$notification_read_only    = true;
		$notification_tooltip_text = __( 'Requires following to enable', 'buddyboss-platform' );

		if ( function_exists( 'bp_is_activity_follow_active' ) && true === bp_is_activity_follow_active() ) {
			$notification_tooltip_text = __( 'Required by activity follow', 'buddyboss-platform' );
			$notification_read_only    = false;
		}

		$this->register_notification_type(
			'bb_activity_following_post',
			__( 'New post by a member you\'re following', 'buddyboss-platform' ),
			esc_html__( 'A new post by someone a member is following', 'buddyboss-platform' ),
			'activity',
			function_exists( 'bp_is_activity_follow_active' ) && true === bp_is_activity_follow_active(),
			$notification_read_only,
			$notification_tooltip_text
		);

		$this->register_email_type(
			'new-activity-following',
			array(
				/* translators: do not remove {} brackets or translate its contents. */
				'email_title'         => __( '[{{{site.name}}}] {{poster.name}} posted {{activity.type}}.', 'buddyboss-platform' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_content'       => __( "<a href=\"{{{poster.url}}}\">{{poster.name}}</a> posted {{activity.type}}:\n\n{{{activity.content}}}", 'buddyboss-platform' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_plain_content' => __( "{{poster.name}} posted {{activity.type}}:\n\n{{{activity.content}}}\\n\nView the post: {{{activity.url}}}", 'buddyboss-platform' ),
				'situation_label'     => __( 'New activity post by someone a member is following', 'buddyboss-platform' ),
				'unsubscribe_text'    => __( 'You will no longer receive emails when someone you are following posts an update.', 'buddyboss-platform' ),
				'group'               => 'activity',
			),
			'bb_activity_following_post'
		);

		$this->register_notification(
			'activity',
			'bb_activity_following_post',
			'bb_activity_following_post',
			'bb-icon-f bb-icon-activity'
		);

		$this->register_notification_filter(
			esc_html__( 'New activity posts', 'buddyboss-platform' ),
			array( 'bb_activity_following_post' ),
			16
		);

		add_filter( 'bp_activity_bb_activity_following_post_notification', array( $this, 'bb_render_activity_following_post_notification' ), 10, 7 );
	}

	/**
	 * Format the notifications for followers when new activity posted.
	 *
	 * @since BuddyBoss 2.2.3
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
	public function bb_render_activity_following_post_notification( $content, $item_id, $secondary_item_id, $total_items, $format, $notification_id, $screen ) {
		$notification = bp_notifications_get_notification( $notification_id );

		if ( ! empty( $notification ) && 'bb_activity_following_post' === $notification->component_action ) {

			$user_id           = $secondary_item_id;
			$user_fullname     = bp_core_get_user_displayname( $user_id );
			$notification_link = bp_get_notifications_permalink();
			$activity          = new BP_Activity_Activity( $item_id );
			$activity_excerpt  = '"' . bp_create_excerpt(
				wp_strip_all_tags( $activity->content ),
				50,
				array(
					'ending' => __( '&hellip;', 'buddyboss-platform' ),
				)
			) . '"';

			if ( '&nbsp;' === $activity_excerpt ) {
				$activity_excerpt = '';
			}

			$activity_excerpt = str_replace( '&hellip;"', '&hellip;', $activity_excerpt );
			$activity_excerpt = str_replace( '&#8203;', '', $activity_excerpt );
			$activity_excerpt = str_replace( '""', '', $activity_excerpt );

			$media_ids    = bp_activity_get_meta( $activity->id, 'bp_media_ids', true );
			$document_ids = bp_activity_get_meta( $activity->id, 'bp_document_ids', true );
			$video_ids    = bp_activity_get_meta( $activity->id, 'bp_video_ids', true );
			$gif_data     = bp_activity_get_meta( $activity->id, '_gif_data', true );
			$poll_id      = bp_activity_get_meta( $activity->id, 'bb_poll_id' );
			$poll         = ! empty( $poll_id ) && function_exists( 'bb_load_polls' ) ? bb_load_polls()->bb_get_poll( $poll_id ) : '';
			$question     = ! empty( $poll->question ) ? $poll->question : '';
			$amount       = 'single';

			if ( 'web_push' === $screen ) {
				$notification_link = add_query_arg( 'rid', (int) $notification_id, bp_activity_get_permalink( $item_id ) );
				if ( ! empty( $activity_excerpt ) ) {
					$text = sprintf(
					/* translators: Activity content. */
						__( 'Posted an update: %s', 'buddyboss-platform' ),
						$activity_excerpt
					);
				} elseif ( $media_ids ) {
					$media_ids = array_filter( ! is_array( $media_ids ) ? explode( ',', $media_ids ) : $media_ids );
					if ( count( $media_ids ) > 1 ) {
						$text = __( 'Posted some photos', 'buddyboss-platform' );
					} else {
						$text = __( 'Posted a photo', 'buddyboss-platform' );
					}
				} elseif ( $document_ids ) {
					$document_ids = array_filter( ! is_array( $document_ids ) ? explode( ',', $document_ids ) : $document_ids );
					if ( count( $document_ids ) > 1 ) {
						$text = __( 'Posted some documents', 'buddyboss-platform' );
					} else {
						$text = __( 'Posted a document', 'buddyboss-platform' );
					}
				} elseif ( $video_ids ) {
					$video_ids = array_filter( ! is_array( $video_ids ) ? explode( ',', $video_ids ) : $video_ids );
					if ( count( $video_ids ) > 1 ) {
						$text = __( 'Posted some videos', 'buddyboss-platform' );
					} else {
						$text = __( 'Posted a video', 'buddyboss-platform' );
					}
				} elseif ( ! empty( $gif_data ) ) {
					$text = __( 'Posted an update', 'buddyboss-platform' );
				} elseif ( ! empty( $question ) ) {
					$text = sprintf(
					/* translators: %s: question. */
						__( 'Posted a poll "%1$s"', 'buddyboss-platform' ),
						$question
					);
				} else {
					$text = __( 'Posted an update', 'buddyboss-platform' );
				}
			} else {
				if ( (int) $total_items > 1 ) {
					$notification_link = add_query_arg( 'type', $notification->component_action, $notification_link );
					$text              = sprintf(
						/* translators: %s: Total reply count. */
						__( 'You have %1$d new posts', 'buddyboss-platform' ),
						(int) $total_items
					);
					$amount = 'multiple';
				} else {
					$notification_link = add_query_arg( 'rid', (int) $notification_id, bp_activity_get_permalink( $item_id ) );
					if ( ! empty( $activity_excerpt ) ) {
						$text = sprintf(
						/* translators: 1: User full name, 2: Activity content. */
							__( '%1$s posted an update: %2$s', 'buddyboss-platform' ),
							$user_fullname,
							$activity_excerpt
						);
					} elseif ( $media_ids ) {
						$media_ids = array_filter( ! is_array( $media_ids ) ? explode( ',', $media_ids ) : $media_ids );
						if ( count( $media_ids ) > 1 ) {
							$text = sprintf(
							/* translators: User full name. */
								__( '%1$s posted some photos', 'buddyboss-platform' ),
								$user_fullname
							);
						} else {
							$text = sprintf(
							/* translators: User full name. */
								__( '%1$s posted a photo', 'buddyboss-platform' ),
								$user_fullname
							);
						}
					} elseif ( $document_ids ) {
						$document_ids = array_filter( ! is_array( $document_ids ) ? explode( ',', $document_ids ) : $document_ids );
						if ( count( $document_ids ) > 1 ) {
							$text = sprintf(
							/* translators: User full name. */
								__( '%1$s posted some documents', 'buddyboss-platform' ),
								$user_fullname
							);
						} else {
							$text = sprintf(
							/* translators: User full name. */
								__( '%1$s posted a document', 'buddyboss-platform' ),
								$user_fullname
							);
						}
					} elseif ( $video_ids ) {
						$video_ids = array_filter( ! is_array( $video_ids ) ? explode( ',', $video_ids ) : $video_ids );
						if ( count( $video_ids ) > 1 ) {
							$text = sprintf(
							/* translators: User full name. */
								__( '%1$s posted some videos', 'buddyboss-platform' ),
								$user_fullname
							);
						} else {
							$text = sprintf(
							/* translators: User full name. */
								__( '%1$s posted a video', 'buddyboss-platform' ),
								$user_fullname
							);
						}
					} elseif ( ! empty( $gif_data ) ) {
						$text = sprintf(
						/* translators: User full name. */
							__( '%1$s posted an update', 'buddyboss-platform' ),
							$user_fullname
						);
					} elseif ( ! empty( $question ) ) {
						$text = sprintf(
						/* translators: %1$s: User full name, %2$s: question. */
							__( '%1$s posted a poll "%2$s"', 'buddyboss-platform' ),
							$user_fullname,
							$question
						);
					} else {
						$text = sprintf(
						/* translators: %s: User full name. */
							__( '%1$s posted an update', 'buddyboss-platform' ),
							$user_fullname
						);
					}
				}
			}

			$content = apply_filters(
				'bb_activity_' . $amount . '_' . $notification->component_action . '_notification',
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

	/**
	 * Register notification for following users.
	 *
	 * @since BuddyBoss 2.2.5
	 */
	public function register_notification_for_following() {
		$notification_read_only    = true;
		$notification_tooltip_text = __( 'Requires following to enable', 'buddyboss-platform' );

		if ( function_exists( 'bp_is_activity_follow_active' ) && true === bp_is_activity_follow_active() ) {
			$notification_tooltip_text = __( 'Required by activity follow', 'buddyboss-platform' );
			$notification_read_only    = false;
		}

		$this->register_notification_type(
			'bb_following_new',
			esc_html__( 'A member starts following you', 'buddyboss-platform' ),
			esc_html__( 'A member is followed by someone', 'buddyboss-platform' ),
			'activity',
			function_exists( 'bp_is_activity_follow_active' ) && true === bp_is_activity_follow_active(),
			$notification_read_only,
			$notification_tooltip_text
		);

		$this->register_email_type(
			'new-follower',
			array(
				/* translators: do not remove {} brackets or translate its contents. */
				'email_title'         => __( '[{{{site.name}}}] {{follower.name}} started following you', 'buddyboss-platform' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_content'       => __( "<a href=\"{{{follower.url}}}\">{{follower.name}}</a> started following you.\n\n{{{member.card}}}", 'buddyboss-platform' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_plain_content' => __( "{{follower.name}} started following you.\n\nTo learn more about them, visit their profile: {{{follower.url}}}", 'buddyboss-platform' ),
				'situation_label'     => __( 'A member receives a new follower', 'buddyboss-platform' ),
				'unsubscribe_text'    => __( 'You will no longer receive emails when someone follows you.', 'buddyboss-platform' ),
				'group'               => 'connections',
			),
			'bb_following_new'
		);

		$this->register_notification(
			'activity',
			'bb_following_new',
			'bb_following_new',
			''
		);

		$this->register_notification_filter(
			esc_html__( 'New followers', 'buddyboss-platform' ),
			array( 'bb_following_new' ),
			17
		);
	}
}
