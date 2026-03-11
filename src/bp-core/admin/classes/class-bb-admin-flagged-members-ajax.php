<?php
/**
 * BuddyBoss Flagged Members Admin AJAX Handler
 *
 * Handles AJAX requests for Flagged Members list
 * in the Settings 2.0 admin interface.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Core\Administration
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class BB_Admin_Flagged_Members_Ajax
 *
 * @since BuddyBoss [BBVERSION]
 */
class BB_Admin_Flagged_Members_Ajax {

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
		add_action( 'wp_ajax_bb_admin_get_flagged_members', array( $this, 'bb_get_flagged_members' ) );
		add_action( 'wp_ajax_bb_admin_get_member_report', array( $this, 'bb_get_member_report' ) );
		add_action( 'wp_ajax_bb_admin_suspend_member', array( $this, 'bb_suspend_member' ) );
		add_action( 'wp_ajax_bb_admin_unsuspend_member', array( $this, 'bb_unsuspend_member' ) );
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
	 * Get flagged members list.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_get_flagged_members() {
		$this->bb_verify_request();

		$page     = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
		$per_page = isset( $_POST['per_page'] ) ? absint( $_POST['per_page'] ) : 20;
		$search   = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';

		$moderation_args = array(
			'page'         => $page,
			'per_page'     => $per_page,
			'count_total'  => true,
			'in_types'     => array( BP_Moderation_Members::$moderation_type ),
			'reported'     => false,
			'search_terms' => ! empty( $search ) ? $search : false,
		);

		// Apply the same "all" filter as the old list table: show items with reported, user_report, or hide_sitewide.
		add_filter( 'bp_moderation_get_where_conditions', array( $this, 'bb_update_where_conditions' ), 10, 2 );

		$result = BP_Moderation::get( $moderation_args );

		remove_filter( 'bp_moderation_get_where_conditions', array( $this, 'bb_update_where_conditions' ), 10 );

		$members = array();
		$admins  = array_map( 'intval', get_users( array( 'role' => 'administrator', 'fields' => 'ID' ) ) );

		if ( ! empty( $result['moderations'] ) ) {
			foreach ( $result['moderations'] as $item ) {
				$user_id      = (int) $item->item_id;
				$is_suspended = (int) $item->hide_sitewide === 1;
				$is_admin     = in_array( $user_id, $admins, true );
				$block_count  = isset( $item->count ) ? (int) $item->count : 0;
				$report_count = isset( $item->count_report ) ? (int) $item->count_report : 0;

				$members[] = array(
					'id'            => (int) $item->id,
					'user_id'       => $user_id,
					'display_name'  => bp_core_get_user_displayname( $user_id ),
					'avatar'        => bp_core_fetch_avatar(
						array(
							'item_id' => $user_id,
							'type'    => 'thumb',
							'width'   => 40,
							'height'  => 40,
							'html'    => false,
						)
					),
					'profile_url'   => bp_core_get_user_domain( $user_id ),
					'blocks'        => $block_count,
					'reports'       => $report_count,
					'is_suspended'  => $is_suspended,
					'is_admin'      => $is_admin,
				);
			}
		}

		wp_send_json_success(
			array(
				'members'    => $members,
				'total'      => isset( $result['total'] ) ? (int) $result['total'] : 0,
				'page'       => $page,
				'per_page'   => $per_page,
				'total_pages' => $per_page > 0 ? (int) ceil( ( isset( $result['total'] ) ? $result['total'] : 0 ) / $per_page ) : 1,
			)
		);
	}

