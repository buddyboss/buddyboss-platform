<?php
/**
 * Two-Factor integration: recovery-method rule and revalidation predicate.
 *
 * @since BuddyBoss [BBVERSION]
 * @package BuddyBoss\Tests
 */

// Shared base class.
require_once dirname( dirname( dirname( __DIR__ ) ) ) . '/includes/testcase-two-factor.php';

/**
 * Recovery-method rule and revalidation predicate.
 *
 * @group two-factor
 */
class BB_Tests_Two_Factor_Save_Rules extends BB_Two_Factor_UnitTestCase {

	/**
	 * Member under test.
	 *
	 * @var int
	 */
	protected $member;

	/**
	 * Create and log in the member under test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->member = $this->create_member( 'tfa_r2_test_save' );
		self::set_current_user( $this->member );
	}

	/**
	 * Submit a set of methods as the plugin's form would, placeholder included.
	 *
	 * @param string[] $providers Provider keys.
	 */
	protected function post_providers( $providers ) {
		$_POST['_two_factor_enabled_providers'] = array_merge( array( '' ), $providers );
	}

	/**
	 * Count Email as a recovery method.
	 *
	 * @param string[] $providers Recovery provider keys.
	 * @return string[]
	 */
	public static function email_is_recovery( $providers ) {
		$providers[] = 'Two_Factor_Email';

		return $providers;
	}

	/**
	 * Count nothing as a recovery method.
	 *
	 * @return string[]
	 */
	public static function no_recovery_providers() {
		return array();
	}

	/**
	 * Give the member real backup codes.
	 */
	protected function generate_backup_codes() {
		Two_Factor_Backup_Codes::get_instance()->generate_codes( get_userdata( $this->member ) );
	}

	// Recovery rule (F-4).

	/**
	 * Recovery providers default.
	 */
	public function test_recovery_providers_default() {
		$this->assertSame( array( 'Two_Factor_Backup_Codes' ), bb_two_factor_get_recovery_providers() );
	}

	/**
	 * Email only is rejected as recovery required.
	 */
	public function test_email_only_is_rejected_as_recovery_required() {
		$this->post_providers( array( 'Two_Factor_Email' ) );

		$result = bb_two_factor_validate_recovery_method( $this->member );

		$this->assertWPError( $result );
		$this->assertSame( 'bb_two_factor_recovery_required', $result->get_error_code() );
	}

	/**
	 * Totp only is rejected as recovery required.
	 */
	public function test_totp_only_is_rejected_as_recovery_required() {
		$this->post_providers( array( 'Two_Factor_Totp' ) );

		$result = bb_two_factor_validate_recovery_method( $this->member );

		$this->assertWPError( $result );
		$this->assertSame( 'bb_two_factor_recovery_required', $result->get_error_code() );
	}

	/**
	 * Email with ungenerated backup codes is rejected as not configured.
	 */
	public function test_email_with_ungenerated_backup_codes_is_rejected_as_not_configured() {
		$this->post_providers( array( 'Two_Factor_Email', 'Two_Factor_Backup_Codes' ) );

		$result = bb_two_factor_validate_recovery_method( $this->member );

		$this->assertWPError( $result );
		$this->assertSame( 'bb_two_factor_recovery_not_configured', $result->get_error_code() );
	}

	/**
	 * Email with generated backup codes is accepted.
	 */
	public function test_email_with_generated_backup_codes_is_accepted() {
		$this->generate_backup_codes();
		$this->post_providers( array( 'Two_Factor_Email', 'Two_Factor_Backup_Codes' ) );

		$this->assertTrue( bb_two_factor_validate_recovery_method( $this->member ) );
	}

	/**
	 * Generated codes not ticked is still rejected.
	 */
	public function test_generated_codes_not_ticked_is_still_rejected() {
		$this->generate_backup_codes();
		$this->post_providers( array( 'Two_Factor_Email' ) );

		$result = bb_two_factor_validate_recovery_method( $this->member );

		$this->assertWPError( $result );
		$this->assertSame( 'bb_two_factor_recovery_required', $result->get_error_code() );
	}

	/**
	 * Empty submission is accepted.
	 */
	public function test_empty_submission_is_accepted() {
		$this->post_providers( array() );

		$this->assertTrue( bb_two_factor_validate_recovery_method( $this->member ) );
	}

