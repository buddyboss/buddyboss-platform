<?php
/**
 * BuddyBoss LearnDash integration SyncGenerator class.
 *
 * @since   BuddyBoss 1.0.0
 * @package BuddyBoss\LearnDash
 */

namespace Buddyboss\LearndashIntegration\Library;

use BP_Groups_Member;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class for controlling gorup syncing
 *
 * @since BuddyBoss 1.0.0
 */
class SyncGenerator {

	protected $syncingToLearndash  = false;
	protected $syncingToBuddypress = false;
	protected $bpGroupId;
	protected $ldGroupId;
	protected $syncMetaKey = '_sync_group_id';
	protected $syncTo;

	/**
	 * Constructor
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function __construct( $bpGroupId = 0, $ldGroupId = 0 ) {
		$this->bpGroupId = $bpGroupId;
		$this->ldGroupId = $ldGroupId;

		$this->populateData();
		$this->verifyInputs();
	}

	/**
	 * Check if there's a ld group.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function hasLdGroup() {
		return ! ! $this->ldGroupId;
	}

	/**
	 * Check if there's a bp group.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function hasBpGroup() {
		return ! ! $this->bpGroupId;
	}

	/**
	 * Get the ld group id.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function getLdGroupId() {
		return $this->ldGroupId;
	}

	/**
	 * Get the bp group id.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function getBpGroupId() {
		return $this->bpGroupId;
	}

	/**
	 * Associate current bp group to a ld group.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function associateToLearndash( $ldGroupId = null ) {
		if ( $this->ldGroupId && ! $ldGroupId ) {
			return $this;
		}

		$this->syncingToLearndash(
			function () use ( $ldGroupId ) {
				$ldGroup = get_post( $ldGroupId );

				if ( ! $ldGroupId || ! $ldGroup ) {
					$this->createLearndashGroup();
				} else {
					$this->unsetBpGroupMeta( false )->unsetLdGroupMeta( false );
					$this->ldGroupId = $ldGroupId;
				}

				$this->setSyncGropuIds();
			}
		);

		return $this;
	}

	/**
	 * Un-associate the current bp group from ld group.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function desyncFromLearndash() {
		if ( ! $this->ldGroupId ) {
			return $this;
		}

		$this->unsetSyncGropuIds();

		return $this;
	}

	/**
	 * delete the bp group without triggering sync.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function deleteBpGroup( $bpGroupId ) {
		$this->syncingToBuddypress(
			function () use ( $bpGroupId ) {
				groups_delete_group( $bpGroupId );
			}
		);
	}

	/**
	 * delete the ld group without triggering sync.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function deleteLdGroup( $ldGroupId ) {
		$this->syncingToLearndash(
			function () use ( $ldGroupId ) {
				$this->remove_ld_group_author_role( $ldGroupId );
				wp_delete_post( $ldGroupId, true );
			}
		);
	}

	/**
	 * Remove the 'group_leader' role for Learndash group author.
	 * If the author is not the leader of any gorup.
	 *
	 * @since BuddyBoss 1.6.3
	 *
	 * @param int $ld_group_id Leardash group id.
	 *
	 * @return void
	 *
	 * @uses  learndash_is_group_leader_user()         Is the author has group_leader role.
	 * @uses  learndash_get_administrators_group_ids() Gets the list of group IDs administered by the user.     *
	 * @uses  learndash_is_admin_user()                Is the author has administrator role.
	 */
	public function remove_ld_group_author_role( $ld_group_id ) {

		$ldgroup = get_post( $ld_group_id );
		$author  = $ldgroup->post_author;

		// When the group author has already administrator role.
		if ( learndash_is_admin_user( $author ) ) {
			return;
		}

		// The group author has no group_leader role.
		if ( ! learndash_is_group_leader_user( $author ) ) {
			return;
		}

		// Gets the list of group IDs administered by the user.
		$group_ids = learndash_get_administrators_group_ids( $author );

		if ( count( $group_ids ) > 1 || ! in_array( $ld_group_id, $group_ids, true ) ) {
			return;
		}

		$this->remove_group_leader_role( $author );
	}

