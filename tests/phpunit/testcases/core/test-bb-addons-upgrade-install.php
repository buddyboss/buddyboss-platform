<?php
/**
 * Tests for the PROD-10242 BuddyBoss Addons upgrade-install migration
 * (functions in bp-core-update.php).
 *
 * Covers the pure-logic / early-return paths that do not require a live
 * Mothership call: the entitlement short-circuit seam, the already-active
 * no-op (which must write no feature settings), and the not-entitled skip.
 *
 * @group core
 * @group bb_addons_upgrade_install
 */
class BB_Addons_Upgrade_Install_Test extends WP_UnitTestCase {

	/**
	 * The seven feature-settings option keys the migration must never write.
	 *
	 * @var string[]
	 */
	protected $guarded_keys = array(
		'bb_reaction_mode',
		'bb-enable-sso',
		'bb-additional-sso-name',
		'bb-additional-sso-profile-picture',
		'bb-sso-reg-options',
		'boss_custom_login',
		'_bb_enable_activity_post_polls',
	);

	/**
	 * The short-circuit seam overrides the entitlement decision before any
	 * Mothership call, in both directions.
	 */
	public function test_entitlement_pre_filter_override() {
		add_filter( 'bb_pre_is_entitled_to_addons', '__return_true' );
		$this->assertTrue( bb_is_entitled_to_addons(), 'pre-filter true must force entitled' );
		remove_filter( 'bb_pre_is_entitled_to_addons', '__return_true' );

		add_filter( 'bb_pre_is_entitled_to_addons', '__return_false' );
		$this->assertFalse( bb_is_entitled_to_addons(), 'pre-filter false must force not-entitled' );
		remove_filter( 'bb_pre_is_entitled_to_addons', '__return_false' );
	}

	/**
	 * Already-active add-on: the migration short-circuits and writes none of the
	 * seven feature-settings keys (the §3C settings-preservation guarantee).
	 */
	public function test_migration_no_op_when_already_active_writes_no_settings() {
		update_option( 'active_plugins', array( 'buddyboss-addons/buddyboss-addons.php' ) );

		$written = array();
		$callbacks = array();
		foreach ( $this->guarded_keys as $key ) {
			$cb = function ( $value ) use ( &$written, $key ) {
				$written[] = $key;
				return $value;
			};
			$callbacks[ $key ] = $cb;
			add_filter( "pre_update_option_{$key}", $cb );
			add_filter( "pre_add_option_{$key}", $cb );
		}

		bb_install_addons_bundle_on_upgrade();

		foreach ( $this->guarded_keys as $key ) {
			remove_filter( "pre_update_option_{$key}", $callbacks[ $key ] );
			remove_filter( "pre_add_option_{$key}", $callbacks[ $key ] );
		}
		delete_option( 'active_plugins' );

		$this->assertSame( array(), $written, 'migration must not write any feature-settings option' );
	}

	/**
	 * Not entitled: the migration returns without installing or activating.
	 */
	public function test_migration_skips_when_not_entitled() {
		delete_option( 'active_plugins' );
		add_filter( 'bb_pre_is_entitled_to_addons', '__return_false' );

		bb_install_addons_bundle_on_upgrade();

		remove_filter( 'bb_pre_is_entitled_to_addons', '__return_false' );

		$active = (array) get_option( 'active_plugins', array() );
		$this->assertNotContains( 'buddyboss-addons/buddyboss-addons.php', $active, 'non-entitled site must not activate the add-on' );
	}
}
