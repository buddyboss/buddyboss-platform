<?php
/**
 * Group threads while "Group Messages" is disabled.
 *
 * @package BuddyBoss\Tests
 */

/**
 * Exception used to stop a screen or an AJAX handler inside a test.
 */
class BP_Tests_Messages_Group_Disabled_Stop extends Exception {

	/**
	 * Captured payload (redirect location, JSON output, ...).
	 *
	 * @var mixed
	 */
	public $payload;

	/**
	 * Constructor.
	 *
	 * @param string $message Stop reason.
	 * @param mixed  $payload Captured payload.
	 */
	public function __construct( $message, $payload = null ) {
		parent::__construct( $message );
		$this->payload = $payload;
	}
}

// phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound -- The helper exception class lives next to the test class.
/**
 * PROD-9747 / PROD-3077: group threads while "Group Messages" is disabled.
 *
 * Open (AJAX), reply (AJAX), direct URL (view and archived screens) and the cache of
 * bb_messages_get_disabled_group_thread_ids().
 *
 * Setting naming: option bp-disable-group-messages = 0 means Group Messages is DISABLED
 * (bp_disable_group_messages() returns false).
 *
 * @group messages
 * @group groups
 * @group prod-9747
 */
class BP_Tests_Messages_Group_Messages_Disabled extends BP_UnitTestCase {
	// phpcs:enable Generic.Files.OneObjectStructurePerFile.MultipleFound

	const DISABLED_TEXT = 'Group messages have been disabled by a site administrator.';

	/**
	 * Original value of the "Group Messages" setting.
	 *
	 * @var mixed
	 */
	protected $group_messages_option;

	/**
	 * Saved BuddyPress routing globals.
	 *
	 * @var array
	 */
	protected $bp_routing = array();

	/**
	 * Save the setting and the routing globals.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		$this->group_messages_option = bp_get_option( 'bp-disable-group-messages' );

		$bp               = buddypress();
		$this->bp_routing = array(
			'current_component' => $bp->current_component,
			'current_action'    => $bp->current_action,
			'action_variables'  => $bp->action_variables,
			'displayed_user'    => isset( $bp->displayed_user ) ? clone $bp->displayed_user : null,
			'template_message'  => isset( $bp->template_message ) ? $bp->template_message : null,
			'template_type'     => isset( $bp->template_message_type ) ? $bp->template_message_type : null,
		);
	}

	/**
	 * Restore the setting, the routing globals and the request superglobals.
	 *
	 * @return void
	 */
	public function tear_down() {
		$bp                        = buddypress();
		$bp->current_component     = $this->bp_routing['current_component'];
		$bp->current_action        = $this->bp_routing['current_action'];
		$bp->action_variables      = $this->bp_routing['action_variables'];
		$bp->template_message      = $this->bp_routing['template_message'];
		$bp->template_message_type = $this->bp_routing['template_type'];
		if ( null !== $this->bp_routing['displayed_user'] ) {
			$bp->displayed_user = $this->bp_routing['displayed_user'];
		}

		// Only the callbacks added by this class; the bootstrap's own wp_redirect => __return_false stays.
		remove_filter( 'wp_doing_ajax', '__return_true' );
		remove_filter( 'bp_messages_message_validated_content', '__return_true' );
		$_POST    = array();
		$_REQUEST = array();

		// Release's setting-change hook (bb_clear_cache_while_group_messsage_settings_updated) unsets the
		// unread-count group straight from WP_Object_Cache::$cache, a notice under the test cache: flush first.
		wp_cache_flush();
		bp_update_option( 'bp-disable-group-messages', $this->group_messages_option );
		wp_cache_flush();
		parent::tear_down();
	}

	/* Helpers ---------------------------------------------------------------- */

	/**
	 * Flag a message as an open group message sent to all group members (meta written by bp_media_messages_save_group_data()).
	 *
	 * @param int $message_id Message ID.
	 * @param int $thread_id  Thread ID.
	 * @param int $group_id   Group ID.
	 *
	 * @return void
	 */
	protected function flag_group_message( $message_id, $thread_id, $group_id ) {
		bp_messages_update_meta( $message_id, 'group_id', $group_id );
		bp_messages_update_meta( $message_id, 'group_message_thread_id', $thread_id );
		bp_messages_update_meta( $message_id, 'group_message_users', 'all' );
		bp_messages_update_meta( $message_id, 'group_message_type', 'open' );
		bp_messages_update_meta( $message_id, 'group_message_thread_type', 'new' );
		bp_messages_update_meta( $message_id, 'message_from', 'group' );
	}

	/**
	 * Group thread from $sender to $members (three people, so private messages never land in it), with a member reply.
	 *
	 * @param int   $sender   Sender user ID.
	 * @param int[] $members  Member user IDs.
	 * @param int   $group_id Group ID.
	 *
	 * @return int Thread ID.
	 */
	protected function create_group_thread( $sender, $members, $group_id ) {
		$group_message = self::factory()->message->create_and_get(
			array(
				'sender_id'  => $sender,
				'recipients' => $members,
				'subject'    => 'Group broadcast',
			)
		);
		$thread_id     = (int) $group_message->thread_id;
		$this->flag_group_message( $group_message->id, $thread_id, $group_id );

		self::factory()->message->create(
			array(
				'sender_id'  => $members[0],
				'thread_id'  => $thread_id,
				'recipients' => array_merge( array( $sender ), array_slice( $members, 1 ) ),
				'content'    => 'Member reply',
			)
		);

		return $thread_id;
	}

	/**
	 * Private thread from $sender to $recipient.
	 *
	 * @param int $sender    Sender user ID.
	 * @param int $recipient Recipient user ID.
	 *
	 * @return int Thread ID.
	 */
	protected function create_private_thread( $sender, $recipient ) {
		$message = self::factory()->message->create_and_get(
			array(
				'sender_id'  => $sender,
				'recipients' => array( $recipient ),
				'subject'    => 'Private',
			)
		);

		return (int) $message->thread_id;
	}

	/**
	 * Sender, two members, a group and its group thread.
	 *
	 * @return array
	 */
	protected function fixture() {
		// Build the fixture with Group Messages disabled, as on the customer site; tests toggle it afterwards.
		$this->set_group_messages( false );

		$u1       = self::factory()->user->create();
		$u2       = self::factory()->user->create();
		$u3       = self::factory()->user->create();
		$group_id = self::factory()->group->create( array( 'creator_id' => $u1 ) );
		groups_join_group( $group_id, $u2 );
		groups_join_group( $group_id, $u3 );

		$group_thread   = $this->create_group_thread( $u1, array( $u2, $u3 ), $group_id );
		$private_thread = $this->create_private_thread( $u1, $u2 );

		return compact( 'u1', 'u2', 'u3', 'group_id', 'group_thread', 'private_thread' );
	}

	/**
	 * Toggle "Group Messages" (true = enabled). Flushes the object cache first, see tear_down().
	 *
	 * @param bool $enabled Whether Group Messages is enabled.
	 *
	 * @return void
	 */
	protected function set_group_messages( $enabled ) {
		wp_cache_flush();
		bp_update_option( 'bp-disable-group-messages', $enabled ? 1 : 0 );
	}

