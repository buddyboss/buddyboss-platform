<?php
/**
 * Topic check before an activity post saves its topic (PROD-10670 S1b).
 *
 * @group activity
 * @group bb_activity_topics
 */
// Pro owns these helpers; the Platform suite runs without Pro, so mirror their bodies.
if ( ! function_exists( 'bb_is_enabled_group_activity_topics' ) ) {
	function bb_is_enabled_group_activity_topics( $enable_group_topics = false ) {
		return (bool) apply_filters( 'bb_is_enabled_group_activity_topics', bp_get_option( 'bb-enable-group-activity-topics', $enable_group_topics ) );
	}
}
if ( ! function_exists( 'bb_can_user_post_to_group_activity_topic' ) ) {
	function bb_can_user_post_to_group_activity_topic( $user_id, $group_id, $topic_id ) {
		if ( ! bp_is_active( 'groups' ) ) {
			return false;
		}
		$topic_permission = bb_topics_manager_instance()->bb_get_topic_permission_type(
			array(
				'topic_id'  => $topic_id,
				'item_id'   => $group_id,
				'item_type' => 'groups',
			)
		);
		if ( groups_is_user_admin( $user_id, $group_id ) ) {
			return true;
		}
		if ( groups_is_user_mod( $user_id, $group_id ) ) {
			return in_array( $topic_permission, array( 'mods', 'members' ), true );
		}
		if ( groups_is_user_member( $user_id, $group_id ) ) {
			return 'members' === $topic_permission;
		}

		return false;
	}
}

class BB_Tests_Activity_Topic_Save_Check extends BP_UnitTestCase {

	private $admin;
	private $member;
	private $saved_options = array();

	public function set_up() {
		parent::set_up();

		foreach ( array( 'bb_enable_activity_topics', 'bb_activity_topic_required', 'bb-enable-group-activity-topics' ) as $option ) {
			$this->saved_options[ $option ] = bp_get_option( $option, null );
		}
		bp_update_option( 'bb_enable_activity_topics', 1 );
		bp_update_option( 'bb_activity_topic_required', 0 );
		bp_update_option( 'bb-enable-group-activity-topics', 1 );

		if ( ! has_action( 'bp_activity_after_save', 'bb_activity_save_topic_data' ) ) {
			add_action( 'bp_activity_after_save', 'bb_activity_save_topic_data', 2, 1 );
		}

		$this->admin  = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$this->member = self::factory()->user->create();
	}

	public function tear_down() {
		unset( $_POST['topic_id'], $_POST['group_id'] );
		foreach ( $this->saved_options as $option => $value ) {
			if ( null === $value ) {
				bp_delete_option( $option );
			} else {
				bp_update_option( $option, $value );
			}
		}
		parent::tear_down();
	}

	private function add_topic( $name, $permission = 'anyone', $item_type = 'activity', $item_id = 0 ) {
		$current = get_current_user_id();
		self::set_current_user( $this->admin );
		$topic = bb_topics_manager_instance()->bb_add_topic(
			array(
				'name'            => $name,
				'permission_type' => $permission,
				'item_type'       => $item_type,
				'item_id'         => $item_id,
			)
		);
		self::set_current_user( $current );
		$this->assertNotEmpty( $topic->topic_id, "topic $name" );

		return (int) $topic->topic_id;
	}

	/**
	 * Post (or edit) an activity update the way the composer does: topic_id and group_id in $_POST.
	 */
	private function post( $user_id, $topic_id, $group_id = 0, $activity_id = 0 ) {
		self::set_current_user( $user_id );
		$_POST['topic_id'] = $topic_id;
		$_POST['group_id'] = $group_id;

		$args = array(
			'component' => $group_id ? 'groups' : 'activity',
			'type'      => 'activity_update',
			'user_id'   => $user_id,
			'item_id'   => $group_id,
			'content'   => 'S1b post',
		);
		if ( $activity_id ) {
			$args['id'] = $activity_id;
		}
		$id = bp_activity_add( $args );
		unset( $_POST['topic_id'], $_POST['group_id'] );
		$this->assertNotEmpty( $id );

		return (int) $id;
	}

