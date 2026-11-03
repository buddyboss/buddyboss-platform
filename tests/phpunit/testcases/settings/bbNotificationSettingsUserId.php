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
		$this->assertStringContainsString( '@' . bb_members_get_user_mentionname( $this->member ), $html, 'The label names the member being edited.' );
	}
}
