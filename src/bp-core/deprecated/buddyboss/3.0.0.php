<?php
/**
 * Deprecated functions.
 *
 * @since BuddyBoss [BBVERSION]
 * @deprecated BuddyBoss 3.0.0
 */

// ──────────────────────────────────────────────────────────────────────────────
// Search Settings 2.0 deprecated functions and hook compatibility.
// Legacy settings API functions were removed from bp-search-settings.php.
// Search settings are now managed by Settings 2.0 (bb-admin-settings-search.php).
// ──────────────────────────────────────────────────────────────────────────────

if ( ! function_exists( 'bp_search_get_settings_sections' ) ) {
	/**
	 * Get the Search settings sections.
	 *
	 * @since BuddyBoss 1.0.0
	 * @deprecated BuddyBoss [BBVERSION] Search settings are now managed by Settings 2.0.
	 *
	 * @return array Empty array.
	 */
	function bp_search_get_settings_sections() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Search feature (bb_admin_settings_register_search_feature)' );

		$sections = array();

		/**
		 * Filter the Search settings sections.
		 *
		 * @since BuddyBoss 1.0.0
		 * @deprecated BuddyBoss [BBVERSION] Use {@see 'bb_search_after_register_settings_fields'} to register additional settings.
		 *
		 * @param array $sections Search settings sections.
		 */
		return (array) apply_filters_deprecated( 'bp_search_get_settings_sections', array( $sections ), 'BuddyBoss [BBVERSION]', 'bb_search_after_register_settings_fields' );
	}
}

if ( ! function_exists( 'bp_search_get_settings_fields' ) ) {
	/**
	 * Get all of the settings fields.
	 *
	 * @since BuddyBoss 1.0.0
	 * @deprecated BuddyBoss [BBVERSION] Search settings are now managed by Settings 2.0.
	 *
	 * @return array Empty array.
	 */
	function bp_search_get_settings_fields() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Search feature (bb_admin_settings_register_search_feature)' );

		$fields = array();

		/**
		 * Filter all Search settings fields.
		 *
		 * @since BuddyBoss 1.0.0
		 * @deprecated BuddyBoss [BBVERSION] Use {@see 'bb_search_after_register_settings_fields'} to register additional fields.
		 *
		 * @param array $fields Search settings fields grouped by section.
		 */
		return (array) apply_filters_deprecated( 'bp_search_get_settings_fields', array( $fields ), 'BuddyBoss [BBVERSION]', 'bb_search_after_register_settings_fields' );
	}
}

if ( ! function_exists( 'bp_search_get_settings_fields_for_section' ) ) {
	/**
	 * Get settings fields for a section.
	 *
	 * @since BuddyBoss 1.0.0
	 * @deprecated BuddyBoss [BBVERSION] Search settings are now managed by Settings 2.0.
	 *
	 * @param string $section_id Section ID.
	 *
	 * @return array Empty array.
	 */
	function bp_search_get_settings_fields_for_section( $section_id = '' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Search feature (bb_admin_settings_register_search_feature)' );

		$fields = array();

		/**
		 * Filter Search settings fields for a specific section.
		 *
		 * @since BuddyBoss 1.0.0
		 * @deprecated BuddyBoss [BBVERSION] Use {@see 'bb_search_after_register_settings_fields'} to register additional fields.
		 *
		 * @param array  $fields     Settings fields for the section.
		 * @param string $section_id The section ID.
		 */
		return (array) apply_filters_deprecated( 'bp_search_get_settings_fields_for_section', array( $fields, $section_id ), 'BuddyBoss [BBVERSION]', 'bb_search_after_register_settings_fields' );
	}
}

/**
 * Fire the legacy `bp_admin_setting_search_register_fields` hook after
 * Settings 2.0 finishes registering search fields.
 *
 * The original hook passed a `BP_Admin_Setting_Search` instance. Settings 2.0
 * no longer uses that class, so a no-op stub is passed to satisfy callbacks
 * that call add_section()/add_field() on the argument.
 *
 * @since BuddyBoss 1.2.6
 * @deprecated BuddyBoss [BBVERSION] Use {@see 'bb_search_after_register_settings_fields'} instead.
 */