	private function stored_topic( $activity_id ) {
		global $wpdb;
		$table = bb_topics_manager_instance()->activity_topic_rel_table;

		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT topic_id FROM `{$table}` WHERE activity_id = %d", $activity_id ) ); // phpcs:ignore
	}

	public function test_member_saves_a_site_topic_anyone_can_use() {
		$topic = $this->add_topic( 'S1b open' );
		$a     = $this->post( $this->member, $topic );

		$this->assertSame( $topic, $this->stored_topic( $a ) );
	}

	public function test_a_topic_id_that_does_not_exist_is_not_saved() {
		$a = $this->post( $this->member, 987654 );

		$this->assertSame( 0, $this->stored_topic( $a ), 'No relationship row points at a missing topic.' );
	}

	public function test_a_deleted_topic_is_not_saved() {
		$topic = $this->add_topic( 'S1b deleted' );
		self::set_current_user( $this->admin );
		bb_topics_manager_instance()->bb_delete_topic( array( 'topic_id' => $topic ) );

		$a = $this->post( $this->member, $topic );

		$this->assertSame( 0, $this->stored_topic( $a ) );
	}

	public function test_member_cannot_save_a_mods_admins_site_topic() {
		$topic = $this->add_topic( 'S1b mods only', 'mods_admins' );

		$this->assertSame( 0, $this->stored_topic( $this->post( $this->member, $topic ) ) );
		$this->assertSame( $topic, $this->stored_topic( $this->post( $this->admin, $topic ) ), 'An administrator may use it.' );
	}

	public function test_a_group_topic_is_not_saved_on_a_site_post() {
		$group = self::factory()->group->create( array( 'creator_id' => $this->admin ) );
		$topic = $this->add_topic( 'S1b group only', 'members', 'groups', $group );

		$this->assertSame( 0, $this->stored_topic( $this->post( $this->member, $topic ) ) );
	}

	public function test_edit_keeps_the_posts_own_topic_after_it_becomes_restricted() {
		$topic = $this->add_topic( 'S1b later restricted' );
		$a     = $this->post( $this->member, $topic );
		$this->assertSame( $topic, $this->stored_topic( $a ) );

		global $wpdb;
		$wpdb->update( bb_topics_manager_instance()->topic_rel_table, array( 'permission_type' => 'mods_admins' ), array( 'topic_id' => $topic, 'item_type' => 'activity', 'item_id' => 0 ) ); // phpcs:ignore

		$other = $this->add_topic( 'S1b other open' );
		$this->post( $this->member, $other, 0, $a );
		$this->assertSame( $other, $this->stored_topic( $a ), 'The member may move the post to an open topic.' );

		$this->post( $this->member, $topic, 0, $a );
		$this->assertSame( $other, $this->stored_topic( $a ), 'Moving to the restricted topic is refused.' );

		// A post that already carries the restricted topic may keep it on edit.
		bb_activity_topics_manager_instance()->bb_add_activity_topic_relationship(
			array(
				'topic_id'    => $topic,
				'activity_id' => $a,
				'component'   => 'activity',
				'item_id'     => 0,
			)
		);
		$this->post( $this->member, $topic, 0, $a );
		$this->assertSame( $topic, $this->stored_topic( $a ) );

		// The stored row is the same either way, so check the decision itself.
		self::set_current_user( $this->member );
		$this->assertTrue( bb_activity_user_can_save_topic( $topic, new BP_Activity_Activity( $a ) ), 'Own topic is allowed on edit.' );
		$this->assertFalse( bb_activity_user_can_save_topic( $topic, new BP_Activity_Activity( $this->post( $this->member, 0 ) ) ), 'Another post may not take it.' );
	}

