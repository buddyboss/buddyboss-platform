<?php
/**
 * BuddyBoss Messages ReadyLaunch Class
 *
 * @since BuddyBoss 2.9.00
 *
 * @package     BuddyBoss\Messages
 * @subpackage  Classes
 * @since       BuddyBoss 2.9.00
 */

/**
 * BuddyBoss Messages ReadyLaunch.
 *
 * @since   BuddyBoss 2.9.00
 * @package BuddyBoss\Messages\Classes
 */
class BB_Messages_Readylaunch {

	/**
	 * The single instance of the class.
	 *
	 * @since  BuddyBoss 2.9.00
	 *
	 * @access private
	 * @var self
	 */
	private static $instance = null;

	/**
	 * Constructor method.
	 *
	 * @since BuddyBoss 2.9.00
	 */
	public function __construct() {
		add_filter( 'bp_messages_js_template_parts', array( $this, 'bb_messages_js_template_parts' ) );
		add_filter( 'bp_core_get_js_strings', array( $this, 'bb_rl_messages_localize_scripts' ), 11, 1 );
		add_action( 'wp_ajax_bb_rl_get_thread_right_panel_data', array( $this, 'bb_rl_get_thread_right_panel_data' ) );
		add_filter( 'bp_messages_recipient_get_where_conditions', array( $this, 'bb_rl_filter_message_threads_by_type' ), 10, 2 );
		add_filter( 'bp_ajax_querystring', array( $this, 'bb_rl_messages_ajax_querystring' ), 10, 2 );
		remove_action( 'bb_nouveau_after_nav_link_compose-action', 'bb_messages_compose_action_sub_nav' );
	}

	/**
	 * Get the instance of this class.
	 *
	 * @since BuddyBoss 2.9.00
	 *
	 * @return BB_Messages_Readylaunch|null
	 */
	public static function instance() {

		if ( null === self::$instance ) {
			$class_name     = __CLASS__;
			self::$instance = new $class_name();
		}

		return self::$instance;
	}

	/**
	 * Add the right panel template part.
	 *
	 * @since BuddyBoss 2.9.00
	 *
	 * @param array $template_parts The template parts.
	 *
	 * @return array The modified template parts.
	 */
	public function bb_messages_js_template_parts( $template_parts ) {
		$template_parts[] = 'parts/bp-messages-right-panel';
		$template_parts[] = 'parts/bp-messages-no-unread-threads';

		return $template_parts;
	}

	/**
	 * Localise the scripts.
	 *
	 * @since BuddyBoss 2.9.00
	 *
	 * @param array $params The parameters.
	 */
	public function bb_rl_messages_localize_scripts( $params ) {
		$params['messages']['nonces']['bb_messages_right_panel'] = wp_create_nonce( 'bb_messages_right_panel' );

		return $params;
	}

	/**
	 * Get thread right panel data.
	 *
	 * @since BuddyBoss 2.9.00
	 */
	public function bb_rl_get_thread_right_panel_data() {
		// Verify nonce.
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'bb_messages_right_panel' ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed' ) );

			return;
		}

		$thread_id = isset( $_POST['thread_id'] ) ? intval( $_POST['thread_id'] ) : 0;
		$page      = isset( $_POST['page'] ) ? intval( $_POST['page'] ) : 1;
		$type      = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : 'participants';

		// Ensure user has access to this thread.
		if ( ! bp_is_active( 'messages' ) || ! messages_check_thread_access( $thread_id ) ) {
			wp_send_json_error( array( 'message' => 'You do not have access to this conversation' ) );

			return;
		}

		$admins = function_exists( 'bb_get_all_admin_users' ) ? bb_get_all_admin_users() : '';

		$response = array(
			'participants' => array(),
			'media'        => array(),
			'files'        => array(),
			'page'         => $page,
			'has_more'     => false,
		);

		// Get thread participants.
		$thread   = new BP_Messages_Thread( false );
		$per_page = bb_messages_recipients_per_page();

		if ( 'participants' === $type ) {
			$results                           = $thread->get_pagination_recipients(
				$thread_id,
				array(
					'per_page' => $per_page,
					'page'     => $page,
				)
			);
			$response['participants_count']    = $thread->total_recipients_count;
			$response['participants_per_page'] = $per_page;
			$response['has_more']              = ( $page * $per_page ) < $thread->total_recipients_count;

			foreach ( $results as $recipient ) {
				$is_current_user = (int) bp_loggedin_user_id() === (int) $recipient->user_id;

				$participant = array(
					'id'            => $recipient->user_id,
					'name'          => $is_current_user ? esc_html__( 'You', 'buddyboss' ) : bp_core_get_user_displayname( $recipient->user_id ),
					'avatar'        => bp_core_fetch_avatar(
						array(
							'item_id' => $recipient->user_id,
							'type'    => 'thumb',
							'width'   => 50,
							'height'  => 50,
							'html'    => false,
						)
					),
					'profile_url'   => bp_core_get_user_domain( $recipient->user_id ),
					'is_you'        => $is_current_user,
					'user_presence' => ! $is_current_user ? bb_get_user_presence_html( $recipient->user_id ) : '',
				);

				if ( bp_is_active( 'moderation' ) ) {
					$participant['is_user_blocked']    = bp_moderation_is_user_blocked( $recipient->user_id );
					$participant['can_be_blocked']     = ! in_array( (int) $recipient->user_id, $admins, true ) && false === bp_moderation_is_user_suspended( $recipient->user_id );
					$participant['is_user_suspended']  = bp_moderation_is_user_suspended( $recipient->user_id );
					$participant['is_user_blocked_by'] = bb_moderation_is_user_blocked_by( $recipient->user_id );
					$participant['is_user_reported']   = bp_moderation_report_exist( $recipient->user_id, BP_Moderation_Members::$moderation_type_report );
					$participant['can_be_report']      = ! in_array( (int) $recipient->user_id, $admins, true ) && false === bp_moderation_user_can( bp_loggedin_user_id(), BP_Moderation_Members::$moderation_type_report );
					$participant['reported_type']      = bp_moderation_get_report_type( BP_Moderation_Members::$moderation_type_report, $recipient->user_id );
				}

				$response['participants'][] = $participant;
			}
		}

