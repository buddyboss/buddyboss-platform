<?php
/**
 * BuddyBoss Messages Notification Class.
 *
 * @package BuddyBoss\Messages
 *
 * @since BuddyBoss 1.9.3
 */

defined( 'ABSPATH' ) || exit;

/**
 * Set up the BP_Messages_Notification class.
 *
 * @since BuddyBoss 1.9.3
 */
class BP_Messages_Notification extends BP_Core_Notification_Abstract {

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
	 * @return null|BP_Messages_Notification|Controller|object
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
			'messages',
			esc_html__( 'Private Messages', 'buddyboss' ),
			esc_html__( 'Private Messages', 'buddyboss' ),
			18
		);

		$this->register_notification_for_new_message();
	}

	/**
	 * Register notification for user new message.
	 *
	 * @since BuddyBoss 1.9.3
	 */
	public function register_notification_for_new_message() {
		$this->register_notification_type(
			'bb_messages_new',
			esc_html__( 'You receive a new private message', 'buddyboss' ),
			esc_html__( 'A member receives a new private message', 'buddyboss' ),
			'messages'
		);

		$this->register_email_type(
			'messages-unread',
			array(
				/* translators: do not remove {} brackets or translate its contents. */
				'email_title'         => __( '[{{{site.name}}}] New message from {{{sender.name}}}', 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_content'       => __( "{{{sender.name}}} sent you a message.\n\n{{{message}}}", 'buddyboss' ),
				/* translators: do not remove {} brackets or translate its contents. */
				'email_plain_content' => __( "{{{sender.name}}} sent you a message.\n\n{{{message}}}\"\n\nGo to the discussion to reply or catch up on the conversation: {{{message.url}}}", 'buddyboss' ),
				'situation_label'     => __( 'A member receives a new private message', 'buddyboss' ),
				'unsubscribe_text'    => __( 'You will no longer receive emails when someone sends you a message.', 'buddyboss' ),
			),
			'bb_messages_new'
		);

		if ( function_exists( 'bb_check_delay_email_notification' ) && bb_check_delay_email_notification() ) {
			$this->register_email_type(
				'messages-unread-digest',
				array(
					/* translators: do not remove {} brackets or translate its contents. */
					'email_title'         => __( '[{{{site.name}}}] You have {{{unread.count}}} unread messages', 'buddyboss' ),
					/* translators: do not remove {} brackets or translate its contents. */
					'email_content'       => __( "You have {{{unread.count}}} unread messages.\n\n{{{message}}}", 'buddyboss' ),
					/* translators: do not remove {} brackets or translate its contents. */
					'email_plain_content' => __( "You have {{{unread.count}}} unread messages.\n\n{{{message}}}\n\nGo to the discussion to reply or catch up on the conversation: {{{message.url}}}", 'buddyboss' ),
					'situation_label'     => __( 'A member receives a new private message', 'buddyboss' ),
					'unsubscribe_text'    => __( 'You will no longer receive emails when someone sends you a message.', 'buddyboss' ),
				),
				'bb_messages_new'
			);
		}

		$this->register_notification(
			'messages',
			'bb_messages_new',
			'bb_messages_new',
			'bb-icon-f bb-icon-comment'
		);

		$filter_types = array( 'bb_messages_new' );

		if ( true === bp_disable_group_messages() ) {
			$filter_types[] = 'bb_groups_new_message';
		}

		if ( ! (bool) bp_get_option( 'hide_message_notification', 1 ) ) {
			$this->register_notification_filter(
				esc_html__( 'New messages', 'buddyboss' ),
				$filter_types,
				30
			);
		}

		add_filter( 'bp_messages_bb_groups_new_message_notification', array( $this, 'bb_format_messages_notification' ), 10, 7 );
		add_filter( 'bp_messages_bb_messages_new_notification', array( $this, 'bb_format_messages_notification' ), 10, 7 );
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
	 * Format Messages notifications.
	 *
	 * @since BuddyBoss 1.9.3
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
	public function bb_format_messages_notification( $content, $item_id, $secondary_item_id, $total_items, $format, $notification_id, $screen ) {

		$notification = bp_notifications_get_notification( $notification_id );
		$total_items  = (int) $total_items;
		$text         = '';
		$link         = trailingslashit( bp_loggedin_user_domain() . bp_get_messages_slug() . '/inbox' );
		$amount       = 'single';
		$title        = bp_core_get_user_displayname( $secondary_item_id );

		if (
			! empty( $notification ) &&
			'messages' === $notification->component_name &&
			(
				'bb_groups_new_message' === $notification->component_action ||
				'bb_messages_new' === $notification->component_action
			)
		) {

			// Get message thread ID.
			$message   = new BP_Messages_Message( $item_id );
			$thread_id = $message->thread_id;
			$link      = ( ! empty( $thread_id ) ) ? bp_get_message_thread_view_link( $thread_id ) : false;

			$media_ids    = bp_messages_get_meta( $item_id, 'bp_media_ids', true );
			$document_ids = bp_messages_get_meta( $item_id, 'bp_document_ids', true );
			$video_ids    = bp_messages_get_meta( $item_id, 'bp_video_ids', true );
			$gif_data     = bp_messages_get_meta( $item_id, '_gif_data', true );
			$excerpt      = '';

			if ( ! empty( $message->message ) ) {
				$excerpt = wp_strip_all_tags( preg_replace( '#(<br\s*?\/?>|</(\w+)><(\w+)>)#', ' ', $message->message ) );
			}

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

			if ( 'web_push' === $screen ) {

				if ( ! empty( $thread_id ) ) {
					$link = bp_get_message_thread_view_link( $thread_id, $notification->user_id );
				}

				if ( ! empty( $excerpt ) ) {
					$text = sprintf(
					/* translators: excerpt */
						__( 'Sent you a message: %s', 'buddyboss' ),
						$excerpt
					);
				} elseif ( $media_ids ) {
					$media_ids = array_filter( ! is_array( $media_ids ) ? explode( ',', $media_ids ) : $media_ids );
					if ( count( $media_ids ) > 1 ) {
						$text = __( 'Sent you some photos', 'buddyboss' );
					} else {
						$text = __( 'Sent you a photo', 'buddyboss' );
					}
				} elseif ( $document_ids ) {
					$document_ids = array_filter( ! is_array( $document_ids ) ? explode( ',', $document_ids ) : $document_ids );
					if ( count( $document_ids ) > 1 ) {
						$text = __( 'Sent you some documents', 'buddyboss' );
					} else {
						$text = __( 'Sent you a document', 'buddyboss' );
					}
				} elseif ( $video_ids ) {
					$video_ids = array_filter( ! is_array( $video_ids ) ? explode( ',', $video_ids ) : $video_ids );
					if ( count( $video_ids ) > 1 ) {
						$text = __( 'Sent you some videos', 'buddyboss' );
					} else {
						$text = __( 'Sent you a video', 'buddyboss' );
					}
				} elseif ( ! empty( $gif_data ) ) {
					$text = __( 'Sent you a gif', 'buddyboss' );
				} else {
					$text = __( 'Sent you a message', 'buddyboss' );
				}

				if ( bp_is_active( 'groups' ) && true === bp_disable_group_messages() ) {

					$group        = bp_messages_get_meta( $item_id, 'group_id', true ); // group id.
					$message_from = bp_messages_get_meta( $item_id, 'message_from', true ); // group.
					$group_name   = bp_get_group_name( groups_get_group( $group ) );

					if ( ! empty( $message_from ) && 'group' === $message_from ) {
						$title = $group_name;
						if ( ! empty( $excerpt ) ) {
							$text = sprintf(
								/* translators: 1. user display name 2. excerpt */
								__( '%1$s sent a new message: %2$s', 'buddyboss' ),
								bp_core_get_user_displayname( $secondary_item_id ),
								$excerpt
							);
						} elseif ( $media_ids ) {
							$media_ids = array_filter( ! is_array( $media_ids ) ? explode( ',', $media_ids ) : $media_ids );
							if ( count( $media_ids ) > 1 ) {
								$text = sprintf(
									/* translators: user display name */
									__( '%s sent some photos', 'buddyboss' ),
									bp_core_get_user_displayname( $secondary_item_id )
								);
							} else {
								$text = sprintf(
									/* translators: 1. user display name */
									__( '%s sent a photo', 'buddyboss' ),
									bp_core_get_user_displayname( $secondary_item_id )
								);
							}
						} elseif ( $document_ids ) {
							$document_ids = array_filter( ! is_array( $document_ids ) ? explode( ',', $document_ids ) : $document_ids );
							if ( count( $document_ids ) > 1 ) {
								$text = sprintf(
									/* translators: user display name */
									__( '%s sent some documents', 'buddyboss' ),
									bp_core_get_user_displayname( $secondary_item_id )
								);
							} else {
								$text = sprintf(
									/* translators: user display name */
									__( '%1$s sent a document', 'buddyboss' ),
									bp_core_get_user_displayname( $secondary_item_id )
								);
							}
						} elseif ( $video_ids ) {
							$video_ids = array_filter( ! is_array( $video_ids ) ? explode( ',', $video_ids ) : $video_ids );
							if ( count( $video_ids ) > 1 ) {
								$text = sprintf(
									/* translators: user display name */
									__( '%1$s sent some videos', 'buddyboss' ),
									bp_core_get_user_displayname( $secondary_item_id )
								);
							} else {
								$text = sprintf(
									/* translators: user display name */
									__( '%s sent a video', 'buddyboss' ),
									bp_core_get_user_displayname( $secondary_item_id )
								);
							}
						} elseif ( ! empty( $gif_data ) ) {
							$text = sprintf(
								/* translators: user display name */
								__( '%s sent a GIF', 'buddyboss' ),
								bp_core_get_user_displayname( $secondary_item_id )
							);
						} else {
							$text = sprintf(
								/* translators: user display name */
								__( '%s sent a new message', 'buddyboss' ),
								bp_core_get_user_displayname( $secondary_item_id )
							);
						}
					}
				}
			} else {
				if ( $total_items > 1 ) {
					$amount = 'multiple';
					$text   = sprintf(
					/* translators: %d total items */
						__( 'You have %d new messages', 'buddyboss' ),
						$total_items
					);

				} else {
					if ( ! empty( $secondary_item_id ) ) {

						if ( bp_is_active( 'groups' ) && true === bp_disable_group_messages() ) {

							$group        = bp_messages_get_meta( $item_id, 'group_id', true ); // group id.
							$message_from = bp_messages_get_meta( $item_id, 'message_from', true ); // group.
							$group_name   = bp_get_group_name( groups_get_group( $group ) );

							if ( empty( $message_from ) ) {
								if ( ! empty( $excerpt ) ) {
									$text = sprintf(
									/* translators: 1. user display name 2. exceprt */
										esc_html__( '%1$s sent you a message: %2$s', 'buddyboss' ),
										bp_core_get_user_displayname( $secondary_item_id ),
										$excerpt
									);
								} elseif ( $media_ids ) {
									$media_ids = array_filter( ! is_array( $media_ids ) ? explode( ',', $media_ids ) : $media_ids );
									if ( count( $media_ids ) > 1 ) {
										$text = sprintf(
										/* translators: 1. user display name 2. photos text */
											esc_html__( '%1$s sent you %2$s', 'buddyboss' ),
											bp_core_get_user_displayname( $secondary_item_id ),
											esc_html__( 'some photos', 'buddyboss' )
										);
									} else {
										$text = sprintf(
										/* translators: 1. user display name 2. photo text */
											esc_html__( '%1$s sent you %2$s', 'buddyboss' ),
											bp_core_get_user_displayname( $secondary_item_id ),
											esc_html__( 'a photo', 'buddyboss' )
										);
									}
								} elseif ( $document_ids ) {
									$document_ids = array_filter( ! is_array( $document_ids ) ? explode( ',', $document_ids ) : $document_ids );
									if ( count( $document_ids ) > 1 ) {
										$text = sprintf(
										/* translators: 1. user display name 2. documents text */
											esc_html__( '%1$s sent you %2$s', 'buddyboss' ),
											bp_core_get_user_displayname( $secondary_item_id ),
											esc_html__( 'some documents', 'buddyboss' )
										);
									} else {
										$text = sprintf(
										/* translators: 1. user display name 2. document text */
											esc_html__( '%1$s sent you %2$s', 'buddyboss' ),
											bp_core_get_user_displayname( $secondary_item_id ),
											esc_html__( 'a document', 'buddyboss' )
										);
									}
								} elseif ( $video_ids ) {
									$video_ids = array_filter( ! is_array( $video_ids ) ? explode( ',', $video_ids ) : $video_ids );
									if ( count( $video_ids ) > 1 ) {
										$text = sprintf(
										/* translators: 1. user display name 2. videos text */
											esc_html__( '%1$s sent you %2$s', 'buddyboss' ),
											bp_core_get_user_displayname( $secondary_item_id ),
											esc_html__( 'some videos', 'buddyboss' )
										);
									} else {
										$text = sprintf(
										/* translators: 1. user display name 2. video text */
											esc_html__( '%1$s sent you %2$s', 'buddyboss' ),
											bp_core_get_user_displayname( $secondary_item_id ),
											esc_html__( 'a video', 'buddyboss' )
										);
									}
								} elseif ( ! empty( $gif_data ) ) {
									$text = sprintf(
									/* translators: 1. user display name 2. gif text */
										esc_html__( '%1$s sent you %2$s', 'buddyboss' ),
										bp_core_get_user_displayname( $secondary_item_id ),
										esc_html__( 'a gif', 'buddyboss' )
									);
								} else {
									$text = sprintf(
									/* translators: %1$s user display name */
										esc_html__( '%1$s sent you a message', 'buddyboss' ),
										bp_core_get_user_displayname( $secondary_item_id )
									);
								}
							} elseif ( 'group' === $message_from ) {
								if ( ! empty( $excerpt ) ) {
									$text = sprintf(
									/* translators: 1. user display name 2. group name 3. excerpt */
										__( '%1$s sent a message to %2$s: %3$s', 'buddyboss' ),
										bp_core_get_user_displayname( $secondary_item_id ),
										$group_name,
										$excerpt
									);
								} elseif ( $media_ids ) {
									$media_ids = array_filter( ! is_array( $media_ids ) ? explode( ',', $media_ids ) : $media_ids );
									if ( count( $media_ids ) > 1 ) {
										$text = sprintf(
										/* translators: 1. user display name 2. photos text 3. group name */
											__( '%1$s sent %2$s to %3$s', 'buddyboss' ),
											bp_core_get_user_displayname( $secondary_item_id ),
											esc_html__( 'some photos', 'buddyboss' ),
											$group_name
										);
									} else {
										$text = sprintf(
										/* translators: 1. user display name 2. photo text 3. group name */
											__( '%1$s sent %2$s to %3$s', 'buddyboss' ),
											bp_core_get_user_displayname( $secondary_item_id ),
											esc_html__( 'a photo', 'buddyboss' ),
											$group_name
										);
									}
								} elseif ( $document_ids ) {
									$document_ids = array_filter( ! is_array( $document_ids ) ? explode( ',', $document_ids ) : $document_ids );
									if ( count( $document_ids ) > 1 ) {
										$text = sprintf(
										/* translators: 1. user display name 2. documents text 3. group name */
											__( '%1$s sent %2$s to %3$s', 'buddyboss' ),
											bp_core_get_user_displayname( $secondary_item_id ),
											esc_html__( 'some documents', 'buddyboss' ),
											$group_name
										);
									} else {
										$text = sprintf(
										/* translators: 1. user display name 2. document text 3. group name */
											__( '%1$s sent %2$s to %3$s', 'buddyboss' ),
											bp_core_get_user_displayname( $secondary_item_id ),
											esc_html__( 'a document', 'buddyboss' ),
											$group_name
										);
									}
								} elseif ( $video_ids ) {
									$video_ids = array_filter( ! is_array( $video_ids ) ? explode( ',', $video_ids ) : $video_ids );
									if ( count( $video_ids ) > 1 ) {
										$text = sprintf(
										/* translators: 1. user display name 2. videos text 3. group name */
											__( '%1$s sent %2$s to %3$s', 'buddyboss' ),
											bp_core_get_user_displayname( $secondary_item_id ),
											esc_html__( 'some videos', 'buddyboss' ),
											$group_name
										);
									} else {
										$text = sprintf(
										/* translators: 1. user display name 2. video text 3. group name */
											__( '%1$s sent %2$s to %3$s', 'buddyboss' ),
											bp_core_get_user_displayname( $secondary_item_id ),
											esc_html__( 'a video', 'buddyboss' ),
											$group_name
										);
									}
								} elseif ( ! empty( $gif_data ) ) {
									$text = sprintf(
									/* translators: 1. user display name 2. gif text 3. group name */
										__( '%1$s sent %2$s to %3$s', 'buddyboss' ),
										bp_core_get_user_displayname( $secondary_item_id ),
										esc_html__( 'a gif', 'buddyboss' ),
										$group_name
									);
								} else {
									$text = sprintf(
									/* translators: 1. user display name 2. group name */
										__( '%1$s sent a message to %2$s', 'buddyboss' ),
										bp_core_get_user_displayname( $secondary_item_id ),
										$group_name
									);
								}
							} else {
								if ( ! empty( $excerpt ) ) {
									$text = sprintf(
									/* translators: 1. user display name 2. exceprt */
										esc_html__( '%1$s sent you a message: %2$s', 'buddyboss' ),
										bp_core_get_user_displayname( $secondary_item_id ),
										$excerpt
									);
								} elseif ( $media_ids ) {
									$media_ids = array_filter( ! is_array( $media_ids ) ? explode( ',', $media_ids ) : $media_ids );
									if ( count( $media_ids ) > 1 ) {
										$text = sprintf(
										/* translators: 1. user display name 2. photos text */
											esc_html__( '%1$s sent you %2$s', 'buddyboss' ),
											bp_core_get_user_displayname( $secondary_item_id ),
											esc_html__( 'some photos', 'buddyboss' )
										);
									} else {
										$text = sprintf(
										/* translators: 1. user display name 2. photo text */
											esc_html__( '%1$s sent you %2$s', 'buddyboss' ),
											bp_core_get_user_displayname( $secondary_item_id ),
											esc_html__( 'a photo', 'buddyboss' )
										);
									}
								} elseif ( $document_ids ) {
									$document_ids = array_filter( ! is_array( $document_ids ) ? explode( ',', $document_ids ) : $document_ids );
									if ( count( $document_ids ) > 1 ) {
										$text = sprintf(
										/* translators: 1. user display name 2. documents text */
											esc_html__( '%1$s sent you %2$s', 'buddyboss' ),
											bp_core_get_user_displayname( $secondary_item_id ),
											esc_html__( 'some documents', 'buddyboss' )
										);
									} else {
										$text = sprintf(
										/* translators: 1. user display name 2. document text */
											esc_html__( '%1$s sent you %2$s', 'buddyboss' ),
											bp_core_get_user_displayname( $secondary_item_id ),
											esc_html__( 'a document', 'buddyboss' )
										);
									}
								} elseif ( $video_ids ) {
									$video_ids = array_filter( ! is_array( $video_ids ) ? explode( ',', $video_ids ) : $video_ids );
									if ( count( $video_ids ) > 1 ) {
										$text = sprintf(
										/* translators: 1. user display name 2. videos text */
											esc_html__( '%1$s sent you %2$s', 'buddyboss' ),
											bp_core_get_user_displayname( $secondary_item_id ),
											esc_html__( 'some videos', 'buddyboss' )
										);
									} else {
										$text = sprintf(
										/* translators: 1. user display name 2. video text */
											esc_html__( '%1$s sent you %2$s', 'buddyboss' ),
											bp_core_get_user_displayname( $secondary_item_id ),
											esc_html__( 'a video', 'buddyboss' )
										);
									}
								} elseif ( ! empty( $gif_data ) ) {
									$text = sprintf(
									/* translators: 1. user display name 2. gif text */
										esc_html__( '%1$s sent you %2$s', 'buddyboss' ),
										bp_core_get_user_displayname( $secondary_item_id ),
										esc_html__( 'a gif', 'buddyboss' )
									);
								} else {
									$text = sprintf(
									/* translators: %1$s user display name */
										esc_html__( '%1$s sent you a message', 'buddyboss' ),
										bp_core_get_user_displayname( $secondary_item_id )
									);
								}
							}
						} else {

							if ( ! empty( $excerpt ) ) {
								$text = sprintf(
								/* translators: 1. user display name 2. except text */
									esc_html__( '%1$s sent you a message: %2$s', 'buddyboss' ),
									bp_core_get_user_displayname( $secondary_item_id ),
									$excerpt
								);
							} elseif ( $media_ids ) {
								$media_ids = array_filter( ! is_array( $media_ids ) ? explode( ',', $media_ids ) : $media_ids );
								if ( count( $media_ids ) > 1 ) {
									$text = sprintf(
									/* translators: 1. user display name 2. photos text */
										esc_html__( '%1$s sent you %2$s', 'buddyboss' ),
										bp_core_get_user_displayname( $secondary_item_id ),
										esc_html__( 'some photos', 'buddyboss' )
									);
								} else {
									$text = sprintf(
									/* translators: 1. user display name 2. photo text */
										esc_html__( '%1$s sent you %2$s', 'buddyboss' ),
										bp_core_get_user_displayname( $secondary_item_id ),
										esc_html__( 'a photo', 'buddyboss' )
									);
								}
							} elseif ( $document_ids ) {
								$document_ids = array_filter( ! is_array( $document_ids ) ? explode( ',', $document_ids ) : $document_ids );
								if ( count( $document_ids ) > 1 ) {
									$text = sprintf(
									/* translators: 1. user display name 2. documents text */
										esc_html__( '%1$s sent you %2$s', 'buddyboss' ),
										bp_core_get_user_displayname( $secondary_item_id ),
										esc_html__( 'some documents', 'buddyboss' )
									);
								} else {
									$text = sprintf(
									/* translators: 1. user display name 2. document text */
										esc_html__( '%1$s sent you %2$s', 'buddyboss' ),
										bp_core_get_user_displayname( $secondary_item_id ),
										esc_html__( 'a document', 'buddyboss' )
									);
								}
							} elseif ( $video_ids ) {
								$video_ids = array_filter( ! is_array( $video_ids ) ? explode( ',', $video_ids ) : $video_ids );
								if ( count( $video_ids ) > 1 ) {
									$text = sprintf(
									/* translators: 1. user display name 2. videos text */
										esc_html__( '%1$s sent you %2$s', 'buddyboss' ),
										bp_core_get_user_displayname( $secondary_item_id ),
										esc_html__( 'some videos', 'buddyboss' )
									);
								} else {
									$text = sprintf(
									/* translators: 1. user display name 2. video text */
										esc_html__( '%1$s sent you %2$s', 'buddyboss' ),
										bp_core_get_user_displayname( $secondary_item_id ),
										esc_html__( 'a video', 'buddyboss' )
									);
								}
							} elseif ( ! empty( $gif_data ) ) {
								$text = sprintf(
								/* translators: 1. user display name 2. gif text */
									esc_html__( '%1$s sent you %2$s', 'buddyboss' ),
									bp_core_get_user_displayname( $secondary_item_id ),
									esc_html__( 'a gif', 'buddyboss' )
								);
							} else {
								$text = sprintf(
								/* translators: %1$s user display name */
									esc_html__( '%1$s sent you a message', 'buddyboss' ),
									bp_core_get_user_displayname( $secondary_item_id )
								);
							}
						}
					} else {

						if ( ! empty( $excerpt ) ) {
							$text = sprintf(
							/* translators: 1. user display name 2. gif text */
								esc_html__( '%1$s sent you a message: %2$s', 'buddyboss' ),
								bp_core_get_user_displayname( $secondary_item_id ),
								$excerpt
							);
						} elseif ( $media_ids ) {
							$media_ids = array_filter( ! is_array( $media_ids ) ? explode( ',', $media_ids ) : $media_ids );
							if ( count( $media_ids ) > 1 ) {
								$text = sprintf(
								/* translators: 1. user display name 2. photos text */
									esc_html__( '%1$s sent you %2$s', 'buddyboss' ),
									bp_core_get_user_displayname( $secondary_item_id ),
									esc_html__( 'some photos', 'buddyboss' )
								);
							} else {
								$text = sprintf(
								/* translators: 1. user display name 2. photo text */
									esc_html__( '%1$s sent you %2$s', 'buddyboss' ),
									bp_core_get_user_displayname( $secondary_item_id ),
									esc_html__( 'a photo', 'buddyboss' )
								);
							}
						} elseif ( $document_ids ) {
							$document_ids = array_filter( ! is_array( $document_ids ) ? explode( ',', $document_ids ) : $document_ids );
							if ( count( $document_ids ) > 1 ) {
								$text = sprintf(
								/* translators: 1. user display name 2. documents text */
									esc_html__( '%1$s sent you %2$s', 'buddyboss' ),
									bp_core_get_user_displayname( $secondary_item_id ),
									esc_html__( 'some documents', 'buddyboss' )
								);
							} else {
								$text = sprintf(
								/* translators: 1. user display name 2. document text */
									esc_html__( '%1$s sent you %2$s', 'buddyboss' ),
									bp_core_get_user_displayname( $secondary_item_id ),
									esc_html__( 'a document', 'buddyboss' )
								);
							}
						} elseif ( $video_ids ) {
							$video_ids = array_filter( ! is_array( $video_ids ) ? explode( ',', $video_ids ) : $video_ids );
							if ( count( $video_ids ) > 1 ) {
								$text = sprintf(
								/* translators: 1. user display name 2. videos text */
									esc_html__( '%1$s sent you %2$s', 'buddyboss' ),
									bp_core_get_user_displayname( $secondary_item_id ),
									esc_html__( 'some videos', 'buddyboss' )
								);
							} else {
								$text = sprintf(
								/* translators: 1. user display name 2. video text */
									esc_html__( '%1$s sent you %2$s', 'buddyboss' ),
									bp_core_get_user_displayname( $secondary_item_id ),
									esc_html__( 'a video', 'buddyboss' )
								);
							}
						} elseif ( ! empty( $gif_data ) ) {
							$text = sprintf(
							/* translators: 1. user display name 2. gif text */
								esc_html__( '%1$s sent you %2$s', 'buddyboss' ),
								bp_core_get_user_displayname( $secondary_item_id ),
								esc_html__( 'a gif', 'buddyboss' )
							);
						} else {
							$text = sprintf(
							/* translators: %1$s user display name */
								esc_html__( '%1$s sent you a message', 'buddyboss' ),
								bp_core_get_user_displayname( $secondary_item_id )
							);
						}
					}
				}
			}
		}

		$content = apply_filters(
			'bb_messages_' . $amount . '_' . $notification->component_action . '_notification',
			array(
				'link'  => $link,
				'text'  => $text,
				'title' => $title,
				'image' => bb_notification_avatar_url( $notification ),
			),
			$notification,
			$text,
			$link,
			$screen
		);

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
