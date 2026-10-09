<?php
/**
 * Tests for the WP Fusion group settings bridge (compat/wp-fusion.php).
 *
 * @group settings
 * @group wp-fusion
 */
class BP_Tests_Settings_WpFusionGroupSettings extends BP_UnitTestCase {

	protected $group_id;

	public function setUp(): void {
		parent::setUp();

		if ( ! function_exists( 'bb_legacy_wpf_save_group_setting' ) ) {
			require_once buddypress()->plugin_dir . 'bp-core/admin/settings/compat/wp-fusion.php';
		}

		$this->group_id = $this->factory->group->create();
	}

	public function test_empty_array_clears_a_saved_field() {
		bb_legacy_wpf_save_group_setting( $this->group_id, 'tag_link', array( '12' ) );
		bb_legacy_wpf_save_group_setting( $this->group_id, 'apply_tags', array( '3' ) );

		bb_legacy_wpf_save_group_setting( $this->group_id, 'tag_link', array() );

		$settings = bb_legacy_wpf_group_settings( $this->group_id );
		$this->assertSame( array(), $settings['tag_link'] );
		$this->assertSame( array( '3' ), $settings['apply_tags'] );
	}

	public function test_meta_row_deleted_when_every_leaf_is_empty() {
		bb_legacy_wpf_save_group_setting( $this->group_id, 'apply_tags', array( '3' ) );
		bb_legacy_wpf_save_group_setting( $this->group_id, 'apply_tags', array() );

		$this->assertSame( '', groups_get_groupmeta( $this->group_id, 'wpf-settings-buddypress' ) );
	}

	public function test_scalar_leaf_is_coerced_to_array_on_read() {
		groups_update_groupmeta(
			$this->group_id,
			'wpf-settings-buddypress',
			array( 'tag_link' => '7' )
		);

		$settings = bb_legacy_wpf_group_settings( $this->group_id );
		$this->assertSame( array( '7' ), $settings['tag_link'] );
		$this->assertSame( array(), $settings['apply_tags'] );
	}

	public function test_unknown_sibling_keys_survive_a_save_unchanged() {
		groups_update_groupmeta(
			$this->group_id,
			'wpf-settings-buddypress',
			array(
				'apply_tags' => array( '1' ),
				'future_flag' => true,
			)
		);

		bb_legacy_wpf_save_group_setting( $this->group_id, 'tag_link', array( '9' ) );

		$stored = groups_get_groupmeta( $this->group_id, 'wpf-settings-buddypress' );
		$this->assertTrue( $stored['future_flag'] );
		$this->assertSame( array( '9' ), $stored['tag_link'] );
	}

	public function test_saving_does_not_touch_wpf_settings_visibility_meta() {
		groups_update_groupmeta( $this->group_id, 'wpf-settings', array( 'lock_content' => 1 ) );

		bb_legacy_wpf_save_group_setting( $this->group_id, 'apply_tags', array( '3' ) );
		bb_legacy_wpf_save_group_setting( $this->group_id, 'apply_tags', array() );

		$this->assertSame( array( 'lock_content' => 1 ), groups_get_groupmeta( $this->group_id, 'wpf-settings' ) );
	}

	public function test_invalid_group_id_writes_nothing() {
		bb_legacy_wpf_save_group_setting( 0, 'apply_tags', array( '3' ) );

		$this->assertSame( array(), bb_legacy_wpf_group_settings( 0 )['apply_tags'] );
	}

	public function test_sanitize_empty_string_returns_empty_array() {
		$this->assertSame( array(), bb_legacy_wpf_sanitize_group_tag_ids( '' ) );
	}

	public function test_sanitize_does_not_mutate_crm_tag_ids() {
		$this->assertSame(
			array( 'VIP  <b>Club</b> %20' ),
			bb_legacy_wpf_sanitize_group_tag_ids( array( 'VIP  <b>Club</b> %20' ) )
		);
	}

	public function test_sanitize_dedupes_drops_empties_and_non_scalars() {
		$this->assertSame(
			array( '1', '2' ),
			bb_legacy_wpf_sanitize_group_tag_ids( array( '1', '', '1', array( 'x' ), '2' ) )
		);
	}

	public function test_sanitize_keeps_only_last_entries_when_capped() {
		$this->assertSame( array( '3' ), bb_legacy_wpf_sanitize_group_tag_ids( array( '1', '2', '3' ), 1 ) );
	}

	public function test_wp_fusion_handler_is_a_noop_without_bp_groups_slug() {
		if ( ! class_exists( 'WPF_BuddyPress' ) ) {
			$this->markTestSkipped( 'WP Fusion is not loaded.' );
		}

		bb_legacy_wpf_save_group_setting( $this->group_id, 'tag_link', array( '9' ) );
		unset( $_POST['bp-groups-slug'], $_POST['wpf-settings-buddypress'] );

		// Same hook the Settings 2.0 AJAX save fires after saving a group.
		do_action( 'bp_group_admin_edit_after', $this->group_id );

		$settings = bb_legacy_wpf_group_settings( $this->group_id );
		$this->assertSame( array( '9' ), $settings['tag_link'] );
	}
}