	/**
	 * Associate current ld group to bp group.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function associateToBuddypress( $bpGroupId = 0 ) {
		if ( $this->bpGroupId && ! $bpGroupId ) {
			return $this;
		}

		$this->syncingToBuddypress(
			function () use ( $bpGroupId ) {
				$bpGroup = groups_get_group( $bpGroupId );

				if ( ! $bpGroupId || ! $bpGroup->id ) {
					$this->createBuddypressGroup();
				} else {
					$this->unsetBpGroupMeta( false )->unsetLdGroupMeta( false );
					$this->bpGroupId = $bpGroupId;
				}

				$this->setSyncGropuIds();
			}
		);

		return $this;
	}

	/**
	 * Un associate current ld group from bp group.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function desyncFromBuddypress() {
		if ( ! $this->bpGroupId ) {
			return $this;
		}

		$this->unsetSyncGropuIds();

		return $this;
	}

	/**
	 * Run a full users sync up bp group to ld group.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function fullSyncToLearndash() {
		$lastSynced = groups_get_groupmeta( $this->bpGroupId, '_last_sync', true ) ?: 0;

		if ( $lastSynced > $this->getLastSyncTimestamp( 'bp' ) ) {
			return;
		}

		$this->syncBpUsers()->syncBpMods()->syncBpAdmins();
		groups_update_groupmeta( $this->bpGroupId, '_last_sync', time() );
	}

	/**
	 * Run a full users sync up ld group to bp group.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function fullSyncToBuddypress() {
		$lastSynced = groups_get_groupmeta( $this->ldGroupId, '_last_sync', true ) ?: 0;

		if ( $lastSynced > $this->getLastSyncTimestamp( 'ld' ) ) {
			return;
		}

		$this->syncLdAdmins()->syncLdUsers();
		update_post_meta( $this->ldGroupId, '_last_sync', time() );
	}

	/**
	 * Sync the bp admins to ld.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function syncBpAdmins() {
		$this->syncingToLearndash(
			function () {
				$adminIds = groups_get_group_admins( $this->bpGroupId );

				foreach ( $adminIds as $admin ) {
					$this->syncBpAdmin( $admin->user_id, false, false );
				}
			}
		);

		$this->clearLdGroupCache();

		return $this;
	}

	/**
	 * Sync the bp mods to ld.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function syncBpMods() {
		$this->syncingToLearndash(
			function () {
				$modIds = groups_get_group_mods( $this->bpGroupId );

				foreach ( $modIds as $mod ) {
					$this->syncBpMod( $mod->user_id, false, false );
				}
			}
		);

		$this->clearLdGroupCache();

		return $this;
	}

	/**
	 * Sync the bp members to ld.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function syncBpUsers() {
		$this->syncingToLearndash(
			function () {
				$members = groups_get_group_members(
					array(
						'group_id' => $this->bpGroupId,
					)
				);

				$members = $members['members'];

				foreach ( $members as $member ) {
					$this->syncBpMember( $member->ID, false, false );
				}
			}
		);

		$this->clearLdGroupCache();

		return $this;
	}

	/**
	 * Sync the ld admins to bp.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function syncLdAdmins() {
		$this->syncingToBuddypress(
			function () {
				$adminIds = learndash_get_groups_administrator_ids( $this->ldGroupId );

				foreach ( $adminIds as $adminId ) {
					$this->syncLdAdmin( $adminId );
				}
			}
		);

		return $this;
	}

	/**
	 * Sync the ld students to bp.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function syncLdUsers() {
		$this->syncingToBuddypress(
			function () {
				$userIds = learndash_get_groups_user_ids( $this->ldGroupId );

				foreach ( $userIds as $userId ) {
					$this->syncLdUser( $userId );
				}
			}
		);

		return $this;
	}

	/**
	 * Sync a bp admin to ld.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function syncBpAdmin( $userId, $remove = false, $clearCache = true ) {
		if ( empty( $this->ldGroupId ) ) {
			$this->ldGroupId = 0;
		}

		$this->syncingToLearndash(
			function () use ( $userId, $remove ) {
				call_user_func_array( $this->getBpSyncFunction( 'admin' ), array( $userId, $this->ldGroupId, $remove ) );
				$this->maybeRemoveAsLdUser( 'admin', $userId );
				$this->promoteAsGroupLeader( $userId, 'admin', $remove );
			}
		);

		if ( $clearCache ) {
			$this->clearLdGroupCache();
		}

		return $this;
	}

	/**
	 * Sync a bp mod to ld.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function syncBpMod( $userId, $remove = false, $clearCache = true ) {
		if ( empty( $this->ldGroupId ) ) {
			$this->ldGroupId = 0;
		}

		$this->syncingToLearndash(
			function () use ( $userId, $remove ) {
				call_user_func_array( $this->getBpSyncFunction( 'mod' ), array( $userId, $this->ldGroupId, $remove ) );
				$this->maybeRemoveAsLdUser( 'mod', $userId );
				$this->promoteAsGroupLeader( $userId, 'mod', $remove );
			}
		);

		if ( $clearCache ) {
			$this->clearLdGroupCache();
		}

		return $this;
	}

	/**
	 * Sync a bp member to ld.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function syncBpMember( $userId, $remove = false, $clearCache = true ) {
		if ( empty( $this->ldGroupId ) ) {
			$this->ldGroupId = 0;
		}

		$this->syncingToLearndash(
			function () use ( $userId, $remove ) {
				call_user_func_array( $this->getBpSyncFunction( 'user' ), array( $userId, $this->ldGroupId, $remove ) );

				// if sync to user, we need to remove previous admin
				if ( 'user' == $this->getBpSyncToRole( 'user' ) ) {
					call_user_func_array( 'ld_update_leader_group_access', array( $userId, $this->ldGroupId, true ) );
				}
			}
		);

		if ( $clearCache ) {
			$this->clearLdGroupCache();
		}

		return $this;
	}

	/**
	 * Check before Sync a bp member to ld.
	 *
	 * @since BuddyBoss 1.5.0
	 */
	public function syncBeforeBpMember( $group_object ) {

		// If no ld group sync.
		if ( empty( $this->ldGroupId ) ) {
			return $group_object;
		}

		$post_label_prefix = 'group';
		$meta              = learndash_get_setting( $this->ldGroupId );
		$post_price_type   = ( isset( $meta[ $post_label_prefix . '_price_type' ] ) ) ? $meta[ $post_label_prefix . '_price_type' ] : '';
		$post_price        = ( isset( $meta[ $post_label_prefix . '_price' ] ) ) ? $meta[ $post_label_prefix . '_price' ] : '';

		// format the Course price to be proper XXX.YY no leading dollar signs or other values.
		if ( ( 'paynow' === $post_price_type ) || ( 'subscribe' === $post_price_type ) ) {
			if ( '' !== $post_price ) {
				$post_price = preg_replace( '/[^0-9.]/', '', $post_price );
				$post_price = number_format( floatval( $post_price ), 2, '.', '' );
			}
		}

		if ( ! empty( $post_price ) && ! learndash_is_user_in_group( bp_loggedin_user_id(), $this->ldGroupId ) ) {
			$group_object->group_id = null;
		}

		return $group_object;
	}

