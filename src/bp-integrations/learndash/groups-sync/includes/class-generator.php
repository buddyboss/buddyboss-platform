<?php

class LearnDash_BuddyPress_Groups_Sync_Generator {
	protected $ld_group;
	protected $bp_group;
	protected $settings;

	public function __construct( $ld_group ) {
		$this->ld_group = get_post( $ld_group );
		$this->settings = bp_learndash_groups_sync_get_settings();

		$this->bp_group = bp_learndash_groups_sync_get_associated_bp_group( $this->ld_group->ID );
	}

	public function generate() {
		$bp_group_id = groups_create_group( [
			'name'   => $this->ld_group->post_title ?: "LearnDash: {$this->ld_group->ID}",
			'status' => $this->settings['auto_bp_group_privacy'],
		] );

		groups_update_groupmeta( $bp_group_id, 'invite_status', $this->settings['auto_bp_group_invite_status'] );

		return $this->associate( $bp_group_id );
	}

	public function associate( $bp_group_id ) {
		$this->bp_group = groups_get_group( $bp_group_id );

		$this->link_groups();

		return $this;
	}

	public function dissociate() {
		$this->unlink_groups();

		return $this;
	}

	public function sync_all( $sync_leaders, $sync_students ) {
		if ( is_null( $sync_leaders ) ) {
			$sync_leaders = $this->settings['auto_sync_leaders'];
		}

		if ( is_null( $sync_students ) ) {
			$sync_students = $this->settings['auto_sync_students'];
		}

		if ( $sync_leaders ) {
			$this->sync_leaders();
		}

		if ( $sync_students ) {
			$this->sync_students();
		}

		return $this;
	}

	public function sync_leaders() {
		$lb_admin_user    = array();
		foreach ( $this->get_ld_leaders() as $leader ) {
			$lb_admin_user[] = $leader->ID;
			$this->add_user( $leader->ID, $this->settings['auto_sync_leaders_role'] );
		}

		// remove user that are not the ls admin group list
		$this->admin_user = array_unique( array_merge( wp_list_pluck( $this->bp_group->admins, 'user_id' ), wp_list_pluck( $this->bp_group->mods, 'user_id' ) ), SORT_REGULAR );
		$admin_user = array_diff( $this->admin_user, $lb_admin_user );
		foreach ( $admin_user as $user ) {
			if ( ! user_can( $user, 'administrator' ) ) {
				groups_leave_group( absint( $this->bp_group->id ), absint( $user ) );
			}
		}

		return $this;
	}

	public function sync_students() {

		$lb_members_user = array();
		foreach ( $this->get_ld_students() as $leader ) {
			$lb_members_user[] = $leader->ID;
			$this->add_user( $leader->ID );
		}

		// remove user that are not the ls admin group list
		add_filter( 'bp_after_groups_get_group_members_parse_args', array( $this, 'add_group_id' ), 10 );
		$bp_members = groups_get_group_members();
		remove_filter( 'bp_after_groups_get_group_members_parse_args', array( $this, 'add_group_id' ), 10 );
		$members_user = array_diff( wp_list_pluck( $bp_members['members'], 'id' ), $lb_members_user );
		foreach ( $members_user as $user ) {
			groups_leave_group( absint( $this->bp_group->id ), absint( $user ) );
		}

		return $this;
	}

	public function add_group_id( $args ) {
		$args['group_id'] = ! empty( $this->bp_group->id ) ? $this->bp_group->id : 0;

		return $args;
	}

	public function get_bp_group() {
		return $this->bp_group;
	}

	public function get_ld_group() {
		return $this->ld_group;
	}

	protected function link_groups() {
		update_post_meta( $this->ld_group->ID, 'buddypress_group_id', $this->bp_group->id ?: 0 );
		groups_update_groupmeta( $this->bp_group->id ?: 0, 'learndash_group_id', $this->ld_group->ID ?: 0 );
	}

	protected function unlink_groups() {
		if ( $this->ld_group ) {
			update_post_meta( $this->ld_group->ID, 'buddypress_group_id', 0 );
		}

		if ( $this->bp_group ) {
			groups_update_groupmeta( $this->bp_group->id ?: 0, 'learndash_group_id', 0 );
		}

		$this->bp_group = null;
	}

	protected function add_user( $user_id, $type = null ) {
		$group_member = new BP_Groups_Member( $user_id, $this->bp_group->id );

		// not in group as any type
		if ( ! $group_member->id ) {
			groups_join_group( $this->bp_group->id, $user_id );
			$group_member = new BP_Groups_Member( $user_id, $this->bp_group->id );
		}

		if ( $type ) {
			if ( $group_member->is_admin ) {
				return;
			}

			if ( $group_member->is_mod && $type === null ) {
				return;
			}

			$group_member->promote( $type );
		}
	}

	protected function get_ld_leaders() {
		return learndash_get_groups_administrators( $this->ld_group->ID, true );
	}

	protected function get_ld_students() {
		$user_ids = get_post_meta( $this->ld_group->ID, 'learndash_group_users_' . $this->ld_group->ID, true );

		return array_map( function ( $user_id ) {
			return new WP_User( $user_id );
		}, $user_ids );
		// return learndash_get_groups_users($this->ld_group->ID, true);
	}
}
