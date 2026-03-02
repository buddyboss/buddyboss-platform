<?php
/**
 * ReadyLaunch - Header Unread Messages template.
 *
 * This template handles displaying unread messages in the header dropdown.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

global $messages_template;

$recipients       = array();
$recipient_names  = array();
$excerpt          = '';
$last_message_id  = 0;
$first_message_id = 0;

if ( bp_has_message_threads( bp_ajax_querystring( 'messages' ) . '&user_id=' . get_current_user_id() ) ) :

	while ( bp_message_threads() ) :
		bp_message_thread();

		$excerpt         = wp_strip_all_tags( bp_create_excerpt( $messages_template->thread->last_message_content, 50, array( 'ending' => '&hellip;' ) ) );
		$last_message_id = (int) $messages_template->thread->last_message_id;

		$group_id = bp_messages_get_meta( $last_message_id, 'group_id', true );
		if ( 0 === $last_message_id && ! $group_id ) {
			$first_message           = BP_Messages_Thread::get_first_message( bp_get_message_thread_id() );
			$group_message_thread_id = bp_messages_get_meta( $first_message->id, 'group_message_thread_id', true ); // group.
			$group_id                = (int) bp_messages_get_meta( $first_message->id, 'group_id', true );
		}

		$group_name                = '';
		$group_avatar              = '';
		$group_link                = '';
		$group_message_users       = '';
		$group_message_type        = '';
		$group_message_thread_type = '';
		$group_message_fresh       = '';

		$is_deleted_group = 0;
		if ( ! empty( $group_id ) ) {
			$group_message_users       = bp_messages_get_meta( $last_message_id, 'group_message_users', true );
			$group_message_type        = bp_messages_get_meta( $last_message_id, 'group_message_type', true );
			$group_message_thread_type = bp_messages_get_meta( $last_message_id, 'group_message_thread_type', true );
			$group_message_fresh       = bp_messages_get_meta( $last_message_id, 'group_message_fresh', true );
			$message_from              = bp_messages_get_meta( $last_message_id, 'message_from', true );

			if ( bp_is_active( 'groups' ) ) {
				$group_name = bp_get_group_name( groups_get_group( $group_id ) );
				if ( empty( $group_name ) ) {
					$group_link = 'javascript:void(0);';
				} else {
					$group_link = bp_get_group_permalink( groups_get_group( $group_id ) );
				}

				if ( function_exists( 'bp_disable_group_avatar_uploads' ) && bp_disable_group_avatar_uploads() && function_exists( 'bb_get_buddyboss_group_avatar' ) ) {
					$group_avatar = bb_get_buddyboss_group_avatar();
				} else {
					$group_avatar = bp_core_fetch_avatar(
						array(
							'item_id'    => $group_id,
							'object'     => 'group',
							'type'       => 'full',
							'avatar_dir' => 'group-avatars',
							/* translators: %s: Group name */
							'alt'        => sprintf( __( 'Group logo of %s', 'buddyboss' ), $group_name ),
							'title'      => $group_name,
							'html'       => false,
						)
					);
				}
			} else {

				$prefix       = apply_filters( 'bp_core_get_table_prefix', $wpdb->base_prefix );
				$groups_table = $prefix . 'bp_groups';
				$group_name   = $wpdb->get_var( "SELECT `name` FROM `{$groups_table}` WHERE `id` = '{$group_id}';" ); // db call ok; no-cache ok.
				$group_link   = 'javascript:void(0);';

				if ( ! empty( $group_name ) && ( ! function_exists( 'bp_disable_group_avatar_uploads' ) || ( function_exists( 'bp_disable_group_avatar_uploads' ) && ! bp_disable_group_avatar_uploads() ) ) ) {
					$directory                = 'group-avatars';
					$avatar_size              = '-bpfull';
					$legacy_group_avatar_name = '-groupavatar-full';
					$legacy_user_avatar_name  = '-avatar2';
					$avatar_folder_dir        = bp_core_avatar_upload_path() . '/' . $directory . '/' . $group_id;
					$avatar_folder_url        = bp_core_avatar_url() . '/' . $directory . '/' . $group_id;

					if ( file_exists( $avatar_folder_dir ) ) {

						$group_avatar = '';

						// Open directory.
						if ( $av_dir = opendir( $avatar_folder_dir ) ) {

							// Stash files in an array once to check for one that matches.
							$avatar_files = array();
							while ( false !== ( $avatar_file = readdir( $av_dir ) ) ) {
								// Only add files to the array (skip directories).
								if ( 2 < strlen( $avatar_file ) ) {
									$avatar_files[] = $avatar_file;
								}
							}

							// Check for array.
							if ( 0 < count( $avatar_files ) ) {

								// Check for current avatar.
								foreach ( $avatar_files as $key => $value ) {
									if ( strpos( $value, $avatar_size ) !== false ) {
										$group_avatar = $avatar_folder_url . '/' . $avatar_files[ $key ];
									}
								}

								// Legacy avatar check.
								if ( ! isset( $group_avatar ) ) {
									foreach ( $avatar_files as $key => $value ) {
										if ( strpos( $value, $legacy_user_avatar_name ) !== false ) {
											$group_avatar = $avatar_folder_url . '/' . $avatar_files[ $key ];
										}
									}

									// Legacy group avatar check.
									if ( ! isset( $group_avatar ) ) {
										foreach ( $avatar_files as $key => $value ) {
											if ( strpos( $value, $legacy_group_avatar_name ) !== false ) {
												$group_avatar = $avatar_folder_url . '/' . $avatar_files[ $key ];
											}
										}
									}
								}
							}
						}
						// Close the avatar directory.
						closedir( $av_dir );
					}
				} elseif ( function_exists( 'bb_attachments_get_default_profile_group_avatar_image' ) && ( function_exists( 'bp_disable_group_avatar_uploads' ) && ! bp_disable_group_avatar_uploads() ) ) {
					$group_avatar = bb_attachments_get_default_profile_group_avatar_image( array( 'object' => 'group' ) );
				} elseif ( function_exists( 'bb_get_buddyboss_group_avatar' ) && ( function_exists( 'bp_disable_group_avatar_uploads' ) && bp_disable_group_avatar_uploads() ) ) {
					$group_avatar = bb_get_buddyboss_group_avatar();
				}
			}

			$is_deleted_group = ( empty( $group_name ) ) ? 1 : 0;
			$group_name       = ( empty( $group_name ) ) ? __( 'Deleted Group', 'buddyboss' ) : $group_name;

		}

		$is_group_thread = 0;
		if ( (int) $group_id > 0 ) {

			$first_message           = BP_Messages_Thread::get_first_message( bp_get_message_thread_id() );
			$group_message_thread_id = bp_messages_get_meta( $first_message->id, 'group_message_thread_id', true ); // group.
			$group_id                = (int) bp_messages_get_meta( $first_message->id, 'group_id', true );
			$message_users           = bp_messages_get_meta( $first_message->id, 'group_message_users', true ); // all - individual.
			$message_type            = bp_messages_get_meta( $first_message->id, 'group_message_type', true );  // open - private.
			$message_from            = bp_messages_get_meta( $first_message->id, 'message_from', true );        // group.

			if ( 'group' === $message_from && bp_get_message_thread_id() === (int) $group_message_thread_id && 'all' === $message_users && 'open' === $message_type ) {
				$is_group_thread = 1;
			}
		}

		// Fetch all recipient.
		$messages_template->thread->recipients = $messages_template->thread->get_recipients();

		if ( function_exists( 'bb_messages_user_can_send_message' ) ) {
			$recipient_ids = wp_list_pluck( (array) $messages_template->thread->recipients, 'user_id' );
			$can_message   = bb_messages_user_can_send_message(
				array(
					'sender_id'     => bp_loggedin_user_id(),
					'recipients_id' => $recipient_ids,
					'thread_id'     => $messages_template->thread->thread_id,
				)
			);
		} else {
			$can_message        = ( $is_group_thread || bp_current_user_can( 'bp_moderate' ) ) ? true : apply_filters( 'bb_can_user_send_message_in_thread', true, $messages_template->thread->thread_id, (array) $messages_template->thread->recipients );
			$is_check_un_access = $can_message && ! $is_group_thread && bp_is_active( 'friends' ) && bp_force_friendship_to_message();
			$un_access_users    = false;
		}

		$recipients       = array();
		$other_recipients = array();
		$current_user     = false;
		if ( is_array( $messages_template->thread->recipients ) && ! $is_group_thread ) {
			foreach ( $messages_template->thread->recipients as $recipient ) {
				if ( empty( $recipient->is_deleted ) ) {
					$is_you         = bp_loggedin_user_id() === $recipient->user_id;
					$recipient_data = array(
						'avatar'             => esc_url(
							bp_core_fetch_avatar(
								array(
									'item_id' => $recipient->user_id,
									'object'  => 'user',
									'type'    => 'thumb',
									'width'   => BP_AVATAR_THUMB_WIDTH,
									'height'  => BP_AVATAR_THUMB_HEIGHT,
									'html'    => false,
								)
							)
						),
						'user_link'          => bp_core_get_userlink( $recipient->user_id, false, true ),
						'user_name'          => bp_core_get_user_displayname( $recipient->user_id ),
						'is_you'             => $is_you,
						'is_user_suspended'  => function_exists( 'bp_moderation_is_user_suspended' ) ? bp_moderation_is_user_suspended( $recipient->user_id ) : false,
						'is_user_blocked'    => function_exists( 'bp_moderation_is_user_blocked' ) ? bp_moderation_is_user_blocked( $recipient->user_id ) : false,
						'is_user_blocked_by' => function_exists( 'bb_moderation_is_user_blocked_by' ) ? bb_moderation_is_user_blocked_by( $recipient->user_id ) : false,
						'is_deleted'         => empty( get_userdata( $recipient->user_id ) ) ? 1 : 0,
					);
					$recipients[]   = $recipient_data;

					if ( ! $is_you ) {
						$other_recipients[] = $recipient_data;
					} else {
						$current_user = $recipient_data;
					}

					if (
						! function_exists( 'bb_messages_user_can_send_message' ) &&
						$is_check_un_access &&
						bp_loggedin_user_id() !== $recipient->user_id &&
						true !== $un_access_users
					) {
						if ( ! friends_check_friendship( bp_loggedin_user_id(), $recipient->user_id ) ) {
							$un_access_users = true;
						}
					}
				}
			}
		}

		if ( ! empty( $is_check_un_access ) && ! empty( $un_access_users ) ) {
			$can_message = false;
		}

		$include_you = count( $other_recipients ) >= 2;
		$first_three = array_slice( $other_recipients, 0, 3 );
		if ( count( $first_three ) === 0 ) {
			$include_you = true;
		}

		$unread_class = '';
		if ( bp_displayed_user_id() === bp_loggedin_user_id() ) {
			$unread_class = bp_message_thread_has_unread() ? 'unread' : '';
		} elseif ( function_exists( 'bb_get_thread_messages_unread_count' ) && 0 < bb_get_thread_messages_unread_count( bp_get_message_thread_id() ) ) {
			$unread_class = 'unread';
		}
		?>

		<li class="read-item <?php echo esc_attr( $unread_class ); ?>" data-thread-id="<?php echo esc_attr( bp_get_message_thread_id() ); ?>">
			<span class="bb-full-link">
				<a href="<?php bp_message_thread_view_link( bp_get_message_thread_id() ); ?>">
					<?php bp_message_thread_subject(); ?>
				</a>
			</span>

			<?php
			if ( function_exists( 'bp_messages_get_avatars' ) && ! empty( bp_messages_get_avatars( bp_get_message_thread_id(), get_current_user_id() ) ) ) {
				$avatars          = bp_messages_get_avatars( bp_get_message_thread_id(), get_current_user_id() );
				$moderation_class = function_exists( 'bp_moderation_is_user_suspended' ) && bp_moderation_is_user_suspended( $avatars[0]['id'] ) ? 'bp-user-suspended' : '';
				$moderation_class = function_exists( 'bp_moderation_is_user_blocked' ) && bp_moderation_is_user_blocked( $avatars[0]['id'] ) ? $moderation_class . ' bp-user-blocked' : $moderation_class;
				if ( ! empty( $moderation_class ) ) {
					$can_message = false;
				}
				$moderation_class = function_exists( 'bb_moderation_is_user_blocked_by' ) && bb_moderation_is_user_blocked_by( $avatars[0]['id'] ) ? $moderation_class . ' bp-user-blocked-by' : $moderation_class;
				?>
				<div class="notification-avatar <?php echo( 1 === count( $avatars ) && 'user' === $avatars[0]['type'] ? esc_attr( $moderation_class ) : '' ); ?>">
					<a href="<?php bp_message_thread_view_link( bp_get_message_thread_id() ); ?>">
						<?php
						if ( 1 === count( $avatars ) && 'user' === $avatars[0]['type'] && function_exists( 'bb_user_presence_html' ) ) {
							bb_user_presence_html( $avatars[0]['id'] );
						}
						if ( count( $avatars ) > 1 ) {
							echo '<div class="thread-multiple-avatar">';
						}
						foreach ( $avatars as $avatar ) {
							echo '<img src="' . esc_url( $avatar['url'] ) . '" alt="' . esc_attr( $avatar['name'] ) . '" />';
							if ( 1 === count( $avatars ) && isset( $avatar['is_deleted'] ) && 0 === $avatar['is_deleted'] ) { // Check user should not be deleted.
								if ( isset( $avatar['is_user_blocked'] ) && true === $avatar['is_user_blocked'] ) {
									echo '<i class="bb-icon-f bb-icon-cancel"></i>';
								} elseif ( ( isset( $avatar['is_user_blocked_by'] ) && true === $avatar['is_user_blocked_by'] ) || ! $can_message ) {
									echo '<i class="bb-icon-f bb-icon-lock"></i>';
								}
							}
						}
						if ( count( $avatars ) > 1 ) {
							echo '</div>';
						}

						?>
					</a>
				</div>
				<?php
			} elseif ( $is_group_thread ) {
				?>
				<div class="notification-avatar">
					<a href="<?php bp_message_thread_view_link( bp_get_message_thread_id() ); ?>">
						<img src="<?php echo esc_url( $group_avatar ); ?>"> </a>
				</div>
				<?php
			} else {
				?>
				<div class="notification-avatar">
					<?php
					if ( count( $other_recipients ) > 1 ) {
						?>
						<a href="<?php echo esc_url( bp_core_get_user_domain( $messages_template->thread->last_sender_id ) ); ?>">
							<?php bp_message_thread_avatar(); ?>
						</a>
						<?php
					} else {
						$recipient = ! empty( $first_three[0] ) ? $first_three[0] : $current_user;

						// If user suspended.
						if ( isset( $recipient['is_user_suspended'] ) && true === $recipient['is_user_suspended'] ) {
							$can_message = false;
						}

						// If user blocked.
						if ( isset( $recipient['is_user_blocked'] ) && true === $recipient['is_user_blocked'] ) {
							$can_message = false;
						}
						?>
						<a href="<?php echo esc_url( $recipient['user_link'] ); ?>">
							<img class="avatar" src="<?php echo esc_url( $recipient['avatar'] ); ?>" alt="<?php echo esc_attr( $recipient['user_name'] ); ?>" />
							<?php
							if ( isset( $recipient['is_deleted'] ) && 0 === $recipient['is_deleted'] ) {
								if ( isset( $recipient['is_user_blocked'] ) && true === $recipient['is_user_blocked'] ) {
									echo '<i class="bb-icon-f bb-icon-cancel"></i>';
								} elseif ( ( isset( $recipient['is_user_blocked_by'] ) && true === $recipient['is_user_blocked_by'] ) || ! $can_message ) {
									echo '<i class="bb-icon-f bb-icon-lock"></i>';
								}
							}
							?>
						</a>
						<?php
					}
					?>
				</div>
				<?php
			}
			?>
			<div class="notification-content">
				<span class="bb-full-link">
					<a href="<?php bp_message_thread_view_link( bp_get_message_thread_id() ); ?>">
						<?php bp_message_thread_subject(); ?>
					</a>
				</span>

				<?php
				if ( $is_group_thread ) {
					?>
					<span class="notification-users">
						<a href="<?php bp_message_thread_view_link( bp_get_message_thread_id() ); ?>">
							<?php
							echo esc_html( ucwords( $group_name ) );
							?>
						</a>
					</span>
					<?php
				} else {
					?>
					<span class="notification-users">
						<a href="<?php bp_message_thread_view_link( bp_get_message_thread_id() ); ?>">
							<?php
							$recipients      = (array) $messages_template->thread->recipients;
							$recipient_names = array();

							$i = 1;
							foreach ( $recipients as $recipient ) :
								if ( bp_loggedin_user_id() !== (int) $recipient->user_id ) :
									++$i;
									$recipient_name = bp_core_get_user_displayname( $recipient->user_id );

									if ( empty( $recipient_name ) ) :
										$recipient_name = esc_html__( 'Deleted User', 'buddyboss' );
									endif;

									if ( bp_is_active( 'moderation' ) ) {
										if ( function_exists( 'bp_moderation_is_user_suspended' ) && bp_moderation_is_user_suspended( $recipient->user_id ) ) {
											$recipient_name = function_exists( 'bb_moderation_is_suspended_label' ) ? bb_moderation_is_suspended_label( $recipient->user_id ) : esc_html__( 'Suspended Member', 'buddyboss' );
										} elseif ( function_exists( 'bp_moderation_is_user_blocked' ) && bp_moderation_is_user_blocked( $recipient->user_id ) ) {
											$recipient_name = function_exists( 'bb_moderation_has_blocked_label' ) ? bb_moderation_has_blocked_label( $recipient_name, $recipient->user_id ) : esc_html__( 'Blocked Member', 'buddyboss' );
										}
									}

									$recipient_names[] = ( $recipient_name ) ? ucwords( $recipient_name ) : '';

									if ( $i > 4 ) {
										break;
									}
								endif;
							endforeach;

							if ( ! empty( $recipients ) && count( $recipients ) > 5 ) {
								$recipient_names[] = esc_html__( 'others', 'buddyboss' );
							}
							echo esc_html( ! empty( $recipient_names ) ? implode( ', ', $recipient_names ) : '' );
							?>
						</a>
					</span>
					<?php
				}
				?>
				<span class="typing-indicator bp-hide"></span>
				<span class="posted">
					<?php
					if ( bp_is_active( 'moderation' ) ) {
						$is_last_sender_suspended  = function_exists( 'bp_moderation_is_user_suspended' ) ? bp_moderation_is_user_suspended( $messages_template->thread->last_sender_id ) : false;
						$is_last_sender_blocked    = function_exists( 'bp_moderation_is_user_blocked' ) ? bp_moderation_is_user_blocked( $messages_template->thread->last_sender_id ) : false;
						$is_last_sender_blocked_by = function_exists( 'bb_moderation_is_user_blocked_by' ) ? bb_moderation_is_user_blocked_by( $messages_template->thread->last_sender_id ) : false;
					}

					$send_media = false;

					$exerpt = wp_strip_all_tags( bp_create_excerpt( preg_replace( '#(<br\s*?\/?>|</(\w+)><(\w+)>)#', ' ', $messages_template->thread->last_message_content ), 50, array( 'ending' => '&hellip;' ) ) );

					if ( empty( $exerpt ) && function_exists( 'buddypress' ) && bp_is_active( 'media' ) ) :
						if ( bp_is_messages_media_support_enabled() ) :
							$media_ids = bp_messages_get_meta( $last_message_id, 'bp_media_ids', true );

							if ( ! empty( $media_ids ) ) :
								$media_ids = explode( ',', $media_ids );

								if ( count( $media_ids ) < 2 ) :
									$send_media = true;
									$exerpt     = esc_html__( 'Sent a photo', 'buddyboss' );
								else :
									$send_media = true;
									$exerpt     = esc_html__( 'Sent some photos', 'buddyboss' );
								endif;
							endif;
						endif;

						if ( function_exists( 'bp_is_messages_video_support_enabled' ) && bp_is_messages_video_support_enabled() ) :
							$video_ids = bp_messages_get_meta( $last_message_id, 'bp_video_ids', true );

							if ( ! empty( $video_ids ) ) :
								$video_ids = explode( ',', $video_ids );

								if ( count( $video_ids ) < 2 ) :
									$send_media = true;
									$exerpt     = esc_html__( 'Sent a video', 'buddyboss' );
								else :
									$send_media = true;
									$exerpt     = esc_html__( 'Sent some videos', 'buddyboss' );
								endif;
							endif;
						endif;

						if ( function_exists( 'bp_is_messages_document_support_enabled' ) && bp_is_messages_document_support_enabled() ) :
							$document_ids = bp_messages_get_meta( $last_message_id, 'bp_document_ids', true );

							if ( ! empty( $document_ids ) ) :
								$document_ids = explode( ',', $document_ids );

								if ( count( $document_ids ) < 2 ) :
									$send_media = true;
									$exerpt     = esc_html__( 'Sent a document', 'buddyboss' );
								else :
									$send_media = true;
									$exerpt     = esc_html__( 'Sent some documents', 'buddyboss' );
								endif;
							endif;
						endif;

						if ( bp_is_messages_gif_support_enabled() ) :
							$gif_data = bp_messages_get_meta( $last_message_id, '_gif_data', true );

							if ( ! empty( $gif_data ) ) :
								$send_media = true;
								$exerpt     = esc_html__( 'Sent a gif', 'buddyboss' );
							endif;
						endif;
					endif;

					if ( bp_is_active( 'moderation' ) ) {
						if ( $is_last_sender_suspended ) {
							$exerpt = bb_moderation_is_suspended_message( $exerpt, BP_Moderation_Message::$moderation_type, $last_message_id );
						} elseif ( $is_last_sender_blocked_by ) {
							$exerpt = bb_moderation_is_blocked_message( $exerpt, BP_Moderation_Message::$moderation_type, $last_message_id );
						} elseif ( $is_last_sender_blocked ) {
							$exerpt = bb_moderation_has_blocked_message( $exerpt, BP_Moderation_Message::$moderation_type, $last_message_id );
						}
					}

					if ( (int) bp_loggedin_user_id() === (int) $messages_template->thread->last_sender_id ) {
						if ( $send_media ) {
							echo esc_html( stripslashes_deep( strtolower( $exerpt ) ) );
						} else {
							echo esc_html( stripslashes_deep( $exerpt ) );
						}
					} else {
						$last_sender = bp_core_get_user_displayname( $messages_template->thread->last_sender_id );
						if ( bp_is_active( 'moderation' ) ) {
							if ( $is_last_sender_suspended ) {
								$last_sender = function_exists( 'bb_moderation_is_suspended_label' ) ? bb_moderation_is_suspended_label( $messages_template->thread->last_sender_id ) : esc_html__( 'Suspended Member', 'buddyboss' );
							} elseif ( $is_last_sender_blocked ) {
								$last_sender = function_exists( 'bb_moderation_has_blocked_label' ) ? bb_moderation_has_blocked_label( $last_sender, $messages_template->thread->last_sender_id ) : esc_html__( 'Blocked Member', 'buddyboss' );
							}
						}
						// For conversations with a single recipient - Don't include the name of the last person to message before the message content.
						if ( ! $is_group_thread && ! empty( $recipients ) && count( $other_recipients ) === 1 ) {
							$last_sender = '';
						}
						if ( $last_sender ) {
							if ( $send_media ) {
								echo esc_html( $last_sender ) . ' ' . esc_html( stripslashes_deep( strtolower( $exerpt ) ) );
							} else {
								echo esc_html( $last_sender ) . ': ' . esc_html( stripslashes_deep( $exerpt ) );
							}
						} else {
							echo esc_html( stripslashes_deep( $exerpt ) );
						}
					}
					?>
				</span>
			</div>
		</li>
		<?php
	endwhile;

else :
	?>
	<li class="bs-item-wrap bb-rl-no-messages-wrap">
		<div class="notification-content">
			<div class="bb-rl-no-messages">
				<i class="bb-icons-rl-chats-circle"></i>
				<h3><?php esc_html_e( 'No message found', 'buddyboss' ); ?></h3>
				<div class="bb-rl-no-messages-description">
					<?php esc_html_e( 'When you have new messages, they will appear here.', 'buddyboss' ); ?>
				</div>
				<div class="bb-rl-no-messages-button">
					<a href="<?php echo esc_url( trailingslashit( bp_loggedin_user_domain() . bp_get_messages_slug() ) ); ?>" class="bb-rl-button bb-rl-button--brandFill bb-rl-button--small">
						<i class="bb-icons-rl-plus"></i>
						<?php esc_html_e( 'New', 'buddyboss' ); ?>
					</a>
				</div>
			</div>
		</div>
	</li>
	<?php
endif;
