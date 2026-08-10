<?php

/**
 * @group core
 * @group PROD10225
 */
class BB_Tests_Core_Functions_BbIsFeatureProvided extends BP_UnitTestCase {

	public function test_unknown_feature_is_not_provided() {
		$this->assertFalse( bb_is_feature_provided( 'does-not-exist' ) );
	}

	public function test_polls_not_provided_when_no_real_provider() {
		// Base test env loads Platform only — the moved BB_Polls class is absent,
		// so polls must read as NOT provided even though a shim may define bb_load_polls().
		$this->assertFalse( class_exists( 'BB_Polls' ), 'precondition: add-on not loaded in test env' );
		$this->assertFalse( bb_is_feature_provided( 'polls' ) );
	}

	/**
	 * A deprecation shim (function present, class absent) must NOT count as a provider.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_polls_shim_is_not_a_provider() {
		if ( ! function_exists( 'bb_load_polls' ) ) {
			// Simulate Pro's shim: the function exists but returns null and no BB_Polls class.
			eval( 'function bb_load_polls() { return null; }' );
		}
		$this->assertFalse( class_exists( 'BB_Polls' ) );
		$this->assertFalse( bb_is_feature_provided( 'polls' ), 'shim must not be treated as a real provider' );
	}

	/**
	 * A real provider (moved class present) reads as provided.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_polls_provided_when_moved_class_present() {
		if ( ! class_exists( 'BB_Polls' ) ) {
			eval( 'class BB_Polls {}' ); // stand-in for the add-on / legacy Pro class
		}
		$this->assertTrue( bb_is_feature_provided( 'polls' ) );
	}

	public function test_pinned_posts_stub_is_not_a_provider() {
		// Platform ships bb_activity_pin_unpin_post() as a stub that advertises
		// bb_activity_pin_unpin_post_is_stub(); with the stub present, pinned must read NOT provided.
		if ( function_exists( 'bb_activity_pin_unpin_post' ) && function_exists( 'bb_activity_pin_unpin_post_is_stub' ) ) {
			$this->assertFalse( bb_is_feature_provided( 'pinned_posts' ) );
		} else {
			$this->markTestSkipped( 'pinned-posts stub not loaded in this env' );
		}
	}

	/**
	 * Regression test for PROD-10225.
	 *
	 * Reproduces the confirmed fatal at class-bp-activity-notification.php ~line 665:
	 * `bb_load_polls()` (Pro's deprecation shim) returns null, and the formatter used
	 * to chain `->bb_get_poll( $poll_id )` straight off that null return, producing
	 * "Call to a member function bb_get_poll() on null". The public method that reaches
	 * this line is BP_Activity_Notification::bb_render_activity_following_post_notification().
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_poll_notification_does_not_fatal_on_null_polls_provider() {
		if ( ! function_exists( 'bb_load_polls' ) ) {
			eval( 'function bb_load_polls() { return null; }' ); // Pro shim behaviour.
		}
		$this->assertFalse( class_exists( 'BB_Polls' ), 'precondition: no real polls provider loaded' );

		$user_id     = self::factory()->user->create();
		$activity_id = self::factory()->activity->create( array( 'user_id' => $user_id ) );
		bp_activity_update_meta( $activity_id, 'bb_poll_id', 999999 ); // A poll id with no provider.

		$notification_id = bp_notifications_add_notification(
			array(
				'user_id'           => $user_id,
				'item_id'           => $activity_id,
				'secondary_item_id' => $user_id,
				'component_name'    => 'activity',
				'component_action'  => 'bb_activity_following_post',
			)
		);

		$this->assertNotEmpty( $notification_id, 'precondition: notification fixture created' );

		// Call the real notification formatter that reaches the poll-fetch line — must
		// return a string, not fatal.
		$notification_instance = new BP_Activity_Notification();
		$content               = $notification_instance->bb_render_activity_following_post_notification(
			'',
			$activity_id,
			$user_id,
			1,
			'string',
			$notification_id,
			'web_push'
		);

		$this->assertInternalType( 'string', $content );
	}

	/**
	 * Task 5 regression test for PROD-10225.
	 *
	 * `bb_get_reaction_mode()` downgrades a saved 'emotions' mode to 'likes' for
	 * display when no emotion-layer provider is available. Before this fix, its
	 * third availability branch was `function_exists( 'bbp_pro_is_license_valid' )
	 * && bbp_pro_is_license_valid()` — a stale proxy after PROD-10191, since a
	 * licensed Pro no longer ships the emotion layer itself (it moved to the
	 * add-on). On a new-Pro + no-add-on + licensed site this made the check
	 * report emotions "available" with no real provider. This test pins the
	 * saved 'emotions' mode getting downgraded to 'likes' in exactly that case.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_reaction_mode_downgrades_when_licensed_pro_has_no_reactions_marker() {
		if ( ! function_exists( 'bbp_pro_is_license_valid' ) ) {
			eval( 'function bbp_pro_is_license_valid() { return true; }' ); // Simulate a licensed Pro.
		}
		$this->assertFalse( class_exists( 'BB_Reactions' ), 'precondition: add-on not loaded in test env' );
		$this->assertFalse( function_exists( 'bp_register_reaction' ), 'precondition: legacy-Pro reactions marker absent' );

		bp_update_option( 'bb_reaction_mode', 'emotions' );

		$this->assertSame( 'likes', bb_get_reaction_mode(), 'a licensed Pro with no reactions provider must not report emotions as available' );

		bp_delete_option( 'bb_reaction_mode' );
	}

	/**
	 * Counterpart to the above: a legacy Pro that still defines the
	 * `bp_register_reaction()` dormancy marker, and is licensed, must still
	 * report emotions as available (existing behaviour must be preserved).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_reaction_mode_stays_emotions_when_legacy_pro_marker_present() {
		if ( ! function_exists( 'bbp_pro_is_license_valid' ) ) {
			eval( 'function bbp_pro_is_license_valid() { return true; }' );
		}
		if ( ! function_exists( 'bp_register_reaction' ) ) {
			eval( 'function bp_register_reaction() {}' ); // Legacy-Pro-only marker.
		}
		$this->assertFalse( class_exists( 'BB_Reactions' ), 'precondition: add-on not loaded in test env' );

		bp_update_option( 'bb_reaction_mode', 'emotions' );

		$this->assertSame( 'emotions', bb_get_reaction_mode(), 'legacy Pro with the reactions marker and a valid license must still be treated as a provider' );

		bp_delete_option( 'bb_reaction_mode' );
	}

	/*
	 * Task 3 (`$is_pro_locked` amplifier fix in bb_admin_settings_get_pro_notice(),
	 * the moved-features elseif branch at ~bp-core-functions.php:10291) is a direct
	 * delegation to bb_is_feature_provided() for the polls/sso/reactions branches —
	 * no new branching logic of its own. Its shim-vs-real-provider behavior is
	 * already exercised by the Task 1 helper tests above
	 * (test_polls_shim_is_not_a_provider, test_polls_provided_when_moved_class_present,
	 * plus the analogous reactions/sso cases covered by bb_is_feature_provided()'s own
	 * switch branches). An entry-point-level test through
	 * bb_admin_settings_get_pro_notice() was attempted here and removed: in this
	 * Platform-only test bootstrap `bb_platform_pro()` never loads, so the function's
	 * earlier `elseif ( ! function_exists( 'bb_platform_pro' ) || ... )` catch-all
	 * locks every feature regardless of the moved-features branch's outcome — the
	 * entry point can't isolate this fix in this environment, so covering it here
	 * would either assert something false about the env or pass unconditionally
	 * whether or not the fix is present. Coverage for Task 3 is: the Task 1 helper
	 * tests (shim vs. real provider), plus code review of the reviewed production
	 * delegation itself.
	 */