	/**
	 * Missing field is accepted.
	 */
	public function test_missing_field_is_accepted() {
		unset( $_POST['_two_factor_enabled_providers'] );

		$this->assertTrue( bb_two_factor_validate_recovery_method( $this->member ) );
	}

	/**
	 * Backup codes only is accepted.
	 */
	public function test_backup_codes_only_is_accepted() {
		$this->post_providers( array( 'Two_Factor_Backup_Codes' ) );

		$this->assertTrue( bb_two_factor_validate_recovery_method( $this->member ) );
	}

	/**
	 * Unknown provider only is accepted.
	 */
	public function test_unknown_provider_only_is_accepted() {
		$this->post_providers( array( 'Not_A_Provider' ) );

		$this->assertTrue( bb_two_factor_validate_recovery_method( $this->member ) );
	}

	/**
	 * Require recovery filter false accepts email only.
	 */
	public function test_require_recovery_filter_false_accepts_email_only() {
		$this->post_providers( array( 'Two_Factor_Email' ) );
		add_filter( 'bb_two_factor_require_recovery_method', '__return_false' );

		$this->assertTrue( bb_two_factor_validate_recovery_method( $this->member ) );
	}

	/**
	 * Recovery providers filter makes email count as recovery.
	 */
	public function test_recovery_providers_filter_makes_email_count_as_recovery() {
		$this->post_providers( array( 'Two_Factor_Email' ) );
		add_filter( 'bb_two_factor_recovery_providers', array( __CLASS__, 'email_is_recovery' ) );

		$this->assertTrue( bb_two_factor_validate_recovery_method( $this->member ) );
	}

	/**
	 * With no recovery providers at all the rule cannot be met, so it is waived (V-2).
	 */
	public function test_recovery_providers_filter_emptied_waives_the_rule() {
		$this->post_providers( array( 'Two_Factor_Email' ) );
		add_filter( 'bb_two_factor_recovery_providers', array( __CLASS__, 'no_recovery_providers' ) );

		$this->assertTrue( bb_two_factor_validate_recovery_method( $this->member ) );
	}

	// Recovery rule when the site does not offer Recovery Codes (V-2).

	/**
	 * Site-level provider list without Recovery Codes, as the plugin's settings store it.
	 *
	 * @return string[]
	 */
	public static function site_providers_without_backup_codes() {
		return array( 'Two_Factor_Email', 'Two_Factor_Totp' );
	}

	/**
	 * Control: with Recovery Codes offered, Email alone is still refused.
	 */
	public function test_control_email_only_refused_when_backup_codes_offered() {
		$result = bb_two_factor_check_recovery_method( $this->member, array( 'Two_Factor_Email' ) );

		$this->assertWPError( $result );
		$this->assertSame( 'bb_two_factor_recovery_required', $result->get_error_code() );
	}

	/**
	 * Recovery Codes turned off in the plugin's settings: Email can be enabled.
	 */
	public function test_email_accepted_when_site_does_not_offer_backup_codes() {
		add_filter( 'pre_option_two_factor_enabled_providers', array( __CLASS__, 'site_providers_without_backup_codes' ) );

		$this->assertArrayNotHasKey( 'Two_Factor_Backup_Codes', Two_Factor_Core::get_supported_providers_for_user( $this->member ) );
		$this->assertTrue( bb_two_factor_check_recovery_method( $this->member, array( 'Two_Factor_Email' ) ) );
	}

	/**
	 * Recovery Codes turned off in the plugin's settings: the authenticator can be enabled.
	 */
	public function test_totp_accepted_when_site_does_not_offer_backup_codes() {
		add_filter( 'pre_option_two_factor_enabled_providers', array( __CLASS__, 'site_providers_without_backup_codes' ) );

		$this->assertTrue( bb_two_factor_check_recovery_method( $this->member, array( 'Two_Factor_Totp' ) ) );
	}

	/**
	 * The save path reads the same rule: Email-only POST accepted when codes are not offered.
	 */
	public function test_save_validator_accepts_email_when_site_does_not_offer_backup_codes() {
		add_filter( 'pre_option_two_factor_enabled_providers', array( __CLASS__, 'site_providers_without_backup_codes' ) );
		$this->post_providers( array( 'Two_Factor_Email' ) );

		$this->assertTrue( bb_two_factor_validate_recovery_method( $this->member ) );
	}

	// Recovery rule with more than one recovery provider (V-3).

	/**
	 * Authenticator listed before Recovery Codes as recovery providers.
	 *
	 * @return string[]
	 */
	public static function totp_then_backup_codes() {
		return array( 'Two_Factor_Totp', 'Two_Factor_Backup_Codes' );
	}