	/**
	 * Sync a ld admin to bp.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function syncLdAdmin( $userId, $remove = false ) {
		$this->syncingToBuddypress(
			function () use ( $userId, $remove ) {
				$this->addUserToBpGroup( $userId, 'admin', $remove );
			}
		);

		return $this;
	}

	/**
	 * Sync a ld student to bp.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function syncLdUser( $userId, $remove = false ) {

		if ( ! isset( $this->ldGroupId ) ) {
			return $this;
		}

		$ldGroupAdmins = learndash_get_groups_administrator_ids( $this->ldGroupId );

		// if this user is learndash leader, we don't want to downgrad them (bp only allow 1 user)
		if ( in_array( $userId, $ldGroupAdmins ) ) {
			return $this;
		}

		$this->syncingToBuddypress(
			function () use ( $userId, $remove ) {
				$this->addUserToBpGroup( $userId, 'user', $remove );
			}
		);

		return $this;
	}

	/**
	 * Verify the givent group ids still exists in db.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function verifyInputs() {
		if ( $this->bpGroupId && ! groups_get_group( $this->bpGroupId )->id ) {
			$this->unsetBpGroupMeta();
		}

		if ( $this->ldGroupId && ! get_post( $this->ldGroupId ) ) {
			$this->unsetLdGroupMeta();
		}
	}

	/**
	 * Populate the class data based on given input.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function populateData() {
		if ( ! $this->bpGroupId ) {
			$this->bpGroupId = $this->loadBpGroupId();
		}

		if ( ! $this->ldGroupId ) {
			$this->ldGroupId = $this->loadLdGroupId();
		}
	}

	/**
	 * Find the bp group id on current ld group.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function loadBpGroupId() {
		return get_post_meta( $this->ldGroupId, $this->syncMetaKey, true ) ?: null;
	}

	/**
	 * Find the ld group id on current bp group.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function loadLdGroupId() {
		if ( function_exists( 'groups_get_groupmeta' ) ) {
			return groups_get_groupmeta( $this->bpGroupId, $this->syncMetaKey, true ) ?: null;
		}
	}

	/**
	 * Sasve bp group id to current ld group.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function setBpGroupId() {
		update_post_meta( $this->ldGroupId, $this->syncMetaKey, $this->bpGroupId );

		return $this;
	}

	/**
	 * Sasve ld group id to current bp group.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function setLdGroupId() {
		if ( function_exists( 'groups_get_groupmeta' ) ) {
			groups_update_groupmeta( $this->bpGroupId, $this->syncMetaKey, $this->ldGroupId );
		}

		return $this;
	}

	/**
	 * Force id sync.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function setSyncGropuIds() {
		return $this->setLdGroupId()->setBpGroupId();
	}

	/**
	 * Remove bp group id from current ld group.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function unsetBpGroupMeta( $removeProp = true ) {
		if ( $removeProp ) {
			$this->bpGroupId = 0;
		}

		delete_post_meta( $this->ldGroupId, $this->syncMetaKey );

		return $this;
	}

	/**
	 * Remove ld group id from current bp group.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function unsetLdGroupMeta( $removeProp = true ) {
		if ( $removeProp ) {
			$this->ldGroupId = null;
		}

		groups_delete_groupmeta( $this->bpGroupId, $this->syncMetaKey );

		return $this;
	}

	/**
	 * Force unsync group ids.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function unsetSyncGropuIds() {
		$this->unsetBpGroupMeta( false )->unsetLdGroupMeta( false );
		$this->bpGroupId = $this->ldGroupId = 0;

		return $this;
	}

	/**
	 * Greate a ld group based on current bp group.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function createLearndashGroup() {
		$bpGroup = groups_get_group( $this->bpGroupId );

		$this->ldGroupId = wp_insert_post(
			array(
				'post_title'   => $bpGroup->name,
				'post_author'  => $bpGroup->creator_id,
				'post_content' => $bpGroup->description,
				'post_status'  => 'publish',
				'post_type'    => learndash_get_post_type_slug( 'group' ),
			)
		);
	}

	/**
	 * Create bp group based on current ld group.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function createBuddypressGroup() {
		$ldGroup  = get_post( $this->ldGroupId );
		$settings = bp_ld_sync( 'settings' );

		// Get the bp parent group id associate with ld parent group.
		$bp_parent_group_id = 0;
		if ( ! empty( $ldGroup->post_parent ) ) {
			$bp_parent_group_id = get_post_meta( $ldGroup->post_parent, '_sync_group_id', true );
		}

		$this->bpGroupId = groups_create_group(
			array(
				'name'      => $ldGroup->post_title ?: sprintf( __( 'For Social Group: %s', 'buddyboss' ), $this->ldGroupId ),
				'status'    => $settings->get( 'learndash.default_bp_privacy' ),
				'parent_id' => $bp_parent_group_id,
			)
		);

		groups_update_groupmeta( $this->bpGroupId, 'invite_status', $settings->get( 'learndash.default_bp_invite_status' ) );

		$this->setSyncGropuIds();
	}

	/**
	 * Maybe remove ld user if user is promote or demote from bp.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function maybeRemoveAsLdUser( $type, $userId ) {
		if ( 'user' == $this->getBpSyncToRole( $type ) ) {
			return;
		}

		// remove them as user, cause they are leader now
		ld_update_group_access( $userId, $this->ldGroupId, true );
	}

	/**
	 * Get the bp role to sync to.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function getBpSyncToRole( $type ) {
		return bp_ld_sync( 'settings' )->get( "buddypress.default_{$type}_sync_to" );
	}

	/**
	 * Get the function that update ld group role.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function getBpSyncFunction( $type ) {
		switch ( $this->getBpSyncToRole( $type ) ) {
			case 'admin':
				return 'ld_update_leader_group_access';
			default:
				return 'ld_update_group_access';
		}
	}

	/**
	 * Get the ld role to sync to.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function getLdSyncToRole( $type ) {
		return bp_ld_sync( 'settings' )->get( "learndash.default_{$type}_sync_to" );
	}

	/**
	 * Add a user to bp group by role.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function addUserToBpGroup( $userId, $type, $remove ) {
		$groupMember = new BP_Groups_Member( $userId, $this->bpGroupId );

		$this->syncTo = $this->getLdSyncToRole( $type );

		// ignore moderator in syncing as there's no moderator in learndash.
		if ( 1 === $groupMember->is_mod && 'admin' === $this->syncTo ) {
			return false;
		}

		if ( $remove ) {
			if ( bp_is_active( 'messages' ) ) {
				bp_messages_remove_user_to_group_message_thread( $this->bpGroupId, $userId );
			}

			return $groupMember->remove();
		}

		add_action( 'groups_member_before_save', array( $this, 'update_group_member_role' ), 10 );

		groups_join_group( $this->bpGroupId, $userId );

		remove_action( 'groups_member_before_save', array( $this, 'update_group_member_role' ), 10 );
	}

	/**
	 * Clear the ld cache after sync.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function clearLdGroupCache() {
		delete_transient( "learndash_group_leaders_{$this->ldGroupId}" );
		delete_transient( "learndash_group_users_{$this->ldGroupId}" );
	}

	/**
	 * Wrapper to prevent infinite 2 way sync when syncing to learndash.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function syncingToLearndash( $callback ) {
		global $bp_ld_sync__syncing_to_learndash;

		$bp_ld_sync__syncing_to_learndash = true;
		$callback();
		$bp_ld_sync__syncing_to_learndash = false;

		return $this;
	}

	/**
	 * Wrapper to prevent infinite 2 way sync when syncing to buddypress.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function syncingToBuddypress( $callback ) {
		global $bp_ld_sync__syncing_to_buddypress;

		$bp_ld_sync__syncing_to_buddypress = true;
		$callback();
		$bp_ld_sync__syncing_to_buddypress = false;

		return $this;
	}

	/**
	 * Get the timestamp when the group is last synced.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function getLastSyncTimestamp( $type = 'bp' ) {
		if ( ! $lastSync = bp_get_option( "bp_ld_sync/{$type}_last_synced" ) ) {
			$lastSync = time();
			bp_update_option( "bp_ld_sync/{$type}_last_synced", $lastSync );
		}

		return $lastSync;
	}

	/**
	 * Update a ld group based on current bp group.
	 *
	 * @since BuddyBoss 1.5.7
	 *
	 * @param int    $groupId     Group id for buddyboss.
	 * @param string $ld_group_id Group id for learndash.
	 */
	public function updateLearndashGroup( $ld_group_id, $groupId ) {
		$bpGroup = groups_get_group( $groupId );

		if ( ! empty( $ld_group_id ) ) {
			wp_update_post(
				array(
					'ID'           => $ld_group_id,
					'post_title'   => $bpGroup->name,
					'post_author'  => $bpGroup->creator_id,
					'post_content' => $bpGroup->description,
					'post_status'  => 'publish',
					'post_type'    => learndash_get_post_type_slug( 'group' ),
				)
			);
		}
	}