	/**
	 * Task 6 regression test for PROD-10225 (finding S-1).
	 *
	 * `bb_get_reaction_button_settings()`'s guard required
	 * `function_exists( 'bbp_pro_is_license_valid' ) && bbp_pro_is_license_valid()` —
	 * a Pro-only function the BuddyBoss Addons plugin does not provide. On an
	 * add-on-only site (BB_Reactions loaded, emotions enabled, no Platform Pro
	 * installed at all) the guard wrongly early-returned the default Like button
	 * and discarded the admin's configured emotion icon/text. This test pins that
	 * an add-on-only site (no bbp_pro_is_license_valid at all) still gets its
	 * configured settings applied.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_reaction_button_settings_applied_on_addon_only_site() {
		$this->assertFalse( function_exists( 'bbp_pro_is_license_valid' ), 'precondition: add-on-only site has no Pro-only function' );

		if ( ! class_exists( 'BB_Reactions' ) ) {
			eval( 'class BB_Reactions {}' ); // Stand-in for the add-on's loaded reactions class.
		}

		bp_update_option( 'bb_reaction_mode', 'emotions' );
		bp_update_option(
			'bb_reactions_button',
			array(
				'icon' => 'heart',
				'text' => 'Love it',
			)
		);

		$settings = bb_get_reaction_button_settings();

		bp_delete_option( 'bb_reaction_mode' );
		bp_delete_option( 'bb_reactions_button' );

		$this->assertSame( 'heart', $settings['icon'], 'add-on-only site must get the admin-configured icon, not the "thumbs-up" default' );
		$this->assertSame( 'Love it', $settings['text'], 'add-on-only site must get the admin-configured text, not the "Like" default' );
	}
}
