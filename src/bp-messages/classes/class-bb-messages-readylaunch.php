<?php
/**
 * BuddyBoss Messages Readylaunch Class
 *
 * @package     BuddyBoss\Messages
 * @subpackage  Classes
 * @since       BuddyBoss [BBVERSION]
 */

/**
 * BuddyBoss Messages Readylaunch.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Messages\Classes
 */
class BB_Messages_Readylaunch {

	/**
	 * The single instance of the class.
	 *
	 * @since  BuddyBoss [BBVERSION]
	 *
	 * @access private
	 * @var self
	 */
	private static $instance = null;

	/**
	 * Constructor method.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function __construct() {
		add_filter( 'bp_messages_js_template_parts', array( $this, 'bb_messages_js_template_parts' ) );
		add_filter( 'bp_core_get_js_strings', array( $this, 'bb_rl_messages_localize_scripts' ), 11, 1 );
		add_action( 'wp_ajax_bb_get_thread_right_panel_data', array( $this, 'bb_get_thread_right_panel_data' ) );
	}

	/**
	 * Get the instance of this class.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return Controller|BB_Activity_Readylaunch|null
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
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $template_parts The template parts.
	 *
	 * @return array The modified template parts.
	 */
	public function bb_messages_js_template_parts( $template_parts ) {
		$template_parts[] = 'parts/bp-messages-right-panel';

		return $template_parts;
	}

	/**
	 * Localize the scripts.
	 *
	 * @since BuddyBoss [BBVERSION]
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
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_get_thread_right_panel_data() {
		// Verify nonce.
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'bb_messages_right_panel' ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed' ) );

			return;
		}

		$thread_id = isset( $_POST['thread_id'] ) ? intval( $_POST['thread_id'] ) : 0;
		$page = isset( $_POST['page'] ) ? intval( $_POST['page'] ) : 1;
		$type = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : 'participants';

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
		$thread = new BP_Messages_Thread( false );
		$per_page = bb_messages_recipients_per_page();

		if ( 'participants' === $type ) {
			$results = $thread->get_pagination_recipients(
				$thread_id,
				array(
					'per_page' => $per_page,
					'page'     => $page,
				)
			);
			$response['participants_count'] = $thread->total_recipients_count;
			$response['participants_per_page'] = $per_page;
			$response['has_more'] = ( $page * $per_page ) < $thread->total_recipients_count;

			foreach ( $results as $recipient ) {
				$is_current_user = (int) bp_loggedin_user_id() === (int) $recipient->user_id;

				$participant = array(
					'id'          => $recipient->user_id,
					'name'        => $is_current_user ? esc_html__( 'You', 'buddyboss' ) : bp_core_get_user_displayname( $recipient->user_id ),
					'avatar'      => bp_core_fetch_avatar(
						array(
							'item_id' => $recipient->user_id,
							'type'    => 'thumb',
							'width'   => 50,
							'height'  => 50,
							'html'    => false,
						)
					),
					'profile_url' => bp_core_get_user_domain( $recipient->user_id ),
					'is_you'      => $is_current_user,
				);

				if ( bp_is_active( 'moderation' ) ) {
					$participant['is_user_blocked']    = bp_moderation_is_user_blocked( $recipient->user_id );
					$participant['can_be_blocked']     = ( ! in_array( (int) $recipient->user_id, $admins, true ) && false === bp_moderation_is_user_suspended( $recipient->user_id ) ) ? true : false;
					$participant['is_user_suspended']  = bp_moderation_is_user_suspended( $recipient->user_id );
					$participant['is_user_blocked_by'] = bb_moderation_is_user_blocked_by( $recipient->user_id );
					$participant['is_user_reported']   = bp_moderation_report_exist( $recipient->user_id, BP_Moderation_Members::$moderation_type_report );
					$participant['can_be_report']      = ! in_array( (int) $recipient->user_id, $admins, true ) && false === bp_moderation_user_can( bp_loggedin_user_id(), BP_Moderation_Members::$moderation_type_report );
					$participant['reported_type']      = bp_moderation_get_report_type( BP_Moderation_Members::$moderation_type_report, $recipient->user_id );
				}

				$response['participants'][] = $participant;
			}
		}

		// Get media attachments if BuddyBoss Media component is active.
		if ( 'media' === $type && bp_is_active( 'media' ) ) {
			global $wpdb, $bp;

			$media_per_page = 20; // Number of media items per page.
			$offset = ( $page - 1 ) * $media_per_page;

			// Get images with pagination.
			$media_items = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"SELECT SQL_CALC_FOUND_ROWS m.* 
					FROM {$bp->media->table_name} m
					INNER JOIN {$bp->messages->table_name_messages} msg ON m.message_id = msg.id 
					WHERE msg.thread_id = %d 
					AND m.type = 'photo'
					ORDER BY m.date_created DESC 
					LIMIT %d OFFSET %d",
					$thread_id,
					$media_per_page,
					$offset
				)
			);
			$total_count = $wpdb->get_var( 'SELECT FOUND_ROWS()' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			$response['has_more'] = ( $page * $media_per_page ) < (int) $total_count;

			if ( ! empty( $media_items ) ) {
				foreach ( $media_items as $media ) {
					$response['media'][] = array(
						'id'    => $media->id,
						'title' => $media->title,
						'url'   => bp_media_get_preview_image_url( $media->id, $media->attachment_id ),
					);
				}
			}
		}

		// Get document files with pagination.
		if ( 'files' === $type && bp_is_active( 'document' ) ) {
			global $wpdb, $bp;

			$files_per_page = 20; // Number of files per page.
			$offset = ( $page - 1 ) * $files_per_page;

			$document_items = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"SELECT SQL_CALC_FOUND_ROWS d.* 
					FROM {$bp->document->table_name} d
					INNER JOIN {$bp->messages->table_name_messages} msg ON d.message_id = msg.id 
					WHERE msg.thread_id = %d 
					ORDER BY d.date_created DESC 
					LIMIT %d OFFSET %d",
					$thread_id,
					$files_per_page,
					$offset
				)
			);
			$total_count    = $wpdb->get_var( 'SELECT FOUND_ROWS()' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			$response['has_more'] = ( $page * $files_per_page ) < (int) $total_count;

			if ( ! empty( $document_items ) ) {
				foreach ( $document_items as $document ) {
					$file_url = wp_get_attachment_url( $document->attachment_id );
					$filetype = wp_check_filetype( $file_url );
					$ext      = $filetype['ext'];
					if ( empty( $ext ) ) {
						$path = wp_parse_url( $file_url, PHP_URL_PATH );
						$ext  = pathinfo( basename( $path ), PATHINFO_EXTENSION );
					}

					// Truncate title for display if it's too long.
					$title = $document->title;
					if ( strlen( $title ) > 15 ) {
						$title = substr( $title, 0, 12 ) . '...';
					}

					$response['files'][] = array(
						'id'         => $document->id,
						'title'      => $title,
						'full_title' => $document->title,
						'url'        => bp_document_get_preview_url( $document->id, $document->attachment_id ),
						'extension'  => $ext,
					);
				}
			}
		}

		wp_send_json_success( $response );
	}
}