	/**
	 * Update a bp group based on current ld group.
	 *
	 * @since BuddyBoss 1.5.7
	 *
	 * @param int    $groupId     Group id for buddyboss.
	 * @param string $ld_group_id Group id for learndash.
	 */
	public function updateBuddypressGroup( $ld_group_id, $groupId ) {
		$ldGroup  = get_post( $ld_group_id );
		$settings = bp_ld_sync( 'settings' );

		if ( ! empty( $groupId ) ) {

			// Get the bp parent group id associate with ld parent group.
			$bp_parent_group_id = 0;
			if ( ! empty( $ldGroup->post_parent ) ) {
				$bp_parent_group_id = get_post_meta( $ldGroup->post_parent, '_sync_group_id', true );
			}

			groups_create_group(
				array(
					'group_id'    => $groupId,
					'creator_id'  => $ldGroup->post_author,
					'name'        => $ldGroup->post_title ?: sprintf( __( 'For Social Group: %s', 'buddyboss' ), $this->ldGroupId ),
					//'status'      => $settings->get( 'learndash.default_bp_privacy' ),
					'description' => $ldGroup->post_content,
					'slug'        => $ldGroup->post_name,
					'parent_id'   => $bp_parent_group_id,
				)
			);

			groups_update_groupmeta( $groupId, 'invite_status', $settings->get( 'learndash.default_bp_invite_status' ) );
		}

		$this->setSyncGropuIds();
	}

