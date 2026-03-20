<?php
/**
 * BuddyBoss Reported Content Admin AJAX Handler
 *
 * Handles AJAX requests for Reported Content list
 * in the Settings 2.0 admin interface.
 *
 * @since BuddyBoss [BBVERSION]
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
	 * Current status filter for WHERE conditions.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var string
	 */
	private $status_filter = '';

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
		$per_page     = isset( $_POST['per_page'] ) ? min( absint( $_POST['per_page'] ), 100 ) : 20;
		$search       = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
		$content_type = isset( $_POST['content_type'] ) ? sanitize_text_field( wp_unslash( $_POST['content_type'] ) ) : '';
		$status       = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '';

		// Whitelist validate status filter.
		$valid_statuses = array( '', 'hidden', 'visible' );
		if ( ! in_array( $status, $valid_statuses, true ) ) {
			$status = '';
		}

		// Whitelist validate content type against registered types.
		if ( ! empty( $content_type ) ) {
			$valid_types = array_keys( bp_moderation_content_types() );
			if ( ! in_array( $content_type, $valid_types, true ) ) {
				$content_type = '';
			}
		}

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

		// Cache admin user IDs for the loop.
		$admins = bb_moderation_get_admin_user_ids();

		// Bypass suspension filters so admin sees real names and avatars.
		bb_moderation_bypass_suspend_filters();

		if ( ! empty( $result['moderations'] ) ) {

			// Pre-warm user caches for all content owners to avoid N+1 queries.
			// Store owner IDs in a map so we don't call bp_moderation_get_content_owner_id() twice.
			$owner_ids = array();
			$owner_map = array();
			foreach ( $result['moderations'] as $moderation ) {
				$owner_id = bp_moderation_get_content_owner_id( (int) $moderation->item_id, $moderation->item_type );
				if ( is_array( $owner_id ) ) {
					$owner_id = ! empty( $owner_id ) ? (int) $owner_id[0] : 0;
				}
				$owner_id                        = (int) $owner_id;
				$owner_map[ $moderation->id ] = $owner_id;
				if ( $owner_id > 0 ) {
					$owner_ids[] = $owner_id;
				}
			}
			if ( ! empty( $owner_ids ) ) {
				cache_users( array_unique( $owner_ids ) );
			}

			foreach ( $result['moderations'] as $moderation ) {
				$item_id      = (int) $moderation->item_id;
				$item_type    = $moderation->item_type;
				$is_hidden    = (int) $moderation->hide_sitewide === 1;
				$report_count = isset( $moderation->count ) ? (int) $moderation->count : 0;

				// Content type label.
				$content_label = isset( $content_types[ $item_type ] ) ? $content_types[ $item_type ] : $item_type;

				// Content URL.
				$content_url = bp_moderation_get_permalink( $item_id, $item_type );

				// Content owner (cached from first pass).
				$owner_id = isset( $owner_map[ $moderation->id ] ) ? $owner_map[ $moderation->id ] : 0;

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

		bb_moderation_restore_suspend_filters();

		// Get status counts for the filter dropdown.
		$status_counts = $this->bb_get_status_counts();

		wp_send_json_success(
			array(
				'items'         => $items,
				'total'         => isset( $result['total'] ) ? (int) $result['total'] : 0,
				'page'          => $page,
				'per_page'      => $per_page,
				'total_pages'   => $per_page > 0 ? (int) ceil( ( isset( $result['total'] ) ? $result['total'] : 0 ) / $per_page ) : 1,
				'status_counts' => $status_counts,
			)
		);
	}

	/**
	 * Get counts for each status filter option using a single query.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return array Associative array with 'all', 'hidden', 'visible' counts.
	 */
	private function bb_get_status_counts() {
		global $wpdb;
		$bp = buddypress();

		$member_type = BP_Moderation_Members::$moderation_type;

		// Single query with conditional aggregation instead of two separate COUNT queries.
		$row = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT COUNT(*) AS total_all, SUM( CASE WHEN hide_sitewide = 1 THEN 1 ELSE 0 END ) AS total_hidden FROM {$bp->moderation->table_name} WHERE item_type != %s AND ( reported != 0 OR user_report != 0 OR hide_sitewide != 0 )",
				$member_type
			)
		);

		$all    = $row ? (int) $row->total_all : 0;
		$hidden = $row ? (int) $row->total_hidden : 0;

		return array(
			'all'     => $all,
			'hidden'  => $hidden,
			'visible' => $all - $hidden,
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
		// Use '_count' meta (total reports) to match the list screen count.
		// '_count_user_reported' only counts user_report=1 and may be 0 when
		// reports are stored with user_report=0 (e.g., content blocks with reasons).
		$report_count = (int) bp_moderation_get_meta( $moderation_id, '_count' );

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
		bb_moderation_bypass_suspend_filters();

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

		// Get all reporters (both reports and blocks).
		// Don't filter by user_report=1 — content reports may be stored with
		// user_report=0 (content blocks with reasons) and should still appear.
		$reporters_raw = BP_Moderation::get_moderation_reporters( $moderation_id );

		// Pre-warm user and term caches for reporters to avoid N+1 queries.
		if ( ! empty( $reporters_raw ) ) {
			$reporter_user_ids = wp_list_pluck( $reporters_raw, 'user_id' );
			cache_users( array_map( 'intval', $reporter_user_ids ) );

			// _prime_term_caches() is available since WP 6.2; guard for WP 6.0 compat.
			if ( function_exists( '_prime_term_caches' ) ) {
				$term_ids = array_unique( array_filter( array_map( 'intval', wp_list_pluck( $reporters_raw, 'category_id' ) ) ) );
				if ( ! empty( $term_ids ) ) {
					_prime_term_caches( $term_ids );
				}
			}
		}

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
						: sanitize_text_field( $reporter->content ),
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

		bb_moderation_restore_suspend_filters();

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

		// Whitelist validate content type.
		if ( ! array_key_exists( $item_type, bp_moderation_content_types() ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid content type.', 'buddyboss' ) ) );
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

		// Whitelist validate content type.
		if ( ! array_key_exists( $item_type, bp_moderation_content_types() ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid content type.', 'buddyboss' ) ) );
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
		if ( user_can( $user_id, 'administrator' ) ) {
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
		$ids    = isset( $_POST['ids'] ) ? array_map( 'absint', wp_unslash( (array) $_POST['ids'] ) ) : array();

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

		// Batch-fetch all moderation rows in a single query instead of N+1.
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$rows         = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT id, item_id, item_type FROM {$bp->moderation->table_name} WHERE id IN ({$placeholders})", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				...$ids
			)
		);

		if ( ! empty( $rows ) ) {
			foreach ( $rows as $row ) {
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