add_action(
	'bb_search_after_register_settings_fields',
	static function () {
		do_action_deprecated(
			'bp_admin_setting_search_register_fields',
			array(
				new class() {
					/**
					 * No-op stub for BP_Admin_Setting_tab::add_section().
					 *
					 * @param mixed ...$args Ignored.
					 */
					public function add_section( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found

					/**
					 * No-op stub for BP_Admin_Setting_tab::add_field().
					 *
					 * @param mixed ...$args Ignored.
					 */
					public function add_field( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
				},
			),
			'BuddyBoss [BBVERSION]',
			'bb_search_after_register_settings_fields'
		);
	}
);

// ──────────────────────────────────────────────────────────────────────────────
// Activity Settings 2.0 deprecated hook compatibility.
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Fire the legacy `bp_admin_setting_activity_register_fields` hook after
 * Settings 2.0 finishes registering activity fields.
 *
 * The original hook passed a `BP_Admin_Setting_Activity` instance. Settings 2.0
 * no longer uses that class, so a no-op stub is passed to satisfy callbacks
 * (e.g. Pro access-control) that call add_section()/add_field() on the argument.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss [BBVERSION] Use {@see 'bb_activity_after_register_settings_fields'} instead.
 */
add_action(
	'bb_activity_after_register_settings_fields',
	static function () {
		do_action_deprecated(
			'bp_admin_setting_activity_register_fields',
			array(
				new class() {
					/**
					 * No-op stub for BP_Admin_Setting_tab::add_section().
					 *
					 * @param mixed ...$args Ignored.
					 */
					public function add_section( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found

					/**
					 * No-op stub for BP_Admin_Setting_tab::add_field().
					 *
					 * @param mixed ...$args Ignored.
					 */
					public function add_field( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
				},
			),
			'BuddyBoss [BBVERSION]',
			'bb_activity_after_register_settings_fields'
		);
	}
);

// ──────────────────────────────────────────────────────────────────────────────
// Messaging Notifications Settings 2.0 deprecated function stubs.
// These functions were in bp-notifications-settings.php and rendered the
// Messaging Notifications section on the legacy Notifications settings page.
// Settings 2.0 replaces them with bb_admin_settings_register_messages_feature().
// ──────────────────────────────────────────────────────────────────────────────

if ( ! function_exists( 'bb_admin_setting_callback_messaging_notification_warning' ) ) {
	/**
	 * Display a warning when Pusher Live Messaging is enabled but messaging
	 * notification settings are disabled.
	 *
	 * @since BuddyBoss 2.1.4
	 * @deprecated BuddyBoss [BBVERSION] Replaced by a notice field in Settings 2.0 Messages feature.
	 */
	function bb_admin_setting_callback_messaging_notification_warning() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Messages feature (bb-messages-live-messaging-notice field)' );
	}
}

if ( ! function_exists( 'bb_messaging_notifications_tutorial' ) ) {
	/**
	 * Link to Messaging Notification tutorial.
	 *
	 * @since BuddyBoss 2.1.4
	 * @deprecated BuddyBoss [BBVERSION] Tutorial link is now in the side panel help_url in Settings 2.0.
	 */
	function bb_messaging_notifications_tutorial() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Messages feature (messaging_notifications side panel help_url)' );
	}
}

if ( ! function_exists( 'bb_admin_setting_callback_messaging_notification_fields' ) ) {
	/**
	 * Callback for rendering the hide/delay messaging notification fields.
	 *
	 * @since BuddyBoss 2.1.4
	 * @deprecated BuddyBoss [BBVERSION] Replaced by Settings 2.0 fields in bb_admin_settings_register_messages_feature().
	 */
	function bb_admin_setting_callback_messaging_notification_fields() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Messages feature (messaging_notifications panel fields)' );
	}
}

// ──────────────────────────────────────────────────────────────────────────────
// Messages Settings 2.0 deprecated hook compatibility.
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Fire the legacy `bp_admin_setting_messages_register_fields` hook after
 * Settings 2.0 finishes registering messages fields.
 *
 * The original hook passed a `BP_Admin_Setting_Messages` instance. Settings 2.0
 * no longer uses that class, so a no-op stub is passed to satisfy callbacks
 * (e.g. Pro access-control) that call add_section()/add_field() on the argument.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss [BBVERSION] Use {@see 'bb_messages_after_register_settings_fields'} instead.
 */
add_action(
	'bb_messages_after_register_settings_fields',
	static function () {
		do_action_deprecated(
			'bp_admin_setting_messages_register_fields',
			array(
				new class() {
					/**
					 * No-op stub for BP_Admin_Setting_tab::add_section().
					 *
					 * @param mixed ...$args Ignored.
					 */
					public function add_section( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found

					/**
					 * No-op stub for BP_Admin_Setting_tab::add_field().
					 *
					 * @param mixed ...$args Ignored.
					 */
					public function add_field( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
				},
			),
			'BuddyBoss [BBVERSION]',
			'bb_messages_after_register_settings_fields'
		);
	}
);

// ──────────────────────────────────────────────────────────────────────────────
// Registration Settings 2.0 deprecated hook compatibility.
// Registration migrated to Settings 2.0 — legacy BP_Admin_Setting_Registration class removed.

/**
 * Fire the legacy `bb_admin_setting_general_registration_fields` hook after
 * Settings 2.0 finishes registering registration fields.
 *
 * @since BuddyBoss 2.6.30
 * @deprecated BuddyBoss [BBVERSION] Use {@see 'bb_registration_after_general_settings_fields'} instead.
 */
add_action(
	'bb_registration_after_register_settings_fields',
	static function () {
		do_action_deprecated(
			'bb_admin_setting_general_registration_fields',
			array(
				new class() {
					public function add_section( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
					public function add_field( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
				},
			),
			'BuddyBoss [BBVERSION]',
			'bb_registration_after_general_settings_fields',
			__( 'Registration fields are now registered via bb_register_feature_field() in Settings 2.0.', 'buddyboss' )
		);
	}
);

/**
 * Fire the legacy `bp_admin_setting_registration_register_fields` hook after
 * Settings 2.0 finishes registering registration fields.
 *
 * The original hook passed a `BP_Admin_Setting_Registration` instance. Settings 2.0
 * no longer uses that class, so a no-op stub is passed to satisfy callbacks
 * that call add_section()/add_field() on the argument.
 *
 * @since BuddyPress 1.6.0
 * @deprecated BuddyBoss [BBVERSION] Use {@see 'bb_register_feature_field'} instead.
 */
add_action(
	'bb_registration_after_register_settings_fields',
	static function () {
		do_action_deprecated(
			'bp_admin_setting_registration_register_fields',
			array(
				// phpcs:ignore PHPCompatibility.Classes.NewAnonymousClasses.Found -- PHP 7.4+ required.
				new class() {
					/**
					 * No-op stub for BP_Admin_Setting_tab::add_section().
					 *
					 * @param mixed ...$args Ignored.
					 */
					public function add_section( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found

					/**
					 * No-op stub for BP_Admin_Setting_tab::add_field().
					 *
					 * @param mixed ...$args Ignored.
					 */
					public function add_field( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
				},
			),
			'BuddyBoss [BBVERSION]',
			'bb_register_feature_field'
		);
	}
);

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
 * @since BuddyBoss 1.2.6
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
					public function add_section( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found

					/**
					 * No-op stub for BP_Admin_Setting_tab::add_field().
					 *
					 * @param mixed ...$args Ignored.
					 */
					public function add_field( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
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
 * @since BuddyBoss 1.7.0
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
					public function add_section( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found

					/**
					 * No-op stub for BP_Admin_Setting_tab::add_field().
					 *
					 * @param mixed ...$args Ignored.
					 */
					public function add_field( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
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
 * @since BuddyBoss 1.2.6
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
					public function add_section( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found

					/**
					 * No-op stub for BP_Admin_Setting_tab::add_field().
					 *
					 * @param mixed ...$args Ignored.
					 */
					public function add_field( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
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

// ──────────────────────────────────────────────────────────────────────────────
// Groups Settings 2.0 deprecated hook compatibility.
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Fire the legacy `bp_admin_setting_groups_register_fields` hook after
 * Settings 2.0 finishes registering groups fields.
 *
 * The original hook passed a BP_Admin_Setting_Groups instance. Settings 2.0
 * no longer uses that class, so a no-op stub is passed to satisfy callbacks
 * that call add_section()/add_field() on the argument.
 *
 * @since BuddyBoss 1.2.6
 * @deprecated BuddyBoss [BBVERSION] Use {@see 'bb_groups_after_register_settings_fields'} and bb_register_feature_field() instead.
 */
add_action(
	'bb_groups_after_register_settings_fields',
	static function () {
		do_action_deprecated(
			'bp_admin_setting_groups_register_fields',
			array(
				new class() {
					/**
					 * No-op stub for BP_Admin_Setting_tab::add_section().
					 *
					 * @param mixed ...$args Ignored.
					 */
					public function add_section( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found

					/**
					 * No-op stub for BP_Admin_Setting_tab::add_field().
					 *
					 * @param mixed ...$args Ignored.
					 */
					public function add_field( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
				},
			),
			'BuddyBoss [BBVERSION]',
			'bb_groups_after_register_settings_fields'
		);
	}
);

// ──────────────────────────────────────────────────────────────────────────────
// Groups admin tabs (moved from bp-core-admin-functions.php).
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Output the tabs in the Groups admin area.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss [BBVERSION] Use {@see bb_register_side_panel()} to register panels in Settings 2.0.
 *
 * @param string $active_tab Name of the tab that is active. Optional.
 */
function bp_core_admin_groups_tabs( $active_tab = '' ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'bb_register_side_panel()' );

	$tabs_html    = '';
	$idle_class   = 'nav-tab';
	$active_class = 'nav-tab nav-tab-active';

	/**
	 * Filters the admin tabs to be displayed.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param array $value Array of tabs to output to the admin area.
	 */
	$tabs = apply_filters( 'bp_core_admin_groups_tabs', bp_core_get_groups_admin_tabs( $active_tab ) );

	// Loop through tabs and build navigation.
	foreach ( array_values( $tabs ) as $tab_data ) {
		$is_current = (bool) ( $tab_data['name'] == $active_tab );
		$tab_class  = $is_current ? $tab_data['class'] . ' ' . $active_class : $tab_data['class'] . ' ' . $idle_class;
		$tabs_html .= '<a href="' . esc_url( $tab_data['href'] ) . '" class="' . esc_attr( $tab_class ) . '">' . esc_html( $tab_data['name'] ) . '</a>';
	}

	echo wp_kses_post( $tabs_html );

	/**
	 * Fires after the output of tabs for the admin area.
	 *
	 * @since BuddyBoss 1.0.0
	 * @deprecated BuddyBoss [BBVERSION] No replacement in Settings 2.0. Use {@see bb_register_side_panel()}.
	 */
	do_action_deprecated( 'bp_admin_groups_tabs', array(), 'BuddyBoss [BBVERSION]', 'bb_register_side_panel()' );
}

/**
 * Register tabs for the BuddyBoss > Groups screens.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss [BBVERSION] Use {@see bb_register_side_panel()} to register panels in Settings 2.0.
 *
 * @param string $active_tab Name of the tab that is active. Optional.
 *
 * @return array
 */
function bp_core_get_groups_admin_tabs( $active_tab = '' ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'bb_register_side_panel()' );

	$tabs = array();

	$tabs[] = array(
		'href'  => bp_get_admin_url( add_query_arg( array( 'page' => 'bp-groups' ), 'admin.php' ) ),
		'name'  => __( 'All Groups', 'buddyboss' ),
		'class' => 'bp-all-groups',
	);

	/**
	 * Filters the tab data used in our wp-admin screens.
	 *
	 * @since BuddyBoss 1.0.0
	 * @deprecated BuddyBoss [BBVERSION] Use {@see bb_register_side_panel()} to register panels in Settings 2.0.
	 *
	 * @param array $tabs Tab data.
	 */
	return apply_filters_deprecated(
		'bp_core_get_groups_admin_tabs',
		array( $tabs ),
		'BuddyBoss [BBVERSION]',
		'bb_register_side_panel()'
	);
}

// ──────────────────────────────────────────────────────────────────────────────
// Legacy Groups admin page functions (moved from bp-groups-admin.php).
// Settings 2.0 replaces: listing → GroupsListScreen.js,
// edit/create → GroupEditModal.js, delete → React modal.
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Set up the Groups admin page.
 *
 * @since BuddyPress 1.7.0
 * @deprecated BuddyBoss [BBVERSION] Groups admin is now handled by Settings 2.0.
 */
function bp_groups_admin_load() {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Groups admin' );
}

/**
 * Handle save/update of screen options for the Groups component admin screen.
 *
 * @since BuddyPress 1.7.0
 * @deprecated BuddyBoss [BBVERSION] Groups admin is now handled by Settings 2.0.
 *
 * @param string $value     Will always be false unless another plugin filters it first.
 * @param string $option    Screen option name.
 * @param string $new_value Screen option form value.
 *
 * @return string|int Option value. False to abandon update.
 */
function bp_groups_admin_screen_options( $value, $option, $new_value ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Groups admin' );

	if ( 'buddyboss_page_bp_groups_per_page' !== $option && 'buddyboss_page_bp_groups_network_per_page' !== $option ) {
		return $value;
	}

	$new_value = (int) $new_value;
	if ( $new_value < 1 || $new_value > 999 ) {
		return $value;
	}

	return $new_value;
}

/**
 * Select the appropriate Groups admin screen, and output it.
 *
 * @since BuddyPress 1.7.0
 * @deprecated BuddyBoss [BBVERSION] Groups admin is now handled by Settings 2.0.
 */
function bp_groups_admin() {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Groups admin' );

	// Fallback: if the load- redirect didn't fire, show a link to Settings 2.0 instead of a blank page.
	$settings_url = function_exists( 'bb_get_settings_url' ) ? add_query_arg( 'tab', 'groups', bb_get_settings_url() ) : '';
	if ( ! empty( $settings_url ) ) {
		printf(
			'<div class="wrap"><h1>%s</h1><p>%s <a href="%s">%s</a></p></div>',
			esc_html__( 'Groups', 'buddyboss' ),
			esc_html__( 'Groups admin has moved.', 'buddyboss' ),
			esc_url( $settings_url ),
			esc_html__( 'Go to Groups Settings', 'buddyboss' )
		);
	}
}

/**
 * Display the single groups edit screen.
 *
 * @since BuddyPress 1.7.0
 * @deprecated BuddyBoss [BBVERSION] Use Settings 2.0 Group Edit Modal.
 */
function bp_groups_admin_edit() {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Group Edit Modal' );
}

/**
 * Display the single groups create screen.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss [BBVERSION] Use Settings 2.0 Group Create Modal.
 */
function bp_groups_admin_create() {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Group Create Modal' );
}

/**
 * Process the data of newly created group from the backend.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss [BBVERSION] Use Settings 2.0 {@see BB_Admin_Groups_Ajax::create_group()}.
 */
function bp_process_create_group_admin() {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'BB_Admin_Groups_Ajax::create_group()' );
}

/**
 * Display the Group delete confirmation screen.
 *
 * @since BuddyPress 1.7.0
 * @deprecated BuddyBoss [BBVERSION] Use Settings 2.0 Group Delete Modal.
 */
function bp_groups_admin_delete() {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Group Delete Modal' );
}

/**
 * Display the Groups admin index screen.
 *
 * @since BuddyPress 1.7.0
 * @deprecated BuddyBoss [BBVERSION] Use Settings 2.0 {@see GroupsListScreen.js}.
 */
function bp_groups_admin_index() {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 GroupsListScreen' );
}

/**
 * Markup for the single group's Settings metabox.
 *
 * @since BuddyPress 1.7.0
 * @deprecated BuddyBoss [BBVERSION] Use Settings 2.0 Group Edit Modal (Permissions tab).
 *
 * @param object $item Information about the current group.
 */
function bp_groups_admin_edit_metabox_settings( $item ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Group Edit Modal' );
}

/**
 * Markup for the single group's Group Hierarchy metabox.
 *
 * @since BuddyPress 1.7.0
 * @deprecated BuddyBoss [BBVERSION] Use Settings 2.0 Group Edit Modal (Integrations tab).
 *
 * @param object $item Information about the current group.
 */
function bp_groups_admin_edit_metabox_group_parent( $item ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Group Edit Modal' );
}

/**
 * Output the markup for a single group's Add New Members metabox.
 *
 * @since BuddyPress 1.7.0
 * @deprecated BuddyBoss [BBVERSION] Use Settings 2.0 Group Edit Modal (Members tab).
 *
 * @param BP_Groups_Group $item The BP_Groups_Group object for the current group.
 */
function bp_groups_admin_edit_metabox_add_new_members( $item ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Group Edit Modal' );
}

/**
 * Renders the Members metabox on single group pages.
 *
 * @since BuddyPress 1.7.0
 * @deprecated BuddyBoss [BBVERSION] Use Settings 2.0 Group Edit Modal (Members tab).
 *
 * @param BP_Groups_Group $item The BP_Groups_Group object for the current group.
 */
function bp_groups_admin_edit_metabox_members( $item ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Group Edit Modal' );
}

/**
 * Renders the Status metabox for the Groups admin edit screen.
 *
 * @since BuddyPress 1.7.0
 * @deprecated BuddyBoss [BBVERSION] Use Settings 2.0 Group Edit Modal.
 *
 * @param object $item Information about the currently displayed group.
 */
function bp_groups_admin_edit_metabox_status( $item ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Group Edit Modal' );
}

/**
 * Create pagination links out of a BP_Group_Member_Query.
 *
 * @since BuddyPress 1.8.0
 * @deprecated BuddyBoss [BBVERSION] Use Settings 2.0 Group Edit Modal (Members tab) with AJAX pagination.
 *
 * @param BP_Group_Member_Query $query       A BP_Group_Member_Query object.
 * @param string                $member_type member|mod|admin|banned.
 *
 * @return string Empty string.
 */
function bp_groups_admin_create_pagination_links( $query, $member_type ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Group Edit Modal' );

	return '';
}

/**
 * Get a set of usernames corresponding to a set of user IDs.
 *
 * @since BuddyPress 1.7.0
 * @deprecated BuddyBoss [BBVERSION] Groups admin is now handled by Settings 2.0.
 *
 * @param array $user_ids Array of user IDs.
 *
 * @return array Array of user_logins corresponding to $user_ids.
 */
function bp_groups_admin_get_usernames_from_ids( $user_ids = array() ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Groups admin' );

	$usernames = array();
	$users     = new WP_User_Query(
		array(
			'blog_id' => 0,
			'include' => $user_ids,
		)
	);

	foreach ( (array) $users->results as $user ) {
		$usernames[] = $user->user_login;
	}

	return $usernames;
}

/**
 * AJAX handler for group member autocomplete requests.
 *
 * @since BuddyPress 1.7.0
 * @deprecated BuddyBoss [BBVERSION] Use Settings 2.0 {@see BB_Admin_Groups_Ajax::member_autocomplete()}.
 */
function bp_groups_admin_autocomplete_handler() {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'BB_Admin_Groups_Ajax::member_autocomplete()' );

	wp_die( -1 );
}

// Register deprecated hooks so third-party code that triggers them gets a deprecation notice.
add_action( 'wp_ajax_bp_group_admin_member_autocomplete', 'bp_groups_admin_autocomplete_handler' );
add_action( 'admin_post_bp_create_group_admin', 'bp_process_create_group_admin' );

// Note: bp_groups_list_table_get_views is fired via do_action_deprecated() inside
// BB_Admin_Groups_Ajax::get_groups() each time the admin groups list is loaded.
// It is deprecated in favour of the 'bb_admin_groups_list_views' filter.
// See: src/bp-core/admin/classes/class-bb-admin-groups-ajax.php

// Note: bp_groups_admin_comment_row_actions is no longer fired.
// Row actions are now handled natively by the Settings 2.0 React UI.
// No consumers found in Platform or Pro — safe to drop without apply_filters_deprecated().

// Note: bp_groups_admin_row_class is no longer fired.
// Settings 2.0 renders group rows via React; CSS classes are managed client-side.

// ──────────────────────────────────────────────────────────────────────────────
// Legacy Group Type cache clearing (replaced by BB_Admin_Groups_Ajax::bb_clear_group_type_cache()).
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Clear the group_type cache when group type post is updated.
 *
 * @since BuddyBoss 2.0.0
 * @deprecated BuddyBoss [BBVERSION] Use {@see BB_Admin_Groups_Ajax::bb_clear_group_type_cache()}.
 *
 * @param int $post_id Post ID.
 */
function bb_groups_clear_group_type_cache_on_update( $post_id ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'BB_Admin_Groups_Ajax::bb_clear_group_type_cache()' );
}

// ──────────────────────────────────────────────────────────────────────────────
// Legacy Group Type CPT functions (replaced by Settings 2.0 Group Types AJAX).
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Save group type post meta box data.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss [BBVERSION] Use Settings 2.0 {@see BB_Admin_Groups_Ajax::create_group_type()} / {@see BB_Admin_Groups_Ajax::update_group_type()}.
 *
 * @param int $post_id Post ID of the group type.
 */
function bp_save_group_type_post_meta_box_data( $post_id ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'BB_Admin_Groups_Ajax::create_group_type() / BB_Admin_Groups_Ajax::update_group_type()' );
}

/**
 * Save group type role labels post meta box data.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss [BBVERSION] Use Settings 2.0 {@see BB_Admin_Groups_Ajax::create_group_type()} / {@see BB_Admin_Groups_Ajax::update_group_type()}.
 *
 * @param int $post_id Post ID of the group type.
 */
function bp_save_group_type_role_labels_post_meta_box_data( $post_id ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'BB_Admin_Groups_Ajax::create_group_type() / BB_Admin_Groups_Ajax::update_group_type()' );
}

/**
 * Register actions and filters for group types admin.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss [BBVERSION] Group types are now managed via Settings 2.0.
 */
function bp_register_group_type_sections_filters_actions() {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Group Types admin' );
}

/**
 * Process bulk group type changes from admin dropdown.
 *
 * @since BuddyPress 2.7.0
 * @deprecated BuddyBoss [BBVERSION] Use Settings 2.0 {@see BB_Admin_Groups_Ajax::bulk_action()}.
 *
 * @param string $doaction Current bulk action being processed.
 */
function bp_groups_admin_process_group_type_bulk_changes( $doaction ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'BB_Admin_Groups_Ajax::bulk_action()' );
}

/**
 * Display admin notice upon group type bulk update.
 *
 * @since BuddyPress 2.7.0
 * @deprecated BuddyBoss [BBVERSION] Use Settings 2.0 toast notifications.
 */
function bp_groups_admin_groups_type_change_notice() {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Groups admin' );
}

/**
 * Add custom columns to group type post list table.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss [BBVERSION] Group types are now listed via Settings 2.0.
 *
 * @param array $columns Existing columns.
 *
 * @return array
 */
function bp_group_type_add_column( $columns ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Group Types admin' );

	return $columns;
}

/**
 * Display data for group type columns in list table.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss [BBVERSION] Group types are now listed via Settings 2.0.
 *
 * @param string $column  Column name.
 * @param int    $post_id Post ID.
 */
function bp_group_type_show_data( $column, $post_id ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Group Types admin' );
}

/**
 * Make group type list table columns sortable.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss [BBVERSION] Group types are now listed via Settings 2.0.
 *
 * @param array $columns Sortable columns.
 *
 * @return array
 */
function bp_group_type_add_sortable_columns( $columns ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Group Types admin' );

	return $columns;
}

/**
 * Hide quick edit link from group type post row actions.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss [BBVERSION] Group types are now managed via Settings 2.0 modals.
 *
 * @param array   $actions Row actions.
 * @param WP_Post $post    Post object.
 *
 * @return array
 */
function bp_group_type_hide_quick_edit( $actions, $post ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Group Types admin' );

	return $actions;
}

/**
 * Register meta boxes for group type post type.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss [BBVERSION] Group type editing is now handled by Settings 2.0 modals.
 */
function bp_group_type_custom_meta_boxes() {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Group Types admin' );
}

/**
 * Generate group type label meta box.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss [BBVERSION] Use Settings 2.0 Group Type modal.
 *
 * @param WP_Post $post Post object.
 */
function bp_group_type_labels_meta_box( $post ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Group Types admin' );
}

/**
 * Generate group type permissions meta box.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss [BBVERSION] Use Settings 2.0 Group Type modal.
 *
 * @param WP_Post $post Post object.
 */
function bp_group_type_permissions_meta_box( $post ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Group Types admin' );
}

/**
 * Display label color selector metabox for group types.
 *
 * @since BuddyBoss 2.0.0
 * @deprecated BuddyBoss [BBVERSION] Use Settings 2.0 Group Type modal.
 *
 * @param WP_Post $post Post object.
 */
function bb_group_type_labelcolor_metabox( $post ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Group Types admin' );
}

/**
 * Display shortcode metabox for group type admin edit.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss [BBVERSION] Use Settings 2.0 Group Type modal.
 *
 * @param WP_Post $post Post object.
 */
function bp_group_shortcode_meta_box( $post ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Group Types admin' );
}

/**
 * Render Group Type metabox on the single group admin edit screen.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss [BBVERSION] Use Settings 2.0 Group Edit Modal.
 *
 * @param BP_Groups_Group|null $group Group object.
 */
function bp_groups_admin_edit_metabox_group_type( ?BP_Groups_Group $group = null ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Group Edit Modal' );
}

/**
 * Process group type update from admin edit screen.
 *
 * Hooked to `bp_group_admin_edit_after`. Bails when legacy nonce is absent.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss [BBVERSION] Use Settings 2.0 {@see BB_Admin_Groups_Ajax::save_group()}.
 *
 * @param int $group_id Group ID.
 */
function bp_groups_process_group_type_update( $group_id ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'BB_Admin_Groups_Ajax::save_group()' );
}

/**
 * Output jQuery to highlight Groups menu when on Group Types CPT page.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss [BBVERSION] Group Types are now managed via Settings 2.0.
 */
function bp_group_type_show_correct_current_menu() {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Group Types admin' );
}

/**
 * Set correct menu parent for Group Types CPT screens.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss [BBVERSION] Group Types are now managed via Settings 2.0.
 *
 * @param string $parent_file The parent file.
 *
 * @return string
 */
function bp_group_type_set_platform_tab_submenu_active( $parent_file ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Group Types admin' );

	return $parent_file;
}

/**
 * Output Groups admin tabs on the Group Types listing page.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss [BBVERSION] Group Types are now managed via Settings 2.0.
 */
function bp_groups_admin_group_type_listing_add_groups_tab() {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Group Types admin' );
}

/**
 * Add sorting filter for Group Types CPT list table.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss [BBVERSION] Group Types are now managed via Settings 2.0.
 */
function bp_group_type_add_request_filter() {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Group Types admin' );
}

/**
 * Sort Group Types CPT list table.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss [BBVERSION] Group Types are now managed via Settings 2.0.
 *
 * @param array $qv Query vars.
 *
 * @return array
 */
function bp_group_type_sort_items( $qv ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Group Types admin' );

	return $qv;
}


// ──────────────────────────────────────────────────────────────────────────────
// Members / Connections settings hooks (moved to Settings 2.0).
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Fire deprecated xprofile register_fields hook for backward compatibility.
 *
 * Legacy Settings 1.0 passed a BP_Admin_Setting_tab instance that third-party
 * plugins called ->add_section() / ->add_field() on. Settings 2.0 uses
 * bb_register_feature_field() instead. Third-party/Pro plugins should hook into
 * 'bb_members_after_register_settings_fields'.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_deprecated_xprofile_register_fields_hook() {
	if ( ! function_exists( 'bb_register_feature' ) || ! bp_is_active( 'xprofile' ) ) {
		return;
	}

	/**
	 * Fires to register xProfile tab settings fields and section.
	 *
	 * The original hook passed a BP_Admin_Setting_Xprofile instance. Settings 2.0
	 * no longer uses that class, so a no-op stub is passed to satisfy callbacks
	 * that call add_section()/add_field() on the argument.
	 *
	 * @since BuddyBoss 1.2.6
	 * @deprecated BuddyBoss [BBVERSION] Use {@see 'bb_members_after_register_settings_fields'} hook with bb_register_feature_field().
	 *
	 * @param object $deprecated No-op stub (was BP_Admin_Setting_tab instance).
	 */
	do_action_deprecated(
		'bp_admin_setting_xprofile_register_fields',
		array(
			new class() {
				/**
				 * No-op stub for BP_Admin_Setting_tab::add_section().
				 *
				 * @param mixed ...$args Ignored.
				 */
				public function add_section( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found

				/**
				 * No-op stub for BP_Admin_Setting_tab::add_field().
				 *
				 * @param mixed ...$args Ignored.
				 */
				public function add_field( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
			},
		),
		'BuddyBoss [BBVERSION]',
		'bb_members_after_register_settings_fields'
	);
}

add_action( 'bb_members_after_register_settings_fields', 'bb_deprecated_xprofile_register_fields_hook', 0 );

/**
 * Fire deprecated friends register_fields hook for backward compatibility.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_deprecated_friends_register_fields_hook() {
	if ( ! function_exists( 'bb_register_feature' ) || ! bp_is_active( 'friends' ) ) {
		return;
	}

	/**
	 * Fires to register Friends tab settings fields and section.
	 *
	 * The original hook passed a BP_Admin_Setting_Friends instance. Settings 2.0
	 * no longer uses that class, so a no-op stub is passed to satisfy callbacks
	 * that call add_section()/add_field() on the argument.
	 *
	 * @since BuddyBoss 1.2.6
	 * @deprecated BuddyBoss [BBVERSION] Use {@see 'bb_members_after_register_settings_fields'} hook with bb_register_feature_field().
	 *
	 * @param object $deprecated No-op stub (was BP_Admin_Setting_tab instance).
	 */
	do_action_deprecated(
		'bp_admin_setting_friends_register_fields',
		array(
			new class() {
				/**
				 * No-op stub for BP_Admin_Setting_tab::add_section().
				 *
				 * @param mixed ...$args Ignored.
				 */
				public function add_section( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found

				/**
				 * No-op stub for BP_Admin_Setting_tab::add_field().
				 *
				 * @param mixed ...$args Ignored.
				 */
				public function add_field( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
			},
		),
		'BuddyBoss [BBVERSION]',
		'bb_members_after_register_settings_fields'
	);
}

add_action( 'bb_members_after_register_settings_fields', 'bb_deprecated_friends_register_fields_hook', 0 );

// ──────────────────────────────────────────────────────────────────────────────
// Members settings save hooks (backward-compatible with Settings 1.0 tabs).
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Fire deprecated legacy setting save hooks for backward compatibility.
 *
 * Legacy Settings 1.0 fires do_action('bp_admin_tab_setting_save', $tab_name)
 * when any settings tab is saved. Third-party plugins may hook into this for
 * 'bp-xprofile' or 'bp-friends' tab names. This bridge ensures those hooks
 * still fire when members settings are saved via Settings 2.0.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $feature_id Feature ID.
 * @param array  $settings   Full submitted settings.
 * @param array  $saved      Keys and values saved by core.
 */
function bb_members_fire_deprecated_save_hooks( $feature_id, $settings, $saved ) {
	if ( 'members' !== $feature_id ) {
		return;
	}

	/**
	 * Fires when xprofile settings are saved.
	 *
	 * @since BuddyBoss 1.0.0
	 * @deprecated BuddyBoss [BBVERSION] Use {@see 'bb_admin_save_feature_settings_after'} with feature_id='members'.
	 *
	 * @param string $tab_name The tab name.
	 */
	do_action_deprecated(
		'bp_admin_tab_setting_save',
		array( 'bp-xprofile' ),
		'BuddyBoss [BBVERSION]',
		'bb_admin_save_feature_settings_after'
	);

	// Fire for friends tab if connection settings were included.
	if ( bp_is_active( 'friends' ) ) {

		/**
		 * Fires when friends settings are saved.
		 *
		 * @since BuddyBoss 1.0.0
		 * @deprecated BuddyBoss [BBVERSION] Use {@see 'bb_admin_save_feature_settings_after'} with feature_id='members'.
		 *
		 * @param string $tab_name The tab name.
		 */
		do_action_deprecated(
			'bp_admin_tab_setting_save',
			array( 'bp-friends' ),
			'BuddyBoss [BBVERSION]',
			'bb_admin_save_feature_settings_after'
		);
	}

	/**
	 * Fires after xprofile settings have been saved.
	 *
	 * @since BuddyBoss 1.0.0
	 * @deprecated BuddyBoss [BBVERSION] Use {@see 'bb_admin_save_feature_settings_after'} with feature_id='members'.
	 *
	 * @param string $tab_name The tab name.
	 */
	do_action_deprecated(
		'bp_admin_tab_setting_saved',
		array( 'bp-xprofile' ),
		'BuddyBoss [BBVERSION]',
		'bb_admin_save_feature_settings_after'
	);

	if ( bp_is_active( 'friends' ) ) {

		/**
		 * Fires after friends settings have been saved.
		 *
		 * @since BuddyBoss 1.0.0
		 * @deprecated BuddyBoss [BBVERSION] Use {@see 'bb_admin_save_feature_settings_after'} with feature_id='members'.
		 *
		 * @param string $tab_name The tab name.
		 */
		do_action_deprecated(
			'bp_admin_tab_setting_saved',
			array( 'bp-friends' ),
			'BuddyBoss [BBVERSION]',
			'bb_admin_save_feature_settings_after'
		);
	}
}

add_action( 'bb_admin_save_feature_settings_after', 'bb_members_fire_deprecated_save_hooks', 99, 3 );

// ──────────────────────────────────────────────────────────────────────────────
// XProfile admin rendering hooks (replaced by Settings 2.0 React UI).
// These hooks only fired in the legacy wp-admin XProfile field editor.
// The data hooks (xprofile_group_before_save, xprofile_field_before_save, etc.)
// are preserved because they fire from within BP_XProfile_Group::save() and
// BP_XProfile_Field::save() which are still used by the AJAX handler.
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Register deprecation notices for legacy XProfile admin rendering hooks.
 *
 * These hooks were used to add custom UI in the legacy XProfile field editor
 * admin page. Since Settings 2.0 uses a React interface, these rendering hooks
 * no longer fire. Third-party plugins should extend the React UI instead.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_deprecated_xprofile_admin_rendering_hooks() {
	$deprecated_hooks = array(
		'xprofile_group_admin_after_description' => __( 'XProfile group admin after description', 'buddyboss' ),
		'xprofile_group_before_submitbox'        => __( 'XProfile group before submitbox', 'buddyboss' ),
		'xprofile_group_submitbox_start'         => __( 'XProfile group submitbox start', 'buddyboss' ),
		'xprofile_group_after_submitbox'         => __( 'XProfile group after submitbox', 'buddyboss' ),
		'xprofile_field_before_contentbox'       => __( 'XProfile field before contentbox', 'buddyboss' ),
		'xprofile_field_after_contentbox'        => __( 'XProfile field after contentbox', 'buddyboss' ),
		'xprofile_field_before_submitbox'        => __( 'XProfile field before submitbox', 'buddyboss' ),
		'xprofile_field_submitbox_start'         => __( 'XProfile field submitbox start', 'buddyboss' ),
		'xprofile_field_after_submitbox'         => __( 'XProfile field after submitbox', 'buddyboss' ),
		'xprofile_field_after_sidebarbox'        => __( 'XProfile field after sidebarbox', 'buddyboss' ),
		'xprofile_field_additional_options'       => __( 'XProfile field additional options', 'buddyboss' ),
		'xprofile_admin_field_name_legend'       => __( 'XProfile admin field name legend', 'buddyboss' ),
		'xprofile_admin_field_action'            => __( 'XProfile admin field action', 'buddyboss' ),
		'xprofile_admin_group_action'            => __( 'XProfile admin group action', 'buddyboss' ),
	);

	foreach ( $deprecated_hooks as $hook => $description ) {
		if ( has_action( $hook ) ) {
			_deprecated_hook(
				$hook,
				'BuddyBoss [BBVERSION]',
				'',
				sprintf(
					/* translators: %s: hook name */
					__( 'The %s hook is no longer fired in the Settings 2.0 React admin interface. Extend the React UI via custom JavaScript instead.', 'buddyboss' ),
					$hook
				)
			);
		}
	}
}

add_action( 'admin_init', 'bb_deprecated_xprofile_admin_rendering_hooks' );

/**
 * Register deprecation notices for legacy Profile admin tab hooks.
 *
 * The `bp_core_admin_users_tabs` and `bp_core_get_users_admin_tabs` filters
 * were used to add tabs to the legacy bp-profile-setup admin page. Since
 * Settings 2.0, Profile Fields, Profile Types, Profile Search, and Profile
 * Navigation are managed via the React admin interface.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_deprecated_profile_admin_tab_hooks() {
	$deprecated_filters = array(
		'bp_core_admin_users_tabs',
		'bp_core_get_users_admin_tabs',
	);

	foreach ( $deprecated_filters as $hook ) {
		if ( has_filter( $hook ) ) {
			_deprecated_hook(
				$hook,
				'BuddyBoss [BBVERSION]',
				'bb_register_side_panel()',
				sprintf(
					/* translators: %s: hook name */
					__( 'The %s filter is no longer used. Profile admin tabs are now managed via Settings 2.0 side panels.', 'buddyboss' ),
					$hook
				)
			);
		}
	}
}

add_action( 'admin_init', 'bb_deprecated_profile_admin_tab_hooks' );

/**
 * Backward-compatible stub for legacy Profile admin tab renderer.
 *
 * The admin tab bar for bp-profile-setup has been removed in Settings 2.0.
 * This stub prevents fatal errors if third-party code or legacy model class
 * `render_admin_form()` methods call the function.
 *
 * @since BuddyBoss [BBVERSION]
 * @deprecated BuddyBoss [BBVERSION] Use Settings 2.0 side panels via bb_register_side_panel().
 *
 * @param string $active_tab Active tab slug (ignored).
 */
function bp_core_admin_users_tabs( $active_tab = '' ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'bb_register_side_panel()' );
}

/**
 * Backward-compatible stub for legacy Profile admin tabs data.
 *
 * @since BuddyBoss [BBVERSION]
 * @deprecated BuddyBoss [BBVERSION] Use Settings 2.0 side panels via bb_register_side_panel().
 *
 * @param string $active_tab Active tab slug (ignored).
 *
 * @return array Empty array.
 */
function bp_core_get_users_admin_tabs( $active_tab = '' ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'bb_register_side_panel()' );

	return array();
}

/**
 * Backward-compatible stub for legacy field type dropdown renderer.
 *
 * Called from BP_XProfile_Field::render_admin_form(). No longer needed since
 * field type selection is handled by the Settings 2.0 React modal.
 *
 * @since BuddyBoss [BBVERSION]
 * @deprecated BuddyBoss [BBVERSION]
 *
 * @param string $select_field_type Currently selected field type.
 */
function bp_xprofile_admin_form_field_types( $select_field_type = '' ) {
	_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]' );
}

// ──────────────────────────────────────────────────────────────────────────────
// Forums Settings 2.0 deprecated hook compatibility.
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Fire the legacy `bp_admin_setting_forums_register_fields` hook after
 * Settings 2.0 finishes registering forum fields.
 *
 * The original hook passed a `BP_Admin_Setting_Forums` instance. Settings 2.0
 * no longer uses that class, so a no-op stub is passed to satisfy callbacks
 * that call add_section()/add_field() on the argument.
 *
 * @since BuddyBoss 1.2.6
 * @deprecated BuddyBoss [BBVERSION] Use {@see 'bb_forums_after_register_settings_fields'} instead.
 */
add_action(
	'bb_forums_after_register_settings_fields',
	static function () {
		do_action_deprecated(
			'bp_admin_setting_forums_register_fields',
			array(
				new class() {
					/**
					 * No-op stub for BP_Admin_Setting_tab::add_section().
					 *
					 * @param mixed ...$args Ignored.
					 */
					public function add_section( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found

					/**
					 * No-op stub for BP_Admin_Setting_tab::add_field().
					 *
					 * @param mixed ...$args Ignored.
					 */
					public function add_field( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
				},
			),
			'BuddyBoss [BBVERSION]',
			'bb_forums_after_register_settings_fields'
		);
	}
);

/**
 * Fire the legacy `bp_admin_tab_setting_save` and `bp_admin_tab_setting_saved`
 * hooks when forums settings are saved via Settings 2.0.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss [BBVERSION] Use Settings 2.0 per-field sanitize/validate callbacks.
 */
add_action(
	'bb_forums_after_save_settings',
	static function () {
		do_action_deprecated(
			'bp_admin_tab_setting_save',
			array( 'bp-forums' ),
			'BuddyBoss [BBVERSION]',
			'bb_forums_after_save_settings'
		);

		do_action_deprecated(
			'bp_admin_tab_setting_saved',
			array( 'bp-forums' ),
			'BuddyBoss [BBVERSION]',
			'bb_forums_after_save_settings'
		);
	}
);

/**
 * Fire the legacy `bbp_admin_get_settings_sections` filter after Settings 2.0
 * finishes registering forum fields.
 *
 * In legacy bbPress, this filter was applied inside bbp_admin_get_settings_sections()
 * allowing third-party plugins to add settings sections to the WordPress Settings API
 * pages. Settings 2.0 no longer calls that function for registration; this deprecation
 * wrapper notifies developers to use bb_register_feature_section() instead.
 *
 * @since bbPress (r4001)
 * @deprecated BuddyBoss [BBVERSION] Use {@see bb_register_feature_section()} to register forum settings sections.
 */
add_action(
	'bb_forums_after_register_settings_fields',
	static function () {
		apply_filters_deprecated(
			'bbp_admin_get_settings_sections',
			array( array() ),
			'BuddyBoss [BBVERSION]',
			'bb_register_feature_section()'
		);
	}
);

/**
 * Fire the legacy `bbp_admin_get_settings_fields` filter after Settings 2.0
 * finishes registering forum fields.
 *
 * In legacy bbPress, this filter was applied inside bbp_admin_get_settings_fields()
 * allowing third-party plugins to add settings fields to the WordPress Settings API
 * pages. Settings 2.0 no longer calls that function for registration; this deprecation
 * wrapper notifies developers to use bb_register_feature_field() instead.
 *
 * @since bbPress (r4001)
 * @deprecated BuddyBoss [BBVERSION] Use {@see bb_register_feature_field()} to register forum settings fields.
 */
add_action(
	'bb_forums_after_register_settings_fields',
	static function () {
		apply_filters_deprecated(
			'bbp_admin_get_settings_fields',
			array( array() ),
			'BuddyBoss [BBVERSION]',
			'bb_register_feature_field()'
		);
	}
);

/**
 * Fire the legacy `bbp_admin_get_settings_fields_for_section` filter after
 * Settings 2.0 finishes registering forum fields.
 *
 * In legacy bbPress, this filter was applied inside
 * bbp_admin_get_settings_fields_for_section() allowing third-party plugins
 * to modify fields for a specific section. Settings 2.0 no longer calls that
 * function for registration; this deprecation wrapper notifies developers to
 * use bb_register_feature_field() instead.
 *
 * @since bbPress (r4001)
 * @deprecated BuddyBoss [BBVERSION] Use {@see bb_register_feature_field()} to register forum settings fields.
 */
add_action(
	'bb_forums_after_register_settings_fields',
	static function () {
		apply_filters_deprecated(
			'bbp_admin_get_settings_fields_for_section',
			array( array(), '' ),
			'BuddyBoss [BBVERSION]',
			'bb_register_feature_field()'
		);
	}
);

// ──────────────────────────────────────────────────────────────────────────────
// Forums Settings API function stubs.
// These functions were defined in bp-forums/admin/settings.php (deleted).
// Third-party code calling them directly would get a fatal error without stubs.
// ──────────────────────────────────────────────────────────────────────────────

if ( ! function_exists( 'bbp_admin_get_settings_sections' ) ) {
	/**
	 * Get the bbPress admin settings sections.
	 *
	 * @since bbPress (r4001)
	 * @deprecated BuddyBoss [BBVERSION] Forum settings are now managed by Settings 2.0.
	 *
	 * @return array Empty array.
	 */
	function bbp_admin_get_settings_sections() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Forums feature (bb_admin_settings_register_forums_feature)' );

		return array();
	}
}

if ( ! function_exists( 'bbp_admin_get_settings_fields' ) ) {
	/**
	 * Get the bbPress admin settings fields.
	 *
	 * @since bbPress (r4001)
	 * @deprecated BuddyBoss [BBVERSION] Forum settings are now managed by Settings 2.0.
	 *
	 * @return array Empty array.
	 */
	function bbp_admin_get_settings_fields() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Forums feature (bb_admin_settings_register_forums_feature)' );

		return array();
	}
}

if ( ! function_exists( 'bbp_admin_get_settings_fields_for_section' ) ) {
	/**
	 * Get the bbPress admin settings fields for a specific section.
	 *
	 * @since bbPress (r4001)
	 * @deprecated BuddyBoss [BBVERSION] Forum settings are now managed by Settings 2.0.
	 *
	 * @param string $section_id Section ID.
	 *
	 * @return array Empty array.
	 */
	function bbp_admin_get_settings_fields_for_section( $section_id = '' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Forums feature (bb_admin_settings_register_forums_feature)' );

		return array();
	}
}

// ──────────────────────────────────────────────────────────────────────────────
// Forums Settings 2.0 deprecated AJAX endpoint stubs.
// These AJAX actions were in BBP_Admin and have been replaced by
// Settings 2.0 AJAX handlers (BB_Admin_Topics_Ajax, BB_Admin_Replies_Ajax).
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Deprecated: Topic suggest AJAX handler.
 *
 * @since bbPress (r4261)
 * @deprecated BuddyBoss [BBVERSION] Use bb_admin_discussion_autocomplete instead.
 */
add_action(
	'wp_ajax_bbp_suggest_topic',
	static function () {
		_deprecated_function( 'bbp_suggest_topic AJAX action', 'BuddyBoss [BBVERSION]', 'bb_admin_discussion_autocomplete' );
		wp_send_json_error( array( 'message' => __( 'This endpoint has been deprecated. Use bb_admin_discussion_autocomplete instead.', 'buddyboss' ) ) );
	}
);

/**
 * Deprecated: Reply suggest AJAX handler.
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss [BBVERSION] Use bb_admin_reply_autocomplete instead.
 */
add_action(
	'wp_ajax_bbp_suggest_reply',
	static function () {
		_deprecated_function( 'bbp_suggest_reply AJAX action', 'BuddyBoss [BBVERSION]', 'bb_admin_reply_autocomplete' );
		wp_send_json_error( array( 'message' => __( 'This endpoint has been deprecated. Use bb_admin_reply_autocomplete instead.', 'buddyboss' ) ) );
	}
);

/**
 * Deprecated: User suggest AJAX handler.
 *
 * @since bbPress (r5014)
 * @deprecated BuddyBoss [BBVERSION] Use standard WordPress user search instead.
 */
add_action(
	'wp_ajax_bbp_suggest_user',
	static function () {
		_deprecated_function( 'bbp_suggest_user AJAX action', 'BuddyBoss [BBVERSION]', 'WordPress user search' );
		wp_send_json_error( array( 'message' => __( 'This endpoint has been deprecated.', 'buddyboss' ) ) );
	}
);

// ──────────────────────────────────────────────────────────────────────────────
// Notifications Settings 2.0 deprecated hook compatibility.
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Fire the legacy `bb_admin_setting_notifications_register_fields` hook after
 * Settings 2.0 finishes registering notification fields.
 *
 * The original hook passed a `BB_Admin_Setting_Notifications` instance. Settings 2.0
 * no longer uses that class, so a no-op stub is passed to satisfy callbacks
 * (e.g. Pro extensions) that call add_section()/add_field() on the argument.
 *
 * @since BuddyBoss [BBVERSION]
 * @deprecated BuddyBoss [BBVERSION] Use {@see 'bb_notifications_after_register_settings_fields'} instead.
 */
add_action(
	'bb_notifications_after_register_settings_fields',
	static function () {
		do_action_deprecated(
			'bb_admin_setting_notifications_register_fields',
			array(
				new class() {
					/**
					 * No-op stub for BP_Admin_Setting_tab::add_section().
					 *
					 * @param mixed ...$args Ignored.
					 */
					public function add_section( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found

					/**
					 * No-op stub for BP_Admin_Setting_tab::add_field().
					 *
					 * @param mixed ...$args Ignored.
					 */
					public function add_field( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
				},
			),
			'BuddyBoss [BBVERSION]',
			'bb_notifications_after_register_settings_fields'
		);
	}
);

/**
 * Fire the legacy `bb_notification_web_push_notification_settings` filter after
 * Settings 2.0 finishes registering web push notification fields.
 *
 * The original filter received and returned an array of bbPress-style field
 * definitions for the web push settings section. Settings 2.0 uses
 * `bb_register_feature_field()` instead, so this fires with an empty array
 * solely to trigger deprecation notices for third-party code still filtering it.
 *
 * @since BuddyBoss [BBVERSION]
 * @deprecated BuddyBoss [BBVERSION] Use {@see 'bb_notifications_web_push_after_settings_fields'} instead.
 */
add_action(
	'bb_notifications_web_push_after_settings_fields',
	static function () {
		apply_filters_deprecated(
			'bb_notification_web_push_notification_settings',
			array( array() ),
			'BuddyBoss [BBVERSION]',
			'bb_notifications_web_push_after_settings_fields'
		);
	}
);

/**
 * Fire the legacy `bp_admin_tab_setting_save` and `bp_admin_tab_setting_saved`
 * hooks when notification settings are saved via Settings 2.0.
 *
 * Follows the same pattern as `bb_media_fire_deprecated_save_hooks()` and
 * `bb_members_fire_deprecated_save_hooks()`.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $feature_id Feature ID.
 * @param array  $settings   Full submitted settings.
 * @param array  $saved      Keys and values saved by core.
 */
function bb_notifications_fire_deprecated_save_hooks( $feature_id, $settings, $saved ) {
	if ( 'notifications' !== $feature_id ) {
		return;
	}

	/**
	 * Fires before the notification settings are saved.
	 *
	 * @since BuddyBoss 1.9.3
	 * @deprecated BuddyBoss [BBVERSION] Use {@see 'bb_admin_save_feature_settings_after'} with feature_id='notifications'.
	 *
	 * @param string $tab_name The tab name.
	 */
	do_action_deprecated(
		'bp_admin_tab_setting_save',
		array( 'bp-notifications' ),
		'BuddyBoss [BBVERSION]',
		'bb_admin_save_feature_settings_after'
	);

	/**
	 * Fires after the notification settings have been saved.
	 *
	 * @since BuddyBoss 1.9.3
	 * @deprecated BuddyBoss [BBVERSION] Use {@see 'bb_admin_save_feature_settings_after'} with feature_id='notifications'.
	 *
	 * @param string $tab_name The tab name.
	 */
	do_action_deprecated(
		'bp_admin_tab_setting_saved',
		array( 'bp-notifications' ),
		'BuddyBoss [BBVERSION]',
		'bb_admin_save_feature_settings_after'
	);
}

add_action( 'bb_admin_save_feature_settings_after', 'bb_notifications_fire_deprecated_save_hooks', 99, 3 );

// ──────────────────────────────────────────────────────────────────────────────
// Deprecated notification settings public API functions.
// Removed in Settings 2.0 migration. Stubs prevent fatal errors in third-party
// code that may call these directly.
// ──────────────────────────────────────────────────────────────────────────────

if ( ! function_exists( 'bb_notification_get_settings_sections' ) ) {
	/**
	 * Get the Notification settings sections.
	 *
	 * @since BuddyBoss 1.9.3
	 * @deprecated BuddyBoss [BBVERSION] Notification settings migrated to Settings 2.0.
	 *
	 * @return array Empty array.
	 */
	function bb_notification_get_settings_sections() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 feature registration' );

		return apply_filters_deprecated(
			'bb_notification_get_settings_sections',
			array( array() ),
			'BuddyBoss [BBVERSION]',
			'Settings 2.0 feature registration'
		);
	}
}

if ( ! function_exists( 'bb_notification_get_settings_fields' ) ) {
	/**
	 * Get all the notification settings fields.
	 *
	 * @since BuddyBoss 1.9.3
	 * @deprecated BuddyBoss [BBVERSION] Notification settings migrated to Settings 2.0.
	 *
	 * @return array Empty array.
	 */
	function bb_notification_get_settings_fields() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 feature registration' );

		return apply_filters_deprecated(
			'bb_notification_get_settings_fields',
			array( array() ),
			'BuddyBoss [BBVERSION]',
			'Settings 2.0 feature registration'
		);
	}
}

if ( ! function_exists( 'bb_notification_get_settings_fields_for_section' ) ) {
	/**
	 * Get settings fields by section.
	 *
	 * @since BuddyBoss 1.9.3
	 * @deprecated BuddyBoss [BBVERSION] Notification settings migrated to Settings 2.0.
	 *
	 * @param string $section_id Section id.
	 *
	 * @return array Empty array.
	 */
	function bb_notification_get_settings_fields_for_section( $section_id = '' ) {
		_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 feature registration' );

		return apply_filters_deprecated(
			'bb_notification_get_settings_fields_for_section',
			array( array(), $section_id ),
			'BuddyBoss [BBVERSION]',
			'Settings 2.0 feature registration'
		);
	}
}

if ( ! function_exists( 'bb_activate_notification' ) ) {
	/**
	 * Render a notification type toggle checkbox.
	 *
	 * This was a rendering helper used by the legacy notification types admin UI.
	 * Settings 2.0 renders notification types via the React `notification_types`
	 * field type instead.
	 *
	 * @since BuddyBoss 1.9.3
	 * @deprecated BuddyBoss [BBVERSION] Use Settings 2.0 notification_types field type.
	 *
	 * @param array $field   Notification field definition.
	 * @param bool  $checked Whether the checkbox is checked.
	 */
	function bb_activate_notification( $field, $checked ) {
		_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 notification_types field type' );
	}
}

// ──────────────────────────────────────────────────────────────────────────────
// Moderation Settings 2.0 deprecated functions and hook compatibility.
// Legacy settings API functions were removed from bp-moderation-settings.php.
// Moderation settings are now managed by Settings 2.0 (bb-admin-settings-moderation.php).
// ──────────────────────────────────────────────────────────────────────────────

if ( ! function_exists( 'bp_moderation_get_settings_sections' ) ) {
	/**
	 * Get the Moderation settings sections.
	 *
	 * @since BuddyBoss 1.5.6
	 * @deprecated BuddyBoss [BBVERSION] Moderation settings are now managed by Settings 2.0.
	 *
	 * @return array Empty array.
	 */
	function bp_moderation_get_settings_sections() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Moderation feature (bb_admin_settings_register_moderation_feature)' );

		$sections = array();

		/**
		 * Filter the Moderation settings sections.
		 *
		 * @since BuddyBoss 1.5.6
		 * @deprecated BuddyBoss [BBVERSION] Use {@see 'bb_moderation_after_register_settings_fields'} to register additional settings.
		 *
		 * @param array $sections Moderation settings sections.
		 */
		return (array) apply_filters_deprecated( 'bp_moderation_get_settings_sections', array( $sections ), 'BuddyBoss [BBVERSION]', 'bb_moderation_after_register_settings_fields' );
	}
}

