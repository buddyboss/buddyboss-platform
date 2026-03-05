<?php
/**
 * BuddyBoss Admin Settings - Media Feature Registration.
 *
 * Registers the Media feature in the Feature Registry and loads
 * all Media settings (side panels, sections, fields).
 *
 * Media is a "super-feature" that wraps three legacy components:
 * bp-media (photos), bp-video (videos), and bp-document (documents).
 * When Media is disabled, all three components are disabled.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Media feature and settings in Feature Registry.
 *
 * Registers the feature, side panels, and delegates field registration
 * to panel-specific functions.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_admin_settings_register_media_feature() {

	// =========================================================================
	// REGISTER FEATURE
	// =========================================================================

	bb_register_feature(
		'media',
		array(
			'label'              => __( 'Media Uploading', 'buddyboss' ),
			'description'        => __( 'Allow members to upload photos, videos, documents, emojis, and GIFs, and organize them into albums or folders.', 'buddyboss' ),
			'icon'               => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-image',
			),
			'license_tier'       => 'free',
			'category'           => 'community',
			'standalone'         => true,
			'is_active_callback' => function () {
				return bp_is_active( 'media' );
			},
			'settings_route'     => '/settings/media',
			'order'              => 70,
		)
	);

	// When media is disabled, only the feature card is needed (so admin can re-enable).
	// Side panels, sections, and fields depend on media functions that aren't loaded.
	if ( ! bp_is_active( 'media' ) ) {
		return;
	}

	// Load settings sub-files only when media is active.
	require_once __DIR__ . '/settings/media/callbacks.php';
	require_once __DIR__ . '/settings/media/settings-photos.php';
	require_once __DIR__ . '/settings/media/settings-videos.php';
	require_once __DIR__ . '/settings/media/settings-documents.php';
	require_once __DIR__ . '/settings/media/settings-emoji.php';
	require_once __DIR__ . '/settings/media/settings-gifs.php';
	require_once __DIR__ . '/settings/media/settings-security.php';
	require_once __DIR__ . '/settings/media/settings-access-controls.php';

	// =========================================================================
	// SIDE PANELS
	// =========================================================================

	// Side Panel 1: Photos (default).
	bb_register_side_panel(
		'media',
		'photos',
		array(
			'title'      => __( 'Photos', 'buddyboss' ),
			'icon'       => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-image',
			),
			'help_url'   => bp_get_admin_url(
				add_query_arg(
					array(
						'page'    => 'bp-help',
						'article' => 62827,
					),
					'admin.php'
				)
			),
			'order'      => 10,
			'is_default' => true,
		)
	);

	// Side Panel 2: Videos.
	bb_register_side_panel(
		'media',
		'videos',
		array(
			'title'    => __( 'Videos', 'buddyboss' ),
			'icon'     => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-video-camera',
			),
			'help_url' => bp_get_admin_url(
				add_query_arg(
					array(
						'page'    => 'bp-help',
						'article' => 124807,
					),
					'admin.php'
				)
			),
			'order'    => 20,
		)
	);

	// Side Panel 3: Documents.
	bb_register_side_panel(
		'media',
		'documents',
		array(
			'title'    => __( 'Documents', 'buddyboss' ),
			'icon'     => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-file-text',
			),
			'help_url' => bp_get_admin_url(
				add_query_arg(
					array(
						'page'    => 'bp-help',
						'article' => 87466,
					),
					'admin.php'
				)
			),
			'order'    => 30,
		)
	);

	// Side Panel 4: Emoji.
	bb_register_side_panel(
		'media',
		'emoji',
		array(
			'title'    => __( 'Emoji', 'buddyboss' ),
			'icon'     => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-smiley',
			),
			'help_url' => bp_get_admin_url(
				add_query_arg(
					array(
						'page'    => 'bp-help',
						'article' => 62828,
					),
					'admin.php'
				)
			),
			'order'    => 40,
		)
	);

	// Side Panel 5: Animated GIFs.
	bb_register_side_panel(
		'media',
		'animated_gifs',
		array(
			'title'    => __( 'Animated GIFs', 'buddyboss' ),
			'icon'     => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-gif',
			),
			'help_url' => bp_get_admin_url(
				add_query_arg(
					array(
						'page'    => 'bp-help',
						'article' => 62829,
					),
					'admin.php'
				)
			),
			'order'    => 50,
		)
	);

	// Side Panel 6: Security & Performance.
	bb_register_side_panel(
		'media',
		'security_performance',
		array(
			'title' => __( 'Security & Performance', 'buddyboss' ),
			'icon'  => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-shield-check',
			),
			'order' => 60,
		)
	);

	// Side Panel 7: Access Controls.
	bb_register_side_panel(
		'media',
		'access_controls',
		array(
			'title' => __( 'Access Controls', 'buddyboss' ),
			'icon'  => array(
				'type'  => 'font',
				'class' => 'bb-icons-rl bb-icons-rl-lock',
			),
			'order' => 70,
		)
	);

	// =========================================================================
	// PANEL FIELDS
	// =========================================================================

	// Panel 1: Photos.
	bb_media_register_photos_panel_fields();

	// Panel 2: Videos.
	bb_media_register_videos_panel_fields();

	// Panel 3: Documents.
	bb_media_register_documents_panel_fields();

	// Panel 4: Emoji.
	bb_media_register_emoji_panel_fields();

	// Panel 5: Animated GIFs.
	bb_media_register_gifs_panel_fields();

	// Panel 6: Security & Performance.
	bb_media_register_security_panel_fields();

	// Panel 7: Access Controls.
	bb_media_register_access_controls_panel_fields();

	/**
	 * Fires after all Media settings panels are registered.
	 * Allows third-party extensions to add more panels or fields.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	do_action( 'bb_media_after_register_settings_fields' );
}

add_action( 'bb_register_features', 'bb_admin_settings_register_media_feature', 20 );
