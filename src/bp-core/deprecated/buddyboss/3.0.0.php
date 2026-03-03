<?php

/**
 * Deprecated functions.
 *
 * @since      BuddyBoss [BBVERSION]
 * @deprecated BuddyBoss 3.0.0
 */

// ──────────────────────────────────────────────────────────────────────────────
// Media Settings 2.0 deprecated hook compatibility.
// Media is a "super-feature" wrapping bp-media (photos), bp-video, bp-document.
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Fire the legacy `bp_admin_setting_media_register_fields` hook after
 * Settings 2.0 finishes registering media fields.
 *
 * The original hook passed a `BP_Admin_Setting_Media` instance. Settings 2.0
 * no longer uses that class, so a no-op stub is passed to satisfy callbacks
 * (e.g. Pro access-control) that call add_section()/add_field() on the argument.
 *
 * @since      BuddyBoss 1.2.6
 * @deprecated BuddyBoss [BBVERSION] Use {@see 'bb_media_after_register_settings_fields'} instead.
 */
add_action(
	'bb_media_after_register_settings_fields',
	static function () {
		do_action_deprecated(
			'bp_admin_setting_media_register_fields',
			array(
				new class() {
					/**
					 * No-op stub for BP_Admin_Setting_tab::add_section().
					 *
					 * @param mixed ...$args Ignored.
					 */
					public function add_section( ...$args ) {
					} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found

					/**
					 * No-op stub for BP_Admin_Setting_tab::add_field().
					 *
					 * @param mixed ...$args Ignored.
					 */
					public function add_field( ...$args ) {
					} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
				},
			),
			'BuddyBoss [BBVERSION]',
			'bb_media_after_register_settings_fields'
		);
	}
);

/**
 * Fire the legacy `bp_admin_setting_video_register_fields` hook after
 * Settings 2.0 finishes registering media fields.
 *
 * The original hook passed a `BP_Admin_Setting_Video` instance. Settings 2.0
 * no longer uses that class, so a no-op stub is passed to satisfy callbacks
 * that call add_section()/add_field() on the argument.
 *
 * @since      BuddyBoss 1.7.0
 * @deprecated BuddyBoss [BBVERSION] Use {@see 'bb_media_after_register_settings_fields'} instead.
 */
add_action(
	'bb_media_after_register_settings_fields',
	static function () {
		do_action_deprecated(
			'bp_admin_setting_video_register_fields',
			array(
				new class() {
					/**
					 * No-op stub for BP_Admin_Setting_tab::add_section().
					 *
					 * @param mixed ...$args Ignored.
					 */
					public function add_section( ...$args ) {
					} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found

					/**
					 * No-op stub for BP_Admin_Setting_tab::add_field().
					 *
					 * @param mixed ...$args Ignored.
					 */
					public function add_field( ...$args ) {
					} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
				},
			),
			'BuddyBoss [BBVERSION]',
			'bb_media_after_register_settings_fields'
		);
	}
);

/**
 * Fire the legacy `bp_admin_setting_document_register_fields` hook after
 * Settings 2.0 finishes registering media fields.
 *
 * The original hook passed a `BP_Admin_Setting_Document` instance. Settings 2.0
 * no longer uses that class, so a no-op stub is passed to satisfy callbacks
 * that call add_section()/add_field() on the argument.
 *
 * @since      BuddyBoss 1.2.6
 * @deprecated BuddyBoss [BBVERSION] Use {@see 'bb_media_after_register_settings_fields'} instead.
 */
add_action(
	'bb_media_after_register_settings_fields',
	static function () {
		do_action_deprecated(
			'bp_admin_setting_document_register_fields',
			array(
				new class() {
					/**
					 * No-op stub for BP_Admin_Setting_tab::add_section().
					 *
					 * @param mixed ...$args Ignored.
					 */
					public function add_section( ...$args ) {
					} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found

					/**
					 * No-op stub for BP_Admin_Setting_tab::add_field().
					 *
					 * @param mixed ...$args Ignored.
					 */
					public function add_field( ...$args ) {
					} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
				},
			),
			'BuddyBoss [BBVERSION]',
			'bb_media_after_register_settings_fields'
		);
	}
);

