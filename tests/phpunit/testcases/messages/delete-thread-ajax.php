<?php
/**
 * Nouveau "Delete conversation" AJAX gate (PROD-10594 F-04).
 *
 * Deleting a whole conversation is limited to moderators and admins switched into a
 * member's account. A member must not pass the switched-admin branch by copying their
 * own logged_in / auth cookies into the members-switching cookies.
 *
 * @group messages
 * @group messages_delete_thread_ajax
 */
class BP_Tests_Messages_Delete_Thread_Ajax extends BP_UnitTestCase {

	/**
	 * Cookies set by a test, removed in tearDown().
	 *
	 * @var string[]
	 */
	protected $set_cookies = array();

	public function setUp(): void {
		parent::setUp();

		if ( ! function_exists( 'bp_nouveau_ajax_delete_thread' ) ) {
			require_once buddypress()->plugin_dir . 'bp-templates/bp-nouveau/includes/messages/ajax.php';
		}

		if ( ! class_exists( 'BP_Core_Members_Switching' ) || ! defined( 'BP_MEMBER_SWITCHING_OLDUSER_COOKIE' ) ) {
			$this->markTestSkipped( 'Members switching is not loaded.' );
		}

		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter( 'wp_die_ajax_handler', array( $this, 'get_die_handler' ), 1 );
	}

	public function tearDown(): void {
		foreach ( $this->set_cookies as $name ) {
			unset( $_COOKIE[ $name ] );
		}
		$this->set_cookies = array();

		unset( $_POST['nonce'], $_POST['id'] );

		remove_filter( 'wp_doing_ajax', '__return_true' );
		remove_filter( 'wp_die_ajax_handler', array( $this, 'get_die_handler' ), 1 );

		parent::tearDown();
	}

	/**
	 * Return the die handler that turns wp_send_json_*() into an exception.
	 *
	 * @return callable
	 */
	public function get_die_handler() {
		return array( $this, 'die_handler' );
	}

	/**
	 * Throw instead of exiting.
	 *
	 * @param string $message Die message.
	 *
	 * @throws WPAjaxDieContinueException Always.
	 */
	public function die_handler( $message ) {
		throw new WPAjaxDieContinueException( (string) $message );
	}

	/**
	 * Call the AJAX handler and return the decoded JSON response.
	 *
	 * @param int $thread_id Thread ID.
	 *
	 * @return array
	 */
	protected function call_delete_thread( $thread_id ) {
		$_POST['nonce'] = wp_create_nonce( 'bp_nouveau_messages' );
		$_POST['id']    = (string) $thread_id;

		ob_start();
		try {
			bp_nouveau_ajax_delete_thread();
		} catch ( WPAjaxDieContinueException $e ) {
			unset( $e );
		}
		$output = ob_get_clean();

		return (array) json_decode( $output, true );
	}

	/**
	 * Put a value in a cookie for the current request.
	 *
	 * @param string $name  Cookie name.
	 * @param string $value Cookie value.
	 */
	protected function set_cookie( $name, $value ) {
		$_COOKIE[ $name ]    = $value;
		$this->set_cookies[] = $name;
	}

	/**
	 * Set the members-switching cookies as if `$old_user_id` had switched into the current user.
	 *
	 * @param int $old_user_id User ID placed in the switching cookies.
	 * @param bool $with_auth  Whether to also set the switching auth cookie.
	 */
	protected function set_switching_cookies( $old_user_id, $with_auth = true ) {
		$expiration = time() + HOUR_IN_SECONDS;

		$this->set_cookie( BP_MEMBER_SWITCHING_OLDUSER_COOKIE, wp_generate_auth_cookie( $old_user_id, $expiration, 'logged_in' ) );

		if ( $with_auth ) {
			$secure = BP_Core_Members_Switching::secure_auth_cookie();
			$name   = $secure ? BP_MEMBER_SWITCHING_SECURE_COOKIE : BP_MEMBER_SWITCHING_COOKIE;
			$scheme = $secure ? 'secure_auth' : 'auth';

			$this->set_cookie( $name, wp_json_encode( array( wp_generate_auth_cookie( $old_user_id, $expiration, $scheme ) ) ) );
		}
	}