if ( ! function_exists( 'bp_moderation_get_settings_fields' ) ) {
	/**
	 * Get all of the Moderation settings fields.
	 *
	 * @since BuddyBoss 1.5.6
	 * @deprecated BuddyBoss [BBVERSION] Moderation settings are now managed by Settings 2.0.
	 *
	 * @return array Empty array.
	 */
	function bp_moderation_get_settings_fields() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Moderation feature (bb_admin_settings_register_moderation_feature)' );

		$fields = array();

		/**
		 * Filter all Moderation settings fields.
		 *
		 * @since BuddyBoss 1.5.6
		 * @deprecated BuddyBoss [BBVERSION] Use {@see 'bb_moderation_after_register_settings_fields'} to register additional fields.
		 *
		 * @param array $fields Moderation settings fields grouped by section.
		 */
		return (array) apply_filters_deprecated( 'bp_moderation_get_settings_fields', array( $fields ), 'BuddyBoss [BBVERSION]', 'bb_moderation_after_register_settings_fields' );
	}
}

if ( ! function_exists( 'bp_moderation_get_settings_fields_for_section' ) ) {
	/**
	 * Get Moderation settings fields for a section.
	 *
	 * @since BuddyBoss 1.5.6
	 * @deprecated BuddyBoss [BBVERSION] Moderation settings are now managed by Settings 2.0.
	 *
	 * @param string $section_id Section ID.
	 *
	 * @return array Empty array.
	 */
	function bp_moderation_get_settings_fields_for_section( $section_id = '' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Moderation feature (bb_admin_settings_register_moderation_feature)' );

		$fields = array();

		/**
		 * Filter Moderation settings fields for a specific section.
		 *
		 * @since BuddyBoss 1.5.6
		 * @deprecated BuddyBoss [BBVERSION] Use {@see 'bb_moderation_after_register_settings_fields'} to register additional fields.
		 *
		 * @param array  $fields     Settings fields for the section.
		 * @param string $section_id The section ID.
		 */
		return (array) apply_filters_deprecated( 'bp_moderation_get_settings_fields_for_section', array( $fields, $section_id ), 'BuddyBoss [BBVERSION]', 'bb_moderation_after_register_settings_fields' );
	}
}