	public function test_group_post_follows_the_group_topic_permission() {
		$group = self::factory()->group->create( array( 'creator_id' => $this->admin ) );
		self::add_user_to_group( $this->member, $group );
		$members_topic = $this->add_topic( 'S1b group members', 'members', 'groups', $group );
		$mods_topic    = $this->add_topic( 'S1b group mods', 'mods', 'groups', $group );

		$this->assertSame( $members_topic, $this->stored_topic( $this->post( $this->member, $members_topic, $group ) ) );
		$this->assertSame( 0, $this->stored_topic( $this->post( $this->member, $mods_topic, $group ) ), 'A member cannot use a moderators topic.' );
		$this->assertSame( $mods_topic, $this->stored_topic( $this->post( $this->admin, $mods_topic, $group ) ), 'The organizer can.' );
	}

	public function test_group_post_refuses_a_site_topic_or_another_groups_topic() {
		$group       = self::factory()->group->create( array( 'creator_id' => $this->admin ) );
		$other_group = self::factory()->group->create( array( 'creator_id' => $this->admin ) );
		self::add_user_to_group( $this->member, $group );
		$site_topic  = $this->add_topic( 'S1b site for group' );
		$other_topic = $this->add_topic( 'S1b other group', 'members', 'groups', $other_group );

		$this->assertSame( 0, $this->stored_topic( $this->post( $this->member, $site_topic, $group ) ) );
		$this->assertSame( 0, $this->stored_topic( $this->post( $this->member, $other_topic, $group ) ) );
	}

	public function test_group_post_saves_no_topic_while_group_topics_are_off() {
		$group = self::factory()->group->create( array( 'creator_id' => $this->admin ) );
		self::add_user_to_group( $this->member, $group );
		$topic = $this->add_topic( 'S1b group off', 'members', 'groups', $group );
		bp_update_option( 'bb-enable-group-activity-topics', 0 );

		$this->assertSame( 0, $this->stored_topic( $this->post( $this->member, $topic, $group ) ) );
	}

	public function test_required_mode_still_saves_an_allowed_topic() {
		bp_update_option( 'bb_activity_topic_required', 1 );
		$open       = $this->add_topic( 'S1b required open' );
		$restricted = $this->add_topic( 'S1b required restricted', 'mods_admins' );

		$this->assertSame( $open, $this->stored_topic( $this->post( $this->member, $open ) ) );
		$this->assertSame( 0, $this->stored_topic( $this->post( $this->member, $restricted ) ) );
	}

	public function test_helper_refuses_an_empty_topic() {
		$a = self::factory()->activity->create( array( 'type' => 'activity_update', 'user_id' => $this->member ) );
		$this->assertFalse( bb_activity_user_can_save_topic( 0, new BP_Activity_Activity( $a ) ) );
	}
}

/**
 * The website post_update AJAX handler refuses an optional topic that is deleted or not allowed (PROD-10670 S1b).
 *
 * @group activity
 * @group bb_activity_topics
 */
class BB_Tests_Activity_Topic_Post_Update_Ajax extends BP_UnitTestCase {

	private $member;

	private $last_response = '';

	private $error_level = 0;

	private $saved_options = array();

	public function set_up() {
		parent::set_up();

		if ( ! function_exists( 'bp_nouveau_ajax_post_update' ) ) {
			$this->markTestSkipped( 'bp-nouveau activity AJAX is not loaded.' );
		}

		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter( 'wp_die_ajax_handler', array( $this, 'get_die_handler' ), 1 );
		$this->error_level = error_reporting();
		error_reporting( $this->error_level & ~E_WARNING );

		foreach ( array( 'bb_enable_activity_topics', 'bb_activity_topic_required', 'bb-enable-group-activity-topics' ) as $option ) {
			$this->saved_options[ $option ] = bp_get_option( $option, null );
		}
		bp_update_option( 'bb_enable_activity_topics', 1 );
		bp_update_option( 'bb_activity_topic_required', 0 );
		bp_update_option( 'bb-enable-group-activity-topics', 1 );

		$this->member = self::factory()->user->create( array( 'role' => 'subscriber' ) );
	}