	/**
	 * Number of messages saved in a thread.
	 *
	 * @param int $thread_id Thread ID.
	 *
	 * @return int
	 */
	protected function count_thread_messages( $thread_id ) {
		global $wpdb;
		$bp = buddypress();

		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$bp->messages->table_name_messages} WHERE thread_id = %d", $thread_id ) ); // phpcs:ignore
	}

	/**
	 * Run an AJAX handler the way admin-ajax.php does and return the decoded JSON.
	 *
	 * @param callable $handler AJAX handler.
	 *
	 * @throws Throwable When the handler fails with anything other than the stop exception.
	 *
	 * @return array
	 */
	protected function run_ajax( $handler ) {
		if ( ! function_exists( $handler ) ) {
			$this->markTestSkipped( "{$handler}() is not loaded (Nouveau template pack ajax.php)." );
		}

		$die_handler = function () {
			return function () {
				throw new BP_Tests_Messages_Group_Disabled_Stop( 'wp_die' );
			};
		};
		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter( 'wp_die_ajax_handler', $die_handler, 1 );

		$_REQUEST = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Copies the $_POST the test itself built; the handler verifies the nonce.
		$level    = error_reporting(); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.prevent_path_disclosure_error_reporting, WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_error_reporting -- Saves the level to restore it.
		// wp_send_json() sends a Content-Type header after PHPUnit printed output.
		error_reporting( $level & ~E_WARNING ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.prevent_path_disclosure_error_reporting, WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_error_reporting -- Silences the wp_send_json() header warning.
		ob_start();
		try {
			call_user_func( $handler );
			$output = ob_get_clean();
		} catch ( BP_Tests_Messages_Group_Disabled_Stop $e ) {
			$output = ob_get_clean();
		} catch ( Throwable $e ) {
			ob_end_clean();
			error_reporting( $level ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.prevent_path_disclosure_error_reporting, WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_error_reporting -- Restores the saved level.
			throw $e;
		}
		error_reporting( $level ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.prevent_path_disclosure_error_reporting, WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_error_reporting -- Restores the saved level.

		remove_filter( 'wp_doing_ajax', '__return_true' );
		remove_filter( 'wp_die_ajax_handler', $die_handler, 1 );

		$json = json_decode( $output, true );
		$this->assertIsArray( $json, 'AJAX handler must print JSON, got: ' . substr( (string) $output, 0, 300 ) );

		return $json;
	}

	/**
	 * Open a thread through the Nouveau AJAX handler.
	 *
	 * @param int $thread_id Thread ID.
	 *
	 * @return array Decoded JSON response.
	 */
	protected function ajax_open_thread( $thread_id ) {
		$_POST = array(
			'nonce' => wp_create_nonce( 'bp_nouveau_messages' ),
			'id'    => $thread_id,
		);

		return $this->run_ajax( 'bp_nouveau_ajax_get_thread_messages' );
	}

	/**
	 * Reply to a thread through the Nouveau AJAX handler.
	 *
	 * @param int $thread_id Thread ID.
	 *
	 * @return array Decoded JSON response.
	 */
	protected function ajax_reply( $thread_id ) {
		// filter_input( INPUT_POST, 'content' ) cannot read $_POST under CLI; the handler's own filter validates the content.
		add_filter( 'bp_messages_message_validated_content', '__return_true' );
		$_POST = array(
			'nonce'     => wp_create_nonce( 'messages_send_message' ),
			'thread_id' => $thread_id,
			'content'   => 'Reply from test',
			'hash'      => 'h1',
		);

		return $this->run_ajax( 'bp_nouveau_ajax_messages_send_reply' );
	}

	/**
	 * Point BuddyPress routing at members/<user>/messages/<action>/<vars> for $user_id (own profile).
	 *
	 * @param int    $user_id          Displayed user ID.
	 * @param string $action           Current action.
	 * @param array  $action_variables Action variables.
	 *
	 * @return void
	 */
	protected function route_messages( $user_id, $action, $action_variables ) {
		$bp                         = buddypress();
		$bp->current_component      = bp_get_messages_slug();
		$bp->current_action         = $action;
		$bp->action_variables       = $action_variables;
		$bp->displayed_user->id     = $user_id;
		$bp->displayed_user->domain = bp_core_get_user_domain( $user_id );
		$bp->template_message       = null;
		$bp->template_message_type  = null;
	}

	/**
	 * Run a screen function; a redirect or reaching the template stops it.
	 *
	 * @param callable $screen Screen function.
	 *
	 * @return array { stop: 'redirect'|'template'|'returned', location: string }
	 */
	protected function run_screen( $screen ) {
		$on_redirect = function ( $location ) {
			throw new BP_Tests_Messages_Group_Disabled_Stop( 'redirect', $location ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Test-only exception, never output.
		};
		$on_template = function () {
			throw new BP_Tests_Messages_Group_Disabled_Stop( 'template' );
		};
		// Priority 1: before the bootstrap's wp_redirect => __return_false, and before any template output.
		add_filter( 'wp_redirect', $on_redirect, 1 );
		add_action( 'messages_screen_conversation', $on_template, 1 );

		try {
			call_user_func( $screen );
			$result = array(
				'stop'     => 'returned',
				'location' => '',
			);
		} catch ( BP_Tests_Messages_Group_Disabled_Stop $e ) {
			$result = array(
				'stop'     => $e->getMessage(),
				'location' => (string) $e->payload,
			);
		}

		remove_filter( 'wp_redirect', $on_redirect, 1 );
		remove_action( 'messages_screen_conversation', $on_template, 1 );

		return $result;
	}

	/* AJAX: open conversation ------------------------------------------------ */

	/**
	 * Release: the refusal is the generic "Sorry, no messages were found." (type info) with no flag, so the JS
	 * cannot show the toast (ajax.php release lines: $response = feedback 'Sorry, no messages were found.').
	 */
	public function test_ajax_open_group_thread_is_refused_with_flag_when_group_messages_disabled() {
		$f = $this->fixture();
		$this->set_group_messages( false );
		$this->set_current_user( $f['u2'] );

		$json = $this->ajax_open_thread( $f['group_thread'] );

		$this->assertFalse( $json['success'] );
		$this->assertSame( self::DISABLED_TEXT, $json['data']['feedback'] );
		$this->assertSame( 'warning', $json['data']['type'] );
		$this->assertTrue( $json['data']['group_messages_disabled'] );
	}

	/**
	 * Group Messages disabled: a private thread still opens through AJAX.
	 *
	 * @return void
	 */
	public function test_ajax_open_private_thread_is_unchanged_when_group_messages_disabled() {
		$f = $this->fixture();
		$this->set_group_messages( false );
		$this->set_current_user( $f['u2'] );

		$json = $this->ajax_open_thread( $f['private_thread'] );

		$this->assertTrue( $json['success'], 'Private thread opens.' );
		$this->assertArrayNotHasKey( 'group_messages_disabled', (array) $json['data'] );
	}

	/**
	 * Group Messages enabled: a group thread opens through AJAX.
	 *
	 * @return void
	 */
	public function test_ajax_open_group_thread_works_when_group_messages_enabled() {
		$f = $this->fixture();
		$this->set_group_messages( true );
		$this->set_current_user( $f['u2'] );

		$json = $this->ajax_open_thread( $f['group_thread'] );

		$this->assertTrue( $json['success'], 'Group Messages enabled: group thread opens as before.' );
	}

	/**
	 * A thread refused for a reason other than the setting keeps the generic refusal (no toast flag).
	 */
	public function test_ajax_open_refused_by_other_filter_keeps_generic_response() {
		$f = $this->fixture();
		$this->set_group_messages( true );
		$this->set_current_user( $f['u2'] );

		$refuse = function ( $thread_id ) use ( $f ) {
			return (int) $thread_id === $f['private_thread'] ? 0 : $thread_id;
		};
		add_filter( 'bb_messages_validate_thread', $refuse, 99 );
		$json = $this->ajax_open_thread( $f['private_thread'] );
		remove_filter( 'bb_messages_validate_thread', $refuse, 99 );

		$this->assertFalse( $json['success'] );
		$this->assertArrayNotHasKey( 'group_messages_disabled', $json['data'] );
		$this->assertSame( 'info', $json['data']['type'] );
	}

	/* AJAX: reply ------------------------------------------------------------ */

	/**
	 * Release: no setting check in bp_nouveau_ajax_messages_send_reply(); the group branch calls
	 * bp_groups_messages_new_message() -> messages_new_message() and the reply is saved.
	 */
	public function test_ajax_reply_to_group_thread_is_refused_when_group_messages_disabled() {
		$f = $this->fixture();
		$this->set_group_messages( false );
		$this->set_current_user( $f['u2'] );

		$before = $this->count_thread_messages( $f['group_thread'] );
		$json   = $this->ajax_reply( $f['group_thread'] );

		$this->assertFalse( $json['success'] );
		$this->assertSame( self::DISABLED_TEXT, $json['data']['feedback'] );
		$this->assertSame( 'h1', $json['data']['hash'], 'Hash is echoed so the JS can remove the pending bubble.' );
		$this->assertSame( $before, $this->count_thread_messages( $f['group_thread'] ), 'No message is created.' );
	}

	/**
	 * Group Messages disabled: a reply to a private thread is still saved.
	 *
	 * @return void
	 */
	public function test_ajax_reply_to_private_thread_is_unchanged_when_group_messages_disabled() {
		$f = $this->fixture();
		$this->set_group_messages( false );
		$this->set_current_user( $f['u2'] );

		$before = $this->count_thread_messages( $f['private_thread'] );
		$json   = $this->ajax_reply( $f['private_thread'] );

		$this->assertTrue( $json['success'], 'Reply to a private thread is saved: ' . wp_json_encode( $json ) );
		$this->assertSame( $before + 1, $this->count_thread_messages( $f['private_thread'] ) );
	}

	/**
	 * Group Messages enabled: a reply to a group thread is saved.
	 *
	 * @return void
	 */
	public function test_ajax_reply_to_group_thread_works_when_group_messages_enabled() {
		$f = $this->fixture();
		$this->set_group_messages( true );
		$this->set_current_user( $f['u2'] );

		$before = $this->count_thread_messages( $f['group_thread'] );
		$json   = $this->ajax_reply( $f['group_thread'] );

		$this->assertTrue( $json['success'], 'Group Messages enabled: reply saved as before: ' . wp_json_encode( $json ) );
		$this->assertSame( $before + 1, $this->count_thread_messages( $f['group_thread'] ) );
	}

	/* Screens: direct URL ---------------------------------------------------- */

	/**
	 * Release: messages_screen_conversation() has no group check and loads the template
	 * (the refusal only happens later in the AJAX open, with the generic text).
	 */
	public function test_view_screen_redirects_group_thread_with_warning_when_group_messages_disabled() {
		$f = $this->fixture();
		$this->set_group_messages( false );
		$this->set_current_user( $f['u2'] );
		$this->route_messages( $f['u2'], 'view', array( $f['group_thread'] ) );

		$result = $this->run_screen( 'messages_screen_conversation' );

		$this->assertSame( 'redirect', $result['stop'] );
		$this->assertSame( trailingslashit( bp_core_get_user_domain( $f['u2'] ) . bp_get_messages_slug() ), $result['location'], 'Back to the inbox.' );
		// bp_core_add_message() sets the bp-message / bp-message-type cookies and these globals in the same call.
		$this->assertSame( self::DISABLED_TEXT, buddypress()->template_message );
		$this->assertSame( 'warning', buddypress()->template_message_type );
	}

	/**
	 * Group Messages disabled: the view screen still loads a private thread.
	 *
	 * @return void
	 */
	public function test_view_screen_loads_private_thread_when_group_messages_disabled() {
		$f = $this->fixture();
		$this->set_group_messages( false );
		$this->set_current_user( $f['u2'] );
		$this->route_messages( $f['u2'], 'view', array( $f['private_thread'] ) );

		$result = $this->run_screen( 'messages_screen_conversation' );

		$this->assertSame( 'template', $result['stop'], 'Private thread reaches the template.' );
		$this->assertNull( buddypress()->template_message );
	}

	/**
	 * Group Messages enabled: the view screen loads a group thread.
	 *
	 * @return void
	 */
	public function test_view_screen_loads_group_thread_when_group_messages_enabled() {
		$f = $this->fixture();
		$this->set_group_messages( true );
		$this->set_current_user( $f['u2'] );
		$this->route_messages( $f['u2'], 'view', array( $f['group_thread'] ) );

		$result = $this->run_screen( 'messages_screen_conversation' );

		$this->assertSame( 'template', $result['stop'], 'Group Messages enabled: unchanged.' );
	}

	/**
	 * A non-recipient keeps the release error; the group rule does not tell an outsider that the thread is a group thread.
	 */
	public function test_view_screen_non_recipient_keeps_access_error() {
		$f        = $this->fixture();
		$outsider = self::factory()->user->create();
		$this->set_group_messages( false );
		$this->set_current_user( $outsider );
		$this->route_messages( $outsider, 'view', array( $f['group_thread'] ) );

		$result = $this->run_screen( 'messages_screen_conversation' );

		$this->assertSame( 'redirect', $result['stop'] );
		// messages_is_valid_thread() is false for a non-recipient, so the release "no longer available" error wins (view.php, before the group check).
		$this->assertSame( 'The conversation you tried to access is no longer available', buddypress()->template_message );
		$this->assertSame( 'error', buddypress()->template_message_type );
	}

	/**
	 * Group Messages disabled: the archived view screen redirects a group thread with the warning.
	 *
	 * @return void
	 */
	public function test_archived_view_screen_redirects_group_thread_when_group_messages_disabled() {
		global $wpdb;

		if ( ! function_exists( 'messages_screen_archived' ) ) {
			$this->markTestSkipped( 'messages_screen_archived() is not loaded.' );
		}

		$f = $this->fixture();
		$this->set_group_messages( false );
		$this->set_current_user( $f['u2'] );

		// Archive the group thread for u2 as bp_nouveau_ajax_hide_thread() does.
		$wpdb->query( $wpdb->prepare( 'UPDATE ' . buddypress()->messages->table_name_recipients . ' SET is_hidden = 1 WHERE thread_id = %d AND user_id = %d', $f['group_thread'], $f['u2'] ) ); // phpcs:ignore
		do_action( 'bb_messages_thread_archived', $f['group_thread'], $f['u2'] );
		$this->assertTrue( messages_is_valid_archived_thread( $f['group_thread'], $f['u2'] ) );

		$this->route_messages( $f['u2'], bb_get_messages_archived_slug(), array( 'view', $f['group_thread'] ) );

		$result = $this->run_screen( 'messages_screen_archived' );

		$this->assertSame( 'redirect', $result['stop'] );
		$this->assertSame( trailingslashit( bb_get_messages_archived_url() ), $result['location'], 'Back to the archived list.' );
		$this->assertSame( self::DISABLED_TEXT, buddypress()->template_message );
		$this->assertSame( 'warning', buddypress()->template_message_type );
	}

	/* Cache of bb_messages_get_disabled_group_thread_ids() --------------------- */

	/**
	 * The disabled group thread IDs follow the setting when it is toggled.
	 *
	 * @return void
	 */
	public function test_disabled_group_thread_ids_follow_setting_toggle() {
		$f = $this->fixture();

		$this->set_group_messages( false );
		$this->assertSame( array( $f['group_thread'] ), bb_messages_get_disabled_group_thread_ids() );

		$this->set_group_messages( true );
		$this->assertSame( array(), bb_messages_get_disabled_group_thread_ids(), 'Enabled: nothing is hidden.' );
		$this->assertFalse( bb_messages_is_disabled_group_thread( $f['group_thread'] ) );

		$this->set_group_messages( false );
		$this->assertSame( array( $f['group_thread'] ), bb_messages_get_disabled_group_thread_ids() );
	}

	/**
	 * A group thread created while Group Messages is enabled is hidden once the setting is disabled
	 * (the cache filled before it existed is dropped by the setting-change incrementor reset).
	 */
	public function test_group_thread_created_while_enabled_is_hidden_after_disabling() {
		$f = $this->fixture();

		$this->set_group_messages( false );
		$this->assertContains( $f['group_thread'], bb_messages_get_disabled_group_thread_ids(), 'Cache filled.' );

		$this->set_group_messages( true );
		$u4            = self::factory()->user->create();
		$second_thread = $this->create_group_thread( $f['u1'], array( $f['u3'], $u4 ), $f['group_id'] );

		$this->set_group_messages( false );
		$ids = bb_messages_get_disabled_group_thread_ids();
		$this->assertContains( $f['group_thread'], $ids );
		$this->assertContains( $second_thread, $ids, 'New group thread is hidden.' );
	}

	/**
	 * The list is cached with the incremented 'bp_messages' key; any saved message resets it.
	 */
	public function test_new_message_invalidates_disabled_group_thread_ids() {
		$f = $this->fixture();
		$this->set_group_messages( false );

		$this->assertSame( array( $f['group_thread'] ), bb_messages_get_disabled_group_thread_ids() );

		// Drop the group flag without saving a message: the cached value is still served.
		$first = BP_Messages_Thread::get_first_message( $f['group_thread'] );
		bp_messages_delete_meta( $first->id, 'group_message_users' );
		$this->assertSame( array( $f['group_thread'] ), bb_messages_get_disabled_group_thread_ids(), 'Cached (no reset yet).' );

		// Any new message resets the incrementor (bb_core_clear_message_cache on messages_message_after_save / messages_message_sent).
		$this->create_private_thread( $f['u3'], $f['u1'] );
		$this->assertSame( array(), bb_messages_get_disabled_group_thread_ids(), 'Recomputed after a new message.' );
	}

	/**
	 * A personal reply inside a group thread (message_from = personal on the first message) is not refused.
	 */
	public function test_private_group_message_thread_is_not_hidden() {
		$u1       = self::factory()->user->create();
		$u2       = self::factory()->user->create();
		$u3       = self::factory()->user->create();
		$group_id = self::factory()->group->create( array( 'creator_id' => $u1 ) );

		$message   = self::factory()->message->create_and_get(
			array(
				'sender_id'  => $u1,
				'recipients' => array( $u2, $u3 ),
				'subject'    => 'Private group message',
			)
		);
		$thread_id = (int) $message->thread_id;
		bp_messages_update_meta( $message->id, 'group_id', $group_id );
		bp_messages_update_meta( $message->id, 'group_message_users', 'individual' );
		bp_messages_update_meta( $message->id, 'group_message_type', 'private' );
		bp_messages_update_meta( $message->id, 'message_from', 'group' );

		$this->set_group_messages( false );
		wp_cache_flush();

		$this->assertNotContains( $thread_id, bb_messages_get_disabled_group_thread_ids() );
		$this->assertSame( $thread_id, bb_messages_validate_groups_thread( $thread_id ), 'Release open check also lets it open.' );
	}

	/**
	 * The unread count already excluded group threads in release; the list exclusion must agree with it.
	 */
	public function test_unread_count_and_list_agree_when_group_messages_disabled() {
		$f = $this->fixture();
		$this->set_group_messages( false );
		$this->set_current_user( $f['u2'] );
		wp_cache_flush();

		$threads = BP_Messages_Thread::get_current_threads_for_user(
			array(
				'user_id'                        => $f['u2'],
				'fields'                         => 'ids',
				'exclude_disabled_group_threads' => true,
			)
		);
		$listed  = array_map( 'intval', (array) $threads['threads'] );

		$this->assertSame( array( $f['private_thread'] ), $listed );
		$this->assertSame( 1, (int) messages_get_unread_count( $f['u2'] ) );
	}

	/* Review round 5 (wf_72503eae-ee3) ---------------------------------------- */

	/**
	 * A: the lookup costs the same number of queries whatever the number of group threads.
	 */
	public function test_disabled_group_thread_ids_query_count_does_not_grow_with_threads() {
		global $wpdb;

		$f = $this->fixture();

		$count_queries = function () use ( $wpdb ) {
			bp_core_reset_incrementor( 'bp_messages' );
			$before = $wpdb->num_queries;
			bb_messages_get_disabled_group_thread_ids();

			return $wpdb->num_queries - $before;
		};

		$few = $count_queries();
		for ( $i = 0; $i < 5; $i++ ) {
			$this->create_group_thread( $f['u1'], array( $f['u2'], $f['u3'] ), $f['group_id'] );
		}
		$many = $count_queries();

		$this->assertCount( 6, bb_messages_get_disabled_group_thread_ids() );
		$this->assertSame( $few, $many, 'Same queries for 1 and 6 group threads.' );
		$this->assertLessThanOrEqual( 3, $many );
	}

	/**
	 * B: a member who cannot see the thread gets the generic answer, not the group reason.
	 */
	public function test_ajax_open_group_thread_by_outsider_keeps_generic_response() {
		$f        = $this->fixture();
		$outsider = self::factory()->user->create();
		$this->set_group_messages( false );
		$this->set_current_user( $outsider );

		$json = $this->ajax_open_thread( $f['group_thread'] );

		$this->assertFalse( $json['success'] );
		$this->assertSame( 'Sorry, no messages were found.', $json['data']['feedback'] );
		$this->assertArrayNotHasKey( 'group_messages_disabled', $json['data'] );
	}

	/**
	 * D: a member whose only conversation is the group thread goes straight to compose, where the notice is shown.
	 */
	public function test_view_screen_group_only_member_is_sent_to_compose() {
		$f = $this->fixture();
		$this->set_group_messages( false );
		$this->set_current_user( $f['u3'] );
		$this->route_messages( $f['u3'], 'view', array( $f['group_thread'] ) );

		$result = $this->run_screen( 'messages_screen_conversation' );

		$this->assertSame( 'redirect', $result['stop'] );
		$this->assertSame( trailingslashit( bp_core_get_user_domain( $f['u3'] ) . bp_get_messages_slug() . '/compose' ), $result['location'] );
		$this->assertSame( self::DISABLED_TEXT, buddypress()->template_message );
		$this->assertSame( 'warning', buddypress()->template_message_type );
	}

	/**
	 * F: an archived group thread opened with the view URL gets the warning instead of a silent redirect.
	 */
	public function test_view_screen_archived_group_thread_gets_warning() {
		global $wpdb;

		$f  = $this->fixture();
		$bp = buddypress();
		$wpdb->query( $wpdb->prepare( "UPDATE {$bp->messages->table_name_recipients} SET is_hidden = 1 WHERE thread_id = %d AND user_id = %d", $f['group_thread'], $f['u2'] ) ); // phpcs:ignore
		wp_cache_flush();

		$this->set_group_messages( false );
		$this->set_current_user( $f['u2'] );
		$this->route_messages( $f['u2'], 'view', array( $f['group_thread'] ) );

		$result = $this->run_screen( 'messages_screen_conversation' );

		$this->assertSame( 'redirect', $result['stop'] );
		$this->assertSame( self::DISABLED_TEXT, buddypress()->template_message );
	}

	/**
	 * K: the non-AJAX reply form cannot write into a group thread while Group Messages is disabled.
	 */
	public function test_legacy_reply_form_to_group_thread_is_refused() {
		$f = $this->fixture();
		$this->set_group_messages( false );
		$this->set_current_user( $f['u2'] );
		$this->route_messages( $f['u2'], 'view', array( $f['group_thread'] ) );

		$_POST    = array(
			'send'               => 'Send',
			'send_message_nonce' => wp_create_nonce( 'messages_send_message' ),
			'content'            => 'Legacy reply',
		);
		$_REQUEST = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Copies the $_POST the test itself built; the screen verifies the nonce.
		$before   = $this->count_thread_messages( $f['group_thread'] );

		$result = $this->run_screen( 'messages_action_conversation' );

		$this->assertSame( 'redirect', $result['stop'] );
		$this->assertSame( $before, $this->count_thread_messages( $f['group_thread'] ), 'No reply saved.' );
		$this->assertSame( self::DISABLED_TEXT, buddypress()->template_message );
	}

	/**
	 * O: recipient lists need the messages nonce and access to the thread.
	 */
	public function test_recipient_list_access_needs_nonce_and_thread_access() {
		if ( ! function_exists( 'bb_nouveau_ajax_can_list_thread_recipients' ) ) {
			$this->markTestSkipped( 'Nouveau messages ajax.php is not loaded.' );
		}

		$f        = $this->fixture();
		$outsider = self::factory()->user->create();

		$this->set_current_user( $f['u2'] );
		$_POST = array();
		$this->assertFalse( bb_nouveau_ajax_can_list_thread_recipients( $f['private_thread'] ), 'No nonce.' );

		$_POST = array( 'nonce' => 'bad' );
		$this->assertFalse( bb_nouveau_ajax_can_list_thread_recipients( $f['private_thread'] ), 'Bad nonce.' );

		$_POST = array( 'nonce' => wp_create_nonce( 'bp_nouveau_messages' ) );
		$this->assertTrue( bb_nouveau_ajax_can_list_thread_recipients( $f['private_thread'] ), 'Recipient with nonce.' );

		$this->set_current_user( $outsider );
		$_POST = array( 'nonce' => wp_create_nonce( 'bp_nouveau_messages' ) );
		$this->assertFalse( bb_nouveau_ajax_can_list_thread_recipients( $f['private_thread'] ), 'Not a recipient.' );
	}

	/* Review round 6: first-message edge cases of bb_messages_get_disabled_group_thread_ids() ---------- */

	/**
	 * Add a message to a thread (or start one) with an exact date_sent and only the given meta.
	 *
	 * @param int    $sender     Sender user ID.
	 * @param int[]  $recipients Recipient user IDs.
	 * @param int    $thread_id  Thread ID, 0 for a new thread.
	 * @param string $date_sent  MySQL date.
	 * @param array  $meta       Meta to keep on the message (all other meta is removed).
	 *
	 * @return object { id: int, thread_id: int }
	 */
	protected function message_with_meta( $sender, $recipients, $thread_id, $date_sent, $meta ) {
		global $wpdb;
		$bp = buddypress();

		$args = array(
			'sender_id'  => $sender,
			'recipients' => $recipients,
			'subject'    => 'Edge case',
			'content'    => 'Edge case ' . wp_rand(),
		);
		if ( $thread_id ) {
			$args['thread_id'] = $thread_id;
		}
		$message_id = (int) self::factory()->message->create( $args );
		$message    = new BP_Messages_Message( $message_id );

		if ( ! $thread_id ) {
			$this->assertSame( 1, $this->count_thread_messages( $message->thread_id ), 'A new thread was started (no existing-thread reuse).' );
		} else {
			$this->assertSame( (int) $thread_id, (int) $message->thread_id, 'Message added to the given thread.' );
		}

		$wpdb->query( $wpdb->prepare( "UPDATE {$bp->messages->table_name_messages} SET date_sent = %s WHERE id = %d", $date_sent, $message_id ) ); // phpcs:ignore
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->messages->table_name_meta} WHERE message_id = %d", $message_id ) ); // phpcs:ignore
		wp_cache_delete( $message_id, 'message_meta' );

		foreach ( $meta as $key => $value ) {
			if ( 'group_message_thread_id' === $key && true === $value ) {
				$value = (int) $message->thread_id;
			}
			bp_messages_update_meta( $message_id, $key, $value );
		}

		return (object) array(
			'id'        => $message_id,
			'thread_id' => (int) $message->thread_id,
		);
	}

	/**
	 * Meta of an open group message sent to all members (thread ID filled in by message_with_meta()).
	 *
	 * @param int $group_id Group ID.
	 *
	 * @return array
	 */
	protected function group_meta( $group_id ) {
		return array(
			'group_id'                  => $group_id,
			'group_message_thread_id'   => true,
			'group_message_users'       => 'all',
			'group_message_type'        => 'open',
			'group_message_thread_type' => 'new',
			'message_from'              => 'group',
		);
	}

	/**
	 * Three new users, so every edge-case thread has its own recipients and is never reused by the existing-thread lookup.
	 *
	 * @return int[]
	 */
	protected function trio() {
		return array( self::factory()->user->create(), self::factory()->user->create(), self::factory()->user->create() );
	}

	/**
	 * Three users and a group with Group Messages disabled.
	 *
	 * @return array
	 */
	protected function edge_fixture() {
		$this->set_group_messages( false );
		$u1       = self::factory()->user->create();
		$u2       = self::factory()->user->create();
		$u3       = self::factory()->user->create();
		$group_id = self::factory()->group->create( array( 'creator_id' => $u1 ) );

		return compact( 'u1', 'u2', 'u3', 'group_id' );
	}

	/**
	 * Assert the lookup agrees with the release open check (bb_messages_validate_groups_thread()) for a thread.
	 *
	 * @param int  $thread_id Thread ID.
	 * @param bool $expected  Whether the thread is expected to be refused.
	 *
	 * @return void
	 */
	protected function assert_matches_open_check( $thread_id, $expected ) {
		bp_core_reset_incrementor( 'bp_messages' );
		wp_cache_flush();
		$open_check = 0 === bb_messages_validate_groups_thread( $thread_id );

		bp_core_reset_incrementor( 'bp_messages' );
		wp_cache_flush();
		$lookup = in_array( $thread_id, bb_messages_get_disabled_group_thread_ids(), true );

		$this->assertSame( $expected, $open_check, 'Release open check (get_first_message + meta).' );
		$this->assertSame( $open_check, $lookup, 'Single-SQL lookup agrees with the open check.' );
	}

	/**
	 * Same date_sent: get_first_message() orders by date_sent then id, so the lower id wins.
	 * Private message first (lower id), group message second: not refused.
	 */
	public function test_disabled_lookup_tie_on_date_sent_lower_id_private_is_not_hidden() {
		$f    = $this->edge_fixture();
		$date = '2024-01-01 10:00:00';

		$first = $this->message_with_meta( $f['u1'], array( $f['u2'], $f['u3'] ), 0, $date, array( 'message_from' => 'personal' ) );
		$this->message_with_meta( $f['u1'], array( $f['u2'], $f['u3'] ), $first->thread_id, $date, $this->group_meta( $f['group_id'] ) );

		$this->assert_matches_open_check( $first->thread_id, false );
	}

	/**
	 * Same date_sent, group message has the lower id: refused.
	 */
	public function test_disabled_lookup_tie_on_date_sent_lower_id_group_is_hidden() {
		$f    = $this->edge_fixture();
		$date = '2024-01-01 10:00:00';

		$first = $this->message_with_meta( $f['u1'], array( $f['u2'], $f['u3'] ), 0, $date, $this->group_meta( $f['group_id'] ) );
		$this->message_with_meta( $f['u2'], array( $f['u1'], $f['u3'] ), $first->thread_id, $date, array( 'message_from' => 'personal' ) );

		$this->assert_matches_open_check( $first->thread_id, true );
	}

	/**
	 * The date wins over the id: the group message has the higher id but the older date, so it is the first message.
	 */
	public function test_disabled_lookup_older_date_beats_lower_id() {
		$f = $this->edge_fixture();

		$first = $this->message_with_meta( $f['u1'], array( $f['u2'], $f['u3'] ), 0, '2024-01-02 10:00:00', array( 'message_from' => 'personal' ) );
		$this->message_with_meta( $f['u1'], array( $f['u2'], $f['u3'] ), $first->thread_id, '2024-01-01 10:00:00', $this->group_meta( $f['group_id'] ) );

		$this->assert_matches_open_check( $first->thread_id, true );
	}

	/**
	 * A joined/left marker message is never the first message: the group message after it decides.
	 */
	public function test_disabled_lookup_skips_joined_left_marker_first() {
		$f = $this->edge_fixture();

		$marker = $this->message_with_meta( $f['u1'], array( $f['u2'], $f['u3'] ), 0, '2024-01-01 09:00:00', array( 'group_message_group_joined' => 'yes' ) );
		$this->message_with_meta( $f['u1'], array( $f['u2'], $f['u3'] ), $marker->thread_id, '2024-01-01 10:00:00', $this->group_meta( $f['group_id'] ) );

		$this->assert_matches_open_check( $marker->thread_id, true );

		list( $a, $b, $c ) = $this->trio();
		$left              = $this->message_with_meta( $a, array( $b, $c ), 0, '2024-01-01 09:00:00', array( 'group_message_group_left' => 'yes' ) );
		$this->message_with_meta( $a, array( $b, $c ), $left->thread_id, '2024-01-01 10:00:00', $this->group_meta( $f['group_id'] ) );

		$this->assert_matches_open_check( $left->thread_id, true );
	}

	/**
	 * Marker first, then a private message, then the group message: the private message is the first one.
	 */
	public function test_disabled_lookup_marker_then_private_then_group_is_not_hidden() {
		$f = $this->edge_fixture();

		$marker = $this->message_with_meta( $f['u1'], array( $f['u2'], $f['u3'] ), 0, '2024-01-01 09:00:00', array( 'group_message_group_joined' => 'yes' ) );
		$this->message_with_meta( $f['u2'], array( $f['u1'], $f['u3'] ), $marker->thread_id, '2024-01-01 10:00:00', array( 'message_from' => 'personal' ) );
		$this->message_with_meta( $f['u1'], array( $f['u2'], $f['u3'] ), $marker->thread_id, '2024-01-01 11:00:00', $this->group_meta( $f['group_id'] ) );

		$this->assert_matches_open_check( $marker->thread_id, false );
	}

	/**
	 * Group meta only on a later message (the thread qualifies as a candidate) but the first message is private: not refused.
	 */
	public function test_disabled_lookup_group_meta_only_on_later_message_is_not_hidden() {
		$f = $this->edge_fixture();

		$first = $this->message_with_meta( $f['u1'], array( $f['u2'], $f['u3'] ), 0, '2024-01-01 09:00:00', array( 'message_from' => 'personal' ) );
		$this->message_with_meta( $f['u1'], array( $f['u2'], $f['u3'] ), $first->thread_id, '2024-01-01 10:00:00', $this->group_meta( $f['group_id'] ) );

		$this->assert_matches_open_check( $first->thread_id, false );
	}

	/**
	 * A first message without any meta is skipped by get_first_message() (INNER JOIN on meta): the lookup does the same.
	 */
	public function test_disabled_lookup_first_message_without_meta_matches_open_check() {
		$f = $this->edge_fixture();

		$first = $this->message_with_meta( $f['u1'], array( $f['u2'], $f['u3'] ), 0, '2024-01-01 09:00:00', array() );
		$this->message_with_meta( $f['u1'], array( $f['u2'], $f['u3'] ), $first->thread_id, '2024-01-01 10:00:00', $this->group_meta( $f['group_id'] ) );

		$this->assert_matches_open_check( $first->thread_id, true );
	}

	/**
	 * The first message must carry every flag the open check reads: group thread ID and message_from = group.
	 */
	public function test_disabled_lookup_requires_thread_id_and_message_from() {
		$f = $this->edge_fixture();

		$meta = $this->group_meta( $f['group_id'] );
		unset( $meta['group_message_thread_id'] );
		$no_thread_id = $this->message_with_meta( $f['u1'], array( $f['u2'], $f['u3'] ), 0, '2024-01-01 09:00:00', $meta );
		$this->assert_matches_open_check( $no_thread_id->thread_id, false );

		list( $a, $b, $c )    = $this->trio();
		$meta                 = $this->group_meta( $f['group_id'] );
		$meta['message_from'] = 'personal';
		$not_from_group       = $this->message_with_meta( $a, array( $b, $c ), 0, '2024-01-01 09:00:00', $meta );
		$this->assert_matches_open_check( $not_from_group->thread_id, false );
	}

	/**
	 * One call over several threads returns exactly the refused ones (the GROUP BY keeps threads apart).
	 */
	public function test_disabled_lookup_mixed_threads_in_one_call() {
		$f = $this->edge_fixture();

		$hidden_a = $this->message_with_meta( $f['u1'], array( $f['u2'], $f['u3'] ), 0, '2024-01-01 09:00:00', $this->group_meta( $f['group_id'] ) );
		$this->message_with_meta( $f['u2'], array( $f['u1'], $f['u3'] ), $hidden_a->thread_id, '2024-01-01 10:00:00', array( 'message_from' => 'personal' ) );

		list( $a, $b, $c ) = $this->trio();
		$listed            = $this->message_with_meta( $a, array( $b, $c ), 0, '2024-01-01 09:00:00', array( 'message_from' => 'personal' ) );
		$this->message_with_meta( $a, array( $b, $c ), $listed->thread_id, '2024-01-01 10:00:00', $this->group_meta( $f['group_id'] ) );

		list( $d, $e, $g ) = $this->trio();
		$hidden_b          = $this->message_with_meta( $d, array( $e, $g ), 0, '2024-01-01 09:00:00', array( 'group_message_group_left' => 'yes' ) );
		$this->message_with_meta( $d, array( $e, $g ), $hidden_b->thread_id, '2024-01-01 10:00:00', $this->group_meta( $f['group_id'] ) );

		bp_core_reset_incrementor( 'bp_messages' );
		wp_cache_flush();

		$ids = bb_messages_get_disabled_group_thread_ids();
		$this->assertEqualsCanonicalizing( array( $hidden_a->thread_id, $hidden_b->thread_id ), $ids );
		$this->assertNotContains( $listed->thread_id, $ids );
	}

	/**
	 * The helper bb_messages_is_disabled_group_thread() is false for empty, negative and unknown IDs.
	 */
	public function test_is_disabled_group_thread_rejects_invalid_ids() {
		$f = $this->fixture();
		$this->set_group_messages( false );

		$this->assertTrue( bb_messages_is_disabled_group_thread( $f['group_thread'] ) );
		$this->assertTrue( bb_messages_is_disabled_group_thread( (string) $f['group_thread'] ), 'Numeric string is cast.' );
		$this->assertFalse( bb_messages_is_disabled_group_thread( 0 ) );
		$this->assertFalse( bb_messages_is_disabled_group_thread( -1 * $f['group_thread'] ) );
		$this->assertFalse( bb_messages_is_disabled_group_thread( $f['group_thread'] + 1000 ) );
		$this->assertFalse( bb_messages_is_disabled_group_thread( $f['private_thread'] ) );
	}

	/* Review round 6: access and controls --------------------------------------------------------- */

	/**
	 * B: a moderator who is not a recipient opens the group thread over AJAX: the reason is shown (access through bp_moderate).
	 */
	public function test_ajax_open_group_thread_by_moderator_gets_flag() {
		$f     = $this->fixture();
		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );
		if ( is_multisite() ) {
			grant_super_admin( $admin );
		}
		$this->set_group_messages( false );
		$this->set_current_user( $admin );
		$this->assertTrue( bp_current_user_can( 'bp_moderate' ) );

		$json = $this->ajax_open_thread( $f['group_thread'] );

		$this->assertFalse( $json['success'] );
		$this->assertSame( self::DISABLED_TEXT, $json['data']['feedback'] );
		$this->assertTrue( $json['data']['group_messages_disabled'] );
	}

	/**
	 * A member who is not in the group thread gets the generic reply error (access is checked first), and nothing is saved.
	 */
	public function test_ajax_reply_to_group_thread_by_outsider_keeps_generic_error() {
		$f        = $this->fixture();
		$outsider = self::factory()->user->create();
		$this->set_group_messages( false );
		$this->set_current_user( $outsider );
		$before = $this->count_thread_messages( $f['group_thread'] );

		$json = $this->ajax_reply( $f['group_thread'] );

		$this->assertFalse( $json['success'] );
		$this->assertSame( 'There was a problem sending your reply. Please try again.', $json['data']['feedback'] );
		$this->assertSame( $before, $this->count_thread_messages( $f['group_thread'] ) );
	}

	/**
	 * D control: a member who still has a listed thread goes back to the inbox, not compose.
	 */
	public function test_view_screen_member_with_other_threads_goes_to_inbox() {
		$f = $this->fixture();
		$this->set_group_messages( false );
		$this->set_current_user( $f['u2'] );
		$this->route_messages( $f['u2'], 'view', array( $f['group_thread'] ) );

		$result = $this->run_screen( 'messages_screen_conversation' );

		$this->assertSame( 'redirect', $result['stop'] );
		$this->assertSame( trailingslashit( bp_core_get_user_domain( $f['u2'] ) . bp_get_messages_slug() ), $result['location'] );
		$this->assertSame( 'warning', buddypress()->template_message_type );
	}

	/**
	 * B/F: a moderator viewing the group thread from the own profile gets the warning (access through bp_moderate).
	 */
	public function test_view_screen_moderator_gets_warning() {
		$f     = $this->fixture();
		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );
		if ( is_multisite() ) {
			grant_super_admin( $admin );
		}
		$this->set_group_messages( false );
		$this->set_current_user( $admin );
		$this->route_messages( $admin, 'view', array( $f['group_thread'] ) );

		$result = $this->run_screen( 'messages_screen_conversation' );

		$this->assertSame( 'redirect', $result['stop'] );
		$this->assertSame( self::DISABLED_TEXT, buddypress()->template_message );
	}

	/**
	 * K control: the legacy reply form still saves a reply in a private thread.
	 */
	public function test_legacy_reply_form_to_private_thread_is_saved() {
		$f = $this->fixture();
		$this->set_group_messages( false );
		$this->set_current_user( $f['u2'] );
		$this->route_messages( $f['u2'], 'view', array( $f['private_thread'] ) );

		$_POST    = array(
			'send'               => 'Send',
			'send_message_nonce' => wp_create_nonce( 'messages_send_message' ),
			'content'            => 'Legacy reply',
		);
		$_REQUEST = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Copies the $_POST the test itself built; the screen verifies the nonce.
		$before   = $this->count_thread_messages( $f['private_thread'] );

		$result = $this->run_screen( 'messages_action_conversation' );

		$this->assertSame( 'redirect', $result['stop'] );
		$this->assertSame( $before + 1, $this->count_thread_messages( $f['private_thread'] ), 'Reply saved.' );
		$this->assertNotSame( self::DISABLED_TEXT, buddypress()->template_message );
	}

	/**
	 * K control: with Group Messages enabled the legacy reply form saves a reply in the group thread.
	 */
	public function test_legacy_reply_form_to_group_thread_works_when_enabled() {
		$f = $this->fixture();
		$this->set_group_messages( true );
		$this->set_current_user( $f['u2'] );
		$this->route_messages( $f['u2'], 'view', array( $f['group_thread'] ) );

		$_POST    = array(
			'send'               => 'Send',
			'send_message_nonce' => wp_create_nonce( 'messages_send_message' ),
			'content'            => 'Legacy reply',
		);
		$_REQUEST = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Copies the $_POST the test itself built; the screen verifies the nonce.
		$before   = $this->count_thread_messages( $f['group_thread'] );

		$this->run_screen( 'messages_action_conversation' );

		$this->assertSame( $before + 1, $this->count_thread_messages( $f['group_thread'] ), 'Reply saved while enabled.' );
	}

	/**
	 * O: a moderator may list the recipients of any thread; thread ID 0 and a nonce of another action are refused.
	 */
	public function test_recipient_list_access_moderator_and_invalid_thread() {
		if ( ! function_exists( 'bb_nouveau_ajax_can_list_thread_recipients' ) ) {
			$this->markTestSkipped( 'Nouveau messages ajax.php is not loaded.' );
		}

		$f     = $this->fixture();
		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );
		if ( is_multisite() ) {
			grant_super_admin( $admin );
		}

		$this->set_current_user( $admin );
		$_POST = array( 'nonce' => wp_create_nonce( 'bp_nouveau_messages' ) );
		$this->assertTrue( bb_nouveau_ajax_can_list_thread_recipients( $f['private_thread'] ), 'Moderator with nonce.' );
		$this->assertFalse( bb_nouveau_ajax_can_list_thread_recipients( 0 ), 'Thread ID 0.' );

		$this->set_current_user( $f['u2'] );
		$_POST = array( 'nonce' => wp_create_nonce( 'messages_send_message' ) );
		$this->assertFalse( bb_nouveau_ajax_can_list_thread_recipients( $f['private_thread'] ), 'Nonce of another action.' );
		$_POST = array();
	}

	/* Review round 6 (R6-2): the notice survives to the page the member lands on --------------------- */

	/**
	 * Archive a thread for a user as bp_nouveau_ajax_hide_thread() does.
	 *
	 * @param int $thread_id Thread ID.
	 * @param int $user_id   User ID.
	 *
	 * @return void
	 */
	protected function archive_thread_for( $thread_id, $user_id ) {
		global $wpdb;
		$bp = buddypress();

		$wpdb->query( $wpdb->prepare( "UPDATE {$bp->messages->table_name_recipients} SET is_hidden = 1 WHERE thread_id = %d AND user_id = %d", $thread_id, $user_id ) ); // phpcs:ignore
		do_action( 'bb_messages_thread_archived', $thread_id, $user_id );
		wp_cache_flush();
	}

	/**
	 * Second hop: run the handler of the page the first redirect points at, keeping the notice of the first hop
	 * (on a real request bp_core_setup_message() reads it back from the cookie). A redirect here would use up the
	 * notice; reaching the template ($hook) means it is shown.
	 *
	 * @param int      $user_id          Displayed user ID.
	 * @param string   $action           Current action of the landing page.
	 * @param array    $action_variables Action variables of the landing page.
	 * @param callable $handler          Screen or action handler of the landing page.
	 * @param string   $hook             Action fired right before the landing page template loads.
	 *
	 * @return array { stop: 'redirect'|'template'|'returned', location: string }
	 */
	protected function run_second_hop( $user_id, $action, $action_variables, $handler, $hook ) {
		$bp      = buddypress();
		$message = $bp->template_message;
		$type    = $bp->template_message_type;

		$this->route_messages( $user_id, $action, $action_variables );
		$bp->template_message      = $message;
		$bp->template_message_type = $type;

		$on_template = function () {
			throw new BP_Tests_Messages_Group_Disabled_Stop( 'template' );
		};
		add_action( $hook, $on_template, 1 );
		$result = $this->run_screen( $handler );
		remove_action( $hook, $on_template, 1 );

		return $result;
	}

	/**
	 * R6-2: archived group thread by URL while the member has another archived thread: the first redirect goes straight
	 * to that thread (where the archived list would send the member), and that page loads with the warning.
	 */
	public function test_archived_view_group_thread_goes_straight_to_other_archived_thread() {
		$f = $this->fixture();
		$this->archive_thread_for( $f['group_thread'], $f['u2'] );
		$this->archive_thread_for( $f['private_thread'], $f['u2'] );
		$this->set_group_messages( false );
		$this->set_current_user( $f['u2'] );
		$this->route_messages( $f['u2'], bb_get_messages_archived_slug(), array( 'view', $f['group_thread'] ) );

		$hop1 = $this->run_screen( 'messages_screen_archived' );

		$this->assertSame( 'redirect', $hop1['stop'] );
		$this->assertSame( trailingslashit( bp_core_get_user_domain( $f['u2'] ) . bp_get_messages_slug() . '/archived/view/' . $f['private_thread'] ), $hop1['location'], 'Straight to the other archived thread.' );
		$this->assertSame( self::DISABLED_TEXT, buddypress()->template_message );

		$hop2 = $this->run_second_hop( $f['u2'], bb_get_messages_archived_slug(), array( 'view', $f['private_thread'] ), 'messages_screen_archived', 'messages_screen_archived' );

		$this->assertSame( 'template', $hop2['stop'], 'The landing page loads, no second redirect.' );
		$this->assertSame( self::DISABLED_TEXT, buddypress()->template_message );
		$this->assertSame( 'warning', buddypress()->template_message_type );
	}

	/**
	 * R6-2: archived group thread by URL, no other archived thread: the archived list loads with the warning
	 * (messages_action_archived() has no thread to send the member on to).
	 */
	public function test_archived_view_group_thread_without_other_archived_thread_lands_on_archived_list() {
		$f = $this->fixture();
		$this->archive_thread_for( $f['group_thread'], $f['u2'] );
		$this->set_group_messages( false );
		$this->set_current_user( $f['u2'] );
		$this->route_messages( $f['u2'], bb_get_messages_archived_slug(), array( 'view', $f['group_thread'] ) );

		$hop1 = $this->run_screen( 'messages_screen_archived' );

		$this->assertSame( 'redirect', $hop1['stop'] );
		$this->assertSame( trailingslashit( bb_get_messages_archived_url() ), $hop1['location'] );

		$hop2 = $this->run_second_hop( $f['u2'], bb_get_messages_archived_slug(), array(), 'messages_action_archived', 'messages_action_archived' );

		$this->assertSame( 'template', $hop2['stop'], 'The archived list loads, no second redirect.' );
		$this->assertSame( self::DISABLED_TEXT, buddypress()->template_message );
		$this->assertSame( 'warning', buddypress()->template_message_type );
	}

	/**
	 * R6-2: the view screen lands on the inbox for a member with listed threads, and the inbox loads without redirecting.
	 */
	public function test_view_screen_group_thread_inbox_loads_with_warning() {
		$f = $this->fixture();
		$this->set_group_messages( false );
		$this->set_current_user( $f['u2'] );
		$this->route_messages( $f['u2'], 'view', array( $f['group_thread'] ) );

		$hop1 = $this->run_screen( 'messages_screen_conversation' );

		$this->assertSame( trailingslashit( bp_core_get_user_domain( $f['u2'] ) . bp_get_messages_slug() ), $hop1['location'] );

		$hop2 = $this->run_second_hop( $f['u2'], 'inbox', array(), 'messages_screen_inbox', 'messages_screen_inbox' );

		$this->assertSame( 'template', $hop2['stop'], 'The inbox loads, no second redirect.' );
		$this->assertSame( self::DISABLED_TEXT, buddypress()->template_message );
	}

	/**
	 * R6-2: legacy reply refusal for a member whose only conversation is the group thread goes straight to compose
	 * (the inbox would redirect there and drop the notice), and compose loads with the warning.
	 */
	public function test_legacy_reply_refusal_group_only_member_lands_on_compose() {
		$f = $this->fixture();
		$this->set_group_messages( false );
		$this->set_current_user( $f['u3'] );
		$this->route_messages( $f['u3'], 'view', array( $f['group_thread'] ) );

		$_POST    = array(
			'send'               => 'Send',
			'send_message_nonce' => wp_create_nonce( 'messages_send_message' ),
			'content'            => 'Legacy reply',
		);
		$_REQUEST = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Copies the $_POST the test itself built; the screen verifies the nonce.
		$before   = $this->count_thread_messages( $f['group_thread'] );

		$hop1 = $this->run_screen( 'messages_action_conversation' );

		$this->assertSame( 'redirect', $hop1['stop'] );
		$this->assertSame( trailingslashit( bp_core_get_user_domain( $f['u3'] ) . bp_get_messages_slug() . '/compose' ), $hop1['location'] );
		$this->assertSame( $before, $this->count_thread_messages( $f['group_thread'] ), 'No reply saved.' );

		$_POST    = array();
		$_REQUEST = array();
		$hop2     = $this->run_second_hop( $f['u3'], 'compose', array(), 'messages_screen_compose', 'messages_screen_compose' );

		$this->assertSame( 'template', $hop2['stop'], 'Compose loads, no second redirect.' );
		$this->assertSame( self::DISABLED_TEXT, buddypress()->template_message );
		$this->assertSame( 'warning', buddypress()->template_message_type );
	}

	/**
	 * R6-2 control: a member with other listed threads is sent to the inbox by the legacy reply refusal.
	 */
	public function test_legacy_reply_refusal_member_with_threads_lands_on_inbox() {
		$f = $this->fixture();
		$this->set_group_messages( false );
		$this->set_current_user( $f['u2'] );
		$this->route_messages( $f['u2'], 'view', array( $f['group_thread'] ) );

		$_POST    = array(
			'send'               => 'Send',
			'send_message_nonce' => wp_create_nonce( 'messages_send_message' ),
			'content'            => 'Legacy reply',
		);
		$_REQUEST = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Copies the $_POST the test itself built; the screen verifies the nonce.

		$hop1 = $this->run_screen( 'messages_action_conversation' );

		$this->assertSame( trailingslashit( bp_core_get_user_domain( $f['u2'] ) . bp_get_messages_slug() ), $hop1['location'] );
		$this->assertSame( self::DISABLED_TEXT, buddypress()->template_message );
	}

	/* Review round 6 (R6-3b-1): one-row recipient check ---------------------------------------------- */

	/**
	 * Mark a user's recipient row of a thread as deleted, as messages_delete_thread() does while others remain.
	 *
	 * @param int $thread_id Thread ID.
	 * @param int $user_id   User ID.
	 *
	 * @return void
	 */
	protected function delete_thread_for( $thread_id, $user_id ) {
		global $wpdb;
		$bp = buddypress();

		$wpdb->query( $wpdb->prepare( "UPDATE {$bp->messages->table_name_recipients} SET is_deleted = 1 WHERE thread_id = %d AND user_id = %d", $thread_id, $user_id ) ); // phpcs:ignore
		bp_core_reset_incrementor( 'bp_messages' );
		wp_cache_flush();
	}

	/**
	 * R6-3b-1: the one-row check gives the same answer as messages_check_thread_access() (recipient, outsider,
	 * deleted recipient, invalid ids).
	 */
	public function test_active_thread_recipient_matches_check_thread_access() {
		$f        = $this->fixture();
		$outsider = self::factory()->user->create();
		$this->delete_thread_for( $f['private_thread'], $f['u2'] );

		$cases = array(
			'recipient'         => array( $f['group_thread'], $f['u2'], true ),
			'sender'            => array( $f['private_thread'], $f['u1'], true ),
			'outsider'          => array( $f['group_thread'], $outsider, false ),
			'deleted recipient' => array( $f['private_thread'], $f['u2'], false ),
			'unknown thread'    => array( $f['group_thread'] + 1000, $f['u2'], false ),
			'thread 0'          => array( 0, $f['u2'], false ),
		);

		foreach ( $cases as $label => $case ) {
			list( $thread_id, $user_id, $expected ) = $case;
			$this->assertSame( $expected, bb_messages_is_active_thread_recipient( $thread_id, $user_id ), $label );
			$this->assertSame( $expected, (bool) messages_check_thread_access( $thread_id, $user_id ), $label . ' (messages_check_thread_access)' );
		}

		$this->set_current_user( $f['u3'] );
		$this->assertTrue( bb_messages_is_active_thread_recipient( $f['group_thread'] ), 'Defaults to the logged-in user.' );
		$this->set_current_user( 0 );
		$this->assertFalse( bb_messages_is_active_thread_recipient( $f['group_thread'] ), 'Logged out.' );
	}

	/**
	 * R6-3b-1: the recipient-list access check reads one row: it does not load the full recipient list.
	 */
	public function test_recipient_list_access_does_not_load_all_recipients() {
		global $wpdb;

		if ( ! function_exists( 'bb_nouveau_ajax_can_list_thread_recipients' ) ) {
			$this->markTestSkipped( 'Nouveau messages ajax.php is not loaded.' );
		}

		$f       = $this->fixture();
		$members = self::factory()->user->create_many( 30 );
		$thread  = (int) self::factory()->message->create_and_get(
			array(
				'sender_id'  => $f['u1'],
				'recipients' => $members,
				'subject'    => 'Large thread',
			)
		)->thread_id;

		$this->set_current_user( $members[0] );
		$_POST = array( 'nonce' => wp_create_nonce( 'bp_nouveau_messages' ) );
		bp_core_reset_incrementor( 'bp_messages' );
		wp_cache_flush();
		get_userdata( $members[0] );

		$before = $wpdb->num_queries;
		$this->assertTrue( bb_messages_is_active_thread_recipient( $thread ) );
		$this->assertLessThanOrEqual( 2, $wpdb->num_queries - $before, 'One-row lookup.' );

		wp_cache_flush();
		$this->assertTrue( bb_nouveau_ajax_can_list_thread_recipients( $thread ) );
		$this->assertFalse( wp_cache_get( 'thread_recipients_' . $thread, 'bp_messages' ), 'The full recipient list is not loaded.' );
		$_POST = array();
	}

	/**
	 * R6-3b-1: a recipient who deleted the thread is refused, as with messages_check_thread_access().
	 */
	public function test_recipient_list_access_refuses_deleted_recipient() {
		if ( ! function_exists( 'bb_nouveau_ajax_can_list_thread_recipients' ) ) {
			$this->markTestSkipped( 'Nouveau messages ajax.php is not loaded.' );
		}

		$f = $this->fixture();
		$this->delete_thread_for( $f['private_thread'], $f['u2'] );
		$this->set_current_user( $f['u2'] );
		$_POST = array( 'nonce' => wp_create_nonce( 'bp_nouveau_messages' ) );

		$this->assertFalse( bb_nouveau_ajax_can_list_thread_recipients( $f['private_thread'] ) );
		$this->assertTrue( bb_nouveau_ajax_can_list_thread_recipients( $f['group_thread'] ), 'Other thread unchanged.' );
		$_POST = array();
	}

	/* Review round 6 (R6-3b-2): the lookup reads only the four flags ---------------------------------- */

	/**
	 * R6-3b-2: the lookup reads only the four keys it checks (no full meta prime of the first message), in two
	 * queries, and returns the same threads.
	 */
	public function test_disabled_lookup_reads_only_the_checked_meta() {
		global $wpdb;

		$f             = $this->fixture();
		$first_message = BP_Messages_Thread::get_first_message( $f['group_thread'] );
		bp_messages_update_meta( $first_message->id, 'message_users_ids', implode( ',', range( 1, 500 ) ) );

		bp_core_reset_incrementor( 'bp_messages' );
		wp_cache_flush();
		$this->assertFalse( bp_disable_group_messages(), 'Group Messages disabled (also reloads the option before counting).' );

		$before = $wpdb->num_queries;
		$ids    = bb_messages_get_disabled_group_thread_ids();

		$this->assertSame( array( $f['group_thread'] ), $ids );
		$this->assertLessThanOrEqual( 2, $wpdb->num_queries - $before, 'Two queries at most.' );
		$this->assertFalse( wp_cache_get( $first_message->id, 'message_meta' ), 'The meta of the first message is not primed.' );

		$before = $wpdb->num_queries;
		$this->assertSame( $ids, bb_messages_get_disabled_group_thread_ids(), 'Cached result.' );
		$this->assertSame( 0, $wpdb->num_queries - $before, 'No query when cached.' );
	}

	/* Review round 6 (R6-S2): archive / unarchive need access to the thread ---------------------------- */

	/**
	 * Archive (hide) or unarchive threads through the Nouveau AJAX handler.
	 *
	 * @param string    $handler    AJAX handler.
	 * @param int|array $thread_ids Thread ID(s).
	 *
	 * @return array Decoded JSON response.
	 */
	protected function ajax_toggle_archive( $handler, $thread_ids ) {
		$_POST = array(
			'nonce' => wp_create_nonce( 'bp_nouveau_messages' ),
			'id'    => implode( ',', (array) $thread_ids ),
		);

		return $this->run_ajax( $handler );
	}

	/**
	 * Whether a user's recipient row of a thread is hidden (archived).
	 *
	 * @param int $thread_id Thread ID.
	 * @param int $user_id   User ID.
	 *
	 * @return int|null 1 hidden, 0 visible, null no row.
	 */
	protected function is_hidden_for( $thread_id, $user_id ) {
		global $wpdb;
		$bp     = buddypress();
		$hidden = $wpdb->get_var( $wpdb->prepare( "SELECT is_hidden FROM {$bp->messages->table_name_recipients} WHERE thread_id = %d AND user_id = %d", $thread_id, $user_id ) ); // phpcs:ignore

		return null === $hidden ? null : (int) $hidden;
	}

	/**
	 * R6-S2: an outsider cannot archive or unarchive a thread, and the error names nobody (no recipients, no group).
	 */
	public function test_hide_and_unhide_thread_refuse_outsider() {
		$f        = $this->fixture();
		$outsider = self::factory()->user->create();
		$this->set_current_user( $outsider );
		$archived = did_action( 'bb_messages_thread_archived' );

		foreach ( array( $f['private_thread'], $f['group_thread'] ) as $thread_id ) {
			$json = $this->ajax_toggle_archive( 'bp_nouveau_ajax_hide_thread', $thread_id );
			$this->assertFalse( $json['success'] );
			$this->assertSame( 'There was a problem archiving conversation.', $json['data']['feedback'] );
			$this->assertArrayNotHasKey( 'toast_message', $json['data'] );

			$json = $this->ajax_toggle_archive( 'bp_nouveau_ajax_unhide_thread', $thread_id );
			$this->assertFalse( $json['success'] );
			$this->assertSame( 'There was a problem unarchiving the conversation.', $json['data']['feedback'] );
			$this->assertArrayNotHasKey( 'toast_message', $json['data'] );
		}

		$this->assertSame( $archived, did_action( 'bb_messages_thread_archived' ), 'No archived action for a foreign thread.' );
	}

	/**
	 * R6-S2: one foreign thread in the list refuses the whole request before anything is archived.
	 */
	public function test_hide_thread_with_a_foreign_thread_changes_nothing() {
		$f       = $this->fixture();
		$other_a = self::factory()->user->create();
		$other_b = self::factory()->user->create();
		$foreign = $this->create_private_thread( $other_a, $other_b );
		$this->set_current_user( $f['u2'] );

		$json = $this->ajax_toggle_archive( 'bp_nouveau_ajax_hide_thread', array( $f['private_thread'], $foreign ) );

		$this->assertFalse( $json['success'] );
		$this->assertSame( 0, $this->is_hidden_for( $f['private_thread'], $f['u2'] ), 'Own thread not archived either.' );
	}

	/**
	 * R6-S2 control: a recipient still archives and unarchives the own thread, with the toast naming the other member.
	 */
	public function test_hide_and_unhide_thread_work_for_recipient() {
		$f = $this->fixture();
		$this->set_current_user( $f['u2'] );

		$json = $this->ajax_toggle_archive( 'bp_nouveau_ajax_hide_thread', $f['private_thread'] );

		$this->assertTrue( $json['success'] );
		$this->assertStringContainsString( bp_core_get_user_displayname( $f['u1'] ), $json['data']['toast_message'] );
		$this->assertSame( 1, $this->is_hidden_for( $f['private_thread'], $f['u2'] ) );

		$json = $this->ajax_toggle_archive( 'bp_nouveau_ajax_unhide_thread', $f['private_thread'] );

		$this->assertTrue( $json['success'] );
		$this->assertStringContainsString( bp_core_get_user_displayname( $f['u1'] ), $json['data']['toast_message'] );
		$this->assertSame( 0, $this->is_hidden_for( $f['private_thread'], $f['u2'] ) );
	}

	/**
	 * R6-S2: moderators keep the delete handler's rule (access or bp_moderate); only their own row could change.
	 */
	public function test_hide_thread_moderator_is_allowed_as_for_delete() {
		$f     = $this->fixture();
		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );
		if ( is_multisite() ) {
			grant_super_admin( $admin );
		}
		$this->set_current_user( $admin );

		$json = $this->ajax_toggle_archive( 'bp_nouveau_ajax_hide_thread', $f['private_thread'] );

		$this->assertTrue( $json['success'] );
		$this->assertSame( 0, $this->is_hidden_for( $f['private_thread'], $f['u2'] ), 'Recipients are not archived for the moderator.' );
	}
}