/**
 * Fire the legacy `bp_admin_setting_moderation_register_fields` hook after
 * Settings 2.0 finishes registering moderation fields.
 *
 * The original hook passed a `BP_Admin_Setting_Moderation` instance. Settings 2.0
 * no longer uses that class, so a no-op stub is passed to satisfy callbacks
 * that call add_section()/add_field() on the argument.
 *
 * @since BuddyBoss 1.5.6
 * @deprecated BuddyBoss [BBVERSION] Use {@see 'bb_moderation_after_register_settings_fields'} instead.
 */
add_action(
	'bb_moderation_after_register_settings_fields',
	static function () {
		do_action_deprecated(
			'bp_admin_setting_moderation_register_fields',
			array(
				new class() {
					/**
					 * No-op stub for BP_Admin_Setting_tab::add_section().
					 *
					 * @param mixed ...$args Ignored.
					 */
					public function add_section( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found

					/**
					 * No-op stub for BP_Admin_Setting_tab::add_field().
					 *
					 * @param mixed ...$args Ignored.
					 */
					public function add_field( ...$args ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
				},
			),
			'BuddyBoss [BBVERSION]',
			'bb_moderation_after_register_settings_fields'
		);
	}
);

if ( ! function_exists( 'bp_moderation_admin_category_listing_add_tab' ) ) {
	/**
	 * Legacy navigation tab for Moderation > Reporting Categories.
	 *
	 * @since BuddyBoss 1.5.6
	 * @deprecated BuddyBoss [BBVERSION] Reporting Categories are now managed by Settings 2.0.
	 */
	function bp_moderation_admin_category_listing_add_tab() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Moderation feature (bb-admin-settings-moderation.php)' );
	}
}

// ──────────────────────────────────────────────────────────────────────────────
// Email Invites Settings 2.0 deprecated functions.
// Legacy settings render callbacks were removed from bp-core-admin-settings.php.
// Invites settings are now managed by Settings 2.0 (bb-admin-settings-invites.php).
// ──────────────────────────────────────────────────────────────────────────────

if ( ! function_exists( 'bp_admin_setting_callback_member_invite_email_subject' ) ) {
	/**
	 * Legacy render callback for the Email Subject invite setting.
	 *
	 * @since BuddyBoss 1.0.0
	 * @deprecated BuddyBoss [BBVERSION] Invites settings are now managed by Settings 2.0.
	 */
	function bp_admin_setting_callback_member_invite_email_subject() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Invites feature (bb-admin-settings-invites.php)' );
	}
}

