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
 * Class adds additional missing hooks from Learndash
 *
 * @since BuddyBoss 1.0.0
 */
class Hooks {

	/**
	 * Constructor
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function __construct() {
		 add_action( 'bp_ld_sync/init', array( $this, 'init' ) );
	}

	/**
	 * Add actions once integration is ready
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function init() {
		// add some helpful missing hooks
		add_action( 'groups_create_group', array( $this, 'groupCreated' ) );
		add_action( 'groups_update_group', array( $this, 'groupUpdated' ) );
		
		// groups_details_updated - When group details updated from manage at front side
		add_action( 'groups_details_updated', array( $this, 'groupUpdated' ) );
		add_action( 'groups_before_delete_group', array( $this, 'groupDeleting' ) );
		add_action( 'groups_delete_group', array( $this, 'groupDeleted' ) );

		// admin
		add_action( 'bp_group_admin_edit_after', array( $this, 'groupUpdated' ) );

		add_action( 'groups_member_before_save', array( $this, 'check_user_can_join_if_ld_sync' ) );
		add_action( 'groups_member_after_save', array( $this, 'groupMemberAdded' ) );
		add_action( 'groups_member_after_remove', array( $this, 'groupMemberRemoved' ) );
		add_action( 'bp_groups_member_after_delete', array( $this, 'groupMemberDeleted' ), 999, 2 );
	}

	/**
	 * Sub action when bp gorup is created
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function groupCreated( $groupId ) {
		do_action( 'bp_ld_sync/buddypress_group_created', $groupId );
	}

	/**
	 * Sub action when bp gorup is updated
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function groupUpdated( $groupId ) {
		do_action( 'bp_ld_sync/buddypress_group_updated', $groupId );
	}

	/**
	 * Sub action before bp gorup is deleted
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function groupDeleting( $groupId ) {
		do_action( 'bp_ld_sync/buddypress_group_deleting', $groupId );
	}

	/**
	 * Sub action after bp gorup is deleted
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function groupDeleted( $groupId ) {
		do_action( 'bp_ld_sync/buddypress_group_deleted', $groupId );
	}

	/**
	 * Sub action when a member is added to bp group, based on type
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function groupMemberAdded( $groupMemberObject ) {
		if ( ! $groupMemberObject->is_confirmed ) {
			return false;
		}

		$groupId  = $groupMemberObject->group_id;
		$memberId = $groupMemberObject->user_id;

		if ( $groupMemberObject->is_banned ) {
			return do_action( 'bp_ld_sync/buddypress_group_member_banned', $groupId, $memberId, $groupMemberObject );
		}

		if ( $groupMemberObject->is_admin ) {
			return do_action( 'bp_ld_sync/buddypress_group_admin_added', $groupId, $memberId, $groupMemberObject );
		}

		if ( $groupMemberObject->is_mod ) {
			return do_action( 'bp_ld_sync/buddypress_group_mod_added', $groupId, $memberId, $groupMemberObject );
		}

		return do_action( 'bp_ld_sync/buddypress_group_member_added', $groupId, $memberId, $groupMemberObject );
	}

	/**
	 * Sub action before save when a member is added to bp group, based on type
	 *
	 * @since BuddyBoss 1.5.0
	 */
	public function check_user_can_join_if_ld_sync( $groupMemberObject ) {

		if ( $groupMemberObject->is_banned ) {
			return do_action( 'bp_ld_sync/bb_ld_before_group_member_banned', $groupMemberObject );
		}

		if ( $groupMemberObject->is_admin ) {
			return do_action( 'bp_ld_sync/bb_ld_before_group_admin_added', $groupMemberObject );
		}

		if ( $groupMemberObject->is_mod ) {
			return do_action( 'bp_ld_sync/bb_ld_before_group_mod_added', $groupMemberObject );
		}

		return do_action( 'bp_ld_sync/bb_ld_before_group_member_added', $groupMemberObject );
	}

	/**
	 * Sub action when a member is deleted to bp group, based on type
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function groupMemberRemoved( $groupMemberObject ) {
		$groupId  = $groupMemberObject->group_id;
		$memberId = $groupMemberObject->user_id;

		if ( $groupMemberObject->is_admin ) {
			return do_action( 'bp_ld_sync/buddypress_group_admin_removed', $groupId, $memberId, $groupMemberObject );
		}

		if ( $groupMemberObject->is_mod ) {
			return do_action( 'bp_ld_sync/buddypress_group_mod_removed', $groupId, $memberId, $groupMemberObject );
		}

		return do_action( 'bp_ld_sync/buddypress_group_member_removed', $groupId, $memberId, $groupMemberObject );
	}

	/**
	 * Hook on user group deleted.
	 *
	 * @since BuddyBoss 1.4.7
	 *
	 * @param $user_id
	 * @param $group_id
	 */
	public function groupMemberDeleted( $user_id, $group_id ) {
		$groupId  = $group_id;
		$memberId = $user_id;
		$group    = groups_get_group( $groupId );

		if ( $group->is_admin ) {

			/**
			 *
			 * Fires when Social Group Organizer is removed.
			 *
			 * @param int $groupId group id
			 * @param int $memberId member id
			 * @param object $group group object
			 *
			 * @since BuddyBoss 1.4.7
			 */
			return do_action( 'bp_ld_sync/buddypress_group_admin_removed', $groupId, $memberId, $group );
		}
		if ( $group->is_mod ) {

			/**
			 *
			 * Fires when Social Group Moderator is removed.
			 *
			 * @param int $groupId group id
			 * @param int $memberId member id
			 * @param object $group group object
			 *
			 * @since BuddyBoss 1.4.7
			 */
			return do_action( 'bp_ld_sync/buddypress_group_mod_removed', $groupId, $memberId, $group );
		}

		return do_action( 'bp_ld_sync/buddypress_group_member_removed', $groupId, $memberId, $group );
	}
}
