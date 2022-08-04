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
			$activity_excerpt = '"' . bp_create_excerpt(
					wp_strip_all_tags( $activity->content ),
					50,
					array(
						'ending' => __( '&hellip;', 'buddyboss' ),
					)
				) . '"';

			if ( '&nbsp;' === $activity_excerpt ) {
				$activity_excerpt = '';
			}

			if ( empty( $activity_excerpt ) && function_exists( 'bp_blogs_activity_comment_content_with_read_more' ) ) {
				$activity_excerpt = bp_blogs_activity_comment_content_with_read_more( '', $activity );

				$activity_excerpt = '"' . bp_create_excerpt(
						wp_strip_all_tags( $activity_excerpt ),
						50,
						array(
							'ending' => __( '&hellip;', 'buddyboss' ),
						)
					) . '"';

				if ( '&nbsp;' === $activity_excerpt ) {
					$activity_excerpt = '';
				}
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
				if ( ! empty( $notification_type_html ) ) {
					if ( ! empty( $activity_excerpt ) ) {
						$text = sprintf(
						/* translators: 1: Activity type, 2: Activity content. */
							__( 'Replied to your %1$s: %2$s', 'buddyboss' ),
							$notification_type_html,
							$activity_excerpt
						);
					} elseif ( $media_ids ) {
						$media_ids = array_filter( explode( ',', $media_ids ) );
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
						$document_ids = array_filter( explode( ',', $document_ids ) );
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
						$video_ids = array_filter( explode( ',', $video_ids ) );
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
						$media_ids = array_filter( explode( ',', $media_ids ) );
						if ( count( $media_ids ) > 1 ) {
							$text = __( 'Replied: some photos', 'buddyboss' );
						} else {
							$text = __( 'Replied: a photo', 'buddyboss' );
						}
					} elseif ( $document_ids ) {
						$document_ids = array_filter( explode( ',', $document_ids ) );
						if ( count( $document_ids ) > 1 ) {
							$text = __( 'Replied: some documents', 'buddyboss' );
						} else {
							$text = __( 'Replied: a document', 'buddyboss' );
						}
					} elseif ( $video_ids ) {
						$video_ids = array_filter( explode( ',', $video_ids ) );
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
							$media_ids = array_filter( explode( ',', $media_ids ) );
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
							$document_ids = array_filter( explode( ',', $document_ids ) );
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
							$video_ids = array_filter( explode( ',', $video_ids ) );
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
							$media_ids = array_filter( explode( ',', $media_ids ) );
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
							$document_ids = array_filter( explode( ',', $document_ids ) );
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
							$video_ids = array_filter( explode( ',', $video_ids ) );
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
}
