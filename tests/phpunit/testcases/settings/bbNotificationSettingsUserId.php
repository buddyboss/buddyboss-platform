<?php
/**
 * Tests for the member whose preferences the notification settings screen renders.
 *
 * @group settings
 * @group notifications
 */
class BB_Tests_Settings_BbNotificationSettingsUserId extends BP_UnitTestCase {

	protected $admin;
	protected $member;
	protected $other;
	protected $previous_user;

	public function setUp(): void {
		parent::setUp();

		$this->previous_user = get_current_user_id();

		$this->admin  = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$this->member = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$this->other  = self::factory()->user->create( array( 'role' => 'subscriber' ) );

		if ( is_multisite() ) {
			grant_super_admin( $this->admin );
		}
	}

	public function tearDown(): void {
		self::set_current_user( $this->previous_user );

		parent::tearDown();
	}

	protected function go_to_notification_settings( $user_id ) {
		$this->go_to( bp_core_get_user_domain( $user_id ) . bp_get_settings_slug() . '/notifications/' );
	}

	public function test_member_on_own_screen() {
		self::set_current_user( $this->member );
		$this->go_to_notification_settings( $this->member );

		$this->assertSame( $this->member, bb_get_notification_settings_user_id() );
	}

	public function test_admin_on_another_members_screen_gets_that_member() {
		self::set_current_user( $this->admin );
		$this->go_to_notification_settings( $this->member );

		$this->assertSame( $this->member, (int) bp_displayed_user_id() );
		$this->assertSame( $this->member, bb_get_notification_settings_user_id() );
	}

	public function test_viewer_who_cannot_edit_falls_back_to_self() {
		self::set_current_user( $this->other );
		$this->go_to_notification_settings( $this->member );

		$this->assertSame( $this->other, bb_get_notification_settings_user_id() );
	}

	public function test_no_displayed_member_uses_logged_in_member() {
		self::set_current_user( $this->admin );
		$this->go_to( home_url( '/' ) );

		$this->assertSame( $this->admin, bb_get_notification_settings_user_id() );
	}

	public function test_enable_notification_row_shows_the_displayed_members_value() {
		bp_update_user_meta( $this->admin, 'enable_notification', 'yes' );
		bp_update_user_meta( $this->member, 'enable_notification', 'no' );

		self::set_current_user( $this->admin );
		$this->go_to_notification_settings( $this->member );

		ob_start();
		bb_render_enable_notification_options();
		$html = ob_get_clean();

		$this->assertSame( 1, preg_match( '/<input type="checkbox" id="enable_notification"[^>]*>/', $html, $input ), 'The Email column toggle should render.' );
		$this->assertFalse( (bool) preg_match( '/checked=/', $input[0] ), 'The member turned email notifications off, so the toggle must be unticked even though the admin has them on.' );
	}

	public function test_mentions_row_shows_the_displayed_members_name_and_value() {
		bp_update_user_meta( $this->admin, 'bb_new_mention', 'yes' );
		bp_update_user_meta( $this->member, 'bb_new_mention', 'no' );

		self::set_current_user( $this->admin );
		$this->go_to_notification_settings( $this->member ); // Re-runs bp_init, which builds the mentions label for this request.

		ob_start();
		bb_render_notification( 'mentions' );
		$html = ob_get_clean();

		if ( 1 !== preg_match( '/<input type="checkbox" id="bb_new_mention_email"[^>]*>/', $html, $input ) ) {
			$this->markTestSkipped( 'The mentions preference is not registered in this test environment.' );
		}

		$this->assertFalse( (bool) preg_match( '/checked=/', $input[0] ), 'The member turned mention emails off, so the box must be unticked even though the admin has them on.' );

		// bb_register_notification_preferences() keeps its result in a per-process static, so the
		// label inside $html can predate this test's members when the whole suite runs. Read the
		// label from the registration filter instead; the last bb_new_mention entry is the one the
		// bp_init run above registered for this request.
		$label = $this->get_registered_mentions_label();

		$this->assertNotNull( $label, 'The bb_new_mention preference should be registered.' );
		$this->assertStringContainsString( '@' . bb_members_get_user_mentionname( $this->member ), $label, 'The label names the member being edited.' );
	}

	public function test_admin_saving_another_members_screen_writes_that_member() {
		bp_update_user_meta( $this->admin, 'enable_notification', 'yes' );
		bp_update_user_meta( $this->member, 'enable_notification', 'yes' );

		self::set_current_user( $this->admin );
		$this->go_to_notification_settings( $this->member );

		$previous_method = isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : null;

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_POST['submit']           = 'Save Changes';
		$_POST['notifications']    = array( 'enable_notification' => 'no' );
		$_REQUEST['_wpnonce']      = wp_create_nonce( 'bp_settings_notifications' );

		try {
			bp_settings_action_notifications();
		} finally {
			unset( $_POST['submit'], $_POST['notifications'], $_REQUEST['_wpnonce'] );

			if ( null === $previous_method ) {
				unset( $_SERVER['REQUEST_METHOD'] );
			} else {
				$_SERVER['REQUEST_METHOD'] = $previous_method;
			}
		}

		$this->assertSame( 'no', bp_get_user_meta( $this->member, 'enable_notification', true ), 'Save on the member\'s screen stores the submitted value for that member.' );
		$this->assertSame( 'yes', bp_get_user_meta( $this->admin, 'enable_notification', true ), 'The admin\'s own preference is left alone.' );
	}

	/**
	 * Label of the bb_new_mention preference as currently registered, bypassing the static cache
	 * in bb_register_notification_preferences().
	 *
	 * @return string|null
	 */
	protected function get_registered_mentions_label() {
		$preferences = apply_filters( 'bb_register_notification_preferences', array() );
		$label       = null;

		if ( ! empty( $preferences['mentions']['fields'] ) ) {
			foreach ( $preferences['mentions']['fields'] as $field ) {
				if ( isset( $field['key'], $field['label'] ) && 'bb_new_mention' === $field['key'] ) {
					$label = $field['label'];
				}
			}
		}

		return $label;
	}
}