	/**
	 * Promote the uesr as a learndash group leader.
	 *
	 * @since BuddyBoss 1.6.3
	 *
	 * @param int $userId Member id.
	 *
	 * @return void
	 */
	public function promoteAsGroupLeader( $userId, $ldRole, $remove = false ) {
		// Default settings options.
		$options = $this->default_sync_options();

		// When synchronization disable.
		if ( empty( $options ) ) {
			return;
		}

		// Remove user.
		if ( true === $remove || 'user' === $ldRole ) {
			$this->remove_group_leader_role( $userId );

			return;
		}

		// Set learndash admin role.
		if ( 'admin' === $ldRole ) {
			$this->member_role_generate( $userId, $options['admin'] );
		}

		// Set learndash moderator role.
		if ( 'mod' === $ldRole ) {
			$this->member_role_generate( $userId, $options['mod'] );
		}
	}

	/**
	 * Get group to learndash sync setting options.
	 *
	 * @since BuddyBoss 1.6.3
	 *
	 * @return array
	 *
	 * @uses  bp_get_option() Get options value.
	 */
	public function default_sync_options() {
		$options = bp_get_option( 'bp_ld_sync_settings', array() );

		if ( empty( $options['buddypress'] ) || empty( $options['buddypress']['enabled'] ) ) {
			return array();
		}

		$option_admin = empty( $options['buddypress']['default_admin_sync_to'] ) ? 'admin' : $options['buddypress']['default_admin_sync_to'];
		$option_mod   = empty( $options['buddypress']['default_mod_sync_to'] ) ? 'admin' : $options['buddypress']['default_mod_sync_to'];

		return array(
			'admin' => $option_admin,
			'mod'   => $option_mod,
		);
	}

