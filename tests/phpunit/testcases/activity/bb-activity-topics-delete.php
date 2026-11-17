<?php
/**
 * Activity topic relationship cleanup on activity delete (PROD-10670).
 *
 * @group activity
 * @group bb_activity_topics
 */
class BB_Tests_Activity_Topics_Delete extends BP_UnitTestCase {

	public function set_up() {
		parent::set_up();

		// bp-activity-filters.php registers the cleanup at load; re-arm it in case a test removed it.
		if ( ! has_action( 'bp_activity_after_delete', 'bb_delete_topic_relationships_of_deleted_activities' ) ) {
			add_action( 'bp_activity_after_delete', 'bb_delete_topic_relationships_of_deleted_activities' );
		}

		// Site-wide topics can only be added by a moderator (bb_topic_before_added validation).
		self::set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
	}

	/**
	 * Create a site-wide topic usable by anyone.
	 *
	 * @return int Topic ID.
	 */
	private function create_topic( $name = 'PROD-10670 topic' ) {
		$topic = bb_topics_manager_instance()->bb_add_topic(
			array(
				'name'            => $name,
				'permission_type' => 'anyone',
				'item_type'       => 'activity',
			)
		);
		$this->assertNotEmpty( $topic->topic_id );

		return (int) $topic->topic_id;
	}

	/**
	 * Create an activity update and tag it with the topic.
	 *
	 * @return int Activity ID.
	 */
	private function create_tagged_activity( $topic_id, $user_id ) {
		$activity_id = self::factory()->activity->create(
			array(
				'component' => buddypress()->activity->id,
				'type'      => 'activity_update',
				'user_id'   => $user_id,
			)
		);
		bb_activity_topics_manager_instance()->bb_add_activity_topic_relationship(
			array(
				'topic_id'    => $topic_id,
				'activity_id' => $activity_id,
				'component'   => buddypress()->activity->id,
				'item_id'     => 0,
			)
		);
		$this->assertSame( $topic_id, (int) bb_activity_topics_manager_instance()->bb_get_activity_topic( $activity_id ) );

		return $activity_id;
	}

