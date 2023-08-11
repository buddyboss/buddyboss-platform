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
			'activity',
			esc_html__( 'Activity Feeds', 'buddyboss' ),
			esc_html__( 'Activity Feeds', 'buddyboss' ),
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
			esc_html__( 'A member replies to your post or comment', 'buddyboss' ),
			esc_html__( 'A member receives a reply to their post or comment', 'buddyboss' ),
			'activity'
		);

		$this->register_email_type(
			'activity-comment',
			array(
				/* translators: do not remove {} brackets or translate its contents. */
				'email_title'         => __( '[{{{site.name}}}] {{poster.name}} replied to one of your updates', 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_content'       => __( "<a href=\"{{{poster.url}}}\">{{poster.name}}</a> replied to one of your updates:\n\n{{{activity_reply}}}", 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_plain_content' => __( "{{poster.name}} replied to one of your updates:\n\n{{{activity_reply}}}\n\nGo to the discussion to reply or catch up on the conversation: {{{thread.url}}}", 'buddyboss' ),
				'situation_label'     => __( 'A member receives a reply to their activity post', 'buddyboss' ),
				'unsubscribe_text'    => __( 'You will no longer receive emails when someone replies to an update or comment you posted.', 'buddyboss' ),
			),
			'bb_activity_comment'
		);

		$this->register_email_type(
			'activity-comment-author',
			array(
				/* translators: do not remove {} brackets or translate its contents. */
				'email_title'         => __( '[{{{site.name}}}] {{poster.name}} replied to one of your comments', 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_content'       => __( "<a href=\"{{{poster.url}}}\">{{poster.name}}</a> replied to one of your comments:\n\n{{{activity_reply}}}", 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_plain_content' => __( "{{poster.name}} replied to one of your comments:\n\n{{{activity_reply}}}\n\nGo to the discussion to reply or catch up on the conversation: {{{thread.url}}}", 'buddyboss' ),
				'situation_label'     => __( 'A member receives a reply to their activity comment', 'buddyboss' ),
				'unsubscribe_text'    => __( 'You will no longer receive emails when someone replies to an update or comment you posted.', 'buddyboss' ),

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
			esc_html__( 'New activity comments', 'buddyboss' ),
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
				$text = esc_html__( 'Started following you', 'buddyboss' );
			} else {
				$text = sprintf(
				/* translators: %s: User full name. */
					__( '%1$s started following you', 'buddyboss' ),
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
					$notification_type_html = esc_html__( 'comment', 'buddyboss' );
				} elseif ( 'post_comment' === $notification_type || 'activity_post' === $notification_type ) {
					$notification_type_html = esc_html__( 'post', 'buddyboss' );
				}
			}

			$activity         = new BP_Activity_Activity( $item_id );
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

			if ( empty( $activity_excerpt ) && function_exists( 'bp_blogs_activity_comment_content_with_read_more' ) ) {
				$activity_excerpt = bp_blogs_activity_comment_content_with_read_more( '', $activity );

				$activity_excerpt = bp_create_excerpt(
					wp_strip_all_tags( $activity_excerpt ),
					50,
					array(
						'ending' => __( '&hellip;', 'buddyboss' ),
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

			$media_ids    = bp_activity_get_meta( $activity->id, 'bp_media_ids', true );
			$document_ids = bp_activity_get_meta( $activity->id, 'bp_document_ids', true );
			$video_ids    = bp_activity_get_meta( $activity->id, 'bp_video_ids', true );
			$gif_data     = bp_activity_get_meta( $activity->id, '_gif_data', true );
			$amount       = 'single';

			if ( 'web_push' === $screen ) {
				$notification_link = add_query_arg( 'rid', (int) $notification_id, bp_activity_get_permalink( $item_id ) );
				if ( ! empty( $notification_type_html ) ) {
					if ( ! empty( $activity_excerpt ) ) {
						$text = sprintf(
						/* translators: 1: Activity type, 2: Activity content. */
							__( 'Replied to your %1$s: %2$s', 'buddyboss' ),
							$notification_type_html,
							$activity_excerpt
						);
					} elseif ( $media_ids ) {
						$media_ids = array_filter( ! is_array( $media_ids ) ? explode( ',', $media_ids ) : $media_ids );
						if ( count( $media_ids ) > 1 ) {
							$text = sprintf(
							/* translators: Activity type. */
								__( 'Replied to your %s: some photos', 'buddyboss' ),
								$notification_type_html
							);
						} else {
							$text = sprintf(
							/* translators: Activity type. */
								__( 'Replied to your %s: a photo', 'buddyboss' ),
								$notification_type_html
							);
						}
					} elseif ( $document_ids ) {
						$document_ids = array_filter( ! is_array( $document_ids ) ? explode( ',', $document_ids ) : $document_ids );
						if ( count( $document_ids ) > 1 ) {
							$text = sprintf(
							/* translators: Activity type. */
								__( 'Replied to your %s: some documents', 'buddyboss' ),
								$notification_type_html
							);
						} else {
							$text = sprintf(
							/* translators: Activity type. */
								__( 'Replied to your %s: a document', 'buddyboss' ),
								$notification_type_html
							);
						}
					} elseif ( $video_ids ) {
						$video_ids = array_filter( ! is_array( $video_ids ) ? explode( ',', $video_ids ) : $video_ids );
						if ( count( $video_ids ) > 1 ) {
							$text = sprintf(
							/* translators: Activity type. */
								__( 'Replied to your %s: some videos', 'buddyboss' ),
								$notification_type_html
							);
						} else {
							$text = sprintf(
							/* translators: Activity type. */
								__( 'Replied to your %s: a video', 'buddyboss' ),
								$notification_type_html
							);
						}
					} elseif ( ! empty( $gif_data ) ) {
						$text = sprintf(
						/* translators: Activity type. */
							__( 'Replied to your %s: a gif', 'buddyboss' ),
							$notification_type_html
						);
					} else {
						$text = sprintf(
						/* translators: Activity type. */
							__( 'Replied to your %s', 'buddyboss' ),
							$notification_type_html
						);
					}
				} else {
					if ( ! empty( $activity_excerpt ) ) {
						$text = sprintf(
						/* translators: Activity content. */
							__( 'Replied: %s', 'buddyboss' ),
							$activity_excerpt
						);
					} elseif ( $media_ids ) {
						$media_ids = array_filter( ! is_array( $media_ids ) ? explode( ',', $media_ids ) : $media_ids );
						if ( count( $media_ids ) > 1 ) {
							$text = __( 'Replied: some photos', 'buddyboss' );
						} else {
							$text = __( 'Replied: a photo', 'buddyboss' );
						}
					} elseif ( $document_ids ) {
						$document_ids = array_filter( ! is_array( $document_ids ) ? explode( ',', $document_ids ) : $document_ids );
						if ( count( $document_ids ) > 1 ) {
							$text = __( 'Replied: some documents', 'buddyboss' );
						} else {
							$text = __( 'Replied: a document', 'buddyboss' );
						}
					} elseif ( $video_ids ) {
						$video_ids = array_filter( ! is_array( $video_ids ) ? explode( ',', $video_ids ) : $video_ids );
						if ( count( $video_ids ) > 1 ) {
							$text = __( 'Replied: some videos', 'buddyboss' );
						} else {
							$text = __( 'Replied: a video', 'buddyboss' );
						}
					} elseif ( ! empty( $gif_data ) ) {
						$text = __( 'Replied: a gif', 'buddyboss' );
					} else {
						$text = __( 'Replied', 'buddyboss' );
					}
				}
			} else {
				if ( (int) $total_items > 1 ) {
					$notification_link = add_query_arg( 'type', $notification->component_action, $notification_link );
					$text              = sprintf(
					/* translators: %s: Total reply count. */
						__( 'You have %1$d new replies', 'buddyboss' ),
						(int) $total_items
					);
					$amount = 'multiple';
				} else {
					$notification_link = add_query_arg( 'rid', (int) $notification_id, bp_activity_get_permalink( $item_id ) );

					if ( ! empty( $notification_type_html ) ) {
						if ( ! empty( $activity_excerpt ) ) {
							$text = sprintf(
							/* translators: 1: User full name, 2: Activity type, 3: Activity content. */
								__( '%1$s replied to your %2$s: %3$s', 'buddyboss' ),
								$user_fullname,
								$notification_type_html,
								$activity_excerpt
							);
						} elseif ( ! empty( $media_ids ) ) {
							$media_ids = array_filter( ! is_array( $media_ids ) ? explode( ',', $media_ids ) : $media_ids );
							if ( count( $media_ids ) > 1 ) {
								$text = sprintf(
								/* translators: 1: User full name, 2: Activity type. */
									__( '%1$s replied to your %2$s: some photos', 'buddyboss' ),
									$user_fullname,
									$notification_type_html
								);
							} else {
								$text = sprintf(
								/* translators: 1: User full name, 2: Activity type. */
									__( '%1$s replied to your %2$s: a photo', 'buddyboss' ),
									$user_fullname,
									$notification_type_html
								);
							}
						} elseif ( ! empty( $document_ids ) ) {
							$document_ids = array_filter( ! is_array( $document_ids ) ? explode( ',', $document_ids ) : $document_ids );
							if ( count( $document_ids ) > 1 ) {
								$text = sprintf(
								/* translators: 1: User full name, 2: Activity type. */
									__( '%1$s replied to your %2$s: some documents', 'buddyboss' ),
									$user_fullname,
									$notification_type_html
								);
							} else {
								$text = sprintf(
								/* translators: 1: User full name, 2: Activity type. */
									__( '%1$s replied to your %2$s: a document', 'buddyboss' ),
									$user_fullname,
									$notification_type_html
								);
							}
						} elseif ( ! empty( $video_ids ) ) {
							$video_ids = array_filter( ! is_array( $video_ids ) ? explode( ',', $video_ids ) : $video_ids );
							if ( count( $video_ids ) > 1 ) {
								$text = sprintf(
								/* translators: 1: User full name, 2: Activity type. */
									__( '%1$s replied to your %2$s: some videos', 'buddyboss' ),
									$user_fullname,
									$notification_type_html
								);
							} else {
								$text = sprintf(
								/* translators: 1: User full name, 2: Activity type. */
									__( '%1$s replied to your %2$s: a video', 'buddyboss' ),
									$user_fullname,
									$notification_type_html
								);
							}
						} elseif ( ! empty( $gif_data ) ) {
							$text = sprintf(
							/* translators: 1: User full name, 2: Activity type. */
								__( '%1$s replied to your %2$s: a gif', 'buddyboss' ),
								$user_fullname,
								$notification_type_html
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
								__( '%1$s replied: %2$s', 'buddyboss' ),
								$user_fullname,
								$activity_excerpt
							);
						} elseif ( $media_ids ) {
							$media_ids = array_filter( ! is_array( $media_ids ) ? explode( ',', $media_ids ) : $media_ids );
							if ( count( $media_ids ) > 1 ) {
								$text = sprintf(
								/* translators: User full name. */
									__( '%1$s replied: some photos', 'buddyboss' ),
									$user_fullname
								);
							} else {
								$text = sprintf(
								/* translators: User full name. */
									__( '%1$s replied: a photo', 'buddyboss' ),
									$user_fullname
								);
							}
						} elseif ( $document_ids ) {
							$document_ids = array_filter( ! is_array( $document_ids ) ? explode( ',', $document_ids ) : $document_ids );
							if ( count( $document_ids ) > 1 ) {
								$text = sprintf(
								/* translators: User full name. */
									__( '%1$s replied: some documents', 'buddyboss' ),
									$user_fullname
								);
							} else {
								$text = sprintf(
								/* translators: User full name. */
									__( '%1$s replied: a document', 'buddyboss' ),
									$user_fullname
								);
							}
						} elseif ( $video_ids ) {
							$video_ids = array_filter( ! is_array( $video_ids ) ? explode( ',', $video_ids ) : $video_ids );
							if ( count( $video_ids ) > 1 ) {
								$text = sprintf(
								/* translators: User full name. */
									__( '%1$s replied: some videos', 'buddyboss' ),
									$user_fullname
								);
							} else {
								$text = sprintf(
								/* translators: User full name. */
									__( '%1$s replied: a video', 'buddyboss' ),
									$user_fullname
								);
							}
						} elseif ( ! empty( $gif_data ) ) {
							$text = sprintf(
							/* translators: User full name. */
								__( '%1$s replied: a gif', 'buddyboss' ),
								$user_fullname
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
		$notification_tooltip_text = __( 'Requires following to enable', 'buddyboss' );

		if ( function_exists( 'bp_is_activity_follow_active' ) && true === bp_is_activity_follow_active() ) {
			$notification_tooltip_text = __( 'Required by activity follow', 'buddyboss' );
			$notification_read_only    = false;
		}

		$this->register_notification_type(
			'bb_activity_following_post',
			__( 'New post by a member you\'re following', 'buddyboss' ),
			esc_html__( 'A new post by someone a member is following', 'buddyboss' ),
			'activity',
			function_exists( 'bp_is_activity_follow_active' ) && true === bp_is_activity_follow_active(),
			$notification_read_only,
			$notification_tooltip_text
		);

		$this->register_email_type(
			'new-activity-following',
			array(
				/* translators: do not remove {} brackets or translate its contents. */
				'email_title'         => __( '[{{{site.name}}}] {{poster.name}} posted {{activity.type}}.', 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_content'       => __( "<a href=\"{{{poster.url}}}\">{{poster.name}}</a> posted {{activity.type}}:\n\n{{{activity.content}}}", 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_plain_content' => __( "{{poster.name}} posted {{activity.type}}:\n\n{{{activity.content}}}\\n\nView the post: {{{activity.url}}}", 'buddyboss' ),
				'situation_label'     => __( 'New activity post by someone a member is following', 'buddyboss' ),
				'unsubscribe_text'    => __( 'You will no longer receive emails when someone you are following posts an update.', 'buddyboss' ),
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
			esc_html__( 'New activity posts', 'buddyboss' ),
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
					'ending' => __( '&hellip;', 'buddyboss' ),
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
			$amount       = 'single';

			if ( 'web_push' === $screen ) {
				$notification_link = add_query_arg( 'rid', (int) $notification_id, bp_activity_get_permalink( $item_id ) );
				if ( ! empty( $activity_excerpt ) ) {
					$text = sprintf(
					/* translators: Activity content. */
						__( 'Posted an update: %s', 'buddyboss' ),
						$activity_excerpt
					);
				} elseif ( $media_ids ) {
					$media_ids = array_filter( ! is_array( $media_ids ) ? explode( ',', $media_ids ) : $media_ids );
					if ( count( $media_ids ) > 1 ) {
						$text = __( 'Posted some photos', 'buddyboss' );
					} else {
						$text = __( 'Posted a photo', 'buddyboss' );
					}
				} elseif ( $document_ids ) {
					$document_ids = array_filter( ! is_array( $document_ids ) ? explode( ',', $document_ids ) : $document_ids );
					if ( count( $document_ids ) > 1 ) {
						$text = __( 'Posted some documents', 'buddyboss' );
					} else {
						$text = __( 'Posted a document', 'buddyboss' );
					}
				} elseif ( $video_ids ) {
					$video_ids = array_filter( ! is_array( $video_ids ) ? explode( ',', $video_ids ) : $video_ids );
					if ( count( $video_ids ) > 1 ) {
						$text = __( 'Posted some videos', 'buddyboss' );
					} else {
						$text = __( 'Posted a video', 'buddyboss' );
					}
				} elseif ( ! empty( $gif_data ) ) {
					$text = __( 'Posted an update', 'buddyboss' );
				} else {
					$text = __( 'Posted an update', 'buddyboss' );
				}
			} else {
				if ( (int) $total_items > 1 ) {
					$notification_link = add_query_arg( 'type', $notification->component_action, $notification_link );
					$text              = sprintf(
						/* translators: %s: Total reply count. */
						__( 'You have %1$d new posts', 'buddyboss' ),
						(int) $total_items
					);
					$amount = 'multiple';
				} else {
					$notification_link = add_query_arg( 'rid', (int) $notification_id, bp_activity_get_permalink( $item_id ) );
					if ( ! empty( $activity_excerpt ) ) {
						$text = sprintf(
						/* translators: 1: User full name, 2: Activity content. */
							__( '%1$s posted an update: %2$s', 'buddyboss' ),
							$user_fullname,
							$activity_excerpt
						);
					} elseif ( $media_ids ) {
						$media_ids = array_filter( ! is_array( $media_ids ) ? explode( ',', $media_ids ) : $media_ids );
						if ( count( $media_ids ) > 1 ) {
							$text = sprintf(
							/* translators: User full name. */
								__( '%1$s posted some photos', 'buddyboss' ),
								$user_fullname
							);
						} else {
							$text = sprintf(
							/* translators: User full name. */
								__( '%1$s posted a photo', 'buddyboss' ),
								$user_fullname
							);
						}
					} elseif ( $document_ids ) {
						$document_ids = array_filter( ! is_array( $document_ids ) ? explode( ',', $document_ids ) : $document_ids );
						if ( count( $document_ids ) > 1 ) {
							$text = sprintf(
							/* translators: User full name. */
								__( '%1$s posted some documents', 'buddyboss' ),
								$user_fullname
							);
						} else {
							$text = sprintf(
							/* translators: User full name. */
								__( '%1$s posted a document', 'buddyboss' ),
								$user_fullname
							);
						}
					} elseif ( $video_ids ) {
						$video_ids = array_filter( ! is_array( $video_ids ) ? explode( ',', $video_ids ) : $video_ids );
						if ( count( $video_ids ) > 1 ) {
							$text = sprintf(
							/* translators: User full name. */
								__( '%1$s posted some videos', 'buddyboss' ),
								$user_fullname
							);
						} else {
							$text = sprintf(
							/* translators: User full name. */
								__( '%1$s posted a video', 'buddyboss' ),
								$user_fullname
							);
						}
					} elseif ( ! empty( $gif_data ) ) {
						$text = sprintf(
						/* translators: User full name. */
							__( '%1$s posted an update', 'buddyboss' ),
							$user_fullname
						);
					} else {
						$text = sprintf(
						/* translators: %s: User full name. */
							__( '%1$s posted an update', 'buddyboss' ),
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
		$notification_tooltip_text = __( 'Requires following to enable', 'buddyboss' );

		if ( function_exists( 'bp_is_activity_follow_active' ) && true === bp_is_activity_follow_active() ) {
			$notification_tooltip_text = __( 'Required by activity follow', 'buddyboss' );
			$notification_read_only    = false;
		}

		$this->register_notification_type(
			'bb_following_new',
			esc_html__( 'A member starts following you', 'buddyboss' ),
			esc_html__( 'A member is followed by someone', 'buddyboss' ),
			'activity',
			function_exists( 'bp_is_activity_follow_active' ) && true === bp_is_activity_follow_active(),
			$notification_read_only,
			$notification_tooltip_text
		);

		$this->register_email_type(
			'new-follower',
			array(
				/* translators: do not remove {} brackets or translate its contents. */
				'email_title'         => __( '[{{{site.name}}}] {{follower.name}} started following you', 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_content'       => __( "<a href=\"{{{follower.url}}}\">{{follower.name}}</a> started following you.\n\n{{{member.card}}}", 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_plain_content' => __( "{{follower.name}} started following you.\n\nTo learn more about them, visit their profile: {{{follower.url}}}", 'buddyboss' ),
				'situation_label'     => __( 'A member receives a new follower', 'buddyboss' ),
				'unsubscribe_text'    => __( 'You will no longer receive emails when someone follows you.', 'buddyboss' ),
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
			esc_html__( 'New followers', 'buddyboss' ),
			array( 'bb_following_new' ),
			17
		);
	}
}