if ( ! function_exists( 'bp_admin_setting_callback_member_invite_email_content' ) ) {
	/**
	 * Legacy render callback for the Email Content invite setting.
	 *
	 * @since BuddyBoss 1.0.0
	 * @deprecated BuddyBoss [BBVERSION] Invites settings are now managed by Settings 2.0.
	 */
	function bp_admin_setting_callback_member_invite_email_content() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Invites feature (bb-admin-settings-invites.php)' );
	}
}

if ( ! function_exists( 'bp_admin_setting_callback_member_invite_member_type' ) ) {
	/**
	 * Legacy render callback for the Set Profile Type invite setting.
	 *
	 * @since BuddyBoss 1.0.0
	 * @deprecated BuddyBoss [BBVERSION] Invites settings are now managed by Settings 2.0.
	 */
	function bp_admin_setting_callback_member_invite_member_type() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Invites feature (bb-admin-settings-invites.php)' );
	}
}

if ( ! function_exists( 'bp_admin_setting_callback_enable_send_invite_member_type' ) ) {
	/**
	 * Legacy render callback for per-profile-type invite setting.
	 *
	 * @since BuddyBoss 1.0.0
	 * @deprecated BuddyBoss [BBVERSION] Invites settings are now managed by Settings 2.0.
	 *
	 * @param array $args Field arguments.
	 */
	function bp_admin_setting_callback_enable_send_invite_member_type( $args = array() ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Invites feature (bb-admin-settings-invites.php)' );
	}
}