	private function relationship_count( $activity_id ) {
		global $wpdb;
		$table = bb_topics_manager_instance()->activity_topic_rel_table;

		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$table}` WHERE activity_id = %d", $activity_id ) ); // phpcs:ignore
	}

	private function insert_orphan_relationship( $topic_id, $activity_id ) {
		global $wpdb;
		$wpdb->insert(
			bb_topics_manager_instance()->activity_topic_rel_table,
			array(
				'topic_id'     => $topic_id,
				'activity_id'  => $activity_id,
				'component'    => 'activity',
				'item_id'      => 0,
				'date_created' => bp_core_current_time(),
				'date_updated' => bp_core_current_time(),
			),
			array( '%d', '%d', '%s', '%d', '%s', '%s' )
		);
		$this->assertSame( 1, $this->relationship_count( $activity_id ) );
	}

	public function test_the_cleanup_is_hooked_outside_the_topics_manager() {
		$this->assertSame( 10, has_action( 'bp_activity_after_delete', 'bb_delete_topic_relationships_of_deleted_activities' ) );

		// The topics manager (only built while topics are on) no longer owns the cleanup.
		$manager = new BB_Activity_Topics_Manager();
		$this->assertFalse( method_exists( $manager, 'bb_delete_activity_topic_relationship_on_activity_delete' ) );
	}

	public function test_deleting_an_activity_removes_its_topic_relationship_while_topics_are_off() {
		$u     = self::factory()->user->create();
		$topic = $this->create_topic();
		$a     = $this->create_tagged_activity( $topic, $u );

		$enabled = bp_get_option( 'bb_enable_activity_topics' );
		bp_update_option( 'bb_enable_activity_topics', 0 );
		$this->assertTrue( bp_activity_delete( array( 'id' => $a ) ) );
		bp_update_option( 'bb_enable_activity_topics', $enabled );

		$this->assertSame( 0, $this->relationship_count( $a ), 'No orphan row is left while topics are off.' );
	}

	public function test_deleting_an_activity_removes_its_topic_relationship() {
		$u     = self::factory()->user->create();
		$topic = $this->create_topic();
		$a     = $this->create_tagged_activity( $topic, $u );

		$this->assertTrue( bp_activity_delete( array( 'id' => $a ) ) );

		$this->assertSame( 0, $this->relationship_count( $a ) );
		$this->assertEmpty( bb_activity_topics_manager_instance()->bb_get_activity_topic( $a ) );
	}

	public function test_bulk_delete_removes_every_relationship_without_per_row_actions() {
		$u     = self::factory()->user->create();
		$topic = $this->create_topic();
		$ids   = array(
			$this->create_tagged_activity( $topic, $u ),
			$this->create_tagged_activity( $topic, $u ),
			$this->create_tagged_activity( $topic, $u ),
		);
		$other = $this->create_tagged_activity( $topic, self::factory()->user->create() );

		$before = did_action( 'bb_before_delete_activity_topic_relationship' );
		$after  = did_action( 'bb_after_delete_activity_topic_relationship' );
		bp_activity_delete( array( 'user_id' => $u ) );

		foreach ( $ids as $a ) {
			$this->assertSame( 0, $this->relationship_count( $a ) );
		}
		$this->assertSame( 1, $this->relationship_count( $other ), 'Another member\'s post keeps its topic.' );

		// Each per-row action used to purge whole cache groups (App API cache); the activity delete purges them once.
		$this->assertSame( $before, did_action( 'bb_before_delete_activity_topic_relationship' ) );
		$this->assertSame( $after, did_action( 'bb_after_delete_activity_topic_relationship' ) );
	}

	public function test_orphan_repair_removes_rows_in_batches_and_keeps_live_rows() {
		$u     = self::factory()->user->create();
		$topic = $this->create_topic();
		$live  = $this->create_tagged_activity( $topic, $u );

		$this->insert_orphan_relationship( $topic, 9900001 );
		$this->insert_orphan_relationship( $topic, 9900002 );
		$this->insert_orphan_relationship( $topic, 9900003 );

		$manager = bb_activity_topics_manager_instance();
		$this->assertSame( 2, $manager->bb_delete_orphan_activity_topic_relationships( 2 ) );
		$this->assertSame( 1, $manager->bb_delete_orphan_activity_topic_relationships( 2 ) );
		$this->assertSame( 0, $manager->bb_delete_orphan_activity_topic_relationships( 2 ), 'Idempotent: nothing left to remove.' );

		$this->assertSame( 1, $this->relationship_count( $live ) );
		$this->assertSame( 0, $this->relationship_count( 9900001 ) + $this->relationship_count( 9900002 ) + $this->relationship_count( 9900003 ) );
	}

	public function test_repair_tool_is_registered_and_paginates() {
		if ( ! function_exists( 'bp_admin_repair_list' ) ) {
			require_once BP_PLUGIN_DIR . '/bp-core/admin/bp-core-admin-tools.php';
		}
		$slugs = wp_list_pluck( bp_admin_repair_list(), 0 );
		$this->assertContains( 'bp-activity-topic-relationships', $slugs );

		$topic = $this->create_topic();
		$this->insert_orphan_relationship( $topic, 9900011 );

		$_POST['offset'] = 0;
		$first           = bb_admin_repair_activity_topic_relationships();
		$this->assertSame( 'running', $first['status'] );
		$this->assertSame( 1, $first['offset'] );

		$_POST['offset'] = $first['offset'];
		$second          = bb_admin_repair_activity_topic_relationships();
		unset( $_POST['offset'] );
		$this->assertSame( 1, $second['status'] );
		$this->assertStringContainsString( 'Complete', $second['message'] );
		$this->assertSame( 0, $this->relationship_count( 9900011 ) );
	}
}