	/**
	 * Create or remove learndash group leader role for BB group member.
	 *
	 * @since BuddyBoss 1.6.3
	 *
	 * @param int    $userId BB group member id.
	 * @param string $role   BB member role in group.
	 *
	 * @return void
	 *
	 * @uses  remove_group_leader_role() Remove group leader role.
	 * @uses  set_group_leader_role()    Add group leader role.
	 */
	public function member_role_generate( $userId, $role ) {
		if ( 'admin' === $role ) {
			$this->set_group_leader_role( $userId );
		} else {
			$this->remove_group_leader_role( $userId );
		}
	}

	/**
	 * Add BB group member role as LD group leader.
	 *
	 * @since BuddyBoss 1.6.3
	 *
	 * @param int $userID Member id.
	 *
	 * @return void
	 *
	 * @uses  learndash_is_group_leader_user() Is member has already group leader role.
	 * @uses  learndash_is_admin_user()        Is member admin user.
	 */
	public function set_group_leader_role( $userId ) {
		// If the user has already 'Administrator' or 'group_leader' role.
		if ( learndash_is_admin_user( $userId ) || learndash_is_group_leader_user( $userId ) ) {
			return;
		}

		$user = new \WP_User( $userId );
		// Add role
		$user->add_role( 'group_leader' );
	}

	/**
	 * Remove LD group leader role.
	 *
	 * @since BuddyBoss 1.6.3
	 *
	 * @param int $userID Member id.
	 *
	 * @return void
	 */
	public function remove_group_leader_role( $userId ) {
		$user = new \WP_User( (int) $userId );
		// Remove role
		$user->remove_role( 'group_leader' );
	}

	/**
	 * Update BB group member role.
	 *
	 * @since 2.4.40
	 *
	 * @param object $group_member Member item.
	 *
	 * @return void
	 */
	public function update_group_member_role( $group_member ) {
		if ( 'user' !== $this->syncTo ) {
			$var				= "is_{$this->syncTo}";
			$group_member->$var = 1;
		}
	}
}