if ( ! function_exists( 'bp_email_invites_tutorial' ) ) {
	/**
	 * Legacy tutorial link for Email Invites section.
	 *
	 * @since BuddyBoss 1.0.0
	 * @deprecated BuddyBoss [BBVERSION] Invites settings are now managed by Settings 2.0.
	 */
	function bp_email_invites_tutorial() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Invites feature (bb-admin-settings-invites.php)' );
	}
}

// ──────────────────────────────────────────────────────────────────────────────
// Invites settings save hooks (backward-compatible with Settings 1.0 tabs).
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Fire deprecated legacy setting save hooks for invites backward compatibility.
 *
 * Legacy Settings 1.0 fires do_action('bp_admin_tab_setting_save', $tab_name)
 * and do_action('bp_admin_tab_setting_saved', $tab_name) when any settings tab
 * is saved. This bridge ensures those hooks still fire when invites settings
 * are saved via Settings 2.0.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $feature_id Feature ID.
 * @param array  $settings   Full submitted settings.
 * @param array  $saved      Keys and values saved by core.
 */
function bb_invites_fire_deprecated_save_hooks( $feature_id, $settings, $saved ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
	if ( 'invites' !== $feature_id ) {
		return;
	}

	/**
	 * Fires when invites settings are saved.
	 *
	 * @since      BuddyBoss 1.0.0
	 *
	 * @param string $tab_name The tab name.
	 *
	 * @deprecated BuddyBoss [BBVERSION] Use {@see 'bb_admin_save_feature_settings_after'} with feature_id='invites'.
	 */
	do_action_deprecated(
		'bp_admin_tab_setting_save',
		array( 'bp-invites' ),
		'BuddyBoss [BBVERSION]',
		'bb_admin_save_feature_settings_after'
	);

	/**
	 * Fires after invites settings have been saved.
	 *
	 * @since      BuddyBoss 1.0.0
	 *
	 * @param string $tab_name The tab name.
	 *
	 * @deprecated BuddyBoss [BBVERSION] Use {@see 'bb_admin_save_feature_settings_after'} with feature_id='invites'.
	 */
	do_action_deprecated(
		'bp_admin_tab_setting_saved',
		array( 'bp-invites' ),
		'BuddyBoss [BBVERSION]',
		'bb_admin_save_feature_settings_after'
	);
}

