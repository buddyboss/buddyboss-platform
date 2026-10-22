<?php
/**
 * Topic reordering per item and by group organizers (PROD-10670 item 6).
 *
 * @group activity
 * @group bb_activity_topics
 */
// Pro owns this helper; the Platform suite runs without Pro, so mirror its one-line body.
if ( ! function_exists( 'bb_is_enabled_group_activity_topics' ) ) {
	function bb_is_enabled_group_activity_topics( $enable_group_topics = false ) {
		return (bool) apply_filters( 'bb_is_enabled_group_activity_topics', bp_get_option( 'bb-enable-group-activity-topics', $enable_group_topics ) );
	}
}

class BB_Tests_Topics_Order extends BP_UnitTestCase {

	private $admin;

	public function set_up() {
		parent::set_up();
		$this->admin = self::factory()->user->create( array( 'role' => 'administrator' ) );
		bp_update_option( 'bb-enable-group-activity-topics', 1 );
	}

	public function tear_down() {
		bp_delete_option( 'bb-enable-group-activity-topics' );
		parent::tear_down();
	}

	private function add_topic( $name, $item_type = 'activity', $item_id = 0, $permission = null ) {
		self::set_current_user( $this->admin );
		$topic = bb_topics_manager_instance()->bb_add_topic(
			array(
				'name'            => $name,
				'permission_type' => $permission ? $permission : ( 'groups' === $item_type ? 'members' : 'anyone' ),
				'item_type'       => $item_type,
				'item_id'         => $item_id,
			)
		);
		$this->assertNotEmpty( $topic->topic_id, "topic $name" );

		return (int) $topic->topic_id;
	}

	private function order_of( $item_type, $item_id ) {
		global $wpdb;
		$table = bb_topics_manager_instance()->topic_rel_table;

		return array_map( 'intval', $wpdb->get_col( $wpdb->prepare( "SELECT topic_id FROM `{$table}` WHERE item_type = %s AND item_id = %d ORDER BY menu_order ASC, id ASC", $item_type, $item_id ) ) ); // phpcs:ignore
	}

	public function test_reorder_touches_only_the_items_own_rows() {
		$organizer = self::factory()->user->create();
		$g1        = self::factory()->group->create( array( 'creator_id' => $organizer ) );
		$g2        = self::factory()->group->create( array( 'creator_id' => $this->admin ) );

		// The same two names adopted site-wide and in both groups (rows are shared by slug).
		$a = $this->add_topic( 'Alpha' );
		$b = $this->add_topic( 'Beta' );
		$this->add_topic( 'Alpha', 'groups', $g1 );
		$this->add_topic( 'Beta', 'groups', $g1 );
		$this->add_topic( 'Alpha', 'groups', $g2 );
		$this->add_topic( 'Beta', 'groups', $g2 );

		$this->assertSame( array( $a, $b ), $this->order_of( 'activity', 0 ) );
		$this->assertSame( array( $a, $b ), $this->order_of( 'groups', $g1 ) );
		$this->assertSame( array( $a, $b ), $this->order_of( 'groups', $g2 ) );

		$this->assertTrue( bb_topics_manager_instance()->bb_update_topics_order( array( $b, $a ), $g1, 'groups' ) );

		$this->assertSame( array( $b, $a ), $this->order_of( 'groups', $g1 ), 'group 1 reordered' );
		$this->assertSame( array( $a, $b ), $this->order_of( 'groups', $g2 ), 'group 2 untouched' );
		$this->assertSame( array( $a, $b ), $this->order_of( 'activity', 0 ), 'site list untouched' );

		$this->assertTrue( bb_topics_manager_instance()->bb_update_topics_order( array( $b, $a ), 0, 'activity' ) );
		$this->assertSame( array( $b, $a ), $this->order_of( 'activity', 0 ) );
		$this->assertSame( array( $a, $b ), $this->order_of( 'groups', $g2 ), 'site reorder leaves group 2 alone' );
	}

	public function test_only_moderators_and_the_groups_organizer_may_reorder() {
		$organizer = self::factory()->user->create();
		$member    = self::factory()->user->create();
		$g         = self::factory()->group->create( array( 'creator_id' => $organizer ) );
		groups_join_group( $g, $member );
		$other = self::factory()->group->create( array( 'creator_id' => $member ) );

		$m = bb_topics_manager_instance();

		self::set_current_user( 0 );
		$this->assertFalse( $m->bb_can_update_topics_order( $g, 'groups' ) );

		self::set_current_user( $member );
		$this->assertFalse( $m->bb_can_update_topics_order( $g, 'groups' ), 'plain member' );
		$this->assertFalse( $m->bb_can_update_topics_order( 0, 'activity' ), 'site list needs a moderator' );

		self::set_current_user( $organizer );
		$this->assertTrue( $m->bb_can_update_topics_order( $g, 'groups' ), 'own group' );
		$this->assertFalse( $m->bb_can_update_topics_order( $other, 'groups' ), 'someone else\'s group' );
		$this->assertFalse( $m->bb_can_update_topics_order( 0, 'activity' ), 'organizer is not a moderator' );
		$this->assertFalse( $m->bb_can_update_topics_order( 0, 'groups' ), 'group id required' );

		bp_update_option( 'bb-enable-group-activity-topics', 0 );
		$this->assertFalse( $m->bb_can_update_topics_order( $g, 'groups' ), 'group topics disabled' );
		bp_update_option( 'bb-enable-group-activity-topics', 1 );

		self::set_current_user( $this->admin );
		$this->assertTrue( $m->bb_can_update_topics_order( $g, 'groups' ) );
		$this->assertTrue( $m->bb_can_update_topics_order( 0, 'activity' ) );
	}
}
