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

/**
 * The reorder AJAX handler (wp_ajax_bb_update_topics_order) as the browser calls it.
 *
 * @group activity
 * @group bb_activity_topics
 */
class BB_Tests_Topics_Order_Ajax extends BP_UnitTestCase {

	private $admin, $organizer, $member, $own_group, $other_group, $a, $b;

	private $last_response = '';

	private $error_level = 0;

	public function set_up() {
		parent::set_up();

		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter( 'wp_die_ajax_handler', array( $this, 'get_die_handler' ), 1 );
		$this->error_level = error_reporting();
		error_reporting( $this->error_level & ~E_WARNING );

		bp_update_option( 'bb-enable-group-activity-topics', 1 );

		$this->admin       = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$this->organizer   = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$this->member      = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$this->own_group   = self::factory()->group->create( array( 'creator_id' => $this->organizer ) );
		$this->other_group = self::factory()->group->create( array( 'creator_id' => $this->admin ) );
		groups_join_group( $this->own_group, $this->member );

		// Alpha and Beta site-wide, and adopted by both groups, all in the order Alpha, Beta.
		self::set_current_user( $this->admin );
		$this->a = $this->add_topic( 'Order Alpha', 'activity', 0 );
		$this->b = $this->add_topic( 'Order Beta', 'activity', 0 );
		foreach ( array( $this->own_group, $this->other_group ) as $group_id ) {
			$this->add_topic( 'Order Alpha', 'groups', $group_id );
			$this->add_topic( 'Order Beta', 'groups', $group_id );
		}
	}

	public function tear_down() {
		$_POST    = array();
		$_REQUEST = array();
		remove_filter( 'wp_doing_ajax', '__return_true' );
		remove_filter( 'wp_die_ajax_handler', array( $this, 'get_die_handler' ), 1 );
		error_reporting( $this->error_level );
		bp_delete_option( 'bb-enable-group-activity-topics' );

		parent::tear_down();
	}

	public function get_die_handler() {
		return array( $this, 'die_handler' );
	}

	public function die_handler( $message ) {
		$this->last_response .= ob_get_clean();
		throw new WPAjaxDieContinueException( is_scalar( $message ) ? (string) $message : '' );
	}

	private function add_topic( $name, $item_type, $item_id ) {
		$topic = bb_topics_manager_instance()->bb_add_topic(
			array(
				'name'            => $name,
				'permission_type' => 'groups' === $item_type ? 'members' : 'anyone',
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

	/**
	 * Post a reorder of Beta above Alpha as $user_id and return the decoded JSON (null when the handler died without JSON).
	 */
	private function reorder( $user_id, $item = array(), $valid_nonce = true ) {
		self::set_current_user( $user_id );

		$post          = array_merge( array( 'topic_ids' => array( $this->b, $this->a ) ), $item );
		$post['nonce'] = $valid_nonce ? wp_create_nonce( 'bb_update_topics_order' ) : 'invalid-nonce';
		$_POST         = wp_slash( $post );
		$_REQUEST      = $_POST;

		$this->last_response = '';
		$level               = ob_get_level();
		ob_start();
		try {
			bb_topics_manager_instance()->bb_update_topics_order_ajax();
		} catch ( WPAjaxDieContinueException $e ) {
			// The handler ended through wp_send_json_* or check_ajax_referer().
		} catch ( WPAjaxDieStopException $e ) {
			// Same, for the stop variant.
		}
		if ( ob_get_level() > $level ) {
			$this->last_response .= (string) ob_get_clean();
		}

		return json_decode( $this->last_response, true );
	}

	private function assert_untouched( $message ) {
		$this->assertSame( array( $this->a, $this->b ), $this->order_of( 'groups', $this->own_group ), "$message: own group" );
		$this->assertSame( array( $this->a, $this->b ), $this->order_of( 'groups', $this->other_group ), "$message: other group" );
		$this->assertSame( array( $this->a, $this->b ), $this->order_of( 'activity', 0 ), "$message: site list" );
	}

	public function test_organizer_reorders_only_their_own_group() {
		$response = $this->reorder(
			$this->organizer,
			array(
				'item_type' => 'groups',
				'item_id'   => $this->own_group,
			)
		);

		$this->assertTrue( $response['success'], $this->last_response );
		$this->assertSame( array( $this->b, $this->a ), $this->order_of( 'groups', $this->own_group ), 'own group reordered' );
		$this->assertSame( array( $this->a, $this->b ), $this->order_of( 'groups', $this->other_group ), 'other group untouched' );
		$this->assertSame( array( $this->a, $this->b ), $this->order_of( 'activity', 0 ), 'site list untouched' );
	}

	public function test_organizer_cannot_reorder_another_group_or_the_site_list() {
		$response = $this->reorder(
			$this->organizer,
			array(
				'item_type' => 'groups',
				'item_id'   => $this->other_group,
			)
		);
		$this->assertFalse( $response['success'], 'other group' );

		$response = $this->reorder( $this->organizer, array( 'item_type' => 'activity' ) );
		$this->assertFalse( $response['success'], 'site list' );

		// No item at all is the site-wide list (older scripts).
		$response = $this->reorder( $this->organizer );
		$this->assertFalse( $response['success'], 'no item' );

		$this->assert_untouched( 'organizer refused' );
	}

	public function test_member_and_guest_cannot_reorder_a_group() {
		$response = $this->reorder(
			$this->member,
			array(
				'item_type' => 'groups',
				'item_id'   => $this->own_group,
			)
		);
		$this->assertFalse( $response['success'], 'plain member' );

		$response = $this->reorder(
			0,
			array(
				'item_type' => 'groups',
				'item_id'   => $this->own_group,
			)
		);
		$this->assertFalse( $response['success'], 'guest' );

		$this->assert_untouched( 'member/guest refused' );
	}

	public function test_organizer_needs_a_valid_nonce() {
		$this->reorder(
			$this->organizer,
			array(
				'item_type' => 'groups',
				'item_id'   => $this->own_group,
			),
			false
		);

		$this->assert_untouched( 'invalid nonce' );
	}

	public function test_admin_group_reorder_leaves_other_lists_alone() {
		$response = $this->reorder(
			$this->admin,
			array(
				'item_type' => 'groups',
				'item_id'   => $this->other_group,
			)
		);

		$this->assertTrue( $response['success'], $this->last_response );
		$this->assertSame( array( $this->b, $this->a ), $this->order_of( 'groups', $this->other_group ), 'that group reordered' );
		$this->assertSame( array( $this->a, $this->b ), $this->order_of( 'groups', $this->own_group ), 'own group untouched' );
		$this->assertSame( array( $this->a, $this->b ), $this->order_of( 'activity', 0 ), 'site list untouched' );

		$response = $this->reorder( $this->admin, array( 'item_type' => 'activity' ) );
		$this->assertTrue( $response['success'], $this->last_response );
		$this->assertSame( array( $this->b, $this->a ), $this->order_of( 'activity', 0 ), 'site list reordered' );
		$this->assertSame( array( $this->a, $this->b ), $this->order_of( 'groups', $this->own_group ), 'site reorder leaves the group' );
	}
}