add_action( 'bb_admin_save_feature_settings_after', 'bb_invites_fire_deprecated_save_hooks', 99, 3 );

// ──────────────────────────────────────────────────────────────────────────────
// Email Templates Settings 2.0 deprecated functions.
// Legacy email admin tabs were removed from bp-core-admin-functions.php.
// Email Templates are now managed by Settings 2.0 (bb-admin-settings-emails.php).
// ──────────────────────────────────────────────────────────────────────────────

if ( ! function_exists( 'bp_core_admin_emails_tabs' ) ) {
	/**
	 * Legacy admin tab output for BuddyBoss > Emails screens.
	 *
	 * @since BuddyBoss 1.0.0
	 * @deprecated BuddyBoss [BBVERSION] Email Templates are now managed by Settings 2.0.
	 *
	 * @param string $active_tab Name of the active tab.
	 */
	function bp_core_admin_emails_tabs( $active_tab = '' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Emails feature (bb-admin-settings-emails.php)' );
	}
}

if ( ! function_exists( 'bp_core_get_emails_admin_tabs' ) ) {
	/**
	 * Legacy tab data builder for BuddyBoss > Emails screens.
	 *
	 * @since BuddyBoss 1.0.0
	 * @deprecated BuddyBoss [BBVERSION] Email Templates are now managed by Settings 2.0.
	 *
	 * @param string $active_tab Name of the active tab.
	 * @return array Empty array (legacy data no longer applicable).
	 */
	function bp_core_get_emails_admin_tabs( $active_tab = '' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Emails feature (bb-admin-settings-emails.php)' );
		return array();
	}
}

