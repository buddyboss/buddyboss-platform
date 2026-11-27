<?php

/**
 * Repeater migration jobs run from the WP-Cron healthcheck (PROD-10317).
 *
 * Turning a field set into a repeater queues `bb_xprofile_mapping_simple_to_repeater_fields_data`
 * on the background updater. Since the healthcheck runs the queue inside the WP-Cron request
 * instead of an admin-ajax loopback, the callback must be loaded outside wp-admin, otherwise the
 * job is deleted without copying anything.
 *
 * @group xprofile
 * @group bb_xprofile_repeaters
 * @group prod_10317
 */
class BB_Tests_XProfile_Repeater_Migration_Cron extends BP_UnitTestCase {

	public function test_mapping_callback_is_loaded_outside_admin() {
		$this->assertFalse( is_admin() );
		$this->assertTrue( function_exists( 'bb_xprofile_mapping_simple_to_repeater_fields_data' ) );
	}

	public function test_cron_healthcheck_runs_queued_repeater_migration() {
		global $bb_background_updater;

		$group_id = self::factory()->xprofile_group->create();
		$field_id = self::factory()->xprofile_field->create(
			array(
				'field_group_id' => $group_id,
				'type'           => 'textbox',
			)
		);
		$user_id  = self::factory()->user->create();

		xprofile_set_field_data( $field_id, $user_id, 'Existing value' );

		// Enable repeaters on the set; its first set is a clone of the field that starts empty.
		bp_xprofile_update_meta( $group_id, 'group', 'is_repeater_enabled', 'on' );
		wp_cache_delete( 'all', 'bp_xprofile_groups' );
		wp_cache_delete( $group_id, 'bp_xprofile_groups' );

		// Clones are made by the admin saving the field set.
		self::set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$clone_ids = bp_get_repeater_clone_field_ids_subset( $group_id, 1 );
		$this->assertCount( 1, $clone_ids );
		$clone_id = (int) $clone_ids[0];
		$this->assertEmpty( xprofile_get_field_data( $clone_id, $user_id ) );

		// Queue the job the way bb_xprofile_migrate_simple_to_repeater_fields_data() does.
		$bb_background_updater->data(
			array(
				'type'     => 'xprofile',
				'group'    => 'xprofile_simple_field_to_repeater',
				'priority' => 5,
				'callback' => 'bb_xprofile_mapping_simple_to_repeater_fields_data',
				'args'     => array( array( $user_id ), $group_id ),
			)
		);
		$bb_background_updater->save();

		// WP-Cron runs without a logged-in user.
		self::set_current_user( 0 );
		$bb_background_updater->handle_cron_healthcheck();

		$this->assertSame( 'Existing value', xprofile_get_field_data( $clone_id, $user_id ) );
		$this->assertSame( 0, (int) $bb_background_updater->get_job_count_by_type_group( 'xprofile', 'xprofile_simple_field_to_repeater' ) );
	}
}
