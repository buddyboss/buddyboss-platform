<?php
/**
 * Tests for activity and group topics (BB_Topics_Manager).
 *
 * BB_Tests_Topics_Manager_Ajax pins PROD-10664: group organizers manage the
 * topics of a group they organize through the topics AJAX handlers, and only
 * topics linked to that group. BB_Tests_Topics_Manager_Scope guards the
 * original Activity Topics feature (PROD-8774) against regressions: settings,
 * required topic, duplicates, limit, rename/delete/migrate, old-slug redirects,
 * feed filtering and posting permissions.
 *
 * The group topic options and permissions live in Platform Pro, which is not
 * loaded in this suite, so the Pro functions they use are mirrored below when missing.
 */

if ( ! function_exists( 'bb_is_enabled_group_activity_topics' ) ) {
	/**
	 * Mirror of the Platform Pro wrapper (includes/topics/bb-topics-functions.php).
	 *
	 * @param bool $enable_group_topics Default value.
	 *
	 * @return bool
	 */
	function bb_is_enabled_group_activity_topics( $enable_group_topics = false ) {
		return (bool) apply_filters( 'bb_is_enabled_group_activity_topics', bp_get_option( 'bb-enable-group-activity-topics', $enable_group_topics ) );
	}
}

if ( ! function_exists( 'bb_get_group_activity_topic_options' ) ) {
	/**
	 * Mirror of the Platform Pro wrapper (includes/topics/bb-topics-functions.php).
	 *
	 * @param string $group_topic_options Default value.
	 *
	 * @return string
	 */
	function bb_get_group_activity_topic_options( $group_topic_options = 'only_from_activity_topics' ) {
		return apply_filters( 'bb_get_group_activity_topic_options', bp_get_option( 'bb-group-activity-topics-options', $group_topic_options ) );
	}
}

if ( ! function_exists( 'bb_group_activity_topic_permission_type' ) ) {
	/**
	 * Mirror of the Platform Pro function (includes/topics/bb-topics-functions.php).
	 *
	 * @param string $existing_permission_type Permission type to return on its own.
	 *
	 * @return array
	 */
	function bb_group_activity_topic_permission_type( $existing_permission_type = '' ) {
		$permission_types = array(
			'members' => 'All group members',
			'mods'    => 'Organizers and Moderators Only',
			'admins'  => 'Organizers Only',
		);

		if ( ! empty( $existing_permission_type ) && isset( $permission_types[ $existing_permission_type ] ) ) {
			return array( $existing_permission_type => $permission_types[ $existing_permission_type ] );
		}

		return $permission_types;
	}
}