		$first_message = BP_Messages_Thread::get_first_message( $thread_id );
		$group_id      = (int) bp_messages_get_meta( $first_message->id, 'group_id' );
		$message_from  = bp_messages_get_meta( $first_message->id, 'message_from' ); // group.

		// Get media attachments if BuddyBoss Media component is active.
		$media_component = bp_is_active( 'media' ) &&
		(
			(
				$group_id &&
				'group' === $message_from &&
				bp_is_group_media_support_enabled()
			) ||
			(
				'group' !== $message_from &&
				bp_is_messages_media_support_enabled()
			)
		);
		$video_component = bp_is_active( 'video' ) &&
		(
			(
				$group_id &&
				'group' === $message_from &&
				bp_is_group_video_support_enabled()
			) ||
			(
				'group' !== $message_from &&
				bp_is_messages_video_support_enabled()
			)
		);
		if ( 'media' === $type && ( $media_component || $video_component ) ) {
			global $wpdb, $bp;

			$media_per_page = 20; // Number of media items per page.
			$offset         = ( $page - 1 ) * $media_per_page;

			// Build media type condition based on active components.
			$media_types = array();
			if ( $media_component ) {
				$media_types[] = 'photo';
			}
			if ( $video_component ) {
				$media_types[] = 'video';
			}

			$prepare_args   = array( $thread_id );
			$prepare_args   = array_merge( $prepare_args, $media_types );
			$prepare_args[] = $media_per_page;
			$prepare_args[] = $offset;

			// Build table names safely.
			$media_table    = $bp->media->table_name;
			$messages_table = $bp->messages->table_name_messages;

			// Build the query properly without interpolated variables.
			if ( ! empty( $media_types ) ) {
				$placeholders = implode( ',', array_fill( 0, count( $media_types ), '%s' ) );
				$sql_query    = sprintf(
					'SELECT SQL_CALC_FOUND_ROWS m.*
					FROM %s m
					INNER JOIN %s msg ON m.message_id = msg.id
					WHERE msg.thread_id = %%d
					AND ( m.type IN (%s) )
					ORDER BY m.date_created DESC
					LIMIT %%d OFFSET %%d',
					$media_table,
					$messages_table,
					$placeholders
				);
				$query        = $wpdb->prepare( $sql_query, $prepare_args ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			} else {
				$sql_query = sprintf(
					'SELECT SQL_CALC_FOUND_ROWS m.*
					FROM %s m
					INNER JOIN %s msg ON m.message_id = msg.id
					WHERE msg.thread_id = %%d
					ORDER BY m.date_created DESC
					LIMIT %%d OFFSET %%d',
					$media_table,
					$messages_table
				);
				$query     = $wpdb->prepare( $sql_query, $thread_id, $media_per_page, $offset ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			}

			$media_items = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
			$total_count = $wpdb->get_var( 'SELECT FOUND_ROWS()' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			$response['has_more'] = ( $page * $media_per_page ) < (int) $total_count;

			if ( ! empty( $media_items ) ) {
				foreach ( $media_items as $media ) {
					$args = array(
						'type'          => 'photo' === $media->type ? 'media' : 'video',
						'id'            => $media->id,
						'title'         => $media->title,
						'attachment_id' => $media->attachment_id,
						'privacy'       => $media->privacy,
					);
					if ( $video_component && 'video' === $media->type ) {
						$poster_id     = bb_get_video_thumb_id( $media->attachment_id );
						$thumbnail_url = bb_get_video_default_placeholder_image();
						if ( $poster_id ) {
							$thumbnail_url = bb_video_get_thumb_url( $media->id, $poster_id, 'bb-video-profile-album-add-thumbnail-directory-poster-image' );
						}
						$args['thumbnail'] = $thumbnail_url;
						$args['full']      = $poster_id ? bb_video_get_thumb_url( $media->id, $poster_id, 'bb-video-poster-popup-image' ) : '';
					} elseif ( $media_component && 'photo' === $media->type ) {
						$args['thumbnail'] = bp_media_get_preview_image_url( $media->id, $media->attachment_id );
						$args['full']      = bp_media_get_preview_image_url( $media->id, $media->attachment_id, 'full' );
					}
					$response['media'][] = $args;
				}
			}
		}

		$document_component = bp_is_active( 'media' ) &&
		(
			(
				$group_id &&
				'group' === $message_from &&
				bp_is_group_document_support_enabled()
			) ||
			(
				'group' !== $message_from &&
				bp_is_messages_document_support_enabled()
			)
		);
		// Get document files with pagination.
		if ( 'files' === $type && $document_component ) {
			global $wpdb, $bp;

			$files_per_page = 20; // Number of files per page.
			$offset         = ( $page - 1 ) * $files_per_page;

			// Build table names safely.
			$document_table = $bp->document->table_name;
			$messages_table = $bp->messages->table_name_messages;

			// Prepare document query safely.
			$sql_query = sprintf(
				'SELECT SQL_CALC_FOUND_ROWS d.*
				FROM %s d
				INNER JOIN %s msg ON d.message_id = msg.id
				WHERE msg.thread_id = %%d
				ORDER BY d.date_created DESC
				LIMIT %%d OFFSET %%d',
				$document_table,
				$messages_table
			);
			$query     = $wpdb->prepare( $sql_query, $thread_id, $files_per_page, $offset ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

			$document_items = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
			$total_count    = $wpdb->get_var( 'SELECT FOUND_ROWS()' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			$response['has_more'] = ( $page * $files_per_page ) < (int) $total_count;

			if ( ! empty( $document_items ) ) {
				foreach ( $document_items as $document ) {
					$attachment_id = $document->attachment_id;
					$extension     = bp_document_extension( $attachment_id );
					// Truncate title for display if it's too long.
					$title = $document->title;
					if ( strlen( $title ) > 15 ) {
						$title = substr( $title, 0, 12 ) . '...';
					}

					$attached_file       = get_attached_file( $attachment_id );
					$filename            = basename( $attached_file );
					$text_attachment_url = wp_get_attachment_url( $attachment_id );
					$audio_url           = in_array( $extension, bp_get_document_preview_music_extensions(), true ) ? bp_document_get_preview_url( bp_get_document_id(), $attachment_id ) : '';
					$video_url           = in_array( $extension, bp_get_document_preview_video_extensions(), true ) ? bb_document_video_get_symlink( bp_get_document_id() ) : '';

					$response['files'][] = array(
						'url'            => bp_document_get_preview_url( $document->id, $attachment_id ),
						'full_title'     => $document->title,
						'id'             => $document->id,
						'svg_icon'       => class_exists( 'BB_Readylaunch' ) ? bb_load_readylaunch()->bb_rl_document_svg_icon( '', $extension ) : '',
						'attachment_id'  => $attachment_id,
						'privacy'        => $document->privacy,
						'extension'      => $extension,
						'author'         => $document->user_id,
						'preview'        => bp_document_get_preview_url( $document->id, $attachment_id ),
						'full_preview'   => bp_document_get_preview_url( $document->id, $attachment_id ),
						'text_preview'   => ! empty( $text_attachment_url ) ? esc_url( $text_attachment_url ) : '',
						'mp3_preview'    => $audio_url ?: '',
						'document_title' => $filename,
						'mirror_text'    => bp_document_mirror_text( $attachment_id ),
						'video'          => $video_url ?: '',
						'title'          => $title,
					);
				}
			}
		}

		wp_send_json_success( $response );
	}

	/**
	 * Filter the message threads by type.
	 *
	 * @since BuddyBoss 2.9.00
	 *
	 * @param string $where_conditions The where conditions.
	 * @param array  $r                The request parameters.
	 *
	 * @return string The where conditions.
	 */
	public function bb_rl_filter_message_threads_by_type( $where_conditions, $r ) {
		if ( ! empty( $r['thread_type'] ) ) {
			$thread_type = sanitize_text_field( wp_unslash( $r['thread_type'] ) );

			if ( 'unread' === $thread_type ) {
				// Only unread messages.
				$where_conditions .= ' AND ( r.unread_count > 0 AND r.is_deleted = 0 AND r.user_id = ' . bp_loggedin_user_id() . ' )';
			}
		}

		return $where_conditions;
	}

	/**
	 * Filter the querystring.
	 *
	 * @since BuddyBoss 2.9.00
	 *
	 * @param string $querystring        The querystring.
	 * @param string $querystring_object The querystring object.
	 *
	 * @return string The querystring.
	 */
	public function bb_rl_messages_ajax_querystring( $querystring, $querystring_object ) {
		if ( 'messages' === $querystring_object && isset( $_POST['thread_type'] ) ) {
			if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'bp_nouveau_messages' ) ) {
				wp_send_json_error(
					array(
						'feedback' => __( 'Unauthorized request.', 'buddyboss' ),
						'type'     => 'error',
					)
				);
			}

			$thread_type = sanitize_text_field( wp_unslash( $_POST['thread_type'] ) );

			if ( 'unread' === $thread_type ) {
				$querystring .= '&type=unread';
			}
		}
		return $querystring;
	}
}