	public function tear_down() {
		$_POST    = array();
		$_REQUEST = array();
		remove_filter( 'wp_doing_ajax', '__return_true' );
		remove_filter( 'wp_die_ajax_handler', array( $this, 'get_die_handler' ), 1 );
		error_reporting( $this->error_level );
		foreach ( $this->saved_options as $option => $value ) {
			if ( null === $value ) {
				bp_delete_option( $option );
			} else {
				bp_update_option( $option, $value );
			}
		}

		parent::tear_down();
	}

	public function get_die_handler() {
		return array( $this, 'die_handler' );
	}

	public function die_handler( $message ) {
		$this->last_response .= ob_get_clean();
		throw new WPAjaxDieContinueException( is_scalar( $message ) ? (string) $message : '' );
	}

	private function add_site_topic( $name, $permission = 'anyone' ) {
		$topic = bb_topics_manager_instance()->bb_add_topic(
			array(
				'name'            => $name,
				'permission_type' => $permission,
				'item_type'       => 'activity',
				'item_id'         => 0,
			)
		);
		$this->assertNotEmpty( $topic->topic_id, "topic $name" );

		return (int) $topic->topic_id;
	}

	/**
	 * Post an update as the member and return the error message, or '' when no topic error was sent.
	 */
	private function post_update( $post ) {
		self::set_current_user( $this->member );

		$post['_wpnonce_post_update'] = wp_create_nonce( 'post_update' );
		$_POST                        = wp_slash( $post );
		$_REQUEST                     = $_POST;

		$this->last_response = '';
		$level               = ob_get_level();
		ob_start();
		try {
			bp_nouveau_ajax_post_update();
		} catch ( WPAjaxDieContinueException $e ) {
			// The handler ended through wp_send_json_*.
		} catch ( WPAjaxDieStopException $e ) {
			// Same, for the stop variant.
		}
		if ( ob_get_level() > $level ) {
			$this->last_response .= (string) ob_get_clean();
		}

		$json = json_decode( $this->last_response, true );

		return isset( $json['data']['message'] ) ? (string) $json['data']['message'] : '';
	}

	public function test_optional_deleted_topic_is_refused() {
		$message = $this->post_update(
			array(
				'content'  => 'PROD-10670 deleted topic',
				'object'   => 'user',
				'topic_id' => 987654,
			)
		);

		$this->assertSame( 'The topic does not exist.', $message );
	}

	public function test_optional_admin_only_topic_is_refused_for_a_member() {
		$admin_topic = $this->add_site_topic( 'Admins Only Topic', 'mods_admins' );

		$message = $this->post_update(
			array(
				'content'  => 'PROD-10670 admin topic',
				'object'   => 'user',
				'topic_id' => $admin_topic,
			)
		);

		$this->assertSame( 'You do not have permission to post in this topic.', $message );
	}

	public function test_optional_allowed_topic_passes_the_check() {
		$topic = $this->add_site_topic( 'Anyone Topic' );

		$message = $this->post_update(
			array(
				'content'  => 'PROD-10670 allowed topic',
				'object'   => 'user',
				'topic_id' => $topic,
			)
		);

		$this->assertNotSame( 'The topic does not exist.', $message );
		$this->assertNotSame( 'You do not have permission to post in this topic.', $message );
	}

	public function test_stale_topic_is_not_refused_while_topics_are_off() {
		bp_update_option( 'bb_enable_activity_topics', 0 );

		$message = $this->post_update(
			array(
				'content'  => 'PROD-10670 topics off',
				'object'   => 'user',
				'topic_id' => 987654,
			)
		);

		$this->assertNotSame( 'The topic does not exist.', $message );
	}
}
