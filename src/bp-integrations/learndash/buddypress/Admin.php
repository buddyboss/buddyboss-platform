<?php
/**
 * BuddyBoss LearnDash integration admin class.
 *
 * @package BuddyBoss\LearnDash
 * @since BuddyBoss 1.0.0
 */

namespace Buddyboss\LearndashIntegration\Buddypress;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class for all admin related functions
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
		if ( ! bp_ld_sync( 'settings' )->get( 'buddypress.enabled' ) ) {
			return;
		}

		// @todo: Remove after testing — Settings 2.0 handles LearnDash group sync via registry meta fields.
		// add_action( 'bp_group_admin_edit_after', array( $this, 'saveGroupSyncMetaBox' ) );
		add_action( 'bp_groups_admin_meta_boxes', array( $this, 'addGroupSyncMetaBox' ) );

		// Settings 2.0: register group meta fields for the edit modal.
		if ( function_exists( 'bb_admin_meta_field_registry' ) ) {
			add_action( 'bb_register_groups_meta_fields', array( $this, 'registerGroupMetaFields' ), 10, 2 );
		}
	}

	/**
	 * Register LearnDash group meta fields for Settings 2.0 edit modal.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param \BB_Admin_Meta_Field_Registry $registry  The registry instance.
	 * @param string                        $component The component identifier.
	 */
	public function registerGroupMetaFields( $registry, $component ) {

		// Enable LearnDash group sync (checkbox).
		$registry->register(
			$component,
			'ld_group_enable',
			array(
				'label'             => __( 'Allow this group to have a LearnDash Group', 'buddyboss' ),
				'type'              => 'checkbox',
				'tab'               => 'integrations',
				'order'             => 800,
				'save_phase'        => 'after',
				'get_value'         => function ( $group ) {
					$generator = bp_ld_sync( 'buddypress' )->sync->generator( $group->id );
					return $generator->hasLdGroup() ? '1' : '0';
				},
				'save_value'        => function ( $group, $value ) {
					if ( empty( $value ) || '0' === $value ) {
						// Desync from LearnDash.
						bp_ld_sync( 'buddypress' )->sync->generator( $group->id )->desyncFromLearndash();
					}
				},
				'sanitize_callback' => 'sanitize_text_field',
			)
		);

		// Associated LearnDash group select.
		$registry->register(
			$component,
			'ld_group_id',
			array(
				'label'             => __( 'Group', 'buddyboss' ),
				'type'              => 'select',
				'tab'               => 'integrations',
				'order'             => 810,
				'save_phase'        => 'after',
				'conditional'       => array(
					'field' => 'ld_group_enable',
					'value' => '1',
				),
				'get_value'         => function ( $group ) {
					$generator = bp_ld_sync( 'buddypress' )->sync->generator( $group->id );
					return $generator->hasLdGroup() ? $generator->getLdGroupId() : 0;
				},
				'get_options'       => function ( $group ) {
					$options = array(
						array(
							'value' => '0',
							'label' => __( 'Select Group', 'buddyboss' ),
						),
					);

					$generator         = bp_ld_sync( 'buddypress' )->sync->generator( $group->id );
					$current_ld_id     = $generator->hasLdGroup() ? $generator->getLdGroupId() : 0;
					$availableLdGroups = bp_ld_sync( 'learndash' )->group->getUnassociatedGroups( $group->id );

					if ( ! empty( $availableLdGroups ) ) {
						foreach ( $availableLdGroups as $ld_group ) {
							$options[] = array(
								'value' => (string) $ld_group->ID,
								'label' => $ld_group->post_title,
							);
						}
					}

					// Include currently associated group if not in unassociated list.
					if ( ! empty( $current_ld_id ) ) {
						$found = false;
						foreach ( $options as $opt ) {
							if ( (string) $opt['value'] === (string) $current_ld_id ) {
								$found = true;
								break;
							}
						}
						if ( ! $found ) {
							$ld_post = get_post( $current_ld_id );
							if ( $ld_post ) {
								$options[] = array(
									'value' => (string) $current_ld_id,
									'label' => $ld_post->post_title,
								);
							}
						}
					}

					return $options;
				},
				'save_value'        => function ( $group, $value ) {
					$new_ld_id = absint( $value );
					$generator = bp_ld_sync( 'buddypress' )->sync->generator( $group->id );

					// Skip if same group already associated.
					if ( $generator->hasLdGroup() && (int) $generator->getLdGroupId() === $new_ld_id ) {
						return;
					}

					if ( ! empty( $new_ld_id ) ) {
						$generator->associateToLearndash( $new_ld_id )
							->syncBpAdmins()
							->syncBpMods()
							->syncBpUsers();
					}
				},
				'sanitize_callback' => 'absint',
			)
		);
	}

	/**
	 * Save group sync metabox value when bp group is saved from admin
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function saveGroupSyncMetaBox( $groupId ) {
		// created from backend
		if ( bp_ld_sync()->isRequestExists( 'bp-ld-sync-enable' ) && ! bp_ld_sync()->getRequest( 'bp-ld-sync-enable' ) ) {
			bp_ld_sync( 'buddypress' )->sync->generator( $groupId )->desyncFromLearndash();
			return false;
		}

		$newGroup  = bp_ld_sync()->getRequest( 'bp-ld-sync-id', null );
		$generator = bp_ld_sync( 'buddypress' )->sync->generator( $groupId );

		if ( $generator->hasLdGroup() && $generator->getLdGroupId() == $newGroup ) {
			return false;
		}

		$generator->associateToLearndash( $newGroup )
			->syncBpAdmins()
			->syncBpMods()
			->syncBpUsers();
	}

	/**
	 * Add group sync metabox
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function addGroupSyncMetaBox() {
		add_meta_box(
			'bp_ld_sync-buddypress-sync',
			__( 'Associated LearnDash Group', 'buddyboss' ),
			array( $this, 'asyncMetaboxHtml' ),
			get_current_screen()->id,
			'side'
		);
	}

	/**
	 * Output group sync metabox html
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function asyncMetaboxHtml() {
		$groupId           = bp_ld_sync()->getRequest( 'gid' );
		$generator         = bp_ld_sync( 'buddypress' )->sync->generator( $groupId );
		$hasLdGroup        = $generator->hasLdGroup();
		$ldGroupId         = $hasLdGroup ? $generator->getLdGroupId() : 0;
		$ldGroup           = get_post( $ldGroupId );
		$availableLdGroups = bp_ld_sync( 'learndash' )->group->getUnassociatedGroups( $groupId );

		require bp_ld_sync()->template( '/admin/buddypress/sync-meta-box.php' );
	}
}