if ( ! function_exists( 'bb_can_user_post_to_group_activity_topic' ) ) {
	/**
	 * Mirror of the Platform Pro function (includes/topics/bb-topics-functions.php).
	 *
	 * @param int $user_id  User ID.
	 * @param int $group_id Group ID.
	 * @param int $topic_id Topic ID.
	 *
	 * @return bool
	 */
	function bb_can_user_post_to_group_activity_topic( $user_id, $group_id, $topic_id ) {
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

/**
 * Shared fixtures and AJAX harness for the topics tests.
 */
abstract class BB_Topics_Manager_TestCase extends BP_UnitTestCase {

	/**
	 * Captured JSON echoed by the handler before it called wp_die().
	 *
	 * @var string
	 */
	protected $last_response = '';

	/**
	 * Message passed to wp_die() by the last handler call ('-1' for a bad nonce).
	 *
	 * @var string
	 */
	protected $last_die_message = '';

	/**
	 * Saved error_reporting level, restored on tear down.
	 *
	 * @var int
	 */
	protected $error_level = 0;

	/**
	 * Fixture IDs.
	 *
	 * @var int
	 */
	protected $admin, $organizer, $other_organizer, $moderator, $member;
	protected $own_group, $other_group;
	protected $alpha, $beta, $gamma;

	/**
	 * @var BB_Topics_Manager
	 */
	protected $manager;

	public function set_up() {
		parent::set_up();

		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter( 'wp_die_ajax_handler', array( $this, 'get_die_handler' ), 1 );

		$this->error_level = error_reporting();
		error_reporting( $this->error_level & ~E_WARNING );

		bp_update_option( 'bb_enable_activity_topics', 1 );
		bp_update_option( 'bb-enable-group-activity-topics', 1 );
		bp_update_option( 'bb-group-activity-topics-options', 'only_from_activity_topics' );

		$this->manager = bb_topics_manager_instance();
		$this->manager->create_tables();

		$this->admin           = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$this->organizer       = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$this->other_organizer = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$this->moderator       = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$this->member          = self::factory()->user->create( array( 'role' => 'subscriber' ) );

		$this->own_group   = self::factory()->group->create( array( 'creator_id' => $this->organizer ) );
		$this->other_group = self::factory()->group->create( array( 'creator_id' => $this->other_organizer ) );

		$this->add_user_to_group( $this->moderator, $this->own_group );
		$mod = new BP_Groups_Member( $this->moderator, $this->own_group );
		$mod->promote( 'mod' );
		$this->add_user_to_group( $this->member, $this->own_group );
		$this->add_user_to_group( $this->organizer, $this->other_group );

		// Global topics: Alpha linked to the own group, Beta to the other group, Gamma to none.
		self::set_current_user( $this->admin );
		$this->alpha = $this->add_topic( 'Alpha Topic', 'activity', 0, 'anyone' );
		$this->beta  = $this->add_topic( 'Beta Topic', 'activity', 0, 'anyone' );
		$this->gamma = $this->add_topic( 'Gamma Topic', 'activity', 0, 'anyone' );
		$this->add_topic( 'Alpha Topic', 'groups', $this->own_group );
		$this->add_topic( 'Beta Topic', 'groups', $this->other_group );
	}

	public function tear_down() {
		$_POST    = array();
		$_GET     = array();
		$_REQUEST = array();
		remove_filter( 'wp_doing_ajax', '__return_true' );
		remove_filter( 'wp_die_ajax_handler', array( $this, 'get_die_handler' ), 1 );
		error_reporting( $this->error_level );

		parent::tear_down();
	}

	public function get_die_handler() {
		return array( $this, 'die_handler' );
	}

	/**
	 * Capture the echoed JSON and stop execution without exiting the process.
	 *
	 * @param string|WP_Error $message wp_die() message.
	 *
	 * @throws WPAjaxDieContinueException Always.
	 */
	public function die_handler( $message ) {
		$this->last_response   .= ob_get_clean();
		$this->last_die_message = is_scalar( $message ) ? (string) $message : '';
		throw new WPAjaxDieContinueException( $this->last_die_message );
	}

	/**
	 * Add a topic through the manager as the current user and return its topic ID.
	 *
	 * @param string $name            Topic name.
	 * @param string $item_type       activity|groups.
	 * @param int    $item_id         Group ID for groups.
	 * @param string $permission_type Permission type.
	 *
	 * @return int
	 */
	protected function add_topic( $name, $item_type, $item_id = 0, $permission_type = 'members' ) {
		$topic = $this->manager->bb_add_topic(
			array(
				'name'            => $name,
				'slug'            => sanitize_title( $name ),
				'permission_type' => $permission_type,
				'item_type'       => $item_type,
				'item_id'         => $item_id,
				'error_type'      => 'wp_error',
			)
		);

		$this->assertNotWPError( $topic, 'Fixture topic could not be created: ' . $name );

		return (int) $topic->topic_id;
	}

	/**
	 * Call one of the topics AJAX handlers as $user_id and decode the JSON it sent.
	 *
	 * @param string      $method       Handler method on BB_Topics_Manager.
	 * @param int         $user_id      Acting user.
	 * @param array       $post         Request body (without nonce).
	 * @param string      $nonce_action Nonce action, or '' to send an invalid nonce.
	 *
	 * @return array|null
	 */
	protected function ajax( $method, $user_id, $post, $nonce_action ) {
		self::set_current_user( $user_id );

		$post['nonce'] = $nonce_action ? wp_create_nonce( $nonce_action ) : 'invalid-nonce';
		$_POST         = wp_slash( $post );
		$_REQUEST      = $_POST;

		$this->last_response    = '';
		$this->last_die_message = '';

		$level = ob_get_level();
		ob_start();
		try {
			if ( method_exists( $this->manager, $method ) ) {
				$this->manager->$method();
			} else {
				call_user_func( $method );
			}
		} catch ( WPAjaxDieContinueException $e ) {
			// Expected: the handler ended via wp_send_json_* or check_ajax_referer().
		} catch ( WPAjaxDieStopException $e ) {
			$this->last_die_message = $e->getMessage();
		}
		// The die handler already closed our buffer; only close it here when it did not run.
		if ( ob_get_level() > $level ) {
			$this->last_response .= (string) ob_get_clean();
		}

		return json_decode( $this->last_response, true );
	}

	protected function add_ajax( $user_id, $post ) {
		return $this->ajax(
			'bb_add_topic_ajax',
			$user_id,
			array_merge(
				array(
					'permission_type' => 'members',
					'topic_id'        => '',
					'item_type'       => 'groups',
					'item_id'         => $this->own_group,
				),
				$post
			),
			'bb_add_topic'
		);
	}

	/**
	 * Relationship row for a topic on an item, or null.
	 */
	protected function relationship( $topic_id, $item_type, $item_id ) {
		global $wpdb;
		$table = bp_core_get_table_prefix() . 'bb_topic_relationships';

		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE topic_id = %d AND item_type = %s AND item_id = %d", $topic_id, $item_type, $item_id ) ); // phpcs:ignore
	}

	protected function assert_denied( $response, $message = '' ) {
		$this->assertIsArray( $response, $message );
		$this->assertFalse( $response['success'], $message );
		$this->assertSame( 'You do not have permission to perform this action.', $response['data']['error'], $message );
	}

	protected function assert_success( $response, $message = '' ) {
		$this->assertIsArray( $response, $message . ' (no JSON response)' );
		$this->assertTrue( $response['success'], $message . ' ' . wp_json_encode( $response ) );
	}
}

/**
 * PROD-10664: group organizers manage their own group's topics.
 *
 * @group core
 * @group topics
 * @group PROD-10664
 */
class BB_Tests_Topics_Manager_Ajax extends BB_Topics_Manager_TestCase {

	/*
	 * Add.
	 */

	public function test_organizer_can_add_activity_topic_to_own_group_in_only_activity_mode() {
		$response = $this->add_ajax( $this->organizer, array( 'name' => 'Gamma Topic' ) );

		$this->assert_success( $response, 'Organizer must be able to add a global topic to their own group (ticket repro).' );
		$this->assertNotNull( $this->relationship( $this->gamma, 'groups', $this->own_group ) );
	}

	public function test_organizer_cannot_add_custom_topic_in_only_activity_mode() {
		$response = $this->add_ajax( $this->organizer, array( 'name' => 'Brand New Custom' ) );

		$this->assertFalse( $response['success'] );
		$this->assertSame( 'Topic name not found in activity topics.', $response['data']['error'] );
		$this->assertEmpty( $this->manager->bb_get_topic_by( 'slug', 'brand-new-custom' ) );
	}

	public function test_organizer_can_add_custom_topic_in_allow_both_mode() {
		bp_update_option( 'bb-group-activity-topics-options', 'allow_both' );

		$response = $this->add_ajax( $this->organizer, array( 'name' => 'Organizer Custom' ) );

		$this->assert_success( $response );
		$topic = $this->manager->bb_get_topic_by( 'slug', 'organizer-custom' );
		$this->assertNotEmpty( $topic );
		$this->assertNotNull( $this->relationship( $topic->id, 'groups', $this->own_group ) );
		$this->assertNull( $this->relationship( $topic->id, 'activity', 0 ), 'A group custom topic must not become a global topic.' );
	}

	public function test_organizer_can_add_custom_topic_in_create_own_mode() {
		bp_update_option( 'bb-group-activity-topics-options', 'create_own_topics' );

		$this->assert_success( $this->add_ajax( $this->organizer, array( 'name' => 'Own Only Topic' ) ) );
	}

	public function test_organizer_cannot_add_topic_to_group_they_do_not_organize() {
		$response = $this->add_ajax( $this->organizer, array( 'name' => 'Gamma Topic', 'item_id' => $this->other_group ) );

		$this->assert_denied( $response );
		$this->assertNull( $this->relationship( $this->gamma, 'groups', $this->other_group ) );
	}

	public function test_organizer_cannot_add_site_wide_activity_topic() {
		$this->assert_denied( $this->add_ajax( $this->organizer, array( 'name' => 'Org Activity', 'item_type' => 'activity', 'item_id' => 0 ) ) );

		// item_type omitted defaults to activity.
		$this->assert_denied( $this->ajax( 'bb_add_topic_ajax', $this->organizer, array( 'name' => 'Org Activity 2' ), 'bb_add_topic' ) );
		$this->assertEmpty( $this->manager->bb_get_topic_by( 'slug', 'org-activity' ) );
	}

	public function test_group_moderator_and_member_cannot_add_topic() {
		$this->assert_denied( $this->add_ajax( $this->moderator, array( 'name' => 'Gamma Topic' ) ), 'Group moderator is not an organizer.' );
		$this->assert_denied( $this->add_ajax( $this->member, array( 'name' => 'Gamma Topic' ) ), 'Plain member.' );
	}

	public function test_organizer_denied_when_group_topics_disabled() {
		bp_update_option( 'bb-enable-group-activity-topics', 0 );

		$this->assert_denied( $this->add_ajax( $this->organizer, array( 'name' => 'Gamma Topic' ) ) );
	}

	public function test_invalid_nonce_is_still_rejected_for_organizer() {
		$response = $this->ajax(
			'bb_add_topic_ajax',
			$this->organizer,
			array(
				'name'      => 'Gamma Topic',
				'item_type' => 'groups',
				'item_id'   => $this->own_group,
			),
			''
		);

		$this->assertNull( $response );
		$this->assertSame( '-1', $this->last_die_message );
		$this->assertNull( $this->relationship( $this->gamma, 'groups', $this->own_group ) );
	}

	/*
	 * Slug (S-2).
	 */

	public function test_posted_slug_is_ignored_for_organizer() {
		bp_update_option( 'bb-group-activity-topics-options', 'allow_both' );

		$response = $this->add_ajax(
			$this->organizer,
			array(
				'name' => 'Visit scam.example',
				'slug' => 'announcements',
			)
		);

		$this->assert_success( $response );
		$this->assertSame( 'visit-scam-example', $response['data']['content']['topic']->slug ?? $response['data']['content']['topic']['slug'] );
		$this->assertEmpty( $this->manager->bb_get_topic_by( 'slug', 'announcements' ), 'An organizer must not be able to claim a slug that differs from the name.' );
	}

	public function test_posted_slug_is_kept_for_admin() {
		$response = $this->ajax(
			'bb_add_topic_ajax',
			$this->admin,
			array(
				'name'            => 'Admin Named',
				'slug'            => 'admin-custom-slug',
				'permission_type' => 'anyone',
				'item_type'       => 'activity',
				'item_id'         => 0,
			),
			'bb_add_topic'
		);

		$this->assert_success( $response );
		$this->assertNotEmpty( $this->manager->bb_get_topic_by( 'slug', 'admin-custom-slug' ) );
	}

	/*
	 * Edit.
	 */

	public function test_organizer_can_change_permission_of_own_global_topic() {
		$response = $this->add_ajax(
			$this->organizer,
			array(
				'name'            => 'alpha-topic',
				'topic_id'        => $this->alpha,
				'permission_type' => 'admins',
			)
		);

		$this->assert_success( $response );
		$this->assertSame( 'admins', $this->relationship( $this->alpha, 'groups', $this->own_group )->permission_type );
		$this->assertSame( 'anyone', $this->relationship( $this->alpha, 'activity', 0 )->permission_type, 'The global relationship must not change.' );
	}

	public function test_organizer_permission_only_edit_of_custom_topic_in_only_activity_mode() {
		$custom = $this->add_topic( 'Legacy Custom', 'groups', $this->own_group );

		$response = $this->add_ajax(
			$this->organizer,
			array(
				'name'            => 'legacy-custom',
				'topic_id'        => $custom,
				'permission_type' => 'admins',
			)
		);

		$this->assert_success( $response, 'S-1: a permission-only edit keeps the same topic, so the only-activity rule must not block it.' );
		$this->assertSame( 'admins', $this->relationship( $custom, 'groups', $this->own_group )->permission_type );
	}

	public function test_organizer_cannot_rename_to_custom_topic_in_only_activity_mode() {
		$custom = $this->add_topic( 'Legacy Custom', 'groups', $this->own_group );

		$response = $this->add_ajax( $this->organizer, array( 'name' => 'Renamed Custom', 'topic_id' => $custom ) );

		$this->assertFalse( $response['success'] );
		$this->assertSame( 'Topic name not found in activity topics.', $response['data']['error'] );
		$this->assertNotNull( $this->relationship( $custom, 'groups', $this->own_group ) );
	}

	public function test_organizer_cannot_save_edit_of_topic_not_linked_to_their_group() {
		// Beta is linked to the other group only.
		$response = $this->add_ajax(
			$this->organizer,
			array(
				'name'            => 'beta-topic',
				'topic_id'        => $this->beta,
				'permission_type' => 'admins',
			)
		);

		$this->assert_denied( $response );
		$this->assertSame( 'members', $this->relationship( $this->beta, 'groups', $this->other_group )->permission_type );
	}

	public function test_edit_and_delete_fetch_are_limited_to_own_group() {
		$own   = array( 'topic_id' => $this->alpha, 'item_type' => 'groups', 'item_id' => $this->own_group );
		$other = array( 'topic_id' => $this->beta, 'item_type' => 'groups', 'item_id' => $this->other_group );
		$cross = array( 'topic_id' => $this->beta, 'item_type' => 'groups', 'item_id' => $this->own_group );

		$this->assert_success( $this->ajax( 'bb_edit_topic_ajax', $this->organizer, $own, 'bb_edit_topic' ) );
		$this->assert_success( $this->ajax( 'bb_delete_topic_ajax', $this->organizer, $own, 'bb_delete_topic' ) );

		$this->assert_denied( $this->ajax( 'bb_edit_topic_ajax', $this->organizer, $other, 'bb_edit_topic' ) );
		$this->assert_denied( $this->ajax( 'bb_delete_topic_ajax', $this->organizer, $other, 'bb_delete_topic' ) );
		$this->assert_denied( $this->ajax( 'bb_edit_topic_ajax', $this->organizer, $cross, 'bb_edit_topic' ) );
	}

	/*
	 * Delete / migrate.
	 */

	public function test_organizer_can_migrate_own_topic_and_posts_move() {
		$this->add_topic( 'Gamma Topic', 'groups', $this->own_group );
		$activity = self::factory()->activity->create(
			array(
				'component' => 'groups',
				'type'      => 'activity_update',
				'item_id'   => $this->own_group,
				'user_id'   => $this->organizer,
			)
		);
		bb_activity_topics_manager_instance()->bb_add_activity_topic_relationship(
			array(
				'topic_id'    => $this->alpha,
				'activity_id' => $activity,
				'component'   => 'groups',
				'item_id'     => $this->own_group,
			)
		);

		$response = $this->ajax(
			'bb_migrate_topic_ajax',
			$this->organizer,
			array(
				'old_topic_id' => $this->alpha,
				'new_topic_id' => $this->gamma,
				'item_type'    => 'groups',
				'item_id'      => $this->own_group,
				'migrate_type' => 'migrate',
			),
			'bb_migrate_topic'
		);

		$this->assert_success( $response );
		$this->assertNull( $this->relationship( $this->alpha, 'groups', $this->own_group ) );
		$this->assertNotNull( $this->relationship( $this->alpha, 'activity', 0 ), 'The global topic stays.' );
		$this->assertSame( $this->gamma, (int) bb_activity_topics_manager_instance()->bb_get_activity_topic( $activity ) );
		$this->assertSame(
			'gamma-topic',
			$this->manager->bb_get_redirected_topic_slug( array( 'old_slug' => 'alpha-topic', 'item_type' => 'groups', 'item_id' => $this->own_group ) ),
			'The old group filter URL must redirect to the new topic.'
		);
	}

	public function test_organizer_can_delete_own_topic_without_migrating() {
		$response = $this->ajax(
			'bb_migrate_topic_ajax',
			$this->organizer,
			array(
				'old_topic_id' => $this->alpha,
				'item_type'    => 'groups',
				'item_id'      => $this->own_group,
				'migrate_type' => 'delete',
			),
			'bb_migrate_topic'
		);

		$this->assert_success( $response );
		$this->assertNull( $this->relationship( $this->alpha, 'groups', $this->own_group ) );
		$this->assertNotNull( $this->relationship( $this->alpha, 'activity', 0 ) );
	}

	public function test_organizer_cannot_migrate_topics_outside_their_group() {
		// Into a topic not linked to the group.
		$into_foreign = $this->ajax(
			'bb_migrate_topic_ajax',
			$this->organizer,
			array(
				'old_topic_id' => $this->alpha,
				'new_topic_id' => $this->beta,
				'item_type'    => 'groups',
				'item_id'      => $this->own_group,
				'migrate_type' => 'migrate',
			),
			'bb_migrate_topic'
		);
		$this->assert_denied( $into_foreign );

		// Delete in another group.
		$other_group = $this->ajax(
			'bb_migrate_topic_ajax',
			$this->organizer,
			array(
				'old_topic_id' => $this->beta,
				'item_type'    => 'groups',
				'item_id'      => $this->other_group,
				'migrate_type' => 'delete',
			),
			'bb_migrate_topic'
		);
		$this->assert_denied( $other_group );
		$this->assertNotNull( $this->relationship( $this->beta, 'groups', $this->other_group ) );

		// Activity topic.
		$this->assert_denied(
			$this->ajax(
				'bb_migrate_topic_ajax',
				$this->organizer,
				array(
					'old_topic_id' => $this->gamma,
					'item_type'    => 'activity',
					'item_id'      => 0,
					'migrate_type' => 'delete',
				),
				'bb_migrate_topic'
			)
		);
		$this->assertNotNull( $this->relationship( $this->gamma, 'activity', 0 ) );
	}

	/**
	 * C-1: the check and the handler must parse item_id the same way. A value
	 * such as "<own>%000" used to pass the check as the own group and then act on
	 * group "<own>0".
	 */
	public function test_crafted_item_id_cannot_reach_another_group() {
		global $wpdb;

		$target_id = (int) ( $this->own_group . '0' );
		$wpdb->insert(
			buddypress()->groups->table_name,
			array(
				'id'           => $target_id,
				'creator_id'   => $this->other_organizer,
				'name'         => 'Target',
				'slug'         => 'target-' . $target_id,
				'description'  => '',
				'status'       => 'public',
				'date_created' => bp_core_current_time(),
			)
		);
		self::set_current_user( $this->admin );
		$this->add_topic( 'Alpha Topic', 'groups', $target_id );

		foreach ( array( $this->own_group . '%000', $this->own_group . '<i>0' ) as $crafted ) {
			$response = $this->ajax(
				'bb_migrate_topic_ajax',
				$this->organizer,
				array(
					'old_topic_id' => $this->alpha,
					'item_type'    => 'groups',
					'item_id'      => $crafted,
					'migrate_type' => 'delete',
				),
				'bb_migrate_topic'
			);
			$this->assert_denied( $response, 'Crafted item_id ' . $crafted );
			$this->assertNotNull( $this->relationship( $this->alpha, 'groups', $target_id ), 'Target group topic must be intact after ' . $crafted );
		}

		// Array value.
		$response = $this->ajax(
			'bb_migrate_topic_ajax',
			$this->organizer,
			array(
				'old_topic_id' => $this->alpha,
				'item_type'    => 'groups',
				'item_id'      => array( $this->own_group ),
				'migrate_type' => 'delete',
			),
			'bb_migrate_topic'
		);
		$this->assert_denied( $response );
		$this->assertNotNull( $this->relationship( $this->alpha, 'groups', $this->own_group ) );
	}

	public function test_organizer_cannot_delete_another_groups_topic_through_own_group() {
		// Beta is linked to the other group only; the request names the own group.
		$cross = array( 'topic_id' => $this->beta, 'item_type' => 'groups', 'item_id' => $this->own_group );

		$this->assert_denied( $this->ajax( 'bb_delete_topic_ajax', $this->organizer, $cross, 'bb_delete_topic' ) );
		$this->assertNotNull( $this->relationship( $this->beta, 'groups', $this->other_group ) );
	}

	public function test_organizer_cannot_migrate_from_a_topic_outside_their_group() {
		// The new topic is in the own group, the old one is not.
		$response = $this->ajax(
			'bb_migrate_topic_ajax',
			$this->organizer,
			array(
				'old_topic_id' => $this->beta,
				'new_topic_id' => $this->alpha,
				'item_type'    => 'groups',
				'item_id'      => $this->own_group,
				'migrate_type' => 'migrate',
			),
			'bb_migrate_topic'
		);

		$this->assert_denied( $response );
		$this->assertNotNull( $this->relationship( $this->beta, 'groups', $this->other_group ) );
	}

	public function test_organizer_group_id_does_not_grant_other_item_types() {
		$response = $this->add_ajax(
			$this->organizer,
			array(
				'name'            => 'Sneaky Activity',
				'item_type'       => 'activity',
				'item_id'         => $this->own_group,
				'permission_type' => 'anyone',
			)
		);

		$this->assert_denied( $response );
		$this->assertFalse( (bool) $this->manager->bb_get_topic_by( 'slug', 'sneaky-activity' ) );
	}

	public function test_only_activity_mode_ignores_other_groups_custom_topics() {
		self::set_current_user( $this->admin );
		$this->add_topic( 'Other Custom', 'groups', $this->other_group );

		$response = $this->add_ajax( $this->organizer, array( 'name' => 'Other Custom' ) );

		$this->assertFalse( $response['success'] );
		$this->assertSame( 'Topic name not found in activity topics.', $response['data']['error'] );
	}

	/*
	 * Global topics linked to a group.
	 */

	public function test_global_topic_cannot_be_renamed_or_swapped_inside_a_group() {
		bp_update_option( 'bb-group-activity-topics-options', 'allow_both' );

		foreach ( array( 'Renamed X', 'Gamma Topic' ) as $name ) {
			$response = $this->add_ajax( $this->organizer, array( 'name' => $name, 'topic_id' => $this->alpha ) );

			$this->assertFalse( $response['success'], $name );
			$this->assertSame( 'You cannot assign or update a global topic under a group.', $response['data']['error'], $name );
		}

		$this->assertNotNull( $this->relationship( $this->alpha, 'groups', $this->own_group ) );
		$this->assertNull( $this->relationship( $this->gamma, 'groups', $this->own_group ) );
		$this->assertFalse( (bool) $this->manager->bb_get_topic_by( 'slug', 'renamed-x' ) );
	}

	public function test_custom_group_topic_can_still_be_renamed() {
		bp_update_option( 'bb-group-activity-topics-options', 'allow_both' );
		$custom = $this->add_topic( 'Old Custom', 'groups', $this->own_group );

		$response = $this->add_ajax( $this->organizer, array( 'name' => 'New Custom', 'topic_id' => $custom ) );

		$this->assert_success( $response );
		$this->assertNull( $this->relationship( $custom, 'groups', $this->own_group ) );
		$this->assertSame( 'New Custom', $response['data']['content']['topic']['name'] );
	}

	/*
	 * Shared topic rows.
	 */

	public function test_activity_topic_name_wins_over_earlier_group_topic_name() {
		bp_update_option( 'bb-group-activity-topics-options', 'allow_both' );
		$this->assert_success( $this->add_ajax( $this->organizer, array( 'name' => 'ANNOUNCEMENTS!!!' ) ) );
		$this->assertSame( 'ANNOUNCEMENTS!!!', $this->manager->bb_get_topic_by( 'slug', 'announcements' )->name );

		$response = $this->add_ajax( $this->admin, array( 'name' => 'Announcements', 'item_type' => 'activity', 'item_id' => 0, 'permission_type' => 'anyone' ) );

		$this->assert_success( $response );
		$this->assertSame( 'Announcements', $this->manager->bb_get_topic_by( 'slug', 'announcements' )->name );
		$this->assertSame( 'Announcements', $response['data']['content']['topic']['name'] );
	}

	public function test_admin_can_rename_activity_topic_to_same_slug() {
		$response = $this->add_ajax(
			$this->admin,
			array(
				'name'            => 'GAMMA topic',
				'topic_id'        => $this->gamma,
				'item_type'       => 'activity',
				'item_id'         => 0,
				'permission_type' => 'anyone',
			)
		);

		$this->assert_success( $response );
		$this->assertSame( 'GAMMA topic', $this->manager->bb_get_topic_by( 'id', $this->gamma )->name );
	}

	public function test_group_rename_does_not_change_shared_topic_name() {
		bp_update_option( 'bb-group-activity-topics-options', 'allow_both' );
		self::set_current_user( $this->admin );
		$custom = $this->add_topic( 'Shared Name', 'groups', $this->other_group );
		$this->add_topic( 'Shared Name', 'groups', $this->own_group );

		$response = $this->add_ajax( $this->organizer, array( 'name' => 'SHARED name', 'topic_id' => $custom, 'permission_type' => 'admins' ) );

		$this->assert_success( $response );
		$this->assertSame( 'Shared Name', $this->manager->bb_get_topic_by( 'id', $custom )->name );
	}

	/*
	 * Reorder and admin.
	 */

	public function test_reorder_site_list_requires_moderator() {
		$this->assert_denied( $this->ajax( 'bb_update_topics_order_ajax', $this->organizer, array( 'topic_ids' => array( $this->alpha ) ), 'bb_update_topics_order' ) );
		$this->assert_success( $this->ajax( 'bb_update_topics_order_ajax', $this->admin, array( 'topic_ids' => array( $this->gamma, $this->alpha ) ), 'bb_update_topics_order' ) );
	}

	public function test_organizer_reorders_only_own_group_list() {
		$first  = $this->add_topic( 'Own First', 'groups', $this->own_group );
		$second = $this->add_topic( 'Own Second', 'groups', $this->own_group );

		$this->assert_success( $this->ajax( 'bb_update_topics_order_ajax', $this->organizer, array( 'topic_ids' => array( $second, $first ), 'item_type' => 'groups', 'item_id' => $this->own_group ), 'bb_update_topics_order' ) );
		$this->assert_denied( $this->ajax( 'bb_update_topics_order_ajax', $this->organizer, array( 'topic_ids' => array( $this->alpha ), 'item_type' => 'groups', 'item_id' => $this->other_group ), 'bb_update_topics_order' ) );
	}

	public function test_admin_keeps_full_access() {
		// Group the admin is not a member of.
		$this->assert_success( $this->add_ajax( $this->admin, array( 'name' => 'Gamma Topic', 'item_id' => $this->other_group ) ) );
		$this->assertNotNull( $this->relationship( $this->gamma, 'groups', $this->other_group ) );

		// Site-wide activity topic.
		$this->assert_success( $this->add_ajax( $this->admin, array( 'name' => 'Admin Activity', 'item_type' => 'activity', 'item_id' => 0, 'permission_type' => 'anyone' ) ) );

		// Custom group topic in only-activity mode (the option restricts organizers only).
		$this->assert_success( $this->add_ajax( $this->admin, array( 'name' => 'Admin Custom', 'item_id' => $this->other_group ) ) );

		$this->assert_success( $this->ajax( 'bb_edit_topic_ajax', $this->admin, array( 'topic_id' => $this->beta, 'item_type' => 'groups', 'item_id' => $this->other_group ), 'bb_edit_topic' ) );
	}
	public function test_group_add_never_renames_a_shared_activity_topic() {
		$response = $this->add_ajax( $this->organizer, array( 'name' => 'GAMMA topic' ) );

		$this->assert_success( $response );
		$this->assertNotNull( $this->relationship( $this->gamma, 'groups', $this->own_group ) );
		$this->assertSame( 'Gamma Topic', $this->manager->bb_get_topic_by( 'id', $this->gamma )->name );
	}

	public function test_activity_edit_with_permission_label_is_refused() {
		$response = $this->add_ajax(
			$this->admin,
			array(
				'name'            => 'Gamma Topic',
				'topic_id'        => $this->gamma,
				'item_type'       => 'activity',
				'item_id'         => 0,
				'permission_type' => 'Anyone',
			)
		);

		$this->assertFalse( $response['success'] );
		$this->assertSame( 'Please choose who can post in this topic.', $response['data']['error'] );
		$this->assertSame( 'anyone', $this->relationship( $this->gamma, 'activity', 0 )->permission_type );
	}
}

/**
 * PROD-8774 regression guard: the original Activity Topics feature.
 *
 * @group core
 * @group topics
 * @group PROD-8774
 */
class BB_Tests_Topics_Manager_Scope extends BB_Topics_Manager_TestCase {

	/**
	 * Create an activity tagged with a topic.
	 *
	 * @param int    $topic_id  Topic ID.
	 * @param string $component activity|groups.
	 * @param int    $item_id   Group ID for groups.
	 *
	 * @return int Activity ID.
	 */
	protected function tagged_activity( $topic_id, $component = 'activity', $item_id = 0 ) {
		$activity = self::factory()->activity->create(
			array(
				'component' => $component,
				'type'      => 'activity_update',
				'item_id'   => $item_id,
				'user_id'   => $this->admin,
			)
		);
		bb_activity_topics_manager_instance()->bb_add_activity_topic_relationship(
			array(
				'topic_id'    => $topic_id,
				'activity_id' => $activity,
				'component'   => $component,
				'item_id'     => $item_id,
			)
		);

		return $activity;
	}

	protected function activity_topic( $activity_id ) {
		return (int) bb_activity_topics_manager_instance()->bb_get_activity_topic( $activity_id );
	}

	/*
	 * Settings.
	 */

	public function test_enable_topics_setting() {
		bp_update_option( 'bb_enable_activity_topics', 0 );
		$this->assertFalse( bb_is_enabled_activity_topics() );

		bp_update_option( 'bb_enable_activity_topics', 1 );
		$this->assertTrue( bb_is_enabled_activity_topics() );
	}

	public function test_topic_required_setting() {
		bp_update_option( 'bb_activity_topic_required', 0 );
		$this->assertFalse( bb_is_activity_topic_required() );

		bp_update_option( 'bb_activity_topic_required', 1 );
		$this->assertTrue( bb_is_activity_topic_required() );
	}

	public function test_required_topic_refuses_relationship_without_topic() {
		bp_update_option( 'bb_activity_topic_required', 1 );
		$activity = self::factory()->activity->create( array( 'user_id' => $this->admin ) );

		$result = bb_activity_topics_manager_instance()->bb_add_activity_topic_relationship(
			array(
				'topic_id'    => 0,
				'activity_id' => $activity,
				'component'   => 'activity',
				'item_id'     => 0,
			)
		);

		$this->assertWPError( $result );
	}

	/*
	 * Global topic management.
	 */

	public function test_duplicate_topic_name_is_rejected() {
		self::set_current_user( $this->admin );
		$duplicate = $this->manager->bb_add_topic(
			array(
				'name'       => 'Alpha Topic',
				'item_type'  => 'activity',
				'item_id'    => 0,
				'error_type' => 'wp_error',
			)
		);

		$this->assertWPError( $duplicate );
		$this->assertSame( 'bb_topic_duplicate_slug', $duplicate->get_error_code() );
	}

	public function test_same_global_topic_can_be_added_to_several_groups() {
		self::set_current_user( $this->admin );
		$this->add_topic( 'Alpha Topic', 'groups', $this->other_group );

		$this->assertNotNull( $this->relationship( $this->alpha, 'groups', $this->own_group ) );
		$this->assertNotNull( $this->relationship( $this->alpha, 'groups', $this->other_group ) );
	}

	public function test_global_topic_limit_is_twenty() {
		self::set_current_user( $this->admin );
		for ( $i = 4; $i <= 20; $i++ ) {
			$this->add_topic( 'Limit Topic ' . $i, 'activity', 0, 'anyone' );
		}

		$over = $this->manager->bb_add_topic(
			array(
				'name'       => 'Limit Topic 21',
				'item_type'  => 'activity',
				'item_id'    => 0,
				'error_type' => 'wp_error',
			)
		);

		$this->assertWPError( $over, 'The 21st global topic must be refused.' );
	}

	public function test_admin_rename_moves_global_posts_and_redirects_old_slug() {
		$feed_post  = $this->tagged_activity( $this->alpha );
		$group_post = $this->tagged_activity( $this->alpha, 'groups', $this->own_group );

		$response = $this->ajax(
			'bb_add_topic_ajax',
			$this->admin,
			array(
				'name'            => 'Alpha Renamed',
				'topic_id'        => $this->alpha,
				'permission_type' => 'anyone',
				'item_type'       => 'activity',
				'item_id'         => 0,
			),
			'bb_add_topic'
		);
		$this->assert_success( $response );

		$renamed = $this->manager->bb_get_topic_by( 'slug', 'alpha-renamed' );
		$this->assertNotEmpty( $renamed );
		$this->assertNotNull( $this->relationship( $renamed->id, 'activity', 0 ) );
		$this->assertNull( $this->relationship( $this->alpha, 'activity', 0 ) );

		$this->assertSame( (int) $renamed->id, $this->activity_topic( $feed_post ), 'Feed posts follow the rename.' );
		$this->assertSame( $this->alpha, $this->activity_topic( $group_post ), 'A global rename must not change group posts.' );
		$this->assertNotNull( $this->relationship( $this->alpha, 'groups', $this->own_group ), 'A global rename must not change group topics.' );

		$this->assertSame( 'alpha-renamed', $this->manager->bb_get_redirected_topic_slug( array( 'old_slug' => 'alpha-topic' ) ) );
	}

	public function test_renaming_twice_redirects_hop_by_hop() {
		foreach ( array( array( $this->alpha, 'Second Name' ), array( 0, 'Third Name' ) ) as $step ) {
			$topic_id = $step[0] ? $step[0] : (int) $this->manager->bb_get_topic_by( 'slug', 'second-name' )->id;
			$this->assert_success(
				$this->ajax(
					'bb_add_topic_ajax',
					$this->admin,
					array(
						'name'            => $step[1],
						'topic_id'        => $topic_id,
						'permission_type' => 'anyone',
						'item_type'       => 'activity',
						'item_id'         => 0,
					),
					'bb_add_topic'
				)
			);
		}

		// Each redirect lands on the next slug, which redirects again.
		$this->assertSame( 'second-name', $this->manager->bb_get_redirected_topic_slug( array( 'old_slug' => 'alpha-topic' ) ) );
		$this->assertSame( 'third-name', $this->manager->bb_get_redirected_topic_slug( array( 'old_slug' => 'second-name' ) ) );
	}

	public function test_permission_only_edit_keeps_slug_and_adds_no_history() {
		$this->assert_success(
			$this->ajax(
				'bb_add_topic_ajax',
				$this->admin,
				array(
					'name'            => 'Alpha Topic',
					'topic_id'        => $this->alpha,
					'permission_type' => 'mods_admins',
					'item_type'       => 'activity',
					'item_id'         => 0,
				),
				'bb_add_topic'
			)
		);

		$this->assertSame( 'mods_admins', $this->relationship( $this->alpha, 'activity', 0 )->permission_type );
		$this->assertSame( 'alpha-topic', $this->manager->bb_get_redirected_topic_slug( array( 'old_slug' => 'alpha-topic' ) ), 'URL stays the same when only the permission changes.' );
	}

	public function test_admin_delete_with_migration_moves_posts_and_redirects() {
		$feed_post = $this->tagged_activity( $this->alpha );

		$this->assert_success(
			$this->ajax(
				'bb_migrate_topic_ajax',
				$this->admin,
				array(
					'old_topic_id' => $this->alpha,
					'new_topic_id' => $this->gamma,
					'item_type'    => 'activity',
					'item_id'      => 0,
					'migrate_type' => 'migrate',
				),
				'bb_migrate_topic'
			)
		);

		$this->assertNull( $this->relationship( $this->alpha, 'activity', 0 ) );
		$this->assertSame( $this->gamma, $this->activity_topic( $feed_post ) );
		$this->assertSame( 'gamma-topic', $this->manager->bb_get_redirected_topic_slug( array( 'old_slug' => 'alpha-topic' ) ) );
	}

	public function test_admin_hard_delete_leaves_posts_uncategorized() {
		$feed_post = $this->tagged_activity( $this->alpha );

		$this->assert_success(
			$this->ajax(
				'bb_migrate_topic_ajax',
				$this->admin,
				array(
					'old_topic_id' => $this->alpha,
					'item_type'    => 'activity',
					'item_id'      => 0,
					'migrate_type' => 'delete',
				),
				'bb_migrate_topic'
			)
		);

		$this->assertNull( $this->relationship( $this->alpha, 'activity', 0 ) );
		$this->assertSame( 0, $this->activity_topic( $feed_post ), 'Posts become uncategorized.' );
		$this->assertNotNull( $this->relationship( $this->alpha, 'groups', $this->own_group ), 'Deleting the global topic does not remove it from groups.' );
	}

	/*
	 * Group topics.
	 */

	public function test_removing_global_topic_from_group_keeps_it_global() {
		$group_post = $this->tagged_activity( $this->alpha, 'groups', $this->own_group );

		$this->assertTrue( $this->manager->bb_delete_topic( array( 'topic_id' => $this->alpha, 'item_type' => 'groups', 'item_id' => $this->own_group ) ) );

		$this->assertNull( $this->relationship( $this->alpha, 'groups', $this->own_group ) );
		$this->assertNotNull( $this->relationship( $this->alpha, 'activity', 0 ) );
		$this->assertSame( 0, $this->activity_topic( $group_post ) );
	}

	public function test_group_topic_limit_is_twenty_per_group() {
		self::set_current_user( $this->admin );
		for ( $i = 2; $i <= 20; $i++ ) {
			$this->add_topic( 'Group Topic ' . $i, 'groups', $this->own_group );
		}

		$over = $this->manager->bb_add_topic(
			array(
				'name'       => 'Group Topic 21',
				'item_type'  => 'groups',
				'item_id'    => $this->own_group,
				'error_type' => 'wp_error',
			)
		);
		$this->assertWPError( $over );

		// Another group is not affected by this group's limit.
		$this->add_topic( 'Group Topic 21', 'groups', $this->other_group );
	}

	/*
	 * Feed filter and posting permissions.
	 */

	public function test_feed_filter_by_topic_returns_only_tagged_posts() {
		// The manager is a singleton that adds its query filters once, in its constructor, and the
		// test case restores the hook table after every test, so attach them again here.
		$activity_topics = bb_activity_topics_manager_instance();
		add_filter( 'bp_activity_get_join_sql', array( $activity_topics, 'bb_activity_topic_get_join_sql' ), 10, 2 );
		add_filter( 'bp_activity_get_where_conditions', array( $activity_topics, 'bb_activity_topic_get_where_conditions' ), 10, 2 );

		$tagged   = $this->tagged_activity( $this->alpha );
		$untagged = self::factory()->activity->create( array( 'user_id' => $this->admin ) );

		$result = bp_activity_get( array( 'topic_id' => $this->alpha, 'per_page' => 50 ) );
		$ids    = wp_list_pluck( $result['activities'], 'id' );

		$this->assertContains( $tagged, array_map( 'intval', $ids ) );
		$this->assertNotContains( $untagged, array_map( 'intval', $ids ) );
	}

	/**
	 * Post an activity update through the nouveau AJAX handler and return the JSON reply.
	 */
	protected function post_update( $user_id, $post ) {
		if ( ! function_exists( 'bp_nouveau_ajax_post_update' ) ) {
			require_once BP_PLUGIN_DIR . 'bp-templates/bp-nouveau/includes/activity/ajax.php';
		}

		self::set_current_user( $user_id );
		$post['_wpnonce_post_update'] = wp_create_nonce( 'post_update' );

		return $this->ajax_with_post( 'bp_nouveau_ajax_post_update', $user_id, $post );
	}

	/**
	 * Like ajax(), but the request body already carries its own nonce field.
	 */
	protected function ajax_with_post( $callable, $user_id, $post ) {
		self::set_current_user( $user_id );
		$_POST    = wp_slash( $post );
		$_REQUEST = $_POST;

		$this->last_response    = '';
		$this->last_die_message = '';

		$level = ob_get_level();
		ob_start();
		try {
			call_user_func( $callable );
		} catch ( WPAjaxDieContinueException $e ) {
			// Expected.
		} catch ( WPAjaxDieStopException $e ) {
			$this->last_die_message = $e->getMessage();
		}
		if ( ob_get_level() > $level ) {
			$this->last_response .= (string) ob_get_clean();
		}

		return json_decode( $this->last_response, true );
	}

	public function test_topic_permission_is_checked_when_topic_is_not_required() {
		bp_update_option( 'bb_activity_topic_required', 0 );
		self::set_current_user( $this->admin );
		$restricted = $this->add_topic( 'Admins Only', 'activity', 0, 'mods_admins' );

		$response = $this->post_update(
			$this->member,
			array(
				'content'  => 'Posting into an admin-only topic.',
				'object'   => 'user',
				'topic_id' => $restricted,
			)
		);

		$this->assertFalse( $response['success'] );
		$this->assertSame( 'You do not have permission to post in this topic.', $response['data']['message'] );
	}

	public function test_group_topic_permission_and_ownership_are_checked_when_topic_is_not_required() {
		bp_update_option( 'bb_activity_topic_required', 0 );
		self::set_current_user( $this->admin );
		$organizers_only = $this->add_topic( 'Organizers Only Topic', 'groups', $this->own_group, 'admins' );

		$response = $this->post_update(
			$this->member,
			array(
				'content'  => 'Posting into an organizer-only group topic.',
				'object'   => 'group',
				'item_id'  => $this->own_group,
				'topic_id' => $organizers_only,
			)
		);
		$this->assertFalse( $response['success'] );
		$this->assertSame( 'You do not have permission to post in this topic.', $response['data']['message'] );

		// Beta is linked to the other group only.
		$response = $this->post_update(
			$this->member,
			array(
				'content'  => 'Posting with another group topic.',
				'object'   => 'group',
				'item_id'  => $this->own_group,
				'topic_id' => $this->beta,
			)
		);
		$this->assertFalse( $response['success'] );
		$this->assertSame( 'The topic does not exist.', $response['data']['message'] );
	}

	/**
	 * Messages the topic check in bp_nouveau_ajax_post_update() can send.
	 */
	protected function assert_not_refused_for_topic( $response, $message = '' ) {
		$this->assertIsArray( $response, $message );
		$error = isset( $response['data']['message'] ) ? $response['data']['message'] : '';
		$this->assertNotContains(
			$error,
			array( 'Please select a topic before posting.', 'The topic does not exist.', 'You do not have permission to post in this topic.' ),
			$message . ' ' . wp_json_encode( $response )
		);
		$this->assertTrue( $response['success'], $message . ' ' . wp_json_encode( $response ) );
	}

	/**
	 * A member's activity tagged with a topic, by the member.
	 */
	protected function member_activity( $topic_id, $component = 'activity', $item_id = 0 ) {
		bp_update_option( '_bp_enable_activity_edit', 1 );

		$activity = self::factory()->activity->create(
			array(
				'component' => $component,
				'type'      => 'activity_update',
				'item_id'   => $item_id,
				'user_id'   => $this->member,
				'content'   => 'Original content',
			)
		);
		bb_activity_topics_manager_instance()->bb_add_activity_topic_relationship(
			array(
				'topic_id'    => $topic_id,
				'activity_id' => $activity,
				'component'   => $component,
				'item_id'     => $item_id,
			)
		);

		return $activity;
	}

	/**
	 * Restrict an activity topic to admins, as the site admin would after members used it.
	 */
	protected function restrict_activity_topic( $topic_id ) {
		self::set_current_user( $this->admin );
		$this->manager->bb_update_topic_relationship(
			array(
				'permission_type' => 'mods_admins',
				'where'           => array( 'topic_id' => $topic_id, 'item_id' => 0, 'item_type' => 'activity' ),
			)
		);
	}

	public function data_topic_required() {
		return array( 'not required' => array( 0 ), 'required' => array( 1 ) );
	}

	/**
	 * @dataProvider data_topic_required
	 */
	public function test_edit_keeps_existing_topic_after_its_permission_changed( $required ) {
		bp_update_option( 'bb_activity_topic_required', $required );
		$activity = $this->member_activity( $this->alpha );
		$this->restrict_activity_topic( $this->alpha );

		$response = $this->post_update(
			$this->member,
			array( 'id' => $activity, 'content' => 'Edited content', 'object' => 'user', 'topic_id' => $this->alpha )
		);

		$this->assert_not_refused_for_topic( $response, 'Unchanged topic on edit' );
		$this->assertSame( $this->alpha, $this->activity_topic( $activity ) );
	}

	/**
	 * @dataProvider data_topic_required
	 */
	public function test_edit_without_topic_field_is_not_blocked( $required ) {
		bp_update_option( 'bb_activity_topic_required', $required );
		$activity = $this->member_activity( $this->alpha );

		$response = $this->post_update( $this->member, array( 'id' => $activity, 'content' => 'Edited content', 'object' => 'user' ) );

		$this->assert_not_refused_for_topic( $response, 'No topic_id posted (PROD-8796 isset guard)' );
		$this->assertSame( $this->alpha, $this->activity_topic( $activity ) );
	}

	/**
	 * @dataProvider data_topic_required
	 */
	public function test_edit_to_restricted_topic_is_refused( $required ) {
		bp_update_option( 'bb_activity_topic_required', $required );
		self::set_current_user( $this->admin );
		$restricted = $this->add_topic( 'Admins Only', 'activity', 0, 'mods_admins' );
		$activity   = $this->member_activity( $this->alpha );

		$response = $this->post_update(
			$this->member,
			array( 'id' => $activity, 'content' => 'Edited content', 'object' => 'user', 'topic_id' => $restricted )
		);

		$this->assertFalse( $response['success'] );
		$this->assertSame( 'You do not have permission to post in this topic.', $response['data']['message'] );
		$this->assertSame( $this->alpha, $this->activity_topic( $activity ) );
	}

	public function test_topics_off_ignores_topic_and_required_setting() {
		bp_update_option( 'bb_activity_topic_required', 1 );
		$activity = $this->member_activity( $this->alpha );
		bp_update_option( 'bb_enable_activity_topics', 0 );

		foreach ( array( 0, $this->beta ) as $topic_id ) {
			$this->assert_not_refused_for_topic(
				$this->post_update( $this->member, array( 'id' => $activity, 'content' => 'Edited while topics are off', 'object' => 'user', 'topic_id' => $topic_id ) ),
				'Topics off, topic_id ' . $topic_id
			);
		}
	}

	public function test_edit_to_allowed_topic_and_removing_topic() {
		bp_update_option( 'bb_activity_topic_required', 0 );
		$activity = $this->member_activity( $this->alpha );

		$this->assert_not_refused_for_topic(
			$this->post_update( $this->member, array( 'id' => $activity, 'content' => 'Moved', 'object' => 'user', 'topic_id' => $this->gamma ) ),
			'Change to an allowed topic'
		);
		$this->assertSame( $this->gamma, $this->activity_topic( $activity ) );

		$this->assert_not_refused_for_topic(
			$this->post_update( $this->member, array( 'id' => $activity, 'content' => 'No topic', 'object' => 'user', 'topic_id' => 0 ) ),
			'Removing the topic when it is not required'
		);

		bp_update_option( 'bb_activity_topic_required', 1 );
		$response = $this->post_update( $this->member, array( 'id' => $activity, 'content' => 'No topic', 'object' => 'user', 'topic_id' => 0 ) );
		$this->assertFalse( $response['success'] );
		$this->assertSame( 'Please select a topic before posting.', $response['data']['message'] );
	}

	/**
	 * @dataProvider data_topic_required
	 */
	public function test_group_edit_keeps_existing_topic_but_checks_a_new_one( $required ) {
		bp_update_option( 'bb_activity_topic_required', $required );
		self::set_current_user( $this->admin );
		$custom          = $this->add_topic( 'Members Then Admins', 'groups', $this->own_group, 'members' );
		$organizers_only = $this->add_topic( 'Organizers Only Topic', 'groups', $this->own_group, 'admins' );
		$activity        = $this->member_activity( $custom, 'groups', $this->own_group );

		// The organizer tightens the topic after the member posted.
		$this->manager->bb_update_topic_relationship(
			array(
				'permission_type' => 'admins',
				'where'           => array( 'topic_id' => $custom, 'item_id' => $this->own_group, 'item_type' => 'groups' ),
			)
		);

		$this->assert_not_refused_for_topic(
			$this->post_update( $this->member, array( 'id' => $activity, 'content' => 'Edited', 'object' => 'group', 'item_id' => $this->own_group, 'topic_id' => $custom ) ),
			'Unchanged group topic on edit'
		);
		$this->assertSame( $custom, $this->activity_topic( $activity ) );

		$response = $this->post_update( $this->member, array( 'id' => $activity, 'content' => 'Edited', 'object' => 'group', 'item_id' => $this->own_group, 'topic_id' => $organizers_only ) );
		$this->assertFalse( $response['success'] );
		$this->assertSame( 'You do not have permission to post in this topic.', $response['data']['message'] );
		$this->assertSame( $custom, $this->activity_topic( $activity ) );
	}

	public function data_topic_settings() {
		return array(
			'both, not required'     => array( 'allow_both', 0 ),
			'both, required'         => array( 'allow_both', 1 ),
			'create own, required'   => array( 'create_own_topics', 1 ),
			'only activity, not req' => array( 'only_from_activity_topics', 0 ),
		);
	}

	/**
	 * Editing a group topic that already has posts keeps or moves those posts.
	 *
	 * @dataProvider data_topic_settings
	 */
	public function test_organizer_edits_topic_that_has_posts( $mode, $required ) {
		bp_update_option( 'bb_activity_topic_required', $required );
		bp_update_option( 'bb-group-activity-topics-options', 'allow_both' );
		self::set_current_user( $this->admin );
		$custom   = $this->add_topic( 'Posts Custom', 'groups', $this->own_group );
		$global   = $this->member_activity( $this->alpha, 'groups', $this->own_group );
		$tagged   = $this->member_activity( $custom, 'groups', $this->own_group );
		bp_update_option( 'bb-group-activity-topics-options', $mode );

		// Permission-only edits keep every post on its topic.
		$this->assert_success( $this->add_ajax( $this->organizer, array( 'name' => 'posts-custom', 'topic_id' => $custom, 'permission_type' => 'admins' ) ), 'custom permission' );
		$this->assert_success( $this->add_ajax( $this->organizer, array( 'name' => 'alpha-topic', 'topic_id' => $this->alpha, 'permission_type' => 'mods' ) ), 'global permission' );
		$this->assertSame( $custom, $this->activity_topic( $tagged ) );
		$this->assertSame( $this->alpha, $this->activity_topic( $global ) );
		$this->assertSame( 'admins', $this->relationship( $custom, 'groups', $this->own_group )->permission_type );

		$rename = $this->add_ajax( $this->organizer, array( 'name' => 'Posts Renamed', 'topic_id' => $custom ) );
		if ( 'only_from_activity_topics' === $mode ) {
			// Renaming to a custom name is not possible in this mode; the post keeps its topic.
			$this->assertFalse( $rename['success'] );
			$this->assertSame( $custom, $this->activity_topic( $tagged ) );
			return;
		}

		$this->assert_success( $rename, 'rename' );
		$renamed = (int) $rename['data']['content']['topic']['topic_id'];
		$this->assertNotSame( $custom, $renamed );
		$this->assertSame( $renamed, $this->activity_topic( $tagged ), 'The post moves to the renamed topic.' );
		$this->assertSame( $this->alpha, $this->activity_topic( $global ), 'Other posts are untouched.' );
		$this->assertSame(
			'posts-renamed',
			$this->manager->bb_get_redirected_topic_slug( array( 'old_slug' => 'posts-custom', 'item_type' => 'groups', 'item_id' => $this->own_group ) )
		);
	}

	public function test_permission_label_is_not_stored_as_permission_type() {
		bp_update_option( 'bb-group-activity-topics-options', 'allow_both' );
		$custom = $this->add_topic( 'Label Custom', 'groups', $this->own_group );

		foreach ( array( 'organizer' => $this->organizer, 'admin' => $this->admin ) as $who => $user_id ) {
			$response = $this->add_ajax( $user_id, array( 'name' => 'label-custom', 'topic_id' => $custom, 'permission_type' => 'All group members' ) );

			$this->assertFalse( $response['success'], $who );
			$this->assertSame( 'Please choose who can post in this topic.', $response['data']['error'], $who );
			$this->assertSame( 'members', $this->relationship( $custom, 'groups', $this->own_group )->permission_type, $who );
		}

		$this->assertFalse( $this->add_ajax( $this->admin, array( 'name' => 'Label Activity', 'item_type' => 'activity', 'item_id' => 0, 'permission_type' => 'Anyone' ) )['success'] );
	}

	public function test_admin_only_topic_cannot_be_used_by_member() {
		self::set_current_user( $this->admin );
		$restricted = $this->add_topic( 'Admins Only', 'activity', 0, 'mods_admins' );

		self::set_current_user( $this->member );
		$this->assertFalse( bb_activity_topics_manager_instance()->bb_can_user_post_to_activity_topic( $restricted ) );
		$this->assertTrue( bb_activity_topics_manager_instance()->bb_can_user_post_to_activity_topic( $this->alpha ) );

		self::set_current_user( $this->admin );
		$this->assertTrue( bb_activity_topics_manager_instance()->bb_can_user_post_to_activity_topic( $restricted ) );
	}
	/**
	 * Number of activity/topic rows for a topic. A refused post must not add one.
	 */
	protected function topic_rows( $topic_id ) {
		global $wpdb;
		$table = bp_core_get_table_prefix() . 'bb_activity_topic_relationship';

		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE topic_id = %d", $topic_id ) ); // phpcs:ignore
	}

	public function data_object_spellings() {
		return array(
			'capitalised user'  => array( 'User', 'user' ),
			'upper-case user'   => array( 'USER', 'user' ),
			'user with symbol'  => array( 'user!', 'user' ),
			'capitalised group' => array( 'Group', 'group' ),
			'upper-case group'  => array( 'GROUP', 'group' ),
		);
	}

	/**
	 * @dataProvider data_object_spellings
	 */
	public function test_object_spelling_cannot_bypass_topic_permission_check( $object, $kind ) {
		bp_update_option( 'bb_activity_topic_required', 0 );
		self::set_current_user( $this->admin );
		$staff_only      = $this->add_topic( 'Staff Only Topic', 'activity', 0, 'mods_admins' );
		$organizers_only = $this->add_topic( 'Organizers Only Topic', 'groups', $this->own_group, 'admins' );
		$topic_id        = 'user' === $kind ? $staff_only : $organizers_only;
		$rows_before     = $this->topic_rows( $topic_id );

		$response = $this->post_update(
			$this->member,
			array(
				'content'  => 'Posting with object=' . $object,
				'object'   => $object,
				'item_id'  => 'group' === $kind ? $this->own_group : 0,
				'topic_id' => $topic_id,
			)
		);

		$this->assertFalse( $response['success'], $object );
		$this->assertSame( 'You do not have permission to post in this topic.', $response['data']['message'], $object );
		$this->assertSame( $rows_before, $this->topic_rows( $topic_id ), $object );

		if ( 'group' === $kind ) {
			// A global topic the group does not offer is still "does not exist" for the group.
			$rows_before = $this->topic_rows( $this->gamma );
			$response    = $this->post_update(
				$this->member,
				array(
					'content'  => 'Posting an unlinked global topic with object=' . $object,
					'object'   => $object,
					'item_id'  => $this->own_group,
					'topic_id' => $this->gamma,
				)
			);
			$this->assertFalse( $response['success'], $object );
			$this->assertSame( 'The topic does not exist.', $response['data']['message'], $object );
			$this->assertSame( $rows_before, $this->topic_rows( $this->gamma ), $object );
		}
	}
}