// ──────────────────────────────────────────────────────────────────────────────
// Media / Video / Document legacy settings filter bridges.
// These apply_filters hooks were used by legacy getter functions in
// bp-media-settings.php. Plugins adding custom sections/fields should now use
// bb_register_feature_section() and bb_register_feature_field().
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Fire deprecated media settings filter hooks after media fields are registered.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_deprecated_media_settings_filter_hooks() {

	/**
	 * Deprecated: bp_media_get_settings_sections.
	 *
	 * Legacy filter on the array of media (photos) setting sections.
	 *
	 * @since      BuddyBoss 1.0.0
	 * @deprecated BuddyBoss [BBVERSION] Use {@see bb_register_feature_section()} instead.
	 */
	apply_filters_deprecated(
		'bp_media_get_settings_sections',
		array( array() ),
		'BuddyBoss [BBVERSION]',
		'bb_register_feature_section()'
	);

	/**
	 * Deprecated: bp_media_get_settings_fields.
	 *
	 * Legacy filter on the full array of all media (photos) settings fields.
	 *
	 * @since      BuddyBoss 1.0.0
	 * @deprecated BuddyBoss [BBVERSION] Use {@see bb_register_feature_field()} instead.
	 */
	apply_filters_deprecated(
		'bp_media_get_settings_fields',
		array( array() ),
		'BuddyBoss [BBVERSION]',
		'bb_register_feature_field()'
	);

	/**
	 * Deprecated: bp_media_get_settings_fields_for_section.
	 *
	 * Legacy filter on fields for a specific media section ID.
	 *
	 * @since      BuddyBoss 1.0.0
	 * @deprecated BuddyBoss [BBVERSION] Use {@see bb_register_feature_field()} instead.
	 */
	apply_filters_deprecated(
		'bp_media_get_settings_fields_for_section',
		array( array(), '' ),
		'BuddyBoss [BBVERSION]',
		'bb_register_feature_field()'
	);

	/**
	 * Deprecated: bp_document_get_settings_sections.
	 *
	 * Legacy filter on the array of document setting sections.
	 *
	 * @since      BuddyBoss 1.0.0
	 * @deprecated BuddyBoss [BBVERSION] Use {@see bb_register_feature_section()} instead.
	 */
	apply_filters_deprecated(
		'bp_document_get_settings_sections',
		array( array() ),
		'BuddyBoss [BBVERSION]',
		'bb_register_feature_section()'
	);

	/**
	 * Deprecated: bp_document_get_settings_fields.
	 *
	 * Legacy filter on the full array of document settings fields.
	 *
	 * @since      BuddyBoss 1.0.0
	 * @deprecated BuddyBoss [BBVERSION] Use {@see bb_register_feature_field()} instead.
	 */
	apply_filters_deprecated(
		'bp_document_get_settings_fields',
		array( array() ),
		'BuddyBoss [BBVERSION]',
		'bb_register_feature_field()'
	);

	/**
	 * Deprecated: bp_document_get_settings_fields_for_section.
	 *
	 * Legacy filter on fields for a specific document section ID.
	 *
	 * @since      BuddyBoss 1.0.0
	 * @deprecated BuddyBoss [BBVERSION] Use {@see bb_register_feature_field()} instead.
	 */
	apply_filters_deprecated(
		'bp_document_get_settings_fields_for_section',
		array( array(), '' ),
		'BuddyBoss [BBVERSION]',
		'bb_register_feature_field()'
	);

	/**
	 * Deprecated: bp_video_get_settings_sections.
	 *
	 * Legacy filter on the array of video setting sections.
	 *
	 * @since      BuddyBoss 1.7.0
	 * @deprecated BuddyBoss [BBVERSION] Use {@see bb_register_feature_section()} instead.
	 */
	apply_filters_deprecated(
		'bp_video_get_settings_sections',
		array( array() ),
		'BuddyBoss [BBVERSION]',
		'bb_register_feature_section()'
	);

	/**
	 * Deprecated: bp_video_get_settings_fields.
	 *
	 * Legacy filter on the full array of video settings fields.
	 *
	 * @since      BuddyBoss 1.7.0
	 * @deprecated BuddyBoss [BBVERSION] Use {@see bb_register_feature_field()} instead.
	 */
	apply_filters_deprecated(
		'bp_video_get_settings_fields',
		array( array() ),
		'BuddyBoss [BBVERSION]',
		'bb_register_feature_field()'
	);

	/**
	 * Deprecated: bp_video_get_settings_fields_for_section.
	 *
	 * Legacy filter on fields for a specific video section ID.
	 *
	 * @since      BuddyBoss 1.7.0
	 * @deprecated BuddyBoss [BBVERSION] Use {@see bb_register_feature_field()} instead.
	 */
	apply_filters_deprecated(
		'bp_video_get_settings_fields_for_section',
		array( array(), '' ),
		'BuddyBoss [BBVERSION]',
		'bb_register_feature_field()'
	);
}

