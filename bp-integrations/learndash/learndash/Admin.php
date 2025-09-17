<?php
/**
 * BuddyBoss LearnDash integration Admin class.
 *
 * @package BuddyBoss\LearnDash
 * @since BuddyBoss 1.0.0
 */

namespace Buddyboss\LearndashIntegration\Learndash;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class for learndash admin related functions
 *
 * @since BuddyBoss 1.0.0
 */
class Admin {

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
		if ( ! bp_ld_sync( 'settings' )->get( 'learndash.enabled' ) ) {
			return;
		}

		add_action( 'add_meta_boxes', array( $this, 'addGroupSyncMetaBox' ) );
	}

	/**
	 * Add group sync metabox to admin
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function addGroupSyncMetaBox() {
		add_meta_box(
			'bp_ld_sync-learndash-sync',
			__( 'Associated Social Group', 'buddyboss' ),
			array( $this, 'asyncMetaboxHtml' ),
			'groups',
			'side'
		);
	}

	/**
	 * Output group sync metabox html
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function asyncMetaboxHtml() {
		$groupId           = get_the_ID();
		$generator         = bp_ld_sync( 'learndash' )->sync->generator( 0, $groupId );
		$hasBpGroup        = $generator->hasBpGroup();
		$bpGroupId         = $hasBpGroup ? $generator->getBpGroupId() : 0;
		$bpGroup           = groups_get_group( $bpGroupId );
		$availableBpGroups = bp_ld_sync( 'buddypress' )->group->getUnassociatedGroups( $groupId );
		$checked           = get_current_screen()->action == 'add' ? bp_ld_sync( 'settings' )->get( 'learndash.default_auto_sync' ) : $hasBpGroup;

		require bp_ld_sync()->template( '/admin/learndash/sync-meta-box.php' );
	}
}