	/**
	 * Recovery Codes listed before the authenticator as recovery providers.
	 *
	 * @return string[]
	 */
	public static function backup_codes_then_totp() {
		return array( 'Two_Factor_Backup_Codes', 'Two_Factor_Totp' );
	}

	/**
	 * A configured recovery provider passes even when an earlier-listed one is not set up.
	 */
	public function test_configured_recovery_provider_passes_regardless_of_list_order() {
		$this->generate_backup_codes();
		$set = array( 'Two_Factor_Email', 'Two_Factor_Totp', 'Two_Factor_Backup_Codes' );

		add_filter( 'bb_two_factor_recovery_providers', array( __CLASS__, 'totp_then_backup_codes' ) );
		$first = bb_two_factor_check_recovery_method( $this->member, $set );
		remove_filter( 'bb_two_factor_recovery_providers', array( __CLASS__, 'totp_then_backup_codes' ) );

		add_filter( 'bb_two_factor_recovery_providers', array( __CLASS__, 'backup_codes_then_totp' ) );
		$second = bb_two_factor_check_recovery_method( $this->member, $set );

		$this->assertTrue( $first );
		$this->assertTrue( $second );
	}

	/**
	 * Only unconfigured recovery providers ticked: refused as not configured.
	 */
	public function test_only_unconfigured_recovery_providers_is_not_configured() {
		add_filter( 'bb_two_factor_recovery_providers', array( __CLASS__, 'totp_then_backup_codes' ) );

		$result = bb_two_factor_check_recovery_method( $this->member, array( 'Two_Factor_Email', 'Two_Factor_Totp', 'Two_Factor_Backup_Codes' ) );

		$this->assertWPError( $result );
		$this->assertSame( 'bb_two_factor_recovery_not_configured', $result->get_error_code() );
	}

	// Revalidation predicate (F-6): identical to the plugin on 0.17.0.

	/**
	 * Enable Email for the member so the plugin sees two-factor in use.
	 */
	protected function enable_email_provider() {
		update_user_meta( $this->member, Two_Factor_Core::ENABLED_PROVIDERS_USER_META_KEY, array( 'Two_Factor_Email' ) );
		update_user_meta( $this->member, Two_Factor_Core::PROVIDER_USER_META_KEY, 'Two_Factor_Email' );
	}

	/**
	 * Can manage matches plugin with no providers.
	 */
	public function test_can_manage_matches_plugin_with_no_providers() {
		$this->assertFalse( Two_Factor_Core::is_user_using_two_factor( $this->member ) );

		$this->assertTrue( Two_Factor_Core::current_user_can_update_two_factor_options( 'save' ) );
		$this->assertTrue( bb_two_factor_current_user_can_manage( 'save' ) );
	}

	/**
	 * Can manage matches plugin with providers and no session marker.
	 */
	public function test_can_manage_matches_plugin_with_providers_and_no_session_marker() {
		$this->enable_email_provider();

		$this->assertTrue( Two_Factor_Core::is_user_using_two_factor( $this->member ) );
		$this->assertFalse( Two_Factor_Core::is_current_user_session_two_factor() );

		$this->assertFalse( Two_Factor_Core::current_user_can_update_two_factor_options( 'save' ) );
		$this->assertFalse( bb_two_factor_current_user_can_manage( 'save' ) );
	}

	/**
	 * Can manage matches plugin when two factor not required for user.
	 */
	public function test_can_manage_matches_plugin_when_two_factor_not_required_for_user() {
		$this->enable_email_provider();
		add_filter( 'two_factor_is_required_for_user', '__return_false' );

		$this->assertTrue( Two_Factor_Core::current_user_can_update_two_factor_options( 'save' ) );
		$this->assertTrue( bb_two_factor_current_user_can_manage( 'save' ) );
	}

	/**
	 * Can manage false when logged out.
	 */
	public function test_can_manage_false_when_logged_out() {
		self::set_current_user( 0 );

		$this->assertFalse( bb_two_factor_current_user_can_manage( 'save' ) );
	}

	/**
	 * Can manage false when feature off.
	 */
	public function test_can_manage_false_when_feature_off() {
		bp_update_option( 'bb-active-features', array( 'two-factor' => 0 ) );

		$this->assertFalse( bb_two_factor_current_user_can_manage( 'save' ) );
	}
}