add_action( 'bb_media_after_register_settings_fields', 'bb_deprecated_media_settings_filter_hooks' );

// ──────────────────────────────────────────────────────────────────────────────
// Media settings save hooks (backward-compatible with Settings 1.0 tabs).
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Fire deprecated legacy setting save hooks for media backward compatibility.
 *
 * Legacy Settings 1.0 fires do_action('bp_admin_tab_setting_save', $tab_name)
 * and do_action('bp_admin_tab_setting_saved', $tab_name) when any settings tab
 * is saved. This bridge ensures those hooks still fire when media settings
 * are saved via Settings 2.0.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $feature_id Feature ID.
 * @param array  $settings   Full submitted settings.
 * @param array  $saved      Keys and values saved by core.
 */
function bb_media_fire_deprecated_save_hooks( $feature_id, $settings, $saved ) {
	if ( 'media' !== $feature_id ) {
		return;
	}

	/**
	 * Fires when media settings are saved.
	 *
	 * @since      BuddyBoss 1.0.0
	 *
	 * @param string $tab_name The tab name.
	 *
	 * @deprecated BuddyBoss [BBVERSION] Use {@see 'bb_admin_save_feature_settings_after'} with feature_id='media'.
	 */
	do_action_deprecated(
		'bp_admin_tab_setting_save',
		array( 'bp-media' ),
		'BuddyBoss [BBVERSION]',
		'bb_admin_save_feature_settings_after'
	);

	/**
	 * Fires after media settings have been saved.
	 *
	 * @since      BuddyBoss 1.0.0
	 *
	 * @param string $tab_name The tab name.
	 *
	 * @deprecated BuddyBoss [BBVERSION] Use {@see 'bb_admin_save_feature_settings_after'} with feature_id='media'.
	 */
	do_action_deprecated(
		'bp_admin_tab_setting_saved',
		array( 'bp-media' ),
		'BuddyBoss [BBVERSION]',
		'bb_admin_save_feature_settings_after'
	);

	// Fire for video tab.
	if ( bp_is_active( 'video' ) ) {

		/** This action is documented above. */
		do_action_deprecated(
			'bp_admin_tab_setting_save',
			array( 'bp-video' ),
			'BuddyBoss [BBVERSION]',
			'bb_admin_save_feature_settings_after'
		);

		/** This action is documented above. */
		do_action_deprecated(
			'bp_admin_tab_setting_saved',
			array( 'bp-video' ),
			'BuddyBoss [BBVERSION]',
			'bb_admin_save_feature_settings_after'
		);
	}

	// Fire for document tab.
	if ( bp_is_active( 'document' ) ) {

		/** This action is documented above. */
		do_action_deprecated(
			'bp_admin_tab_setting_save',
			array( 'bp-document' ),
			'BuddyBoss [BBVERSION]',
			'bb_admin_save_feature_settings_after'
		);

		/** This action is documented above. */
		do_action_deprecated(
			'bp_admin_tab_setting_saved',
			array( 'bp-document' ),
			'BuddyBoss [BBVERSION]',
			'bb_admin_save_feature_settings_after'
		);
	}
}

add_action( 'bb_admin_save_feature_settings_after', 'bb_media_fire_deprecated_save_hooks', 99, 3 );