	/**
	 * Get member report details (reporters + blockers).
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_get_member_report() {
		$this->bb_verify_request();

		$user_id       = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
		$moderation_id = isset( $_POST['moderation_id'] ) ? absint( $_POST['moderation_id'] ) : 0;

		if ( empty( $user_id ) || empty( $moderation_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid member ID.', 'buddyboss' ) ) );
		}

		// Load the moderation record directly by ID.
		global $wpdb;
		$bp  = buddypress();
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bp->moderation->table_name} WHERE id = %d", $moderation_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( empty( $row ) || (int) $row->item_id !== $user_id ) {
			wp_send_json_error( array( 'message' => __( 'No moderation record found for this member.', 'buddyboss' ) ) );
		}

		// Member summary.
		$is_suspended = (int) $row->hide_sitewide === 1;
		$block_count  = (int) bp_moderation_get_meta( $moderation_id, '_count' );
		$report_count = (int) bp_moderation_get_meta( $moderation_id, '_count_user_reported' );

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

		// Get blockers (user_report = 0).
		$blockers_raw = BP_Moderation::get_moderation_reporters(
			$moderation_id,
			array( 'user_repoted' => false )
		);

		$blockers = array();
		if ( ! empty( $blockers_raw ) ) {
			foreach ( $blockers_raw as $blocker ) {
				$blockers[] = array(
					'user_id'      => (int) $blocker->user_id,
					'display_name' => bp_core_get_user_displayname( $blocker->user_id ),
					'avatar'       => bp_core_fetch_avatar(
						array(
							'item_id' => $blocker->user_id,
							'type'    => 'thumb',
							'width'   => 40,
							'height'  => 40,
							'html'    => false,
						)
					),
					'profile_url'  => bp_core_get_user_domain( $blocker->user_id ),
					'date'         => ! empty( $blocker->date_created )
						? date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $blocker->date_created ) )
						: '',
				);
			}
		}

		wp_send_json_success(
			array(
				'user_id'       => $user_id,
				'display_name'  => bp_core_get_user_displayname( $user_id ),
				'avatar'        => bp_core_fetch_avatar(
					array(
						'item_id' => $user_id,
						'type'    => 'thumb',
						'width'   => 40,
						'height'  => 40,
						'html'    => false,
					)
				),
				'profile_url'   => bp_core_get_user_domain( $user_id ),
				'blocks'        => $block_count,
				'reports'       => $report_count,
				'is_suspended'  => $is_suspended,
				'reporters'     => $reporters,
				'blockers'      => $blockers,
			)
		);
	}

	/**
	 * Suspend a member.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_suspend_member() {
		$this->bb_verify_request();

		$user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;

		if ( empty( $user_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid member ID.', 'buddyboss' ) ) );
		}

		// Don't allow suspending administrators.
		$admins = array_map( 'intval', get_users( array( 'role' => 'administrator', 'fields' => 'ID' ) ) );
		if ( in_array( $user_id, $admins, true ) ) {
			wp_send_json_error( array( 'message' => __( 'Cannot suspend an administrator.', 'buddyboss' ) ) );
		}

		if ( bp_moderation_is_user_suspended( $user_id ) ) {
			wp_send_json_error( array( 'message' => __( 'This member is already suspended.', 'buddyboss' ) ) );
		}

		BP_Suspend_Member::suspend_user( $user_id );

		wp_send_json_success(
			array( 'message' => __( 'Member suspended successfully.', 'buddyboss' ) )
		);
	}

	/**
	 * Unsuspend a member.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_unsuspend_member() {
		$this->bb_verify_request();

		$user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;

		if ( empty( $user_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid member ID.', 'buddyboss' ) ) );
		}

		if ( ! bp_moderation_is_user_suspended( $user_id ) ) {
			wp_send_json_error( array( 'message' => __( 'This member is not currently suspended.', 'buddyboss' ) ) );
		}

		BP_Suspend_Member::unsuspend_user( $user_id );

		wp_send_json_success(
			array( 'message' => __( 'Member unsuspended successfully.', 'buddyboss' ) )
		);
	}

	/**
	 * Filter WHERE conditions for "all" flagged members view.
	 *
	 * Shows items with reported, user_report, or hide_sitewide set — replicates
	 * the old BP_Moderation_List_Table logic.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $where_conditions Current conditions.
	 * @param array $r                Parsed arguments.
	 *
	 * @return array
	 */
	public function bb_update_where_conditions( $where_conditions, $r ) {
		$where_conditions['reported'] = '( ms.reported != 0 OR ms.user_report != 0 OR ms.hide_sitewide != 0 )';

		return $where_conditions;
	}
}

// Initialize.
new BB_Admin_Flagged_Members_Ajax();