	/**
	 * Create a two-member thread.
	 *
	 * @param int $sender    Sender ID.
	 * @param int $recipient Recipient ID.
	 *
	 * @return int Thread ID.
	 */
	protected function create_thread( $sender, $recipient ) {
		$message = self::factory()->message->create_and_get(
			array(
				'sender_id'  => $sender,
				'recipients' => array( $recipient ),
				'subject'    => 'Delete thread gate',
			)
		);

		return (int) $message->thread_id;
	}

	/**
	 * Whether a thread still has recipients.
	 *
	 * @param int $thread_id Thread ID.
	 *
	 * @return bool
	 */
	protected function thread_exists( $thread_id ) {
		global $wpdb;

		$bp = buddypress();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (bool) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$bp->messages->table_name_recipients} WHERE thread_id = %d", $thread_id ) );
	}

	public function test_member_without_switching_cookie_cannot_delete_thread() {
		$u1        = self::factory()->user->create();
		$u2        = self::factory()->user->create();
		$thread_id = $this->create_thread( $u1, $u2 );

		self::set_current_user( $u1 );

		$response = $this->call_delete_thread( $thread_id );

		$this->assertFalse( $response['success'] );
		$this->assertTrue( $this->thread_exists( $thread_id ) );
	}

	/**
	 * The member copies their own logged_in and auth cookies into the switching cookies.
	 */
	public function test_member_with_forged_self_switching_cookie_cannot_delete_thread() {
		$u1        = self::factory()->user->create();
		$u2        = self::factory()->user->create();
		$thread_id = $this->create_thread( $u1, $u2 );

		self::set_current_user( $u1 );
		$this->set_switching_cookies( $u1 );

		// The forged cookie is a valid logged_in cookie, which the old gate accepted.
		$this->assertInstanceOf( 'WP_User', BP_Core_Members_Switching::get_old_user() );

		$response = $this->call_delete_thread( $thread_id );

		$this->assertFalse( $response['success'] );
		$this->assertTrue( $this->thread_exists( $thread_id ) );
	}

	/**
	 * A switching cookie of another non-moderator member is not enough either.
	 */
	public function test_member_with_non_moderator_old_user_cannot_delete_thread() {
		$u1        = self::factory()->user->create();
		$u2        = self::factory()->user->create();
		$thread_id = $this->create_thread( $u1, $u2 );

		self::set_current_user( $u1 );
		$this->set_switching_cookies( $u2 );

		$response = $this->call_delete_thread( $thread_id );

		$this->assertFalse( $response['success'] );
		$this->assertTrue( $this->thread_exists( $thread_id ) );
	}

	/**
	 * An admin's logged_in cookie alone, without the switching auth cookie, is refused.
	 */
	public function test_moderator_old_user_without_auth_cookie_cannot_delete_thread() {
		$admin     = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$u1        = self::factory()->user->create();
		$u2        = self::factory()->user->create();
		$thread_id = $this->create_thread( $u1, $u2 );

		self::set_current_user( $u1 );
		$this->set_switching_cookies( $admin, false );

		$response = $this->call_delete_thread( $thread_id );

		$this->assertFalse( $response['success'] );
		$this->assertTrue( $this->thread_exists( $thread_id ) );
	}

	/**
	 * An admin switched into a participant's account can still delete the conversation.
	 */
	public function test_switched_admin_can_delete_thread() {
		$admin     = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$u1        = self::factory()->user->create();
		$u2        = self::factory()->user->create();
		$thread_id = $this->create_thread( $u1, $u2 );

		if ( is_multisite() ) {
			grant_super_admin( $admin );
		}

		self::set_current_user( $u1 );
		$this->set_switching_cookies( $admin );

		$response = $this->call_delete_thread( $thread_id );

		$this->assertTrue( $response['success'] );
		$this->assertFalse( $this->thread_exists( $thread_id ) );
	}

	public function test_moderator_can_delete_thread() {
		$admin     = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$u1        = self::factory()->user->create();
		$u2        = self::factory()->user->create();
		$thread_id = $this->create_thread( $u1, $u2 );

		if ( is_multisite() ) {
			grant_super_admin( $admin );
		}

		self::set_current_user( $admin );

		$response = $this->call_delete_thread( $thread_id );

		$this->assertTrue( $response['success'] );
		$this->assertFalse( $this->thread_exists( $thread_id ) );
	}

	/**
	 * Meta of every message, not only the cached newest page, is still readable by the
	 * bp_messages_thread_after_delete listeners (media, video, document and GIF cleanup).
	 */
	public function test_after_delete_listeners_read_meta_of_every_message() {
		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$u1    = self::factory()->user->create();
		$u2    = self::factory()->user->create();

		if ( is_multisite() ) {
			grant_super_admin( $admin );
		}

		$message_ids = array();
		$thread_id   = 0;

		// More messages than the 10 that BP_Messages_Thread caches.
		for ( $i = 0; $i < 12; $i++ ) {
			$args = array(
				'sender_id'  => $u1,
				'recipients' => array( $u2 ),
				'subject'    => 'Delete thread meta',
				'content'    => 'Message ' . $i,
				'date_sent'  => gmdate( 'Y-m-d H:i:s', time() - ( ( 20 - $i ) * MINUTE_IN_SECONDS ) ),
			);

			if ( $thread_id ) {
				$args['thread_id'] = $thread_id;
			}

			$message       = self::factory()->message->create_and_get( $args );
			$thread_id     = (int) $message->thread_id;
			$message_ids[] = (int) $message->id;
		}

		$oldest_id = $message_ids[0];
		bp_messages_update_meta( $oldest_id, 'bb_test_attachment_ids', '123' );

		// Start from a cold meta cache, as on a site without a persistent object cache.
		foreach ( $message_ids as $message_id ) {
			wp_cache_delete( $message_id, 'message_meta' );
		}

		$seen     = array();
		$listener = function ( $deleted_thread_id, $deleted_message_ids ) use ( &$seen, $oldest_id ) {
			$seen['ids']  = array_map( 'intval', (array) $deleted_message_ids );
			$seen['meta'] = bp_messages_get_meta( $oldest_id, 'bb_test_attachment_ids', true );
		};
		add_action( 'bp_messages_thread_after_delete', $listener, 1, 2 );

		self::set_current_user( $admin );

		$response = $this->call_delete_thread( $thread_id );

		remove_action( 'bp_messages_thread_after_delete', $listener, 1 );

		$this->assertTrue( $response['success'] );
		$this->assertContains( $oldest_id, $seen['ids'] );
		$this->assertSame( '123', $seen['meta'] );

		// The meta rows themselves are gone.
		global $wpdb;
		$bp = buddypress();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$this->assertSame( 0, (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$bp->messages->table_name_meta} WHERE message_id = %d", $oldest_id ) ) );
	}

	/**
	 * A group message sent to members individually saves the same media, video and document
	 * IDs on the message in every member's thread. Deleting one of those threads keeps the
	 * items another thread still uses and deletes the rest. The last thread deletes them.
	 */
	public function test_delete_thread_keeps_attachments_used_by_other_threads() {
		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$u1    = self::factory()->user->create();
		$u2    = self::factory()->user->create();
		$u3    = self::factory()->user->create();

		if ( is_multisite() ) {
			grant_super_admin( $admin );
		}

		$message_one = self::factory()->message->create_and_get(
			array(
				'sender_id'  => $u1,
				'recipients' => array( $u2 ),
				'subject'    => 'Group message sent individually',
			)
		);
		$message_two = self::factory()->message->create_and_get(
			array(
				'sender_id'  => $u1,
				'recipients' => array( $u3 ),
				'subject'    => 'Group message sent individually',
			)
		);
		$thread_one  = (int) $message_one->thread_id;
		$thread_two  = (int) $message_two->thread_id;

		$this->assertNotEmpty( $message_one->id );
		$this->assertNotSame( $thread_one, $thread_two );

		$items     = array();
		$item_args = function () use ( $u1 ) {
			return array(
				'attachment_id' => self::factory()->post->create( array( 'post_type' => 'attachment' ) ),
				'user_id'       => $u1,
				'privacy'       => 'message',
			);
		};

		/*
		 * Video and document ship in the BuddyBoss Add-ons plugin, which this suite does
		 * not load. Cover each type whose code is loaded; media always is.
		 */
		$types = array(
			'media'    => array( 'BP_Media', 'bp_media_add', 'bp_media_ids' ),
			'video'    => array( 'BP_Video', 'bp_video_add', 'bp_video_ids' ),
			'document' => array( 'BP_Document', 'bp_document_add', 'bp_document_ids' ),
		);
		$types = array_filter(
			$types,
			function ( $type ) {
				return class_exists( $type[0] ) && function_exists( $type[1] );
			}
		);

		foreach ( array( 'shared', 'own' ) as $key ) {
			foreach ( $types as $type => $type_args ) {
				$items[ $type ][ $key ] = call_user_func( $type_args[1], $item_args() );
			}
		}

		$meta_keys = wp_list_pluck( $types, 2 );
		foreach ( $meta_keys as $type => $meta_key ) {
			bp_messages_update_meta( $message_one->id, $meta_key, $items[ $type ]['shared'] . ',' . $items[ $type ]['own'] );
			bp_messages_update_meta( $message_two->id, $meta_key, (string) $items[ $type ]['shared'] );
		}

		$exists = function ( $type, $id ) use ( $types ) {
			$class = $types[ $type ][0];
			$item  = new $class( $id );

			return ! empty( $item->id );
		};

		self::set_current_user( $admin );

		$response = $this->call_delete_thread( $thread_one );
		$this->assertTrue( $response['success'] );

		foreach ( $meta_keys as $type => $meta_key ) {
			$this->assertTrue( $exists( $type, $items[ $type ]['shared'] ), "Shared {$type} must stay while another thread uses it." );
			$this->assertFalse( $exists( $type, $items[ $type ]['own'] ), "The deleted thread's own {$type} must be deleted." );
		}

		unset( $_POST['nonce'], $_POST['id'] );
		$response = $this->call_delete_thread( $thread_two );
		$this->assertTrue( $response['success'] );

		foreach ( $meta_keys as $type => $meta_key ) {
			$this->assertFalse( $exists( $type, $items[ $type ]['shared'] ), "The last thread using the {$type} deletes it." );
		}
	}

	/**
	 * The template helper agrees with the AJAX handler.
	 */
	public function test_user_can_delete_conversation_helper() {
		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$u1    = self::factory()->user->create();
		$u2    = self::factory()->user->create();

		if ( is_multisite() ) {
			grant_super_admin( $admin );
		}

		self::set_current_user( $u1 );
		$this->assertFalse( bb_messages_user_can_delete_conversation() );

		// Switched in by a user who cannot moderate (e.g. a role with edit_users only).
		$this->set_switching_cookies( $u2 );
		$this->assertFalse( bb_messages_user_can_delete_conversation() );

		// Switched in by an admin.
		$this->set_switching_cookies( $admin );
		$this->assertTrue( bb_messages_user_can_delete_conversation() );

		self::set_current_user( $admin );
		$this->assertTrue( bb_messages_user_can_delete_conversation() );
	}
}
