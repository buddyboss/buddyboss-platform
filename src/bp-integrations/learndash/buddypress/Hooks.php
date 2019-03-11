<?php
/**
 * BuddyBoss LearnDash integration hooks class.
 * 
 * @package BuddyBoss\LearnDash
 * @since BuddyBoss 1.0.0
 */

namespace Buddyboss\LearndashIntegration\Buddypress;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * 
 * 
 * @since BuddyBoss 1.0.0
 */
class Hooks
{
	/**
	 * 
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function __construct()
	{
		add_action('bp_ld_sync/init', [$this, 'init']);
	}

	/**
	 * 
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function init() {
		// add some helpful missing hooks
		add_action( 'groups_create_group', [ $this, 'groupCreated' ] );
		add_action( 'groups_update_group', [ $this, 'groupUpdated' ] );
		add_action( 'groups_before_delete_group', [ $this, 'groupDeleting' ] );
		add_action( 'groups_delete_group', [ $this, 'groupDeleted' ] );

		// admin
		add_action( 'bp_group_admin_edit_after', [ $this, 'groupUpdated' ] );

		add_action( 'groups_member_after_save', [ $this, 'groupMemberAdded' ] );
		add_action( 'groups_member_after_remove', [ $this, 'groupMemberRemoved' ] );
		add_action( 'bp_ld_sync/export_report_column', [ $this, 'export_report_column' ], 10, 2 );
	}

	/**
	 * 
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function export_report_column( $columns, $report_generator ) {
		if ( ! empty( $report_generator->args['step'] ) && in_array( $report_generator->args['step'], array( 'forum' ) ) ) {
			$columns['status'] = $report_generator->column( 'status' );
		}

		return $columns;
	}

	/**
	 * 
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function groupCreated($groupId)
	{
		do_action('bp_ld_sync/buddypress_group_created', $groupId);
	}

	/**
	 * 
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function groupUpdated($groupId)
	{
		do_action('bp_ld_sync/buddypress_group_updated', $groupId);
	}

	/**
	 * 
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function groupDeleting($groupId)
	{
		do_action('bp_ld_sync/buddypress_group_deleting', $groupId);
	}

	/**
	 * 
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function groupDeleted($groupId)
	{
		do_action('bp_ld_sync/buddypress_group_deleted', $groupId);
	}

	/**
	 * 
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function groupMemberAdded($groupMemberObject)
	{
		if (! $groupMemberObject->is_confirmed) {
			return false;
		}

		$groupId = $groupMemberObject->group_id;
		$memberId = $groupMemberObject->user_id;

		if ($groupMemberObject->is_banned) {
			return do_action('bp_ld_sync/buddypress_group_member_banned', $groupId, $memberId, $groupMemberObject);
		}

		if ($groupMemberObject->is_admin) {
			return do_action('bp_ld_sync/buddypress_group_admin_added', $groupId, $memberId, $groupMemberObject);
		}

		if ($groupMemberObject->is_mod) {
			return do_action('bp_ld_sync/buddypress_group_mod_added', $groupId, $memberId, $groupMemberObject);
		}

		return do_action('bp_ld_sync/buddypress_group_member_added', $groupId, $memberId, $groupMemberObject);
	}

	/**
	 * 
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function groupMemberRemoved($groupMemberObject)
	{
		$groupId = $groupMemberObject->group_id;
		$memberId = $groupMemberObject->user_id;

		if ($groupMemberObject->is_admin) {
			return do_action('bp_ld_sync/buddypress_group_admin_removed', $groupId, $memberId, $groupMemberObject);
		}

		if ($groupMemberObject->is_mod) {
			return do_action('bp_ld_sync/buddypress_group_mod_removed', $groupId, $memberId, $groupMemberObject);
		}

		return do_action('bp_ld_sync/buddypress_group_member_removed', $groupId, $memberId, $groupMemberObject);
	}
}