/**
 * Fire deprecated bp_admin_tab_setting_save/saved hooks for the legacy bp-registration tab.
 *
 * Follows the same pattern as `bb_advanced_fire_deprecated_save_hooks()`.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $feature_id Feature ID.
 * @param array  $settings   Full submitted settings.
 * @param array  $saved      Keys and values saved by core.
 */
function bb_registration_fire_deprecated_save_hooks( $feature_id, $settings, $saved ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
	if ( 'registration' !== $feature_id ) {
		return;
	}

	/**
	 * Fires when registration settings are saved (legacy bridge).
	 *
	 * @since      BuddyBoss 1.0.0
	 * @deprecated BuddyBoss [BBVERSION] Use {@see 'bb_admin_save_feature_settings_after'} with feature_id='registration'.
	 *
	 * @param string $tab_name Tab name.
	 */
	do_action_deprecated( 'bp_admin_tab_setting_save', array( 'bp-registration' ), 'BuddyBoss [BBVERSION]', 'bb_admin_save_feature_settings_after' );

	/**
	 * Fires after registration settings have been saved (legacy bridge).
	 *
	 * @since      BuddyBoss 1.0.0
	 * @deprecated BuddyBoss [BBVERSION] Use {@see 'bb_admin_save_feature_settings_after'} with feature_id='registration'.
	 *
	 * @param string $tab_name Tab name.
	 */
	do_action_deprecated( 'bp_admin_tab_setting_saved', array( 'bp-registration' ), 'BuddyBoss [BBVERSION]', 'bb_admin_save_feature_settings_after' );
}
add_action( 'bb_admin_save_feature_settings_after', 'bb_registration_fire_deprecated_save_hooks', 99, 3 );

// ──────────────────────────────────────────────────────────────────────────────
// Registration Settings 2.0 deprecated render callbacks.
// Legacy render callbacks were removed from bp-core-admin-settings.php.
// Registration settings are now managed by Settings 2.0 (bb-admin-settings-registration.php).
// ──────────────────────────────────────────────────────────────────────────────

if ( ! function_exists( 'bp_admin_setting_callback_register' ) ) {
	/**
	 * Legacy render callback for the Enable Registration checkbox.
	 *
	 * @since BuddyPress 1.6.0
	 * @deprecated BuddyBoss [BBVERSION] Registration settings are now managed by Settings 2.0.
	 */
	function bp_admin_setting_callback_register() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Registration feature (bb-admin-settings-registration.php)' );
	}
}

if ( ! function_exists( 'bp_admin_setting_callback_register_show_confirm_email' ) ) {
	/**
	 * Legacy render callback for the Confirm Email checkbox.
	 *
	 * @since BuddyBoss 1.1.6
	 * @deprecated BuddyBoss [BBVERSION] Registration settings are now managed by Settings 2.0.
	 */
	function bp_admin_setting_callback_register_show_confirm_email() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Registration feature (bb-admin-settings-registration.php)' );
	}
}

if ( ! function_exists( 'bb_admin_setting_callback_register_show_legal_agreement' ) ) {
	/**
	 * Legacy render callback for the Legal Agreement checkbox.
	 *
	 * @since BuddyBoss 1.5.8.3
	 * @deprecated BuddyBoss [BBVERSION] Registration settings are now managed by Settings 2.0.
	 */
	function bb_admin_setting_callback_register_show_legal_agreement() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Registration feature (bb-admin-settings-registration.php)' );
	}
}

if ( ! function_exists( 'bp_admin_setting_callback_register_show_confirm_password' ) ) {
	/**
	 * Legacy render callback for the Confirm Password checkbox.
	 *
	 * @since BuddyBoss 1.1.6
	 * @deprecated BuddyBoss [BBVERSION] Registration settings are now managed by Settings 2.0.
	 */
	function bp_admin_setting_callback_register_show_confirm_password() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Registration feature (bb-admin-settings-registration.php)' );
	}
}

if ( ! function_exists( 'bp_admin_setting_callback_register_allow_custom_registration' ) ) {
	/**
	 * Legacy render callback for the Registration Form select.
	 *
	 * @since BuddyBoss 1.2.8
	 * @deprecated BuddyBoss [BBVERSION] Registration settings are now managed by Settings 2.0.
	 */
	function bp_admin_setting_callback_register_allow_custom_registration() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Registration feature (bb-admin-settings-registration.php)' );
	}
}

if ( ! function_exists( 'bp_admin_setting_callback_register_page_url' ) ) {
	/**
	 * Legacy render callback for the Custom Registration Page URL input.
	 *
	 * @since BuddyBoss 1.2.8
	 * @deprecated BuddyBoss [BBVERSION] Registration settings are now managed by Settings 2.0.
	 */
	function bp_admin_setting_callback_register_page_url() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Registration feature (bb-admin-settings-registration.php)' );
	}
}

if ( ! function_exists( 'bp_admin_registration_setting_tutorial' ) ) {
	/**
	 * Legacy tutorial link for the Registration settings section.
	 *
	 * @since BuddyBoss 1.2.8
	 * @deprecated BuddyBoss [BBVERSION] Registration settings are now managed by Settings 2.0.
	 */
	function bp_admin_registration_setting_tutorial() {
		_deprecated_function( __FUNCTION__, 'BuddyBoss [BBVERSION]', 'Settings 2.0 Registration feature (bb-admin-settings-registration.php)' );
	}
}
