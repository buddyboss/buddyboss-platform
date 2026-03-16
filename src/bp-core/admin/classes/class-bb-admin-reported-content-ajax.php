<?php
/**
 * BuddyBoss Reported Content Admin AJAX Handler
 *
 * Handles AJAX requests for Reported Content list
 * in the Settings 2.0 admin interface.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Core\Administration
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class BB_Admin_Reported_Content_Ajax
 *
 * @since BuddyBoss [BBVERSION]
 */
class BB_Admin_Reported_Content_Ajax {

	/**
	 * Nonce action (shared with BB_Admin_Settings_Ajax).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'bb_admin_settings';

	/**
	 * Constructor.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function __construct() {
		$this->bb_register_ajax_handlers();
	}

	/**
	 * Register AJAX handlers.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	private function bb_register_ajax_handlers() {
		add_action( 'wp_ajax_bb_admin_get_reported_content', array( $this, 'bb_get_reported_content' ) );
		add_action( 'wp_ajax_bb_admin_get_content_report', array( $this, 'bb_get_content_report' ) );
		add_action( 'wp_ajax_bb_admin_hide_content', array( $this, 'bb_hide_content' ) );
		add_action( 'wp_ajax_bb_admin_unhide_content', array( $this, 'bb_unhide_content' ) );
		add_action( 'wp_ajax_bb_admin_suspend_content_owner', array( $this, 'bb_suspend_content_owner' ) );
		add_action( 'wp_ajax_bb_admin_unsuspend_content_owner', array( $this, 'bb_unsuspend_content_owner' ) );
		add_action( 'wp_ajax_bb_admin_reported_content_bulk_action', array( $this, 'bb_bulk_action' ) );
	}

	/**
	 * Temporarily bypass suspension filters so admin can see real user data.
	 *
	 * Content owners may be suspended — admin needs to see actual names
	 * and avatars, not "Unknown Member" placeholders.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	private function bb_bypass_suspend_filters() {
		global $moderation_suspend;

		if ( empty( $moderation_suspend['user'] ) || ! $moderation_suspend['user'] instanceof BP_Suspend_Member ) {
			return;
		}

		$suspend = $moderation_suspend['user'];

		remove_filter( 'bp_core_get_user_displayname', array( $suspend, 'get_the_author_name' ), 9999 );
		remove_filter( 'get_the_author_display_name', array( $suspend, 'get_the_author_name' ), 9999 );
		remove_filter( 'get_the_author_user_nicename', array( $suspend, 'get_the_author_name' ), 9999 );
		remove_filter( 'get_the_author_user_login', array( $suspend, 'get_the_author_name' ), 9999 );
		remove_filter( 'get_the_author_user_email', array( $suspend, 'get_the_author_name' ), 9999 );
		remove_filter( 'get_avatar_url', array( $suspend, 'get_avatar_url' ), 9999 );
		remove_filter( 'bp_core_fetch_avatar_url_check', array( $suspend, 'bp_fetch_avatar_url' ), 1005 );
		remove_filter( 'bp_core_fetch_gravatar_url_check', array( $suspend, 'bp_fetch_avatar_url' ), 1005 );
		remove_filter( 'bp_core_get_user_domain', array( $suspend, 'bp_core_get_user_domain' ), 9999 );
	}

	/**
	 * Restore suspension filters after admin data fetch.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	private function bb_restore_suspend_filters() {
		global $moderation_suspend;

		if ( empty( $moderation_suspend['user'] ) || ! $moderation_suspend['user'] instanceof BP_Suspend_Member ) {
			return;
		}

		$suspend = $moderation_suspend['user'];

		add_filter( 'bp_core_get_user_displayname', array( $suspend, 'get_the_author_name' ), 9999, 2 );
		add_filter( 'get_the_author_display_name', array( $suspend, 'get_the_author_name' ), 9999, 2 );
		add_filter( 'get_the_author_user_nicename', array( $suspend, 'get_the_author_name' ), 9999, 2 );
		add_filter( 'get_the_author_user_login', array( $suspend, 'get_the_author_name' ), 9999, 2 );
		add_filter( 'get_the_author_user_email', array( $suspend, 'get_the_author_name' ), 9999, 2 );
		add_filter( 'get_avatar_url', array( $suspend, 'get_avatar_url' ), 9999, 3 );
		add_filter( 'bp_core_fetch_avatar_url_check', array( $suspend, 'bp_fetch_avatar_url' ), 1005, 2 );
		add_filter( 'bp_core_fetch_gravatar_url_check', array( $suspend, 'bp_fetch_avatar_url' ), 1005, 2 );
		add_filter( 'bp_core_get_user_domain', array( $suspend, 'bp_core_get_user_domain' ), 9999, 2 );
	}

	/**
	 * Verify AJAX request (nonce + capability).
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	private function bb_verify_request() {
		if ( ! bp_current_user_can( 'bp_moderate' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'You do not have permission to perform this action.', 'buddyboss' ) ),
				403
			);
		}

		check_ajax_referer( self::NONCE_ACTION, 'nonce' );
	}

	/**
	 * Map content type slugs to icon classes for the React frontend.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $content_type Content type slug.
	 *
	 * @return string Icon class string.
	 */
	private function bb_get_content_type_icon( $content_type ) {
		$icons = array(
			'activity'         => 'bb-icons-rl bb-icons-rl-activity',
			'activity_comment' => 'bb-icons-rl bb-icons-rl-chat-dots',
			'media'            => 'bb-icons-rl bb-icons-rl-image',
			'video'            => 'bb-icons-rl bb-icons-rl-video-camera',
			'document'         => 'bb-icons-rl bb-icons-rl-file-text',
			'forum_topic'      => 'bb-icons-rl bb-icons-rl-chats-circle',
			'forum_reply'      => 'bb-icons-rl bb-icons-rl-chat-circle',
			'groups'           => 'bb-icons-rl bb-icons-rl-users-four',
			'forum'            => 'bb-icons-rl bb-icons-rl-chats-circle',
			'comment'          => 'bb-icons-rl bb-icons-rl-chat-dots',
		);

		return isset( $icons[ $content_type ] ) ? $icons[ $content_type ] : 'bb-icons-rl bb-icons-rl-file-text';
	}

	/**
	 * Get reported content list.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_get_reported_content() {
		$this->bb_verify_request();

		$page         = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
		$per_page     = isset( $_POST['per_page'] ) ? absint( $_POST['per_page'] ) : 20;
		$search       = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
		$content_type = isset( $_POST['content_type'] ) ? sanitize_text_field( wp_unslash( $_POST['content_type'] ) ) : '';
		$status       = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '';

		$moderation_args = array(
			'page'          => $page,
			'per_page'      => $per_page,
			'count_total'   => true,
			'exclude_types' => array( BP_Moderation_Members::$moderation_type ),
			'reported'      => false,
			'search_terms'  => ! empty( $search ) ? $search : false,
		);

		// Filter by content type if provided.
		if ( ! empty( $content_type ) ) {
			$moderation_args['in_types'] = array( $content_type );
		}

		// Store status for the WHERE condition filter.
		$this->status_filter = $status;

		// Show items with reported, user_report, or hide_sitewide (same as legacy list table).
		add_filter( 'bp_moderation_get_where_conditions', array( $this, 'bb_update_where_conditions' ), 10, 2 );

		$result = BP_Moderation::get( $moderation_args );

		remove_filter( 'bp_moderation_get_where_conditions', array( $this, 'bb_update_where_conditions' ), 10 );

		$items         = array();
		$content_types = bp_moderation_content_types();

		// Cache admin user IDs for the loop (same pattern as legacy list table).
		$admins = array_map(
			'intval',
			get_users(
				array(
					'role'   => 'administrator',
					'fields' => 'ID',
				)
			)
		);

		// Bypass suspension filters so admin sees real names and avatars.
		$this->bb_bypass_suspend_filters();

		if ( ! empty( $result['moderations'] ) ) {
			foreach ( $result['moderations'] as $moderation ) {
				$item_id      = (int) $moderation->item_id;
				$item_type    = $moderation->item_type;
				$is_hidden    = (int) $moderation->hide_sitewide === 1;
				$report_count = isset( $moderation->count ) ? (int) $moderation->count : 0;

				// Content type label.
				$content_label = isset( $content_types[ $item_type ] ) ? $content_types[ $item_type ] : $item_type;

				// Content URL.
				$content_url = bp_moderation_get_permalink( $item_id, $item_type );

				// Content owner.
				$owner_id = bp_moderation_get_content_owner_id( $item_id, $item_type );
				if ( is_array( $owner_id ) ) {
					$owner_id = ! empty( $owner_id ) ? (int) $owner_id[0] : 0;
				}
				$owner_id = (int) $owner_id;

				$owner_data = array(
					'user_id'      => $owner_id,
					'display_name' => '',
					'avatar'       => '',
					'profile_url'  => '',
				);

				if ( $owner_id > 0 ) {
					$owner_data['display_name'] = bp_core_get_user_displayname( $owner_id );
					$owner_data['avatar']       = bp_core_fetch_avatar(
						array(
							'item_id' => $owner_id,
							'type'    => 'thumb',
							'width'   => 40,
							'height'  => 40,
							'html'    => false,
						)
					);
					$owner_data['profile_url'] = bp_core_get_user_domain( $owner_id );
				}

				$is_owner_suspended = ( $owner_id > 0 ) ? bp_moderation_is_user_suspended( $owner_id ) : false;
				$is_owner_admin     = ( $owner_id > 0 ) ? in_array( $owner_id, $admins, true ) : false;

				// Check if a suspend/unsuspend background process is in progress.
				// Check both meta keys because the suspended status changes immediately
				// while the background job is still processing content.
				$suspend_in_progress = false;
				if ( $owner_id > 0 && ! $is_owner_admin ) {
					$suspend_id = BP_Core_Suspend::get_suspend_id( $owner_id, BP_Moderation_Members::$moderation_type );
					if ( $suspend_id ) {
						$suspend_in_progress = ! empty( bb_suspend_get_meta( $suspend_id, 'suspend' ) ) || ! empty( bb_suspend_get_meta( $suspend_id, 'unsuspend' ) );
					}
				}

				$items[] = array(
					'id'                  => (int) $moderation->id,
					'item_id'             => $item_id,
					'item_type'           => $item_type,
					'content_label'       => $content_label,
					'content_icon'        => $this->bb_get_content_type_icon( $item_type ),
					'content_url'         => $content_url,
					'owner'               => $owner_data,
					'reports'             => $report_count,
					'is_hidden'           => $is_hidden,
					'is_owner_suspended'  => (bool) $is_owner_suspended,
					'is_owner_admin'      => (bool) $is_owner_admin,
					'suspend_in_progress' => (bool) $suspend_in_progress,
				);
			}
		}

		$this->bb_restore_suspend_filters();

		wp_send_json_success(
			array(
				'items'       => $items,
				'total'       => isset( $result['total'] ) ? (int) $result['total'] : 0,
				'page'        => $page,
				'per_page'    => $per_page,
				'total_pages' => $per_page > 0 ? (int) ceil( ( isset( $result['total'] ) ? $result['total'] : 0 ) / $per_page ) : 1,
			)
		);
	}

	/**
	 * Get content report details (reporters).
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_get_content_report() {
		$this->bb_verify_request();

		$moderation_id = isset( $_POST['moderation_id'] ) ? absint( $_POST['moderation_id'] ) : 0;

		if ( empty( $moderation_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid moderation ID.', 'buddyboss' ) ) );
		}

		// Load the moderation record directly by ID.
		global $wpdb;
		$bp  = buddypress();
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bp->moderation->table_name} WHERE id = %d", $moderation_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( empty( $row ) ) {
			wp_send_json_error( array( 'message' => __( 'No moderation record found.', 'buddyboss' ) ) );
		}

		$item_id      = (int) $row->item_id;
		$item_type    = $row->item_type;
		$is_hidden    = (int) $row->hide_sitewide === 1;
		$report_count = (int) bp_moderation_get_meta( $moderation_id, '_count_user_reported' );

		$content_types = bp_moderation_content_types();
		$content_label = isset( $content_types[ $item_type ] ) ? $content_types[ $item_type ] : $item_type;
		$content_url   = bp_moderation_get_permalink( $item_id, $item_type );

		// Content owner.
		$owner_id = bp_moderation_get_content_owner_id( $item_id, $item_type );
		if ( is_array( $owner_id ) ) {
			$owner_id = ! empty( $owner_id ) ? (int) $owner_id[0] : 0;
		}
		$owner_id = (int) $owner_id;

		// Bypass suspension filters.
		$this->bb_bypass_suspend_filters();

		$owner_data = array(
			'user_id'      => $owner_id,
			'display_name' => '',
			'avatar'       => '',
			'profile_url'  => '',
		);

		if ( $owner_id > 0 ) {
			$owner_data['display_name'] = bp_core_get_user_displayname( $owner_id );
			$owner_data['avatar']       = bp_core_fetch_avatar(
				array(
					'item_id' => $owner_id,
					'type'    => 'thumb',
					'width'   => 40,
					'height'  => 40,
					'html'    => false,
				)
			);
			$owner_data['profile_url'] = bp_core_get_user_domain( $owner_id );
		}

		$is_owner_suspended = ( $owner_id > 0 ) ? bp_moderation_is_user_suspended( $owner_id ) : false;

		// Get reporters (user_report = 1).
		$reporters_raw = BP_Moderation::get_moderation_reporters(
			$moderation_id,
			array( 'user_repoted' => true )
		);

		$reporters = array();
		if ( ! empty( $reporters_raw ) ) {
			foreach ( $reporters_raw as $reporter ) {
				$term_data = get_term( $reporter->category_id );

				$reporters[] = array(
					'user_id'       => (int) $reporter->user_id,
					'display_name'  => bp_core_get_user_displayname( $reporter->user_id ),
					'avatar'        => bp_core_fetch_avatar(
						array(
							'item_id' => $reporter->user_id,
							'type'    => 'thumb',
							'width'   => 40,
							'height'  => 40,
							'html'    => false,
						)
					),
					'profile_url'   => bp_core_get_user_domain( $reporter->user_id ),
					'category_name' => ( ! is_wp_error( $term_data ) && ! empty( $term_data->name ) )
						? wp_specialchars_decode( $term_data->name, ENT_QUOTES )
						: __( 'Other', 'buddyboss' ),
					'category_desc' => ( ! is_wp_error( $term_data ) && ! empty( $term_data->description ) )
						? wp_specialchars_decode( $term_data->description, ENT_QUOTES )
						: $reporter->content,
					'date'          => ! empty( $reporter->date_created )
						? date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $reporter->date_created ) )
						: '',
				);
			}
		}

		$response = array(
			'moderation_id'      => $moderation_id,
			'item_id'            => $item_id,
			'item_type'          => $item_type,
			'content_label'      => $content_label,
			'content_icon'       => $this->bb_get_content_type_icon( $item_type ),
			'content_url'        => $content_url,
			'owner'              => $owner_data,
			'reports'            => $report_count,
			'is_hidden'          => $is_hidden,
			'is_owner_suspended' => (bool) $is_owner_suspended,
			'reporters'          => $reporters,
		);

		$this->bb_restore_suspend_filters();

		wp_send_json_success( $response );
	}

	/**
	 * Hide reported content.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_hide_content() {
		$this->bb_verify_request();

		$item_id   = isset( $_POST['item_id'] ) ? absint( $_POST['item_id'] ) : 0;
		$item_type = isset( $_POST['item_type'] ) ? sanitize_text_field( wp_unslash( $_POST['item_type'] ) ) : '';

		if ( empty( $item_id ) || empty( $item_type ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid content ID or type.', 'buddyboss' ) ) );
		}

		$result = bp_moderation_hide(
			array(
				'content_id'   => $item_id,
				'content_type' => $item_type,
			)
		);

		if ( ! $result ) {
			wp_send_json_error( array( 'message' => __( 'Failed to hide content.', 'buddyboss' ) ) );
		}

		wp_send_json_success(
			array( 'message' => __( 'Content hidden successfully.', 'buddyboss' ) )
		);
	}

	/**
	 * Unhide reported content.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_unhide_content() {
		$this->bb_verify_request();

		$item_id   = isset( $_POST['item_id'] ) ? absint( $_POST['item_id'] ) : 0;
		$item_type = isset( $_POST['item_type'] ) ? sanitize_text_field( wp_unslash( $_POST['item_type'] ) ) : '';

		if ( empty( $item_id ) || empty( $item_type ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid content ID or type.', 'buddyboss' ) ) );
		}

		$result = bp_moderation_unhide(
			array(
				'content_id'   => $item_id,
				'content_type' => $item_type,
			)
		);

		if ( ! $result ) {
			wp_send_json_error( array( 'message' => __( 'Failed to unhide content.', 'buddyboss' ) ) );
		}

		wp_send_json_success(
			array( 'message' => __( 'Content unhidden successfully.', 'buddyboss' ) )
		);
	}

	/**
	 * Suspend the owner of reported content.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_suspend_content_owner() {
		$this->bb_verify_request();

		$user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;

		if ( empty( $user_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid user ID.', 'buddyboss' ) ) );
		}

		// Don't allow suspending administrators.
		$admins = array_map( 'intval', get_users( array( 'role' => 'administrator', 'fields' => 'ID' ) ) );
		if ( in_array( $user_id, $admins, true ) ) {
			wp_send_json_error( array( 'message' => __( 'Cannot suspend an administrator.', 'buddyboss' ) ) );
		}

		if ( bp_moderation_is_user_suspended( $user_id ) ) {
			wp_send_json_error( array( 'message' => __( 'This user is already suspended.', 'buddyboss' ) ) );
		}

		BP_Suspend_Member::suspend_user( $user_id );

		wp_send_json_success(
			array( 'message' => __( 'User suspended successfully.', 'buddyboss' ) )
		);
	}

	/**
	 * Unsuspend the owner of reported content.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_unsuspend_content_owner() {
		$this->bb_verify_request();

		$user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;

		if ( empty( $user_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid user ID.', 'buddyboss' ) ) );
		}

		if ( ! bp_moderation_is_user_suspended( $user_id ) ) {
			wp_send_json_error( array( 'message' => __( 'This user is not currently suspended.', 'buddyboss' ) ) );
		}

		BP_Suspend_Member::unsuspend_user( $user_id );

		wp_send_json_success(
			array( 'message' => __( 'User unsuspended successfully.', 'buddyboss' ) )
		);
	}

	/**
	 * Bulk action on reported content items.
	 *
	 * Supported actions: hide, unhide.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_bulk_action() {
		$this->bb_verify_request();

		$action = isset( $_POST['bulk_action'] ) ? sanitize_text_field( wp_unslash( $_POST['bulk_action'] ) ) : '';
		$ids    = isset( $_POST['ids'] ) ? array_map( 'absint', (array) $_POST['ids'] ) : array();

		if ( empty( $action ) || empty( $ids ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid action or no items selected.', 'buddyboss' ) ) );
		}

		$allowed_actions = array( 'hide', 'unhide' );
		if ( ! in_array( $action, $allowed_actions, true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid bulk action.', 'buddyboss' ) ) );
		}

		global $wpdb;
		$bp      = buddypress();
		$success = 0;

		foreach ( $ids as $moderation_id ) {
			$row = $wpdb->get_row( $wpdb->prepare( "SELECT item_id, item_type FROM {$bp->moderation->table_name} WHERE id = %d", $moderation_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			if ( empty( $row ) ) {
				continue;
			}

			if ( 'hide' === $action ) {
				$result = bp_moderation_hide(
					array(
						'content_id'   => (int) $row->item_id,
						'content_type' => $row->item_type,
					)
				);
			} else {
				$result = bp_moderation_unhide(
					array(
						'content_id'   => (int) $row->item_id,
						'content_type' => $row->item_type,
					)
				);
			}

			if ( $result ) {
				$success++;
			}
		}

		wp_send_json_success(
			array(
				/* translators: %d: Number of items. */
				'message' => sprintf( __( '%d item(s) updated successfully.', 'buddyboss' ), $success ),
				'count'   => $success,
			)
		);
	}

	/**
	 * Filter WHERE conditions for reported content view.
	 *
	 * Shows items with reported, user_report, or hide_sitewide set — replicates
	 * the old BP_Moderation_List_Table logic for the reported-content tab.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $where_conditions Current conditions.
	 * @param array $r                Parsed arguments.
	 *
	 * @return array
	 */
	public function bb_update_where_conditions( $where_conditions, $r ) {
		$status = ! empty( $this->status_filter ) ? $this->status_filter : '';

		if ( 'hidden' === $status ) {
			$where_conditions['reported'] = '( ms.hide_sitewide = 1 )';
		} elseif ( 'visible' === $status ) {
			$where_conditions['reported'] = '( ( ms.reported != 0 OR ms.user_report != 0 ) AND ms.hide_sitewide = 0 )';
		} else {
			$where_conditions['reported'] = '( ms.reported != 0 OR ms.user_report != 0 OR ms.hide_sitewide != 0 )';
		}

		return $where_conditions;
	}
}

// Initialize.
new BB_Admin_Reported_Content_Ajax();
